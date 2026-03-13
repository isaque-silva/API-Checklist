<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chk_eval_options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('evaluative_option_group_id');
            $table->string('option_value');
            $table->timestamps();
            $table->softDeletes();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();

            $table->foreign('evaluative_option_group_id')->references('id')->on('chk_eval_option_groups')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chk_eval_options');
    }
};
