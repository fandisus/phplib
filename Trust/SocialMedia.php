<?php
namespace Trust;
class SocialMedia {
  public static $latestError;
  
  public static function ParseFBroot($fbAppId) { ?>
<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = 'https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v2.11&appId=<?=$fbAppId?>';
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>
  <?php }
  
  public static function EmbedFBPage($fbPageUri) { ?>
    <div class="fb-page" data-href="<?= $fbPageUri ?>" data-tabs="timeline" data-height="400" data-small-header="false" data-adapt-container-width="true" data-hide-cover="true" data-show-facepile="true"><blockquote cite="<?=$fbPageUri?>" class="fb-xfbml-parse-ignore"><a href="<?= $fbPageUri ?>">Facebook Page</a></blockquote></div>
  <?php }
  
  public static function EmbedTwit($twiterID) { ?>
    <a class="twitter-timeline" data-height="400" href="https://twitter.com/<?= $twiterID ?>?ref_src=twsrc%5Etfw">Tweets by <?= $twiterID ?></a> <script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>
  <?php }
  
  public static function EmbedYoutube($videoUri) {
    $videoid = self::GetYoutubeVideoId($videoUri);
    if ($videoid == null) return;
    ?>
    <iframe width="357" height="400" src="https://www.youtube.com/embed/<?=$videoid?>" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>
  <?php }
  
  public static function GetYoutubeVideoId($videoUri) {
    $parsed = parse_url($videoUri);
    //Kalau cuma id video yang dimasukin
    if (!isset($parsed['host']) && strlen($parsed['path']) == 11) return $videoUri;
    //Kalau link lengkap
    if ($parsed['host'] != 'www.youtube.com') { self::$latestError = 'Bukan yutub'; return null; }
    if ($parsed['path'] != '/watch') { self::$latestError = 'Url tidak punya tulisan watch'; return null; }
    parse_str($parsed['query'], $query);
    if (!isset($query['v'])) { self::$latestError = 'Parameter v tidak ditemukan'; return null; }
    if (strlen($query['v']) != 11) { self::$latestError = 'Jumlah karakter id bukan 11'; return null; }
    self::$latestError = null;
    return $query['v'];
  }
  public static function GetYoutubeTitleById($youtubeid) {
    $youtube = file_get_contents('http://youtube.com/get_video_info?video_id='.$youtubeid);
    parse_str($youtube, $arr);
    if (!isset($arr['title'])) return null;
    return $arr['title'];
  }
}