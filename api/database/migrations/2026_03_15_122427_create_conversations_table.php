<?php

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
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();

            // sender and receiver
            $table->foreignIdFor(Profile::class, 'lower_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_available_for_lower_profile')->default(true);

            $table->foreignIdFor(Profile::class, 'higher_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_available_for_higher_profile')->default(true);

            // unique together sender and receiver
            $table->unique(['lower_profile_id', 'higher_profile_id']);

            $table->foreignIdFor(Message::class, 'last_message_id')->nullable();

            $table->foreignIdFor(Message::class, 'lower_profile_last_read_message_id')->nullable();
            $table->foreignIdFor(Message::class, 'higher_profile_last_read_message_id')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
