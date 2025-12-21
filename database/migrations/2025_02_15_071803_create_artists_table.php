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
        Schema::create('artists', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->unsignedBigInteger('prn_artist_id')->unique();
            $table->string('spotify', 64)->nullable()->unique();
            $table->string('instagram', 100)->nullable()->index();
            $table->string('twitter', 100)->nullable()->index();
            $table->string('facebook', 100)->nullable()->index();
            $table->string('youtube', 128)->nullable()->index();
            $table->string('homepage', 255)->nullable();
            $table->string('apple_music', 128)->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('artists');
    }
};
