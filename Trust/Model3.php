<?php
namespace Trust;
use Trust\DB;
abstract class Model3 {
  //Dibanding Model2: jsonColumns belum supported.
  //Fungsi yang belum support: multiInsert, delWhere, where, allWhere, count, countWhere
  protected $_oldVals;
  protected abstract static function tableName();
  protected abstract static function PK();
  protected abstract static function hasSerial();
  protected abstract static function jsonColumns();
  const MULTI_INSERT_BATCH_COUNT = 10000;
  
  protected static $_publicProps;
  protected static function getPublicProps()  {
//    if (static::$_publicProps != null) return static::$_publicProps;
    static::$_publicProps = array_filter(
            get_class_vars(get_called_class()),
            function($propName) { return substr($propName, 0,1) !== '_'; },
            ARRAY_FILTER_USE_KEY);
    return static::$_publicProps;
  }
  public function __construct($props) {
    if (is_object($props)) {
      foreach (static::getPublicProps() as $k=>$v) if (isset($props->$k)) $this->$k = $props->$k;
    } elseif (is_array($props)) {
      foreach (static::getPublicProps() as $k=>$v) if (isset($props[$k])) $this->$k = $props[$k];
    }
  }
  public static function find($PKs, $cols='*') {
    $sql = "SELECT $cols FROM ".static::tableName();
    $conds = array_map(function($PK) { return "$PK=:$PK"; }, static::PK());
    $sql.= ' WHERE '.implode(' AND ', $conds);
    $bindings=[];
    foreach (static::PK() as $prop) {
      if (!isset($PKs[$prop])) throw new \Exception("Find requires '$prop' property");
      $bindings[$prop] = $PKs[$prop];
    }
    $baris = DB::getOneRow($sql, $bindings);
    if ($baris == null) throw new \Exception("Data not found");
    return static::loadDbRow($baris, true);
  }
  //Cuma tepake kalo nak update. Bareng dengan fungsi find. Kalo select bulky, sebaiknyo ndak dipake
  protected static function loadDbRow($row, $withOldVals=false) {
    $obj = new static($row);
    if ($withOldVals) {
      $obj->_oldVals = new \stdClass();
      foreach (static::getPublicProps() as $k=>$v) $obj->_oldVals->$k = $row->$k;
    }
//    //Dianggap sudah pasti json string. Harus dikonversi ke object/array.
//    foreach (static::jsonColumns() as $col) { //UNTESTED
//      if (!isset($obj->$col)) continue;
//      if ($withOldVals) $obj->_oldVals->$col = json_decode($row->$col);
//      $obj->$col = json_decode($row->$col);
//    }
    return $obj;
  }
  
  public function trim() { foreach (static::getPublicProps() as $k=>$v) $this->$k = trim($this->$k); }
  public function checkPKForInsert() {
    $bindings = [];
    foreach (static::PK() as $PK) $bindings[$PK] = $this->$PK;
    
    $sql = 'SELECT * FROM '.static::tableName();
    $conds = array_map(function($PK) { return "$PK=:$PK"; }, static::PK());
    $sql.= ' WHERE '.implode(' AND ', $conds);
    $ada = DB::rowExists($sql, $bindings);
    if ($ada) {
      $message = 'Sudah ada data ['.implode(',', static::PK()).']=';
      $vals = array_map(function($PK) { return $this->$PK; }, static::PK());
      $message.= implode(',', $vals);
      throw new \Exception($message);
    }
  }
  public function checkPKForUpdate() {
    $bindings=[]; $cols=[];
    foreach (static::PK() as $PK) {
      $bindings["new$PK"] = $this->$PK;
      $bindings["old$PK"] = $this->_oldVals->$PK;
      $cols[] = "$PK<>:old$PK";
      $cols[] = "$PK=:new$PK";
    }
    $sql = 'SELECT * FROM '.static::tableName();
    $sql.= ' WHERE '.implode(' AND ', $cols);
    $ada = DB::rowExists($sql,$bindings);
    if ($ada) {
      $message = 'Sudah ada data ['.implode(',', static::PK()).']=';
      $vals = array_map(function($PK) { return $this->$PK; }, static::PK());
      $message.= implode(',', $vals);
      throw new \Exception($message);
    }
  }
  //Prepare for PGDB  ** Perlu tambah untuk json_columns.
  private function prepareForDB(&$bindings) {
    foreach ($bindings as $k=>$v) if (gettype($v) == "boolean") $bindings[$k] = ($v) ? 'true' : '0';
  }
  public function insert() { //Untested for AI
    if (!static::hasSerial()) $this->checkPKForInsert();
    $cols=[]; $bindings=[];
    $publicProps = static::getPublicProps();
    if (static::hasSerial()) unset($publicProps[static::PK()[0]]);
    foreach ($publicProps as $col=>$v) {
      $cols[] = ":$col";
      $bindings[$col] = $this->$col;
    }
    $this->prepareForDB($bindings);
    $colnames = implode(',', array_keys($publicProps));
    $sql = 'INSERT INTO '.static::tableName()." ($colnames) VALUES (".implode(',',$cols).')';
    if (static::hasSerial()) {
      $pkName = static::PK()[0];
      $this->{$pkName} = DB::insert($sql, $bindings, static::tableName().'_'.$pkName.'_seq');
    } else {
      DB::exec($sql, $bindings);
    }
  }
  public function update() {
    if (!static::hasSerial()) $this->checkPKForUpdate();
    $diff = \Trust\Basic::objDiff($this->_oldVals, $this);
    if (!count($diff)) throw new \Exception('Tidak ada perubahan data');
    $cols = []; $bindings=[];
    foreach ($diff as $col=>$obj) {
      $cols[] = "$col=:$col";
      $bindings[$col] = $obj->new;
    }
    foreach (static::PK() as $PK) $bindings["old$PK"] = $this->_oldVals->$PK;
    $this->prepareForDB($bindings);
    
    $sql = 'UPDATE '.static::tableName().' SET ';
    $sql.= implode(', ', $cols);
    $conds = array_map(function($PK) { return "$PK=:old$PK"; }, static::PK());
    $sql.= ' WHERE '.implode(' AND ', $conds);
    DB::exec($sql, $bindings);
  }
  public function delete() {
    $sql = 'DELETE FROM '.static::tableName();
    $conds = array_map(function($PK) { return "$PK=:$PK"; }, static::PK());
    $sql.= ' WHERE '.implode(' AND ', $conds);
    $bindings = [];
    foreach (static::PK() as $PK) $bindings[$PK] = $this->$PK;
    DB::exec($sql, $bindings);
  }
  public function assign($obj) {
    foreach (static::getPublicProps() as $k=>$v) if (isset($obj->$k)) $this->$k = $obj->$k;
  }
  
  public static function all($cols='*', $bindings=[]) {
    $rows = DB::get("SELECT $cols FROM ".static::tableName(),$bindings);
    return array_map(function($r) { return new static($r); }, $rows);
  }
  public static function allPlus($moreQuery, $cols='*', $bindings=[]) {
    $rows = DB::get("SELECT $cols FROM ".static::tableName()." $moreQuery", $bindings);
    return array_map(function($r) { return new static($r); }, $rows);
  }
  public static function count() {
    return DB::getOneVal('SELECT COUNT(*) FROM '.static::tableName());
  }
  public static function countPlus($strWhere, $bindings=[]) {
    return DB::getOneVal('SELECT COUNT(*) FROM '.static::tableName()." $strWhere", $bindings);
  }
}
