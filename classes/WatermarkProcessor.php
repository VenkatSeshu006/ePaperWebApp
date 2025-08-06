<?php
/**
 * WatermarkProcessor Class
 * Handles watermark application to clip images
 */

class WatermarkProcessor {
    private $watermarkSettings;
    private $db;
    
    public function __construct() {
        $this->db = getConnection();
        $this->loadWatermarkSettings();
    }
    
    /**
     * Load watermark settings from database
     */
    private function loadWatermarkSettings() {
        $this->watermarkSettings = [];
        
        try {
            $result = $this->db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'watermark_%'");
            while ($row = $result->fetch()) {
                $this->watermarkSettings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Exception $e) {
            error_log("Error loading watermark settings: " . $e->getMessage());
        }
    }
    
    /**
     * Check if watermark is enabled
     */
    public function isWatermarkEnabled() {
        return !empty($this->watermarkSettings['watermark_enabled']) && 
               $this->watermarkSettings['watermark_enabled'] === '1' &&
               !empty($this->watermarkSettings['watermark_logo_path']) &&
               file_exists($this->watermarkSettings['watermark_logo_path']);
    }
    
    /**
     * Apply watermark to a clip image
     */
    public function applyWatermark($sourceImagePath, $outputImagePath = null) {
        if (!$this->isWatermarkEnabled()) {
            // If no watermark, just copy the source to output if different paths
            if ($outputImagePath && $outputImagePath !== $sourceImagePath) {
                copy($sourceImagePath, $outputImagePath);
            }
            return $outputImagePath ?: $sourceImagePath;
        }
        
        if (!file_exists($sourceImagePath)) {
            throw new Exception("Source image not found: $sourceImagePath");
        }
        
        $watermarkPath = $this->watermarkSettings['watermark_logo_path'];
        if (!file_exists($watermarkPath)) {
            throw new Exception("Watermark image not found: $watermarkPath");
        }
        
        // If no output path specified, create a temporary watermarked version
        if (!$outputImagePath) {
            $pathInfo = pathinfo($sourceImagePath);
            $outputImagePath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_watermarked.' . $pathInfo['extension'];
        }
        
        try {
            // Load source image
            $sourceImage = $this->createImageFromFile($sourceImagePath);
            if (!$sourceImage) {
                throw new Exception("Cannot load source image");
            }
            
            // Load watermark image
            $watermarkImage = $this->createImageFromFile($watermarkPath);
            if (!$watermarkImage) {
                imagedestroy($sourceImage);
                throw new Exception("Cannot load watermark image");
            }
            
            // Get dimensions
            $sourceWidth = imagesx($sourceImage);
            $sourceHeight = imagesy($sourceImage);
            $watermarkWidth = imagesx($watermarkImage);
            $watermarkHeight = imagesy($watermarkImage);
            
            // Calculate watermark size based on settings
            $newWatermarkSize = $this->calculateWatermarkSize($sourceWidth, $sourceHeight);
            
            // Resize watermark if needed
            if ($watermarkWidth !== $newWatermarkSize['width'] || $watermarkHeight !== $newWatermarkSize['height']) {
                $resizedWatermark = imagecreatetruecolor($newWatermarkSize['width'], $newWatermarkSize['height']);
                
                // Preserve transparency
                imagealphablending($resizedWatermark, false);
                imagesavealpha($resizedWatermark, true);
                $transparent = imagecolorallocatealpha($resizedWatermark, 255, 255, 255, 127);
                imagefill($resizedWatermark, 0, 0, $transparent);
                imagealphablending($resizedWatermark, true);
                
                imagecopyresampled($resizedWatermark, $watermarkImage, 0, 0, 0, 0,
                    $newWatermarkSize['width'], $newWatermarkSize['height'],
                    $watermarkWidth, $watermarkHeight);
                
                imagedestroy($watermarkImage);
                $watermarkImage = $resizedWatermark;
                $watermarkWidth = $newWatermarkSize['width'];
                $watermarkHeight = $newWatermarkSize['height'];
            }
            
            // Calculate position
            $position = $this->calculateWatermarkPosition($sourceWidth, $sourceHeight, $watermarkWidth, $watermarkHeight);
            
            // Apply watermark with opacity
            $opacity = intval($this->watermarkSettings['watermark_opacity'] ?? 80);
            $this->imagecopymerge_alpha($sourceImage, $watermarkImage, 
                $position['x'], $position['y'], 0, 0, 
                $watermarkWidth, $watermarkHeight, $opacity);
            
            // Save the result
            $this->saveImage($sourceImage, $outputImagePath);
            
            // Cleanup
            imagedestroy($sourceImage);
            imagedestroy($watermarkImage);
            
            return $outputImagePath;
            
        } catch (Exception $e) {
            error_log("Watermark application error: " . $e->getMessage());
            
            // Fallback: copy original if watermark fails
            if ($outputImagePath && $outputImagePath !== $sourceImagePath) {
                copy($sourceImagePath, $outputImagePath);
            }
            
            return $outputImagePath ?: $sourceImagePath;
        }
    }
    
    /**
     * Create image resource from file
     */
    private function createImageFromFile($filePath) {
        $imageInfo = getimagesize($filePath);
        if (!$imageInfo) {
            return false;
        }
        
        switch ($imageInfo[2]) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($filePath);
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($filePath);
                imagealphablending($image, false);
                imagesavealpha($image, true);
                return $image;
            case IMAGETYPE_GIF:
                return imagecreatefromgif($filePath);
            default:
                return false;
        }
    }
    
