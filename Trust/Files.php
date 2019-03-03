<?php
namespace Trust;
class Files {
  public static $latest_error=null;
  /**
   * To force file download to files with txt, pdf, jpeg extensions.
   * Warning: high security risk
   * @param string $filename The file to be downloaded
   * @param mixed $extensions The disallowed file extensions
   * @throws Exception
   * @return void This function will stop the code flow and force download of file
   */
  static function DownloadFile($filename, $disallow_exts = array()) {
    $hasil = new \stdClass();
    if (!file_exists($filename)) throw new \Exception ("File tidak ditemukan");
    if (count($disallow_exts) > 0) {
      $info = pathinfo($filename);
      if (in_array($info['extension'], $disallow_exts)) throw new Exception("File tidak dapat diakses");
    }
    header("Content-Disposition: attachment; filename=\"" . basename($filename) . "\"");
    header("Content-type: application/octet-stream");
    // or header("Content-Type: application/force-download");
    header("Content-Length: " . filesize($filename));
    header("Connection: close");
    readfile($filename);
    die();
  }

  /**
   * Scan files informations in a directory.
   * @param type $directory The directory to be scanned
   * @return arrayofobj a[filename][size/modTime/path]
   */
  static function GetDirFiles($directory, $isObj=false) { //recursive, 
    $files = array();
    $dh = opendir($directory);
    while ($filename = readdir($dh)) {
      if (($filename != ".") && ($filename != "..")) {
        $path = "$directory/$filename";
        if (is_dir($path)) {
          $files[$path] = Files::GetDirFiles($path, $isObj);
        } else {
          if ($isObj) {
            $o = new \stdClass();
            $o->size = round(filesize($path) / 1024);
            $o->modTime = filemtime($path);
            $o->filename = $filename;
            $o->path = $path;
            $files[]=$o;
          } else {
            $files[] = $path;
          }
        }
      }
    }
    return $files;
  }
  static function recurse_copy($src,$dst) { //http://stackoverflow.com/questions/2050859/copy-entire-contents-of-a-directory-to-another-using-php
    $dir = opendir($src);
    @mkdir($dst);
    while($file = readdir($dir)) { 
      if ($file != '.' && $file != '..' ) { 
        $srcpath = "$src/$file"; $dstpath = "$dst/$file";
        if (is_dir($srcpath)) Files::recurse_copy($srcpath,$dstpath);
        else copy($srcpath,$dstpath);
      }
    }
    closedir($dir);
  }
  static function recurse_delete($filefolder) {
    if (!file_exists($filefolder)) return;
    if (is_dir($filefolder)) {
      foreach(glob("{$filefolder}/*") as $file) {
        if(is_dir($file)) self::recurse_delete($file);
        else unlink($file);
      }
      rmdir($filefolder);
    } else unlink($filefolder);
  }

  static function Encrypt($pure_string, $encryption_key) {
    $iv_size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_ECB);
    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
    $encrypted_string = mcrypt_encrypt(MCRYPT_BLOWFISH, $encryption_key, utf8_encode($pure_string), MCRYPT_MODE_ECB, $iv);
    return $encrypted_string;
  }

  /**
   * Returns decrypted original string
   */
  static function Decrypt($encrypted_string, $encryption_key) {
    $iv_size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_ECB);
    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
    $decrypted_string = mcrypt_decrypt(MCRYPT_BLOWFISH, $encryption_key, $encrypted_string, MCRYPT_MODE_ECB, $iv);
    return $decrypted_string;
  }
  static function checkUpload($upload) {
    $errMsgs = [
        UPLOAD_ERR_INI_SIZE=>"File size too large",
        UPLOAD_ERR_FORM_SIZE=>"File size too large",
        UPLOAD_ERR_PARTIAL=>"Upload interrupted",
        UPLOAD_ERR_NO_FILE=>"File not found"
    ];
    if (!isset($upload)) return "Upload not found"; //Koding ini harus diedit. Parah. Ndak beguno
    $errorCode = $upload['error'];
    if ($errorCode != 0) return $errMsgs[$errorCode];
  }
  public static function checkUpload2($filesIndex) {
    $errMsgs = [
        UPLOAD_ERR_INI_SIZE=>"File size too large",
        UPLOAD_ERR_FORM_SIZE=>"File size too large",
        UPLOAD_ERR_PARTIAL=>"Upload interrupted",
        UPLOAD_ERR_NO_FILE=>"File not found"
    ];
    if (!isset($_FILES[$filesIndex])) return "Upload not found";
    $errorCode = $_FILES[$filesIndex]['error'];
    if ($errorCode != 0) { static::$latest_error = $errMsgs[$errorCode]; return false; }
    return true;
  }
  static function newName($path, $filename) {
    $res = "$path/$filename";
    if (!file_exists($res)) return $res;
    $fnameNoExt = pathinfo($filename,PATHINFO_FILENAME);
    $ext = pathinfo($filename, PATHINFO_EXTENSION);

    $i = 1;
    while(file_exists("$path/$fnameNoExt ($i).$ext")) $i++;
    return "$path/$fnameNoExt ($i).$ext";
  }

  static function getExtension($path) { return pathinfo($path, PATHINFO_EXTENSION); }
  static function getFilenameNoExt($path) { return pathinfo($path, PATHINFO_FILENAME); }

  //kfriend @ https://gist.github.com/liunian/9338301
  public static function human_filesize($size, $precision = 2) {
    static $units = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
    $step = 1024;
    $i = 0;
    while (($size / $step) > 0.9) {
        $size = $size / $step;
        $i++;
    }
    return round($size, $precision).$units[$i];
  }
  
}
