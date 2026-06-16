<?php

use App\Enums\MessageType;
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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(Profile::class, 'sender_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_available_on_sender')->default(true);

            $table->morphs('messageable');

            $table->boolean('is_available_on_receiver')->default(true);

            $table->text('body')->nullable();
            $table->enum('type', MessageType::cases())->default(MessageType::Text->value);

            $table->boolean('is_read')->default(0);

            $table->foreignIdFor(Message::class, 'reply_id')->nullable()->default(null)->constrained()->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('sender_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
