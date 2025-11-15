# Shipping Label Printer

A PHP-based label printing system for shipping labels. This tool generates PDF labels with automatic image processing and positioning for 2x2 label layouts on letter-size paper.

## Screenshots
![Label Layout](generated.png)

### Sample Output
The application generates PDF labels with your configured images positioned in the 2x2 layout.

## Features

- **2x2 Label Layout**: Prints 4 labels per page (2 columns × 2 rows) on letter-size paper
- **Automatic Image Processing**: Intelligently rotates and scales images to fit label dimensions
- **Multi-Image Support**: Configure up to 4 different images for each label position
- **Smart Positioning**: Images are automatically centered and scaled to preserve aspect ratio
- **Image Validation**: Validates image files before processing
- **Error Handling**: Graceful error handling with visual feedback for invalid images
- **Dynamic Image Loading**: Specify images via URL parameters without editing code
- **Configurable Image Storage**: Set custom base directory for image files
- **Security Features**: Path traversal protection and input validation

## Label Specifications

- **Page Size**: Letter (215.9mm × 279.4mm)
- **Label Dimensions**: 4" × 5" (101.6mm × 127mm each)
- **Layout**: 2 columns × 2 rows = 4 labels total
- **Margins**: 4mm left, 12mm top/bottom
- **Column Spacing**: 4.5mm between columns

## Requirements

- PHP 7.0 or higher
- ImageMagick (for image rotation)
- FPDF library (included)

## Installation

### Ubuntu/Debian
```bash
# Install PHP CLI
sudo apt install php-cli

# Install ImageMagick
sudo apt install imagemagick

# Clone the repository
git clone <repository-url>
cd php-fba-label-printer
```

### Other Systems
Ensure you have PHP CLI and ImageMagick installed on your system.

## Usage

### Basic Usage
```bash
# Start the development server
php -S 127.0.0.1:8000

# Open in browser
# Navigate to http://127.0.0.1:8000
```

### Dynamic Image Loading via URL Parameters

You can specify images directly in the URL without editing code:

```bash
# Single image
http://127.0.0.1:8000/index.php?top-left=label.png

# Multiple images
http://127.0.0.1:8000/index.php?top-left=label.png&bottom-right=invoice.png

# All positions
http://127.0.0.1:8000/index.php?top-left=a.png&top-right=b.png&bottom-left=c.png&bottom-right=d.png
```

**Available positions:**
- `top-left` - Top-left label position
- `top-right` - Top-right label position
- `bottom-left` - Bottom-left label position
- `bottom-right` - Bottom-right label position

### Command Line Usage
```bash
# Generate PDF directly
php index.php > labels.pdf

# View the generated PDF
evince labels.pdf
```

### Configuration

#### Image Base Directory

Edit the `$image_base_directory` variable in `index.php` (line 110) to configure where images are stored:

```php
// Use script's directory (default)
$image_base_directory = '';

// Use absolute path
$image_base_directory = '/var/www/images';

// Use relative path (relative to script directory)
$image_base_directory = 'images';
```

**Note:** The base directory must exist. If it doesn't exist, images will not be resolved.

### Supported Image Formats
- JPEG (.jpg, .jpeg)
- PNG (.png)
- GIF (.gif)

## How It Works

1. **URL Parameter Processing**: Reads image paths from URL GET parameters (e.g., `?top-left=image.png`)
2. **Path Sanitization**: Validates and sanitizes input paths to prevent security issues
3. **Path Resolution**: Resolves image paths relative to the configured base directory
4. **Image Validation**: Checks if image files exist and are in supported formats
5. **Smart Processing**: Analyzes image dimensions and determines optimal orientation
6. **Automatic Rotation**: Rotates images 90° if it provides better fit
7. **Scaling & Centering**: Scales images to fit label dimensions while preserving aspect ratio
8. **PDF Generation**: Creates PDF with properly positioned images using FPDF

## File Structure

```
php-fba-label-printer/
├── index.php              # Main application file
├── lib/                   # FPDF library and fonts
│   ├── fpdf.php          # FPDF core library
│   ├── font/             # Font files
│   └── ...
├── sample.png            # Sample images
├── sample2.png
├── sample3.png
├── sample4.png
└── README.md
```

## Security

The application includes several security features to prevent unauthorized file access:

- **Path Traversal Protection**: Automatically sanitizes and blocks `../` patterns in URL parameters
- **Absolute Path Prevention**: Rejects paths starting with `/` to prevent access to system directories
- **Input Validation**: Only allows safe characters (alphanumeric, dots, hyphens, underscores, forward slashes)
- **Base Directory Restriction**: All image paths are resolved relative to the configured base directory
- **Path Normalization**: Converts backslashes to forward slashes and normalizes path components

**Security Notes:**
- Images can only be accessed from within the configured base directory
- Path traversal attempts (e.g., `../etc/passwd`) are automatically blocked
- Invalid or malicious paths are rejected before file system access

## Error Handling

The system provides visual feedback for various error conditions:
- **File not found**: Shows "File not found" message
- **Invalid format**: Shows "Invalid image format" message  
- **Unsupported type**: Shows "Unsupported image type" message
- **Processing error**: Shows "PROCESSING ERROR" message
- **Invalid paths**: Paths outside the base directory or with invalid characters are silently rejected

## Contributing

### Git Commands for Development

To temporarily ignore changes to a file during development:
```bash
git update-index --assume-unchanged <file>
```

To resume tracking changes:
```bash
git update-index --no-assume-unchanged <file>
```

## License

This project is open source. Please check the license file for details.

## Troubleshooting

### Common Issues

1. **ImageMagick not found**: Ensure ImageMagick is installed and accessible from command line
2. **Permission errors**: Check file permissions for image files
3. **Memory issues**: For large images, consider resizing before processing

### Debug Mode

To enable debug output, modify the error reporting in `index.php`:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```