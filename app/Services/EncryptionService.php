<?php

namespace App\Services;

use Exception;

class EncryptionService
{
    private string $cipher = 'aes-256-cbc';

    /**
     * 🔒️ Método para encriptar un string
     *
     * @param string $data
     * @param string $key
     * @return string
     */
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
            throw $e;
        }
    }

    /**
     * 🔒️ Método para desencriptar un string
     *
     * @param string $data
     * @param string $key
     * @return string
     */
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
            throw $e;
        }
    }
}
