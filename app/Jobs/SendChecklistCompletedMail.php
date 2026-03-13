<?php

namespace App\Jobs;

use App\Mail\ChecklistCompletedMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendChecklistCompletedMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $application;
    protected $emails;

    /**
     * Create a new job instance.
     *
     * @param mixed $application
     * @param array $emails
     * @return void
     */
    public function __construct($application, array $emails)
    {
        $this->application = $application;
        $this->emails = $emails;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Garante que temos um array de e-mails
        $emails = is_array($this->emails) ? $this->emails : [$this->emails];
        
        // Remove espaços em branco e valores vazios
        $emails = array_filter(array_map('trim', $emails));
        
        // Remove duplicatas
        $emails = array_unique($emails);
        
        // Envia e-mail para cada endereço válido
        foreach ($emails as $email) {
            try {
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    Mail::to($email)->send(new ChecklistCompletedMail($this->application));
                    \Log::info("E-mail enviado com sucesso para: " . $email);
                } else {
                    \Log::warning("Endereço de e-mail inválido: " . $email);
                }
            } catch (\Exception $e) {
                \Log::error("Erro ao enviar e-mail para {$email}: " . $e->getMessage());
            }
        }
    }
}
