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

class CompressImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $attachmentId;

    public function __construct(string $attachmentId)
    {
        $this->attachmentId = $attachmentId;
    }

    public function handle()
    {
        $attachment = Attachment::find($this->attachmentId);

        if (!$attachment) {
            Log::error('CompressImageJob: Attachment não encontrado.', [
                'attachment_id' => $this->attachmentId
            ]);
            return;
        }

        $filePath = storage_path('app/public/' . $attachment->file_path);
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        try {
            $converted = false;

            // Conversão de HEIC/HEIF para JPG
            if (in_array($extension, ['heic', 'heif'])) {
                $newFilePath = preg_replace('/\.(heic|heif)$/i', '.jpg', $filePath);
                $cmd = "magick convert " . escapeshellarg($filePath) . " " . escapeshellarg($newFilePath);
                exec($cmd, $output, $returnVar);

                if ($returnVar === 0 && file_exists($newFilePath)) {
                    // Atualiza o attachment
                    Storage::disk('public')->delete($attachment->file_path);
                    $relativeNewPath = str_replace(storage_path('app/public/'), '', $newFilePath);
                    $attachment->update(['file_path' => $relativeNewPath]);

                    $filePath = $newFilePath;
                    $extension = 'jpg';
                    $converted = true;
                } else {
                    Log::error('CompressImageJob: Falha na conversão de HEIC/HEIF.', [
                        'attachment_id' => $this->attachmentId,
                        'command' => $cmd,
                        'output' => $output,
                        'return_var' => $returnVar
                    ]);
                    return;
                }
            }

            \Tinify\setKey(config('services.tinify.key'));

            $source = \Tinify\fromFile($filePath);
            $source->toFile($filePath);

            $compressedSize = Storage::disk('public')->size(str_replace(storage_path('app/public/'), '', $filePath));

            $attachment->update([
                'compressed_size' => $compressedSize,
                'is_compressed' => $compressedSize < $attachment->original_size,
            ]);

            Log::info('CompressImageJob: Imagem comprimida com sucesso via TinyPNG.', [
                'attachment_id' => $this->attachmentId,
                'compressed_size' => $compressedSize,
                'converted' => $converted
            ]);

        } catch (\Exception $e) {
            Log::error('CompressImageJob: Erro na compressão via TinyPNG', [
                'error' => $e->getMessage(),
                'attachment_id' => $this->attachmentId
            ]);
        }
    }
}
