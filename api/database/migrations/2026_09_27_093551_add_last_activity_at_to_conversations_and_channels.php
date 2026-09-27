<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->timestamp('last_activity_at')->nullable()->after('created_at');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->timestamp('last_activity_at')->nullable()->after('created_at');
        });

        // 2. Backfill from created_at
        DB::table('channels')->update([
            'last_activity_at' => DB::raw('created_at'),
        ]);

        DB::table('conversations')->update([
            'last_activity_at' => DB::raw('created_at'),
        ]);

        // 3. Tighten to NOT NULL
        Schema::table('channels', function (Blueprint $table) {
            $table->timestamp('last_activity_at')->nullable(false)->change();
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->timestamp('last_activity_at')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->dropColumn('last_activity_at');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn('last_activity_at');
        });
    }
};
