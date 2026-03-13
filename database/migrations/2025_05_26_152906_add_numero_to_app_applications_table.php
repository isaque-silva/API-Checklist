<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_applications', function (Blueprint $table) {
            $table->unsignedBigInteger('number')->nullable()->unique();
        });

        // Atualiza os registros existentes com valores sequenciais
        DB::statement('SET @i := 0');
        DB::statement('UPDATE app_applications SET number = (@i := @i + 1)');
        
        // Adiciona AUTO_INCREMENT após popular os dados
        DB::statement('ALTER TABLE app_applications MODIFY COLUMN number BIGINT UNSIGNED AUTO_INCREMENT UNIQUE');
    }

    public function down(): void
    {
        Schema::table('app_applications', function (Blueprint $table) {
            $table->dropColumn('number');
        });
    }
};
