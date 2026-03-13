<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_answer_options', function (Blueprint $table) {
            $table->boolean('is_selected')->default(false)->after('comment');
        });
    }

    public function down(): void
    {
        Schema::table('app_answer_options', function (Blueprint $table) {
            $table->dropColumn('is_selected');
        });
    }
};
