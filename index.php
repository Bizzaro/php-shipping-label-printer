<?php
require('lib/fpdf.php');

// Function to rotate image in place using ImageMagick convert command
function rotateImageInPlace($imagePath) {
    // Use ImageMagick convert command to rotate 90 degrees clockwise, overwriting original
    $command = "convert '$imagePath' -rotate 90 '$imagePath' 2>/dev/null";
    $result = shell_exec($command);
    
    // Return the same path since we overwrote the original
    return $imagePath;
}

// Image validation function
function validateImage($imagePath) {
    if (!file_exists($imagePath)) {
        return ['valid' => false, 'error' => 'File not found'];
    }
    
    $imageInfo = getimagesize($imagePath);
    if (!$imageInfo) {
        return ['valid' => false, 'error' => 'Invalid image format'];
    }
    
    $allowedTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF];
    if (!in_array($imageInfo[2], $allowedTypes)) {
        return ['valid' => false, 'error' => 'Unsupported image type'];
    }
    
    return ['valid' => true, 'info' => $imageInfo];
}

// Image processing function
function processImageForLabel($imagePath, $targetWidth, $targetHeight) {
    if (!file_exists($imagePath)) {
        return false;
    }
    
    // Get image dimensions
    $imageInfo = getimagesize($imagePath);
    if (!$imageInfo) {
        return false;
    }
    
    $sourceWidth = $imageInfo[0];
    $sourceHeight = $imageInfo[1];
    
    // Calculate aspect ratios for both orientations
    $sourceRatio = $sourceWidth / $sourceHeight;
    $targetRatio = $targetWidth / $targetHeight;
    
    // Check if rotated version fits better
    $rotatedRatio = $sourceHeight / $sourceWidth; // 90-degree rotation
    $rotatedTargetRatio = $targetHeight / $targetWidth;
    
    // Calculate scaling factors for both orientations
    $normalScale = min($targetWidth / $sourceWidth, $targetHeight / $sourceHeight);
    $rotatedScale = min($targetWidth / $sourceHeight, $targetHeight / $sourceWidth);
    
    // Choose orientation that gives better scaling (larger scale factor)
    $shouldRotate = $rotatedScale > $normalScale;
    
    if ($shouldRotate) {
        // Use rotated dimensions for scaling calculation
        $scale = $rotatedScale;
        $scaledWidth = $sourceHeight * $scale;
        $scaledHeight = $sourceWidth * $scale;
        
        // Rotate image in place
        $finalImagePath = rotateImageInPlace($imagePath);
    } else {
        // Use normal dimensions
        $scale = $normalScale;
        $scaledWidth = $sourceWidth * $scale;
        $scaledHeight = $sourceHeight * $scale;
        
        $finalImagePath = $imagePath;
    }
    
    // Calculate centering offsets
    $offsetX = ($targetWidth - $scaledWidth) / 2;
    $offsetY = ($targetHeight - $scaledHeight) / 2;
    
    return [
        'path' => $finalImagePath,
        'x' => $offsetX,
        'y' => $offsetY,
        'width' => $scaledWidth,
        'height' => $scaledHeight,
        'rotated' => $shouldRotate
    ];
}

// Define label dimensions
$label_width = 101.6; // mm (4 inches)
$label_height = 127; // mm (5 inches)
$columns = 2;
$rows = 2;
$page_width = 215.9; // A4 width in mm
$page_height = 279.4; // A4 height in mm
$left_margin = 4; // Top-left label: 3.5mm + 0.5mm shift = 4mm from left edge
$top_margin = 12; // Top labels: 12mm from top edge
$column_spacing = 4.5; // Space between left and right labels: 4.5mm
$row_spacing = 0; // No space between rows (vertical)
$bottom_margin = 12; // Bottom labels: 12mm from bottom edge

// Configuration: Base directory where images are stored
// Set to empty string to use script's directory, or set to absolute path like '/var/www/images'
// If relative path, it will be resolved relative to the script's directory
$image_base_directory = ''; // Default: use script's directory

// Function to resolve image path from base directory
function resolveImagePath($relativePath) {
    global $image_base_directory;
    
    // If base directory is empty, use script's directory
    if (empty($image_base_directory)) {
        $baseDir = __DIR__;
    } else {
        // If base directory is absolute, use it directly
        if (strpos($image_base_directory, '/') === 0 || 
            (PHP_OS_FAMILY === 'Windows' && preg_match('/^[A-Z]:/', $image_base_directory))) {
            $baseDir = $image_base_directory;
        } else {
            // Relative path: resolve relative to script's directory
            $baseDir = __DIR__ . '/' . $image_base_directory;
        }
    }
    
    // Normalize the base directory path (must exist)
    $baseDir = realpath($baseDir);
    if ($baseDir === false) {
        // Base directory doesn't exist, return false
        return false;
    }
    
    // Normalize the relative path manually (remove .. and .)
    // Split path into components
    $parts = explode('/', $relativePath);
    $normalized = [];
    
    foreach ($parts as $part) {
        if ($part === '' || $part === '.') {
            // Skip empty parts and current directory
            continue;
        } elseif ($part === '..') {
            // Go up one directory
            if (!empty($normalized)) {
                array_pop($normalized);
            } else {
                // Trying to go above base directory - security violation
                return false;
            }
        } else {
            $normalized[] = $part;
        }
    }
    
    // Reconstruct the normalized relative path
    $normalizedPath = implode('/', $normalized);
    
    // Combine base directory with normalized relative path
    $fullPath = $baseDir . '/' . $normalizedPath;
    
    // Security check: ensure the full path is within base directory
    // Use realpath to normalize, but if file doesn't exist, manually check
    $resolvedPath = realpath($fullPath);
    if ($resolvedPath !== false) {
        // File exists, use realpath result
        // Verify it's still within base directory (should always be, but double-check)
        if (strpos($resolvedPath, $baseDir) !== 0) {
            return false;
        }
        return $resolvedPath;
    } else {
        // File doesn't exist yet, but path is valid
        // Construct absolute path and verify it would be within base directory
        $absolutePath = $baseDir . '/' . $normalizedPath;
        // Normalize separators
        $absolutePath = str_replace('\\', '/', $absolutePath);
        $baseDirNormalized = str_replace('\\', '/', $baseDir);
        
        // Check that absolute path starts with base directory
        if (strpos($absolutePath, $baseDirNormalized) !== 0) {
            return false;
        }
        
        return $absolutePath;
    }
}

