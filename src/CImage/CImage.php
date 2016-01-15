<?php
/**
 * Handeling Images.
 *
 */
class CImage {
    private $pathToImage;

    public function __construct($path, $src) {
        define('IMG_PATH', $path . DIRECTORY_SEPARATOR);
        define('CACHE_PATH', $path . '/../cache/');
        
        is_dir(IMG_PATH) or $this->errorMessage('The image dir is not a valid directory.');
        is_writable(CACHE_PATH) or $this->errorMessage('The cache dir is not a writable directory.');
        
        $this->pathToImage = realpath(IMG_PATH . $src);
        $basePath = realpath(IMG_PATH);
        
        substr_compare($basePath, $this->pathToImage, 0, strlen($basePath)) == 0 or $this->errorMessage('Security constraint: Source image is not directly below the directory IMG_PATH.');

    }
    
    
    /**
     * Display error message.
     *
     * @param string $message the error message to display.
     */
    private function errorMessage($message) {
      header("Status: 404 Not Found");
      die('img.php says 404 - ' . htmlentities($message));
    }

    /**
     * Display log message.
     *
     * @param string $message the log message to display.
     */
    private function verbose($message) {
      echo "<p>" . htmlentities($message) . "</p>";
    }

    /**
     * Output an image together with last modified header.
     *
     * @param string $file as path to the image.
     * @param boolean $verbose if verbose mode is on or off.
     */
    private function outputImage($file, $verbose) {
        $info = getimagesize($file);
        !empty($info) or $this->errorMessage("The file doesn't seem to be an image.");
        $mime   = $info['mime'];

        $lastModified = filemtime($file);
        $gmdate = gmdate("D, d M Y H:i:s", $lastModified);

        if($verbose) {
            $this->verbose("Memory peak: " . round(memory_get_peak_usage() /1024/1024) . "M");
            $this->verbose("Memory limit: " . ini_get('memory_limit'));
            $this->verbose("Time is {$gmdate} GMT.");
        }

        if(!$verbose) {
            header('Last-Modified: ' . $gmdate . ' GMT');
        }
        if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $lastModified){
            if($verbose) { $this->verbose("Would send header 304 Not Modified, but its verbose mode."); exit; }
            header('HTTP/1.0 304 Not Modified');
        } else {
            if($verbose) { $this->verbose("Would send header to deliver image with modified time: {$gmdate} GMT, but its verbose mode."); exit; }
            header('Content-type: ' . $mime);
            readfile($file);
        }
        exit;
    }

    /**
     * Sharpen image as http://php.net/manual/en/ref.image.php#56144
     * http://loriweb.pair.com/8udf-sharpen.html
     *
     * @param resource $image the image to apply this filter on.
     * @return resource $image as the processed image.
     */
    private function sharpenImage($image) {
      $matrix = array(
        array(-1,-1,-1,),
        array(-1,16,-1,),
        array(-1,-1,-1,)
      );
      $divisor = 8;
      $offset = 0;
      imageconvolution($image, $matrix, $divisor, $offset);
      return $image;
    }

    /**
     * Create new image and keep transparency
     *
     * @param resource $image the image to apply this filter on.
     * @return resource $image as the processed image.
     */
    private function createImageKeepTransparency($width, $height) {
        $img = imagecreatetruecolor($width, $height);
        imagealphablending($img, false);
        imagesavealpha($img, true);
        return $img;
    }
    
    
    public function getImage($src, $verbose, $saveAs, $quality, $ignoreCache, $newWidth, $newHeight, $cropToFit, $sharpen) {
        if($verbose) {
            $query = array();
            parse_str($_SERVER['QUERY_STRING'], $query);
            unset($query['verbose']);
            $url = '?' . http_build_query($query);


            echo <<<EOD
                <html lang='en'>
                <meta charset='UTF-8'/>
                <title>img.php verbose mode</title>
                <h1>Verbose mode</h1>
                <p><a href=$url><code>$url</code></a><br>
                <img src='{$url}' /></p>
EOD;
        }



        //
        // Get information on the image
        //
        $imgInfo = list($width, $height, $type, $attr) = getimagesize($this->pathToImage);
        !empty($imgInfo) or $this->errorMessage("The file doesn't seem to be an image.");
        $mime = $imgInfo['mime'];

        if($verbose) {
            $filesize = filesize($this->pathToImage);
            $this->verbose("Image file: {$this->pathToImage}");
            $this->verbose("Image information: " . print_r($imgInfo, true));
            $this->verbose("Image width x height (type): {$width} x {$height} ({$type}).");
            $this->verbose("Image file size: {$filesize} bytes.");
            $this->verbose("Image mime type: {$mime}.");
        }



        //
        // Calculate new width and height for the image
        //
        $aspectRatio = $width / $height;

        if($cropToFit && $newWidth && $newHeight) {
            $targetRatio = $newWidth / $newHeight;
            $cropWidth   = $targetRatio > $aspectRatio ? $width : round($height * $targetRatio);
            $cropHeight  = $targetRatio > $aspectRatio ? round($width  / $targetRatio) : $height;
            if($verbose) { $this->verbose("Crop to fit into box of {$newWidth}x{$newHeight}. Cropping dimensions: {$cropWidth}x{$cropHeight}."); }
        } else if($newWidth && !$newHeight) {
            $newHeight = round($newWidth / $aspectRatio);
            if($verbose) { $this->verbose("New width is known {$newWidth}, height is calculated to {$newHeight}."); }
        } else if(!$newWidth && $newHeight) {
            $newWidth = round($newHeight * $aspectRatio);
            if($verbose) { $this->verbose("New height is known {$newHeight}, width is calculated to {$newWidth}."); }
        } else if($newWidth && $newHeight) {
            $ratioWidth  = $width  / $newWidth;
            $ratioHeight = $height / $newHeight;
            $ratio = ($ratioWidth > $ratioHeight) ? $ratioWidth : $ratioHeight;
            $newWidth  = round($width  / $ratio);
            $newHeight = round($height / $ratio);
            if($verbose) { $this->verbose("New width & height is requested, keeping aspect ratio results in {$newWidth}x{$newHeight}."); }
        } else {
            $newWidth = $width;
            $newHeight = $height;
            if($verbose) { $this->verbose("Keeping original width & heigth."); }
        }



        //
        // Creating a filename for the cache
        //
        $parts          = pathinfo($this->pathToImage);
        $fileExtension  = $parts['extension'];
        $dirName        = preg_replace('/\//', '-', dirname($src));
        $saveAs         = is_null($saveAs) ? $fileExtension : $saveAs;
        $quality_       = is_null($quality) ? null : "_q{$quality}";
        $cropToFit_     = is_null($cropToFit) ? null : "_cf";
        $sharpen_       = is_null($sharpen) ? null : "_s";
        $cacheFileName = CACHE_PATH . "-{$dirName}-{$parts['filename']}_{$newWidth}_{$newHeight}{$quality_}{$cropToFit_}{$sharpen_}.{$saveAs}";
        $cacheFileName = preg_replace('/^a-zA-Z0-9\.-_/', '', $cacheFileName);

        if($verbose) { $this->verbose("Cache file is: {$cacheFileName}"); }



        //
        // Is there already a valid image in the cache directory, then use it and exit
        //
        $imageModifiedTime = filemtime($this->pathToImage);
        $cacheModifiedTime = is_file($cacheFileName) ? filemtime($cacheFileName) : null;

        // If cached image is valid, output it.
        if(!$ignoreCache && is_file($cacheFileName) && $imageModifiedTime < $cacheModifiedTime) {
            if($verbose) { 
                $this->verbose("Cache file is valid, output it."); 
            }
            $this->outputImage($cacheFileName, $verbose);
        }

        if($verbose) { $this->verbose("Cache is not valid, process image and create a cached version of it."); }



        //
        // Open up the original image from file
        //
        if($verbose) { $this->verbose("File extension is: {$fileExtension}"); }

        switch($fileExtension) {
            case 'jpg':
            case 'jpeg':
                $image = imagecreatefromjpeg($this->pathToImage);
                if($verbose) { $this->verbose("Opened the image as a JPEG image."); }
                break;

            case 'png':
                $image = imagecreatefrompng($this->pathToImage);
                if($verbose) { $this->verbose("Opened the image as a PNG image."); }
                break;
                
            case 'gif':
                $image = imagecreatefromgif($this->pathToImage);
                if($verbose) { $this->verbose("Opened the image as a GIF image."); }
                break;

            default: errorPage('No support for this file extension.');
        }



        //
        // Resize the image if needed
        //
        if($cropToFit) {
            if($verbose) { 
                $this->verbose("Resizing, crop to fit."); 
            }
            $cropX = round(($width - $cropWidth) / 2);
            $cropY = round(($height - $cropHeight) / 2);
            $imageResized = $this->createImageKeepTransparency($newWidth, $newHeight);
            imagecopyresampled($imageResized, $image, 0, 0, $cropX, $cropY, $newWidth, $newHeight, $cropWidth, $cropHeight);
            $image = $imageResized;
            $width = $newWidth;
            $height = $newHeight;
        } else if(!($newWidth == $width && $newHeight == $height)) {
            if($verbose) { $this->verbose("Resizing, new height and/or width."); }
            $imageResized = $this->createImageKeepTransparency($newWidth, $newHeight);
            imagecopyresampled($imageResized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            $image  = $imageResized;
            $width  = $newWidth;
            $height = $newHeight;
        }



        //
        // Apply filters and postprocessing of image
        //
        if($sharpen) {
          $image = $this->sharpenImage($image);
        }



        //
        // Save the image
        //
        switch($saveAs) {
            case 'jpeg':
            case 'jpg':
                if($verbose) { $this->verbose("Saving image as JPEG to cache using quality = {$quality}."); }
                imagejpeg($image, $cacheFileName, $quality);
            break;

            case 'png':
                if($verbose) { $this->verbose("Saving image as PNG to cache."); }
                // Turn off alpha blending and set alpha flag
                imagealphablending($image, false);
                imagesavealpha($image, true);
                imagepng($image, $cacheFileName);
            break;
            
            case 'gif':
                if($verbose) { $this->verbose("Saving image as GIF to cache."); }
                imagegif($image, $cacheFileName);
            break;
            
            default:
                $this->errorMessage('No support to save as this file extension.');
            break;
        }

        if($verbose) {
            clearstatcache();
            $cacheFilesize = filesize($cacheFileName);
            $this->verbose("File size of cached file: {$cacheFilesize} bytes.");
            $this->verbose("Cache file has a file size of " . round($cacheFilesize/$filesize*100) . "% of the original size.");
        }



        //
        // Output the resulting image
        //
        $this->outputImage($cacheFileName, $verbose);
    }
}

