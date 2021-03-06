<?php
 class CGallery
 {

   // Get incoming parameters
   private $path;
   private $pathComplete;
   private $pathToGallery;
   private $basePath;

  function __construct() {

  }

  function errorMessage($text) {
   header("Status: 404 Not Found");
   die('img.php says 404 - ' . htmlentities($text));
  }

  function readAllItemsInDir($path, $validImages = array('png', 'jpg', 'jpeg')) {
    $files = glob($path . '/*');
    $gallery = "<ul class='gallery'>\n";
    $len = strlen(GALLERY_PATH);

    foreach($files as $file) {
      $parts = pathinfo($file);
      $href  = str_replace('\\', '/', substr($file, $len + 1));

      // Is this an image or a directory
      if(is_file($file) && in_array($parts['extension'], $validImages)) {
        $item    = "<img src='img.php?src="
          . GALLERY_BASEURL
          . $href
          . "&amp;width=128&amp;height=128&amp;crop-to-fit' alt=''/>";
        $caption = basename($file);
      }
      elseif(is_dir($file)) {
        $item    = "<img src='img/folder.png' alt=''/>";
        $caption = basename($file) . '/';
      }
      else {
        continue;
      }

      // Avoid to long captions breaking layout
      $fullCaption = $caption;
      if(strlen($caption) > 18) {
        $caption = substr($caption, 0, 10) . '…' . substr($caption, -5);
      }

      $gallery .= "<li><a href='?path={$href}' title='{$fullCaption}'><figure class='figure overview'>{$item}<figcaption>{$caption}</figcaption></figure></a></li>\n";
    }
    $gallery .= "</ul>\n";

    return $gallery;
  }

  function readItem($path, $validImages = array('png', 'jpg', 'jpeg')) {
    $parts = pathinfo($path);
    if(!(is_file($path) && in_array($parts['extension'], $validImages))) {
      return "<p>This is not a valid image for this gallery.";
    }

    // Get info on image
    $imgInfo = list($width, $height, $type, $attr) = getimagesize($path);
    $mime = $imgInfo['mime'];
    $gmdate = gmdate("D, d M Y H:i:s", filemtime($path));
    $filesize = round(filesize($path) / 1024);

    // Get constraints to display original image
    $displayWidth  = $width > 800 ? "&amp;width=800" : null;
    $displayHeight = $height > 600 ? "&amp;height=600" : null;

    // Display details on image
    $len = strlen(GALLERY_PATH);
    $href = GALLERY_BASEURL . str_replace('\\', '/', substr($path, $len + 1));
    $item = <<<EOD
    <p><img src='img.php?src={$href}{$displayWidth}{$displayHeight}' alt=''/></p>
    <p>Original image dimensions are {$width}x{$height} pixels. <a href='img.php?src={$href}'>View original image</a>.</p>
    <p>File size is {$filesize}KBytes.</p>
    <p>Image has mimetype: {$mime}.</p>
    <p>Image was last modified: {$gmdate} GMT.</p>
EOD;
    return $item;
  }

  function createBreadcrumb($path) {
    $parts = explode('/', trim(substr($path, strlen(GALLERY_PATH) + 1), '/'));
    $breadcrumb = "<ul class='breadcrumb'>\n<li><a href='?'>Hem</a> »</li>\n";

    if(!empty($parts[0])) {
      $combine = null;
      foreach($parts as $part) {
        $combine .= ($combine ? '/' : null) . $part;
        $breadcrumb .= "<li><a href='?path={$combine}'>$part</a> » </li>\n";
      }
    }

    $breadcrumb .= "</ul>\n";
    return $breadcrumb;
  }

  function getArguments() {

    // Get incoming parameters
    $this->path = isset($_GET['path']) ? $_GET['path'] : null;
    $this->pathComplete = GALLERY_PATH . DIRECTORY_SEPARATOR . $this->path;

    $this->pathToGallery = realpath($this->pathComplete);
    $this->basePath      = realpath(GALLERY_PATH);
    $this->validateArguments();
  }

  function validateArguments() {
    // Validate incoming arguments
    ($this->pathToGallery !== false) or $this->errorMessage("The path to the gallery image seems to be a non existing path.");
    ($this->basePath !== false) or $this->errorMessage("The basepath to the gallery, GALLERY_PATH, seems to be a non existing path.");
    is_dir(GALLERY_PATH) or $this->errorMessage('The gallery dir "' . GALLERY_PATH . '" is not a valid directory.');
    substr_compare($this->basePath, $this->pathToGallery, 0, strlen($this->basePath)) == 0 or $this->errorMessage("Security constraint: Source gallery is not directly below the directory GALLERY_PATH.\n" . $this->basePath . "\n" . $this->pathToGallery);
  }

  function readDirectory() {
    // Read and present images in the current directory
    $gallery = null;
    if(is_dir($this->pathToGallery)) {
      $gallery = $this->readAllItemsInDir($this->pathToGallery);
    }
    else if(is_file($this->pathToGallery)) {
      $gallery = $this->readItem($this->pathToGallery);
    }
    return $gallery;
  }

  function createGallery() {
    $this->getArguments();
    $this->validateArguments();
    $gallery = array('gallery' => $this->readDirectory(), 'breadcrumb' => $this->createBreadcrumb($this->pathToGallery));
    return $gallery;
  }

}
