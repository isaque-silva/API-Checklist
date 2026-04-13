<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chk_checklists', function (Blueprint $table) {
            $table->uuid('client_id')->nullable()->after('id')->index();
            $table->foreign('client_id')
                ->references('id')
                ->on('sys_clients')
                ->onDelete('cascade');
        });

        Schema::table('app_applications', function (Blueprint $table) {
            $table->uuid('client_id')->nullable()->after('id')->index();
            $table->foreign('client_id')
                ->references('id')
                ->on('sys_clients')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('chk_checklists', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropIndex(['client_id']);
            $table->dropColumn('client_id');
        });

        Schema::table('app_applications', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropIndex(['client_id']);
            $table->dropColumn('client_id');
        });
    }
};
