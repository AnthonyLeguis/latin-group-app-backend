<?php

namespace App\Console\Commands;

use App\Models\ApplicationForm;
use App\Models\ApplicationDocument;
use App\Models\ApplicationFormHistory;
use App\Models\ContactUs;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;

class CleanupInactiveData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:cleanup-inactive 
                            {--force : Ejecutar sin confirmación}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Elimina toda la data inactiva (application forms no activas, documentos, PDFs, contact_us, clientes sin forms). Preserva usuarios admin/agent y forms activas.';

    protected $deletedCounts = [
        'forms' => 0,
        'documents' => 0,
        'history' => 0,
        'contact_us' => 0,
        'tokens' => 0,
        'pdf_files' => 0,
        'document_files' => 0,
        'clients' => 0,
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧹 Iniciando limpieza de data inactiva...');
        $this->newLine();

        // Obtener IDs de forms activos (los que se van a preservar)
        $activeFormIds = ApplicationForm::where('status', ApplicationForm::STATUS_ACTIVE)->pluck('id')->toArray();
        $activeFormsCount = count($activeFormIds);

        // Obtener IDs de clientes que tienen alguna application form (activa o no)
        $clientIdsWithForms = ApplicationForm::distinct()->pluck('client_id')->toArray();

        $this->warn("⚠️  Forms ACTIVAS que se preservarán: {$activeFormsCount}");
        
        // Obtener estadísticas antes de eliminar
        $formsToDelete = ApplicationForm::whereNotIn('id', $activeFormIds)->count();
        $documentsToDelete = ApplicationDocument::whereNotIn('application_form_id', $activeFormIds)->count();
        $historyToDelete = ApplicationFormHistory::whereNotIn('application_form_id', $activeFormIds)->count();
        $contactUsToDelete = ContactUs::count();
        $clientsToDelete = User::where('type', 'client')
            ->whereNotIn('id', $clientIdsWithForms)
            ->count();

        $this->newLine();
        $this->info('📊 Estadísticas de eliminación:');
        $this->table(
            ['Tipo', 'Cantidad a eliminar'],
            [
                ['Application Forms (no activas)', $formsToDelete],
                ['Documents (de forms no activas)', $documentsToDelete],
                ['History (de forms no activas)', $historyToDelete],
                ['Contact Us (todos)', $contactUsToDelete],
                ['Clientes sin forms', $clientsToDelete],
            ]
        );

        $this->newLine();

        // Confirmación
        if (!$this->option('force')) {
            if (!$this->confirm('¿Estás seguro de que deseas continuar con la eliminación?', false)) {
                $this->warn('❌ Operación cancelada.');
                return 1;
            }
        }

        $this->newLine();
        $this->info('🚀 Iniciando eliminación...');
        $this->newLine();

        try {
            DB::beginTransaction();

            // 1. Eliminar documentos y sus archivos físicos
            $this->cleanupDocuments($activeFormIds);

            // 2. Eliminar historial de forms no activas
            $this->cleanupHistory($activeFormIds);

            // 3. Eliminar forms no activas y sus PDFs
            $this->cleanupForms($activeFormIds);

            // 4. Eliminar clientes sin application forms
            $this->cleanupClientsWithoutForms($clientIdsWithForms);

            // 5. Eliminar todos los registros de contact_us
            $this->cleanupContactUs();

            // 6. Eliminar tokens antiguos (opcional, mantener solo últimos 30 días)
            $this->cleanupTokens();

            // 7. Limpiar directorios huérfanos en storage/app/pdfs/
            $this->cleanupOrphanedPdfDirectories($activeFormIds);

            DB::commit();

            $this->newLine();
            $this->info('✅ Limpieza completada exitosamente!');
            $this->newLine();

            // Mostrar resumen
            $this->displaySummary();

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Error durante la limpieza: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }
    }

    /**
     * Eliminar documentos de forms no activas y sus archivos
     */
    protected function cleanupDocuments(array $activeFormIds): void
    {
        $this->info('📄 Eliminando documentos de forms no activas...');

        // Obtener documentos a eliminar
        $documents = ApplicationDocument::whereNotIn('application_form_id', $activeFormIds)->get();

        $progressBar = $this->output->createProgressBar($documents->count());
        $progressBar->start();

        foreach ($documents as $document) {
            try {
                // Eliminar archivo físico
                if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
                    Storage::disk('public')->delete($document->file_path);
                    $this->deletedCounts['document_files']++;
                }

                // Eliminar registro de BD
                $document->delete();
                $this->deletedCounts['documents']++;

            } catch (\Exception $e) {
                $this->warn("\n⚠️  Error eliminando documento ID {$document->id}: " . $e->getMessage());
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->comment("   ✓ {$this->deletedCounts['documents']} documentos eliminados de BD");
        $this->comment("   ✓ {$this->deletedCounts['document_files']} archivos de documentos eliminados");
        $this->newLine();
    }

    /**
     * Eliminar historial de forms no activas
     */
    protected function cleanupHistory(array $activeFormIds): void
    {
        $this->info('📋 Eliminando historial de forms no activas...');

        $deleted = ApplicationFormHistory::whereNotIn('application_form_id', $activeFormIds)->delete();
        $this->deletedCounts['history'] = $deleted;

        $this->comment("   ✓ {$deleted} registros de historial eliminados");
        $this->newLine();
    }

    /**
     * Eliminar forms no activas y sus PDFs
     */
    protected function cleanupForms(array $activeFormIds): void
    {
        $this->info('📝 Eliminando application forms no activas y sus PDFs...');

        // Obtener forms a eliminar
        $forms = ApplicationForm::whereNotIn('id', $activeFormIds)->get();

        $progressBar = $this->output->createProgressBar($forms->count());
        $progressBar->start();

        $deletedDirs = 0;

        foreach ($forms as $form) {
            try {
                // Eliminar el directorio completo de la form (incluyendo TODOS los archivos)
                $formDir = "pdfs/{$form->id}";
                if (Storage::disk('local')->exists($formDir)) {
                    // Contar archivos antes de eliminar
                    $files = Storage::disk('local')->allFiles($formDir);
                    $this->deletedCounts['pdf_files'] += count($files);
                    
                    // Eliminar directorio completo con todos sus archivos
                    Storage::disk('local')->deleteDirectory($formDir);
                    $deletedDirs++;
                }

                // Eliminar registro de BD
                $form->delete();
                $this->deletedCounts['forms']++;

            } catch (\Exception $e) {
                $this->warn("\n⚠️  Error eliminando form ID {$form->id}: " . $e->getMessage());
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->comment("   ✓ {$this->deletedCounts['forms']} application forms eliminadas");
        $this->comment("   ✓ {$this->deletedCounts['pdf_files']} archivos PDF eliminados");
        $this->comment("   ✓ {$deletedDirs} directorios de PDFs eliminados");
        $this->newLine();
    }

    /**
     * Eliminar todos los registros de contact_us
     */
    protected function cleanupContactUs(): void
    {
        $this->info('📧 Eliminando registros de Contact Us...');

        // No usar truncate dentro de transacción, usar delete()
        $deleted = ContactUs::count();
        ContactUs::query()->delete();
        $this->deletedCounts['contact_us'] = $deleted;

        $this->comment("   ✓ {$deleted} registros de Contact Us eliminados");
        $this->newLine();
    }

    /**
     * Eliminar clientes que no tienen ninguna application form
     */
    protected function cleanupClientsWithoutForms(array $clientIdsWithForms): void
    {
        $this->info('👥 Eliminando clientes sin application forms...');

        // Obtener clientes sin forms
        $clients = User::where('type', 'client')
            ->whereNotIn('id', $clientIdsWithForms)
            ->get();

        $progressBar = $this->output->createProgressBar($clients->count());
        $progressBar->start();

        foreach ($clients as $client) {
            try {
                // Eliminar sus tokens
                $client->tokens()->delete();

                // Eliminar el usuario
                $client->delete();
                $this->deletedCounts['clients']++;

            } catch (\Exception $e) {
                $this->warn("\n⚠️  Error eliminando cliente ID {$client->id}: " . $e->getMessage());
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->comment("   ✓ {$this->deletedCounts['clients']} clientes sin forms eliminados");
        $this->newLine();
    }

    /**
     * Eliminar tokens antiguos (más de 30 días)
     */
    protected function cleanupTokens(): void
    {
        $this->info('🔑 Eliminando tokens antiguos (> 30 días)...');

        $thirtyDaysAgo = now()->subDays(30);
        $deleted = PersonalAccessToken::where('created_at', '<', $thirtyDaysAgo)->delete();
        $this->deletedCounts['tokens'] = $deleted;

        $this->comment("   ✓ {$deleted} tokens antiguos eliminados");
        $this->newLine();
    }

    /**
     * Limpiar directorios huérfanos en storage/app/pdfs/
     * (directorios que no corresponden a forms activas)
     */
    protected function cleanupOrphanedPdfDirectories(array $activeFormIds): void
    {
        $this->info('🗑️  Limpiando directorios huérfanos de PDFs...');

        $orphanedDirs = 0;
        $orphanedFiles = 0;

        // Obtener todos los directorios en storage/app/pdfs/
        $allDirs = Storage::disk('local')->directories('pdfs');

        foreach ($allDirs as $dir) {
            // Extraer el ID del form del nombre del directorio (pdfs/123 -> 123)
            $formId = basename($dir);

            // Si el directorio no corresponde a una form activa, eliminarlo
            if (!in_array($formId, $activeFormIds)) {
                try {
                    // Contar archivos antes de eliminar
                    $files = Storage::disk('local')->allFiles($dir);
                    $orphanedFiles += count($files);

                    // Eliminar directorio completo
                    Storage::disk('local')->deleteDirectory($dir);
                    $orphanedDirs++;

                } catch (\Exception $e) {
                    $this->warn("\n⚠️  Error eliminando directorio huérfano {$dir}: " . $e->getMessage());
                }
            }
        }

        $this->comment("   ✓ {$orphanedDirs} directorios huérfanos eliminados");
        $this->comment("   ✓ {$orphanedFiles} archivos huérfanos eliminados");
        $this->newLine();
    }

    /**
     * Mostrar resumen de la limpieza
     */
    protected function displaySummary(): void
    {
        $this->info('📊 RESUMEN DE LIMPIEZA:');
        $this->table(
            ['Categoría', 'Cantidad Eliminada'],
            [
                ['Application Forms (no activas)', $this->deletedCounts['forms']],
                ['Documentos (BD)', $this->deletedCounts['documents']],
                ['Archivos de Documentos', $this->deletedCounts['document_files']],
                ['Archivos PDF', $this->deletedCounts['pdf_files']],
                ['Historial', $this->deletedCounts['history']],
                ['Contact Us', $this->deletedCounts['contact_us']],
                ['Clientes sin forms', $this->deletedCounts['clients']],
                ['Tokens antiguos', $this->deletedCounts['tokens']],
            ]
        );

        $this->newLine();
        $this->info('💾 Datos preservados:');
        $activeFormsCount = ApplicationForm::where('status', ApplicationForm::STATUS_ACTIVE)->count();
        $activeDocsCount = ApplicationDocument::whereHas('applicationForm', function($q) {
            $q->where('status', ApplicationForm::STATUS_ACTIVE);
        })->count();
        $remainingClientsCount = User::where('type', 'client')->count();
        $adminAgentCount = User::whereIn('type', ['admin', 'agent'])->count();

        $this->comment("   ✓ {$activeFormsCount} application forms activas");
        $this->comment("   ✓ {$activeDocsCount} documentos de forms activas");
        $this->comment("   ✓ {$remainingClientsCount} clientes con forms (preservados)");
        $this->comment("   ✓ {$adminAgentCount} usuarios admin/agent (preservados)");
        $this->newLine();
    }
}
