<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class ImageUploadService
{
    /**
     * Upload profile photo
     */
    public function uploadProfilePhoto(UploadedFile $file, $userId): string
    {
        // Generate unique filename
        $extension = $file->getClientOriginalExtension();
        $filename = 'profile_' . $userId . '_' . time() . '.' . $extension;
        
        // Resize and optimize image
        $image = Image::make($file)
            ->fit(500, 500) // Resize to 500x500
            ->encode($extension, 80); // Compress to 80% quality

        // Store in S3: profile-photos/{user_id}/
        $path = "profile-photos/{$userId}/{$filename}";
        Storage::disk('s3')->put($path, $image, 'public');

        return $path;
    }

    /**
     * Upload verification document
     */
    public function uploadDocument(UploadedFile $file, $userId, $documentType): string
    {
        // Generate unique filename
        $extension = $file->getClientOriginalExtension();
        $filename = $documentType . '_' . $userId . '_' . time() . '.' . $extension;
        
        // Store in S3: documents/verification/{user_id}/
        $path = "documents/verification/{$userId}/{$filename}";
        
        Storage::disk('s3')->put($path, file_get_contents($file), 'public');

        return $path;
    }

    /**
     * Delete file from storage
     */
    public function deleteFile(string $path, string $disk = 's3'): bool
    {
        if (Storage::disk($disk)->exists($path)) {
            return Storage::disk($disk)->delete($path);
        }
        
        return false;
    }

    /**
     * Get file URL
     */
    public function getFileUrl(string $path, string $disk = 's3'): ?string
    {
        if (Storage::disk($disk)->exists($path)) {
            return Storage::disk($disk)->url($path);
        }
        
        return null;
    }

    /**
     * Validate image dimensions
     */
    public function validateImageDimensions(UploadedFile $file, int $maxWidth = 2000, int $maxHeight = 2000): bool
    {
        $image = getimagesize($file->getRealPath());
        
        if (!$image) {
            return false;
        }

        return $image[0] <= $maxWidth && $image[1] <= $maxHeight;
    }
}