<?php
/**
 * XooPress Two-Factor Authentication (TOTP)
 *
 * Implements Time-based One-Time Passwords (RFC 6238).
 * Pure PHP implementation with no external dependencies.
 *
 * @package XooPress
 * @subpackage Core
 */

namespace XooPress\Core;

class TwoFactor
{
    /**
     * Number of digits in the TOTP code
     */
    protected int $digits = 6;

    /**
     * Time step in seconds (default: 30)
     */
    protected int $timeStep = 30;

    /**
     * Allowed clock skew in steps (default: 1 = ±30s)
     */
    protected int $skew = 1;

    /**
     * Issuer name for QR code
     */
    protected string $issuer = 'XooPress';

    /**
     * Generate a random base32-encoded secret key
     *
     * @param int $length Length of the secret in bytes (20 = 160 bits = standard)
     * @return string
     */
    public function generateSecret(int $length = 20): string
    {
        $bytes = random_bytes($length);
        return $this->base32Encode($bytes);
    }

    /**
     * Get the TOTP URL for QR code generation
     *
     * @param string $label User identifier (e.g., email or username)
     * @param string $secret Base32-encoded secret
     * @param string|null $issuer Issuer name
     * @return string
     */
    public function getQRCodeUrl(string $label, string $secret, ?string $issuer = null): string
    {
        $issuer = $issuer ?? $this->issuer;
        $encodedLabel = rawurlencode($label);
        $encodedIssuer = rawurlencode($issuer);

        return "otpauth://totp/{$encodedIssuer}:{$encodedLabel}?secret={$secret}&issuer={$encodedIssuer}&algorithm=SHA1&digits={$this->digits}&period={$this->timeStep}";
    }

    /**
     * Verify a TOTP code against a secret
     *
     * @param string $secret Base32-encoded secret
     * @param string $code User-provided TOTP code
     * @return bool
     */
    public function verify(string $secret, string $code): bool
    {
        $secret = strtoupper($secret);
        $code = trim($code);

        // Basic validation
        if (empty($code) || !preg_match('/^\d{' . $this->digits . '}$/', $code)) {
            return false;
        }

        $decodedSecret = $this->base32Decode($secret);
        if ($decodedSecret === false || empty($decodedSecret)) {
            return false;
        }

        $timeSlice = (int) floor(time() / $this->timeStep);

        // Check current, past, and future time steps (allow clock skew)
        for ($i = -$this->skew; $i <= $this->skew; $i++) {
            $calculatedCode = $this->generateTOTP($decodedSecret, $timeSlice + $i);
            if (hash_equals($calculatedCode, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate a TOTP code for a given secret and time slice
     *
     * @param string $decodedSecret Raw binary secret
     * @param int $timeSlice Time slice counter
     * @return string
     */
    protected function generateTOTP(string $decodedSecret, int $timeSlice): string
    {
        // Pack time slice as 8-byte big-endian
        $counter = pack('J', $timeSlice); // 64-bit, big-endian, unsigned

        // HMAC-SHA1
        $hash = hash_hmac('sha1', $counter, $decodedSecret, true);

        // Dynamic offset (last nibble)
        $offset = ord($hash[19]) & 0x0F;

        // Extract 4 bytes from hash at offset
        $binaryCode = (
            (ord($hash[$offset]) & 0x7F) << 24 |
            (ord($hash[$offset + 1]) & 0xFF) << 16 |
            (ord($hash[$offset + 2]) & 0xFF) << 8 |
            (ord($hash[$offset + 3]) & 0xFF)
        );

        // Modulo to get the right number of digits
        $code = $binaryCode % (10 ** $this->digits);

        // Pad with leading zeros
        return str_pad((string) $code, $this->digits, '0', STR_PAD_LEFT);
    }

    /**
     * Generate a set of single-use recovery codes
     *
     * @param int $count Number of recovery codes to generate
     * @return array Array of recovery codes
     */
    public function generateRecoveryCodes(int $count = 10): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $code = strtoupper(bin2hex(random_bytes(4))); // 8 hex chars
            $codes[] = implode('-', str_split($code, 4)); // XXXX-XXXX format
        }
        return $codes;
    }

    /**
     * Verify a recovery code against stored hashed recovery codes
     *
     * @param string $code User-provided recovery code
     * @param array $hashedCodes Array of password_hash'd recovery codes
     * @return string|false The matched code on success, false on failure
     */
    public function verifyRecoveryCode(string $code, array $hashedCodes): string|false
    {
        $code = strtoupper(trim($code));

        foreach ($hashedCodes as $hashed) {
            if (password_verify($code, $hashed)) {
                return $hashed; // Return the hash to be removed from storage
            }
        }

        return false;
    }

    /**
     * Hash recovery codes for storage (bcrypt)
     *
     * @param array $codes Recovery codes to hash
     * @return array Hashed codes
     */
    public function hashRecoveryCodes(array $codes): array
    {
        $hashed = [];
        foreach ($codes as $code) {
            $hashed[] = password_hash($code, PASSWORD_BCRYPT);
        }
        return $hashed;
    }

    /**
     * Encode binary data to base32
     *
     * @param string $data Raw binary string
     * @return string Base32-encoded string
     */
    protected function base32Encode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binary = '';
        $len = strlen($data);

        for ($i = 0; $i < $len; $i++) {
            $binary .= str_pad(decbin(ord($data[$i])), 8, '0', STR_PAD_LEFT);
        }

        $encoded = '';
        $binaryLen = strlen($binary);
        for ($i = 0; $i < $binaryLen; $i += 5) {
            $chunk = substr($binary, $i, 5);
            $chunk = str_pad($chunk, 5, '0');
            $encoded .= $alphabet[bindec($chunk)];
        }

        // Add padding
        $padLen = (8 - (strlen($encoded) % 8)) % 8;
        $encoded .= str_repeat('=', $padLen);

        return $encoded;
    }

    /**
     * Decode a base32 string to binary
     *
     * @param string $data Base32-encoded string
     * @return string|false Raw binary string, or false on failure
     */
    protected function base32Decode(string $data): string|false
    {
        // Remove padding
        $data = strtoupper(rtrim($data, '='));

        if (!preg_match('/^[A-Z2-7]+$/', $data)) {
            return false;
        }

        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binary = '';

        $len = strlen($data);
        for ($i = 0; $i < $len; $i++) {
            $pos = strpos($alphabet, $data[$i]);
            if ($pos === false) {
                return false;
            }
            $binary .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }

        $decoded = '';
        $binaryLen = strlen($binary);
        for ($i = 0; $i + 8 <= $binaryLen; $i += 8) {
            $decoded .= chr(bindec(substr($binary, $i, 8)));
        }

        return $decoded;
    }

    /**
     * Check if 2FA is enabled for a user
     *
     * @param string $secret The stored secret
     * @param bool $enabled The stored enabled flag
     * @return bool
     */
    public function isEnabledForUser(string $secret, bool $enabled): bool
    {
        return $enabled && !empty($secret);
    }
}