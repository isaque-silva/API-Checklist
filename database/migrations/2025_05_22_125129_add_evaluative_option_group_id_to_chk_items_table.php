<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chk_items', function (Blueprint $table) {
            $table->uuid('evaluative_option_group_id')->nullable()->after('selection_type');
        });
    }

    public function down(): void
    {
        Schema::table('chk_items', function (Blueprint $table) {
            $table->dropColumn('evaluative_option_group_id');
        });
    }
};
