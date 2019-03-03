<?php
date_default_timezone_set("Asia/Jakarta");
define("DS", DIRECTORY_SEPARATOR);
define("LIBDIR", dirname(__DIR__)); // physical path of html_root. For include/require usages

spl_autoload_register(function($className) {
  //Taken from Faker
  $className = ltrim($className, '\\');
  $fileName = '';
  if ($lastNsPos = strripos($className, '\\')) {
    $namespace = substr($className, 0, $lastNsPos);
    $className = substr($className, $lastNsPos + 1);
    $fileName = str_replace('\\', DS, $namespace) . DS;
  }
  $fileName = __DIR__ . DS . $fileName . $className . '.php';
  if (file_exists($fileName)) {
    require $fileName;
    return true;
  }

  return false;
});
