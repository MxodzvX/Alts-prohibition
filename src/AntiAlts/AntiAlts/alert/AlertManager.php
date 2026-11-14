<?php

declare(strict_types=1);

namespace Lunaria\AntiAlts\alert;

use Lunaria\AntiAlts\Main;
use pocketmine\player\Player;

final class AlertManager{
    public function __construct(private Main $plugin){}
    /** @param array{score:int,accounts:array<string,array<string,string>>,matchCount:int} $risk */
    public function send(string $player, array $risk) : void{
        $format = (string) $this->plugin->getConfig()->getNested("alerts.format", "{player}: {risk}");
        $message = str_replace(["{player}", "{risk}", "{matches}"], [$player, (string) $risk["score"], (string) $risk["matchCount"]], $format);
        foreach($this->plugin->getServer()->getOnlinePlayers() as $staff){
            if($staff->hasPermission("antialts.alerts")) $staff->sendMessage($message);
        }
    }
}