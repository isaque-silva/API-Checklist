<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_applications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('checklist_id');   
            $table->enum('status', ['completed', 'in_progress', 'deleted'])->default('in_progress');
    
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();

            $table->foreign('checklist_id')->references('id')->on('chk_checklists')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_applications');
    }
};
