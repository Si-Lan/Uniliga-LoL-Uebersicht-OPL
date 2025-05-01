<?php
/** @var mysqli $dbcn  */

echo create_html_head_elements();

?>
<body class="home <?= is_light_mode(true)?>">
<?php

echo create_header($dbcn, home_button: FALSE);

?>
<main>
	<div id="turnier-select">
		<a href='/spieler' class="icon-link page-link">
            <?php echo "<span class='material-symbol icon-link-icon'>" . file_get_contents(dirname(__FILE__) . "/../icons/material/person.svg") . "</span>" ?>
            <span class="link-text">Spieler</span>
			<?php echo "<span class='material-symbol page-link-icon'>" . file_get_contents(dirname(__FILE__) . "/../icons/material/chevron_right.svg") . "</span>" ?>
        </a>
        <div id="turnier-liste">
		    <h2>Turniere</h2>
		<?php
		$local_img_path = "/img/tournament_logos";
		$logo_filename = is_light_mode() ? "logo_light.webp" : "logo.webp";
		$tournaments = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType = 'tournament' AND deactivated = FALSE ORDER BY dateStart DESC")->fetch_all(MYSQLI_ASSOC);
		foreach ($tournaments as $i=>$tournament) {
			if ($tournament["OPL_ID_logo"] == NULL) {
				$tournimg_url = "";
			} else {
				$tournimg_url = $local_img_path."/". $tournament['OPL_ID_logo']."/".$logo_filename;
			}

			$t_name_clean = preg_replace("/LoL\s/i","",$tournament["name"]);

			if ($i != 0) echo "<div class='divider'></div>";
			echo "
				<a href='/turnier/{$tournament["OPL_ID"]}' class=\"turnier-button {$tournament["OPL_ID"]}\">
					<img class='color-switch' alt src='$tournimg_url'>
					<span>$t_name_clean</span>
				</a>";
		}
		$dbcn->close();
		?>
        </div>
	</div>
</main>

</body>