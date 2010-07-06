<?php
require_once('config.php');

if($USE_CAPTCHA) {
  require_once('recaptchalib.php');
}
?>
<html>
<head>
<title>Media Poster and Shortening Service</title>
<?php
if($USE_CAPTCHA) {
?>
<script type="text/javascript">
function doSubmit() {
  var fields = ["recaptcha_challenge_field", "recaptcha_response_field"];
  for(var i = 0; i < fields.length; i++) {
    var source = document.getElementById(fields[i]);
    if(!source || !source.value) {
      alert("Please complete the CAPTCHA first");
      return false;
    }
    var h = 0; var o;
    while(o = document.getElementById(fields[i]+(h++))) { o.value = source.value; }
  }
  return true;
}
</script>
<?php
}
?>
</head>
<body>
<h1>Media Poster and Shortening Service</h1>
<hr>
<form action="tiny.php" method="get" <?php if($USE_CAPTCHA) { echo "onsubmit=\"return doSubmit();\""; } ?>>
<?php
if($USE_CAPTCHA) {
?>
<input id="recaptcha_challenge_field0" type="hidden" name="recaptcha_challenge_field" value="">
<input id="recaptcha_response_field0" type="hidden" name="recaptcha_response_field" value="">
<?php
}
?>
PIN: <input type="password" name="pin" size="6"><br>
URL: <input type="text" name="url"><br>
<input type="submit" value="Submit">
</form>
<hr>
<form action="tiny.php" method="post" enctype="multipart/form-data" <?php if($USE_CAPTCHA) { echo "onsubmit=\"return doSubmit();\""; } ?>>
<?php
if($USE_CAPTCHA) {
?>
<input id="recaptcha_challenge_field1" type="hidden" name="recaptcha_challenge_field" value="">
<input id="recaptcha_response_field1" type="hidden" name="recaptcha_response_field" value="">
<?php
}
?>
PIN: <input type="password" name="pin" size="6"><br>
File: <input type="file" name="media"><br>
<input type="submit" value="Submit">
</form>
<hr>
<?php if($USE_CAPTCHA) { echo recaptcha_get_html($CAPTCHA_PUB); } ?>
</body>
</html>
