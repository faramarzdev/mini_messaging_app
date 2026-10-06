<?php

use App\Enums\ChannelJoinModes;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->enum('join_mode', ChannelJoinModes::cases())->default(ChannelJoinModes::Open);
        });

        DB::table('channels')
            ->where('confirm_joined', true)
            ->update(['join_mode' => ChannelJoinModes::ApprovalNeeded]);
        DB::table('channels')
            ->where('can_join_by_link', false)
            ->update(['join_mode' => ChannelJoinModes::Closed]);

        Schema::table('channels', function (Blueprint $table) {
            $table->dropColumn(['can_join_by_link', 'confirm_joined']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->boolean('can_join_by_link')->default(false);
            $table->boolean('confirm_joined')->default(false);

            $table->dropColumn('join_mode');
        });
    }
};
