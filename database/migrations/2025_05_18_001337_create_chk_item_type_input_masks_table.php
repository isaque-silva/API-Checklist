<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chk_item_type_input_masks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('item_type_id');
            $table->uuid('input_mask_id');
            $table->timestamps();

            $table->foreign('item_type_id')->references('id')->on('chk_item_types')->onDelete('cascade');
            $table->foreign('input_mask_id')->references('id')->on('chk_input_masks')->onDelete('cascade');

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chk_item_type_input_masks');
    }
};
