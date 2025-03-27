<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use League\Csv\Reader;
use Aws\S3\S3Client;
use Exception;

class ImageProcessorController extends Controller
{
    /**
     * @var S3Client
     */
    protected $s3Client;
    
    /**
     * Constructor s3Client with AWS credentials
     * 
     * @return void
     * @throws Exception
     */
    public function __construct()
    {
        $this->s3Client = new S3Client([
            'version' => 'latest',
            'region' => config('services.aws.region'),
            'credentials' => [
                'key' => config('services.aws.key'),
                'secret' => config('services.aws.secret'),
            ],
            'profile' => config('services.aws.profile', 'support'),
        ]);
    }

    /**
     * Process a CSV file and extract images from S3
     * 
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function processImages(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
            'bucket_name' => 'required|string',
        ]);

        $outputDir = storage_path('app/public/output');
        if (!file_exists($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        try {
            // Process the uploaded CSV file
            $csvFile = $request->file('csv_file');
            $csvPath = $csvFile->getRealPath();
            
            // Read CSV file
            $csv = Reader::createFromPath($csvPath, 'r');
            $csv->setHeaderOffset(0);
            
            $records = $csv->getRecords();
            $processed = 0;
            $failed = 0;
            $results = [];
            
            foreach ($records as $record) {
                if (!isset($record['documentfrontrawimage'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'CSV must contain "documentfrontrawimage" column'
                    ], 400);
                }
                
                $imageKey = $record['documentfrontrawimage'];
                
                try {
                    // Get corresponding JSON key
                    $jsonKey = $this->getJsonKey($imageKey);
                    
                    // Download JSON file from S3
                    $jsonObject = $this->s3Client->getObject([
                        'Bucket' => $request->bucket_name,
                        'Key' => $jsonKey,
                    ]);
                    
                    $jsonContent = $jsonObject['Body']->getContents();
                    $data = json_decode($jsonContent, true);
                    
                    if (!isset($data['requestBody'])) {
                        throw new Exception("'requestBody' not found in JSON");
                    }
                    
                    $requestBody = json_decode($data['requestBody'], true);
                    
                    // Get base64-encoded image data
                    if (!isset($requestBody['documentFrontRawImage']) || !isset($requestBody['documentBackRawImage'])) {
                        throw new Exception("Missing 'documentFrontRawImage' or 'documentBackRawImage' in requestBody");
                    }
                    
                    // Decode base64 to image bytes
                    $frontImageB64 = $requestBody['documentFrontRawImage'];
                    $backImageB64 = $requestBody['documentBackRawImage'];
                    
                    $frontImageBytes = base64_decode($frontImageB64);
                    $backImageBytes = base64_decode($backImageB64);
                    
                    // Extract transaction ID for naming
                    $transactionId = basename($jsonKey, '.json');
                    
                    // Save images as PNG
                    $frontImagePath = "{$outputDir}/{$transactionId}_front.png";
                    $backImagePath = "{$outputDir}/{$transactionId}_back.png";
                    
                    file_put_contents($frontImagePath, $frontImageBytes);
                    file_put_contents($backImagePath, $backImageBytes);
                    
                    $results[] = [
                        'transaction_id' => $transactionId,
                        'front_image' => url(Storage::url("public/output/{$transactionId}_front.png")),
                        'back_image' => url(Storage::url("public/output/{$transactionId}_back.png")),
                    ];
                    
                    Log::info("Saved images: {$frontImagePath}, {$backImagePath}");
                    $processed++;
                    
                } catch (Exception $e) {
                    Log::error("Failed to process {$imageKey}: {$e->getMessage()}");
                    $failed++;
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => "Processing completed. Processed: {$processed}, Failed: {$failed}",
                'results' => $results
            ]);
            
        } catch (Exception $e) {
            Log::error("Processing failed: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'message' => "Processing failed: " . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Derive the JSON key from an image key by extracting the transaction ID.
     * 
     * @param string $imageKey S3 key for an image
     * @return string Corresponding JSON key
     * @throws Exception
     */
    private function getJsonKey($imageKey)
    {
        $parts = explode('/', $imageKey);
        
        // Find the resource index
        $resourceIndex = null;
        foreach ($parts as $i => $part) {
            if (strpos($part, 'resource=') === 0) {
                $resourceIndex = $i;
                break;
            }
        }
        
        if ($resourceIndex === null) {
            throw new Exception("Invalid key format: {$imageKey}");
        }
        
        $basePath = implode('/', array_slice($parts, 0, $resourceIndex + 1));
        
        // Get transaction part
        $transactionPart = $parts[$resourceIndex + 1];
        if (strpos($transactionPart, 'transaction=') !== 0) {
            throw new Exception("Invalid key format: {$imageKey}");
        }
        
        $transactionId = explode('=', $transactionPart)[1];
        
        return "{$basePath}/{$transactionId}.json";
    }
}
