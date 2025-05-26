
<?php
$pageMeta = new \App\UI\Page\PageMeta('Wartung',bodyClass: 'error');
?>
<?= new \App\UI\Components\Navigation\Header(\App\UI\Enums\HeaderType::MAINTENANCE)?>

<div style='text-align: center'>Die Webseite wird gerade gewartet, versuche es bitte später erneut!</div>