<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class MediaRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function findByChecksum(string $checksum): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM media_files WHERE checksum_sha256 = :checksum AND deleted_at IS NULL LIMIT 1',
            ['checksum' => $checksum]
        );
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM media_files WHERE id = :id AND deleted_at IS NULL',
            ['id' => $id]
        );
    }

    public function create(array $attributes): array
    {
        $uuid = generate_uuid_v4();

        $this->db->query(
            'INSERT INTO media_files
                (uuid, disk, original_name, stored_name, path, extension, mime_type, size_bytes,
                 width, height, checksum_sha256, visibility, uploader_id, owner_type, owner_id,
                 created_at, updated_at)
             VALUES
                (:uuid, :disk, :original_name, :stored_name, :path, :extension, :mime_type, :size_bytes,
                 :width, :height, :checksum, :visibility, :uploader_id, :owner_type, :owner_id,
                 NOW(), NOW())',
            [
                'uuid' => $uuid,
                'disk' => $attributes['disk'] ?? 'local',
                'original_name' => $attributes['original_name'],
                'stored_name' => $attributes['stored_name'],
                'path' => $attributes['path'],
                'extension' => $attributes['extension'],
                'mime_type' => $attributes['mime_type'],
                'size_bytes' => $attributes['size_bytes'],
                'width' => $attributes['width'] ?? null,
                'height' => $attributes['height'] ?? null,
                'checksum' => $attributes['checksum'],
                'visibility' => $attributes['visibility'] ?? 'public',
                'uploader_id' => $attributes['uploader_id'] ?? null,
                'owner_type' => $attributes['owner_type'] ?? null,
                'owner_id' => $attributes['owner_id'] ?? null,
            ]
        );

        return $this->findById((int) $this->db->lastInsertId());
    }

    public function incrementDownloadCount(int $mediaId): void
    {
        $this->db->query(
            'UPDATE media_files SET download_count = download_count + 1 WHERE id = :id',
            ['id' => $mediaId]
        );
    }
}
