<?php

namespace App\Jobs;

use App\Models\App\Attachment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class CompressAttachmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $attachmentId;

    protected $imageExtensions = ['jpg', 'jpeg', 'png', 'heic', 'webp', 'gif'];
    protected $pdfExtensions = ['pdf'];
    protected $textExtensions = ['csv', 'xml', 'txt'];

    public function __construct(string $attachmentId)
    {
        $this->attachmentId = $attachmentId;
    }

    public function handle()
    {
        $attachment = Attachment::find($this->attachmentId);

        if (!$attachment) {
            Log::error('CompressAttachmentJob: Attachment não encontrado.', [
                'attachment_id' => $this->attachmentId
            ]);
            return;
        }

        $filePath = storage_path('app/public/' . $attachment->file_path);
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        try {
            if (in_array($extension, $this->imageExtensions)) {
                $this->compressImage($filePath, $attachment);
            } elseif (in_array($extension, $this->pdfExtensions)) {
                $this->compressPdf($filePath, $attachment);
            } elseif (in_array($extension, $this->textExtensions)) {
                $this->compressTextFile($filePath, $attachment);
            } else {
                Log::info('CompressAttachmentJob: Tipo de arquivo não possui compressão automatizada.', [
                    'attachment_id' => $this->attachmentId,
                    'extension' => $extension
                ]);
            }
        } catch (\Exception $e) {
            Log::error('CompressAttachmentJob: Erro na compressão.', [
                'error' => $e->getMessage(),
                'attachment_id' => $this->attachmentId
            ]);
        }
    }

    protected function compressImage($filePath, $attachment)
    {
        \Tinify\setKey(config('services.tinify.key'));
        $source = \Tinify\fromFile($filePath);
        $source->toFile($filePath);

        $this->updateAttachment($attachment);

        Log::info('CompressAttachmentJob: Imagem comprimida com sucesso via TinyPNG.', [
            'attachment_id' => $this->attachmentId
        ]);
    }

    protected function compressPdf($filePath, $attachment)
    {
        $outputPath = $filePath . '.compressed.pdf';

        $process = new Process([
            'gs',
            '-sDEVICE=pdfwrite',
            '-dCompatibilityLevel=1.4',
            '-dPDFSETTINGS=/ebook',
            '-dNOPAUSE',
            '-dQUIET',
            '-dBATCH',
            '-sOutputFile=' . $outputPath,
            $filePath
        ]);

        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        Storage::disk('public')->put($attachment->file_path, file_get_contents($outputPath));
        unlink($outputPath);

        $this->updateAttachment($attachment);

        Log::info('CompressAttachmentJob: PDF comprimido com sucesso via Ghostscript.', [
            'attachment_id' => $this->attachmentId
        ]);
    }

    protected function compressTextFile($filePath, $attachment)
    {
        $zipPath = $filePath . '.zip';
        $zip = new \ZipArchive();

        if ($zip->open($zipPath, \ZipArchive::CREATE) === TRUE) {
            $zip->addFile($filePath, basename($filePath));
            $zip->close();

            Storage::disk('public')->put($attachment->file_path, file_get_contents($zipPath));
            unlink($zipPath);

            $this->updateAttachment($attachment);

            Log::info('CompressAttachmentJob: Arquivo texto comprimido com sucesso via ZIP.', [
                'attachment_id' => $this->attachmentId
            ]);
        } else {
            throw new \Exception('Falha ao criar o arquivo ZIP.');
        }
    }

    protected function updateAttachment($attachment)
    {
        $compressedSize = Storage::disk('public')->size($attachment->file_path);
        $attachment->update([
            'compressed_size' => $compressedSize,
            'is_compressed' => $compressedSize < $attachment->original_size,
        ]);
    }
}
