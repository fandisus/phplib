<?php
//TODO: WARNING! Updating this to old projects might cause problems:
//Old behavior: updating null fields will ignore the field.
//New behavior: null fields will be updated to null. To ignore, must specify ignores.
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
  
  protected static $_publicProps = [];
  protected static function getPublicProps()  {
    $class= get_called_class();
    if (isset(static::$_publicProps[$class])) return static::$_publicProps[$class];
//    if (static::$_publicProps != null) return static::$_publicProps;
    static::$_publicProps[$class] = array_filter(
            get_class_vars($class),
            function($propName) { return substr($propName, 0,1) !== '_'; },
            ARRAY_FILTER_USE_KEY);
    return static::$_publicProps[$class];
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
  //Ado fungsi ini supaya kalau multiinsert bikin object ndak ribet bolak balik decode encode row.
  public static function loadDbRow($row, $withOldVals=false) {
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
  public static function multiInsert(&$objects, $batchSize=10000, $JSONConvert=false) { //Pake byref biar hemat memory
    $count=count($objects);
    if ($count === 0) throw new \Exception('No object to insert.');
    //cols sesuai kiriman di $objects, dan ndak dijson_encode di sini. Ke depan perlu dipertimbangkan untuk encode di sini.
    $columnList = array_keys(self::getPublicProps()); //TODO: Later when getPublicProps is a flat array (no keys), remove array_keys
//    $temp_cols = [];
//    foreach ($objects[0] as $k=>$v) $temp_cols[]=$k;
    $sql = "INSERT INTO \"".static::tableName()."\" (\"".implode("\",\"", $columnList)."\") VALUES ";
    if (DB::$driver === 'mysql') $sql = str_replace ('"', '`', $sql);
    
    if (DB::$driver == 'pgsql') {
      foreach ($objects as &$obj) {
        foreach ($obj as &$val) if (gettype($val) == "boolean") $val = ($val) ? 'true' : '0';
      }
    } elseif (DB::$driver == 'mysql') {
      foreach ($objects as &$obj) {
        foreach ($obj as &$val) if (gettype($val) == "boolean") $val = ($val) ? 1 : 0;
      }
    }
    
    DB::init(true); //force: true, Di init duluan, biar pdo->quote berjalan lancar sesuai driver.
    $idx=0; $sqls=[]; $vals=[];
    $strVals = [];
    while ($idx < $count) {
      $o = $objects[$idx++];
      if ($JSONConvert) foreach (static::jsonColumns() as $col) $o->$col = json_encode($o->$col);
      $vals = [];
      foreach ($columnList as $col) $vals[]=($o->$col === null) ? 'NULL' : DB::$pdo->quote($o->$col);
      $strVals[]='('. implode(',', $vals). ')';
      if ($JSONConvert) foreach (static::jsonColumns() as $col) $o->$col = json_decode($o->$col);
      if ($idx % $batchSize == 0) {
        if ($idx == $count) break;
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
  
  //Problem dulu:
  //- Trust\Basic pakai if(!isset) untuk ignore. --> Nak set null jadi ndak biso.
  //- Kalau pakai if (!property_exists), nak ignore jadi ndak biso.
  //Solution: Buat parameter ignores, dan targets
  //ignores untuk menentukan kolom yang diabaikan. Targets untuk menentukan HANYA kolom target yang mau diupdate.
  public function update($targets=[],$ignores=[],$debug=false) {
    if (!static::hasSerial()) $this->checkPKForUpdate();
    $diff = \Trust\Basic::objDiff($this->_oldVals, $this);
    if (!count($diff)) throw new \Exception('Tidak ada perubahan data');
    $onlyUpdateTarget = (count($targets) === 0) ? false : true;
    $cols = []; $bindings=[];
    foreach ($diff as $col=>$obj) {
      if (in_array($col, $ignores)) continue; //ignores fields specified in ignore.
      if ($onlyUpdateTarget && !in_array($col, $targets)) continue;
      $cols[] = "$col=:$col";
      $bindings[$col] = $obj->new; //Hasil dari objDiff. Ada prop 'new' dan 'old'
    }
    foreach (static::PK() as $PK) $bindings["old$PK"] = $this->_oldVals->$PK;
    $this->prepareForDB($bindings);
    
    $sql = 'UPDATE '.static::tableName().' SET ';
    $sql.= implode(', ', $cols);
    $conds = array_map(function($PK) { return "$PK=:old$PK"; }, static::PK());
    $sql.= ' WHERE '.implode(' AND ', $conds);
//    if ($debug)  {
//      Debug::print_r($bindings);
//      die ($sql);
//    }
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
  //This allWithOldVals is for getting $_oldVals when using all and allPlus.
  //Be careful with usage. Must to set it to false again after each usage.
  //If not set to false, will affect future queries of sibling classes.
  protected static $_allWithOldVals = false;
  public static function setAllWithOldVals($boolVal) { self::$_allWithOldVals = $boolVal; }
  public static function getAllWithOldVals() { return self::$_allWithOldVals; }
  
  public static function all($cols='*', $bindings=[]) {
    $rows = DB::get("SELECT $cols FROM ".static::tableName(),$bindings);
    return array_map(function($r) { return static::loadDbRow($r, self::$_allWithOldVals); }, $rows);
  }
  public static function allPlus($moreQuery, $cols='*', $bindings=[]) {
    $rows = DB::get("SELECT $cols FROM ".static::tableName()." $moreQuery", $bindings);
    return array_map(function($r) { return static::loadDbRow($r, self::$_allWithOldVals); }, $rows);
  }
  public static function count() {
    return DB::getOneVal('SELECT COUNT(*) FROM '.static::tableName());
  }
  public static function countPlus($strWhere, $bindings=[]) {
    return DB::getOneVal('SELECT COUNT(*) FROM '.static::tableName()." $strWhere", $bindings);
  }
}
