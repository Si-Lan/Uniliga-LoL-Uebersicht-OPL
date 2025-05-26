<?php

use App\Components\Navigation\Header;
use App\Components\Player\PlayerOverview;
use App\Enums\HeaderType;
use App\Page\PageMeta;
use App\Repositories\PlayerRepository;

$playerRepo = new PlayerRepository();

$player = $playerRepo->findById($_GET["player"]);

$pageMeta = new PageMeta($player->name, bodyClass: 'player');

?>

<?= new Header(HeaderType::PLAYERS) ?>

<div class='main-content'>
    <?= new PlayerOverview($player, false) ?>
</div>