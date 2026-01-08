<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Attachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentService
{
    /**
     * Store an uploaded file and create an Attachment record.
     * Note: Returns an "Orphaned" attachment (message_id = null)
     */
    public function upload(UploadedFile $file): Attachment
    {
        $uuid = (string) Str::uuid();
        $filename = $uuid . '.' . $file->getClientOriginalExtension();
        
        // Store in storage/app/public/attachments
        // Ensure you run: php artisan storage:link
        $path = $file->storeAs('attachments', $filename, 'public');

        return Attachment::create([
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'path' => $path,
            'size_bytes' => $file->getSize(),
            'message_id' => null, // Will be linked when message is sent
        ]);
    }

    /**
     * Link valid attachment IDs to a message
     */
    public function linkAttachmentsToMessage(int $messageId, array $attachmentIds): void
    {
        if (empty($attachmentIds)) {
            return;
        }

        // We only link attachments that are currently orphaned to prevent stealing
        Attachment::whereIn('id', $attachmentIds)
            ->whereNull('message_id')
            ->update(['message_id' => $messageId]);
    }
}
