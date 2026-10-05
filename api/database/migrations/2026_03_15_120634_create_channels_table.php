<?php

use App\Enums\ChannelType;
use App\Enums\ChannelVisibility;
use App\Models\User;
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
        Schema::create('channels', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(User::class, 'owner_id')->constrained()->cascadeOnDelete();

            $table->string('name', 95);
            $table->string('description', 255)->nullable()->default(null);

            // type:       channel or group
            $table->enum('type', ChannelType::cases())->default(ChannelType::Channel);
            // visibility: private or public
            $table->enum('visibility', ChannelVisibility::cases())->default(ChannelVisibility::Private);

            $table->boolean('can_join_by_link')->default(false);
            $table->boolean('confirm_joined')->default(false);

            $table->unsignedBigInteger('messages_count')->default(1);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('channels');
    }
};
