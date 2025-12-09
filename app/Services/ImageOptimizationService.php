<?php

namespace App\Services;

use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageOptimizationService
{
    /**
     * Generate optimized images with multiple sizes
     */
    public function generateOptimizedImages($imagePath, $options = [])
    {
        $sizes = $options['sizes'] ?? [
            'thumbnail' => ['width' => 150, 'height' => 150],
            'small' => ['width' => 300, 'height' => 300],
            'medium' => ['width' => 600, 'height' => 600],
            'large' => ['width' => 1200, 'height' => 1200],
        ];

        $quality = $options['quality'] ?? 80;
        $disk = $options['disk'] ?? 'public';
        
        $optimizedImages = [];
        
        foreach ($sizes as $sizeName => $dimensions) {
            $optimizedPath = $this->generateOptimizedImage(
                $imagePath,
                $dimensions['width'],
                $dimensions['height'],
                $quality,
                $disk,
                $sizeName
            );
            
            if ($optimizedPath) {
                $optimizedImages[$sizeName] = [
                    'path' => $optimizedPath,
                    'url' => Storage::disk($disk)->url($optimizedPath),
                    'width' => $dimensions['width'],
                    'height' => $dimensions['height'],
                ];
            }
        }

        return $optimizedImages;
    }

    /**
     * Generate single optimized image
     */
    protected function generateOptimizedImage($originalPath, $width, $height, $quality, $disk, $sizeName)
    {
        try {
            // Get full path
            $fullPath = Storage::disk($disk)->path($originalPath);
            
            if (!file_exists($fullPath)) {
                return null;
            }

            // Load image
            $image = Image::make($fullPath);

            // Resize maintaining aspect ratio
            $image->fit($width, $height, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            // Generate new filename
            $pathInfo = pathinfo($originalPath);
            $newFilename = $pathInfo['filename'] . '_' . $sizeName . '.' . $pathInfo['extension'];
            $newPath = $pathInfo['dirname'] . '/optimized/' . $newFilename;

            // Create directory if not exists
            $fullNewPath = Storage::disk($disk)->path($newPath);
            $directory = dirname($fullNewPath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            // Save optimized image
            $image->save($fullNewPath, $quality);

            return $newPath;

        } catch (\Exception $e) {
            \Log::error('Image optimization failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate thumbnail
     */
    public function generateThumbnail($imagePath, $width = 150, $height = 150, $quality = 80)
    {
        return $this->generateOptimizedImage(
            $imagePath,
            $width,
            $height,
            $quality,
            'public',
            'thumb'
        );
    }

    /**
     * Compress image
     */
    public function compressImage($imagePath, $quality = 80, $disk = 'public')
    {
        try {
            $fullPath = Storage::disk($disk)->path($imagePath);
            
            if (!file_exists($fullPath)) {
                return false;
            }

            $image = Image::make($fullPath);
            $image->save($fullPath, $quality);

            return true;

        } catch (\Exception $e) {
            \Log::error('Image compression failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Convert to WebP format (better compression)
     */
    public function convertToWebP($imagePath, $quality = 80, $disk = 'public')
    {
        try {
            $fullPath = Storage::disk($disk)->path($imagePath);
            
            if (!file_exists($fullPath)) {
                return null;
            }

            $pathInfo = pathinfo($imagePath);
            $webpFilename = $pathInfo['filename'] . '.webp';
            $webpPath = $pathInfo['dirname'] . '/' . $webpFilename;
            $fullWebpPath = Storage::disk($disk)->path($webpPath);

            $image = Image::make($fullPath);
            $image->encode('webp', $quality);
            $image->save($fullWebpPath);

            return $webpPath;

        } catch (\Exception $e) {
            \Log::error('WebP conversion failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get image dimensions
     */
    public function getImageDimensions($imagePath, $disk = 'public')
    {
        try {
            $fullPath = Storage::disk($disk)->path($imagePath);
            
            if (!file_exists($fullPath)) {
                return null;
            }

            $image = Image::make($fullPath);

            return [
                'width' => $image->width(),
                'height' => $image->height(),
                'size' => filesize($fullPath),
                'mime_type' => $image->mime(),
            ];

        } catch (\Exception $e) {
            \Log::error('Get image dimensions failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Optimize uploaded image automatically
     */
    public function optimizeUpload($uploadedFile, $directory = 'uploads', $options = [])
    {
        // Store original
        $originalPath = $uploadedFile->store($directory, 'public');

        // Compress original
        $this->compressImage($originalPath, $options['quality'] ?? 85, 'public');

        // Generate optimized versions
        $optimizedImages = $this->generateOptimizedImages($originalPath, $options);

        // Generate WebP version
        $webpPath = $this->convertToWebP($originalPath, $options['quality'] ?? 85, 'public');

        return [
            'original' => [
                'path' => $originalPath,
                'url' => Storage::disk('public')->url($originalPath),
            ],
            'webp' => $webpPath ? [
                'path' => $webpPath,
                'url' => Storage::disk('public')->url($webpPath),
            ] : null,
            'optimized' => $optimizedImages,
        ];
    }

    /**
     * Delete optimized images
     */
    public function deleteOptimizedImages($originalPath, $disk = 'public')
    {
        try {
            $pathInfo = pathinfo($originalPath);
            $optimizedDir = $pathInfo['dirname'] . '/optimized/';

            // Delete all optimized versions
            $files = Storage::disk($disk)->files($optimizedDir);
            foreach ($files as $file) {
                if (Str::startsWith(basename($file), $pathInfo['filename'])) {
                    Storage::disk($disk)->delete($file);
                }
            }

            // Delete WebP version
            $webpPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.webp';
            if (Storage::disk($disk)->exists($webpPath)) {
                Storage::disk($disk)->delete($webpPath);
            }

            // Delete original
            Storage::disk($disk)->delete($originalPath);

            return true;

        } catch (\Exception $e) {
            \Log::error('Delete optimized images failed: ' . $e->getMessage());
            return false;
        }
    }
}