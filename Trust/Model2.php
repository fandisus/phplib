<?php
namespace Trust;
use Trust\DB;

if (!interface_exists('Trust\iSaveable')) {
  interface iSaveable {
    public function save();
    public static function delete($id);
  }
  interface iLoadable {
    static function find($id, $cols="*");
    static function all($cols="*");
  }
}
abstract class Model2 implements iSaveable, iLoadable { //Applied to tagtoyota
  protected $old_vals;
  protected static $json_columns = [], $table_name;
  protected static $key_name="id", $increment=true; //$hasTimestamps=true; dibuang di Model2
  protected static $makeOldVals=true;
  protected static $multiInsertBatchCount = 10000;
  public static function setMultiInsertBatchCount($v) { static::$multiInsertBatchCount = $v; }
  
  //Constructor are meant to read from database, so json fields are expected to be in json format.
  public function __construct($arrProps) {
    //Model: json_columns di old_vals dalam json_string.
    //Model2: json_columns di old_vals dalam object.
    foreach ($arrProps as $k=>$v) $this->$k = $v;
    if (static::$makeOldVals) { 
      $this->old_vals = new \stdClass();
      foreach ($arrProps as $k=>$v) $this->old_vals->$k = $v;
    }
    foreach (static::$json_columns as $col) {
      if (!isset($this->$col)) continue; //Kalau bikin objek baru dan objek baru ndak masukin ini.
      if (is_object($this->$col) || is_array($this->$col)) continue;
      $this->$col = json_decode($this->$col);
      if (static::$makeOldVals) $this->old_vals->$col = json_decode($this->old_vals->$col);
    }
  }
  public function save() {
    $key = static::$key_name;
    if (!isset($this->$key) || !$this->$key) $this->insert(); else $this->update();
  }

  public function getPublicProps() {
    //Model: json_columns dan boolean siap ditulis ke db. json_columns dalam json string, boolean sudah jadi true.
    //Model2: cuma ambek public props bae. Konversi ke format pg jangan di sini.
    //Model: Return dalam bentuk array.
    //Model2: Return dalam bentuk object.
    $props = get_object_vars($this); //galo galo
    $classVars = get_class_vars(get_class($this)); //yang protected bae.
    foreach ($classVars as $k=>$v) unset($props[$k]); //Hapus protected properties
//    foreach (static::$json_columns as $v) { if (isset($props[$v])) $props[$v] = json_encode($props[$v]);}
//    foreach ($props as $k=>$v) if (gettype($v) == "boolean") $props[$k] = ($v) ? 'true' : '0';
    //if (get_class($this) == "SSBIN\\User") JSONResponse::Debug ($props);
    return json_decode(json_encode($props));
  }
  protected static function prepareForDB(&$props) {
    foreach (static::$json_columns as $v) { if (isset($props->$v)) $props->$v = json_encode($props->$v);}
    foreach ($props as $k=>$v) if (gettype($v) == "boolean") $props->$k = ($v) ? 'true' : '0';
  }
//  public function setTimestamps($forceNew = false) {} //Di Model2 dibuang.
//  public static function newDataInfo() {} //Di Model2 dibuang.
  
  public function insert() {//Note: Kalau id AI, ndak perlu sengajo diset. Karena bakal dibuang biar AI.
//    $this->setTimestamps(); //di Model2 dibuang.
    $props = $this->getPublicProps();
    static::prepareForDB($props);
    unset ($props->id); //kalo ado id, buang, biar jadi default (AI)
    $propsArr = get_object_vars($props);
    $cols = array_keys($propsArr);
//    $vals = array_values($propsArr); //Tek guno, jadi dicomment.
    
    $sql = "INSERT INTO \"".static::$table_name."\" (\"".implode("\",\"", $cols)."\") VALUES (:".implode(",:",$cols).")";
    try {
      if (static::$increment) {
        $this->{static::$key_name} = DB::insert($sql, $propsArr, static::$table_name."_".static::$key_name."_seq");
      } else {
        DB::insert($sql, $propsArr);
      }
    } catch (\Exception $ex) {
      throw $ex;
    }
    return true;
  }
  
