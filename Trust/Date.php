<?php
namespace Trust;
class Date {
  protected static $engMonths = ['zz','january','february','march','april','may','june','july','august','september','October','november','december'];
  protected static $indMonths = ['zz','januari','februari','maret','april','mei','juni','juli','agustus','september','oktober','november','desember'];
  /**
   * Converts string to month number
   * @param string $monthName the string representation of the month
   * @return int/false returns month number or false when not found
   */
  static function monthFromName($monthName) {
    $monthName = trim($monthName);
    $res = array_search(strtolower($monthName),Date::$engMonths);
    if ($res) return $res;
    $res = array_search(strtolower($monthName), Date::$indMonths);
    return $res;
    //returns false when not found
  }
  /**
   * Check if the specified string is a valid javascript datetime string.
   * Might be a problem for non US computers
   * @param string $theDate The date string to be checked
   * @return boolean
   */
  static function isJavaDate($theDate) {
    if (\DateTime::createFromFormat("D M d Y H:i:s \G\M\TO +", $theDate)) return true;
    return false;
  }
  static function isSQLDate($theDate) {
    if (\DateTime::createFromFormat('Y-m-d', $theDate)) return true;
    return false;
  }
  static function isSQLTime($theTime) {
    if (\DateTime::createFromFormat('Y-m-d H:i:s', $theTime)) return true;
    return false;
  }
  
  static function firstDayOfMonth() { return Date('m-01-Y'); }
  static function lastDayOfMonth() { return Date('m-t-Y'); }
  static function fromJavascript($strDate) { //http://stackoverflow.com/questions/24258876/convert-javascript-datetime-into-php-datetime
    //Warning: This also converts the date timezone to server timezone.
    //contoh: client: 7AM GMT+7, server: UTC Timezone, result: 0AM UTC  (PHP does not save the UTC part, just 0AM)
    //solusi: date_default_timezone_set("Asia/Jakarta");
    return strtotime(substr($strDate,0,strpos($strDate,"(")));
  }
  //Might be a problem for non US 
  static function toJavascript($timestamp) { return date("D M d Y H:i:s \G\M\TO",$timestamp); }
  static function toSqlDateTime($timestamp) { return date("Y-m-d H:i:s",$timestamp); }
  static function toSqlDate($timestamp) { return date("Y-m-d",$timestamp); }

  static function fromJavascriptToSQLDate($strDate) { return Date::toSqlDate(Date::fromJavascript($strDate)); }
  static function fromJavascriptToSQLDateTime($strDate) { return Date::toSqlDateTime(Date::fromJavascript($strDate)); }
}
?>
