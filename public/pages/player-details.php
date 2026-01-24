<?php

use App\Domain\Repositories\PlayerRepository;
use App\UI\Components\Navigation\Header;
use App\UI\Components\Player\PlayerOverview;
use App\UI\Enums\HeaderType;
use App\UI\Page\PageMeta;

$playerRepo = new PlayerRepository();

$player = $playerRepo->findById($_GET["player"]);

$pageMeta = new PageMeta($player->name, bodyClass: 'player');

?>

<?= new Header(HeaderType::PLAYERS) ?>

<div class='main-content'>
    <?= new PlayerOverview($player, false) ?>
</div>