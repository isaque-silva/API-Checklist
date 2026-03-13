<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chk_areas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('checklist_id');
            $table->string('title');
            $table->string('description')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();

            $table->foreign('checklist_id')->references('id')->on('chk_checklists')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chk_areas');
    }
};
