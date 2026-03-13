<?php

namespace App\Jobs;

use App\Models\App\Attachment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CompressVideoJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    protected $attachmentId;

    public function __construct($attachmentId)
    {
        $this->attachmentId = $attachmentId;
    }

    public function handle(): void
    {
        $attachment = Attachment::find($this->attachmentId);

        if (!$attachment) {
            \Log::error('Attachment não encontrado', ['id' => $this->attachmentId]);
            return;
        }

        $filePath = $attachment->file_path;

        if (!$filePath) {
            \Log::error('file_path está vazio no Attachment', ['attachment' => $attachment]);
            return;
        }

        // ✅ Ajuste correto com 'private'
        $path = storage_path('app/public/' . $filePath);
        $compressedPath = storage_path('app/public/' . dirname($filePath) . '/compressed_' . basename($filePath));

        if (!file_exists($path)) {
            \Log::error('Arquivo de vídeo não encontrado', ['path' => $path]);
            return;
        }

        $command = 'ffmpeg -i ' . escapeshellarg($path)
            . ' -vcodec libx264 -crf 30 -preset veryfast -vf scale=640:-2 -movflags +faststart '
            . escapeshellarg($compressedPath);

        exec($command, $output, $code);

        if ($code === 0 && file_exists($compressedPath)) {
            unlink($path);
            rename($compressedPath, $path);

            $compressedSize = filesize($path);
            $attachment->update([
                'compressed_size' => $compressedSize,
                'is_compressed' => $compressedSize < $attachment->original_size,
            ]);

            \Log::info('Compressão de vídeo concluída', ['path' => $path]);
        } else {
            \Log::error('Falha na compressão de vídeo', [
                'command' => $command,
                'output' => $output,
                'code' => $code
            ]);
        }
    }
}
