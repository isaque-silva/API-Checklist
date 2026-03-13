<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chk_items', function (Blueprint $table) {
            $table->boolean('filter')->default(false)->after('require_attachment');
        });
    }

    public function down(): void
    {
        Schema::table('chk_items', function (Blueprint $table) {
            $table->dropColumn('filter');
        });
    }
};
