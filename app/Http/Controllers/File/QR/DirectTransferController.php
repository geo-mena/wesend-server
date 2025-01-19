<?php

namespace App\Http\Controllers\File\QR;

use App\Http\Controllers\Controller;
use App\Services\QR\DirectTransferService;
use App\Services\TransferService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DirectTransferController extends Controller
{
    protected $directTransferService;
    protected $transferService;

    public function __construct(
        DirectTransferService $directTransferService,
        TransferService $transferService
    ) {
        $this->directTransferService = $directTransferService;
        $this->transferService = $transferService;
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
                'expires_at' => now()->addMinutes(60)
            ]);

            $qrData = config('app.frontend_url') . '/send/direct/' . $token;

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
     * Descarga un archivo específico de una transferencia
     * 
     * @param string $token
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function download($token, Request $request)
    {
        try {
            DB::beginTransaction();

            $transfer = $this->directTransferService->findTransfer($token);

            if (!$request->has('download')) {
                return redirect()->to(config('app.frontend_url') . '/send/direct/' . $token);
            }

            if ($transfer->used) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta transferencia ya ha sido utilizada'
                ], 403);
            }

            // Obtener archivo de la transferencia
            $file = $transfer->files()
                ->where('id', $request->input('file_id'))
                ->firstOrFail();

            // Obtener contenido desencriptado usando el TransferService
            $fileContent = $this->transferService->getDecryptedFile($file);

            if (empty($fileContent)) {
                throw new Exception('Empty file content');
            }

            // Marcar la transferencia como usada
            $transfer->used = true;
            $transfer->save();

            DB::commit();

            $headers = [
                'Content-Type' => $file->mime_type,
                'Content-Disposition' => 'attachment; filename="' . $file->original_name . '"',
                'Content-Length' => strlen($fileContent),
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache'
            ];

            return response($fileContent, 200, $headers);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error downloading direct transfer file: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error downloading file'
            ], 500);
        }
    }

    /**
     * Verifica el estado de una transferencia directa
     * 
     * @param string $token
     * @return JsonResponse
     */
    public function findTransfer($token)
    {
        try {
            $transfer = $this->directTransferService->findTransfer($token);

            return response()->json([
                'success' => true,
                'files' => $transfer->files->map(function ($file) use ($token) {
                    return [
                        'id' => $file->id,
                        'name' => $file->original_name,
                        'size' => $file->size,
                        'mime_type' => $file->mime_type,
                        'download_url' => route('direct.download', [
                            'token' => $token,
                            'download' => true,
                            'file_id' => $file->id
                        ])
                    ];
                }),
                'expires_at' => $transfer->expires_at->format('d/m/Y H:i')
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transfer not found or expired'
            ], 404);
        }
    }
}
