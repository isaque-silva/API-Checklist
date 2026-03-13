<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chk_checklists', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('description')->nullable();
            $table->uuid('email_group_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
        });
        
        // Garante que a tabela sys_email_groups exista antes de adicionar a chave estrangeira
        if (Schema::hasTable('sys_email_groups')) {
            Schema::table('chk_checklists', function (Blueprint $table) {
                $table->foreign('email_group_id')
                      ->references('id')
                      ->on('sys_email_groups')
                      ->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('chk_checklists');
    }
};
