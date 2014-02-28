<?php
/**
 * ImageService
 *
 * Handles image resizing as requested via query string.
 * Note: 'base file' means the actual full image (before the ?)
 * 'derived file' means anything that's been created from the base file.
 *
 * @author Andy Kirk <andy.kirk@npeu.ox.ac.uk
 * @copyright Copyright (c) 2014
 * @version 0.1
 */
class ImageService {

    public $header = 'HTTP/1.0 404 Not Found';
    public $output = '';

    public function __construct()
    {
    }

    public function run()
    {
        $path      = str_replace('?' . $_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI']);
        $pathinfo  = pathinfo($path);
        $root      = $_SERVER['DOCUMENT_ROOT'];
        $cache_dir = $pathinfo['dirname'] . DIRECTORY_SEPARATOR . $pathinfo['filename'];
        $file      = $root . $path;

        // This isn't really necessary as htaccess already checked the base file exists, but 
        // included just in case:
        if (!file_exists($file)) {
            return;
        }
        $size = (int) $_GET['s'];
        $ext  = $pathinfo['extension'];
        $type = $ext;
        if ($ext == 'jpeg') {
        if ($ext == 'jpeg') {
            $ext = 'jpg';
        }
        if ($type == 'jpg') {
            $type = 'jpeg';
        }

        // Create the folder to hold the cached images:
        if (!file_exists($root . $cache_dir)) {
            mkdir($root . $cache_dir);
        }

        $base_lastmod = filemtime($file);
        $derived_name = md5($base_lastmod . $size);
        $derived_file = $root . $cache_dir . DIRECTORY_SEPARATOR . $derived_name . '.' . $ext;
        if (file_exists($derived_file)) {
            $this->setResult($derived_file);
            return;
        }

        if (!$this->scaleImage($file, $derived_file, $size, $type)) {
             return;
        }

        $this->setResult($derived_file);
        return;
    }

    protected function scaleImage($src_file, $dest_file, $size, $type, $quality = 100)
    {
        $src_data = getimagesize($src_file);
        $src_w    = $src_data[0];
        $src_h    = $src_data[1];

        if ($src_w > $src_h) {
            $dst_h = round($size * ($src_h / $src_w));
            $dst_w = $size;
        } else {
            $dst_w = round($size * ($src_w /  $src_h));
            $dst_h = $size;
        }

        $new_img = imagecreatetruecolor($dst_w, $dst_h);
        $f       = 'imagecreatefrom' . $type;
        $src_img = $f($src_file);
        
        
        
        Imagefill($new_img, 0, 0, imagecolorallocate($new_img, 255, 255, 255));
        
        
        imagecopyresampled($new_img, $src_img, 0, 0, 0, 0, $dst_w, $dst_h, $src_w, $src_h);

        switch ($type) {
            case 'jpeg':
                $r = imagejpeg($new_img, $dest_file, $quality);
                break;
            case 'png':
                $r = imagepng($new_img, $dest_file);
                break;
            case 'gif':
                $r = imagegif($new_img, $dest_file);
                break;
        } // switch
        if (!$r) {
             return false;
        }
        chmod($dest_file, 0777);
        return true;
    }

    protected function setImageContent($file)
    {
        $this->output = file_get_contents($file);
    }

    protected function setImageHeader($mime)
    {
        $this->header = 'Content-type: ' . $mime;
    }

    protected function setResult($file)
    {
        $data = getimagesize($file);
        $this->setImageHeader($data['mime']);
        $this->setImageContent($file);
        return;
    }
}

$img_service = new ImageService;
$img_service->run();
header($img_service->header);
echo $img_service->output;
?>