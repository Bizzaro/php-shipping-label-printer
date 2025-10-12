---
date: 2025-01-12T10:21:48-04:00
researcher: AI Assistant
git_commit: 1f4b567
branch: master
repository: php-shipping-label-printer
topic: "How to modify the system to support a 2x2 layout like the sample PDF"
tags: [research, codebase, layout, pdf-generation, fpdf]
status: complete
last_updated: 2025-01-12
last_updated_by: AI Assistant
---

# Research: How to modify the system to support a 2x2 layout like the sample PDF

**Date**: 2025-01-12T10:21:48-04:00
**Researcher**: AI Assistant
**Git Commit**: 1f4b567
**Branch**: master
**Repository**: php-shipping-label-printer

## Research Question
How would I modify this PHP shipping label printer to support a 2x2 layout like the sample PDF `R022_SI-LABEL-LS-0405.pdf`?

## Summary
The current system generates a 3x10 label layout (3 columns, 10 rows) on letter-size paper using FPDF. To support a 2x2 layout, the system would need modifications to the grid configuration, label dimensions, and positioning calculations in `index.php`. The core FPDF library and font system can remain unchanged.

## Detailed Findings

### Current System Architecture
The system consists of several key components:

#### Main Entry Point (`index.php`)
- **Current Layout**: 3 columns × 10 rows = 30 labels per page
- **Label Dimensions**: 66mm × 25mm per label
- **Page Size**: Letter size (215.9mm × 279.4mm)
- **Grid Configuration**: 
  - `$columns = 3`
  - `$rows = 10`
  - `$label_width = 66` (mm)
  - `$label_height = 25` (mm)

#### Layout Calculation Logic
The positioning is calculated using nested loops:
```php
for ($row = 0; $row < $rows; $row++) {
    for ($col = 0; $col < $columns; $col++) {
        $x = $left_margin + $col * ($label_width + $column_spacing);
        $y = $top_margin + $row * ($label_height + $row_spacing);
    }
}
```

#### FPDF Library (`lib/fpdf.php`)
- **Version**: 1.86 (2023-06-25)
- **Purpose**: Core PDF generation functionality
- **Features**: Font management, page layout, positioning, text rendering
- **No modifications needed**: The library supports any grid layout

#### Font System (`lib/font/`)
- **Available Fonts**: Helvetica, Courier, Times, Symbol, ZapfDingbats
- **Font Files**: PHP-based font definitions with character width mappings
- **No modifications needed**: Font system is layout-agnostic

#### Text Processing (`lib/textualnumber.php`)
- **Purpose**: Converts numbers to textual representation
- **Usage**: Likely used for generating label content
- **No modifications needed**: Content generation is independent of layout

### 2x2 Layout Requirements

Based on the sample PDF and web research, a 2x2 layout would require:

#### Grid Configuration Changes
- **New Layout**: 2 columns × 2 rows = 4 labels per page
- **Label Dimensions**: 4" × 5" (101.6mm × 127mm) labels
- **Page Utilization**: More efficient use of page real estate

#### Positioning Calculations
The current positioning logic would need adjustment:
- **Column spacing**: Increased for larger labels
- **Row spacing**: Adjusted for 2-row layout
- **Margins**: Potentially adjusted for optimal label placement

### Specific Modification Points

#### 1. Grid Configuration (`index.php:7-8`)
```php
// Current
$columns = 3;
$rows = 10;

// Modified for 2x2
$columns = 2;
$rows = 2;
```

#### 2. Label Dimensions (`index.php:5-6`)
```php
// Current
$label_width = 66; // mm
$label_height = 25; // mm

// Modified for 2x2 (4" x 5" labels)
$label_width = 101.6; // mm (4 inches)
$label_height = 127; // mm (5 inches)
```

#### 3. Spacing Adjustments (`index.php:13-14`)
```php
// Current
$column_spacing = 3.5; // Space between columns
$row_spacing = 0.4; // Space between rows

// Modified for 2x2 (no vertical separation, horizontal separation only)
$column_spacing = 10; // Space between columns (horizontal)
$row_spacing = 0; // No space between rows (vertical)
```

