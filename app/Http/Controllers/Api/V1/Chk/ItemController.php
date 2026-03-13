<?php

namespace App\Http\Controllers\Api\V1\Chk;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

//Models
use App\Models\Chk\Item;
use App\Models\Chk\SelectionOption;
use App\Models\Chk\ItemEvalOption;
use App\Models\Chk\EvalOption;
use App\Models\Chk\EvalOptionGroup;
use App\Models\Chk\ItemType;
use App\Models\Chk\Area;

class ItemController
{
    private function error(string $message, int $status = 422): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $message
        ], $status);
    }

    public function index(string $areaId): JsonResponse
    {
        $area = Area::find($areaId);
        if (!$area) {
            return $this->error('The informed area does not exist.');
        }

         $items = $area->items()->with('type')->get();
        if ($items->isEmpty()) {
            return $this->error('No items found for the informed area.');
        }

        $items->each(function ($item) {
            $item->loadMissing('type');

            switch ($item->type->name) {
                case 'texto':
                case 'numero':
                case 'data':
                    $item->loadMissing('mask');
                    break;
                case 'selecao':
                    $item->loadMissing('selectionOptions');
                    break;
                case 'avaliativo':
                    $item->loadMissing(['evalOptions.evalOption']);
                    break;
                case 'arquivo':
                    // Nada adicional a carregar para o tipo arquivo
                    break;
            }
        });

        return response()->json(['items' => $items]);
    }

    public function show(string $id): JsonResponse
    {
        $item = Item::with('type')->find($id);
        if (!$item) {
            return $this->error('Item não encontrado.');
        }

        switch ($item->type->name) {
            case 'texto':
            case 'numero':
            case 'data':
                $item->loadMissing('mask');
                break;
            case 'selecao':
                $item->loadMissing('selectionOptions');
                break;
            case 'avaliativo':
                $item->loadMissing(['evalOptions.evalOption']);
                break;
        }

        return response()->json(['item' => $item]);
    }

    public function store(Request $request, string $areaId): JsonResponse
    {
        $area = Area::find($areaId);

        if (!$area) {
            Log::error('Erro ao criar item: área não encontrada.', ['area_id' => $areaId, 'payload' => $request->all()]);
            return $this->error('A área informada não existe.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'filter' => 'nullable||boolean',
            'item_type_id' => 'required|uuid',
            'input_mask_id' => 'nullable|uuid',
            'selection_type' => 'nullable|in:single,multiple',
            'allow_attachment' => 'nullable|boolean',
            'require_attachment' => 'nullable|boolean',
            'allow_comment' => 'nullable|boolean',
            'require_comment' => 'nullable|boolean',
            'evaluative_option_group_id' => 'nullable|uuid',
            'options' => 'nullable|array',
            'options.*.value' => 'required_without:options.*.eval_option_id|string',
            'options.*.eval_option_id' => 'required_without:options.*.value|uuid',
            'options.*.require_attachment' => 'boolean',
            'options.*.require_comment' => 'boolean',
        ]);

        $type = ItemType::find($validated['item_type_id']);
        if (!$type) {
            Log::error('Erro ao criar item: tipo de item não encontrado.', ['item_type_id' => $validated['item_type_id'], 'payload' => $request->all()]);
            return $this->error('The item type informed does not exist.');
        }

        DB::beginTransaction();

        try {
            $allow_attachment = $validated['allow_attachment'] ?? true;
            $allow_comment = $validated['allow_comment'] ?? true;
            $requireAttachment = $allow_attachment ? ($validated['require_attachment'] ?? false) : false;
            $requireComment = $allow_comment ? ($validated['require_comment'] ?? false) : false;

            $itemData = [
                'id' => Str::uuid(),
                'checklist_area_id' => $areaId,
                'name' => $validated['name'],
                'filter' => $validated['filter'] ?? false,
                'item_type_id' => $validated['item_type_id'],
                'allow_attachment' => $allow_attachment,
                'allow_comment' => $allow_comment,
                'require_attachment' => $requireAttachment,
                'require_comment' => $requireComment,
            ];

            $validOptions = [];

            switch ($type->name) {
                case 'texto':
                case 'numero':
                case 'data':
                    if (empty($validated['input_mask_id'])) {
                        throw new \Exception('The input mask must be informed for the selected field type.');
                    }

                    $isValid = $type->ItemTypeInputMask()->where('input_mask_id', $validated['input_mask_id'])->exists();
                    if (!$isValid) {
                        throw new \Exception('The input mask informed is not compatible with the selected field type.');
                    }

                    $itemData['input_mask_id'] = $validated['input_mask_id'];
                    break;
                    
                case 'arquivo':
                    // Para o tipo arquivo, não precisamos de máscara ou opções adicionais
                    // Apenas garantimos que os campos de anexo e comentário sejam configurados corretamente
                    $itemData['allow_attachment'] = true; // Sempre permite anexo para o tipo arquivo
                    $itemData['require_attachment'] = $requireAttachment; // Mantém a configuração do usuário
                    break;

                case 'selecao':
                    if (empty($validated['selection_type']) || !in_array($validated['selection_type'], ['single', 'multiple'])) {
                        throw new \Exception('The selection type must be informed and valid for the selected field type.');
                    }
                    $itemData['selection_type'] = $validated['selection_type'];
                    break;

                case 'avaliativo':
                    if (empty($validated['evaluative_option_group_id'])) {
                        throw new \Exception('The evaluative option group must be informed for the selected field type.');
                    }

                    $group = EvalOptionGroup::find($validated['evaluative_option_group_id']);
                    if (!$group) {
                        throw new \Exception('The evaluative option group informed does not exist.');
                    }

                    $itemData['evaluative_option_group_id'] = $validated['evaluative_option_group_id'];
                    $validOptions = EvalOption::where('evaluative_option_group_id', $validated['evaluative_option_group_id'])->pluck('id')->toArray();
                    break;

                default:
                    throw new \Exception('The selected field type is not valid.');
            }

            $item = Item::create($itemData);

            $uniqueEvalOptions = [];

            foreach ($validated['options'] ?? [] as $opt) {
                $requireAttachmentOpt = $allow_attachment ? ($opt['require_attachment'] ?? false) : false;
                $requireCommentOpt = $allow_comment ? ($opt['require_comment'] ?? false) : false;

                if ($type->name === 'selecao' && isset($opt['value'])) {
                    SelectionOption::create([
                        'id' => Str::uuid(),
                        'checklist_item_id' => $item->id,
                        'value' => $opt['value'],
                        'require_attachment' => $requireAttachmentOpt,
                        'require_comment' => $requireCommentOpt,
                    ]);
                }

                if ($type->name === 'avaliativo' && isset($opt['eval_option_id'])) {
                    if (in_array($opt['eval_option_id'], $uniqueEvalOptions)) {
                        Log::warning('Opção avaliativa repetida ignorada.', ['eval_option_id' => $opt['eval_option_id']]);
                        continue;
                    }
                    $uniqueEvalOptions[] = $opt['eval_option_id'];

                    if (!in_array($opt['eval_option_id'], $validOptions)) {
                        throw new \Exception('The evaluative option does not belong to the informed group.');
                    }

                    ItemEvalOption::create([
                        'id' => Str::uuid(),
                        'checklist_item_id' => $item->id,
                        'eval_option_id' => $opt['eval_option_id'],
                        'require_attachment' => $requireAttachmentOpt,
                        'require_comment' => $requireCommentOpt,
                    ]);
                }
            }

            DB::commit();

            $item->load('selectionOptions', 'evalOptions');

            return response()->json([
                'message' => 'Item criado com sucesso',
                'item' => $item
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Erro ao criar item.', [
                'area_id' => $areaId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all()
            ]);

            return $this->error($e->getMessage());
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $item = Item::with('type')->find($id);
        if (!$item) {
            Log::error('Erro ao atualizar item: item não encontrado.', ['item_id' => $id]);
            return $this->error('Item não encontrado.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'item_type_id' => 'required|uuid',
            'filter' => 'nullable|boolean',
            'input_mask_id' => 'nullable|uuid',
            'selection_type' => 'nullable|in:single,multiple',
            'allow_attachment' => 'nullable|boolean',
            'require_attachment' => 'nullable|boolean',
            'allow_comment' => 'nullable|boolean',
            'require_comment' => 'nullable|boolean',
            'evaluative_option_group_id' => 'nullable|uuid',
            'options' => 'nullable|array',
            'options.*.value' => 'required_without:options.*.eval_option_id|string',
            'options.*.eval_option_id' => 'required_without:options.*.value|uuid',
            'options.*.require_attachment' => 'boolean',
            'options.*.require_comment' => 'boolean',
        ]);

        $type = ItemType::find($validated['item_type_id']);
        if (!$type) {
            Log::error('Erro ao atualizar item: tipo de item não encontrado.', ['item_type_id' => $validated['item_type_id']]);
            return $this->error('O tipo de item informado não existe.');
        }

        DB::beginTransaction();

        try {
            $allow_attachment = $validated['allow_attachment'] ?? true;
            $allow_comment = $validated['allow_comment'] ?? true;
            $requireAttachment = $allow_attachment ? ($validated['require_attachment'] ?? false) : false;
            $requireComment = $allow_comment ? ($validated['require_comment'] ?? false) : false;

            $item->update([
                'name' => $validated['name'],
                'item_type_id' => $validated['item_type_id'],
                'filter' => $validated['filter'] ?? false,
                'allow_attachment' => $allow_attachment,
                'allow_comment' => $allow_comment,
                'require_attachment' => $requireAttachment,
                'require_comment' => $requireComment,
                'input_mask_id' => null,
                'selection_type' => null,
                'evaluative_option_group_id' => null,
            ]);

            switch ($type->name) {
                case 'texto':
                case 'numero':
                case 'data':
                    if (empty($validated['input_mask_id'])) {
                        throw new \Exception('A máscara de entrada deve ser informada.');
                    }
                    $isValid = $type->ItemTypeInputMask()->where('input_mask_id', $validated['input_mask_id'])->exists();
                    if (!$isValid) {
                        throw new \Exception('A máscara de entrada não é compatível com o tipo de item.');
                    }
                    $item->input_mask_id = $validated['input_mask_id'];
                    $item->save();
                    break;
                    
                case 'arquivo':
                    // Para o tipo arquivo, garantimos que os campos de anexo e comentário sejam configurados corretamente
                    $item->allow_attachment = true; // Sempre permite anexo para o tipo arquivo
                    $item->require_attachment = $requireAttachment; // Mantém a configuração do usuário
                    $item->save();
                    break;

                case 'selecao':
                    if (empty($validated['selection_type']) || !in_array($validated['selection_type'], ['single', 'multiple'])) {
                        throw new \Exception('O tipo de seleção deve ser informado e válido.');
                    }
                    $item->selection_type = $validated['selection_type'];
                    $item->save();

                    $item->selectionOptions()->delete();

                    foreach ($validated['options'] ?? [] as $opt) {
                        $requireAttachmentOpt = $allow_attachment ? ($opt['require_attachment'] ?? false) : false;
                        $requireCommentOpt = $allow_comment ? ($opt['require_comment'] ?? false) : false;

                        SelectionOption::create([
                            'id' => Str::uuid(),
                            'checklist_item_id' => $item->id,
                            'value' => $opt['value'],
                            'require_attachment' => $requireAttachmentOpt,
                            'require_comment' => $requireCommentOpt,
                        ]);
                    }
                    break;

                case 'avaliativo':
                    if (empty($validated['evaluative_option_group_id'])) {
                        throw new \Exception('O grupo de opções avaliativas deve ser informado.');
                    }

                    $group = EvalOptionGroup::find($validated['evaluative_option_group_id']);
                    if (!$group) {
                        throw new \Exception('O grupo de opções avaliativas não existe.');
                    }

                    $item->evaluative_option_group_id = $validated['evaluative_option_group_id'];
                    $item->save();

                    $validOptions = EvalOption::where('evaluative_option_group_id', $validated['evaluative_option_group_id'])->pluck('id')->toArray();

                    $item->evalOptions()->delete();

                    $uniqueEvalOptions = [];

                    foreach ($validated['options'] ?? [] as $opt) {
                        if (in_array($opt['eval_option_id'], $uniqueEvalOptions)) {
                            Log::warning('Opção avaliativa repetida ignorada.', ['eval_option_id' => $opt['eval_option_id'], 'item_id' => $id]);
                            continue;
                        }

                        $uniqueEvalOptions[] = $opt['eval_option_id'];

                        if (!in_array($opt['eval_option_id'], $validOptions)) {
                            throw new \Exception('A opção avaliativa informada não existe ou não pertence ao grupo.');
                        }

                        $requireAttachmentOpt = $allow_attachment ? ($opt['require_attachment'] ?? false) : false;
                        $requireCommentOpt = $allow_comment ? ($opt['require_comment'] ?? false) : false;

                        ItemEvalOption::create([
                            'id' => Str::uuid(),
                            'checklist_item_id' => $item->id,
                            'eval_option_id' => $opt['eval_option_id'],
                            'require_attachment' => $requireAttachmentOpt,
                            'require_comment' => $requireCommentOpt,
                        ]);
                    }
                    break;
            }

            DB::commit();

            return response()->json([
                'message' => 'Item atualizado com sucesso.',
                'item' => $item->fresh()
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Erro ao atualizar item.', [
                'item_id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->error($e->getMessage());
        }
    }


    public function destroy(string $id): JsonResponse
    {
        $item = Item::with('type', 'selectionOptions', 'evalOptions')->find($id);
        if (!$item) {
            return $this->error('Item não encontrado.');
        }

        switch ($item->type->name) {
            case 'selecao':
                $item->selectionOptions()->delete();
                break;
            case 'avaliativo':
                $item->evalOptions()->delete();
                break;
        }

        $item->delete();

        return response()->json(['message' => 'Item deletado com sucesso.']);
    }

   }
