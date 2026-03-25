<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('categories_seients', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('color_hex');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories_seients');
    }
};
