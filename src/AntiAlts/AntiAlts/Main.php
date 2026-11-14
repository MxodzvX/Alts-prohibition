<?php

declare(strict_types=1);

namespace AntiAlts\AntiAlts;

use AntiAlts\AntiAlts\alert\AlertManager;
use AntiAlts\AntiAlts\analysis\RiskAnalyzer;
use AntiAlts\AntiAlts\command\AltsCommand;
use AntiAlts\AntiAlts\player\PlayerDataCollector;
use AntiAlts\AntiAlts\storage\AltStorage;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\plugin\PluginBase;

final class Main extends PluginBase implements Listener{
    private AltStorage $storage;
    private PlayerDataCollector $collector;
    private RiskAnalyzer $analyzer;
    private AlertManager $alerts;

    protected function onEnable() : void{
        @mkdir($this->getDataFolder());
        $this->saveDefaultConfig();
        $this->storage = new AltStorage($this->getDataFolder() . "alts.sqlite");
        $this->collector = new PlayerDataCollector($this);
        $this->analyzer = new RiskAnalyzer($this);
        $this->alerts = new AlertManager($this);
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getServer()->getCommandMap()->register("antialts", new AltsCommand($this));
        $this->getLogger()->info("AntiAlts + AntiSpoofer habilitado.");
    }

    protected function onDisable() : void{
        if(isset($this->storage)) $this->storage->close();
    }

    public function onJoin(PlayerJoinEvent $event) : void{
        $player = $event->getPlayer();
        $data = $this->collector->collect($player);
        $this->storage->savePlayer($player->getName(), $data);
        $matches = $this->storage->findMatches($data, $player->getName());
        $risk = $this->analyzer->calculate($matches);
        if($this->getConfig()->getNested("settings.alert-on-join", true) && $risk["score"] >= (int) $this->getConfig()->getNested("settings.risk-threshold", 60)){
            $this->alerts->send($player->getName(), $risk);
        }
    }

    public function getStorage() : AltStorage{ return $this->storage; }
    public function getCollector() : PlayerDataCollector{ return $this->collector; }
    public function getAnalyzer() : RiskAnalyzer{ return $this->analyzer; }
    public function getAlerts() : AlertManager{ return $this->alerts; }
}