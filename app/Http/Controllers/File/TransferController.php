<?php

namespace App\Http\Controllers\File;

use App\Http\Controllers\Controller;
use App\Jobs\SendEmailNotificationJob;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Services\TransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Transfer;
use Exception;

class TransferController extends Controller
{
    protected $transferService;

    public function __construct(TransferService $transferService)
    {
        $this->transferService = $transferService;
    }

    /**
     *  🚧 Método para crear una transferencia por email
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function createEmailTransfer(Request $request)
    {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'exists:files,id',
            'recipient_email' => 'required|email',
            'sender_email' => 'required|email',
            'message' => 'nullable|string',
            'password' => 'nullable|string|min:6',
            'expires_in' => 'nullable|in:1,2,3',
            'single_download' => 'nullable|boolean'
        ]);

        try {
            $transfer = Transfer::create([
                'type' => 'email',
                'message' => $request->input('message'),
                'password' => $request->has('password') ? Hash::make($request->input('password')) : null,
                'download_token' => Str::uuid(),
                'expires_at' => now()->addDays($request->input('expires_in', 1)),
                'sender_email' => $request->input('sender_email'),
                'recipient_email' => $request->input('recipient_email'),
                'single_download' => $request->input('single_download', false),
                'downloaded' => false
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

    /**
     * 🌱 Método para crear una transferencia por enlace
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function createLinkTransfer(Request $request)
    {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'exists:files,id',
            'message' => 'nullable|string',
            'password' => 'nullable|string|min:6',
            'expires_in' => 'nullable|in:1,2,3',
            'single_download' => 'nullable|boolean'
        ]);

        try {
            $transfer = Transfer::create([
                'type' => 'link',
                'message' => $request->input('message'),
                'password' => $request->has('password') ? Hash::make($request->input('password')) : null,
                'download_token' => Str::uuid(),
                'expires_at' => now()->addDays($request->input('expires_in', 1)),
                'single_download' => $request->input('single_download', false),
                'downloaded' => false
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

    /**
     * 🌱 Método para descargar un archivo
     *
     * @param string $token
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function download($token, Request $request)
    {
        try {
            $transfer = Transfer::where('download_token', $token)
                ->where('expires_at', '>', now())
                ->with('files')
                ->lockForUpdate()
                ->firstOrFail();

            if (!$request->has('download')) {
                return redirect()->to(config('app.frontend_url') . '/send/' . $token);
            }

            if ($transfer->password && !$request->has('password')) {
                return redirect()->to(config('app.frontend_url') . '/send/' . $token);
            }

            //! Verificar si es descarga única y ya fue descargada
            if ($transfer->single_download && $transfer->downloaded) {
                return response()->json([
                    'success' => false,
                    'message' => 'This link has already been used'
                ], 403);
            }

            //! Verificar contraseña si existe
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

            // Obtener archivo de la transferencia
            $file = $transfer->files()
                ->where('id', $request->input('file_id'))
                ->firstOrFail();

            $fileContent = $this->transferService->getDecryptedFile($file);

            if (empty($fileContent)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Empty file content'
                ], 500);
            }

            //! Marcar como descargado si es single_download
            if ($transfer->single_download) {
                $transfer->downloaded = true;
                $transfer->save();

                // Programar la limpieza para después de enviar el archivo
                register_shutdown_function(function () use ($transfer) {
                    $this->transferService->cleanupSingleDownload($transfer);
                });
            }

            DB::commit();

            $headers = [
                'Content-Type' => $file->mime_type,
                'Content-Disposition' => 'inline; filename="' . $file->original_name . '"',
                'Content-Length' => strlen($fileContent),
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache'
            ];

            return response($fileContent, 200, $headers);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error downloading file'
            ], 500);
        }
    }

    /**
     * 🌱 Método para verificar una transferencia
     *
     * @param string $token
     * @return JsonResponse
     * @throws Exception
     */
    public function checkTransfer($token)
    {
        try {
            $transfer = Transfer::where('download_token', $token)
                ->where('expires_at', '>', now())
                ->with('files')
                ->firstOrFail();

            //! Verificar si es descarga única y ya fue descargado
            if ($transfer->single_download && $transfer->downloaded) {
                return response()->json([
                    'success' => false,
                    'message' => 'This link has already been used'
                ], 403);
            }

            if ($transfer->password) {
                return response()->json([
                    'success' => true,
                    'is_protected' => true,
                    'single_download' => $transfer->single_download,
                    'downloaded' => $transfer->downloaded
                ]);
            }

            $files = $transfer->files->map(function ($file) use ($token) {
                return [
                    'name' => $file->original_name,
                    'size' => $file->size,
                    'download_url' => route('download', [
                        'token' => $token,
                        'download' => true,
                        'file_id' => $file->id
                    ])
                ];
            });

            return response()->json([
                'success' => true,
                'is_protected' => false,
                'file_info' => $files,
                'message' => $transfer->message,
                'expires_at' => $transfer->expires_at->format('d/m/Y H:i'),
                'single_download' => $transfer->single_download,
                'downloaded' => $transfer->downloaded
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transfer not found or expired'
            ], 404);
        }
    }

    /**
     * 🌱 Método para validar la contraseña de una transferencia
     *
     * @param Request $request
     * @param string $token
     * @return JsonResponse
     * @throws Exception
     */
    public function validatePassword(Request $request, $token)
    {
        try {
            $transfer = Transfer::where('download_token', $token)
                ->where('expires_at', '>', now())
                ->with('files')
                ->firstOrFail();

            if (!$transfer->password) {
                throw new Exception('This transfer is not password protected');
            }

            if (!Hash::check($request->input('password'), $transfer->password)) {
                throw new Exception('Invalid password');
            }

            $files = $transfer->files->map(function ($file) use ($token, $request) {
                return [
                    'name' => $file->original_name,
                    'size' => $file->size,
                    'download_url' => route('download', [
                        'token' => $token,
                        'password' => $request->input('password'),
                        'download' => true,
                        'file_id' => $file->id
                    ])
                ];
            });

            return response()->json([
                'success' => true,
                'file_info' => $files,
                'message' => $transfer->message,
                'expires_at' => $transfer->expires_at->format('d/m/Y H:i'),
                'single_download' => $transfer->single_download,
                'downloaded' => $transfer->downloaded
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 401);
        }
    }

    /**
     * 🌱 Método para previsualizar un archivo PDF
     * 
     * @param string $token
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function previewFile($token, Request $request)
    {
        try {
            $transfer = Transfer::where('download_token', $token)
                ->where('expires_at', '>', now())
                ->with('files')
                ->firstOrFail();

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

            $file = $transfer->files()
                ->where('id', $request->input('file_id'))
                ->firstOrFail();

            //! Validar que sea un PDF
            if ($file->mime_type !== 'application/pdf') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only PDF files can be previewed'
                ], 400);
            }

            $fileContent = $this->transferService->getDecryptedFile($file);

            if (empty($fileContent)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Empty file content'
                ], 500);
            }

            $headers = [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $file->original_name . '"',
                'Content-Length' => strlen($fileContent),
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache'
            ];

            return response($fileContent, 200, $headers);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error previewing file'
            ], 500);
        }
    }
}
