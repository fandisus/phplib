<?php
namespace Trust;
class DMS {
  public $pole, $deg, $min, $sec;
  public function __construct($pole, $deg, $min, $sec) {
    if (!in_array($pole, ['N','E','W','S'])) throw new Exception('Invalid coordinate pole value');
    if (in_array($pole, ['N','S']) && $deg > 90) throw new Exception ('Latitude degree should be less than 90', 0);
    if (in_array($pole, ['W','E']) && $deg > 180) throw new Exception ('Longitude degree should be less than 180', 0);
    if ($min > 60) throw new Exception('Minutes cant be larger than 60');
    if ($sec > 60) throw new Exception('Seconds cant be larger than 60');
    if ($deg < 0) throw new Exception('Degrees cant be negative');
    if ($min < 0) throw new Exception('Minutes cant be negative');
    if ($sec < 0) throw new Exception('Seconds cant be negative');
    $this->pole = $pole; $this->deg = $deg; $this->min = $min; $this->sec = $sec;
  }
  public function to_string() {
    return "$this->pole $this->deg°$this->min'$this->sec\"";
    //return $this->pole .' '.$this->deg.'°'.$this->min."'".$this->sec.'"';
  }
  public function degree() {
    $sign = (in_array($this->pole,['S','W'])) ? -1 : 1;
    return $sign * round($this->deg + $this->min/60 + $this->sec/3600, 6);
  }
}
class Geo {
  /**
   * validates and converts degree or DMS string to respective degree value.
   * @param string $str
   * @param string $type 'lat' or 'long', to define
   * @return double The degree value of the lat / long string
   * @return null Returns null on invalid string
   */
  public static function degreeFromStr($str, $type) {
    if (is_numeric($str)) return self::numStrToDegree($str, $type);
    else return self::dmsStrToDegree($str, $type);
  }
  public static function numStrToDegree($str, $type) {
    if (!is_numeric($str)) return null;
    if ($type == 'lat' && abs($str) > 90) return null;
    if ($type == 'long' && abs($str) > 180) return null;
    return floatval($str);
  }
  public static function dmsStrToDegree($str, $type) {
    if (trim($str) == '') return null;
    if ($type == 'lat') $patt = '/^([NS])\s(\d{1,3})°(\d{1,2})\'(\d{1,2}\.*\d*)"$/';
    elseif ($type == 'long') $patt = '/^([WE])\s(\d{1,3})°(\d{1,2})\'(\d{1,2}\.*\d*)"$/';
    else return null;
    
    $matches = [];
    preg_match($patt, $str, $matches);
    if (!count($matches)) return null;
    try {
      $oDms = new DMS($matches[1], $matches[2], $matches[3], $matches[4]);
      return $oDms->degree();
    } catch (\Exception $ex) {
      return null;
      //JSONResponse::Error($ex->getMessage());
    }
  }
}
