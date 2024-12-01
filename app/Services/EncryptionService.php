<?php

namespace App\Services;

class EncryptionService
{
    private string $cipher = 'aes-256-cbc';

    public function encrypt(string $data, string $key): string
    {
        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = openssl_random_pseudo_bytes($ivLength);

        $encrypted = openssl_encrypt(
            $data,
            $this->cipher,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        // Combinar IV y datos encriptados
        return base64_encode($iv . $encrypted);
    }

    public function decrypt(string $data, string $key): string
    {
        $data = base64_decode($data);

        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);

        return openssl_decrypt(
            $encrypted,
            $this->cipher,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
    }
}
