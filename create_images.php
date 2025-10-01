<!-- Placeholder image for products -->
<!-- In a real application, you would upload actual product images -->
<!-- For demo purposes, we'll use placeholder images -->

<?php
// Create a simple placeholder image using PHP GD
function createPlaceholderImage($filename, $text) {
    $width = 300;
    $height = 300;
    
    $image = imagecreatetruecolor($width, $height);
    
    // Colors
    $bg_color = imagecolorallocate($image, 233, 236, 239);
    $text_color = imagecolorallocate($image, 108, 117, 125);
    $border_color = imagecolorallocate($image, 206, 212, 218);
    
    // Fill background
    imagefill($image, 0, 0, $bg_color);
    
    // Add border
    imagerectangle($image, 0, 0, $width-1, $height-1, $border_color);
    
    // Add text
    $font = 5; // Built-in font
    $text_x = ($width - strlen($text) * 9) / 2;
    $text_y = $height / 2;
    imagestring($image, $font, $text_x, $text_y - 10, $text, $text_color);
    
    // Save image
    imagepng($image, $filename);
    imagedestroy($image);
}

// Create placeholder images if they don't exist
$placeholders = [
    'smartphone.jpg' => 'Smartphone',
    'headphones.jpg' => 'Headphones',
    'tshirt.jpg' => 'T-Shirt',
    'jeans.jpg' => 'Jeans',
    'coffeemaker.jpg' => 'Coffee Maker',
    'runningshoes.jpg' => 'Running Shoes',
    'phpbook.jpg' => 'PHP Book',
    'placeholder.jpg' => 'Product'
];

foreach ($placeholders as $filename => $text) {
    $filepath = "assets/images/$filename";
    if (!file_exists($filepath)) {
        createPlaceholderImage($filepath, $text);
    }
}
?>
