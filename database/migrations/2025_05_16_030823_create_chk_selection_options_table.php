<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chk_selection_options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('checklist_item_id')->nullable();
            $table->string('value');
            $table->boolean('require_attachment')->default(false);
            $table->boolean('require_comment')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();

            $table->foreign('checklist_item_id')->references('id')->on('chk_items')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chk_selection_options');
    }
};