#### 4. Content Rendering (Major Change Required)
The content rendering needs to be completely changed from text to images:
```php
// Current (text-based)
$pdf->SetXY($x + 2, $y + $label_height / 2);
$pdf->Cell($label_width - 4, 5, 'MOVED', 0, 0, 'C');

// Modified for image-based labels
$pdf->Image($image_path, $x, $y, $label_width, $label_height, 'JPG');
```

#### 5. Image Processing Requirements
New functionality needed for image handling:
- **Image Input**: Accept image files for each label
- **Aspect Ratio Preservation**: Scale images to fit 4"×5" without distortion
- **Image Positioning**: Center images within label boundaries
- **Image Format Support**: Handle common formats (JPG, PNG, etc.)

## Code References
- `index.php:5-8` - Grid configuration variables
- `index.php:21-30` - Label positioning and content generation loop
- `index.php:23-24` - X/Y coordinate calculations
- `index.php:26-28` - Content positioning and rendering
- `lib/fpdf.php:1-1935` - Core PDF generation library
- `lib/textualnumber.php:1-125` - Text processing utilities

## Architecture Documentation

### Current Layout System
The system uses a simple grid-based approach:
1. **Grid Definition**: Columns and rows are defined as constants
2. **Position Calculation**: Nested loops calculate X/Y coordinates
3. **Content Rendering**: FPDF methods position and render text
4. **Page Management**: Single page with all labels

### Layout Flexibility
The current architecture supports layout changes through:
- **Configuration Variables**: Easy modification of grid dimensions
- **Modular Design**: Layout logic separated from content generation
- **FPDF Integration**: Leverages existing PDF generation capabilities

### Content Management
- **Text Rendering**: Uses FPDF's Cell() method for text positioning
- **Font Support**: Multiple font families available
- **Content Processing**: TextualNumber class for number-to-text conversion

## Historical Context (from thoughts/)
No historical context found in thoughts/ directory.

## Related Research
No related research documents found in thoughts/shared/research/.

## Open Questions
1. **Image Input Method**: How will images be provided for each label? (File upload, directory scan, etc.)
2. **Image Aspect Ratio Handling**: What happens when source images don't match 4"×5" aspect ratio?
3. **Image Quality**: What resolution/quality settings are needed for optimal printing?
4. **Page Size**: Should the page size remain letter-size or be adjusted?
5. **Printing Considerations**: Are there specific printer requirements for the 2x2 layout?

## Implementation Recommendations

### Immediate Changes Required
1. **Update Grid Configuration**: Change columns and rows to 2x2
2. **Adjust Label Dimensions**: Set to 4"×5" (101.6mm × 127mm)
3. **Modify Spacing**: Set row spacing to 0, adjust column spacing
4. **Replace Text with Images**: Implement image rendering using FPDF's Image() method
5. **Add Image Processing**: Handle aspect ratio preservation and centering
6. **Test Layout**: Verify label positioning and image rendering

### Image Processing Implementation
1. **Aspect Ratio Calculation**: Calculate source image dimensions and target dimensions
2. **Scaling Logic**: Determine if image should be scaled by width or height to fit without distortion
3. **Centering Logic**: Calculate X/Y offsets to center the scaled image within the 4"×5" label
4. **Image Format Support**: Handle JPG, PNG, and other common formats
5. **Error Handling**: Handle missing images, invalid formats, and processing errors

### Optional Enhancements
1. **Dynamic Configuration**: Make layout configurable via parameters
2. **Image Input Methods**: Support file upload, directory scanning, or API input
3. **Layout Validation**: Add checks for label dimensions vs page size
4. **Multiple Layouts**: Support both 3x10 and 2x2 layouts

### Testing Considerations
1. **Print Testing**: Verify labels print correctly on target hardware
2. **Image Testing**: Test various image sizes and aspect ratios
3. **Spacing Testing**: Confirm adequate horizontal spacing, no vertical spacing
4. **Page Layout Testing**: Verify optimal use of page space
5. **Image Quality Testing**: Ensure images render at appropriate resolution
6. **Error Handling Testing**: Test with missing images and invalid formats
