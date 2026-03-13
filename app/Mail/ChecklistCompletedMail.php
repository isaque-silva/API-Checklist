<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ChecklistCompletedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public $application;
    public function __construct($application)
    {
        $this->application = $application;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->application->number . ' - Novo Checklist Concluído - ' . $this->application->checklist->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.checklist_completed',
            with: [
                'application' => $this->application
            ],
        );
    }

    /**
     * Build the message with PDF attachment.
     */
    public function build()
    {
        // Carrega o objeto Application com todos os relacionamentos necessários
        $application = \App\Models\App\Application::with([
            'answers' => function($query) {
                $query->with([
                    'item.mask', // Carrega a relação mask do item
                    'attachments', // Anexos diretos da resposta
                    'options' => function($query) {
                        $query->with([
                            'attachments', // Anexos da opção
                            'option' // Dados da opção
                        ]);
                    }
                ]);
            },
            'checklist.areas.items' => function($query) {
                $query->with(['type', 'mask']); // Mudado de input_mask para mask
            },
        ])->find($this->application->id);

        // Configurações do PDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::setOptions([
            'isPhpEnabled' => true,
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'Arial',
        ]);

        $pdf = $pdf->loadView('emails.checklist_completed_pdf', [
            'application' => $application
        ]);
        
        // Define o papel e orientação
        $pdf->setPaper('a4', 'portrait');
        
        // Habilita o acesso a variáveis PHP no template
        $pdf->getDomPDF()->set_option('isPhpEnabled', true);
        
        // Obtém o conteúdo do PDF
        $output = $pdf->output();

        return $this
            ->subject($this->application->number . ' - Novo Checklist Concluído - ' . $this->application->checklist->title)
            ->view('emails.checklist_completed')
            ->with(['application' => $application])
            ->attachData($output, 'checklist.pdf', [
                'mime' => 'application/pdf',
            ]);
    }
}
