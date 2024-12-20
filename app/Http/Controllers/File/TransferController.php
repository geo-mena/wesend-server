<?php

namespace App\Http\Controllers\File;

use App\Http\Controllers\Controller;
use App\Jobs\SendEmailNotificationJob;
use App\Services\TransferService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Transfer;
use Exception;
use Illuminate\Support\Facades\Log;

class TransferController extends Controller
{
    protected $transferService;

    public function __construct(TransferService $transferService)
    {
        $this->transferService = $transferService;
    }

    //! Método para crear una transferencia por email
    public function createEmailTransfer(Request $request)
    {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'exists:files,id',
            'recipient_email' => 'required|email',
            'sender_email' => 'required|email',
            'message' => 'nullable|string',
            'password' => 'nullable|string|min:6',
            'expires_in' => 'nullable|in:1,3'
        ]);

        try {
            $transfer = Transfer::create([
                'type' => 'email',
                'message' => $request->input('message'),
                'password' => $request->has('password') ?
                    Hash::make($request->input('password')) : null,
                'sender_email' => $request->input('sender_email'),
                'recipient_email' => $request->input('recipient_email'),
                'download_token' => Str::random(32),
                'expires_at' => now()->addDays($request->input('expires_in', 1))
            ]);

            // Asociar archivos con la transferencia
            $transfer->files()->attach($request->input('files'));

            // Encolar el envío del email
            SendEmailNotificationJob::dispatch($transfer);

            return response()->json([
                'success' => true,
                'message' => 'Transfer created successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating transfer'
            ], 500);
        }
    }

    //! Método para crear un enlace de descarga
    public function createLinkTransfer(Request $request)
    {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'exists:files,id',
            'message' => 'nullable|string',
            'password' => 'nullable|string|min:6',
            'expires_in' => 'nullable|in:1,3'
        ]);

        try {
            $transfer = Transfer::create([
                'type' => 'link',
                'message' => $request->input('message'),
                'password' => $request->has('password') ?
                    Hash::make($request->input('password')) : null,
                'download_token' => Str::random(32),
                'expires_at' => now()->addDays($request->input('expires_in', 1))
            ]);

            $transfer->files()->attach($request->input('files'));

            return response()->json([
                'success' => true,
                'download_link' => route('download', ['token' => $transfer->download_token])
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating transfer'
            ], 500);
        }
    }

    //! Método para descargar un archivo
    public function download($token, Request $request)
    {
        try {
            $transfer = Transfer::where('download_token', $token)
                ->where('expires_at', '>', now())
                ->with('files')
                ->firstOrFail();

            // Verificar contraseña si existe
            if ($transfer->password) {
                $request->validate([
                    'password' => 'required|string'
                ]);

                if (!Hash::check($request->input('password'), $transfer->password)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid password'
                    ], 403);
                }
            }


            // Obtener archivo de ImageKit y desencriptar
            $file = $transfer->files()->first();

            if (!$file) {
                return response()->json([
                    'success' => false,
                    'message' => 'No file found'
                ], 404);
            }

            $fileContent = $this->transferService->getDecryptedFile($file);

            if (empty($fileContent)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Empty file content'
                ], 500);
            }

            $headers = [
                'Content-Type' => $file->mime_type,
                'Content-Disposition' => 'inline; filename="' . $file->original_name . '"',
                'Content-Length' => strlen($fileContent),
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache'
            ];

            return response($fileContent, 200, $headers);
        } catch (Exception $e) {
            Log::error('Download error', [
                'token' => $token,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error downloading file'
            ], 500);
        }
    }
}
