<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ApiResponseOptimizer
{
    /**
     * Optimize paginated response for mobile
     */
    public function optimizePaginatedResponse($data, $options = [])
    {
        $simplified = $options['simplified'] ?? false;
        $includeLinks = $options['include_links'] ?? false;

        if ($data instanceof LengthAwarePaginator) {
            $response = [
                'data' => $data->items(),
                'pagination' => [
                    'total' => $data->total(),
                    'count' => $data->count(),
                    'per_page' => $data->perPage(),
                    'current_page' => $data->currentPage(),
                    'total_pages' => $data->lastPage(),
                    'has_more' => $data->hasMorePages(),
                ],
            ];

            if (!$simplified && $includeLinks) {
                $response['pagination']['links'] = [
                    'first' => $data->url(1),
                    'last' => $data->url($data->lastPage()),
                    'prev' => $data->previousPageUrl(),
                    'next' => $data->nextPageUrl(),
                ];
            }

            return $response;
        }

        return ['data' => $data];
    }

    /**
     * Create minimal response (only essential fields)
     */
    public function createMinimalResponse($data, $fields = [])
    {
        if ($data instanceof Collection) {
            return $data->map(function ($item) use ($fields) {
                return $this->extractFields($item, $fields);
            });
        }

        return $this->extractFields($data, $fields);
    }

    /**
     * Extract specific fields from model
     */
    protected function extractFields($item, $fields)
    {
        if (empty($fields)) {
            return $item;
        }

        $result = [];
        foreach ($fields as $field) {
            if (isset($item->$field)) {
                $result[$field] = $item->$field;
            }
        }

        return $result;
    }

    /**
     * Add cache headers to response
     */
    public function addCacheHeaders($response, $ttl = 3600)
    {
        return $response->header('Cache-Control', "public, max-age={$ttl}")
                       ->header('Expires', gmdate('D, d M Y H:i:s', time() + $ttl) . ' GMT');
    }

    /**
     * Create cached response
     */
    public function cachedResponse($cacheKey, $callback, $ttl = 3600)
    {
        return Cache::remember($cacheKey, $ttl, $callback);
    }

    /**
     * Optimize image URLs for mobile
     */
    public function optimizeImageUrls($data, $size = 'medium')
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_string($value) && $this->isImageUrl($value)) {
                    $data[$key] = $this->getOptimizedImageUrl($value, $size);
                } elseif (is_array($value) || is_object($value)) {
                    $data[$key] = $this->optimizeImageUrls($value, $size);
                }
            }
        } elseif (is_object($data)) {
            foreach ($data as $key => $value) {
                if (is_string($value) && $this->isImageUrl($value)) {
                    $data->$key = $this->getOptimizedImageUrl($value, $size);
                } elseif (is_array($value) || is_object($value)) {
                    $data->$key = $this->optimizeImageUrls($value, $size);
                }
            }
        }

        return $data;
    }

    /**
     * Check if URL is an image
     */
    protected function isImageUrl($url)
    {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));
        return in_array($extension, $imageExtensions);
    }

    /**
     * Get optimized image URL
     */
    protected function getOptimizedImageUrl($originalUrl, $size = 'medium')
    {
        // Replace original path with optimized version
        $pathInfo = pathinfo($originalUrl);
        $filename = $pathInfo['filename'] . '_' . $size . '.' . $pathInfo['extension'];
        $optimizedUrl = $pathInfo['dirname'] . '/optimized/' . $filename;

        // Check if optimized version exists, otherwise return original
        // In production, you might want to check actual file existence
        return $optimizedUrl;
    }

    /**
     * Compress response data
     */
    public function compressResponse($data)
    {
        // Remove null values
        $data = $this->removeNullValues($data);

        // Remove empty arrays
        $data = $this->removeEmptyArrays($data);

        return $data;
    }

    /**
     * Remove null values recursively
     */
    protected function removeNullValues($data)
    {
        if (is_array($data)) {
            return array_filter(array_map([$this, 'removeNullValues'], $data), function ($value) {
                return $value !== null;
            });
        } elseif (is_object($data)) {
            foreach ($data as $key => $value) {
                if ($value === null) {
                    unset($data->$key);
                } else {
                    $data->$key = $this->removeNullValues($value);
                }
            }
        }

        return $data;
    }

    /**
     * Remove empty arrays recursively
     */
    protected function removeEmptyArrays($data)
    {
        if (is_array($data)) {
            return array_filter(array_map([$this, 'removeEmptyArrays'], $data), function ($value) {
                return !(is_array($value) && empty($value));
            });
        } elseif (is_object($data)) {
            foreach ($data as $key => $value) {
                if (is_array($value) && empty($value)) {
                    unset($data->$key);
                } else {
                    $data->$key = $this->removeEmptyArrays($value);
                }
            }
        }

        return $data;
    }

    /**
     * Create infinite scroll response
     */
    public function createInfiniteScrollResponse($data, $cursor = null)
    {
        if ($data instanceof LengthAwarePaginator) {
            return [
                'data' => $data->items(),
                'cursor' => [
                    'next' => $data->hasMorePages() ? $data->currentPage() + 1 : null,
                    'has_more' => $data->hasMorePages(),
                ],
            ];
        }

        return ['data' => $data];
    }

    /**
     * Create lightweight list response
     */
    public function createLightweightList($collection, $idField = 'id', $nameField = 'name')
    {
        return $collection->map(function ($item) use ($idField, $nameField) {
            return [
                $idField => $item->$idField,
                $nameField => $item->$nameField,
            ];
        });
    }

    /**
     * Add timestamps for delta sync
     */
    public function addDeltaSyncInfo($data, $lastSync = null)
    {
        return [
            'data' => $data,
            'sync_info' => [
                'last_sync' => $lastSync,
                'current_timestamp' => now()->timestamp,
            ],
        ];
    }
}