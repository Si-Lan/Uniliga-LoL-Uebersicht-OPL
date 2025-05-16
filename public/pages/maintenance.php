
<?php
$pageMeta = new \App\Page\PageMeta('Wartung',bodyClass: 'error');
?>
<?= new \App\Components\Navigation\Header(\App\Enums\HeaderType::MAINTENANCE)?>

<div style='text-align: center'>Die Webseite wird gerade gewartet, versuche es bitte später erneut!</div>