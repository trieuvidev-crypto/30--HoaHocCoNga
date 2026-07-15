<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Core\Logger;
use App\Repositories\MediaRepository;
use RuntimeException;

/**
 * Handles every upload in the platform: validates extension + real MIME
 * type (never the client-supplied one) + size, detects duplicates via
 * SHA-256 so identical files are referenced rather than re-stored,
 * renames to a UUID-based filename (never trusts the original name),
 * and stores outside the public webroot per FILE_STORAGE.md.
 */
final class MediaUploadService
{
    public function __construct(
        private readonly MediaRepository $media,
        private readonly Logger $logger
    ) {
    }

    /**
     * @param array $file one entry from $_FILES, e.g. $_FILES['avatar']
     * @param string $directoryKey key into config('upload.directories')
     *
     * @throws RuntimeException with a Vietnamese, user-safe message
     */
    public function store(array $file, string $directoryKey, ?int $uploaderId = null, ?string $ownerType = null, ?int $ownerId = null): array
    {
        $this->assertUploadOk($file);

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedTypes = config('upload.allowed_types');

        if (!isset($allowedTypes[$extension])) {
            throw new RuntimeException('Định dạng tệp không được hỗ trợ.');
        }

        $maxBytes = (int) config('upload.max_size_mb', 200) * 1024 * 1024;

        if ($file['size'] > $maxBytes) {
            throw new RuntimeException('Tệp vượt quá dung lượng cho phép (' . config('upload.max_size_mb') . 'MB).');
        }

        $detectedMime = $this->detectRealMimeType($file['tmp_name']);

        if (!in_array($detectedMime, $allowedTypes[$extension], true)) {
            // Extension says one thing, actual file content says another —
            // classic disguised-executable pattern. Reject outright.
            $this->logger->warning('security', 'Tệp tải lên bị từ chối do MIME không khớp phần mở rộng.', [
                'declared_extension' => $extension,
                'detected_mime' => $detectedMime,
                'uploader_id' => $uploaderId,
            ]);

            throw new RuntimeException('Tệp không hợp lệ hoặc bị hỏng.');
        }

        $checksum = hash_file('sha256', $file['tmp_name']);

        $existing = $this->media->findByChecksum($checksum);

        if ($existing !== null) {
            // Duplicate detection per FILE_STORAGE.md — reference the
            // existing physical file instead of storing a second copy.
            return $existing;
        }

        $directory = config("upload.directories.{$directoryKey}", 'misc');
        $storageRoot = config('upload.storage_root') . '/' . $directory;

        if (!is_dir($storageRoot)) {
            mkdir($storageRoot, 0775, true);
        }

        $storedName = generate_uuid_v4() . '.' . $extension;
        $destination = "{$storageRoot}/{$storedName}";

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new RuntimeException('Không thể lưu tệp. Vui lòng thử lại.');
        }

        [$width, $height] = $this->imageDimensions($destination, $detectedMime);

        $record = $this->media->create([
            'original_name' => basename($file['name']),
            'stored_name' => $storedName,
            'path' => "{$directory}/{$storedName}",
            'extension' => $extension,
            'mime_type' => $detectedMime,
            'size_bytes' => $file['size'],
            'width' => $width,
            'height' => $height,
            'checksum' => $checksum,
            'uploader_id' => $uploaderId,
            'owner_type' => $ownerType,
            'owner_id' => $ownerId,
        ]);

        $this->logger->info('media', 'Tải lên tệp thành công.', ['media_uuid' => $record['uuid'], 'uploader_id' => $uploaderId]);

        return $record;
    }

    private function assertUploadOk(array $file): void
    {
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new RuntimeException('Yêu cầu tải lên không hợp lệ.');
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Tải lên thất bại. Vui lòng thử lại.');
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('Tệp tải lên không hợp lệ.');
        }
    }

    private function detectRealMimeType(string $tmpPath): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $tmpPath) ?: 'application/octet-stream';
        finfo_close($finfo);

        return $mime;
    }

    /** @return array{0: ?int, 1: ?int} */
    private function imageDimensions(string $path, string $mime): array
    {
        if (!str_starts_with($mime, 'image/') || $mime === 'image/svg+xml') {
            return [null, null];
        }

        $size = @getimagesize($path);

        return $size ? [$size[0], $size[1]] : [null, null];
    }
}
