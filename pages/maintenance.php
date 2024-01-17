<?php
include_once __DIR__."/../functions/fe-functions.php";

$pass = check_login();
?>
<!DOCTYPE html>
<html lang="de">
<?php
$lightmode = is_light_mode(true);

echo create_html_head_elements();

?>
<body class="error <?php echo $lightmode?>">
<?php

$pass_wrong = $pass ? "" : "Falsches Passwort";

echo create_header(title: "maintenance", home_button: false, open_login: !$pass, loginforminfo: $pass_wrong, search_button: false);
echo "<div style='text-align: center'>Die Webseite wird gerade gewartet, versuche es bitte später erneut!</div>";
?>
</body>
</html>