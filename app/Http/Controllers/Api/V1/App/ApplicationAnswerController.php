<?php

namespace App\Http\Controllers\Api\V1\App;

use App\Jobs\SendChecklistCompletedMail;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

// Models
use App\Models\App\Application;
use App\Models\App\Answer;
use App\Models\App\AnswerOption;
use App\Models\App\Attachment;
use App\Models\Chk\Item;
use App\Models\Chk\SelectionOption;
use App\Models\Chk\ItemEvalOption;
use App\Models\Chk\Area;
use App\Models\Chk\Checklist;

class ApplicationAnswerController extends Controller
{
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'checklist_id' => 'required|uuid',
                'email_group_id' => 'nullable|uuid'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Se a validação falhar (422), retorna a mesma mensagem do 404
            return response()->json([
                'error' => 'Checklist não encontrado.',
                'message' => 'O checklist_id informado não foi encontrado no sistema.',
                'checklist_id' => $request->input('checklist_id')
            ], 404);
        }

        // Verifica se o checklist existe
        $checklistExists = \App\Models\Chk\Checklist::where('id', $data['checklist_id'])->exists();
        if (!$checklistExists) {
            return response()->json([
                'error' => 'Checklist não encontrado.',
                'message' => 'O checklist_id informado não foi encontrado no sistema.',
                'checklist_id' => $data['checklist_id']
            ], 404);
        }

        DB::beginTransaction();

        try {
            $application = Application::create([
                'id' => Str::uuid(),
                'checklist_id' => $data['checklist_id'],
                'email_group_id' => $data['email_group_id'] ?? null,
                'status' => 'in_progress',
                'applied_at' => now(), 
            ]);

            $application->refresh();

            $areas = Area::where('checklist_id', $data['checklist_id'])
                        ->orderBy('order')
                        ->get();

            if ($areas->isEmpty()) {
                throw new \Exception('Nenhuma área encontrada para o checklist.');
            }

            foreach ($areas as $area) {
                $items = Item::with('type', 'selectionOptions', 'evalOptions')
                            ->where('checklist_area_id', $area->id)
                            ->get();

                foreach ($items as $item) {
                    $typeMap = [
                        'texto' => 'text',
                        'numero' => 'number',
                        'data' => 'datetime',
                        'selecao' => 'selection',
                        'avaliativo' => 'evaluative',
                        'arquivo' => 'file',
                    ];

                    $responseType = $typeMap[$item->type->name] ?? null;

                    if (!$responseType) {
                        throw new \Exception("Tipo de item inválido: {$item->type->name}");
                    }

                    $answer = Answer::create([
                        'id' => Str::uuid(),
                        'application_id' => $application->id,
                        'checklist_item_id' => $item->id,
                        'response_type' => $responseType,
                        'selected_option_id' => null,
                    ]);

                    if ($item->type->name === 'selecao') {
                        foreach ($item->selectionOptions as $option) {
                            AnswerOption::create([
                                'id' => Str::uuid(),
                                'answer_id' => $answer->id,
                                'option_id' => $option->id,
                                'type' => 'selection',
                            ]);
                        }
                    }

                    if ($item->type->name === 'avaliativo') {
                        foreach ($item->evalOptions as $option) {
                            AnswerOption::create([
                                'id' => Str::uuid(),
                                'answer_id' => $answer->id,
                                'option_id' => $option->id, // Corrigido: usar o ID da opção do checklist, não do eval_option
                                'type' => 'evaluative',
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            $checklist = \App\Models\Chk\Checklist::with([
                'areas' => function($q) {
                    $q->orderBy('order')->with([
                        'items' => function($q) {
                            $q->with(['type', 'selectionOptions', 'evalOptions.evalOption']);
                        }
                    ]);
                }
            ])->find($data['checklist_id']);

            return response()->json([
                'message' => 'Checklist iniciado com sucesso.',
                'application_id' => $application->id,

                'status' => $application->status,
                'email_group_id' => $application->email_group_id,
                'email_group_name' => $application->emailGroup->name ?? null,
                'number' => $application->numero_formatado,
                'number_raw' => $application->number,
                'applied_at' => $application->applied_at,
                'completed_at' => $application->completed_at,
                'created_by' => $application->created_by,
                'updated_by' => $application->updated_by,
                'checklist' => $checklist,
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Erro ao iniciar checklist.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show(string $applicationId)
    {
        $application = Application::with(['emailGroup', 'answers.options.attachments'])->find($applicationId);

        if (!$application) {
            return response()->json(['error' => 'Aplicação não encontrada.'], 404);
        }

        $checklist = Checklist::with([
            'areas' => function($q) {
                $q->orderBy('order')->with([
                    'items' => function($q) {
                        $q->with(['type', 'mask', 'selectionOptions', 'evalOptions.evalOption']);
                    }
                ]);
            }
        ])->find($application->checklist_id);

        // Mapeia as respostas por item_id para fácil acesso
        $answersByItem = $application->answers->keyBy('checklist_item_id');

        // Adiciona as respostas aos itens correspondentes
        foreach ($checklist->areas as $area) {
            foreach ($area->items as $item) {
                $answer = $answersByItem[$item->id] ?? null;
                
                if ($answer) {
                    $type = $item->type->name;
                    $selectionType = strtolower($item->selection_type ?? '');
                    $attachments = [];

                    // Primeiro processa os anexos
                    if (in_array($type, ['selecao', 'avaliativo'])) {
                        // Para tipos avaliativo e selecao: anexos por option
                        foreach ($answer->options as $opt) {
                            foreach ($opt->attachments as $att) {
                                $attachments[] = [
                                    'id' => $att->id,
                                    'file_name' => $att->file_name,
                                    'url' => \Storage::disk('public')->url($att->file_path),
                                ];
                            }
                        }
                    } else {
                        // Para texto, numero, data: anexos por answer
                        foreach ($answer->attachments as $att) {
                            $attachments[] = [
                                'id' => $att->id,
                                'file_name' => $att->file_name,
                                'url' => \Storage::disk('public')->url($att->file_path),
                            ];
                        }
                    }

                    // Depois monta o responseValue com os anexos já processados
                    $responseValue = match ($type) {
                        'texto' => $answer->response_text,
                        'numero' => $answer->response_number,
                        'data' => $answer->response_datetime,
                        'selecao', 'avaliativo' => $answer->options->filter(fn($opt) => $opt->is_selected == 1)
                            ->map(function($opt) {
                                $optionAttachments = $opt->attachments->map(function($att) {
                                    return [
                                        'id' => $att->id,
                                        'file_name' => $att->file_name,
                                        'url' => \Storage::disk('public')->url($att->file_path),
                                    ];
                                })->toArray();

                                return [
                                    'option_id' => $opt->option_id,
                                    'reference_id' => $opt->id,
                                    'type' => $opt->type,
                                    'comment' => $opt->comment,
                                    'is_selected' => true,
                                    'attachments' => $optionAttachments,
                                ];
                            })->values(),
                        default => null
                    };

                    $answerData = [
                        'id' => $answer->id,
                        'response' => $responseValue,
                    ];

                    // Adiciona comment e attachments para tipos texto, numero, data e arquivo
                    if (in_array($type, ['texto', 'numero', 'data', 'arquivo'])) {
                        $answerData['comment'] = $answer->comment;
                        $answerData['attachments'] = $attachments;
                    }

                    $item->answer = $answerData;
                } else {
                    $item->answer = null;
                }
            }
        }

        return response()->json([
            'message' => 'Checklist carregado com sucesso.',
            'application_id' => $application->id,
            'status' => $application->status,
            'email_group_id' => $application->email_group_id,
            'email_group_name' => $application->emailGroup->name ?? null,
            'number' => $application->getNumeroFormatadoAttribute(),
            'applied_at' => $application->applied_at,
            'completed_at' => $application->completed_at,
            'created_by' => $application->created_by,
            'updated_by' => $application->updated_by,
            'checklist' => $checklist,
        ]);
    }

    public function index(Request $request)
    {
        $status = $request->query('status');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $checklistId = $request->query('checklist_id');

        // Validação das datas (opcional)
        if ($startDate) {
            try {
                \Carbon\Carbon::parse($startDate);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Data de início inválida. Use o formato YYYY-MM-DD.'], 400);
            }
        }

        if ($endDate) {
            try {
                \Carbon\Carbon::parse($endDate);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Data de fim inválida. Use o formato YYYY-MM-DD.'], 400);
            }
        }

        // Validação de lógica das datas
        if ($startDate && $endDate) {
            $startCarbon = \Carbon\Carbon::parse($startDate);
            $endCarbon = \Carbon\Carbon::parse($endDate);
            
            if ($startCarbon->gt($endCarbon)) {
                return response()->json(['error' => 'Data de início não pode ser maior que a data de fim.'], 400);
            }
        }

        // Validação do checklist_id (opcional)
        if ($checklistId) {
            if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $checklistId)) {
                return response()->json(['error' => 'ID do checklist inválido. Use um UUID válido.'], 400);
            }
            
            // Verifica se o checklist existe
            $checklistExists = \App\Models\Chk\Checklist::where('id', $checklistId)->exists();
            if (!$checklistExists) {
                return response()->json(['error' => 'Checklist não encontrado.'], 404);
            }
        }

        $query = Application::with([
            'emailGroup', 
            'answers.options.attachments', 
            'answers.item.type'
        ]);

        if (in_array($status, ['in_progress', 'completed', 'deleted'])) {
            $query->where('status', $status);
        }

        // Filtro por checklist_id (opcional)
        if ($checklistId) {
            $query->where('checklist_id', $checklistId);
        }

        // Filtro de data de início (opcional)
        if ($startDate) {
            $startDateTime = \Carbon\Carbon::parse($startDate)->startOfDay();
            $query->where('completed_at', '>=', $startDateTime);
        }

        // Filtro de data de fim (opcional)
        if ($endDate) {
            $endDateTime = \Carbon\Carbon::parse($endDate)->endOfDay();
            $query->where('completed_at', '<=', $endDateTime);
        }

        $applications = $query->limit(350)->get();

        $result = $applications->map(function ($application) {
            $checklist = \App\Models\Chk\Checklist::with([
                'areas' => function ($q) {
                    $q->orderBy('order')->with([
                        'items' => function ($q) {
                            $q->with([
                                'type',
                                'mask',
                                'selectionOptions',
                                'evalOptions.evalOption'
                            ]);
                        }
                    ]);
                }
            ])->find($application->checklist_id);

            $answers = $application->answers->keyBy('checklist_item_id');

            foreach ($checklist->areas as $area) {
                foreach ($area->items as $item) {
                    $answer = $answers[$item->id] ?? null;

                    if (!$answer) {
                        $item->answer = null;
                        continue;
                    }

                    $type = $item->type->name;
                    $selectionType = strtolower($item->selection_type ?? '');

                    $responseValue = match ($type) {
                        'texto' => $answer->response_text,
                        'numero' => $answer->response_number,
                        'data' => $answer->response_datetime,
                        'selecao' => $answer->options->map(fn($opt) => [
                            'option_id' => $opt->option_id,
                            'reference_id' => $opt->id,
                            'type' => $opt->type,
                            'comment' => $opt->comment,
                            'is_selected' => (bool) $opt->is_selected,
                        ])->values(),
                        'avaliativo' => $answer->options->map(fn($opt) => [
                            'option_id' => $opt->option_id,
                            'reference_id' => $opt->id,
                            'type' => $opt->type,
                            'comment' => $opt->comment,
                            'is_selected' => (bool) $opt->is_selected,
                        ])->values(),
                        default => null
                    };

                    $attachments = [];

                    // Para tipos avaliativo e selecao: anexos por option
                    if (in_array($type, ['selecao', 'avaliativo'])) {
                        foreach ($answer->options as $opt) {
                            foreach ($opt->attachments as $att) {
                                $attachments[] = [
                                    'id' => $att->id,
                                    'file_name' => $att->file_name,
                                    'url' => \Storage::disk('public')->url($att->file_path),
                                ];
                            }
                        }
                    } else {
                        // Para texto, numero, data: anexos por answer
                        foreach ($answer->attachments as $att) {
                            $attachments[] = [
                                'id' => $att->id,
                                'file_name' => $att->file_name,
                                'url' => \Storage::disk('public')->url($att->file_path),
                            ];
                        }
                    }

                    $answerData = [
                        'id' => $answer->id,
                        'response' => $responseValue,
                        'comment' => $answer->comment,
                        'options' => in_array($type, ['selecao', 'avaliativo']) ? $answer->options->map(fn($opt) => [
                            'option_id' => $opt->option_id,
                            'reference_id' => $opt->id,
                            'type' => $opt->type,
                            'comment' => $opt->comment,
                            'is_selected' => (bool) $opt->is_selected,
                        ])->values() : [],
                        'attachments' => $attachments,
                    ];

                    // Adiciona comment e attachments para tipos texto, numero, data e arquivo
                    if (in_array($type, ['texto', 'numero', 'data', 'arquivo'])) {
                        $answerData['comment'] = $answer->comment;
                        $answerData['attachments'] = $attachments;
                    }

                    $item->answer = $answerData;
                }
            }

            return [
                'message' => 'Checklist carregado com sucesso.',
                'application_id' => $application->id,
                'status' => $application->status,
                'email_group_id' => $application->email_group_id,
                'email_group_name' => $application->emailGroup->name ?? null,
                'number' => $application->getNumeroFormatadoAttribute(),
                'applied_at' => $application->applied_at,
                'completed_at' => $application->completed_at,
                'created_by' => $application->created_by,
                'updated_by' => $application->updated_by,
                'checklist' => $checklist,
            ];
        });

        return response()->json([
            'applications' => $result
        ]);
    }

    public function destroy(string $applicationId)
    {
        $application = Application::find($applicationId);

        if (!$application) {
            return response()->json(['error' => 'Aplicação não encontrada.'], 404);
        }

        $application->delete();

        return response()->json(['message' => 'Aplicação excluída com sucesso.']);
    }

    public function generatePdf(string $applicationId)
    {
        ini_set('memory_limit', config('pdf.memory_limit', '512M'));
        // Carrega o objeto Application com todos os relacionamentos necessários
        $application = Application::with([
            'answers' => function($query) {
                $query->with([
                    'item.mask',
                    'attachments',
                    'options' => function($query) {
                        $query->with([
                            'attachments',
                            'option'
                        ]);
                    }
                ]);
            },
            'checklist.areas.items' => function($query) {
                $query->with(['type', 'mask']);
            },
        ])->find($applicationId);

        if (!$application) {
            return response()->json(['error' => 'Aplicação não encontrada.'], 404);
        }

        try {
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
            
            // Obtém o conteúdo do PDF em base64
            $pdfContent = $pdf->output();
            $pdfBase64 = base64_encode($pdfContent);

            return response()->json([
                'message' => 'PDF gerado com sucesso.',
                'application_id' => $application->id,
                'pdf_base64' => $pdfBase64,
                'filename' => 'checklist_' . $application->number . '.pdf',
                'size_bytes' => strlen($pdfContent),
                'generated_at' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao gerar PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    public function storePartial(Request $request, string $applicationId)
    {
        return $this->processAnswers($request, $applicationId, false);
    }

    public function storeAndSubmit(Request $request, string $applicationId)
    {
        return $this->processAnswers($request, $applicationId, true);
    }

    private function processAnswers(Request $request, string $applicationId, bool $finalize)
    {
        $application = Application::with(['answers'])->find($applicationId);

        if (!$application) {
            return response()->json(['error' => 'Aplicação não encontrada.'], 404);
        }

        $data = $request->validate([
            'answers' => 'required|array|min:1',
            'answers.*.item_id' => 'required|uuid|exists:chk_items,id',
            'answers.*.response_text' => 'nullable|string',
            'answers.*.response_number' => 'nullable|numeric',
            'answers.*.response_datetime' => 'nullable|date',
            'answers.*.selected_option_id' => 'nullable|uuid',
            'answers.*.comment' => 'nullable|string',
            'answers.*.options' => 'nullable|array',
            'answers.*.options.*.option_id' => 'required|uuid',
            'answers.*.options.*.type' => 'required|in:selection,evaluative',
            'answers.*.options.*.comment' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            foreach ($data['answers'] as $input) {

                $item = Item::with(['type', 'area'])->find($input['item_id']);

                if (!$item) {
                    throw new \Exception("Item não encontrado: {$input['item_id']}");
                }

                if ($item->area->checklist_id !== $application->checklist_id) {
                    throw new \Exception("Item {$item->id} não pertence ao checklist da aplicação.");
                }

                $answer = Answer::where('application_id', $applicationId)
                                ->where('checklist_item_id', $item->id)
                                ->first();

                if (!$answer) {
                    throw new \Exception("Resposta não encontrada para o item {$item->id}");
                }

                $type = $item->type->name;
                $typeMap = [
                    'texto' => 'text',
                    'numero' => 'number',
                    'data' => 'datetime',
                    'selecao' => 'selection',
                    'avaliativo' => 'evaluative',
                    'arquivo' => 'file',
                ];

                $responseType = $typeMap[$type] ?? null;

                if (!$responseType) {
                    throw new \Exception("Tipo de item inválido: {$type}");
                }

                $selectionType = strtolower($item->selection_type ?? '');
                $optionsToProcess = $input['options'] ?? [];
                $selectedOptionId = null;

                // Se for seleção única, obtém o ID da opção selecionada
                if ($type === 'selecao' && $selectionType === 'single' && !empty($optionsToProcess)) {
                    $selectedOptionId = $optionsToProcess[0]['option_id'] ?? null;
                }

                $answer->update([
                    'response_type' => $responseType,
                    'response_text' => $input['response_text'] ?? null,
                    'response_number' => isset($input['response_number']) 
                    ? floatval(str_replace(',', '.', $input['response_number'])) 
                    : null,
                    'response_datetime' => isset($input['response_datetime']) 
                    ? Carbon::parse($input['response_datetime'])->format('Y-m-d H:i:s') 
                    : null,
                    'selected_option_id' => $selectedOptionId,
                    'comment' => $input['comment'] ?? null
                ]);

                // Não vamos mais deletar opções não selecionadas
                // Apenas atualizamos o status de seleção de todas as opções
                $selectedOptions = array_column($optionsToProcess, 'option_id');

                foreach ($optionsToProcess as $opt) {
                    $optionModel = $opt['type'] === 'selection'
                        ? SelectionOption::find($opt['option_id'])
                        : ItemEvalOption::find($opt['option_id']);

                    if ($finalize && !$optionModel) {
                        throw new \Exception("Opção inválida: {$opt['option_id']}");
                    }

                    if ($finalize && ($optionModel->require_comment ?? false) && empty($opt['comment'])) {
                        throw new \Exception("Comentário obrigatório para a opção {$opt['option_id']}");
                    }

                    if ($finalize && ($optionModel->require_attachment ?? false)) {
                        \Log::info('[Validação anexo] Buscando AnswerOption', [
                            'answer_id' => $answer->id,
                            'option_id' => $opt['option_id'],
                            'type' => $opt['type'],
                        ]);
                        $answerOption = AnswerOption::where('answer_id', $answer->id)
                            ->where('option_id', $opt['option_id'])
                            ->where('type', $opt['type'])
                            ->first();
                        \Log::info('[Validação anexo] AnswerOption encontrado', [
                            'answerOption_id' => $answerOption?->id
                        ]);
                        $hasAttachment = $answerOption && Attachment::where('answer_option_id', $answerOption->id)->exists();
                        \Log::info('[Validação anexo] Tem anexo?', [
                            'hasAttachment' => $hasAttachment
                        ]);
                        if (!$hasAttachment) {
                            throw new \Exception("Anexo obrigatório não enviado para a opção {$opt['option_id']}");
                        }
                    }

                    // Para cada opção no input, marcamos como selecionada
                    AnswerOption::updateOrCreate(
                        [
                            'answer_id' => $answer->id,
                            'option_id' => $opt['option_id'],
                            'type' => $opt['type']
                        ],
                        [
                            'comment' => $opt['comment'] ?? null,
                            'is_selected' => true // Marca a opção como selecionada
                        ]
                    );

                    // Se for uma seleção única, desmarca as outras opções
                    if ($type === 'selecao' && $selectionType === 'single') {
                        AnswerOption::where('answer_id', $answer->id)
                            ->where('option_id', '!=', $opt['option_id'])
                            ->where('type', $opt['type'])
                            ->update(['is_selected' => false]);
                    }
                }

                // Para as opções que não estão no input, marcamos como não selecionadas
                if (!empty($optionsToProcess)) {
                    $processedOptionIds = array_column($optionsToProcess, 'option_id');
                    AnswerOption::where('answer_id', $answer->id)
                        ->whereNotIn('option_id', $processedOptionIds)
                        ->update(['is_selected' => false]);
                }
            }

            if ($finalize) {
                $pending = Answer::with(['item.type', 'options'])
                    ->where('application_id', $applicationId)
                    ->get()
                    ->filter(function ($answer) {
                        $type = $answer->item->type->name ?? null;
                        $selectionType = strtolower($answer->item->selection_type ?? '');

                        if ($type === 'selecao' && $selectionType === 'multiple') {
                            return $answer->options->where('is_selected', true)->isEmpty();
                        }

                        if ($type === 'avaliativo') {
                            return $answer->options->where('is_selected', true)->isEmpty();
                        }

                        // Para o tipo 'arquivo', verificamos se há anexos
                        if ($type === 'arquivo') {
                            $hasAttachments = $answer->attachments->isNotEmpty();
                            if ($answer->item->require_attachment && !$hasAttachments) {
                                return true; // Item pendente se for obrigatório e não tiver anexo
                            }
                            return false; // Não é considerado pendente se não for obrigatório ou se tiver anexo
                        }

                        return is_null($answer->response_text)
                            && is_null($answer->response_number)
                            && is_null($answer->response_datetime)
                            && is_null($answer->selected_option_id);
                    })
                    ->count();

                if ($pending > 0) {
                    throw new \Exception("Ainda há {$pending} itens sem resposta.");
                }

                $application->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

                // Dispara o envio de e-mail em segundo plano
                $emailGroup = $application->emailGroup ?? $application->checklist->EmailGroup ?? null;

                if ($emailGroup && !empty($emailGroup->emails)) {
                    // Divide os e-mails por vírgula, remove espaços e valores vazios
                    $emails = array_filter(array_map('trim', explode(',', $emailGroup->emails)));
                    
                    // Filtra apenas e-mails válidos
                    $validEmails = array_filter($emails, function($email) {
                        return filter_var($email, FILTER_VALIDATE_EMAIL);
                    });
                    
                    if (!empty($validEmails)) {
                        // Remove duplicatas
                        $validEmails = array_unique($validEmails);
                        \Log::info('Enviando e-mails para: ' . implode(', ', $validEmails));
                        SendChecklistCompletedMail::dispatch($application, $validEmails)->onQueue('emails');
                    } else {
                        \Log::warning('Nenhum e-mail válido encontrado no grupo de e-mails: ' . $emailGroup->emails);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'message' => $finalize ? 'Checklist finalizado com sucesso.' : 'Respostas salvas parcialmente.'
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Erro ao salvar respostas.',
                'message' => $e->getMessage()
            ], 422);
        }
    }


}