// Function to safely read and sanitize URL parameters for image paths
function getImageFromUrl($position) {
    if (!isset($_GET[$position])) {
        return null;
    }
    
    $value = trim($_GET[$position]);
    
    // Return null for empty strings
    if (empty($value)) {
        return null;
    }
    
    // Normalize path separators: convert backslashes to forward slashes
    $value = str_replace('\\', '/', $value);
    
    // Explicit absolute path prevention: reject paths starting with /
    if (strpos($value, '/') === 0) {
        return null;
    }
    
    // Basic sanitization: remove path traversal attempts and null bytes
    $value = str_replace(['../', '..\\', "\0"], '', $value);
    
    // Only allow alphanumeric, dots, hyphens, underscores, and forward slashes
    // This prevents directory traversal while allowing subdirectories
    if (!preg_match('/^[a-zA-Z0-9._\/-]+$/', $value)) {
        return null;
    }
    
    return $value;
}

// Define valid position keys
$validPositions = ['top-left', 'top-right', 'bottom-left', 'bottom-right'];

// Build images array exclusively from URL parameters
$images = [];

foreach ($validPositions as $position) {
    $urlImage = getImageFromUrl($position);
    if ($urlImage !== null) {
        // Resolve the full path from base directory
        $resolvedPath = resolveImagePath($urlImage);
        if ($resolvedPath !== false) {
            // Only add if path resolution succeeded (file exists and is within base directory)
            $images[$position] = $resolvedPath;
        }
        // If path resolution failed (invalid or outside base directory), skip it
        // This prevents access to files outside the configured base directory
    }
    // If no URL parameter provided, position is not set (will be filtered out)
}

// Filter out empty image entries
$images = array_filter($images, function($img) {
    return !empty($img);
});

$pdf = new FPDF('P', 'mm', 'letter');
$pdf->AddPage();
$pdf->SetMargins(0, 0, 0); // Set all margins to 0 to avoid FPDF defaults
$pdf->AddFont('Helvetica', '', 'helvetica.php');
$pdf->SetFont('Helvetica', '', 40);

// Position mapping for easy reference
$positionMap = [
    'top-left' => [0, 0],
    'top-right' => [0, 1],
    'bottom-left' => [1, 0],
    'bottom-right' => [1, 1]
];

for ($row = 0; $row < $rows; $row++) {
    for ($col = 0; $col < $columns; $col++) {
        $x = $left_margin + $col * ($label_width + $column_spacing);
        // Calculate Y position: top labels use top_margin, bottom labels use bottom_margin
        if ($row == 0) {
            $y = $top_margin; // Top row: 12mm from top
        } else {
            $y = $page_height - $bottom_margin - $label_height; // Bottom row: 12mm from bottom
        }
        
        // Find which position this corresponds to
        $currentPosition = null;
        foreach ($positionMap as $position => $coords) {
            if ($coords[0] == $row && $coords[1] == $col) {
                $currentPosition = $position;
                break;
            }
        }
        
        // Only render if we have an image for this position
        if ($currentPosition && isset($images[$currentPosition])) {
            $imagePath = $images[$currentPosition];
            $validation = validateImage($imagePath);
            
            if ($validation['valid']) {
                $imageData = processImageForLabel($imagePath, $label_width, $label_height);
                
                if ($imageData) {
                    // Render image with calculated positioning
                    $pdf->Image(
                        $imageData['path'],
                        $x + $imageData['x'],
                        $y + $imageData['y'],
                        $imageData['width'],
                        $imageData['height']
                    );
                } else {
                    // Image processing failed
                    $pdf->Rect($x, $y, $label_width, $label_height);
                    $pdf->SetXY($x + 2, $y + $label_height / 2);
                    $pdf->Cell($label_width - 4, 5, 'PROCESSING ERROR', 0, 0, 'C');
                }
            } else {
                // Image validation failed
                $pdf->Rect($x, $y, $label_width, $label_height);
                $pdf->SetXY($x + 2, $y + $label_height / 2);
                $pdf->Cell($label_width - 4, 5, $validation['error'], 0, 0, 'C');
            }
        }
        // If no image for this position, skip rendering (no border, no text)
    }
}

$pdf->Output();
?>