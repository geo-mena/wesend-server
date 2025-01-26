<?php

namespace App\Http\Controllers\File;

use App\Http\Controllers\Controller;
use App\Services\EmailVerificationService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmailController extends Controller
{
    protected $emailVerificationService;

    public function __construct(
        EmailVerificationService $emailVerificationService
    ) {
        $this->emailVerificationService = $emailVerificationService;
    }

    /**
     * 🌱 Solicitar código de verificación
     * 
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function requestVerification(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        try {
            $this->emailVerificationService->sendVerificationCode(
                $request->input('email')
            );

            return response()->json([
                'success' => true,
                'message' => 'Verification code sent successfully'
            ]);
        } catch (Exception $e) {
            Log::debug('Error sending verification code: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error sending verification code'
            ], 500);
        }
    }

    /**
     * 🌱 Verificar código 
     * 
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function verifyCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6'
        ]);

        try {
            $isValid = $this->emailVerificationService->verifyCode(
                $request->input('email'),
                $request->input('code')
            );

            if (!$isValid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid verification code'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Email verified successfully'
            ]);
        } catch (Exception $e) {
            Log::debug('Error verifying code: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error verifying code'
            ], 500);
        }
    }

    /**
     * 🌱 Verificar estado de verificación
     * 
     * @param string $email
     * @return JsonResponse
     * @throws Exception
     */
    public function checkVerification($email)
    {
        try {
            $isVerified = $this->emailVerificationService->isEmailVerified($email);

            return response()->json([
                'success' => true,
                'is_verified' => $isVerified
            ]);
        } catch (Exception $e) {
            Log::debug('Error checking verification status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error checking verification status'
            ], 500);
        }
    }
}
