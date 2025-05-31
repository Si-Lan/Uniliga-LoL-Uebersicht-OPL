<?php

namespace App\UI\Page;

use App\Core\Utilities\UserContext;

class LayoutRenderer {
	public static function render(PageMeta $meta, string $content): void {
		?>
		<!DOCTYPE html>
		<html lang="de">
		<head>
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <link rel="icon" href="https://silence.lol/favicon-dark.ico" media="(prefers-color-scheme: dark)">
            <link rel="icon" href="https://silence.lol/favicon-light.ico" media="(prefers-color-scheme: light)">
			<title>
				<?= $meta->title ?>
			</title>
            <?php foreach($meta->css as $css): ?>
                <link rel="stylesheet" href="/assets/css/<?= $css ?>.css">
        	<?php endforeach; ?>
            <?php foreach(AssetManager::getCssFiles() as $css): ?>
                <link rel="stylesheet" href="<?= $css ?>">
            <?php endforeach; ?>
			<?php foreach($meta->js as $js): ?>
                <script src="/assets/js/<?= $js ?>.js"></script>
			<?php endforeach; ?>
			<?php foreach(AssetManager::getJsFiles() as $js): ?>
                <script src="<?= $js ?>"></script>
			<?php endforeach; ?>
            <meta property="og:site_name" content="Silence.lol | Uniliga LoL Übersicht">
            <meta property="og:title" content="<?= $meta->shortTitle ?>">
            <meta property="og:description" content="Turnierübersicht, Matchhistory und Statistiken zu Teams und Spielern für die League of Legends Uniliga">
            <meta property="og:image" content="https://silence.lol/storage/img/silence_s_logo_bg_250.png">
            <meta name="theme-color" content="#e7e7e7">
		</head>
        <?php $classes = implode(' ', array_filter([$meta->bodyClass, UserContext::getLightModeClass()])) ?>
        <body class="<?= $classes ?>">
        <?= $content ?>
        </body>
		</html>
<?php
	}
}