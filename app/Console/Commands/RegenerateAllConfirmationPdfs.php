<?php

namespace App\Console\Commands;

use App\Models\ApplicationForm;
use App\Services\PdfGeneratorService;
use Illuminate\Console\Command;

class RegenerateAllConfirmationPdfs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pdfs:regenerate-confirmations
                            {--confirmed-only : Solo regenerar PDFs de planillas confirmadas}
                            {--id= : Regenerar PDF de una planilla específica por ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerar todos los PDFs de confirmación con el nuevo formato de fechas y pie de página';

    protected PdfGeneratorService $pdfGenerator;

    /**
     * Create a new command instance.
     */
    public function __construct(PdfGeneratorService $pdfGenerator)
    {
        parent::__construct();
        $this->pdfGenerator = $pdfGenerator;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔄 Iniciando regeneración de PDFs de confirmación...');
        $this->newLine();

        // Si se especificó un ID específico
        if ($id = $this->option('id')) {
            return $this->regenerateSinglePdf($id);
        }

        // Obtener todas las planillas según el filtro
        $query = ApplicationForm::with(['client', 'agent']);

        if ($this->option('confirmed-only')) {
            $query->where('confirmed', true);
            $this->info('📋 Filtrando solo planillas confirmadas...');
        }

        $forms = $query->get();
        $total = $forms->count();

        if ($total === 0) {
            $this->warn('⚠️  No se encontraron planillas para regenerar.');
            return Command::SUCCESS;
        }

        $this->info("📊 Total de planillas a procesar: {$total}");
        $this->newLine();

        // Confirmar acción
        if (!$this->confirm('¿Deseas continuar con la regeneración de PDFs?', true)) {
            $this->warn('❌ Operación cancelada por el usuario.');
            return Command::SUCCESS;
        }

        $this->newLine();

        // Barra de progreso
        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        $success = 0;
        $failed = 0;
        $errors = [];

        foreach ($forms as $form) {
            try {
                // Generar PDF de confirmación
                $pdfPath = $this->pdfGenerator->generateConfirmationPdf($form);
                
                // Actualizar la ruta en la base de datos
                $form->update(['pdf_path' => $pdfPath]);
                
                $success++;
                
            } catch (\Exception $e) {
                $failed++;
                $errors[] = [
                    'id' => $form->id,
                    'client' => $form->client->name ?? 'N/A',
                    'error' => $e->getMessage()
                ];
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Mostrar resultados
        $this->info("✅ PDFs regenerados exitosamente: {$success}");
        
        if ($failed > 0) {
            $this->error("❌ PDFs fallidos: {$failed}");
            $this->newLine();
            $this->error('Errores encontrados:');
            
            foreach ($errors as $error) {
                $this->line("  • Planilla #{$error['id']} ({$error['client']}): {$error['error']}");
            }
        }

        $this->newLine();
        $this->info('🎉 Proceso completado!');

        return Command::SUCCESS;
    }

    /**
     * Regenerar PDF de una planilla específica
     */
    private function regenerateSinglePdf(int $id): int
    {
        $this->info("🔍 Buscando planilla #{$id}...");

        $form = ApplicationForm::with(['client', 'agent'])->find($id);

        if (!$form) {
            $this->error("❌ No se encontró la planilla #{$id}");
            return Command::FAILURE;
        }

        $this->info("📄 Planilla encontrada: {$form->client->name}");
        $this->newLine();

        try {
            // Generar PDF
            $this->info('🔄 Generando PDF...');
            $pdfPath = $this->pdfGenerator->generateConfirmationPdf($form);
            
            // Actualizar en base de datos
            $form->update(['pdf_path' => $pdfPath]);
            
            $this->newLine();
            $this->info("✅ PDF regenerado exitosamente!");
            $this->line("   Ruta: {$pdfPath}");
            $this->newLine();
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->newLine();
            $this->error("❌ Error al generar PDF: {$e->getMessage()}");
            $this->newLine();
            
            return Command::FAILURE;
        }
    }
}
