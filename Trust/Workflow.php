<?php
namespace Trust;
class Workflow {
  static public $available_service = ['abc'];
  public static function setAvailableServices($services) {
    static::$available_service = $services;
  }
  public static function showIfNoPost($viewpath) {
    if (!count($_POST)) {
      if (!file_exists($viewpath)) die("File $viewpath could not be found");
      require($viewpath);
      die();
    }
  }
  public static function callServiceIfExists($service) {
    if (!isset($service)) JSONResponse::Error('Service not specified');
    if (!in_array($service, static::$available_service)) JSONResponse::Error('Service unavailable');
    if (!function_exists($service)) JSONResponse::Error('Service not found');
    $service();
    die();
  }
  
  public static function setServices($view, $services) {
    static::showIfNoPost($view);
    static::setAvailableServices($services);
    static::callServiceIfExists($_POST['a']);
  }
}