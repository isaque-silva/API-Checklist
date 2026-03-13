<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sys_email_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('description')->nullable();
            $table->text('emails');
            $table->timestamps();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();

            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_email_groups');
    }
};
