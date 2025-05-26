<?php

use App\UI\Components\UI\PageLinkWrapper;

$content =
	$this->teamLogoHtml
	.'<div class="team-name-rank">'
	.$this->teamNameTargetHtml
	.$this->ranksHtml
	.'</div>';

echo new PageLinkWrapper(
	href: $this->href,
	additionalClasses: ["standing-item", "team"],
	content: $content
);
