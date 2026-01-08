<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UploadAttachmentRequest;
use App\Http\Resources\AttachmentResource;
use App\Services\AttachmentService;
use Illuminate\Http\JsonResponse;

class AttachmentController extends Controller
{
    public function __construct(protected AttachmentService $attachmentService)
    {
    }

    /**
     * Upload a file
     */
    public function store(UploadAttachmentRequest $request): JsonResponse
    {
        $attachment = $this->attachmentService->upload($request->file('file'));

        return response()->json([
            'success' => true,
            'message' => 'File uploaded successfully',
            'data' => new AttachmentResource($attachment)
        ], 201);
    }
}
