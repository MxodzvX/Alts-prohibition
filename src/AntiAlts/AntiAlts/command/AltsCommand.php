<?php

declare(strict_types=1);

namespace AntiAlts\AntiAlts\command;

use Lunaria\AntiAlts\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

final class AltsCommand extends Command{
    public function __construct(private Main $plugin){
        parent::__construct("alts", "Muestra cuentas relacionadas", "/alts <jugador>");
        $this->setPermission("antialts.command");
    }
    public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
        if(!$this->testPermission($sender)) return true;
        if(!isset($args[0])){ $sender->sendMessage("§cUso: /alts <jugador>"); return true; }
        $matches = $this->plugin->getStorage()->findByName($args[0]);
        if($matches === []){ $sender->sendMessage(str_replace("{player}", $args[0], (string) $this->plugin->getConfig()->getNested("messages.no-alts"))); return true; }
        $sender->sendMessage(str_replace("{player}", $args[0], (string) $this->plugin->getConfig()->getNested("messages.header")));
        foreach($matches as $name => $identifiers){
            $risk = $this->plugin->getAnalyzer()->calculate([$name => $identifiers]);
            $line = str_replace(["{name}", "{risk}", "{matches}"], [$name, (string) $risk["score"], (string) count($identifiers)], (string) $this->plugin->getConfig()->getNested("messages.entry"));
            $sender->sendMessage($line);
        }
        return true;
    }
}