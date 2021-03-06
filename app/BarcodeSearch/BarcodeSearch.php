<?php

namespace PretrashBarcode;

use PretrashBarcode\Providers\UPCDatabase;
use PretrashBarcode\Providers\SearchUPC;
use PretrashBarcode\Providers\UPCItemDb;
use Illuminate\Support\Facades\Log;

class BarcodeSearch {

  private $providerList;

  public function __construct() {
    $this->providerList = [
        new UPCDatabase(env('UPCDATABASE_KEY')),
        new SearchUPC(env('SEARCHUPC_KEY')),
        new UPCItemDb()
      ];
  }

  public function search($barcode) {
    if(strlen($barcode) == 13 && $barcode[0] == '0') {
      $barcode = substr($barcode, 1);
    }
    foreach($this->providerList as $provider) {
      if($name = $provider->search($barcode)) {
        return $name;
      }
    }
  }
}
