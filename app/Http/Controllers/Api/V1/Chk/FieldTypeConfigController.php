<?php

namespace App\Http\Controllers\Api\V1\Chk;

use App\Http\Controllers\Controller;
use App\Models\Chk\ItemType;
use App\Models\Chk\InputMask;
use App\Models\Chk\EvalOptionGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class FieldTypeConfigController extends Controller
{

    public function index(): JsonResponse
    {
        $allMasks = InputMask::select('id', 'name', 'label')->get()->keyBy('id');

        // Grupos avaliativos com opções
        $evalOptionGroups = EvalOptionGroup::with(['options:id,evaluative_option_group_id,option_value'])->get()->map(function ($group) {
            return [
                'id' => $group->id,
                'name' => $group->name,
                'options' => $group->options->map(fn ($opt) => [
                    'id' => $opt->id,
                    'value' => $opt->option_value,
                ])
            ];
        });

        // Tipos de campo com máscaras, grupos e configuração de seleção
        $types = ItemType::select('id', 'name as slug', 'label')->get()->map(function ($type) use ($allMasks, $evalOptionGroups) {
            $maskIds = DB::table('chk_item_type_input_masks')
                ->where('item_type_id', $type->id)
                ->pluck('input_mask_id');

            return [
                'id' => $type->id,
                'slug' => $type->slug,
                'label' => $type->label,
                'requires_options' => in_array($type->slug, ['avaliativo', 'selecao']),
                'requires_mask' => $maskIds->isNotEmpty(),
                'masks' => $maskIds->map(fn ($id) => $allMasks[$id])->values(),
                'groups' => $type->slug === 'avaliativo' ? $evalOptionGroups : [],
                'selection_type' => $type->slug === 'selecao'
                    ?  ['single', 'multiple']
                    : null,
            ];
        });

        return response()->json(['types' => $types]);

    }
}
