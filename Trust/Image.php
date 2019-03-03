<?php
namespace Trust;
class Image {
  public static $latest_error = '';
  static function IsImage($tempFile) {
    // Get the size of the image: 0:Width, 1:Height, 2:Type
    $size = getimagesize($tempFile);
    if (!isset($size))return false;
    if (!in_array($size[2],[IMAGETYPE_GIF,IMAGETYPE_PNG,IMAGETYPE_JPEG])) return false;
    if (!$size[0] || !$size[1]) return false;
    return true;
  }


//fungsi: $SourcePath, $maxWidth, $maxHeight, $targetPath
  static function GenerateThumb($sourcePath, $maxWidth, $maxHeight, $targetPath) {
    list($srcWidth, $srcHeight, $srcType) = getimagesize($sourcePath);
    switch($srcType) {
      case IMAGETYPE_GIF:  $readFunction = "imagecreatefromgif"; $writeFunction="imagegif"; break;
      case IMAGETYPE_JPEG: $readFunction = "imagecreatefromjpeg"; $writeFunction="imagejpeg"; break;
      case IMAGETYPE_PNG:  $readFunction = "imagecreatefrompng"; $writeFunction="imagepng"; break;
    }
    $srcImage = $readFunction($sourcePath);
    if ($srcImage === false) return false;
    if ($srcWidth<$maxWidth && $srcHeight<$maxHeight) {
      $newWidth = $srcWidth;
      $newHeight = $srcHeight;
    } elseif ($srcWidth/$maxWidth >= $srcHeight/$maxHeight) { //size limiter: width
      $newWidth = (int) $maxWidth;
      $newHeight = (int) ($maxWidth/$srcWidth * $srcHeight);
    } else { //size limiter: height
      $newHeight = (int) $maxHeight;
      $newWidth = (int) ($maxHeight/$srcHeight * $srcWidth);
    }
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    if ($srcType == IMAGETYPE_PNG){
      $trans = imagecolorallocatealpha($newImage, 255, 255, 0, 127);
      imagefill($newImage,0,0, $trans);
      imagesavealpha($newImage, true);
    }
    imagecopyresampled($newImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $srcWidth, $srcHeight);
    if ($srcType == IMAGETYPE_GIF) imagegif($newImage, $targetPath); 
    elseif ($srcType == IMAGETYPE_JPEG) imagejpeg($newImage, $targetPath, 80);
    elseif ($srcType == IMAGETYPE_PNG) imagepng($newImage, $targetPath, 0);
    imagedestroy($srcImage);
    imagedestroy($newImage);
    return true;
  }

  static function CommonUploadErrors($key){
    $uploadErrors = [
      UPLOAD_ERR_INI_SIZE     => "File is larger than the specified amount set by the server",
      UPLOAD_ERR_FORM_SIZE    => "File is larger than the specified amount specified by browser",
      UPLOAD_ERR_PARTIAL      => "File could not be fully uploaded. Please try again later",
      UPLOAD_ERR_NO_FILE      => "File is not found",
      UPLOAD_ERR_NO_TMP_DIR   => "Can't write to disk, due to server configuration ( No tmp dir found )",
      UPLOAD_ERR_CANT_WRITE   => "Failed to write file to disk. Please check you file permissions",
      UPLOAD_ERR_EXTENSION    => "A PHP extension has halted this file upload process"
    ];
    return $uploadErrors[$key];
  }
  
  static function checkImageUpload($upload) {
    $err = Files::checkUpload($upload);
    if ($err) return $err;
    if (!Image::IsImage($upload['tmp_name'])) {
      unlink($upload['tmp_name']);
      return "Gambar yang diupload tidak dapat diproses.\nHanya tipe gif, jpg atau png yang diterima.";
    }
    return null;
  }
  static function checkImageUpload2($filesIndex) {
    if (!Files::checkUpload2($filesIndex)) { static::$latest_error = Files::$latest_error; return false; };
    if (!Image::IsImage($_FILES[$filesIndex]['tmp_name'])) {
      unlink($upload['tmp_name']);
      static::$latest_error = "Gambar yang diupload tidak dapat diproses.\nHanya tipe png, jpg atau gif yang diterima.";
      return false;
    }
    return true;
  }
}
