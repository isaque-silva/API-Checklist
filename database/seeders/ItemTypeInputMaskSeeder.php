<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ItemTypeInputMaskSeeder extends Seeder
{
    public function run(): void
    {
        // Busca os IDs dos tipos de itens
        $tipoTextoId = DB::table('chk_item_types')->where('name', 'texto')->value('id');
        $tipoNumeroId = DB::table('chk_item_types')->where('name', 'numero')->value('id');
        $tipoDataId = DB::table('chk_item_types')->where('name', 'data')->value('id');

        // Busca os IDs das máscaras de entrada
        $mascaras = [
            'texto_curto' => DB::table('chk_input_masks')->where('name', 'texto_curto')->value('id'),
            'texto_longo' => DB::table('chk_input_masks')->where('name', 'texto_longo')->value('id'),
            'monetario' => DB::table('chk_input_masks')->where('name', 'monetario')->value('id'),
            'porcentagem' => DB::table('chk_input_masks')->where('name', 'porcentagem')->value('id'),
            'quantidade' => DB::table('chk_input_masks')->where('name', 'quantidade')->value('id'),
            'decimal' => DB::table('chk_input_masks')->where('name', 'decimal')->value('id'),
            'data_completa' => DB::table('chk_input_masks')->where('name', 'data_completa')->value('id'),
            'mes_ano' => DB::table('chk_input_masks')->where('name', 'mes_ano')->value('id'),
            'hora_24h_segundos' => DB::table('chk_input_masks')->where('name', 'hora_24h_segundos')->value('id'),
            'hora_24h' => DB::table('chk_input_masks')->where('name', 'hora_24h')->value('id'),
        ];

        // Define os mapeamentos com os IDs buscados
        $mapeamentos = [
            // texto
            ['item_type_id' => $tipoTextoId, 'input_mask_id' => $mascaras['texto_curto']],
            ['item_type_id' => $tipoTextoId, 'input_mask_id' => $mascaras['texto_longo']],

            // número
            ['item_type_id' => $tipoNumeroId, 'input_mask_id' => $mascaras['monetario']],
            ['item_type_id' => $tipoNumeroId, 'input_mask_id' => $mascaras['porcentagem']],
            ['item_type_id' => $tipoNumeroId, 'input_mask_id' => $mascaras['quantidade']],
            ['item_type_id' => $tipoNumeroId, 'input_mask_id' => $mascaras['decimal']],

            // data
            ['item_type_id' => $tipoDataId, 'input_mask_id' => $mascaras['data_completa']],
            ['item_type_id' => $tipoDataId, 'input_mask_id' => $mascaras['mes_ano']],
            ['item_type_id' => $tipoDataId, 'input_mask_id' => $mascaras['hora_24h_segundos']],
            ['item_type_id' => $tipoDataId, 'input_mask_id' => $mascaras['hora_24h']],
        ];

        foreach ($mapeamentos as $map) {
            if ($map['item_type_id'] && $map['input_mask_id']) {
                DB::table('chk_item_type_input_masks')->insert([
                    'id' => Str::uuid(),
                    'item_type_id' => $map['item_type_id'],
                    'input_mask_id' => $map['input_mask_id'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