  public function json_encode() { 
    foreach (static::$json_columns as $v) {
      if (!isset($this->$v)) continue;
      $this->$v = json_encode($this->$v); 
      $this->old_vals->$v = json_encode($this->old_vals->$v);
    }
  }
  public function json_decode() { 
    foreach (static::$json_columns as $v) {
      if (!isset($this->$v)) continue;
      $this->$v = json_decode($this->$v); 
      $this->old_vals->$v = json_decode($this->old_vals->$v);
    }
  }
  public static function multiInsert(&$objects) { //Pake byref biar hemat memory. //Untested di Modal2.
    //Modal2: Bisa bisa collection of Objects atau Arrays. Terserah.
    //cols sesuai kiriman di $objects, json_columns harus sudah dalam string. Ndak diencode di sini.
    foreach ($objects as $k=>$v) unset ($objects[$k]->old_vals);
    $colNames = [];
    foreach ($objects[0] as $k=>$v) $colNames[]=$k;
    $sql = "INSERT INTO \"".static::$table_name."\" (\"".implode("\",\"", $colNames)."\") VALUES ";

    //Logic ini dipindah ke loop di bawah. Biar penyesuaian format tipedata DB jadi sikok.
//    foreach ($objects as $i=>$obj) {
//      foreach ($obj as $key=>$val) {
//        if (gettype($val) == "boolean") {
//          if (is_array($obj)) $objects[$i][$key] = ($val) ? 'true' : '0';
//          elseif (is_object($obj)) $objects[$i]->$key = ($val) ? 'true' : '0';
//        }
//      }
//    }

    $idx=0; $sqls=[]; $vals=[];$count=count($objects);
    while ($idx < $count) {
      $o = $objects[$idx++];
      $vals = [];
      foreach ($o as $k=>$v) $vals[]=$v;
      //print_r($vals);die();
      foreach ($vals as $k=>$v) {
        if (gettype($vals[$k] == 'boolean')) $vals[$k] = ($v) ? 'true' : '0'; //Untested
        $vals[$k] = ($v==null) ? 'NULL' : DB::$pdo->quote($v); //Semua value di Quote, kecuali null.
      }
      $strVals[]='('. implode(',', $vals). ')';
      if ($idx % static::$multiInsertBatchCount == 0) {
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
  /**
   * getChanges
   *  
   * Cari perbedaan dari old_vals dengan yang sekarang.
   */
  public function getDiff() {
    return \Trust\Basic::objDiff($this->old_vals, $this);
  }
  public function getDirtyProps() {
    $this->json_encode();
    $props = $this->getPublicProps();
    foreach ($this->old_vals as $k=>$v) {
      //Kalau update, pasti old_vals ada semua, props ada semua. ndak ada berarti ndak mau diupdate.
      if (isset($props->$k) && $props->$k == $v) unset($props->$k);
    }
    foreach (static::$json_columns as $v) { if (isset($props->$v)) json_decode($props->$v); }
    $this->json_decode();
    return $props;
  }
  public function update() {
//    $this->setTimestamps(); //di Model2 dibuang.
    $props = $this->getDirtyProps();
    static::prepareForDB($props);
    foreach ($props as $k=>$v) { $cols[]="$k=:$k"; }
	if (!isset($cols)) throw new \Exception('No value to update');
    
    $propsArr = get_object_vars($props);
    $propsArr['PK'] = $this->old_vals->{static::$key_name};
    $sql = "UPDATE \"".static::$table_name."\" SET ".implode(",",$cols)." WHERE ".static::$key_name."=:PK";
    try {
      return DB::exec($sql, $propsArr);
    } catch (\Exception $ex) {
      throw $ex;
    }
  }
	public function assign($newObj) {
		foreach ($newObj as $k=>$v) $this->$k = $v;
	}

  public static function delete($PK) {
    $sql = "DELETE FROM \"".static::$table_name."\" WHERE ".static::$key_name."=:".static::$key_name;
    try {
      return DB::exec($sql, [static::$key_name=>$PK]);
    } catch (\Exception $ex) {
      throw $ex;
    }
  }
  
  public static function delWhere($strWhere, $colVals) {
    $sql = "DELETE FROM \"".static::$table_name."\" $strWhere";
    try {
      return DB::exec($sql, $colVals);
    } catch (\Exception $ex) {
      throw $ex;
    }
  }

  public static function all($cols="*") {
    $sql = "SELECT $cols FROM \"".static::$table_name."\"";
    try {
      $read = DB::get($sql, []);
    } catch (\Exception $ex) {
      throw $ex;
    }
    foreach ($read as $k=>$v) $read[$k] = new static($v);
    return $read;
  }

  public static function find($PK, $cols="*") {
    $sql = "SELECT $cols FROM \"".static::$table_name."\" WHERE ".static::$key_name."=:".static::$key_name;
    try {
      $read = DB::get($sql, [static::$key_name=>$PK]);
    } catch (\Exception $ex) {
      throw $ex;
    }
    if (count($read)) return new static($read[0]);
    return null;
  }
  public static function where($strWhere, $colVals, $cols="*") {
    $sql = "SELECT $cols FROM \"".static::$table_name."\" $strWhere";
    try {
      $read = DB::get($sql, $colVals);
    } catch (\Exception $ex) {
      throw $ex;
    }
    if (count($read)) return new static($read[0]);
    return null;
  }
  public static function allWhere($strWhere, $colVals, $cols="*") {
    $sql = "SELECT $cols FROM \"".static::$table_name."\" $strWhere";
    try {
      $read = DB::get($sql, $colVals);
    } catch (\Exception $ex) {
      throw $ex;
    }
    foreach ($read as $k=>$v) $read[$k] = new static($v);
    return $read;
  }
  public static function count() {
    $sql = 'SELECT COUNT(*) FROM "'.static::$table_name.'"';
    try {
      return DB::getOneVal($sql);
    } catch (Exception $ex) {
      throw $ex;
    }
  }
  public static function countWhere($strWhere,$colVals) {
    $sql = 'SELECT COUNT(*) FROM "'.static::$table_name."\" $strWhere";
    try {
      return DB::getOneVal($sql,$colVals);
    } catch (Exception $ex) {
      throw $ex;
    }
  }
}
