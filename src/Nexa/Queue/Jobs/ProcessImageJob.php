<?php

namespace Nexa\Queue\Jobs;

use Nexa\Queue\Job;
// use Nexa\Logging\Logger; // Logger class doesn't exist yet

class ProcessImageJob extends Job
{
    protected $queue = 'images';
    protected $maxAttempts = 2;
    protected $timeout = 120; // 2 minutes for image processing

    public function __construct($imageData)
    {
        parent::__construct($imageData);
    }

    /**
     * Execute the job
     */
    public function handle()
    {
        $imagePath = $this->get('image_path');
        $sizes = $this->get('sizes', ['thumbnail' => [150, 150], 'medium' => [300, 300], 'large' => [800, 600]]);
        $outputDir = $this->get('output_dir', 'uploads/processed/');
        
        if (!$imagePath || !file_exists($imagePath)) {
            throw new \InvalidArgumentException('Image file not found: ' . $imagePath);
        }

        // Create output directory if it doesn't exist
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $processedImages = [];
        // $logger = new Logger(); // Logger class doesn't exist yet

        foreach ($sizes as $sizeName => $dimensions) {
            $outputPath = $this->generateOutputPath($imagePath, $sizeName, $outputDir);
            
            try {
                $this->resizeImage($imagePath, $outputPath, $dimensions[0], $dimensions[1]);
                $processedImages[$sizeName] = $outputPath;
                
                // $logger->debug("Image resized", [
                //     'size' => $sizeName,
                //     'dimensions' => $dimensions,
                //     'output_path' => $outputPath,
                //     'job_id' => $this->getId()
                // ]);
                
                // Temporary logging until Logger class is implemented
                error_log("Image resized - size: {$sizeName}, output: {$outputPath}, job_id: {$this->getId()}");
                
            } catch (\Exception $e) {
                // $logger->error("Failed to resize image", [
                //     'size' => $sizeName,
                //     'error' => $e->getMessage(),
                //     'job_id' => $this->getId()
                // ]);
                
                // Temporary logging until Logger class is implemented
                error_log("Failed to resize image - size: {$sizeName}, error: {$e->getMessage()}, job_id: {$this->getId()}");
                throw $e;
            }
        }

        // Update database with processed image paths if model ID is provided
        $modelId = $this->get('model_id');
        $modelClass = $this->get('model_class');
        
        if ($modelId && $modelClass && class_exists($modelClass)) {
            $this->updateModel($modelClass, $modelId, $processedImages);
        }

        // $logger->info('Image processing completed', [
        //     'original_image' => $imagePath,
        //     'processed_images' => $processedImages,
        //     'job_id' => $this->getId()
        // ]);
        
        // Temporary logging until Logger class is implemented
        $processedCount = count($processedImages);
        error_log("Image processing completed - original: {$imagePath}, processed: {$processedCount} images, job_id: {$this->getId()}");
    }

    /**
     * Resize image to specified dimensions
     */
    private function resizeImage($sourcePath, $outputPath, $width, $height)
    {
        // Get image info
        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            throw new \Exception('Invalid image file');
        }

        $sourceWidth = $imageInfo[0];
        $sourceHeight = $imageInfo[1];
        $imageType = $imageInfo[2];

        // Create source image resource
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($sourcePath);
                break;
            default:
                throw new \Exception('Unsupported image type');
        }

        if (!$sourceImage) {
            throw new \Exception('Failed to create image resource');
        }

        // Calculate aspect ratio
        $aspectRatio = $sourceWidth / $sourceHeight;
        $targetAspectRatio = $width / $height;

        if ($aspectRatio > $targetAspectRatio) {
            // Image is wider than target
            $newWidth = $width;
            $newHeight = $width / $aspectRatio;
        } else {
            // Image is taller than target
            $newHeight = $height;
            $newWidth = $height * $aspectRatio;
        }

        // Create new image
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG and GIF
        if ($imageType == IMAGETYPE_PNG || $imageType == IMAGETYPE_GIF) {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefill($newImage, 0, 0, $transparent);
        }

        // Resize image
        imagecopyresampled(
            $newImage, $sourceImage,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $sourceWidth, $sourceHeight
        );

        // Save image
        $success = false;
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $success = imagejpeg($newImage, $outputPath, 85);
                break;
            case IMAGETYPE_PNG:
                $success = imagepng($newImage, $outputPath, 6);
                break;
            case IMAGETYPE_GIF:
                $success = imagegif($newImage, $outputPath);
                break;
        }

        // Clean up
        imagedestroy($sourceImage);
        imagedestroy($newImage);

        if (!$success) {
            throw new \Exception('Failed to save resized image');
        }
    }

    /**
     * Generate output path for resized image
     */
    private function generateOutputPath($originalPath, $sizeName, $outputDir)
    {
        $pathInfo = pathinfo($originalPath);
        $filename = $pathInfo['filename'] . '_' . $sizeName . '.' . $pathInfo['extension'];
        return rtrim($outputDir, '/') . '/' . $filename;
    }

    /**
     * Update model with processed image paths
     */
    private function updateModel($modelClass, $modelId, $processedImages)
    {
        try {
            $model = $modelClass::find($modelId);
            if ($model) {
                // Store processed images as JSON
                $model->processed_images = json_encode($processedImages);
                $model->image_processed_at = date('Y-m-d H:i:s');
                $model->save();
            }
        } catch (\Exception $e) {
            // Log error but don't fail the job
            // $logger = new Logger(); // Logger class doesn't exist yet
            // $logger->error('Failed to update model with processed images', [
            //     'model_class' => $modelClass,
            //     'model_id' => $modelId,
            //     'error' => $e->getMessage(),
            //     'job_id' => $this->getId()
            // ]);
            
            // Temporary logging until Logger class is implemented
            error_log("Failed to update model with processed images: {$e->getMessage()}");
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Exception $exception)
    {
        // $logger = new Logger(); // Logger class doesn't exist yet
        // $logger->error('Image processing job failed', [
        //     'image_path' => $this->get('image_path'),
        //     'error' => $exception->getMessage(),
        //     'attempts' => $this->getAttempts(),
        //     'job_id' => $this->getId()
        // ]);
        
        // Temporary logging until Logger class is implemented
        $imagePath = $this->get('image_path');
        $error = $exception->getMessage();
        $attempts = $this->getAttempts();
        $jobId = $this->getId();
        error_log("Image processing job failed - path: {$imagePath}, error: {$error}, attempts: {$attempts}, job_id: {$jobId}");

        // Clean up any partially processed images
        $this->cleanupPartialImages();
    }

    /**
     * Clean up partially processed images
     */
    private function cleanupPartialImages()
    {
        $outputDir = $this->get('output_dir', 'uploads/processed/');
        $imagePath = $this->get('image_path');
        
        if ($imagePath) {
            $pathInfo = pathinfo($imagePath);
            $pattern = $outputDir . $pathInfo['filename'] . '_*.' . $pathInfo['extension'];
            
            foreach (glob($pattern) as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * Determine if the job should be retried
     */
    public function shouldRetry(\Exception $exception)
    {
        // Don't retry for validation errors or unsupported image types
        if ($exception instanceof \InvalidArgumentException) {
            return false;
        }

        if (strpos($exception->getMessage(), 'Unsupported image type') !== false) {
            return false;
        }

        return parent::shouldRetry($exception);
    }

    /**
     * Retry a failed job
     *
     * @param string $jobId
     * @return bool
     */
    public function retry($jobId)
    {
        // For image processing jobs, we can simply return false as retry logic
        // is typically handled by the queue system itself
        // This method is mainly for custom retry logic if needed
        return false;
    }
}