    /**
     * Save image to file
     */
    private function saveImage($imageResource, $filePath) {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                return imagejpeg($imageResource, $filePath, 95);
            case 'png':
                imagesavealpha($imageResource, true);
                return imagepng($imageResource, $filePath);
            case 'gif':
                return imagegif($imageResource, $filePath);
            default:
                return imagejpeg($imageResource, $filePath, 95);
        }
    }
    
    /**
     * Calculate watermark size based on source image and settings
     */
    private function calculateWatermarkSize($sourceWidth, $sourceHeight) {
        $size = $this->watermarkSettings['watermark_size'] ?? 'medium';
        
        // Base sizes (max width)
        $baseSizes = [
            'small' => 100,
            'medium' => 150,
            'large' => 200
        ];
        
        $maxWidth = $baseSizes[$size] ?? $baseSizes['medium'];
        
        // Don't make watermark larger than 1/4 of source width
        $maxWidth = min($maxWidth, $sourceWidth / 4);
        
        return [
            'width' => $maxWidth,
            'height' => intval($maxWidth * 0.6) // Maintain reasonable aspect ratio
        ];
    }
    
    /**
     * Calculate watermark position
     */
    private function calculateWatermarkPosition($sourceWidth, $sourceHeight, $watermarkWidth, $watermarkHeight) {
        $position = $this->watermarkSettings['watermark_position'] ?? 'top-center';
        $margin = intval($this->watermarkSettings['watermark_margin'] ?? 20);
        
        $x = 0;
        $y = 0;
        
        // Calculate X position
        switch ($position) {
            case 'top-left':
            case 'bottom-left':
                $x = $margin;
                break;
            case 'top-right':
            case 'bottom-right':
                $x = $sourceWidth - $watermarkWidth - $margin;
                break;
            case 'top-center':
            case 'bottom-center':
            default:
                $x = ($sourceWidth - $watermarkWidth) / 2;
                break;
        }
        
        // Calculate Y position
        switch ($position) {
            case 'top-left':
            case 'top-center':
            case 'top-right':
                $y = $margin;
                break;
            case 'bottom-left':
            case 'bottom-center':
            case 'bottom-right':
                $y = $sourceHeight - $watermarkHeight - $margin;
                break;
            default:
                $y = $margin;
                break;
        }
        
        return [
            'x' => max(0, intval($x)),
            'y' => max(0, intval($y))
        ];
    }
    
    /**
     * Copy and merge with alpha transparency support
     */
    private function imagecopymerge_alpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct) {
        // Create a temporary image for blending
        $tmp = imagecreatetruecolor($src_w, $src_h);
        
        // Copy source to temporary
        imagecopy($tmp, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h);
        
        // Copy watermark to temporary with alpha blending
        imagecopy($tmp, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h);
        
        // Copy blended result back to destination with opacity
        imagecopymerge($dst_im, $tmp, $dst_x, $dst_y, 0, 0, $src_w, $src_h, $pct);
        
        imagedestroy($tmp);
    }
    
    /**
     * Generate watermarked clip for download
     */
    public function generateWatermarkedClip($originalClipPath) {
        if (!$this->isWatermarkEnabled()) {
            return $originalClipPath;
        }
        
        try {
            // Create watermarked version in temp directory
            $pathInfo = pathinfo($originalClipPath);
            $tempDir = dirname(__DIR__) . '/temp/watermarked/';
            
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            
            $watermarkedPath = $tempDir . uniqid('clip_') . '.' . $pathInfo['extension'];
            
            return $this->applyWatermark($originalClipPath, $watermarkedPath);
            
        } catch (Exception $e) {
            error_log("Error generating watermarked clip: " . $e->getMessage());
            return $originalClipPath; // Return original on error
        }
    }
    
    /**
     * Clean up old watermarked temporary files
     */
    public function cleanupTempFiles($maxAge = 3600) { // 1 hour default
        $tempDir = dirname(__DIR__) . '/temp/watermarked/';
        
        if (!is_dir($tempDir)) {
            return;
        }
        
        $files = glob($tempDir . '*');
        $now = time();
        
        foreach ($files as $file) {
            if (is_file($file) && ($now - filemtime($file) > $maxAge)) {
                unlink($file);
            }
        }
    }
}
?>
