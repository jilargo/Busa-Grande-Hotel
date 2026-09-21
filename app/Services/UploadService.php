<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Exceptions\ValidationException;

/**
 * Safe image uploads for rooms / room types.
 *
 * Validation happens server-side: real MIME type from PHP, whitelisted types,
 * strict size limit, and a random filename so uploads can never overwrite
 * existing assets or execute as PHP scripts.
 */
final class UploadService
{
    private const ALLOWED_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
    ];

    private const MAX_BYTES = 2 * 1024 * 1024; // 2 MB

    /** Stores the uploaded file and returns the public path to it. */
    public function storeImage(?array $file): ?string
    {
        if ($file === null) {
            return null;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new ValidationException(['image' => 'The image could not be uploaded.']);
        }

        if ($file['size'] > self::MAX_BYTES) {
            throw new ValidationException(['image' => 'The image must not exceed 2 MB.']);
        }

        $mime = (string) (mime_content_type($file['tmp_name']) ?: $file['type']);
        if (!in_array($mime, self::ALLOWED_TYPES, true)) {
            throw new ValidationException(['image' => 'Only JPEG, PNG, WebP and GIF images are allowed.']);
        }

        $extension = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
        };

        $dirname = base_path('public/uploads/rooms');
        if (!is_dir($dirname)) {
            mkdir($dirname, 0775, true);
        }

        $filename = date('Ymd') . '-' . bin2hex(random_bytes(8)) . '.' . $extension;

        if (!move_uploaded_file($file['tmp_name'], $dirname . '/' . $filename)) {
            throw new ValidationException(['image' => 'The image could not be saved.']);
        }

        // Store the path relative to /public so it is served like any asset.
        return 'uploads/rooms/' . $filename;
    }
}