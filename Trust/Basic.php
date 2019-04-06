<?php
namespace Trust;
class Basic {
  public static function Rp($money=null) {
    if ($money == null) return "Rp.0";
    return "Rp.".number_format($money, 0, ",", ".");
  }
  public static function RpShort($money=null) {
    if ($money == null) return "Rp.0";
    $suffix = "";
    if ($money >= 1000000) { $money = round($money/1000000, 2); $suffix = "M"; }
    elseif ($money >= 1000) { $money = round($money/1000, 0); $suffix = "K"; }
    return "Rp.".number_format($money, 0, ",", ".").$suffix;
  }
  public static function array_merge() { //Merges $arrParam1, $arrParam2, ...
    $args = func_get_args(); //assume all args are arrays
    $all = [];
    foreach ($args as $v) foreach ($v as $i) $all[] = $i;
    return $all;
  }
  public static function array_merge_inside($arr) { //Merges $arr[0]~array, $arr[1]~array, ...
    $all = [];
    foreach ($arr as $v) foreach ($v as $i) $all[] = $i;
    return $all;
  }  
  public static function array_remove(&$arr,$item) {
    $idx = array_search($item,$arr);
    if ($idx === false) return false;
    array_splice($arr, $idx,1);
    return true;
  }
  static function flatten_array(&$arr) { //Nested array into one toplevel array. usage: for output of Files::GetDirFiles
    foreach ($arr as $k=>$v) {
      if (is_array($arr[$k])) {
        Basic::flatten_array($arr[$k]);
        foreach ($arr[$k] as $k2=>$v2) {
          $arr[] = $v2;
        }
        unset ($arr[$k]);
      }
    }
  }
  /**
   * Untuk mencari perbedaan antara dua objek.
   * cuma support untuk:
   * - array of primitives
   * - nested objects.
   * Kalau objects di dalam array, bakal error (atau mungkin nanti diabaikan)
   * Cat: struktur objek ikut $old. Property baru di $new bakal diabaikan.
   * @param type $obj1
   * @param type $obj2
   * @return array
   * $hasil['prop1']->old = 'anu' 
   * $hasil['prop1']->new = 'anu2' 
   * $hasil['prop1']['prop2]->old = 'anu'
   * $hasil['prop1']['prop2]->new = 'anu'
   * $hasil['_removedFields']= ['nama','alamat','password']
   * $hasil['_addedFields']=['name','address','password']
   * $hasil['arr1']->added = 'anu'
   * $hasil['arr1']->removed = 'anu2'
   */
  static function objDiff($old,$new) {
    $hasil = [];
    foreach ($old as $k=>$v) {
      if (!property_exists($new, $k)) { $hasil['_removedFields'][] = $k; continue; }
      if ($old->$k == $new->$k) continue;
      //Every change types, including standard primitives
      $diff = new \stdClass();
      $diff->old = $old->$k;
      $diff->new = $new->$k;
      $hasil[$k] = $diff;
      if (is_object($old->$k)) { //Kalo object
        $hasil[$k]->innerdiff = static::objDiff($old->$k, $new->$k);
        //innerdiff next target: arr[login_info.last_login]->new/old=...,  arr[login_info.cooktok]->new/old=...
        continue;
      }
      elseif (is_array($old->$k)) { //kalo array. Belum ado ngurus reorder. Equality check mungkin perlu pake json_encode_decode.
        $removed = array_diff($old->$k, $new->$k); //get what is removed
        $added = array_diff($new->$k, $old->$k); //get what is added
        $diff = new \stdClass();
        if (count($added)) $diff->added = $added;
        if (count($removed)) $diff->removed = $removed;
        $diff->old = $old->$k;
        $diff->new = $new->$k;
        $hasil[$k] = $diff;
      } else { //Standard primitive
      }
    }
    foreach ($new as $k=>$v) { if (!property_exists($old, $k)) $hasil['_addedFields'][] = $k; }
    return $hasil;
  }
  //Untuk olah hasil fungsi objDiff di atas.
  static function humanReadableDiff($diff, $prefix='') {
    $hasil = [];
    foreach ($diff as $k=>$v) {
      $path = substr("$prefix/$k", 1);
      if (!is_array($v)) {
        if (isset($v->added)) foreach ($v->added as $k2=>$v2) $hasil[] = "Added $v2 to $path";
        if (isset($v->removed)) foreach ($v->removed as $k2=>$v2) $hasil[] = "Removed $v2 to $path";
        if (is_array($v->old)) { $v->old = json_encode($v->old); $v->new = json_encode($v->new); }
        $hasil[]="Modified $path from $v->old to $v->new";
      } else {
        $hasil = array_merge($hasil, static::humanReadableDiff($v, "/$path"));
      }
    }
    return $hasil;
  }
  //Untuk olah hasil fungsi objDiff di atas.
  static function pgReadableDiff($diff, $prefix='') {
    $hasil = [];
    foreach ($diff as $k=>$v) {
      $path = substr("$prefix/$k", 1);
      if (!is_array($v)) {
        if ($prefix == '') {
          if (is_array($v->old)) {
            $v->old = json_encode($v->old);
            $v->new = json_encode($v->new);
          }
          $hasil[] = "$k = $v->new";
        } else {
          $paths = explode('/', $path);
          $field = array_shift($paths);
          $newpath = implode(',', $paths);
          $newval = json_encode($v->new);
          $hasil[] = "$field = jsonb_set($field, '{{$newpath}}', '$newval')";
        }
      } else {
        $hasil = array_merge($hasil, static::pgReadableDiff ($v, "/$path"));
      }
    }
    return $hasil;
  }
  public static function RandomString($num, $lettersOnly=false) { 
    if ($lettersOnly) $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    else $characters = '0123456789ABCDEFGHIJKLMNPQRSTUVWXYZ';//tanpa huruf kecil dan O
    $maxIndex = strlen($characters)-1;
    $randstring = '';
    for ($i = 0; $i < $num; $i++) {
      $randstring .= $characters[rand(0, $maxIndex)];
    }
    return $randstring;
  }

}


//Contoh getDiff()
//$a = [
//    'nama'=>'Fandi',
//    'nilais'=>[100,99,80,200],
//    'mobil'=>[
//        'tipe'=>'Avanza',
//        'tahun'=>'2001',
//        'nopol'=>[
//            'nopol'=> 'BG 1234 ava',
//            'expired'=>'01 01 2012'
//        ]
//    ]
//];
//$a = json_decode(json_encode($a));
//
//$b = [
//    'nama'=>'Fandi Susanto',
//    'nilais'=>[100,99,300,200],
//    'mobil'=>[
//        'tipe'=>'Fortuner',
//        'tahun'=>'2020',
//        'nopol'=>[
//            'nopol'=> 'BG 1234 anu',
//            'expired'=>'01 01 2020'
//        ]
//    ]
//];
//$b = json_decode(json_encode($b));
//
//$diff = \Trust\Basic::objDiff($a, $b);
//\Trust\Debug::print_r(\Trust\Basic::pgReadableDiff($diff));
