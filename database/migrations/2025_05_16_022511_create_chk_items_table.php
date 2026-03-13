<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chk_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('checklist_area_id');
            $table->uuid('item_type_id');
            $table->uuid('input_mask_id')->nullable();
            $table->string('name');
            $table->enum('selection_type', ['single', 'multiple'])->nullable();
            $table->boolean('allow_attachment')->default(true);
            $table->boolean('require_attachment')->default(false);
            $table->boolean('allow_comment')->default(true);
            $table->boolean('require_comment')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->foreign('checklist_area_id')->references('id')->on('chk_areas')->onDelete('cascade');
            $table->foreign('item_type_id')->references('id')->on('chk_item_types');
            $table->foreign('input_mask_id')->references('id')->on('chk_input_masks');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chk_items');
    }
};
