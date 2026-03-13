<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_answer_options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('answer_id');
            $table->uuid('option_id'); // ID da opção selecionada (SelectionOption ou ItemEvalOption)
            $table->enum('type', ['selection', 'evaluative']);
            $table->text('comment')->nullable();

            $table->timestamps();
            $table->softDeletes();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();

            $table->foreign('answer_id')->references('id')->on('app_answers')->onDelete('cascade');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('app_answer_options');
    }
};
