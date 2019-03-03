<?php
class TransaksiBCA {
  public $tanggal, $ket, $masuk, $keluar, $saldo;
  public function __construct($info) {
    $this->tanggal = $info[1];
    $this->ket = str_replace("<br>","",$info[2]);
    $branch = $info[3];
    $jumlah = $this->toFloat($info[4]);
    $jenis = $info[5];
    if ($jenis == "DB") $this->keluar = $jumlah;
    if ($jenis == "CR") $this->masuk = $jumlah;
    //$this->saldo = $this->toFloat($info[6]);
  }
  private function toFloat($num) {
    return (float) str_replace(",", "", $num);
  }
}
