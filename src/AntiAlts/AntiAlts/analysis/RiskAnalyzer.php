<?php

declare(strict_types=1);

namespace Lunaria\AntiAlts\analysis;

use Lunaria\AntiAlts\Main;

final class RiskAnalyzer{
    public function __construct(private Main $plugin){}

    /** @param array<string,array<string,string>> $matches @return array{score:int,accounts:array<string,array<string,string>>,matchCount:int} */
    public function calculate(array $matches) : array{
        $weights = $this->plugin->getConfig()->getNested("risk.weights", []);
        $scores = []; $totalKinds = [];
        $bannedAlt = false;
        foreach($matches as $name => $identifiers){
            $score = 0;
            if($this->plugin->getServer()->getNameBans()->isBanned(strtolower($name))){
                $score += 35;
                $bannedAlt = true;
            }
            foreach(array_keys($identifiers) as $kind){
                $score += (int) ($weights[$kind] ?? 0);
                $totalKinds[$kind] = true;
            }
            $scores[$name] = min(100, $score);
        }
        $count = count($totalKinds);
        $high = (int) $this->plugin->getConfig()->getNested("settings.high-risk-matches", 3);
        $bonus = $count >= $high ? (int) $this->plugin->getConfig()->getNested("risk.multi-match-bonus", 20) : 0;
        $max = $scores === [] ? 0 : max($scores);
        return ["score" => min(100, $max + $bonus), "accounts" => $matches, "matchCount" => $count, "bannedAlt" => $bannedAlt];
    }
}