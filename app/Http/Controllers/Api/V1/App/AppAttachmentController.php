<?php

namespace App\Http\Controllers\Api\V1\App;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;

use App\Models\App\Attachment;
use App\Models\App\AnswerOption;
use App\Models\App\Answer;

use App\Jobs\CompressVideoJob;
use App\Jobs\CompressAttachmentJob;

class AppAttachmentController extends Controller
{
    public function index(string $type, string $referenceId): JsonResponse
    {
        if (!in_array($type, ['option', 'answer'])) {
            return response()->json(['error' => 'Tipo inválido. Use "option" ou "answer".'], 400);
        }

        $column = $type === 'option' ? 'answer_option_id' : 'answer_id';

        $attachments = Attachment::where($column, $referenceId)->get()->map(function ($attachment) {
            return [
                'id' => $attachment->id,
                'file_name' => $attachment->file_name,
                'file_path' => $attachment->file_path,
                'original_size' => $attachment->original_size,
                'compressed_size' => $attachment->compressed_size,
                'status' => $attachment->is_compressed ? 'compressed' : 'pending_compression',
                'url' => Storage::disk('public')->url($attachment->file_path),
                'created_by' => $attachment->created_by,
                'created_at' => $attachment->created_at->toDateTimeString(),
            ];
        });

        return response()->json([
            'attachments' => $attachments
        ]);
    }

    public function store(Request $request, string $type, string $referenceId): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,heic,webp,gif,pdf,mp4,avi,mov,csv,xml,txt|max:102400',
        ]);

        if (!in_array($type, ['option', 'answer'])) {
            return response()->json(['error' => 'Tipo inválido. Use "option" ou "answer".'], 400);
        }

        $reference = $type === 'option'
            ? AnswerOption::find($referenceId)
            : Answer::find($referenceId);

        if (!$reference) {
            return response()->json(['error' => 'Referência não encontrada.'], 404);
        }

        return $this->handleUpload($request, $type, $referenceId);
    }

    private function handleUpload(Request $request, string $type, string $referenceId): JsonResponse
    {
        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        $originalSize = $file->getSize();

        $filename = Str::uuid() . '.' . $extension;
        $path = 'attachments/' . $filename;

        $stream = fopen($file->getRealPath(), 'r+');
        Storage::disk('public')->put($path, $stream);
        fclose($stream);

        if (!Storage::disk('public')->exists($path)) {
            return response()->json(['error' => 'Falha ao salvar o arquivo no servidor.'], 500);
        }

        $data = [
            'id' => Str::uuid(),
            'answer_option_id' => $type === 'option' ? $referenceId : null,
            'answer_id' => $type === 'answer' ? $referenceId : null,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'original_size' => $originalSize,
            'compressed_size' => null,
            'is_compressed' => false,
            'created_by' => $request->login ?? null,
        ];

        $attachment = Attachment::create($data);

        $videoExtensions = ['mp4', 'avi', 'mov'];
        if (in_array($extension, $videoExtensions)) {
            CompressVideoJob::dispatch($attachment->id)->delay(now()->addSeconds(5));
        } else {
            CompressAttachmentJob::dispatch($attachment->id)->delay(now()->addSeconds(5));
        }

        return response()->json([
            'message' => 'Anexo recebido com sucesso.',
            'attachment' => [
                'id' => $attachment->id,
                'file_name' => $attachment->file_name,
                'status' => 'pending_compression',
            ],
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $attachment = Attachment::find($id);

        if (!$attachment) {
            return response()->json(['error' => 'Anexo não encontrado.'], 404);
        }

        return response()->json([
            'id' => $attachment->id,
            'file_name' => $attachment->file_name,
            'file_path' => $attachment->file_path,
            'original_size' => $attachment->original_size,
            'compressed_size' => $attachment->compressed_size,
            'status' => $attachment->is_compressed ? 'compressed' : 'pending_compression',
            'url' => Storage::disk('public')->url($attachment->file_path),
            'created_by' => $attachment->created_by,
            'created_at' => $attachment->created_at->toDateTimeString(),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $attachment = Attachment::find($id);
        if (!$attachment) {
            return response()->json(['error' => 'Anexo não encontrado.'], 404);
        }

        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();

        return response()->json(['message' => 'Anexo excluído com sucesso.']);
    }
}
