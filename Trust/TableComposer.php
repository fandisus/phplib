<?php
namespace Trust;
class TableComposer {
  public $tableName;
  protected $lastCol;
  protected $columns=[];
  protected $constraints=[];
  protected $indexes=[];
  protected $comments=[];
  
  protected function returner($colName) {
    $this->lastCol = $colName;
    return $this;
  }
  public function __construct($tableName) { $this->tableName = $tableName; }
  public function increments($colName) {
    if (DB::$driver === 'pgsql') $this->columns[] = "$colName SERIAL";
    elseif (DB::$driver === 'mysql') $this->columns[] = "$colName INT AUTO_INCREMENT";
    return $this->returner($colName);
  }
  public function bigIncrements($colName) {
    if (DB::$driver === 'pgsql') $this->columns[] = "$colName BIGSERIAL";
    elseif (DB::$driver === 'mysql') $this->columns[] = "$colName BIGINT AUTO_INCREMENT";
    return $this->returner($colName);
  }
  public function string($colName, $length=50) {
    if (DB::$driver === 'pgsql') $this->columns[] = "$colName CHARACTER VARYING($length)";
    elseif (DB::$driver === 'mysql') $this->columns[] = "$colName VARCHAR($length)";
    return $this->returner($colName);
  }
  public function text($colName) {
    $this->columns[] = "$colName TEXT";
    return $this->returner($colName);
  }
  public function integer($colName) {
    if (DB::$driver === 'pgsql') $this->columns[] = "$colName INTEGER";
    elseif (DB::$driver === 'mysql') $this->columns[] = "$colName INT";
    return $this->returner($colName);
  }
  public function bigInteger($colName) {
    if (DB::$driver === 'pgsql' || DB::$driver === 'mysql') $this->columns[] = "$colName BIGINT";
    return $this->returner($colName);
  }
  public function double($colName) {
    if (DB::$driver === 'pgsql') $this->columns[] = "$colName DOUBLE PRECISION";
    elseif (DB::$driver === 'mysql') $this->columns[] = "$colName DOUBLE";
    return $this->returner($colName);
  }
  public function numeric($colname, $precision, $scale) {
    $this->columns[] = "$colname NUMERIC ($precision, $scale)";
    return $this->returner($colname);
  }
  public function bool($colName) {
    $this->columns[] = "$colName BOOL";
    return $this->returner($colName);
  }
  public function timestamp($colName) {
    if (DB::$driver === 'pgsql') $this->columns[] = "$colName TIMESTAMP";
    elseif (DB::$driver === 'mysql') $this->columns[] = "$colName DATETIME";
    return $this->returner($colName);
  }
  public function date($colName) {
    $this->columns[] = "$colName DATE";
    return $this->returner($colName);
  }
  public function time($colname) {
    $this->columns[] = "$colname TIME";
    return $this->returner($colname);
  }
  public function jsonb($colName) {
    if (DB::$driver === 'pgsql') $this->columns[] = "$colName JSONB";
    elseif (DB::$driver === 'mysql') $this->columns[] = "$colName JSON";
    return $this->returner($colName);
  }

  
  public function notNull() {
    $this->columns[count($this->columns)-1] .= " NOT NULL";
    return $this;
  }
  public function unique() {
    $col = $this->lastCol;
    $this->constraints[] = "CONSTRAINT uq_$this->tableName"."_$col UNIQUE ($col)";
    return $this;
  }
  public function index() {
    $col = $this->lastCol;
    $this->indexes[] = "CREATE INDEX idx_$col"."_$this->tableName ON $this->tableName USING BTREE ($col);";
    return $this;
  }
  public function ginPropIndex($props) { //Not supported in mysql
    if (DB::$driver === 'mysql') return $this;
    $col = $this->lastCol;
    if (!is_array($props)) $props = [$props];
    foreach ($props as $v) {
      $this->indexes[] = "CREATE INDEX idx_$v"."_$col"."_$this->tableName ON $this->tableName USING GIN (($col"."->'$v'));";
    }
    return $this;
  }
  public function ginIndex() { //Not supported in mysql
    if (DB::$driver === 'mysql') return $this;
    $col = $this->lastCol;
    $this->indexes[] = "CREATE INDEX idx_$col"."_$this->tableName ON $this->tableName USING GIN ($col);";
    return $this;
  }
  public function mysqlJsonIndex($props) {
    $col = $this->lastCol;
    //$props format: [['name'=>'name','path'=>'$.location.name','type'=>'INT/VARCHAR(45)']]
    foreach ($props as $v) {
      $this->columns[] = "{$this->lastCol}_{$v['name']} {$v['type']} AS ($this->lastCol->>\"$v[path]\")";
//      $this->indexes[] = "ALTER TABLE $this->tableName ADD {$this->lastCol}_{$v['name']} $v[type] "
//              . "AS ($this->lastCol->>\"$v[path]\")";
      $this->indexes[] = "CREATE INDEX idx_{$this->lastCol}_{$v['name']}_{$this->tableName} ON $this->tableName USING BTREE ($this->lastCol);";
    }
    return $this;
  }
  public function primary($cols="") {
    if ($cols == "") $cols = $this->lastCol;
    $strCols = (is_array($cols)) ? implode(",",$cols) : $cols;
    $this->constraints[] = "CONSTRAINT pk_$this->tableName PRIMARY KEY ($strCols)";
    return $this;
  }
  public function foreign($ref,$refcol,$onupdate = "",$ondelete = "") {
    $col = $this->lastCol;
    $onupdate = ($onupdate == "") ? " ON UPDATE CASCADE" : " ON UPDATE $onupdate";
    $ondelete = ($ondelete == "") ? " ON DELETE CASCADE" : " ON DELETE $ondelete";
    $this->constraints[] = "CONSTRAINT fk_$col"."_$this->tableName FOREIGN KEY ($col) REFERENCES $ref ($refcol)$onupdate$ondelete";
    return $this;
  }
  public function multiForeign($cols,$ref,$refcols,$onupdate,$ondelete) {
    $onupdate = ($onupdate == "") ? "" : " ON UPDATE $onupdate";
    $ondelete = ($ondelete == "") ? "" : " ON DELETE $ondelete";
    $this->constraints[] = "CONSTRAINT fk_$ref"."_$this->tableName FOREIGN KEY ($cols) REFERENCES $ref ($refcols)$onupdate$ondelete";
    return $this;
  }
  public function comment() {
    $args = func_get_args();
    if (count($args) == 1) {
      $col = $this->lastCol;
      $c = $args[0];
    } else {
      $col = $args[0];
      $c = $args[1];
    }
    $c = str_replace("'", "''", $c);
    $this->comments[] = "COMMENT ON COLUMN $this->tableName.$col IS '$c';";
    return $this;
  }
  
  public function parse() {
    $insides = \Trust\Basic::array_merge($this->columns, $this->constraints);
    $strInsides = implode(",\n  ", $insides);
    $comment = "-- tabel $this->tableName --";
    $dropper = "DROP TABLE IF EXISTS $this->tableName CASCADE;";
    $creator = "CREATE TABLE $this->tableName (\n  $strInsides\n);";
    return \Trust\Basic::array_merge( [$comment, $dropper, $creator], $this->indexes, $this->comments );
  }
}
