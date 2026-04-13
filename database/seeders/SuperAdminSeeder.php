<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sys\User;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = 'admin@checklist.com';

        if (User::where('email', $email)->exists()) {
            $this->command->info("Super Admin já existe ({$email}). Pulando...");
            return;
        }

        User::create([
            'name' => 'Super Admin',
            'email' => $email,
            'password' => Hash::make('admin123'),
            'client_id' => null,
            'is_active' => true,
            'is_super_admin' => true,
            'created_by' => 'system',
            'updated_by' => 'system',
        ]);

        $this->command->info("Super Admin criado com sucesso!");
        $this->command->info("  Email: {$email}");
        $this->command->info("  Senha: admin123");
        $this->command->warn("  IMPORTANTE: Altere a senha após o primeiro login!");
    }
}
