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
            $table->boolean('is_favorite')->default(false)->after('content');
            // Check if soft deletes is already there or needed, user asked to "Delete" messages. 
            // Usually "Delete for everyone" deletes the record, "Delete for me" requires pivot or complex logic.
            // For now assuming hard delete or simple soft delete as per existing deleteMessage controller.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('is_favorite');
        });
    }
};
