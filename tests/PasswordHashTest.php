<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Vardumper\LegacyWordpressPasswordEncoder\LegacyEncoder\PasswordHash;

final class PasswordHashTest extends TestCase
{
    private $passwordHash;

    protected function setUp(): void
    {
        $this->passwordHash = new PasswordHash(8, true);
    }

    public function testHashPasswordWithBlowfish(): void
    {
        if (!defined('CRYPT_BLOWFISH')) {
            static::markTestSkipped('Blowfish not supported.');
        }

        $password = 'testpassword';
        $this->passwordHash->portableHashes = false; // Ensure portableHashes is false
        $hash = $this->passwordHash->hashPassword($password);

        static::assertNotEquals('*', $hash);
        static::assertEquals(60, strlen($hash));
    }

    public function testHashPasswordWithBlowfishWithPortableHashes(): void
    {
        if (!defined('CRYPT_BLOWFISH')) {
            static::markTestSkipped('Blowfish not supported.');
        }

        $password = 'testpassword';
        $this->passwordHash->portableHashes = true; // Ensure portableHashes is true
        $hash = $this->passwordHash->hashPassword($password);

        static::assertNotEquals('*', $hash);
        static::assertEquals(34, strlen($hash));
    }

    public function testHashPasswordWithPortableHashes(): void
    {
        $password = 'testpassword';
        $this->passwordHash->portableHashes = true;
        $hash = $this->passwordHash->hashPassword($password);

        static::assertNotEquals('*', $hash);
        static::assertEquals(34, strlen($hash));
    }

    public function testCheckPassword(): void
    {
        $password = 'testpassword';
        $hash = $this->passwordHash->hashPassword($password);

        static::assertTrue($this->passwordHash->checkPassword($password, $hash));
        static::assertFalse($this->passwordHash->checkPassword('wrongpassword', $hash));
    }
}
