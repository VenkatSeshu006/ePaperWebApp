<?php
/**
 * Image Processing Utility Class
 */

class ImageProcessor {
    
    /**
     * Create thumbnail from image
     */
    public static function createThumbnail($sourcePath, $destPath, $width = THUMBNAIL_WIDTH, $height = THUMBNAIL_HEIGHT) {
        try {
            $imageInfo = getimagesize($sourcePath);
            if (!$imageInfo) {
                throw new Exception("Invalid image file");
            }
            
            $sourceWidth = $imageInfo[0];
            $sourceHeight = $imageInfo[1];
            $mimeType = $imageInfo['mime'];
            
            // Create source image resource
            switch ($mimeType) {
                case 'image/jpeg':
                    $sourceImage = imagecreatefromjpeg($sourcePath);
                    break;
                case 'image/png':
                    $sourceImage = imagecreatefrompng($sourcePath);
                    break;
                case 'image/gif':
                    $sourceImage = imagecreatefromgif($sourcePath);
                    break;
                default:
                    throw new Exception("Unsupported image type");
            }
            
            if (!$sourceImage) {
                throw new Exception("Failed to create image resource");
            }
            
            // Calculate thumbnail dimensions
            $aspectRatio = $sourceWidth / $sourceHeight;
            
            if ($width / $height > $aspectRatio) {
                $newWidth = $height * $aspectRatio;
                $newHeight = $height;
            } else {
                $newWidth = $width;
                $newHeight = $width / $aspectRatio;
            }
            
            // Create thumbnail
            $thumbnail = imagecreatetruecolor($newWidth, $newHeight);
            
            // Preserve transparency for PNG and GIF
            if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
                imagealphablending($thumbnail, false);
                imagesavealpha($thumbnail, true);
                $transparent = imagecolorallocatealpha($thumbnail, 0, 0, 0, 127);
                imagefill($thumbnail, 0, 0, $transparent);
            }
            
            // Resize image
            imagecopyresampled($thumbnail, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $sourceWidth, $sourceHeight);
            
            // Create directory if it doesn't exist
            $destDir = dirname($destPath);
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }
            
            // Save thumbnail
            $result = imagejpeg($thumbnail, $destPath, IMAGE_QUALITY);
            
            // Clean up
            imagedestroy($sourceImage);
            imagedestroy($thumbnail);
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Thumbnail creation failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Resize image
     */
    public static function resizeImage($sourcePath, $destPath, $maxWidth = MAX_IMAGE_WIDTH, $maxHeight = MAX_IMAGE_HEIGHT) {
        try {
            $imageInfo = getimagesize($sourcePath);
            if (!$imageInfo) {
                return false;
            }
            
            $sourceWidth = $imageInfo[0];
            $sourceHeight = $imageInfo[1];
            
            // Don't resize if image is already smaller
            if ($sourceWidth <= $maxWidth && $sourceHeight <= $maxHeight) {
                return copy($sourcePath, $destPath);
            }
            
            // Calculate new dimensions
            $aspectRatio = $sourceWidth / $sourceHeight;
            
            if ($maxWidth / $maxHeight > $aspectRatio) {
                $newWidth = $maxHeight * $aspectRatio;
                $newHeight = $maxHeight;
            } else {
                $newWidth = $maxWidth;
                $newHeight = $maxWidth / $aspectRatio;
            }
            
            return self::createThumbnail($sourcePath, $destPath, $newWidth, $newHeight);
            
        } catch (Exception $e) {
            error_log("Image resize failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get image dimensions
     */
    public static function getDimensions($imagePath) {
        $imageInfo = getimagesize($imagePath);
        if (!$imageInfo) {
            return false;
        }
        
        return [
            'width' => $imageInfo[0],
            'height' => $imageInfo[1],
            'mime' => $imageInfo['mime']
        ];
    }
    
    /**
     * Validate image file
     */
    public static function validateImage($filePath) {
        if (!file_exists($filePath)) {
            return false;
        }
        
        $imageInfo = getimagesize($filePath);
        if (!$imageInfo) {
            return false;
        }
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        return in_array($imageInfo['mime'], $allowedTypes);
    }
}
?>
