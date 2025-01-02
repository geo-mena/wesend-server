<?php

namespace App\Http\Controllers\File;

use App\Http\Controllers\Controller;
use App\Services\UploadService;
use App\Services\EncryptionService;
use Illuminate\Http\Request;
use App\Models\File;
use Exception;
use Illuminate\Support\Facades\Log;

class FileController extends Controller
{
    protected $uploadService;
    protected $encryptionService;

    public function __construct(UploadService $uploadService, EncryptionService $encryptionService)
    {
        $this->uploadService = $uploadService;
        $this->encryptionService = $encryptionService;
    }

    /**
     * Método para subir un chunk de un archivo
     *
     * @param Request $request
     * @return JsonResponse
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

            // Encriptar chunk
            // $encryptedChunk = $this->encryptionService->encrypt(
            //     file_get_contents($chunk->getPathname()),
            //     config('app.encryption_key')
            // );

            // Almacenar chunk en Redis
            // $this->uploadService->storeChunk($uploadId, $chunkNumber, $encryptedChunk, $totalChunks);

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
     * Método para finalizar la subida de un archivo
     *
     * @param Request $request
     * @return JsonResponse
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
            Log::debug('Error finalizing upload: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error finalizing upload'
            ], 500);
        }
    }

    /**
     * Método para eliminar los chunks de un archivo
     *
     * @param string $uploadId
     * @return JsonResponse
     */
    public function deleteChunks($uploadId)
    {
        try {
            // Eliminar los chunks de Redis
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
     * Método para eliminar un archivo
     *
     * @param int $id
     * @return JsonResponse
     */
    public function deleteFile($id)
    {
        try {
            $file = File::findOrFail($id);

            // Eliminar archivo de ImageKit
            $this->uploadService->deleteFileFromStorage($file->storage_path);

            // Eliminar registro de la base de datos
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
}
