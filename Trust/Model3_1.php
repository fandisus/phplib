<?php
namespace Trust;
use Trust\DB;
abstract class Model3_1 { //Tambah jsonColumns
  //TODO for Model3_2: Update getPublicProps pake array_keys, biar foreachnya lebih sederhana.
  //multiInsert sudah berhasil di ikreports. Tapi mungkin perlu disempurnakan lebih lanjut.
  //Dibanding Model2: jsonColumns belum supported.
  //Fungsi yang belum support: delWhere, where, allWhere, count, countWhere
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
    //foreach ($obj as $k=>$v) $this->$k = $v; --> Mungkin diubah cak ini bae.
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
    foreach (static::jsonColumns() as $col) { //UNTESTED
      if (!isset($obj->$col)) continue;
      if ($withOldVals) $obj->_oldVals->$col = json_decode($row->$col);
      $obj->$col = json_decode($row->$col);
    }
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
  private function prepareForDB(&$bindings) { //$this ndak berubah, cuma $bindings untuk preparedStatements yang berubah isinyo.
    foreach ($bindings as $k=>$v) if (gettype($v) == "boolean") $bindings[$k] = ($v) ? 'true' : '0';
    foreach (static::jsonColumns() as $col) { if (isset($bindings[$col])) $bindings[$col] = json_encode($bindings[$col]); }
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
  public static function multiInsert(&$objects, $batchSize=10000) { //Pake byref biar hemat memory
    //cols sesuai kiriman di $objects, dan ndak dijson_encode di sini. Ke depan perlu dipertimbangkan untuk encode di sini.
    $columnList = array_keys(self::getPublicProps()); //TODO: Later when getPublicProps is a flat array (no keys), remove array_keys
//    $temp_cols = [];
//    foreach ($objects[0] as $k=>$v) $temp_cols[]=$k;
    $sql = "INSERT INTO \"".static::tableName()."\" (\"".implode("\",\"", $columnList)."\") VALUES ";
    if (DB::$driver === 'mysql') $sql = str_replace ('"', '`', $sql);
    
    if (DB::$driver === 'pgsql') {
    foreach ($objects as $i=>$obj) {
      foreach ($obj as $key=>$val) if (gettype($val) == "boolean") $objects[$i][$key] = ($val) ? 'true' : '0';
    }
    }
    
    DB::init(true); //force: true, Di init duluan, biar pdo->quote berjalan lancar sesuai driver.
    $idx=0; $sqls=[]; $vals=[];$count=count($objects);
    while ($idx < $count) {
      $o = $objects[$idx++];
      $vals = [];
      foreach ($columnList as $col) $vals[]=($o->$col === null) ? 'NULL' : DB::$pdo->quote($o->$col);
      $strVals[]='('. implode(',', $vals). ')';
      if ($idx % $batchSize == 0) {
        $sqls[] = $sql.implode(',', $strVals);
        $strVals=[];
      }
    }
    $sqls[] = $sql.implode(',', $strVals);
    try {
      foreach ($sqls as $s) { \Trust\DB::exec($s,[]); }
    } catch (Exception $ex) {
      throw $ex;
    }
  }
  
  public function update() {
    if (!static::hasSerial()) $this->checkPKForUpdate();
    $diff = \Trust\Basic::objDiff($this->_oldVals, $this);
    //Kalau di objBaru($this) ndak ada field lama, berarti tidak mau diupdate.
    if(isset($diff['_removedFields'])) unset ($diff['_removedFields']);
    if (!count($diff)) throw new \Exception('Tidak ada perubahan data');
    $cols = []; $bindings=[];
    foreach ($diff as $col=>$obj) {
      $cols[] = "$col=:$col";
      $bindings[$col] = $obj->new; //Hasil dari objDiff. Ada prop 'new' dan 'old'
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
    return array_map(function($r) { return static::loadDbRow($r); }, $rows);
  }
  public static function allPlus($moreQuery, $cols='*', $bindings=[]) {
    $rows = DB::get("SELECT $cols FROM ".static::tableName()." $moreQuery", $bindings);
    return array_map(function($r) { return static::loadDbRow($r); }, $rows);
  }
  public static function count() {
    return DB::getOneVal('SELECT COUNT(*) FROM '.static::tableName());
  }
  public static function countPlus($strWhere, $bindings=[]) {
    return DB::getOneVal('SELECT COUNT(*) FROM '.static::tableName()." $strWhere", $bindings);
  }
}
