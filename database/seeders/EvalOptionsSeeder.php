<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EvalOptionsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $grupos = [
            'Sim/Não' => ['Sim', 'Não'],
            'Bom/Ruim' => ['Bom', 'Ruim'],
            'Bom/Normal/Ruim' => ['Bom', 'Normal', 'Ruim'],
        ];

        foreach ($grupos as $groupName => $options) {
            // Verifica se o grupo já existe
            $groupId = DB::table('chk_eval_option_groups')
                ->where('name', $groupName)
                ->value('id');

            // Se não existir, cria
            if (!$groupId) {
                $groupId = (string) Str::uuid();
                DB::table('chk_eval_option_groups')->insert([
                    'id' => $groupId,
                    'name' => $groupName,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // Para cada opção do grupo
            foreach ($options as $optionValue) {
                // Verifica se já existe a opção com esse valor no grupo
                $exists = DB::table('chk_eval_options')
                    ->where('evaluative_option_group_id', $groupId)
                    ->where('option_value', $optionValue)
                    ->exists();

                if (!$exists) {
                    DB::table('chk_eval_options')->insert([
                        'id' => (string) Str::uuid(),
                        'evaluative_option_group_id' => $groupId,
                        'option_value' => $optionValue,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }
}
