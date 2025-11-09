<?php

namespace App\Mail;

use App\Models\ApplicationForm;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicationCreatedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public ApplicationForm $form;
    public User $agent;
    public string $completePdfPath;
    public ?string $authorizationPdfPath;
    public string $token;

    /**
     * Create a new message instance.
     */
    public function __construct(
        ApplicationForm $form,
        User $agent,
        string $completePdfPath,
        ?string $authorizationPdfPath,
        string $token
    ) {
        $this->form = $form;
        $this->agent = $agent;
        $this->completePdfPath = $completePdfPath;
        $this->authorizationPdfPath = $authorizationPdfPath;
        $this->token = $token;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(env('MAIL_FROM_ADDRESS', 'noreply@latingroup.com'), env('MAIL_FROM_NAME', 'Latin Group')),
            subject: '✅ Nueva Planilla de Aplicación Creada - ' . $this->form->client->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $frontendBaseUrl = rtrim(config('services.frontend.url', url('/')), '/');
        $confirmationLink = $frontendBaseUrl . '/confirm/' . $this->token;
        
        return new Content(
            view: 'emails.application-created',
            with: [
                'agentName' => $this->agent->name,
                'clientName' => $this->form->client->name,
                'applicantName' => $this->form->applicant_name,
                'formId' => $this->form->id,
                'confirmationLink' => $confirmationLink,
                'createdAt' => $this->form->created_at->format('d/m/Y H:i'),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];

        // Adjuntar PDF completo de la planilla
        if ($this->completePdfPath && file_exists($this->completePdfPath)) {
            $attachments[] = Attachment::fromPath($this->completePdfPath)
                ->as("Planilla_Completa_{$this->form->client->name}_{$this->form->id}.pdf")
                ->withMime('application/pdf');
        }

        // Adjuntar PDF de autorización del cliente
        if ($this->authorizationPdfPath && file_exists($this->authorizationPdfPath)) {
            $attachments[] = Attachment::fromPath($this->authorizationPdfPath)
                ->as("Autorizacion_Cliente_{$this->form->client->name}_{$this->form->id}.pdf")
                ->withMime('application/pdf');
        }

        return $attachments;
    }
}
