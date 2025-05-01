<?php
include_once dirname(__DIR__,2)."/src/functions/fe-functions.php";

$pass = check_login();
?>
<!DOCTYPE html>
<html lang="de">
<?php

echo create_html_head_elements();

?>
<body class="error <?=is_light_mode(true)?>">
<?php

echo create_header(title: "maintenance", home_button: false, search_button: false);
echo "<div style='text-align: center'>Die Webseite wird gerade gewartet, versuche es bitte später erneut!</div>";
?>
</body>
</html>