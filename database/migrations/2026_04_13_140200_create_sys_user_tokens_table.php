<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sys_user_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index();
            $table->string('token', 128)->unique()->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('sys_users')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_user_tokens');
    }
};
