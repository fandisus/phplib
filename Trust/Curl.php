<?php
namespace Trust;
class Curl {
  //Note: Also see VCurl class at project Solaris.
  //Params is GET format.
  public static function Post($uri, $params) {
    //https://stackoverflow.com/questions/2138527/php-curl-http-post-sample-code?utm_medium=organic&utm_source=google_rich_qa&utm_campaign=google_rich_qa
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL,$uri);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $server_output = curl_exec ($ch);

    curl_close ($ch);
    return $server_output;
  }
  public static function TryConnect($uri) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_URL, $uri);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $out = curl_exec($ch);
    //https://www.saotn.org/php-curl-check-website-availability/
    $http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
    curl_close($ch);
    return $http_code; //200 = success, 0 = cant connect.
//      if ( ( $http_code == "200" ) || ( $http_code == "302" ) ) {
//        return true;
//      } else {
//        // return $http_code;, possible too
//        return false;
//      }
//    
//    return $out;
  }
}
