<?php
require_once('config.php');

if($USE_CAPTCHA) {
  require_once('recaptchalib.php');

  $challenge = $_GET['recaptcha_challenge_field'];
  if(!isset($challenge)) { $challenge = $_POST['recaptcha_challenge_field']; }

  $response = $_GET['recaptcha_response_field'];
  if(!isset($response)) { $response = $_POST['recaptcha_response_field']; }
}

$api_key = $_GET['api_key'];
if(!isset($api_key)) { $api_key = $_POST['api_key']; }
$pin = $_GET['pin'];
if(!isset($pin)) { $pin = $_POST['pin']; }

if(!isset($api_key) && isset($pin)) {
  if(!$USE_CAPTCHA) {
    $api_key = $PINS[$pin];
  } else if(isset($challenge) && isset($response)) {
    $check = recaptcha_check_answer($CAPTCHA_PRIV,$_SERVER['REMOTE_ADDR'],$challenge,$response);
    if(!$check->is_valid) { die("Bad CAPTCHA response"); }
    $api_key = $PINS[$pin];
  }
}

if(isset($api_key)) {
  if(isset($_FILES['media'])) {
    if(!$API_KEYS[$api_key] || !in_array("media",$API_KEYS[$api_key])) { die(); }
    if($VERIFY_OAUTH) {
      if(!isset($_SERVER['HTTP_X_VERIFY_CREDENTIALS_AUTHORIZATION']) || !isset($_SERVER['HTTP_X_AUTH_SERVICE_PROVIDER'])) { die(); }
      $ci = curl_init();
      curl_setopt($ci, CURLOPT_HTTPHEADER, array("Authorization: ".$_SERVER['HTTP_X_VERIFY_CREDENTIALS_AUTHORIZATION']));
      curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, 1);
      curl_setopt($ci, CURLOPT_TIMEOUT, 300);
      curl_setopt($ci, CURLOPT_HEADER, 0);
      curl_setopt($ci, CURLOPT_URL, $_SERVER['HTTP_X_AUTH_SERVICE_PROVIDER']);
      if(!curl_exec($ci)) { die(); }
      $code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
      if($code < 200 || $code >= 300) { die(); }
    }
    $conn = make_conn(); if(!$conn) { die(); }
    $key = next_key($conn); if(!$key) { die(); }
    if(!file_exists($_FILES['media']['tmp_name'])) { die(); }
    $n = 1;
    while(file_exists("media/".$key['code'].($n==1?"":"_".$n))) { $n += 1; }
    move_uploaded_file($_FILES['media']['tmp_name'],"media/".$key['code'].($n==1?"":"_".$n));
    mysql_query("insert into media (name,path,type,size) values ('".s($_FILES['media']['name'])."','".s("media/".$key['code'].($n==1?"":"_".$n))."','".s($_FILES['media']['type'])."','".s($_FILES['media']['size'])."')");
    $mid = mysql_insert_id();
    mysql_query("update mapping set type='MEDIA',iid='".s($mid)."' where id='".s($key['id'])."'",$conn);
    foreach($_POST as $name => $value) {
      if(in_array($name,array("source","message"))) {
        mysql_query("insert into mattr (mid,name,value) values ('".s($mid)."','".s($name)."','".s($value)."')",$conn);
      }
    }
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\r\n";
    echo "<rsp status=\"ok\">\r\n";
    echo "<mediaid>".$key['code']."</mediaid>\r\n";
    echo "<mediaurl>".$ROOT.$key['code']."</mediaurl>\r\n";
    echo "</rsp>\r\n";
  } else if(isset($_GET['url'])) {
    if(!$API_KEYS[$api_key] || !in_array("short",$API_KEYS[$api_key])) { die(); }
    $conn = make_conn(); if(!$conn) { die(); }
    $key = next_key($conn); if(!$key) { die(); }
    mysql_query("insert into links (url) values ('".s($_GET['url'])."')",$conn);
    $lid = mysql_insert_id();
    mysql_query("update mapping set type='LINK',iid='".s($lid)."' where id='".s($key['id'])."'",$conn);
    echo $ROOT.$key['code']."\r\n";
  }
} else if(isset($_GET['key'])) {
  $conn = make_conn(); if(!$conn) { die(); }
  $rs = mysql_query("select type,iid from mapping where code='".s($_GET['key'])."' limit 1",$conn);
  $row = mysql_fetch_array($rs);
  if(!$row || $row['iid'] == 0) {
    header("HTTP/1.1 404 Not Found");
    die("The requested content could not be found.\r\n");
  }
  if($row['type'] == 'LINK') {
    $rs = mysql_query("select url from links where id='".s($row['iid'])."'",$conn);
    if($row = mysql_fetch_array($rs)) {
      header("Location: ".$row['url']); die();
    } else {
      header("HTTP/1.1 500 Internal Server Error");
      die("Unexpected error while retrieving URL");
    }
  } else if($row['type'] == 'MEDIA') {
    $rs = mysql_query("select path,type,size from media where id='".s($row['iid'])."'",$conn);
    if($row = mysql_fetch_array($rs)) {
      if(!file_exists($row['path'])) { die(); }
      header("Content-Type: ".$row['type']);
      header("Content-Length: ".$row['size']);
      $f = fopen($row['path'],"r");
      fpassthru($f);
      fclose($f);
    } else {
      header("HTTP/1.1 500 Internal Server Error");
      die("Unexpected error while retrieving media");
    }
  } else {
    header("HTTP/1.1 500 Internal Server Error");
    die("Unexpected type");
  }
} else {
?>
Media Service and URL-Shortening Service

*Posting to Media Service
  POST /tiny.php?api_key|pin
    api_key|pin - The key or pin issued for the media service (required)
    media - The file (as an <input type="file" name="media">) (required)
    source - The service posting the tweet (optional)
    message - The associated message (optional)
  Response
<?php echo "    <"."?xml version=\"1.0\" encoding=\"UTF-8\" ?".">\r\n"; ?>
    <rsp status="ok">
    <mediaid>abc</mediaid>
    <mediaurl><?php echo $ROOT; ?>abc</mediaurl>
    </rsp>


*Getting a URL shortened
  GET /tiny.php?api_key|pin&url
    api_key|pin - The key or pin issued for the url service (required)
    url - The URL you wish to shorten (required)
  Response
    <?php echo $ROOT; ?>abc
<?php
}

function make_conn() {
  $conn = mysql_connect($GLOBALS['MYSQL_HOST'],$GLOBALS['MYSQL_USER'],$GLOBALS['MYSQL_PASS']) or die("Error: Unable to connect to server");
  mysql_select_db($GLOBALS['MYSQL_DB'],$conn) or die("Error: Unable to access database.");
  return $conn;
}

function next_key($conn) {
  $rs = mysql_query("select id,code from mapping where iid=0 order by rand() limit 1",$conn);
  return mysql_fetch_array($rs);  
}

function s($istr) {
  return mysql_escape_string($istr);
}
?>
