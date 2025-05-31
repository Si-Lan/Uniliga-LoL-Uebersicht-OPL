<?php

namespace App\UI\Components\UI;

use App\Core\Utilities\UserContext;
use App\UI\Components\Helpers\IconRenderer;

class SummonerCardCollapseButton {
	public function __construct() {}
	public function render(): string {
		ob_start();
		if (UserContext::summonerCardCollapsed()) {
			echo "<button type='button' class='exp_coll_sc'>".IconRenderer::getMaterialIconDiv('unfold_more')."Stats ein</button>";
		} else {
			echo "<button type='button' class='exp_coll_sc'>".IconRenderer::getMaterialIconDiv('unfold_less')."Stats aus</button>";
		}
		return ob_get_clean();
	}
	public function __toString(): string {
		return $this->render();
	}
}