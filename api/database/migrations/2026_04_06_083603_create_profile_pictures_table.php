<?php

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
        Schema::create('profile_pictures', function (Blueprint $table) {
            $table->uuid('uuid')->primary();

            $table->foreignIdFor(Profile::class, 'profile_id')->constrained()->cascadeOnDelete();

            $table->string('path'); // relative path within disk
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedInteger('size');

            $table->timestamp('created_at')->useCurrent();

            $table->index('profile_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profile_pictures');
    }
};
