<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('encarts', function (Blueprint $table) {
            $table->dateTime('diffusion_de')->nullable()->index();
            $table->dateTime('diffusion_a')->nullable()->index();
            $table->unsignedBigInteger('impressions')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('encarts', function (Blueprint $table) {
            $table->dropColumn(['diffusion_de', 'diffusion_a', 'impressions']);
        });
    }
};
