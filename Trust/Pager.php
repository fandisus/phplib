<?php
namespace Trust;
class Pager {
  /**
   * Processes $_POST to a more meaningful object
   * strWhere to be put at Count and Select query
   * strOrder and strLimit to be put at Select query
   * currentPage and itemsPerPage to be returned for angular processing
   * @return \stdClass{strWhere, strOrder, strLimit, currentPage, itemsPerPage}
   */
  static function GetQueryAttributes() { //Next: Tambahkan attr untuk masukin custom where yang selalu hadir
    //Initializer, if the $_POST data is empty
    $currentPage = 1;
    $itemsPerPage = 50;
    $filterBy = array();
    $orderBy  = array();
    if (isset($_POST['pager'])) {
      $pager = $_POST['pager'];
      foreach($pager as $k=>$v) $$k = $v;
    }
    if (!is_numeric($currentPage)) die(json_encode(array("result"=>"error", "message"=>"Error loading page")));
    $strWhere = $strOrder = ""; //TODO: Sanitize query
    if (count($filterBy) > 0) {
      $wheres = array(); //key, text, query
      foreach ($filterBy as $v) $wheres[] = "$v[key] LIKE '%$v[query]%'";
      $strWhere = "WHERE ".implode(" AND ", $wheres);
    }
    if (count($orderBy) > 0) {
      $ords = array(); //key, text, dir
      foreach ($orderBy as $v) $ords[] = "$v[key] $v[dir]";
      $strOrder = "ORDER BY ".implode(",", $ords);
    }

    $strLimit = "LIMIT $itemsPerPage OFFSET ".(($currentPage - 1)*$itemsPerPage);
    $res = new \stdClass();
    $res->currentPage = $currentPage;
    $res->itemsPerPage = $itemsPerPage;
    $res->strWhere = $strWhere;
    $res->strOrder = $strOrder;
    $res->strLimit = $strLimit;
    return $res;
  }
}
