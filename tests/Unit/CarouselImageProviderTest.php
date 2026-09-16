<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\CarouselImageProvider;
use Nette\Utils\FileSystem;
use PHPUnit\Framework\TestCase;

final class CarouselImageProviderTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/smps-carousel-' . bin2hex(random_bytes(8));
        FileSystem::createDir($this->directory);
    }

    protected function tearDown(): void
    {
        FileSystem::delete($this->directory);
    }

    public function testMissingDirectoryProducesAnEmptyCarousel(): void
    {
        self::assertSame(
            [],
            (new CarouselImageProvider($this->directory . '/missing'))->getImageFileNames(),
        );
    }

    public function testOnlySupportedTopLevelImagesAreReturnedInStableOrder(): void
    {
        FileSystem::write($this->directory . '/10 Finale.webp', 'image');
        FileSystem::write($this->directory . '/2 Opening.JPG', 'image');
        FileSystem::write($this->directory . '/notes.pdf', 'not an image');
        FileSystem::write($this->directory . '/.hidden.jpg', 'hidden');
        FileSystem::createDir($this->directory . '/nested');
        FileSystem::write($this->directory . '/nested/1.jpg', 'nested');

        self::assertSame(
            ['2%20Opening.JPG', '10%20Finale.webp'],
            (new CarouselImageProvider($this->directory))->getImageFileNames(),
        );
    }
}
