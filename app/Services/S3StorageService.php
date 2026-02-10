<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class S3StorageService
{
    /**
     * Upload profile photo to S3
     * Path: profile-photos/{user_id}/profile_{user_id}_{timestamp}.{ext}
     */
    public function uploadProfilePhoto(UploadedFile $file, $userId): string
    {
        $extension = $file->getClientOriginalExtension();
        $filename = "profile_{$userId}_" . time() . ".{$extension}";
        $path = "profile-photos/{$userId}/{$filename}";
        
        Storage::disk('s3')->put($path, file_get_contents($file), 'public');
        
        return $path;
    }

    /**
     * Upload verification document to S3
     * Path: documents/verification/{user_id}/{document_type}_{user_id}_{timestamp}.{ext}
     */
    public function uploadDocument(UploadedFile $file, $userId, string $documentType): string
    {
        $extension = $file->getClientOriginalExtension();
        $filename = "{$documentType}_{$userId}_" . time() . ".{$extension}";
        $path = "documents/verification/{$userId}/{$filename}";
        
        Storage::disk('s3')->put($path, file_get_contents($file), 'public');
        
        return $path;
    }

    /**
     * Upload message attachment to S3
     * Path: messages/{type}/{conversation_id}/{filename}
     * Types: images, videos, documents, audio
     */
    public function uploadMessageAttachment(UploadedFile $file, $conversationId, string $type = 'documents'): string
    {
        $extension = $file->getClientOriginalExtension();
        $filename = Str::random(20) . '_' . time() . ".{$extension}";
        $path = "messages/{$type}/{$conversationId}/{$filename}";
        
        Storage::disk('s3')->put($path, file_get_contents($file), 'public');
        
        return $path;
    }

    /**
     * Upload slider image to S3
     * Path: cms/sliders/desktop/ or cms/sliders/mobile/
     */
    public function uploadSliderImage(UploadedFile $file, bool $isMobile = false): string
    {
        $extension = $file->getClientOriginalExtension();
        $filename = 'slider_' . Str::random(15) . '_' . time() . ".{$extension}";
        $folder = $isMobile ? 'cms/sliders/mobile' : 'cms/sliders/desktop';
        $path = "{$folder}/{$filename}";
        
        Storage::disk('s3')->put($path, file_get_contents($file), 'public');
        
        return $path;
    }

    /**
     * Upload CMS setting image to S3
     * Path: cms/settings/{setting_key}/
     */
    public function uploadSettingImage(UploadedFile $file, string $settingKey): string
    {
        $extension = $file->getClientOriginalExtension();
        $filename = $settingKey . '_' . time() . ".{$extension}";
        $path = "cms/settings/{$settingKey}/{$filename}";
        
        Storage::disk('s3')->put($path, file_get_contents($file), 'public');
        
        return $path;
    }

    /**
     * Upload listing image to S3
     * Path: listings/images/{listing_id}/
     */
    public function uploadListingImage(UploadedFile $file, $listingId): string
    {
        $extension = $file->getClientOriginalExtension();
        $filename = 'listing_' . Str::random(10) . '_' . time() . ".{$extension}";
        $path = "listings/images/{$listingId}/{$filename}";
        
        Storage::disk('s3')->put($path, file_get_contents($file), 'public');
        
        return $path;
    }

    /**
     * Upload review image to S3
     * Path: reviews/images/{review_id}/
     */
    public function uploadReviewImage(UploadedFile $file, $reviewId): string
    {
        $extension = $file->getClientOriginalExtension();
        $filename = 'review_' . Str::random(10) . '_' . time() . ".{$extension}";
        $path = "reviews/images/{$reviewId}/{$filename}";
        
        Storage::disk('s3')->put($path, file_get_contents($file), 'public');
        
        return $path;
    }

    /**
     * Delete file from S3
     */
    public function deleteFile(string $path): bool
    {
        if (Storage::disk('s3')->exists($path)) {
            return Storage::disk('s3')->delete($path);
        }
        
        return false;
    }

    /**
     * Get public URL for S3 file
     */
    public function getFileUrl(string $path): string
    {
        return Storage::disk('s3')->url($path);
    }

    /**
     * Get temporary signed URL for private S3 file
     * @param string $path
     * @param int $expiration Minutes until expiration (default: 60)
     */
    public function getSignedUrl(string $path, int $expiration = 60): string
    {
        return Storage::disk('s3')->temporaryUrl($path, now()->addMinutes($expiration));
    }

    /**
     * Check if file exists in S3
     */
    public function fileExists(string $path): bool
    {
        return Storage::disk('s3')->exists($path);
    }

    /**
     * Get file size in bytes
     */
    public function getFileSize(string $path): int
    {
        return Storage::disk('s3')->size($path);
    }

    /**
     * Get file MIME type
     */
    public function getFileMimeType(string $path): string
    {
        return Storage::disk('s3')->mimeType($path);
    }

    /**
     * Determine attachment type from MIME type
     */
    public function getAttachmentTypeFromMime(string $mimeType): string
    {
        if (str_contains($mimeType, 'image')) {
            return 'images';
        } elseif (str_contains($mimeType, 'video')) {
            return 'videos';
        } elseif (str_contains($mimeType, 'audio')) {
            return 'audio';
        } else {
            return 'documents';
        }
    }
}
