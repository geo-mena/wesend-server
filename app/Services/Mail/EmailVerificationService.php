<?php

namespace App\Services\Mail;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerificationCode;
use Exception;

class EmailVerificationService
{
    protected $redis;

    public function __construct()
    {
        $this->redis = Redis::connection();
    }

    /**
     * 🌱 Generar y enviar código de verificación
     * 
     * @param string $email
     * @return bool
     * @throws Exception
     */
    public function sendVerificationCode(string $email): bool
    {
        try {
            // Generar código aleatorio de 6 dígitos
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            // Almacenar en Redis con expiración de 15 minutos
            $this->redis->setex(
                "email_verification:{$email}",
                900, // 15 minutos
                $code
            );

            // Enviar email con el código
            Mail::to($email)->send(new VerificationCode($code));

            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * 🌱 Verificar código
     * 
     * @param string $email
     * @param string $code
     * @return bool
     * @throws Exception
     */
    public function verifyCode(string $email, string $code): bool
    {
        try {
            $storedCode = $this->redis->get("email_verification:{$email}");

            if (!$storedCode) {
                return false;
            }

            if ($code !== $storedCode) {
                return false;
            }

            // Si el código es válido, marcar email como verificado
            $this->markEmailAsVerified($email);

            // Eliminar código usado
            $this->redis->del("email_verification:{$email}");

            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * 🌱 Marcar email como verificado
     * 
     * @param string $email
     * @return void
     * @throws Exception
     */
    private function markEmailAsVerified(string $email): void
    {
        $this->redis->setex(
            "verified_email:{$email}",
            86400,
            'true'
        );
    }

    /**
     * 🌱 Verificar si un email está verificado
     * 
     * @param string $email
     * @return bool
     * @throws Exception
     */
    public function isEmailVerified(string $email): bool
    {
        return (bool) $this->redis->exists("verified_email:{$email}");
    }
}
