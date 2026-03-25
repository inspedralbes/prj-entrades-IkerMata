<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('preus_sessio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sessio_id')->constrained('sessions')->onDelete('cascade');
            $table->foreignId('categoria_id')->constrained('categories_seients')->onDelete('cascade');
            $table->decimal('preu', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preus_sessio');
    }
};
