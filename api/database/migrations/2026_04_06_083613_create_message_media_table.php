<?php

use App\Models\Message;
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
        Schema::create('message_media', function (Blueprint $table) {
            $table->uuid('uuid')->primary();

            $table->foreignIdFor(Message::class, 'message_id')->constrained()->cascadeOnDelete();

            $table->string('disk');
            $table->string('path');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');

            $table->timestamp('created_at')->useCurrent();

            $table->index('message_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_media');
    }
};
