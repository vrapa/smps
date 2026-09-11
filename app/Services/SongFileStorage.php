<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\entities\Song;
use App\Model\entities\SongFile;
use App\Model\entities\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Nette\Http\FileUpload;
use RuntimeException;
use Throwable;

final class SongFileStorage
{
    public const CATEGORY_RECORDINGS = 'nahravky';
    public const CATEGORY_ORCHESTRA_SHEET_MUSIC = 'noty-orchestr';
    public const CATEGORY_CHOIR_SHEET_MUSIC = 'noty-sbor';
    public const MAX_UPLOAD_SIZE = 104_857_600;

    private const SHEET_MUSIC_MAX_SIZE = 20_971_520;

    /** @var array<string, string> */
    private const SHEET_MUSIC_MIME_TYPES = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /** @var array<string, string> */
    private const RECORDING_MIME_TYPES = [
        'audio/mpeg' => 'mp3',
        'audio/wav' => 'wav',
        'audio/x-wav' => 'wav',
        'audio/ogg' => 'ogg',
        'application/ogg' => 'ogg',
        'audio/mp4' => 'm4a',
        'audio/x-m4a' => 'm4a',
    ];

    private string $storageRoot;

    public function __construct(
        private EntityManagerInterface $entityManager,
        ?string $storageRoot = null,
    ) {
        $this->storageRoot = rtrim(
            $storageRoot ?? dirname(__DIR__, 2) . '/www/dokumenty/skladby',
            '/\\',
        );
    }

    public function store(
        FileUpload $upload,
        Song $song,
        User $createdBy,
        string $category,
        string $description,
    ): SongFile {
        [$extension, $maxSize] = $this->getUploadRule($category, $upload);
        if ($upload->getSize() > $maxSize) {
            throw new InvalidArgumentException('Soubor překračuje povolenou velikost.');
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $songFile = new SongFile();
        $songFile->setSong($song);
        $songFile->setCreatedAt(new DateTimeImmutable());
        $songFile->setCreatedBy($createdBy);
        $songFile->setCategory($category);
        $songFile->setFilename($filename);
        $songFile->setDescription($description);

        $path = $this->getFilePath($songFile);
        $upload->move($path);

        try {
            $this->entityManager->getConnection()->transactional(function () use ($songFile): void {
                $this->entityManager->persist($songFile);
                $this->entityManager->flush();
            });
        } catch (Throwable $exception) {
            if (is_file($path)) {
                @unlink($path);
            }
            throw $exception;
        }

        return $songFile;
    }

    public function delete(SongFile $songFile): void
    {
        $path = $this->getFilePath($songFile);
        if (!is_file($path)) {
            throw new RuntimeException('Stored song file was not found.');
        }

        $quarantinePath = $path . '.deleting-' . bin2hex(random_bytes(8));
        if (!@rename($path, $quarantinePath)) {
            throw new RuntimeException('Stored song file could not be prepared for deletion.');
        }

        try {
            $this->entityManager->getConnection()->transactional(function () use ($songFile): void {
                $this->entityManager->remove($songFile);
                $this->entityManager->flush();
            });
        } catch (Throwable $exception) {
            if (!@rename($quarantinePath, $path)) {
                throw new RuntimeException('Stored song file could not be restored after rollback.', 0, $exception);
            }
            throw $exception;
        }

        if (!@unlink($quarantinePath)) {
            throw new RuntimeException('Stored song file could not be deleted.');
        }
    }

    public function getFilePath(SongFile $songFile): string
    {
        $category = $songFile->getCategory();
        $this->assertAllowedCategory($category);

        $filename = $songFile->getFilename();
        if (
            $filename === ''
            || $filename === '.'
            || $filename === '..'
            || basename(str_replace('\\', '/', $filename)) !== $filename
        ) {
            throw new InvalidArgumentException('Stored filename is invalid.');
        }

        return $this->storageRoot
            . DIRECTORY_SEPARATOR . $songFile->getSong()->getId()
            . DIRECTORY_SEPARATOR . $category
            . DIRECTORY_SEPARATOR . $filename;
    }

    /** @return array{string, int} */
    private function getUploadRule(string $category, FileUpload $upload): array
    {
        $this->assertAllowedCategory($category);
        if (!$upload->isOk()) {
            throw new InvalidArgumentException('Soubor se nepodařilo bezpečně nahrát.');
        }

        $mimeType = $upload->getContentType();
        $mimeTypes = $category === self::CATEGORY_RECORDINGS
            ? self::RECORDING_MIME_TYPES
            : self::SHEET_MUSIC_MIME_TYPES;
        if ($mimeType === null || !isset($mimeTypes[$mimeType])) {
            throw new InvalidArgumentException('Tento typ souboru není povolen.');
        }

        $maxSize = $category === self::CATEGORY_RECORDINGS
            ? self::MAX_UPLOAD_SIZE
            : self::SHEET_MUSIC_MAX_SIZE;

        return [$mimeTypes[$mimeType], $maxSize];
    }

    private function assertAllowedCategory(string $category): void
    {
        if (
            !in_array($category, [
            self::CATEGORY_RECORDINGS,
            self::CATEGORY_ORCHESTRA_SHEET_MUSIC,
            self::CATEGORY_CHOIR_SHEET_MUSIC,
            ], true)
        ) {
            throw new InvalidArgumentException('Kategorie souboru není povolena.');
        }
    }
}
