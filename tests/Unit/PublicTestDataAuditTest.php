<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class PublicTestDataAuditTest extends TestCase
{
    private const PRIVATE_FIXTURE_EXTENSIONS = [
        'db', 'doc', 'docx', 'dump', 'flac', 'gif', 'jpeg', 'jpg', 'm4a',
        'mid', 'midi', 'mp3', 'mp4', 'mscz', 'musicxml', 'mxl', 'ogg', 'pdf',
        'png', 'ppt', 'pptx', 'sqlite', 'sqlite3', 'sql', 'wav', 'webm', 'webp',
        'xls', 'xlsx',
    ];

    public function testLiteralTestEmailsUseTheReservedExampleDomain(): void
    {
        foreach ($this->testFiles() as $path) {
            $source = file_get_contents($path);
            self::assertIsString($source);
            preg_match_all(
                '/[a-z0-9.!#$%&\'*+\/=?^_`{|}~-]+@([a-z0-9.-]+\.[a-z]{2,})/i',
                $source,
                $matches,
            );

            foreach ($matches[1] as $domain) {
                self::assertSame(
                    'example.test',
                    strtolower($domain),
                    'Use the reserved example.test domain in ' . $this->relativePath($path),
                );
            }
        }
    }

    public function testStaticPasswordHashesAreExplicitlyDocumentedAsSynthetic(): void
    {
        foreach ($this->testFiles() as $path) {
            $source = file_get_contents($path);
            self::assertIsString($source);
            preg_match_all('/\$2[aby]\$\d{2}\$[.\/A-Za-z0-9]{53}/', $source, $matches);

            if ($matches[0] === []) {
                continue;
            }

            self::assertSame('Unit/PasswordCompatibilityTest.php', $this->relativePath($path));
            self::assertStringContainsString('not production', $source);
            self::assertCount(1, $matches[0]);
        }
    }

    public function testFixtureTreeContainsNoDocumentsRecordingsOrBinaryMedia(): void
    {
        foreach ($this->testFiles() as $path) {
            self::assertNotContains(
                strtolower(pathinfo($path, PATHINFO_EXTENSION)),
                self::PRIVATE_FIXTURE_EXTENSIONS,
                'Unexpected test fixture type: ' . $this->relativePath($path),
            );
        }
    }

    public function testPdfUploadSamplesAreClearlySynthetic(): void
    {
        foreach ($this->testFiles() as $path) {
            if (realpath($path) === __FILE__) {
                continue;
            }
            $source = file_get_contents($path);
            self::assertIsString($source);
            preg_match_all('/%PDF-[^\'"\r\n]*/', $source, $matches);

            foreach ($matches[0] as $sample) {
                self::assertStringContainsString(
                    'test document',
                    $sample,
                    'PDF samples must be generated test markers, not document content.',
                );
            }
        }
    }

    /** @return list<string> */
    private function testFiles(): array
    {
        $directory = dirname(__DIR__);
        $paths = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->isFile()) {
                $paths[] = $file->getPathname();
            }
        }
        sort($paths);

        return $paths;
    }

    private function relativePath(string $path): string
    {
        return str_replace('\\', '/', substr($path, strlen(dirname(__DIR__)) + 1));
    }
}
