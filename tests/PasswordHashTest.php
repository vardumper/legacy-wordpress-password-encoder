<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Vardumper\LegacyWordpressPasswordEncoder\LegacyEncoder\PasswordHash;

class PasswordHashTest extends TestCase
{
    private PasswordHash $passwordHash;

    protected function setUp(): void
    {
        $this->passwordHash = new PasswordHash(8, false);
    }

    public function testHashPasswordWithBlowfish(): void
    {
        if (defined('CRYPT_BLOWFISH')) {
            static::markTestSkipped('Blowfish not supported.');
        }

        $password = 'testpassword';
        $hash = $this->passwordHash->hashPassword($password);

        static::assertNotEquals('*', $hash);
        static::assertEquals(60, strlen($hash));
    }

    public function testHashPasswordWithExtendedDes(): void
    {
        if (defined('CRYPT_BLOWFISH')) {
            static::markTestSkipped('Extended DES not supported while Blowfish is available.');
        }

        $password = 'testpassword';
        $this->passwordHash->portableHashes = false;
        $hash = $this->passwordHash->hashPassword($password);

        static::assertNotEquals('*', $hash);
        static::assertEquals(20, strlen($hash));
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
