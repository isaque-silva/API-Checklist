<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chk_input_masks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('label');
            $table->string('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chk_input_masks');
    }
};
