<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('answer_option_id')->nullable();
            $table->string('file_path');
            $table->string('file_name');
            $table->bigInteger('original_size')->nullable();
            $table->bigInteger('compressed_size')->nullable();
            $table->boolean('is_compressed')->default(false);

            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('answer_option_id')->references('id')->on('app_answer_options')->onDelete('cascade');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('app_attachments');
    }
};
