<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('communities', function (Blueprint $table) {
            $table->string('search_name')
                ->nullable()
                ->after('name');

            $table->index('search_name');
        });
    }

    public function down(): void
    {
        Schema::table('communities', function (Blueprint $table) {
            $table->dropIndex([
                'communities_search_name_index',
            ]);

            $table->dropColumn('search_name');
        });
    }
};