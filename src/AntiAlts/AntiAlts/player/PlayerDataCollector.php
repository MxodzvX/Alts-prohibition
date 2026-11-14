<?php

declare(strict_types=1);

namespace Lunaria\AntiAlts\player;

use Lunaria\AntiAlts\Main;
use pocketmine\player\Player;

final class PlayerDataCollector{
    public function __construct(private Main $plugin){}

    /** @return array<string, string> */
    public function collect(Player $player) : array{
        $checks = $this->plugin->getConfig()->get("checks", []);
        $data = [];
        $add = static function(string $key, string $value) use (&$data, $checks) : void{
            if(($checks[$key] ?? false) && $value !== "") $data[$key] = $value;
        };

        $add("ip", $player->getNetworkSession()->getIp());
        $add("xuid", $player->getXuid());
        $add("uuid", $player->getUniqueId()->toString());

        // PMMP guarda los datos de login adicionales en PlayerInfo::getExtraData().
        $extra = $player->getPlayerInfo()->getExtraData();
        $flat = $this->flatten($extra);
        $add("device-id", (string) ($flat["DeviceId"] ?? $flat["deviceId"] ?? ""));
        $add("client-random-id", (string) ($flat["ClientRandomId"] ?? $flat["clientRandomId"] ?? ""));
        $add("device-model", (string) ($flat["DeviceModel"] ?? $flat["deviceModel"] ?? ""));
        $os = $flat["DeviceOS"] ?? $flat["deviceOS"] ?? null;
        $add("device-os", $os === null ? "" : (string) $os);
        return $data;
    }

    /** @return array<string, mixed> */
    private function flatten(array $input) : array{
        $result = [];
        foreach($input as $key => $value){
            if(is_array($value)) $result += $this->flatten($value);
            else $result[(string) $key] = $value;
        }
        return $result;
    }
}