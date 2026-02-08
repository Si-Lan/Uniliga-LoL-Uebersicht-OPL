<?php
/** @var \App\UI\Components\UI\PageLink $this */
?>
<a href="<?=$this->href?>" class="<?=$this->classes?>" <?= $this->newTab ? 'target="_blank"' : ''?>>
	<?php if($this->materialIcon !== null): ?>
		<?= \App\UI\Components\Helpers\IconRenderer::getMaterialIconSpan($this->materialIcon,['icon-link-icon']) ?>
	<?php endif; ?>
    <span class="link-text"><?=$this->text?></span>
	<?php if($this->linkIcon): ?>
		<?= \App\UI\Components\Helpers\IconRenderer::getMaterialIconSpan($this->linkIcon,["page-link-icon"])?>
	<?php endif; ?>
</a>