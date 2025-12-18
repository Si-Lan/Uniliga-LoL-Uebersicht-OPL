<?php

namespace App\Domain\Enums\Jobs;

enum UpdateJobAction: string {
	case UPDATE_TEAMS = 'update_teams';
	case UPDATE_PLAYERS = 'update_players';
	case UPDATE_MATCHES = 'update_matches';
	case UPDATE_RESULTS = 'update_results';
	case UPDATE_RIOTIDS_OPL = 'update_riotids_opl';
	case UPDATE_PUUIDS = 'update_puuids';
	case UPDATE_RIOTIDS_PUUIDS = 'update_riotids_puuid';
	case UPDATE_PLAYER_RANKS = 'update_player_ranks';
	case UPDATE_TEAM_RANKS = 'update_team_ranks';
	case UPDATE_GAMEDATA = 'update_gamedata';
    case UPDATE_PLAYER_STATS = 'update_player_stats';
    case UPDATE_TEAM_STATS = 'update_team_stats';
	case UPDATE_TEAM = 'update_team';
	case UPDATE_GROUP = 'update_group';
	case UPDATE_MATCH = 'update_match';
	case UPDATE_TOURNAMENT = 'update_tournament';
	case FULL_UPDATE = 'full_update';
	case DOWNLOAD_CHAMPION_IMAGES = 'download_champion_images';
	case DOWNLOAD_ITEM_IMAGES = 'download_item_images';
	case DOWNLOAD_SPELL_IMAGES = 'download_spell_images';
	case DOWNLOAD_RUNE_IMAGES = 'download_rune_images';
}
