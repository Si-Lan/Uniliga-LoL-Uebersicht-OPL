<?php
/** @var mysqli $dbcn  */

use App\Components\Navigation\Header;
use App\Enums\HeaderType;
use App\Page\PageMeta;
use App\Repositories\PlayerRepository;

$playerRepo = new PlayerRepository();

$player = $playerRepo->findById($_GET["player"]);

$pageMeta = new PageMeta($player->name, bodyClass: 'player');

?>

<?= new Header(HeaderType::PLAYERS) ?>

<div class='main-content'>
    <?= create_player_overview($dbcn,$player->id, true) ?>
</div>