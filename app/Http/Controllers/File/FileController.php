<?php

namespace App\Http\Controllers\File;

use App\Http\Controllers\Controller;
use App\Services\UploadService;
use App\Services\EncryptionService;
use Illuminate\Http\Request;
use App\Models\File;
use App\Services\RateLimitService;
use Exception;

class FileController extends Controller
{
    protected $uploadService;
    protected $encryptionService;
    protected $rateLimitService;

    public function __construct(
        UploadService $uploadService,
        EncryptionService $encryptionService,
        RateLimitService $rateLimitService
    ) {
        $this->uploadService = $uploadService;
        $this->encryptionService = $encryptionService;
        $this->rateLimitService = $rateLimitService;
    }

    /**
     * 🌱 Método para subir un chunk de un archivo
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required',
            'chunk' => 'required|integer',
            'totalChunks' => 'required|integer',
            'uploadId' => 'required|string'
        ]);

        try {
            $chunk = $request->file('file');
            $chunkNumber = $request->input('chunk');
            $totalChunks = $request->input('totalChunks');
            $uploadId = $request->input('uploadId');

            // Leer el contenido del chunk
            $chunkContent = file_get_contents($chunk->getPathname());

            // Encriptar
            $encryptedChunk = $this->encryptionService->encrypt(
                $chunkContent,
                config('app.encryption_key')
            );

            $this->uploadService->storeChunk($uploadId, $chunkNumber, $encryptedChunk, $totalChunks);

            return response()->json([
                'success' => true,
                'message' => 'Chunk uploaded successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error uploading chunk'
            ], 500);
        }
    }

    /**
     * 🌱 Método para finalizar la subida de un archivo
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function finalize(Request $request)
    {
        $request->validate([
            'uploadId' => 'required|string',
            'filename' => 'required|string',
            'mimeType' => 'required|string'
        ]);

        try {
            $uploadId = $request->input('uploadId');

            // Combinar chunks y subir a ImageKit
            $finalFile = $this->uploadService->finalizeUpload($uploadId);

            if (empty($finalFile['encryption_key'])) {
                throw new Exception('Missing encryption key in finalized file');
            }

            // Crear registro en la base de datos
            $file = File::create([
                'original_name' => $request->input('filename'),
                'storage_path' => $finalFile['path'],
                'size' => $finalFile['size'],
                'mime_type' => $request->input('mimeType'),
                'encryption_key' => $finalFile['encryption_key'],
                'expires_at' => now()->addDays(3)
            ]);

            return response()->json([
                'success' => true,
                'fileId' => $file->id,
                'message' => 'File uploaded successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error finalizing upload'
            ], 500);
        }
    }

    /**
     * 🌱 Método para eliminar los chunks de un archivo
     *
     * @param string $uploadId
     * @return JsonResponse
     * @throws Exception
     */
    public function deleteChunks($uploadId)
    {
        try {
            $this->uploadService->deleteChunks($uploadId);

            return response()->json([
                'success' => true,
                'message' => 'Chunks deleted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting chunks'
            ], 500);
        }
    }

    /**
     * 🌱 Método para eliminar un archivo
     *
     * @param int $id
     * @return JsonResponse
     * @throws Exception
     */
    public function deleteFile($id)
    {
        try {
            $file = File::findOrFail($id);

            $this->uploadService->deleteFileFromStorage($file->storage_path);
            $file->delete();

            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting file'
            ], 500);
        }
    }

    /**
     * 🌱 Método para subir varios archivos a la vez
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function finalizeBatch(Request $request)
    {
        $request->validate([
            'uploads' => 'required|array',
            'uploads.*.uploadId' => 'required|string',
            'uploads.*.filename' => 'required|string',
            'uploads.*.mimeType' => 'required|string',
            'uploads.*.totalSize' => 'required|integer'
        ]);

        try {
            //! Calcular tamaño total del batch
            $totalBatchSize = collect($request->input('uploads'))
                ->sum(function ($upload) {
                    return $upload['totalSize'];
                });

            //! Verificar límite antes de procesar
            $checkLimit = $this->rateLimitService->canUpload(
                $request->ip(),
                $totalBatchSize
            );

            if (!$checkLimit['allowed']) {
                return response()->json([
                    'success' => false,
                    'message' => $checkLimit['message'],
                    'remaining_bytes' => $checkLimit['remaining_bytes'],
                    'reset_in' => $checkLimit['reset_in']
                ], 429);
            }

            // Procesar todos los archivos de una vez
            $finalFiles = $this->uploadService->finalizeBatch($request->input('uploads'));

            // Crear registros en la base de datos en batch
            $files = collect($request->input('uploads'))->map(function ($upload, $index) use ($finalFiles) {
                return File::create([
                    'original_name' => $upload['filename'],
                    'storage_path' => $finalFiles[$index]['path'],
                    'size' => $finalFiles[$index]['size'],
                    'mime_type' => $upload['mimeType'],
                    'encryption_key' => $finalFiles[$index]['encryption_key'],
                    'expires_at' => now()->addDays(3)
                ]);
            });

            //! Registrar uso total al finalizar
            $this->rateLimitService->trackUsage(
                $request->ip(),
                $totalBatchSize
            );

            $results = $files->map(function ($file, $index) use ($request) {
                return [
                    'uploadId' => $request->input('uploads')[$index]['uploadId'],
                    'fileId' => $file->id,
                    'success' => true
                ];
            })->all();

            return response()->json([
                'success' => true,
                'files' => $results,
                'message' => 'Files uploaded successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error finalizing uploads'
            ], 500);
        }
    }

    /**
     * 🔖 Verifica si se puede realizar una subida basado en el límite de uso
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function checkLimit(Request $request)
    {
        $request->validate([
            'totalSize' => 'required|integer|min:1'
        ]);

        try {
            $checkLimit = $this->rateLimitService->canUpload(
                $request->ip(),
                $request->input('totalSize')
            );

            return response()->json([
                'success' => true,
                'allowed' => $checkLimit['allowed'],
                'remaining_bytes' => $checkLimit['remaining_bytes'],
                'reset_in' => $checkLimit['reset_in'],
                'message' => $checkLimit['message'] ?? null
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar límite de subida'
            ], 500);
        }
    }
}
