<?php
namespace Trust;
abstract class ConfigFile {
  protected static abstract function file_path();
  protected static abstract function default_content();
  public static function load() {
    if (!file_exists(static::file_path())) file_put_contents(static::file_path(), static::default_content());
    $obj = unserialize(file_get_contents(static::file_path()));
    return $obj;
  }
  public function save() { file_put_contents(static::file_path(), serialize($this)); }
  public function __construct($obj) {
    foreach ($obj as $k=>$v) $this->$k = $v;
  }
}