<?php
namespace Trust;
class JSONResponse {
  static function Success($arr=[]) {
    header('Content-Type: application/json');
    $arr["result"]="success";
    echo json_encode($arr);
    die();
  }
  static function Error($msg,$arr=[]) {
    header('Content-Type: application/json');
    $arr["result"]="error";
    $arr['message']=$msg;
    echo json_encode($arr);
    die();
  }
  static function Debug($data) {
    header('Content-Type: application/json');
    echo json_encode(["result"=>"debug", "data"=>$data]);
    die();
  }
}
