# 2x2 Label Layout Implementation Plan

## Overview

This plan implements a 2x2 label layout (2 columns × 2 rows = 4 labels per page) for the PHP shipping label printer system. The current system generates a 3x10 layout (30 labels per page) with 66mm × 25mm labels. The new layout will use 4" × 5" (101.6mm × 127mm) labels and support image-based content instead of text-based content.

## Current State Analysis

The existing system (`index.php`) uses:
- **Grid Configuration**: 3 columns × 10 rows = 30 labels per page
- **Label Dimensions**: 66mm × 25mm per label  
- **Page Size**: Letter size (215.9mm × 279.4mm)
- **Content Type**: Text-based labels with "MOVED" text
- **Positioning**: Nested loops calculate X/Y coordinates with fixed spacing

### Key Discoveries:
- Current system uses FPDF library (v1.86) for PDF generation
- Layout is hardcoded in `index.php` with configuration variables
- Text rendering uses FPDF's `Cell()` method
- No image processing capabilities currently exist
- Font system supports multiple families but won't be needed for image-based labels

## Desired End State

After implementation, the system will:
- Generate 2×2 label layouts (4 labels per page) OR single labels as needed
- Use 4" × 5" (101.6mm × 127mm) label dimensions
- Support image-based content with aspect ratio preservation
- Maintain letter-size page format
- Handle multiple image formats (JPG, PNG, etc.)
- Center images within label boundaries
- Provide error handling for missing/invalid images
- **Support single-label printing to avoid wasting label paper**

### Success Criteria:
- PDF generates correctly with 2×2 layout when 4 images provided
- PDF generates correctly with single label when 1 image provided
- Images scale properly to fit 4"×5" labels without distortion
- Horizontal spacing between columns is adequate (10mm)
- No vertical spacing between rows
- Images are centered within label boundaries
- Error handling works for missing/invalid images
- **Single labels are positioned optimally on the page (top-left position)**

## What We're NOT Doing

- Modifying the FPDF library (`lib/fpdf.php`)
- Changing the font system (`lib/font/`)
- Altering the TextualNumber class (`lib/textualnumber.php`)
- Supporting multiple layout types simultaneously
- Adding image upload functionality
- Implementing dynamic configuration
- Changing page size from letter format

## Implementation Approach

The implementation will modify the existing `index.php` file to:
1. Update grid configuration (2×2 instead of 3×10)
2. Adjust label dimensions (4"×5" instead of 66mm×25mm)
3. Modify spacing calculations
4. Replace text rendering with image rendering
5. Add image processing logic for aspect ratio preservation and centering

## Phase 1: Grid Configuration and Layout Changes

### Overview
Update the basic grid configuration and label dimensions to support 2×2 layout with 4"×5" labels.

### Changes Required:

#### 1. Grid Configuration
**File**: `index.php`
**Changes**: Update grid dimensions and label sizes

```php
// Current configuration (lines 5-8)
$label_width = 66; // mm
$label_height = 25; // mm
$columns = 3;
$rows = 10;

// Updated configuration
$label_width = 101.6; // mm (4 inches)
$label_height = 127; // mm (5 inches)
$columns = 2;
$rows = 2;
```

#### 2. Spacing Adjustments
**File**: `index.php`
**Changes**: Update spacing for 2×2 layout

```php
// Current spacing (lines 13-14)
$column_spacing = 3.5; // Space between columns
$row_spacing = 0.4; // Space between rows

// Updated spacing
$column_spacing = 10; // Space between columns (horizontal)
$row_spacing = 0; // No space between rows (vertical)
```

### Success Criteria:

#### Automated Verification:
- [x] PHP syntax is valid: `php -l index.php`
- [x] No fatal errors when running: `php index.php > /dev/null 2>&1 && echo "Success"`
- [x] PDF file is generated: `php index.php > test.pdf && test -f test.pdf`

#### Manual Verification:
- [x] PDF opens correctly in PDF viewer
- [x] Layout shows 2×2 grid (4 labels total)
- [x] Labels are 4"×5" dimensions
- [x] Horizontal spacing between columns is visible
- [x] No vertical spacing between rows
- [x] Labels are positioned correctly on page

**Implementation Note**: After completing this phase and all automated verification passes, pause here for manual confirmation from the human that the manual testing was successful before proceeding to the next phase.

---

## Phase 2: Image Processing Implementation

### Overview
Replace text-based content with image-based content, including aspect ratio preservation and centering logic.

### Changes Required:

#### 1. Image Processing Function
**File**: `index.php`
**Changes**: Add image processing function before the main loop

```php
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
    
    // Calculate aspect ratios
    $sourceRatio = $sourceWidth / $sourceHeight;
    $targetRatio = $targetWidth / $targetHeight;
    
    // Determine scaling factor to fit without distortion
    if ($sourceRatio > $targetRatio) {
        // Source is wider - scale by height
        $scale = $targetHeight / $sourceHeight;
        $scaledWidth = $sourceWidth * $scale;
        $scaledHeight = $targetHeight;
    } else {
        // Source is taller - scale by width
        $scale = $targetWidth / $sourceWidth;
        $scaledWidth = $targetWidth;
        $scaledHeight = $sourceHeight * $scale;
    }
    
    // Calculate centering offsets
    $offsetX = ($targetWidth - $scaledWidth) / 2;
    $offsetY = ($targetHeight - $scaledHeight) / 2;
    
    return [
        'path' => $imagePath,
        'x' => $offsetX,
        'y' => $offsetY,
        'width' => $scaledWidth,
        'height' => $scaledHeight
    ];
}
```

#### 2. Image Array Configuration
**File**: `index.php`
**Changes**: Add image configuration after grid setup

