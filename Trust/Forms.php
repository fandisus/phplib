<?php
namespace Trust;
class Forms {
  public static function getPostObject($name) {
    if (!isset($_POST[$name])) JSONResponse::Error("Object $name not found");
    $o = json_decode(json_encode($_POST[$name]));
    if ($o == null) JSONResponse::Error("Failed to post data");
    return $o;
  }
  public static function isInt($var) {
    return filter_var($var, FILTER_VALIDATE_INT);
  }
  public static function validateEmail($email) {
    return filter_var($email,FILTER_VALIDATE_EMAIL);
  }
  public static function validateDate($date) {
    //https://stackoverflow.com/questions/12030810/php-date-validation
    $ymd = explode('-', $date);
    if (count($ymd) < 3) return false;
    if (!checkdate($ymd[1], $ymd[2], $ymd[0])) return false;
    return true;
  }
//  public static function DateHasPassed($date) { //Syarat: $date dianggap sudah valid.
//    $D1 = new \DateTime($date);
//    $D2 = new \DateTime();
//    //Unfinished, to be finished later.
//  }
//  public static function DateIsFutureOrNow($date) {
//    
//  }
  public static function validateTime($time) {
    //https://stackoverflow.com/questions/3964972/validate-this-format-hhmm
    return preg_match("/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/", $time);
  }
  public static function validatePass($pwd) {
    $errors = [];
    if (strlen($pwd) < 7) $errors[] = "Password terlalu pendek!";
    //if (!preg_match("#[0-9]+#", $pwd)) $errors[] = "Password must include at least one number!";
    if (!preg_match("#[a-zA-Z]+#", $pwd)) $errors[] = "Password minimal harus punya satu huruf!";
    if(!preg_match("#[A-Z]+#", $pwd) ) $error[] = "Password harus punya minimal satu huruf besar!";

    return $errors;
  }
  public static function validateUsername($user) {
    $errors = [];
    if (strlen($user) < 3) $errors[] = "Username terlalu pendek";
    if (strlen($user) > 100) $errors[] = "Username terlalu panjang";
    if (!preg_match('/^\w{3,100}$/', $user)) $errors[] = "Username hanya boleh berisi karakter alfanumerik";
    if (!preg_match('/^[A-Za-z_]/', $user)) $errors[] = "Username tidak boleh diawali angka";
    return $errors;
  }
  public static function validateURL($url) {
    $errors = [];
    if (trim($url) == '') $errors[] = "URL tidak boleh kosong";
    if (!filter_var($url, FILTER_VALIDATE_URL)) $errors[] = "URL $url tidak valid";
    return $errors;
  }
  public static function cleanWysiwyg($content) {
    require (__DIR__.'/../htmlpurifier-4.10.0-lite/library/HTMLPurifier.auto.php');
    $config = \HTMLPurifier_Config::createDefault();
    $config->set('CSS.MaxImgLength',null);
    $config->set('HTML.MaxImgLength',null);
    $purifier = new \HTMLPurifier($config);
    $clean_html = $purifier->purify($content);
    return $clean_html;
  }
}
