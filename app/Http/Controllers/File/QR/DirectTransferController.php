<?php

namespace App\Http\Controllers\File\QR;

use App\Http\Controllers\Controller;
use App\Services\QR\DirectTransferService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DirectTransferController extends Controller
{
    protected $directTransferService;

    public function __construct(
        DirectTransferService $directTransferService
    ) {
        $this->directTransferService = $directTransferService;
    }

    /**
     * Genera un código QR y PIN para transferencia directa
     * 
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function generate(Request $request)
    {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'exists:files,id'
        ]);

        try {
            $pin = mt_rand(100000, 999999); // Genera PIN de 6 dígitos
            $token = Str::uuid();

            $transfer = $this->directTransferService->create([
                'files' => $request->input('files'),
                'pin' => $pin,
                'token' => $token,
                'expires_at' => now()->addMinutes(10)
            ]);

            $qrData = route('direct.download', ['token' => $token]);

            return response()->json([
                'success' => true,
                'pin' => $pin,
                'qr_data' => $qrData,
                'expires_at' => $transfer->expires_at
            ]);
        } catch (Exception $e) {
            Log::debug($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error generating direct transfer'
            ], 500);
        }
    }

    /**
     * Valida el PIN y permite la descarga
     * 
     * @param Request $request
     * @param string $token
     * @return JsonResponse
     * @throws Exception
     */
    public function validatePin(Request $request, $token)
    {
        $request->validate([
            'pin' => 'required|string|size:6'
        ]);

        try {
            $transfer = $this->directTransferService->validatePin(
                $token,
                $request->input('pin')
            );

            return response()->json([
                'success' => true,
                'files' => $transfer->files
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 403);
        }
    }

    /**
     * Descarga archivos de una transferencia directa
     * 
     * @param string $token
     * @return JsonResponse
     * @throws Exception
     */
    public function download($token)
    {
        try {
            $transfer = $this->directTransferService->findTransfer($token);

            return response()->json([
                'success' => true,
                'files' => $transfer->files->map(function ($file) {
                    return [
                        'id' => $file->id,
                        'name' => $file->original_name,
                        'size' => $file->size,
                        'mime_type' => $file->mime_type
                    ];
                })
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transfer not found or expired'
            ], 404);
        }
    }
}