```php
// Image configuration - supports 1-4 images with position control
$images = [
    'top-left' => 'image1.jpg',    // Top-left label (optional)
    'top-right' => 'image2.jpg',   // Top-right label (optional)
    'bottom-left' => 'image3.jpg', // Bottom-left label (optional)
    'bottom-right' => 'image4.jpg' // Bottom-right label (optional)
];

// Filter out empty image entries
$images = array_filter($images, function($img) {
    return !empty($img);
});
```

#### 3. Content Rendering Update
**File**: `index.php`
**Changes**: Replace text rendering with image rendering in the main loop

```php
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
        $y = $top_margin + $row * ($label_height + $row_spacing);
        
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
                // Fallback: draw border and error text
                $pdf->Rect($x, $y, $label_width, $label_height);
                $pdf->SetXY($x + 2, $y + $label_height / 2);
                $pdf->Cell($label_width - 4, 5, 'IMAGE ERROR', 0, 0, 'C');
            }
        }
        // If no image for this position, skip rendering (no border, no text)
    }
}
```

### Success Criteria:

#### Automated Verification:
- [x] PHP syntax is valid: `php -l index.php`
- [x] No fatal errors when running: `php index.php > /dev/null 2>&1 && echo "Success"`
- [x] PDF file is generated: `php index.php > test.pdf && test -f test.pdf`

#### Manual Verification:
- [x] Images are displayed in correct positions
- [x] Images maintain aspect ratio (no distortion)
- [x] Images are centered within label boundaries
- [x] Error handling works for missing images
- [x] Error handling works for invalid image formats
- [x] Image quality is acceptable for printing
- [x] **Single label printing works in any position (top-left, top-right, bottom-left, bottom-right)**
- [x] **Multiple label printing works in any combination of positions**
- [x] **Empty positions don't show borders or text when no image provided**

**Implementation Note**: After completing this phase and all automated verification passes, pause here for manual confirmation from the human that the manual testing was successful before proceeding to the next phase.

---

## Phase 3: Error Handling and Robustness

### Overview
Add comprehensive error handling for image processing, file validation, and edge cases.

### Changes Required:

#### 1. Image Validation Function
**File**: `index.php`
**Changes**: Add image validation before processing

```php
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
```

#### 2. Enhanced Error Handling
**File**: `index.php`
**Changes**: Update the main loop with better error handling

```php
for ($row = 0; $row < $rows; $row++) {
    for ($col = 0; $col < $columns; $col++) {
        $x = $left_margin + $col * ($label_width + $column_spacing);
        $y = $top_margin + $row * ($label_height + $row_spacing);
        
        // Calculate image index
        $imageIndex = ($row * $columns) + $col;
        
        if (isset($images[$imageIndex])) {
            $imagePath = $images[$imageIndex];
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
        } else {
            // No image specified for this position
            $pdf->Rect($x, $y, $label_width, $label_height);
            $pdf->SetXY($x + 2, $y + $label_height / 2);
            $pdf->Cell($label_width - 4, 5, 'NO IMAGE', 0, 0, 'C');
        }
    }
}
```

### Success Criteria:

#### Automated Verification:
- [x] PHP syntax is valid: `php -l index.php`
- [x] No fatal errors when running: `php index.php > /dev/null 2>&1 && echo "Success"`
- [x] PDF file is generated: `php index.php > test.pdf && test -f test.pdf`

#### Manual Verification:
- [ ] Error messages are displayed for missing images
- [ ] Error messages are displayed for invalid image formats
- [ ] Error messages are displayed for unsupported image types
- [ ] System handles empty image array gracefully
- [ ] System handles malformed image files gracefully
- [ ] Error messages are readable and informative

**Implementation Note**: After completing this phase and all automated verification passes, pause here for manual confirmation from the human that the manual testing was successful before proceeding to the next phase.

---

## Testing Strategy

### Unit Tests:
- Test image validation with various file types
- Test aspect ratio calculations with different image dimensions
- Test centering calculations for various image sizes
- Test error handling with missing/invalid files

### Integration Tests:
- Generate PDF with valid images
- Generate PDF with missing images
- Generate PDF with invalid image formats
- Verify 2×2 layout positioning

### Manual Testing Steps:
1. **Layout Verification**: Open generated PDF and verify 2×2 layout
2. **Image Quality**: Check that images render at appropriate resolution
3. **Aspect Ratio**: Test with images of different aspect ratios
4. **Error Handling**: Test with missing images and invalid formats
5. **Print Testing**: Print sample labels to verify physical output
6. **Edge Cases**: Test with very small and very large images
7. **Single Label Testing**: Test with only 1 image in any position (top-left, top-right, bottom-left, bottom-right)
8. **Multiple Label Testing**: Test with 2, 3, and 4 images in various combinations
9. **Paper Conservation**: Verify that single labels don't waste other label positions
10. **Position Control**: Test that labels render only in specified positions

## Performance Considerations

- Image processing occurs for each label (1-4 times per page depending on images provided)
- No image caching implemented - images are processed on each run
- Memory usage increases with image size
- Consider adding image size limits for very large images
- **Single label printing is more efficient (only 1 image processed)**

## Migration Notes

- No database or persistent data to migrate
- Existing 3×10 layout functionality will be replaced
- Image files must be provided in the specified directory
- No backward compatibility with text-based labels

## References

- Original research: `thoughts/shared/research/2025-01-12-2x2-label-layout-modification.md`
- Current implementation: `index.php:1-33`
- FPDF library: `lib/fpdf.php:1-1935`
- Sample PDF: `R022_SI-LABEL-LS-0405.pdf`
