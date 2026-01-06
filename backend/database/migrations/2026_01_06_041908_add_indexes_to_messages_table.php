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
        Schema::table('messages', function (Blueprint $table) {
            // Índices para mejorar rendimiento de queries
            $table->index(['sender_id', 'receiver_id', 'created_at']);
            $table->index(['receiver_id', 'read_at']); // Para obtener mensajes no leídos
            $table->index(['created_at']); // Para ordenamiento
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex(['sender_id', 'receiver_id', 'created_at']);
            $table->dropIndex(['receiver_id', 'read_at']);
            $table->dropIndex(['created_at']);
        });
    }
};
