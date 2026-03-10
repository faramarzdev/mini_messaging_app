<?php

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
        Schema::create('chats', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(User::class, 'owner_id')->constrained('users')->nullOnDelete();
            $table->string('name', 95);
            $table->string('name', 255)->nullable()->default(null);
            $table->string('slug', 95)->unique();
            $table->text('members')->nullable()->default(null);


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};
