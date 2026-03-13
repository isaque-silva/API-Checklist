<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FieldTypesSeeder extends Seeder
{
    public function run(): void
    {
        // Tipos de campo
        $tipos = [
            ['slug' => 'texto', 'label' => 'Texto'],
            ['slug' => 'numero', 'label' => 'Numérico'],
            ['slug' => 'data', 'label' => 'Data'],
            ['slug' => 'avaliativo', 'label' => 'Avaliativo'],
            ['slug' => 'selecao', 'label' => 'Lista de Seleção'],
            ['slug' => 'arquivo', 'label' => 'Arquivo']
        ];

        foreach ($tipos as $tipo) {
            $existing = DB::table('chk_item_types')->where('name', $tipo['slug'])->first();

            if ($existing) {
                DB::table('chk_item_types')->where('id', $existing->id)->update([
                    'label' => $tipo['label'],
                    'updated_at' => now()
                ]);
            } else {
                DB::table('chk_item_types')->insert([
                    'id' => Str::uuid(),
                    'name' => $tipo['slug'],
                    'label' => $tipo['label'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        // Máscaras de entrada
        $mascaras = [
            // texto
            ['name' => 'texto_curto', 'label' => 'Texto Curto'],
            ['name' => 'texto_longo', 'label' => 'Texto Longo'],

            // numérico
            ['name' => 'monetario', 'label' => 'Monetário'],
            ['name' => 'quantidade', 'label' => 'Quantidade'],
            ['name' => 'decimal', 'label' => 'Decimal'],
            ['name' => 'porcentagem', 'label' => 'Porcentagem'],

            // data/hora
            ['name' => 'hora_24h', 'label' => 'hh:mm'],
            ['name' => 'hora_24h_segundos', 'label' => 'hh:mm:ss'],
            ['name' => 'data_completa', 'label' => 'dd/mm/aaaa'],
            ['name' => 'mes_ano', 'label' => 'mm/aaaa'],
        ];

        foreach ($mascaras as $mask) {
            $existing = DB::table('chk_input_masks')->where('name', $mask['name'])->first();

            if ($existing) {
                DB::table('chk_input_masks')->where('id', $existing->id)->update([
                    'label' => $mask['label'],
                    'updated_at' => now()
                ]);
            } else {
                DB::table('chk_input_masks')->insert([
                    'id' => Str::uuid(),
                    'name' => $mask['name'],
                    'label' => $mask['label'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
    }
}
