<?php
namespace Trust;
class Server {
  /**
   * Sometimes php.ini will have magic_quotes_gpc = on. This function will negate that.
   * After using this function, it is fully the programmer task to secure inputs.
   */
  static function killMagicQuotesGpc() {
    if (get_magic_quotes_gpc()) {
      function stripslashes_gpc(&$value)
      {
          $value = stripslashes($value);
      }
      array_walk_recursive($_GET, 'stripslashes_gpc');
      array_walk_recursive($_POST, 'stripslashes_gpc');
      array_walk_recursive($_COOKIE, 'stripslashes_gpc');
      array_walk_recursive($_REQUEST, 'stripslashes_gpc');
    }
  }
  
  /**
   * Starts session if not started yet.
   */
  static function StartSession() {
    $sessionStarted = false;
    if (version_compare(PHP_VERSION, '5.4.0', '<')) {
      $sessionStarted = (session_id()!='');
    } else {
      $sessionStarted = (session_status() == PHP_SESSION_ACTIVE);
    }

    if (!$sessionStarted) session_start();
  }
  /**
   * Generate CSRF Token and save token in session
   */
  static function csrf_token($salt='') {
    if (!isset($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = [];
    
    $token = hash('sha256',session_id().$_SERVER['REMOTE_ADDR'].rand(0,PHP_INT_MAX).$salt);
    $_SESSION['csrf_token'][] = $token;
    if (count($_SESSION['csrf_token']) > 10) array_shift($_SESSION['csrf_token']);
    return $token;
  }
  static function csrf_is_valid() {
    if (!isset($_POST['token'])) return false;
    if (!isset($_SESSION['csrf_token'])) return false;
    return (in_array($_POST['token'], $_SESSION['csrf_token']));
  }
  static function check_csrf_token($messageIfInvalid='Token csrf tidak valid') {
    if (count($_POST) && !Server::csrf_is_valid()) {
      JSONResponse::Error($messageIfInvalid);
    } else unset($_POST['token']);
  }
}
