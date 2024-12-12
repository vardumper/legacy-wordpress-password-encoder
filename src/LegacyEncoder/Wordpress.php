<?php

declare(strict_types=1);

namespace Vardumper\LegacyWordpressPasswordEncoder\LegacyEncoder;

use Shopware\Core\Checkout\Customer\Password\LegacyEncoder\LegacyEncoderInterface;

final class Wordpress implements LegacyEncoderInterface
{
    public function getName(): string
    {
        return 'wordpress';
    }

    public function isPasswordValid(string $password, string $hash): bool
    {
        $passwordHash = new PasswordHash(8, true);

        return $passwordHash->checkPassword($password, $hash);
    }

    protected function generateInternal(string $password, string $salt, int $iterations): string
    {
        $passwordHash = new PasswordHash($iterations, true);

        return $passwordHash->hashPassword($password);
    }
}
