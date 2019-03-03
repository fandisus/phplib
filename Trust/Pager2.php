<?php
namespace Trust;
class Pager2 {
  /**
   * Processes $_POST to a more meaningful object
   * strWhere to be put at Count and Select query
   * strOrder and strLimit to be put at Select query
   * currentPage and itemsPerPage to be returned for angular processing
   * @return \stdClass{strWhere, strOrder, strLimit, currentPage, itemsPerPage}
   */
  public $currentPage, $itemsPerPage, $strLimit;
  public $filterBy; //key, text, query
  public $orderBy; //key, text, dir
  public function strWhere() {
    if (count($this->filterBy) === 0) return '';
    $wheres = []; //key, text, query
    foreach ($this->filterBy as $v) $wheres[] = "$v[key] LIKE '%$v[query]%'";
    return "WHERE ".implode(" AND ", $wheres);
  }
  public function strOrder() {
    if (count($this->orderBy) === 0) return '';
    $ords = array(); //key, text, dir
    foreach ($this->orderBy as $v) $ords[] = "$v[key] $v[dir]";
    return "ORDER BY ".implode(",", $ords);
  }
  public function __construct($currentPage, $itemsPerPage, $strLimit, $filterBy = [], $orderBy = []) {
    $this->currentPage = $currentPage;
    $this->itemsPerPage = $itemsPerPage;
    $this->strLimit = $strLimit;
    $this->filterBy = $filterBy;
    $this->orderBy = $orderBy;
  }
//    $res->strWhere = $strWhere;
//    $res->strOrder = $strOrder;
  
  static function GetQueryAttributes() { //Next: Tambahkan attr untuk masukin custom where yang selalu hadir
    //Initializer, if the $_POST data is empty
    $currentPage = 1;
    $itemsPerPage = 50;
    $filterBy = []; //key, text, query
    $orderBy  = []; //key, text, dir
    if (isset($_POST['pager'])) { //pager:{currentPage:1,itemsPerPage:50,filterBy:[],orderBy:[]}
      $pager = $_POST['pager'];
      foreach($pager as $k=>$v) $$k = $v;
    }
    if (!is_numeric($currentPage)) throw new \Exception("Error loading page");
    $strLimit = "LIMIT $itemsPerPage OFFSET ".(($currentPage - 1)*$itemsPerPage);
    
    $res = new Pager2($currentPage, $itemsPerPage, $strLimit, $filterBy, $orderBy);
    return $res;
  }
}
