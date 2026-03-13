<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_answers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('application_id');
            $table->uuid('checklist_item_id');

            $table->enum('response_type', ['text', 'number', 'datetime', 'selection', 'evaluative', 'file']);

            $table->string('response_text')->nullable();
            $table->decimal('response_number', 18, 4)->nullable();
            $table->timestamp('response_datetime')->nullable();

            $table->uuid('selected_option_id')->nullable(); // Para seleção única
            $table->text('comment')->nullable(); // comentário geral, se aplicável

            $table->timestamps();
            $table->softDeletes();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();

            $table->foreign('application_id')->references('id')->on('app_applications')->onDelete('cascade');
            $table->foreign('checklist_item_id')->references('id')->on('chk_items')->onDelete('cascade');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('app_answers');
    }
};
