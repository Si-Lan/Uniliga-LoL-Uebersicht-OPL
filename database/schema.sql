CREATE TABLE `games` (
  `RIOT_matchID` varchar(20) NOT NULL,
  `matchdata` longtext DEFAULT NULL CHECK (json_valid(`matchdata`)),
  `played_at` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `games_in_tournament` (
  `RIOT_matchID` varchar(20) NOT NULL,
  `OPL_ID_tournament` int(11) NOT NULL,
  `OPL_ID_blueTeam` int(11) DEFAULT NULL,
  `OPL_ID_redTeam` int(11) DEFAULT NULL,
  `winningTeam` int(11) DEFAULT NULL,
  `not_ul_game` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `games_to_matches` (
  `RIOT_matchID` varchar(20) NOT NULL,
  `OPL_ID_matches` int(11) NOT NULL,
  `OPL_ID_blueTeam` int(11) DEFAULT NULL,
  `OPL_ID_redTeam` int(11) DEFAULT NULL,
  `opl_confirmed` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `local_patches` (
  `patch` varchar(10) NOT NULL,
  `data` tinyint(1) NOT NULL DEFAULT 0,
  `champion_webp` tinyint(1) NOT NULL DEFAULT 0,
  `item_webp` tinyint(1) NOT NULL DEFAULT 0,
  `spell_webp` tinyint(1) NOT NULL DEFAULT 0,
  `runes_webp` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `lol_ranked_splits` (
  `season` int(11) NOT NULL,
  `split` int(11) NOT NULL,
  `split_start` date NOT NULL,
  `split_end` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `matchups` (
  `OPL_ID` int(11) NOT NULL,
  `OPL_ID_tournament` int(11) NOT NULL,
  `OPL_ID_team1` int(11) DEFAULT NULL,
  `OPL_ID_team2` int(11) DEFAULT NULL,
  `team1Score` varchar(2) DEFAULT NULL,
  `team2Score` varchar(2) DEFAULT NULL,
  `plannedDate` datetime DEFAULT NULL,
  `playday` int(2) DEFAULT NULL,
  `bestOf` int(11) DEFAULT NULL,
  `played` tinyint(1) NOT NULL,
  `winner` int(11) DEFAULT NULL,
  `loser` int(11) DEFAULT NULL,
  `draw` tinyint(1) NOT NULL DEFAULT 0,
  `def_win` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `players` (
  `OPL_ID` int(11) NOT NULL,
  `name` text NOT NULL,
  `riotID_name` varchar(20) DEFAULT NULL,
  `riotID_tag` varchar(5) DEFAULT NULL,
  `summonerName` text DEFAULT NULL,
  `summonerID` text DEFAULT NULL,
  `PUUID` text DEFAULT NULL,
  `rank_tier` varchar(20) DEFAULT NULL,
  `rank_div` varchar(5) DEFAULT NULL,
  `rank_LP` int(11) DEFAULT NULL,
  `matches_gotten` longtext NOT NULL DEFAULT '[]' CHECK (json_valid(`matches_gotten`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `players_in_teams` (
  `OPL_ID_player` int(11) NOT NULL,
  `OPL_ID_team` int(11) NOT NULL,
  `removed` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `players_in_teams_in_tournament` (
  `OPL_ID_player` int(11) NOT NULL,
  `OPL_ID_team` int(11) NOT NULL,
  `OPL_ID_tournament` int(11) NOT NULL,
  `removed` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `players_season_rank` (
  `OPL_ID_player` int(11) NOT NULL,
  `season` int(11) NOT NULL,
  `split` int(11) NOT NULL,
  `rank_tier` varchar(20) DEFAULT NULL,
  `rank_div` varchar(5) DEFAULT NULL,
  `rank_LP` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `stats_players_in_tournaments` (
  `OPL_ID_player` int(11) NOT NULL,
  `OPL_ID_tournament` int(11) NOT NULL,
  `roles` longtext NOT NULL DEFAULT '{"top":0,"jungle":0,"middle":0,"bottom":0,"utility":0}' CHECK (json_valid(`roles`)),
  `champions` longtext NOT NULL DEFAULT '{}' CHECK (json_valid(`champions`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `stats_players_teams_tournaments` (
  `OPL_ID_player` int(11) NOT NULL,
  `OPL_ID_team` int(11) NOT NULL,
  `OPL_ID_tournament` int(11) NOT NULL,
  `roles` longtext NOT NULL DEFAULT '{"top":0,"jungle":0,"middle":0,"bottom":0,"utility":0}' CHECK (json_valid(`roles`)),
  `champions` longtext NOT NULL DEFAULT '{}' CHECK (json_valid(`champions`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `stats_teams_in_tournaments` (
  `OPL_ID_team` int(11) NOT NULL,
  `OPL_ID_tournament` int(11) NOT NULL,
  `champs_played` longtext DEFAULT NULL CHECK (json_valid(`champs_played`)),
  `champs_banned` longtext DEFAULT NULL CHECK (json_valid(`champs_banned`)),
  `champs_played_against` longtext DEFAULT NULL CHECK (json_valid(`champs_played_against`)),
  `champs_banned_against` longtext DEFAULT NULL CHECK (json_valid(`champs_banned_against`)),
  `games_played` int(11) DEFAULT NULL,
  `games_won` int(11) DEFAULT NULL,
  `avg_win_time` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `teams` (
  `OPL_ID` int(11) NOT NULL,
  `name` text NOT NULL,
  `shortName` varchar(20) DEFAULT NULL,
  `OPL_logo_url` varchar(50) DEFAULT NULL,
  `OPL_ID_logo` int(11) DEFAULT NULL,
  `last_logo_download` datetime DEFAULT NULL,
  `avg_rank_tier` varchar(20) DEFAULT NULL,
  `avg_rank_div` varchar(5) DEFAULT NULL,
  `avg_rank_num` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `teams_in_tournaments` (
  `OPL_ID_team` int(11) NOT NULL,
  `OPL_ID_group` int(11) NOT NULL,
  `standing` int(2) DEFAULT NULL,
  `played` int(3) DEFAULT NULL,
  `wins` int(2) DEFAULT NULL,
  `draws` int(2) DEFAULT NULL,
  `losses` int(2) DEFAULT NULL,
  `points` int(2) DEFAULT NULL,
  `single_wins` int(2) DEFAULT NULL,
  `single_losses` int(2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `teams_tournament_rank` (
  `OPL_ID_team` int(11) NOT NULL,
  `OPL_ID_tournament` int(11) NOT NULL,
  `second_ranked_split` tinyint(1) NOT NULL,
  `avg_rank_tier` varchar(20) DEFAULT NULL,
  `avg_rank_div` varchar(5) DEFAULT NULL,
  `avg_rank_num` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `team_logo_history` (
  `OPL_ID_team` int(11) NOT NULL,
  `dir_key` int(11) NOT NULL DEFAULT -1,
  `update_time` date NOT NULL,
  `diff_to_prev` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `team_name_history` (
  `OPL_ID_team` int(11) NOT NULL,
  `name` text NOT NULL,
  `shortName` varchar(20) DEFAULT NULL,
  `update_time` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tournaments` (
  `OPL_ID` int(11) NOT NULL,
  `OPL_ID_parent` int(11) DEFAULT NULL,
  `OPL_ID_top_parent` int(11) DEFAULT NULL,
  `name` text NOT NULL,
  `split` varchar(25) DEFAULT NULL,
  `season` int(11) DEFAULT NULL,
  `eventType` varchar(25) DEFAULT NULL,
  `format` varchar(20) DEFAULT NULL,
  `number` varchar(11) DEFAULT NULL,
  `numberRangeTo` varchar(11) DEFAULT NULL,
  `dateStart` date DEFAULT NULL,
  `dateEnd` date DEFAULT NULL,
  `OPL_logo_url` varchar(50) DEFAULT NULL,
  `OPL_ID_logo` int(11) DEFAULT NULL,
  `finished` tinyint(1) NOT NULL DEFAULT 0,
  `deactivated` tinyint(1) NOT NULL DEFAULT 0,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  `ranked_season` int(11) DEFAULT NULL,
  `ranked_split` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `updates_cron` (
  `OPL_ID_tournament` int(11) NOT NULL,
  `last_update` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `updates_user_group` (
  `OPL_ID_group` int(11) NOT NULL,
  `last_update` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `updates_user_matchup` (
  `OPL_ID_matchup` int(11) NOT NULL,
  `last_update` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `updates_user_team` (
  `OPL_ID_team` int(11) NOT NULL,
  `last_update` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



-- Indizes

ALTER TABLE `games`
  ADD PRIMARY KEY (`RIOT_matchID`);

ALTER TABLE `games_in_tournament`
  ADD PRIMARY KEY (`RIOT_matchID`,`OPL_ID_tournament`),
  ADD KEY `OPL_ID_blueTeam` (`OPL_ID_blueTeam`),
  ADD KEY `OPL_ID_redTeam` (`OPL_ID_redTeam`),
  ADD KEY `winningTeam` (`winningTeam`),
  ADD KEY `RIOT_matchID` (`RIOT_matchID`),
  ADD KEY `OPL_ID_tournament` (`OPL_ID_tournament`);

ALTER TABLE `games_to_matches`
  ADD PRIMARY KEY (`RIOT_matchID`,`OPL_ID_matches`),
  ADD KEY `RIOT_matchID` (`RIOT_matchID`),
  ADD KEY `OPL_ID_matches` (`OPL_ID_matches`),
  ADD KEY `OPL_ID_blueTeam` (`OPL_ID_blueTeam`),
  ADD KEY `OPL_ID_redTeam` (`OPL_ID_redTeam`);

ALTER TABLE `local_patches`
  ADD PRIMARY KEY (`patch`);

ALTER TABLE `lol_ranked_splits`
  ADD PRIMARY KEY (`season`,`split`);

ALTER TABLE `matchups`
  ADD PRIMARY KEY (`OPL_ID`),
  ADD KEY `OPL_ID_tournament` (`OPL_ID_tournament`),
  ADD KEY `OPL_ID_team1` (`OPL_ID_team1`),
  ADD KEY `OPL_ID_team2` (`OPL_ID_team2`),
  ADD KEY `loser` (`loser`),
  ADD KEY `winner` (`winner`);

ALTER TABLE `players`
  ADD PRIMARY KEY (`OPL_ID`);

ALTER TABLE `players_in_teams`
  ADD PRIMARY KEY (`OPL_ID_player`,`OPL_ID_team`),
  ADD KEY `OPL_ID_player` (`OPL_ID_player`),
  ADD KEY `OPL_ID_team` (`OPL_ID_team`);

ALTER TABLE `players_in_teams_in_tournament`
  ADD PRIMARY KEY (`OPL_ID_player`,`OPL_ID_team`,`OPL_ID_tournament`),
  ADD KEY `OPL_ID_player` (`OPL_ID_player`),
  ADD KEY `OPL_ID_team` (`OPL_ID_team`),
  ADD KEY `OP_ID_tournament` (`OPL_ID_tournament`);

ALTER TABLE `players_season_rank`
  ADD PRIMARY KEY (`OPL_ID_player`,`season`,`split`);

ALTER TABLE `stats_players_in_tournaments`
  ADD PRIMARY KEY (`OPL_ID_player`,`OPL_ID_tournament`),
  ADD KEY `OPL_ID_player` (`OPL_ID_player`),
  ADD KEY `OPL_ID_tournament` (`OPL_ID_tournament`);

ALTER TABLE `stats_players_teams_tournaments`
  ADD PRIMARY KEY (`OPL_ID_player`,`OPL_ID_team`,`OPL_ID_tournament`),
  ADD KEY `OPL_ID_player` (`OPL_ID_player`),
  ADD KEY `OPL_ID_team` (`OPL_ID_team`),
  ADD KEY `OPL_ID_tournament` (`OPL_ID_tournament`);

ALTER TABLE `stats_teams_in_tournaments`
  ADD PRIMARY KEY (`OPL_ID_team`,`OPL_ID_tournament`),
  ADD KEY `OPL_ID_team` (`OPL_ID_team`),
  ADD KEY `OPL_ID_tournament` (`OPL_ID_tournament`);

ALTER TABLE `teams`
  ADD PRIMARY KEY (`OPL_ID`);

ALTER TABLE `teams_in_tournaments`
  ADD PRIMARY KEY (`OPL_ID_team`,`OPL_ID_group`),
  ADD KEY `teamID` (`OPL_ID_team`),
  ADD KEY `tournamentID` (`OPL_ID_group`);

ALTER TABLE `teams_tournament_rank`
  ADD PRIMARY KEY (`OPL_ID_team`,`OPL_ID_tournament`,`second_ranked_split`),
  ADD KEY `OPL_ID_tournament` (`OPL_ID_tournament`);

ALTER TABLE `team_logo_history`
  ADD PRIMARY KEY (`OPL_ID_team`,`dir_key`),
  ADD KEY `OPL_ID_team` (`OPL_ID_team`);

ALTER TABLE `team_name_history`
  ADD PRIMARY KEY (`OPL_ID_team`,`update_time`),
  ADD KEY `OPL_ID_team` (`OPL_ID_team`);

ALTER TABLE `tournaments`
  ADD PRIMARY KEY (`OPL_ID`),
  ADD KEY `tournamentID` (`OPL_ID_parent`),
  ADD KEY `ranked_season` (`ranked_season`,`ranked_split`),
  ADD KEY `OPL_ID_top_parent` (`OPL_ID_top_parent`);

ALTER TABLE `updates_cron`
  ADD PRIMARY KEY (`OPL_ID_tournament`),
  ADD KEY `OPL_ID_tournament` (`OPL_ID_tournament`);

ALTER TABLE `updates_user_group`
  ADD PRIMARY KEY (`OPL_ID_group`),
  ADD KEY `OPL_ID_group` (`OPL_ID_group`);

ALTER TABLE `updates_user_matchup`
  ADD PRIMARY KEY (`OPL_ID_matchup`),
  ADD KEY `OPL_ID_matchup` (`OPL_ID_matchup`);

ALTER TABLE `updates_user_team`
  ADD PRIMARY KEY (`OPL_ID_team`),
  ADD KEY `OPL_ID_team` (`OPL_ID_team`);



-- Views

DROP VIEW IF EXISTS `events_groups`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `events_groups` AS
    SELECT `tournaments`.`OPL_ID` AS `OPL_ID`,
           `tournaments`.`OPL_ID_parent` AS `OPL_ID_parent`,
           `tournaments`.`OPL_ID_top_parent` AS `OPL_ID_top_parent`,
           `tournaments`.`name` AS `name`,
           `tournaments`.`split` AS `split`,
           `tournaments`.`season` AS `season`,
           `tournaments`.`eventType` AS `eventType`,
           `tournaments`.`format` AS `format`,
           `tournaments`.`number` AS `number`,
           `tournaments`.`numberRangeTo` AS `numberRangeTo`,
           `tournaments`.`dateStart` AS `dateStart`,
           `tournaments`.`dateEnd` AS `dateEnd`,
           `tournaments`.`OPL_logo_url` AS `OPL_logo_url`,
           `tournaments`.`OPL_ID_logo` AS `OPL_ID_logo`,
           `tournaments`.`finished` AS `finished`,
           `tournaments`.`deactivated` AS `deactivated`,
           `tournaments`.`archived` AS `archived`,
           `tournaments`.`ranked_season` AS `ranked_season`,
           `tournaments`.`ranked_split` AS `ranked_split`
    FROM `tournaments`
    WHERE `tournaments`.`eventType` = 'group' ;

DROP VIEW IF EXISTS `events_in_groupstage`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `events_in_groupstage` AS
    SELECT `tournaments`.`OPL_ID` AS `OPL_ID`,
           `tournaments`.`OPL_ID_parent` AS `OPL_ID_parent`,
           `tournaments`.`OPL_ID_top_parent` AS `OPL_ID_top_parent`,
           `tournaments`.`name` AS `name`,
           `tournaments`.`split` AS `split`,
           `tournaments`.`season` AS `season`,
           `tournaments`.`eventType` AS `eventType`,
           `tournaments`.`format` AS `format`,
           `tournaments`.`number` AS `number`,
           `tournaments`.`numberRangeTo` AS `numberRangeTo`,
           `tournaments`.`dateStart` AS `dateStart`,
           `tournaments`.`dateEnd` AS `dateEnd`,
           `tournaments`.`OPL_logo_url` AS `OPL_logo_url`,
           `tournaments`.`OPL_ID_logo` AS `OPL_ID_logo`,
           `tournaments`.`finished` AS `finished`,
           `tournaments`.`deactivated` AS `deactivated`,
           `tournaments`.`archived` AS `archived`,
           `tournaments`.`ranked_season` AS `ranked_season`,
           `tournaments`.`ranked_split` AS `ranked_split`
    FROM `tournaments`
    WHERE `tournaments`.`eventType` = 'group'
       OR (`tournaments`.`eventType` = 'league' AND `tournaments`.`format` = 'swiss');

DROP VIEW IF EXISTS `events_leagues`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `events_leagues` AS
    SELECT `tournaments`.`OPL_ID` AS `OPL_ID`,
           `tournaments`.`OPL_ID_parent` AS `OPL_ID_parent`,
           `tournaments`.`OPL_ID_top_parent` AS `OPL_ID_top_parent`,
           `tournaments`.`name` AS `name`,
           `tournaments`.`split` AS `split`,
           `tournaments`.`season` AS `season`,
           `tournaments`.`eventType` AS `eventType`,
           `tournaments`.`format` AS `format`,
           `tournaments`.`number` AS `number`,
           `tournaments`.`numberRangeTo` AS `numberRangeTo`,
           `tournaments`.`dateStart` AS `dateStart`,
           `tournaments`.`dateEnd` AS `dateEnd`,
           `tournaments`.`OPL_logo_url` AS `OPL_logo_url`,
           `tournaments`.`OPL_ID_logo` AS `OPL_ID_logo`,
           `tournaments`.`finished` AS `finished`,
           `tournaments`.`deactivated` AS `deactivated`,
           `tournaments`.`archived` AS `archived`,
           `tournaments`.`ranked_season` AS `ranked_season`,
           `tournaments`.`ranked_split` AS `ranked_split`
    FROM `tournaments`
    WHERE `tournaments`.`eventType` = 'league' ;

DROP VIEW IF EXISTS `events_playoffs`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `events_playoffs` AS
    SELECT `tournaments`.`OPL_ID` AS `OPL_ID`,
           `tournaments`.`OPL_ID_parent` AS `OPL_ID_parent`,
           `tournaments`.`OPL_ID_top_parent` AS `OPL_ID_top_parent`,
           `tournaments`.`name` AS `name`,
           `tournaments`.`split` AS `split`,
           `tournaments`.`season` AS `season`,
           `tournaments`.`eventType` AS `eventType`,
           `tournaments`.`format` AS `format`,
           `tournaments`.`number` AS `number`,
           `tournaments`.`numberRangeTo` AS `numberRangeTo`,
           `tournaments`.`dateStart` AS `dateStart`,
           `tournaments`.`dateEnd` AS `dateEnd`,
           `tournaments`.`OPL_logo_url` AS `OPL_logo_url`,
           `tournaments`.`OPL_ID_logo` AS `OPL_ID_logo`,
           `tournaments`.`finished` AS `finished`,
           `tournaments`.`deactivated` AS `deactivated`,
           `tournaments`.`archived` AS `archived`,
           `tournaments`.`ranked_season` AS `ranked_season`,
           `tournaments`.`ranked_split` AS `ranked_split`
    FROM `tournaments`
    WHERE `tournaments`.`eventType` = 'playoffs' ;

DROP VIEW IF EXISTS `events_tournaments`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `events_tournaments` AS
    SELECT `tournaments`.`OPL_ID` AS `OPL_ID`,
           `tournaments`.`OPL_ID_parent` AS `OPL_ID_parent`,
           `tournaments`.`OPL_ID_top_parent` AS `OPL_ID_top_parent`,
           `tournaments`.`name` AS `name`,
           `tournaments`.`split` AS `split`,
           `tournaments`.`season` AS `season`,
           `tournaments`.`eventType` AS `eventType`,
           `tournaments`.`format` AS `format`,
           `tournaments`.`number` AS `number`,
           `tournaments`.`numberRangeTo` AS `numberRangeTo`,
           `tournaments`.`dateStart` AS `dateStart`,
           `tournaments`.`dateEnd` AS `dateEnd`,
           `tournaments`.`OPL_logo_url` AS `OPL_logo_url`,
           `tournaments`.`OPL_ID_logo` AS `OPL_ID_logo`,
           `tournaments`.`finished` AS `finished`,
           `tournaments`.`deactivated` AS `deactivated`,
           `tournaments`.`archived` AS `archived`,
           `tournaments`.`ranked_season` AS `ranked_season`,
           `tournaments`.`ranked_split` AS `ranked_split`
    FROM `tournaments`
    WHERE `tournaments`.`eventType` = 'tournament' ;

DROP VIEW IF EXISTS `events_wildcards`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `events_wildcards` AS
    SELECT `tournaments`.`OPL_ID` AS `OPL_ID`,
           `tournaments`.`OPL_ID_parent` AS `OPL_ID_parent`,
           `tournaments`.`OPL_ID_top_parent` AS `OPL_ID_top_parent`,
           `tournaments`.`name` AS `name`,
           `tournaments`.`split` AS `split`,
           `tournaments`.`season` AS `season`,
           `tournaments`.`eventType` AS `eventType`,
           `tournaments`.`format` AS `format`,
           `tournaments`.`number` AS `number`,
           `tournaments`.`numberRangeTo` AS `numberRangeTo`,
           `tournaments`.`dateStart` AS `dateStart`,
           `tournaments`.`dateEnd` AS `dateEnd`,
           `tournaments`.`OPL_logo_url` AS `OPL_logo_url`,
           `tournaments`.`OPL_ID_logo` AS `OPL_ID_logo`,
           `tournaments`.`finished` AS `finished`,
           `tournaments`.`deactivated` AS `deactivated`,
           `tournaments`.`archived` AS `archived`,
           `tournaments`.`ranked_season` AS `ranked_season`,
           `tournaments`.`ranked_split` AS `ranked_split`
    FROM `tournaments`
    WHERE `tournaments`.`eventType` = 'wildcard' ;

DROP VIEW IF EXISTS `events_with_standings`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `events_with_standings` AS
    SELECT `tournaments`.`OPL_ID` AS `OPL_ID`,
           `tournaments`.`OPL_ID_parent` AS `OPL_ID_parent`,
           `tournaments`.`OPL_ID_top_parent` AS `OPL_ID_top_parent`,
           `tournaments`.`name` AS `name`,
           `tournaments`.`split` AS `split`,
           `tournaments`.`season` AS `season`,
           `tournaments`.`eventType` AS `eventType`,
           `tournaments`.`format` AS `format`,
           `tournaments`.`number` AS `number`,
           `tournaments`.`numberRangeTo` AS `numberRangeTo`,
           `tournaments`.`dateStart` AS `dateStart`,
           `tournaments`.`dateEnd` AS `dateEnd`,
           `tournaments`.`OPL_logo_url` AS `OPL_logo_url`,
           `tournaments`.`OPL_ID_logo` AS `OPL_ID_logo`,
           `tournaments`.`finished` AS `finished`,
           `tournaments`.`deactivated` AS `deactivated`,
           `tournaments`.`archived` AS `archived`,
           `tournaments`.`ranked_season` AS `ranked_season`,
           `tournaments`.`ranked_split` AS `ranked_split`
    FROM `tournaments`
    WHERE `tournaments`.`eventType` in ('group','playoffs','wildcard')
       OR (`tournaments`.`eventType` = 'league' AND `tournaments`.`format` = 'swiss');

DROP VIEW IF EXISTS `latest_team_logo_in_tournament`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `latest_team_logo_in_tournament` AS
    SELECT `tlh`.`OPL_ID_team` AS `OPL_ID_team`,
           `tr`.`OPL_ID` AS `OPL_ID_tournament`,
           `tlh`.`dir_key` AS `dir_key`
    FROM (
        `team_logo_history` `tlh`
            JOIN `tournaments` `tr`
            ON (
                (`tlh`.`update_time` < `tr`.`dateEnd` OR `tr`.`dateEnd` IS NULL)
                    AND `tr`.`eventType` = 'tournament'
                )
        )
    WHERE `tlh`.`update_time` = (
        SELECT max(`tlh2`.`update_time`)
        FROM `team_logo_history` `tlh2`
        WHERE `tlh2`.`OPL_ID_team` = `tlh`.`OPL_ID_team`
          AND (`tlh2`.`update_time` < `tr`.`dateEnd` OR `tr`.`dateEnd` IS NULL)
        );

DROP VIEW IF EXISTS `latest_team_name_in_tournament`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `latest_team_name_in_tournament` AS
    SELECT `tnh`.`OPL_ID_team` AS `OPL_ID_team`,
           `tr`.`OPL_ID` AS `OPL_ID_tournament`,
           `tnh`.`name` AS `name`
    FROM (
        `team_name_history` `tnh`
            JOIN `tournaments` `tr`
            ON (
                (`tnh`.`update_time` < `tr`.`dateEnd` OR `tr`.`dateEnd` IS NULL)
                    AND `tr`.`eventType` = 'tournament'
                )
        )
    WHERE `tnh`.`update_time` = (
        SELECT max(`tnh2`.`update_time`)
        FROM `team_name_history` `tnh2`
        WHERE `tnh2`.`OPL_ID_team` = `tnh`.`OPL_ID_team`
          AND (`tnh2`.`update_time` < `tr`.`dateEnd` OR `tr`.`dateEnd` is null)
        );

DROP VIEW IF EXISTS `teams_in_tournament_stages`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `teams_in_tournament_stages` AS
    SELECT `teams_in_tournaments`.`OPL_ID_team` AS `OPL_ID_team`,
           `teams_in_tournaments`.`OPL_ID_group` AS `OPL_ID_group`,
           `teams_in_tournaments`.`standing` AS `standing`,
           `teams_in_tournaments`.`played` AS `played`,
           `teams_in_tournaments`.`wins` AS `wins`,
           `teams_in_tournaments`.`draws` AS `draws`,
           `teams_in_tournaments`.`losses` AS `losses`,
           `teams_in_tournaments`.`points` AS `points`,
           `teams_in_tournaments`.`single_wins` AS `single_wins`,
           `teams_in_tournaments`.`single_losses` AS `single_losses`
    FROM `teams_in_tournaments` ;

DROP VIEW IF EXISTS `teams_season_rank_in_tournament`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `teams_season_rank_in_tournament` AS
    SELECT `ttr`.`OPL_ID_team` AS `OPL_ID_team`,
           `ttr`.`OPL_ID_tournament` AS `OPL_ID_tournament`,
           coalesce(`rs_next`.`season`,`t`.`ranked_season`) AS `season`,
           coalesce(`rs_next`.`split`,`t`.`ranked_split`) AS `split`,
           `ttr`.`avg_rank_tier` AS `avg_rank_tier`,
           `ttr`.`avg_rank_div` AS `avg_rank_div`,
           `ttr`.`avg_rank_num` AS `avg_rank_num`
    FROM (
        (
            `teams_tournament_rank` `ttr`
                JOIN `tournaments` `t`
                ON (`ttr`.`OPL_ID_tournament` = `t`.`OPL_ID`)
            )
            LEFT JOIN `lol_ranked_splits` `rs_next`
            ON (
                `ttr`.`second_ranked_split` = 1
                    AND (
                        `rs_next`.`season` > `t`.`ranked_season`
                            OR `rs_next`.`season` = `t`.`ranked_season`
                                   AND `rs_next`.`split` > `t`.`ranked_split`
                        )
                    AND !exists(
                    SELECT 1
                    FROM `lol_ranked_splits` `rs2`
                    WHERE (`rs2`.`season` > `t`.`ranked_season` OR `rs2`.`season` = `t`.`ranked_season` AND `rs2`.`split` > `t`.`ranked_split`)
                      AND (`rs2`.`season` < `rs_next`.`season` OR `rs2`.`season` = `rs_next`.`season` AND `rs2`.`split` < `rs_next`.`split`) LIMIT 1
                                )
                )
        )
    WHERE `ttr`.`second_ranked_split` = 0
       OR `rs_next`.`season` IS NOT NULL ;



-- Constraints

ALTER TABLE `games_in_tournament`
  ADD CONSTRAINT `games_in_tournament_ibfk_1` FOREIGN KEY (`RIOT_matchID`) REFERENCES `games` (`RIOT_matchID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `games_in_tournament_ibfk_2` FOREIGN KEY (`OPL_ID_tournament`) REFERENCES `tournaments` (`OPL_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `games_in_tournament_ibfk_3` FOREIGN KEY (`OPL_ID_blueTeam`) REFERENCES `teams` (`OPL_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `games_in_tournament_ibfk_4` FOREIGN KEY (`OPL_ID_redTeam`) REFERENCES `teams` (`OPL_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `games_in_tournament_ibfk_5` FOREIGN KEY (`winningTeam`) REFERENCES `teams` (`OPL_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `games_to_matches`
  ADD CONSTRAINT `games_to_matches_ibfk_1` FOREIGN KEY (`OPL_ID_matches`) REFERENCES `matchups` (`OPL_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `games_to_matches_ibfk_2` FOREIGN KEY (`RIOT_matchID`) REFERENCES `games` (`RIOT_matchID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `games_to_matches_ibfk_3` FOREIGN KEY (`OPL_ID_blueTeam`) REFERENCES `teams` (`OPL_ID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `games_to_matches_ibfk_4` FOREIGN KEY (`OPL_ID_redTeam`) REFERENCES `teams` (`OPL_ID`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `matchups`
  ADD CONSTRAINT `matchups_ibfk_1` FOREIGN KEY (`OPL_ID_tournament`) REFERENCES `tournaments` (`OPL_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `matchups_ibfk_2` FOREIGN KEY (`OPL_ID_team1`) REFERENCES `teams` (`OPL_ID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `matchups_ibfk_3` FOREIGN KEY (`OPL_ID_team2`) REFERENCES `teams` (`OPL_ID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `matchups_ibfk_4` FOREIGN KEY (`winner`) REFERENCES `teams` (`OPL_ID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `matchups_ibfk_5` FOREIGN KEY (`loser`) REFERENCES `teams` (`OPL_ID`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `players_in_teams`
  ADD CONSTRAINT `players_in_teams_ibfk_1` FOREIGN KEY (`OPL_ID_player`) REFERENCES `players` (`OPL_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `players_in_teams_ibfk_2` FOREIGN KEY (`OPL_ID_team`) REFERENCES `teams` (`OPL_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `players_in_teams_in_tournament`
  ADD CONSTRAINT `players_in_teams_in_tournament_ibfk_1` FOREIGN KEY (`OPL_ID_player`) REFERENCES `players` (`OPL_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `players_in_teams_in_tournament_ibfk_2` FOREIGN KEY (`OPL_ID_team`) REFERENCES `teams` (`OPL_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `players_in_teams_in_tournament_ibfk_3` FOREIGN KEY (`OPL_ID_tournament`) REFERENCES `tournaments` (`OPL_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `players_season_rank`
  ADD CONSTRAINT `players_season_rank_ibfk_1` FOREIGN KEY (`OPL_ID_player`) REFERENCES `players` (`OPL_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `stats_players_in_tournaments`
  ADD CONSTRAINT `stats_players_in_tournaments_ibfk_1` FOREIGN KEY (`OPL_ID_player`) REFERENCES `players` (`OPL_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `stats_players_in_tournaments_ibfk_2` FOREIGN KEY (`OPL_ID_tournament`) REFERENCES `tournaments` (`OPL_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `stats_players_teams_tournaments`
  ADD CONSTRAINT `stats_players_teams_tournaments_ibfk_1` FOREIGN KEY (`OPL_ID_player`) REFERENCES `players` (`OPL_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `stats_players_teams_tournaments_ibfk_2` FOREIGN KEY (`OPL_ID_team`) REFERENCES `teams` (`OPL_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `stats_players_teams_tournaments_ibfk_3` FOREIGN KEY (`OPL_ID_tournament`) REFERENCES `tournaments` (`OPL_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `stats_teams_in_tournaments`
  ADD CONSTRAINT `stats_teams_in_tournaments_ibfk_1` FOREIGN KEY (`OPL_ID_team`) REFERENCES `teams` (`OPL_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `stats_teams_in_tournaments_ibfk_2` FOREIGN KEY (`OPL_ID_tournament`) REFERENCES `tournaments` (`OPL_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `teams_in_tournaments`
  ADD CONSTRAINT `teams_in_tournaments_ibfk_1` FOREIGN KEY (`OPL_ID_group`) REFERENCES `tournaments` (`OPL_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `teams_in_tournaments_ibfk_2` FOREIGN KEY (`OPL_ID_team`) REFERENCES `teams` (`OPL_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `teams_tournament_rank`
  ADD CONSTRAINT `teams_tournament_rank_ibfk_1` FOREIGN KEY (`OPL_ID_tournament`) REFERENCES `tournaments` (`OPL_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `team_logo_history`
  ADD CONSTRAINT `team_logo_history_ibfk_1` FOREIGN KEY (`OPL_ID_team`) REFERENCES `teams` (`OPL_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `team_name_history`
  ADD CONSTRAINT `team_name_history_ibfk_1` FOREIGN KEY (`OPL_ID_team`) REFERENCES `teams` (`OPL_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `tournaments`
  ADD CONSTRAINT `tournamentID` FOREIGN KEY (`OPL_ID_parent`) REFERENCES `tournaments` (`OPL_ID`) ON DELETE SET NULL ON UPDATE SET NULL,
  ADD CONSTRAINT `tournaments_ibfk_1` FOREIGN KEY (`ranked_season`,`ranked_split`) REFERENCES `lol_ranked_splits` (`season`, `split`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `tournaments_ibfk_2` FOREIGN KEY (`OPL_ID_top_parent`) REFERENCES `tournaments` (`OPL_ID`) ON DELETE SET NULL ON UPDATE SET NULL;

ALTER TABLE `updates_cron`
  ADD CONSTRAINT `updates_cron_ibfk_1` FOREIGN KEY (`OPL_ID_tournament`) REFERENCES `tournaments` (`OPL_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `updates_user_group`
  ADD CONSTRAINT `updates_user_group_ibfk_1` FOREIGN KEY (`OPL_ID_group`) REFERENCES `tournaments` (`OPL_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `updates_user_matchup`
  ADD CONSTRAINT `updates_user_matchup_ibfk_1` FOREIGN KEY (`OPL_ID_matchup`) REFERENCES `matchups` (`OPL_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `updates_user_team`
  ADD CONSTRAINT `updates_user_team_ibfk_1` FOREIGN KEY (`OPL_ID_team`) REFERENCES `teams` (`OPL_ID`) ON DELETE CASCADE ON UPDATE CASCADE;