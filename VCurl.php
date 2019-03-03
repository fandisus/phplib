<?php

/* ---------------------------------------------------------
  Class Curl
  Author  : VbA
  Contact : Admin@TheHoster.Net
  Facebook: BukanVbA
  URL     : www.CoderID.net | www.TheHoster.net
  --------------------------------------------------------- */

class VCurl {
  var $output = array();
  var $headerData;
  var $bodyData;
  var $followLocation;
  var $enableHttpCode;
  var $enablePost;
  var $dataPost;
  var $enableCookies;
  var $cookiesName;
  var $disableSSL;
  var $otherSet;
  var $setCurl;
  var $curlFile;
  var $httpAuth;
  var $timeOut;
  var $proxy;
  var $customHeader;
  function setCurl($header = 0, $nobody = 0, $follow = 1, $retunResult = 1) {
    $this->headerData = $header;
    $this->bodyData = $nobody;
    $this->followLocation = $follow;
  }
  function enableHttpCode($status = false) {
    $this->enableHttpCode = $status;
  }
  function enablePost($status = false) {
    $this->enablePost = $status;
  }
  function dataPost($dataSend = null) {
    $this->dataPost = $dataSend;
  }
  function enableCookies($status = false, $name) {
    $this->enableCookies = $status;
    $this->cookiesName = $name;
  }
  function disableSSL($status = false) {
    $this->disableSSL = $status;
  }
  function curlFile($status = false) {
    $this->curlFile = $status;
  }
  function otherSet($status = false) {
    $this->otherSet = $status;
  }
  function other($status = false) {
    $this->other = $status;
  }
  function httpAuth($status = false, $user, $pass) {
    $this->httpAuth = $status;
    $this->httpUser = $user;
    $this->httpPass = $pass;
  }
  function proxy($status = false, $ipProxy) {
    $this->proxy = $status;
    $this->ipProxy = $ipProxy;
  }
  function timeOut($status = false, $time) {
    $this->timeOut = $status;
    $this->time = $time;
  }
  function customHeader($status = false, $newCustomHeader) {
    $this->customHeader = $status;
    $this->newCustomHeader = $newCustomHeader;
  }
  function goCurl($target, $agent = "Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)", $referral = 'http://www.google.com', $file = "") {
    $curl = curl_init();
    if ($this->customHeader) {
      curl_setopt($curl, CURLOPT_HTTPHEADER, $this->newCustomHeader);
    }
    if ($this->httpAuth) {
      curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
      curl_setopt($curl, CURLOPT_USERPWD, $this->httpUser . ":" . $this->httpPass);
    }
    if ($this->proxy) {
      curl_setopt($curl, CURLOPT_HTTPPROXYTUNNEL, 0);
      curl_setopt($curl, CURLOPT_PROXY, $this->ipProxy);
    }
    curl_setopt($curl, CURLOPT_URL, $target);
    curl_setopt($curl, CURLOPT_HEADER, $this->headerData);
    curl_setopt($curl, CURLOPT_NOBODY, $this->bodyData);
    curl_setopt($curl, CURLOPT_USERAGENT, $agent);
    if ($this->curlFile) {
      curl_setopt($curl, CURLOPT_BUFFERSIZE, 256);
      curl_setopt($curl, CURLOPT_FILE, $file);
    }
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, $this->followLocation);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_REFERER, $referral);
    curl_setopt($curl, CURLOPT_AUTOREFERER, 1);

    if ($this->timeOut) {
      curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 0);
      curl_setopt($curl, CURLOPT_TIMEOUT, $this->time);
    }
    if ($this->enablePost) {
      curl_setopt($curl, CURLOPT_POST, 1);
      curl_setopt($curl, CURLOPT_POSTFIELDS, $this->dataPost);
    }
    if ($this->enableCookies) {
      curl_setopt($curl, CURLOPT_COOKIEJAR, $this->cookiesName);
      curl_setopt($curl, CURLOPT_COOKIEFILE, $this->cookiesName);
    }
    if ($this->disableSSL) {
      curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
      curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
      curl_setopt($curl, CURLOPT_VERBOSE, 1);
    }
    $this->output[0] = curl_exec($curl);
    if ($this->enableHttpCode) {
      $this->output[1] = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    }
    if ($this->otherSet) {
      $this->output[2] = curl_getinfo($curl, CURLINFO_TOTAL_TIME);
      $this->output[3] = curl_getinfo($curl, CURLINFO_SPEED_DOWNLOAD);
      $this->output[4] = curl_getinfo($curl, CURLINFO_SPEED_UPLOAD);
      $this->output[5] = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
    }
    curl_close($curl);
    return $this->output;
  }

}

?>
