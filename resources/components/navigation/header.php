<?php
/** @var \App\Enums\HeaderType $type */
/** @var \App\Entities\Tournament|null $tournament */

use App\Components\Helpers\IconRenderer;
use App\Utilities\UserContext;

?>

<?php if (UserContext::isMaintenanceMode()):?>
    <div style='text-align: center; padding: 5px 0; background-color: #7e1616'>Achtung: Wartungsmodus ist aktiviert!</div>
<?php endif; ?>

<header class='<?= $type->value ?>'>
	<?php if ($type->showHomeButton()): ?>
		<a href='/' class='button material-symbol'><?= IconRenderer::getMaterialIcon('home')?></a>
	<?php endif; ?>
	<?php if ($type->value == 'home'): ?>
		<h1><?= $type->getTitle($tournament) ?></h1>
	<?php endif; ?>

	<?php if ($type->showSearchBar()): ?>
		<div class='searchbar'>
			<span class='material-symbol search-icon' title='Suche'>
				<?= IconRenderer::getMaterialIcon('search')?>
			</span>
			<input class='search-all deletable-search' placeholder='Suche' type='search'>
			<button class='material-symbol search-clear' title='Suche leeren'>
				<?= IconRenderer::getMaterialIcon('close')?>
			</button>
		</div>
	<?php endif; ?>

	<?php if ($type->value != 'home'): ?>
		<h1 class="tournament-title"><?= $type->getTitle($tournament) ?></h1>
	<?php endif; ?>

	<button type='button' class='material-symbol settings-button'><?= IconRenderer::getMaterialIcon('tune')?></button>

    <div class='settings-menu'>
        <a class='settings-option toggle-mode' href=''><?= IconRenderer::getMaterialIconDiv((UserContext::isLightMode()?'light':'dark').'_mode')?></a>
        <?php if (UserContext::isLoggedIn()): ?>
            <a class='settings-option opl-write' href='/admin'>Admin<?= IconRenderer::getMaterialIconDiv('edit_square')?></a>
            <a class='settings-option rgapi-write' href='/admin/rgapi'>RGAPI<?= IconRenderer::getMaterialIconDiv('videogame_asset')?></a>
        	<a class='settings-option ddragon-write' href='/admin/ddragon'>DDragon<?= IconRenderer::getMaterialIconDiv('photo_library')?></a>
    	    <a class='settings-option update-log' href='/admin/updates'>Update-Logs</a>
        	<a class='settings-option logout' href='?logout'>Logout<?= IconRenderer::getMaterialIconDiv('logout')?></a>
    <?php else: ?>
	        <a class='settings-option github-link' href='https://github.com/Si-Lan/Uniliga-LoL-Uebersicht-OPL' target='_blank'>GitHub<?= IconRenderer::getGithubIconDiv()?></a>
    	    <a class='settings-option' href='https://ko-fi.com/silencelol' target='_blank'>Spenden<?= IconRenderer::getMaterialIconDiv('payments')?></a>
        	<a class='settings-option feedback' href=''>Feedback<?= IconRenderer::getMaterialIconDiv('mail')?></a>
        	<a class='settings-option login' href='?login'>Login<?= IconRenderer::getMaterialIconDiv('login')?></a>
    <?php endif; ?>
    </div>
</header>

<?php $classes = implode(' ', array_filter(['dismissable-popup' , $type->autoOpenLogin() ? "modalopen_auto" : ""])); ?>
<dialog id='login-dialog' class='<?= $classes ?>'>
    <div class='dialog-content'>
        <button class='close-popup'><span class='material-symbol'><?= IconRenderer::getMaterialIconDiv('close')?></span></button>
        <div class='close-button-space'></div>
        <div style='display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 40px'>
            <form action='<?= strtok($_SERVER['REQUEST_URI'],'?')?>?login' method='post' style='display: flex; flex-direction: column; align-items: center; gap: 1em;'>
            <label class='password-label'><input type='password' name='keypass' id='keypass' placeholder='Password' /></label>
            <?= $type->passwordText() ?>
            <input type='submit' id='submit' value='Login' />
            </form>
        </div>
    </div>
</dialog>