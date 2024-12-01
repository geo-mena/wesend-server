<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;

class EncryptionService
{
    private string $cipher = 'aes-256-cbc';

    public function encrypt(string $data, string $key): string
    {
        try {
            $ivLength = openssl_cipher_iv_length($this->cipher);
            $iv = openssl_random_pseudo_bytes($ivLength);

            $encrypted = openssl_encrypt(
                $data,
                $this->cipher,
                $key,
                OPENSSL_RAW_DATA,
                $iv
            );

            if ($encrypted === false) {
                throw new Exception('Encryption failed: ' . openssl_error_string());
            }

            $result = $iv . $encrypted;

            return $result;
        } catch (Exception $e) {
            Log::error('Encryption error', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function decrypt(string $data, string $key): string
    {
        try {
            $ivLength = openssl_cipher_iv_length($this->cipher);

            $iv = substr($data, 0, $ivLength);
            $encrypted = substr($data, $ivLength);

            $decrypted = openssl_decrypt(
                $encrypted,
                $this->cipher,
                $key,
                OPENSSL_RAW_DATA,
                $iv
            );

            if ($decrypted === false) {
                throw new Exception('Decryption failed: ' . openssl_error_string());
            }

            return $decrypted;
        } catch (Exception $e) {
            Log::error('Decryption error', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
