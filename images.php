<?php
/**
 * ImageService
 * https://github.com/andykirk/ImageService
 *
 * Handles image resizing as requested via query string.
 * Note: 'base file' means the actual full image (before the ?)
 * 'derived file' means anything that's been created from the base file.
 *
 * @author Andy Kirk <andy.kirk@npeu.ox.ac.uk>
 * @copyright Copyright (c) 2014
 * @version 0.1
 */
class ImageService {

	public $header      = 'HTTP/1.0 404 Not Found';
	public $output      = '';
    
    protected $dir_grp  = 'www-data';
    protected $dir_perm = 0775;
    
    protected $path;
    protected $pathinfo;
    public $cache_root;

	public function __construct()
	{
        $this->path       = str_replace('?' . $_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI']);
		$this->pathinfo   = pathinfo($this->path);
        $this->cache_root = $this->pathinfo['dirname'];
	}

	public function run()
	{
		
		$root      = $_SERVER['DOCUMENT_ROOT'];
		$cache_dir = $this->cache_root . DIRECTORY_SEPARATOR . $this->pathinfo['filename'];
		$file      = $root . $this->path;
        
		// This isn't really necessary as .htaccess already checked the base file exists, but 
        // included just in case:
		if (!file_exists($file)) {
			return;
		}
		$size   = (int) $_GET['s'];
        
        // Whether the image should be at least s or max s:
        $minmax = (isset($_GET['m']) && $_GET['m'] == '1')
                ? 'min'
                : '';
                
		$ext  = strtolower($this->pathinfo['extension']);
		$type = $ext;
		if ($ext == 'jpeg') {
			$ext = 'jpg';
		}
		if ($type == 'jpg') {
			$type = 'jpeg';
		}
        
		mkdir($root . $cache_dir, $this->dir_perm, true);
        // Create the folder to hold the cached images:
		if (!file_exists($root . $cache_dir)) {
			mkdir($root . $cache_dir, $this->dir_perm, true);
            #mkdir($root . $cache_dir);
            #chown($root . $cache_dir, $this->dir_grp);
            #chmod($root . $cache_dir, $this->dir_perm);
		}

		$base_lastmod = filemtime($file);
   
		$derived_name = md5($base_lastmod . $size . $minmax);
		$derived_file = $root . $cache_dir . DIRECTORY_SEPARATOR . $derived_name . '.' . $ext;
        
		if (file_exists($derived_file)) {
			$this->setResult($derived_file);
			return;
		}
		if (!$this->scaleImage($file, $derived_file, $size, $type, 100, $minmax)) {
		 	return;
		}

		$this->setResult($derived_file);
		return;
	}

	protected function scaleImage($src_file, $dest_file, $size, $type, $quality = 100, $minmax = '')
	{
		$src_data = getimagesize($src_file);
		$src_w    = $src_data[0];
		$src_h    = $src_data[1];
        if ($minmax == '') {
            if ($src_w > $src_h) {
                $dst_h = round($size * ($src_h / $src_w));
                $dst_w = $size;
            } else {
                $dst_w = round($size * ($src_w /  $src_h));
                $dst_h = $size;
            }
        } else {
            if ($src_w > $src_h) {
                $dst_h = $size;
                $dst_w = round($size * ($src_w / $src_h));
            } else {
                $dst_w = $size;
                $dst_h = round($size * ($src_h /  $src_w));
            }
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