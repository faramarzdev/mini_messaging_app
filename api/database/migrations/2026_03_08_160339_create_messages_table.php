<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(User::class, 'sender_id')->nullable()->constrained('users')->nullOnDelete();

            //
            $table->foreignIdFor(User::class, 'receiver_id')->nullable()->constrained('users')->nullOnDelete();

            $table->text('content');
            $table->string('type', 50)->default('text'); // e.g., 'text', 'image', 'video'
            $table->boolean('is_read')->default(false);
            $table->timestamp('sent_at')->useCurrent();

            $table->timestamps();
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
