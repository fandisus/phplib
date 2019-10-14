<?php
namespace Trust;
class Versioning {
  public $major, $minor, $revision;
  public function __construct($strVer) {
    $arr = explode('.', $strVer);
    $this->major = $arr[0];
    $this->minor = $arr[1];
    $this->revision = $arr[2];
  }
  public function compare($ver) { //retval: -1 older 0 same 1 newer
    $compMajor = $this->major <=> $ver->major;
    if ($compMajor !== 0) return $compMajor;
    $compMinor = $this->minor <=> $ver->minor;
    if ($compMinor !== 0) return $compMinor;
    $compRev = $this->revision <=> $ver->revision;
    return $compRev; //-1 older 0 same 1 newer
  }
  public function needUpdateTo($ver) {
    if ($this->compare($ver) < 0) return true;
    return false;
  }
  public function isNewer($ver) {
    if ($this->compare($ver) > 0) return true;
    return false;
  }
  public function isOlder($ver) {
    if ($this->compare($ver) < 0) return true;
    return false;
  }
}