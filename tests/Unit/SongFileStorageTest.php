<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Model\entities\Song;
use App\Model\entities\SongFile;
use App\Model\entities\User;
use App\Services\SongFileStorage;
use Closure;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Nette\Http\FileUpload;
use Nette\Utils\FileSystem;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class SongFileStorageTest extends TestCase
{
    private string $storageRoot;

    protected function setUp(): void
    {
        $this->storageRoot = sys_get_temp_dir() . '/smps-song-files-' . bin2hex(random_bytes(8));
    }

    protected function tearDown(): void
    {
        FileSystem::delete($this->storageRoot);
    }

    public function testPdfIsStoredUnderGeneratedFilenameInsideAllowedCategory(): void
    {
        $connection = $this->transactionConnection();
        $persisted = null;
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($connection);
        $entityManager->expects(self::once())
            ->method('persist')
            ->willReturnCallback(static function (object $entity) use (&$persisted): void {
                $persisted = $entity;
            });
        $entityManager->expects(self::once())->method('flush');

        $song = new Song();
        $song->setId(12);
        $songFile = (new SongFileStorage($entityManager, $this->storageRoot))->store(
            $this->createUpload('%PDF-1.4 test document', '../../unsafe-name.php'),
            $song,
            new User(),
            SongFileStorage::CATEGORY_CHOIR_SHEET_MUSIC,
            'Testovací noty',
        );

        self::assertSame($songFile, $persisted);
        self::assertMatchesRegularExpression('/^[a-f0-9]{32}\.pdf$/', $songFile->getFilename());
        self::assertSame('Testovací noty', $songFile->getDescription());
        self::assertFileExists(
            $this->storageRoot . '/12/' . SongFileStorage::CATEGORY_CHOIR_SHEET_MUSIC
            . '/' . $songFile->getFilename(),
        );
    }

    public function testInvalidCategoryIsRejectedBeforePersistence(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('persist');

        $song = new Song();
        $song->setId(12);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Kategorie souboru není povolena.');

        (new SongFileStorage($entityManager, $this->storageRoot))->store(
            $this->createUpload('%PDF-1.4 test document', 'score.pdf'),
            $song,
            new User(),
            '../outside',
            'Test',
        );
    }

    public function testDisallowedMimeTypeIsRejected(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $song = new Song();
        $song->setId(12);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tento typ souboru není povolen.');

        (new SongFileStorage($entityManager, $this->storageRoot))->store(
            $this->createUpload('<?php echo "unsafe";', 'score.pdf'),
            $song,
            new User(),
            SongFileStorage::CATEGORY_CHOIR_SHEET_MUSIC,
            'Test',
        );
    }

    public function testSheetMusicSizeLimitIsEnforced(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $song = new Song();
        $song->setId(12);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Soubor překračuje povolenou velikost.');

        (new SongFileStorage($entityManager, $this->storageRoot))->store(
            $this->createUpload('%PDF-1.4 test document', 'score.pdf', SongFileStorage::MAX_UPLOAD_SIZE),
            $song,
            new User(),
            SongFileStorage::CATEGORY_CHOIR_SHEET_MUSIC,
            'Test',
        );
    }

    public function testDatabaseFailureRemovesAlreadyMovedFile(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('transactional')->willThrowException(new RuntimeException('database failed'));
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($connection);

        $song = new Song();
        $song->setId(12);

        try {
            (new SongFileStorage($entityManager, $this->storageRoot))->store(
                $this->createUpload('%PDF-1.4 test document', 'score.pdf'),
                $song,
                new User(),
                SongFileStorage::CATEGORY_CHOIR_SHEET_MUSIC,
                'Test',
            );
            self::fail('Expected database failure was not thrown.');
        } catch (RuntimeException $exception) {
            self::assertSame('database failed', $exception->getMessage());
        }

        self::assertSame([], $this->findStoredFiles());
    }

    public function testLegacyTraversalFilenameIsRejected(): void
    {
        $song = new Song();
        $song->setId(12);
        $songFile = new SongFile();
        $songFile->setSong($song);
        $songFile->setCategory(SongFileStorage::CATEGORY_RECORDINGS);
        $songFile->setFilename('../outside.mp3');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Stored filename is invalid.');

        (new SongFileStorage($this->createStub(EntityManagerInterface::class), $this->storageRoot))
            ->getFilePath($songFile);
    }

    public function testDeletionCoordinatesFileAndDatabaseRemovalInTransaction(): void
    {
        $connection = $this->transactionConnection();
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($connection);
        $entityManager->expects(self::once())->method('remove');
        $entityManager->expects(self::once())->method('flush');

        $song = new Song();
        $song->setId(12);
        $songFile = new SongFile();
        $songFile->setSong($song);
        $songFile->setCategory(SongFileStorage::CATEGORY_RECORDINGS);
        $songFile->setFilename('safe-recording.mp3');

        $storage = new SongFileStorage($entityManager, $this->storageRoot);
        $path = $storage->getFilePath($songFile);
        FileSystem::createDir(dirname($path));
        file_put_contents($path, 'test recording');

        $storage->delete($songFile);

        self::assertFileDoesNotExist($path);
    }

    private function transactionConnection(): Connection
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('transactional')
            ->willReturnCallback(static fn (Closure $callback): mixed => $callback($connection));

        return $connection;
    }

    private function createUpload(string $contents, string $name, ?int $reportedSize = null): FileUpload
    {
        FileSystem::createDir($this->storageRoot);
        $temporaryFile = $this->storageRoot . '/upload-' . bin2hex(random_bytes(8));
        file_put_contents($temporaryFile, $contents);

        return new FileUpload([
            'name' => $name,
            'size' => $reportedSize ?? filesize($temporaryFile),
            'tmp_name' => $temporaryFile,
            'error' => UPLOAD_ERR_OK,
        ]);
    }

    /** @return list<string> */
    private function findStoredFiles(): array
    {
        if (!is_dir($this->storageRoot)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->storageRoot));
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}
