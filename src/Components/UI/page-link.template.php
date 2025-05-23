<?php
/** @var \App\Components\UI\PageLink $this */
?>
<a href="<?=$this->href?>" class="<?=$this->classes?>">
    <?php if($this->materialIcon !== null): ?>
        <?= \App\Components\Helpers\IconRenderer::getMaterialIconSpan($this->materialIcon,['icon-link-icon']) ?>
    <?php endif; ?>
	<span class="link-text"><?=$this->text?></span>
    <?php if($this->linkIcon): ?>
        <?=\App\Components\Helpers\IconRenderer::getMaterialIconSpan($this->linkIcon,["page-link-icon"])?>
    <?php endif; ?>
</a>