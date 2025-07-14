<?php

class FileUploadSecurity {
    
    // Allowed file types with their MIME types
    private $allowedTypes = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'txt' => 'text/plain',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png'
    ];
    
    // Maximum file size (10MB)
    private $maxFileSize = 10 * 1024 * 1024;
    
    // Suspicious patterns for malicious content
    private $suspiciousPatterns = [
        '/<script[^>]*>.*?<\/script>/i',
        '/<\?php/i',
        '/eval\s*\(/i',
        '/exec\s*\(/i',
        '/system\s*\(/i',
        '/shell_exec\s*\(/i',
        '/passthru\s*\(/i',
        '/file_get_contents\s*\(/i',
        '/fopen\s*\(/i',
        '/base64_decode\s*\(/i'
    ];
    
    public function __construct() {
        // Configure PHP settings for file uploads
        $this->configurePHPSettings();
    }
    
    /**
     * Configure PHP settings for secure file uploads
     */
    private function configurePHPSettings() {
        ini_set('upload_max_filesize', '10M');
        ini_set('post_max_size', '10M');
        ini_set('max_execution_time', 300);
        ini_set('max_input_time', 300);
        ini_set('memory_limit', '256M');
    }
    
    /**
     * Validate uploaded file
     */
    public function validateFile($file) {
        $errors = [];
        
        // Check if file was uploaded
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'ไม่สามารถอัปโหลดไฟล์ได้';
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            $errors[] = 'ขนาดไฟล์ใหญ่เกินไป (สูงสุด ' . $this->formatBytes($this->maxFileSize) . ')';
        }
        
        // Check file extension
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!array_key_exists($fileExtension, $this->allowedTypes)) {
            $allowedExtensions = array_keys($this->allowedTypes);
            $errors[] = 'ประเภทไฟล์ไม่ถูกต้อง (อนุญาตเฉพาะ: ' . implode(', ', $allowedExtensions) . ')';
        }
        
        // Validate MIME type
        if (isset($this->allowedTypes[$fileExtension])) {
            $expectedMimeType = $this->allowedTypes[$fileExtension];
            if (!$this->validateMimeType($file['tmp_name'], $expectedMimeType, $file['type'])) {
                $errors[] = 'ประเภทไฟล์ไม่ตรงกับเนื้อหา';
            }
        }
        
        // Scan for malicious content
        if ($this->scanForMaliciousContent($file['tmp_name'])) {
            $errors[] = 'ไฟล์มีเนื้อหาที่อาจเป็นอันตราย';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'fileInfo' => [
                'name' => $file['name'],
                'size' => $file['size'],
                'type' => $file['type'],
                'extension' => $fileExtension
            ]
        ];
    }
    
    /**
     * Validate MIME type of uploaded file
     */
    private function validateMimeType($filePath, $expectedMimeType, $uploadedMimeType) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);
        
        // Check if detected MIME type matches expected
        if ($detectedMimeType !== $expectedMimeType) {
            // Some files might have alternative MIME types
            $alternativeMimeTypes = $this->getAlternativeMimeTypes($expectedMimeType);
            if (!in_array($detectedMimeType, $alternativeMimeTypes)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get alternative MIME types for certain file types
     */
    private function getAlternativeMimeTypes($mimeType) {
        $alternatives = [
            'application/pdf' => ['application/pdf'],
            'text/plain' => ['text/plain', 'text/x-log'],
            'image/jpeg' => ['image/jpeg', 'image/jpg'],
            'image/png' => ['image/png', 'image/x-png'],
            'application/msword' => ['application/msword', 'application/vnd.ms-word'],
            'application/vnd.ms-excel' => ['application/vnd.ms-excel', 'application/excel']
        ];
        
        return $alternatives[$mimeType] ?? [$mimeType];
    }
    
    /**
     * Scan file for malicious content
     */
    private function scanForMaliciousContent($filePath) {
        $content = file_get_contents($filePath);
        
        foreach ($this->suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Generate safe file name
     */
    public function generateSafeFileName($originalName) {
        $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        
        // Remove dangerous characters
        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '', $baseName);
        
        // Ensure name isn't too long
        if (strlen($safeName) > 100) {
            $safeName = substr($safeName, 0, 100);
        }
        
        // Add unique prefix
        $uniqueName = uniqid() . '_' . $safeName . '.' . $fileExtension;
        
        return $uniqueName;
    }
    
    /**
     * Create upload directory structure
     */
    public function createUploadDirectory() {
        $uploadDir = "uploads/" . date('Y/m/');
        
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                throw new Exception('ไม่สามารถสร้างโฟลเดอร์สำหรับอัปโหลดไฟล์ได้');
            }
        }
        
        return $uploadDir;
    }
    
    /**
     * Compress image file
     */
    public function compressImage($sourcePath, $targetPath, $quality = 85) {
        $imageInfo = getimagesize($sourcePath);
        $mimeType = $imageInfo['mime'];
        
        switch ($mimeType) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $image = imagecreatefrompng($sourcePath);
                break;
            default:
                return false;
        }
        
        if (!$image) {
            return false;
        }
        
        // Get original dimensions
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Calculate new dimensions (max 1200px width)
        $maxWidth = 1200;
        if ($width > $maxWidth) {
            $ratio = $maxWidth / $width;
            $newWidth = $maxWidth;
            $newHeight = $height * $ratio;
            
            // Create new image with new dimensions
            $newImage = imagecreatetruecolor($newWidth, $newHeight);
            
            // Preserve transparency for PNG
            if ($mimeType === 'image/png') {
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
            }
            
            imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            $image = $newImage;
        }
        
        // Save compressed image
        switch ($mimeType) {
            case 'image/jpeg':
                return imagejpeg($image, $targetPath, $quality);
            case 'image/png':
                return imagepng($image, $targetPath, 9);
        }
        
        return false;
    }
    
    /**
     * Log file upload attempt
     */
    public function logUploadAttempt($userId, $fileName, $fileSize, $result, $errors = []) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => $userId,
            'file_name' => $fileName,
            'file_size' => $fileSize,
            'result' => $result,
            'errors' => $errors,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        $logFile = 'logs/file_uploads.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($logFile, json_encode($logEntry) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    /**
     * Get allowed file types for client-side validation
     */
    public function getAllowedTypes() {
        return array_keys($this->allowedTypes);
    }
    
    /**
     * Get maximum file size
     */
    public function getMaxFileSize() {
        return $this->maxFileSize;
    }
    
    /**
     * Get maximum file size in human readable format
     */
    public function getMaxFileSizeFormatted() {
        return $this->formatBytes($this->maxFileSize);
    }
}