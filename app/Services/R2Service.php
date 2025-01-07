<?php

namespace App\Services;

use Aws\S3\S3Client;
use Exception;
use GuzzleHttp\Promise\Utils;

class R2Service
{
    protected $client;
    protected $bucket;

    public function __construct()
    {
        $this->bucket = config('services.r2.bucket');

        ini_set('memory_limit', '1G');

        $this->client = new S3Client([
            'version' => 'latest',
            'region' => 'auto',
            'endpoint' => config('services.r2.endpoint'),
            'credentials' => [
                'key' => config('services.r2.access_key'),
                'secret' => config('services.r2.secret_key'),
            ],
            'use_path_style_endpoint' => true,
            'http' => [
                'connect_timeout' => 5,
                'timeout' => 300,        
                'read_timeout' => 300,   
                'pool_size' => 25        
            ],
            'stream_size' => 67108864
        ]);
    }

    /**
     * 🔒️ Subir archivos en batch
     *
     * @param array $files
     * @return array
     * @throws Exception
     */
    public function uploadBatch(array $files): array
    {
        try {
            $promises = [];

            foreach ($files as $file) {
                $stream = $this->createStream($file['content']);

                $promises[$file['path']] = $this->client->putObjectAsync([
                    'Bucket' => $this->bucket,
                    'Key' => $file['path'],
                    'Body' => $stream,
                    '@http' => [
                        'timeout' => 300
                    ]
                ]);
            }

            // Esperar a que todas las promesas se completen
            $results = Utils::settle($promises)->wait();

            $uploadResults = [];
            foreach ($results as $path => $result) {
                if ($result['state'] === 'fulfilled') {
                    $uploadResults[] = $path;
                } else {
                    throw $result['reason'];
                }
            }

            return $uploadResults;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * 🔒️ Obtener archivos en batch
     *
     * @param array $paths
     * @return array
     * @throws Exception
     */
    private function createStream($content)
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $content);
        rewind($stream);
        return $stream;
    }

    /**
     * 🔒️ Obtener archivos en batch
     *
     * @param array $paths
     * @return array
     * @throws Exception
     */
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
            throw $e;
        }
    }

    /**
     * 🔒️ Obtener archivos en batch
     *
     * @param array $paths
     * @return array
     * @throws Exception
     */
    public function get(string $path): string
    {
        try {
            $result = $this->client->getObject([
                'Bucket' => $this->bucket,
                'Key' => $path
            ]);

            return (string) $result['Body'];
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * 🔒️ Eliminar archivos en batch
     *
     * @param array $paths
     * @return void
     * @throws Exception
     */
    public function delete(string $path): void
    {
        try {
            $this->client->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $path
            ]);
        } catch (Exception $e) {
            throw $e;
        }
    }
}
