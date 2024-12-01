<?php

namespace App\Http\Controllers\File;

use App\Http\Controllers\Controller;
use App\Services\UploadService;
use App\Services\EncryptionService;
use Illuminate\Http\Request;
use App\Models\File;
use Exception;

class FileController extends Controller
{
    protected $uploadService;
    protected $encryptionService;

    public function __construct(UploadService $uploadService, EncryptionService $encryptionService)
    {
        $this->uploadService = $uploadService;
        $this->encryptionService = $encryptionService;
    }

    //! Método para subir un chunk
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
            $uploadId = $request->input('uploadId');

            // Encriptar chunk
            $encryptedChunk = $this->encryptionService->encrypt(
                file_get_contents($chunk->getPathname()),
                config('app.encryption_key')
            );

            // Almacenar chunk en Redis
            $this->uploadService->storeChunk($uploadId, $chunkNumber, $encryptedChunk);

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

    //! Método para finalizar la subida
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
                'fileId' => $file->id
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error finalizing upload'
            ], 500);
        }
    }
}
