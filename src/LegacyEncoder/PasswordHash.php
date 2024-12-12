<?php

declare(strict_types=1);

namespace Vardumper\LegacyWordpressPasswordEncoder\LegacyEncoder;

/**
 * Portable PHP password hashing framework.
 *
 * @package phpass
 * @version 0.3 / WordPress
 * @link https://www.openwall.com/phpass/
 * @since 2.5.0
 */
final class PasswordHash
{
    public string $itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    public int $iterationCountLog2;

    public $portableHashes;

    public $randomState;

    /**
     * PHP5 constructor.
     */
    public function __construct(int $iterationCountLog2, bool $portableHashes)
    {
        if ($iterationCountLog2 < 4 || $iterationCountLog2 > 31) {
            $iterationCountLog2 = 8;
        }
        $this->iterationCountLog2 = $iterationCountLog2;

        $this->portableHashes = $portableHashes;

        $this->randomState = \microtime() . \uniqid((string) \rand(), true);
    }

    /**
     * PHP4 constructor.
     */
    public function passwordHash(int $iterationCountLog2, bool $portableHashes): void
    {
        self::__construct($iterationCountLog2, $portableHashes);
    }

    public function getRandomBytes(int $count)
    {
        $output = '';
        if (@is_readable('/dev/urandom') && ($fh = @fopen('/dev/urandom', 'rb'))) {
            $output = \fread($fh, $count);
            \fclose($fh);
        }

        if (\strlen($output) < $count) {
            $output = '';
            for ($i = 0; $i < $count; $i += 16) {
                $this->randomState = \md5(\microtime() . $this->randomState);
                $output .= \pack('H*', \md5($this->randomState));
            }
            $output = \substr($output, 0, $count);
        }

        return $output;
    }

    public function encode64($input, $count)
    {
        $output = '';
        $i = 0;
        do {
            $value = \ord($input[$i++]);
            $output .= $this->itoa64[$value & 0x3f];
            if ($i < $count) {
                $value |= \ord($input[$i]) << 8;
            }
            $output .= $this->itoa64[($value >> 6) & 0x3f];
            if ($i++ >= $count) {
                break;
            }
            if ($i < $count) {
                $value |= \ord($input[$i]) << 16;
            }
            $output .= $this->itoa64[($value >> 12) & 0x3f];
            if ($i++ >= $count) {
                break;
            }
            $output .= $this->itoa64[($value >> 18) & 0x3f];
        } while ($i < $count);

        return $output;
    }

    public function gensaltPrivate($input): string
    {
        $output = '$P$';
        $output .= $this->itoa64[min($this->iterationCountLog2 + ((\PHP_VERSION >= '5') ? 5 : 3), 30)];
        $output .= $this->encode64($input, 6);

        return $output;
    }

    public function cryptPrivate($password, $setting): string
    {
        $output = '*0';
        if (substr($setting, 0, 2) === $output) {
            $output = '*1';
        }

        $id = substr($setting, 0, 3);
        # We use "$P$", phpBB3 uses "$H$" for the same thing
        if ($id !== '$P$' && $id !== '$H$') {
            return $output;
        }

        $countLog2 = strpos($this->itoa64, $setting[3]);
        if ($countLog2 < 7 || $countLog2 > 30) {
            return $output;
        }

        $count = 1 << $countLog2;

        $salt = substr($setting, 4, 8);
        if (\strlen($salt) !== 8) {
            return $output;
        }

        # We're kind of forced to use MD5 here since it's the only
        # cryptographic primitive available in all versions of PHP
        # currently in use. To implement our own low-level crypto
        # in PHP would result in much worse performance and
        # consequently in lower iteration counts and hashes that are
        # quicker to crack (by non-PHP code).
        if (\PHP_VERSION >= '5') {
            $hash = md5($salt . $password, true);
            do {
                $hash = md5($hash . $password, true);
            } while (--$count);
        } else {
            $hash = pack('H*', md5($salt . $password));
            do {
                $hash = pack('H*', md5($hash . $password));
            } while (--$count);
        }

        $output = substr($setting, 0, 12);
        $output .= $this->encode64($hash, 16);

        return $output;
    }

    public function gensaltExtended($input): string
    {
        $countLog2 = min($this->iterationCountLog2 + 8, 24);
        # This should be odd to not reveal weak DES keys, and the
        # maximum valid value is (2**24 - 1) which is odd anyway.
        $count = (1 << $countLog2) - 1;

        $output = '_';
        $output .= $this->itoa64[$count & 0x3f];
        $output .= $this->itoa64[($count >> 6) & 0x3f];
        $output .= $this->itoa64[($count >> 12) & 0x3f];
        $output .= $this->itoa64[($count >> 18) & 0x3f];

        $output .= $this->encode64($input, 3);

        return $output;
    }

    public function gensaltBlowfish($input)
    {
        # This one needs to use a different order of characters and a
        # different encoding scheme from the one in encode64() above.
        # We care because the last character in our encoded string will
        # only represent 2 bits. While two known implementations of
        # bcrypt will happily accept and correct a salt string which
        # has the 4 unused bits set to non-zero, we do not want to take
        # chances and we also do not want to waste an additional byte
        # of entropy.
        $itoa64 = './ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

        $output = '$2a$';
        $output .= \chr(\ord('0') + intdiv($this->iterationCountLog2, 10));
        $output .= \chr(\ord('0') + $this->iterationCountLog2 % 10);
        $output .= '$';

        $i = 0;
        do {
            $c1 = \ord($input[$i++]);
            $output .= $itoa64[$c1 >> 2];
            $c1 = ($c1 & 0x03) << 4;
            if ($i >= 16) {
                $output .= $itoa64[$c1];

                break;
            }

            $c2 = \ord($input[$i++]);
            $c1 |= $c2 >> 4;
            $output .= $itoa64[$c1];
            $c1 = ($c2 & 0x0f) << 2;

            $c2 = \ord($input[$i++]);
            $c1 |= $c2 >> 6;
            $output .= $itoa64[$c1];
            $output .= $itoa64[$c2 & 0x3f];
        } while (1);

        return $output;
    }

    public function hashPassword($password)
    {
        $random = '';

        if (\defined('CRYPT_BLOWFISH') && !$this->portableHashes) {
            $random = $this->getRandomBytes(16);
            $hash = \crypt($password, $this->gensaltBlowfish($random));
            if (\strlen($hash) === 60) {
                return $hash;
            }
        }

        if (\defined('CRYPT_EXT_DES') && !$this->portableHashes) {
            if (\strlen($random) < 3) {
                $random = $this->getRandomBytes(3);
            }
            $hash = \crypt($password, $this->gensaltExtended($random));
            if (\strlen($hash) === 20) {
                return $hash;
            }
        }

        if (\strlen($random) < 6) {
            $random = $this->getRandomBytes(6);
        }
        $hash = $this->cryptPrivate($password, $this->gensaltPrivate($random));
        if (\strlen($hash) === 34) {
            return $hash;
        }

        return '*';
    }

    public function checkPassword($password, $stored_hash)
    {
        if (\strlen($password) > 4096) {
            return false;
        }

        $hash = $this->cryptPrivate($password, $stored_hash);
        if ($hash[0] === '*') {
            $hash = crypt($password, $stored_hash);
        }

        return $hash === $stored_hash;
    }
}
