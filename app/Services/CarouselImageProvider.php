<?php

declare(strict_types=1);

namespace App\Services;

final class CarouselImageProvider
{
    private const ALLOWED_EXTENSIONS = ['jpeg', 'jpg', 'png', 'webp'];

    private string $directory;

    public function __construct(?string $directory = null)
    {
        $this->directory = rtrim(
            $directory ?? dirname(__DIR__, 2) . '/www/images/carousel',
            '/\\',
        );
    }

    /** @return list<string> URL-encoded file names relative to the carousel directory. */
    public function getImageFileNames(): array
    {
        $entries = @scandir($this->directory);
        if ($entries === false) {
            return [];
        }

        $images = [];
        foreach ($entries as $entry) {
            if ($entry === '' || $entry[0] === '.') {
                continue;
            }

            $path = $this->directory . DIRECTORY_SEPARATOR . $entry;
            $extension = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
            if (
                !is_file($path)
                || is_link($path)
                || !in_array($extension, self::ALLOWED_EXTENSIONS, true)
            ) {
                continue;
            }

            $images[] = $entry;
        }

        natcasesort($images);

        return array_values(array_map('rawurlencode', $images));
    }
}
