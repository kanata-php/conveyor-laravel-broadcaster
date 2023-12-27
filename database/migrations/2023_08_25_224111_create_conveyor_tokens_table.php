<?php

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
        Schema::create('conveyor_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('name', 40);
            $table->string('token', 500);
            $table->dateTime('expire_at')->nullable()->comment('Expire date for token. Null when doesnt expire.');
            $table->string('aud', 100)->nullable()->comment('Audience: domain allowed to use token. Null when not restricted.');
            $table->string('aud_protocol', 10)->nullable();
            $table->integer('allowed_uses')->nullable()->comment('Number of times this token is allowed to be used.');
            $table->integer('uses')->nullable()->comment('Number of times this token as been used.');
            $table->foreignId('user_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conveyor_tokens');
    }
};
