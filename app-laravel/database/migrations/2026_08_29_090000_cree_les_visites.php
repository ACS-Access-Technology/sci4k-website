<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visites', function (Blueprint $table) {
            $table->id();
            $table->string('chemin', 255)->index();
            $table->char('session_hash', 64)->index();
            $table->string('user_agent', 255)->nullable();
            $table->dateTime('visitee_le')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visites');
    }
};
