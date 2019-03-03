<?php
namespace Trust;

include_once __DIR__.'/../Mobile-Detect-2.8.32/Mobile_Detect.php';
class Browser {
  public static $detector;
  public static function initDetector() {
    if (self::$detector == null) self::$detector = new \Mobile_Detect ();
  }
  public static function isMobile() {
    self::initDetector();
    return self::$detector->isMobile();
  }
  public static function isTablet() {
    self::initDetector();
    return self::$detector->isTablet();
  }
  public static function isIOS() {
    self::initDetector();
    return self::$detector->isiOS();
  }
  public static function isAndroidOS() {
    self::initDetector();
    return self::$detector->isAndroidOS();
  }
}
