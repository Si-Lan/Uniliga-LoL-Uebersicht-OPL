<?php

namespace App\Domain\Entities\ValueObjects;

class RankMapper {
    private const array TIERS = [
        'IRON' => 1,
        'BRONZE' => 5,
        'SILVER' => 9,
        'GOLD' => 13,
        'PLATINUM' => 17,
        'EMERALD' => 21,
        'DIAMOND' => 25,
    ];
    private const array DIVISIONS = [
        'IV' => 0,
        'III' => 1,
        'II' => 2,
        'I' => 3
    ];
    private const array APEX_TIERS = [
        'MASTER' => [29, 6],
        'GRANDMASTER' => [35, 3],
        'CHALLENGER' => [38, 2]
    ];


    public static function getValue(Rank $rank): int {
        if (is_null($rank->rankTier)) return 0;
        $rankTier = strtoupper($rank->rankTier);

        if (isset(self::APEX_TIERS[$rankTier])) {
            return self::APEX_TIERS[$rankTier][0] + floor(self::APEX_TIERS[$rankTier][1]/2);
        }
        if (isset(self::TIERS[$rankTier]) && isset(self::DIVISIONS[$rank->rankDiv])) {
            return self::TIERS[$rankTier] + self::DIVISIONS[$rank->rankDiv];
        }

        return 0;
    }

    public static function fromValue(int|float $value): Rank {
        foreach (self::TIERS as $tier => $baseValue) {
            if ($value >= $baseValue && $value < $baseValue + 4 ) {
                $divisionOffset = floor($value) - $baseValue;
                $division = array_search($divisionOffset, self::DIVISIONS);
                return new Rank($tier, $division);
            }
        }
        foreach (self::APEX_TIERS as $tier => [$baseValue, $spread]) {
            if ($value >= $baseValue && $value < $baseValue + $spread ) {
                return new Rank($tier, null);
            }
        }

        return new Rank("UNRANKED", null);
    }

    public static function compare(Rank $rank1, Rank $rank2): int {
        $value1 = self::getValue($rank1);
        $value2 = self::getValue($rank2);

        return $value1 <=> $value2;
    }
}