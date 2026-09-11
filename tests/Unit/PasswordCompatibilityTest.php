<?php

declare(strict_types=1);

namespace Tests\Unit;

use Nette\Security\Passwords;
use PHPUnit\Framework\TestCase;

final class PasswordCompatibilityTest extends TestCase
{
    private const TEST_PASSWORD = 'choir-test-password';

    /**
     * Representative bcrypt hash generated from TEST_PASSWORD, not production
     * data. Its 60-character format matches the current users.password column.
     */
    private const REPRESENTATIVE_BCRYPT_HASH =
        '$2y$10$42AQn4CbDILC.3VTt2oSOupiw./3fRFd8jayyvzxJ40Xc/Xdixauq';

    public function testRepresentativeStoredBcryptHashCanBeVerified(): void
    {
        $passwords = new Passwords();

        self::assertTrue($passwords->verify(self::TEST_PASSWORD, self::REPRESENTATIVE_BCRYPT_HASH));
        self::assertFalse($passwords->verify('incorrect-password', self::REPRESENTATIVE_BCRYPT_HASH));
    }

    public function testNewHashFitsCurrentDatabaseColumnAndCanBeVerified(): void
    {
        $passwords = new Passwords();
        $hash = $passwords->hash(self::TEST_PASSWORD);

        self::assertLessThanOrEqual(60, strlen($hash));
        self::assertTrue($passwords->verify(self::TEST_PASSWORD, $hash));
    }
}
