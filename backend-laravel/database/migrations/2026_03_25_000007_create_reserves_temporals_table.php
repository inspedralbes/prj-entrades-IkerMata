<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reserves_temporals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seient_id')->constrained('seients')->onDelete('cascade');
            $table->foreignId('sessio_id')->constrained('sessions')->onDelete('cascade');
            $table->uuid('usuari_id');
            $table->foreign('usuari_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->unique(['seient_id', 'sessio_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reserves_temporals');
    }
};
