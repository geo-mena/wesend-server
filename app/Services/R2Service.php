<?php

namespace App\Services;

use Aws\S3\S3Client;
use Exception;
use Illuminate\Support\Facades\Log;

class R2Service
{
    protected $client;
    protected $bucket;

    public function __construct()
    {
        $this->bucket = config('services.r2.bucket');

        $this->client = new S3Client([
            'version' => 'latest',
            'region' => 'auto',
            'endpoint' => config('services.r2.endpoint'),
            'credentials' => [
                'key' => config('services.r2.access_key'),
                'secret' => config('services.r2.secret_key'),
            ],
            'use_path_style_endpoint' => true
        ]);
    }

    public function upload(string $content, string $path): string
    {
        try {
            $this->client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $path,
                'Body' => $content,
            ]);

            return $path;
        } catch (Exception $e) {
            Log::error('R2 upload error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function get(string $path): string
    {
        try {
            $result = $this->client->getObject([
                'Bucket' => $this->bucket,
                'Key' => $path
            ]);

            return (string) $result['Body'];
        } catch (Exception $e) {
            Log::error('R2 download error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function delete(string $path): void
    {
        try {
            $this->client->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $path
            ]);
        } catch (Exception $e) {
            Log::error('R2 delete error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
