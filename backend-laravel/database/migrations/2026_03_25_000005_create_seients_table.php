<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('seients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sala_id')->constrained('sales')->onDelete('cascade');
            $table->string('fila');
            $table->integer('numero');
            $table->foreignId('categoria_id')->constrained('categories_seients')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seients');
    }
};
