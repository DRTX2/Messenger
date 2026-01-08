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
        // 1. Conversations Table (Groups & P2P)
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique()->index(); // For safer frontend URLs
            $table->boolean('is_group')->default(false);
            $table->string('name')->nullable(); // Group name
            $table->string('avatar_url')->nullable(); 
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('last_message_at')->nullable()->index(); // For sorting list
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Participants Table (Many-to-Many User<->Conversation)
        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('is_admin')->default(false); // For Group Admin
            $table->timestamp('formatted_last_read_at')->nullable(); // When did this user last read this chat?
            $table->timestamps();

            $table->unique(['conversation_id', 'user_id']);
        });

        // 3. Modify Messages Table (Link to Conversation, not just receiver)
        // We modify the existing table or create a fresh structure. 
        // Assuming we are upgrading, we'll add conversation_id and make receiver_id nullable (or dropping it in favor of logic).
        // For this "Enterprise Rewrite", it's cleaner to use a new structure or heavily modify. 
        // Let's modify the EXISTING table to keep it simple but powerful.
        
        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('conversation_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            $table->enum('type', ['text', 'image', 'file', 'video', 'voice', 'system'])->default('text')->after('content');
            $table->json('metadata')->nullable()->after('type'); // For file size, duration, width/height
            $table->foreignId('parent_id')->nullable()->references('id')->on('messages'); // For Threaded replies
            
            // Make content nullable for file-only messages
            $table->text('content')->nullable()->change();
            
            // We keep receiver_id for direct DM compatibility or migration, but core logic moves to conversation_id
            $table->foreignId('receiver_id')->nullable()->change();
        });

        // 4. Reactions Table (Polymorphic "Like", "Heart", etc.)
        Schema::create('message_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('emoji', 10); // The reaction itself
            $table->timestamps();

            $table->unique(['message_id', 'user_id', 'emoji']); // User can't react with same emoji twice
        });

        // 5. Attachments Table (If we want separated files, usually good for Enterprise management)
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('original_name');
            $table->string('mime_type');
            $table->string('path');
            $table->unsignedBigInteger('size_bytes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('message_reactions');
        Schema::dropIfExists('conversation_participants');
        
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['conversation_id']);
            $table->dropColumn(['conversation_id', 'type', 'metadata', 'parent_id']);
            // Note: Reverting changes to columns (nullable->not nullable) is tricky without raw SQL, skipping for brevity
        });

        Schema::dropIfExists('conversations');
    }
};
