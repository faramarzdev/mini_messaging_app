<?php

use App\Enums\ChannelMemberStatus;
use App\Enums\ChannelRoles;
use App\Models\Channel;
use App\Models\Message;
use App\Models\Profile;
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
        Schema::create('channel_members', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(Channel::class, 'channel_id')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Profile::class, 'profile_id')->constrained()->cascadeOnDelete();
            $table->unique(['channel_id', 'profile_id']);

            $table->foreignIdFor(Message::class, 'last_read_message_id')->nullable()->default(null)->constrained()->nullOnDelete();

            $table->enum('role', ChannelRoles::cases())->default(ChannelRoles::Member->value);

            $table->enum('status', ChannelMemberStatus::cases())->default(ChannelMemberStatus::Pending->value);

            $table->timestamp('joined_at')->useCurrent();

            $table->index('profile_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('channel_members');
    }
};
