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

class ApplicationConfirmedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public ApplicationForm $form;
    public User $agent;
    public string $authorizationPdfPath;

    public function __construct(ApplicationForm $form, User $agent, string $authorizationPdfPath)
    {
        $this->form = $form;
        $this->agent = $agent;
        $this->authorizationPdfPath = $authorizationPdfPath;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(env('MAIL_FROM_ADDRESS', 'noreply@latingroup.com'), env('MAIL_FROM_NAME', 'Latin Group')),
            subject: '✅ Cliente confirmó la planilla - ' . ($this->form->client->name ?? 'Cliente'),
        );
    }

    public function content(): Content
    {
    $frontendBaseUrl = rtrim(config('services.frontend.url', url('/')), '/');
    $applicationLink = $frontendBaseUrl . '/application-forms';

        return new Content(
            view: 'emails.application-confirmed',
            with: [
                'agentName' => $this->agent->name,
                'clientName' => $this->form->client->name ?? $this->form->applicant_name,
                'formId' => $this->form->id,
                'confirmedAt' => optional($this->form->confirmed_at)->format('d/m/Y H:i'),
                'applicationLink' => $applicationLink,
            ],
        );
    }

    public function attachments(): array
    {
        if (!file_exists($this->authorizationPdfPath)) {
            return [];
        }

        return [
            Attachment::fromPath($this->authorizationPdfPath)
                ->as('Autorizacion_Cliente_' . ($this->form->client->name ?? 'cliente') . '_' . $this->form->id . '.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
