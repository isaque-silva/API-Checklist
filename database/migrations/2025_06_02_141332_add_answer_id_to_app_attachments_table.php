<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_attachments', function (Blueprint $table) {
            $table->uuid('answer_id')->nullable()->after('answer_option_id');

            $table->foreign('answer_id')
                  ->references('id')
                  ->on('app_answers')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('app_attachments', function (Blueprint $table) {
            $table->dropForeign(['answer_id']);
            $table->dropColumn('answer_id');
        });
    }
};
