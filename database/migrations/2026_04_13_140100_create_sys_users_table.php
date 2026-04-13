<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sys_users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('client_id')->nullable()->index();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_super_admin')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();

            $table->foreign('client_id')
                ->references('id')
                ->on('sys_clients')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_users');
    }
};
