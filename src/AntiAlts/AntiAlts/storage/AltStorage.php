<?php

declare(strict_types=1);

namespace AntiAlts\AntiAlts\storage;

use SQLite3;

final class AltStorage{
    private SQLite3 $db;
    public function __construct(string $path){
        $this->db = new SQLite3($path);
        $this->db->exec("PRAGMA journal_mode=WAL;");
        $this->db->exec("PRAGMA synchronous=NORMAL;");
        $this->db->exec("CREATE TABLE IF NOT EXISTS players (name TEXT PRIMARY KEY COLLATE NOCASE, last_seen INTEGER NOT NULL)");
        $this->db->exec("CREATE TABLE IF NOT EXISTS identifiers (name TEXT NOT NULL COLLATE NOCASE, kind TEXT NOT NULL, value TEXT NOT NULL, PRIMARY KEY(name, kind, value))");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_identifiers_lookup ON identifiers(kind, value)");
    }

    /** @param array<string,string> $data */
    public function savePlayer(string $name, array $data) : void{
        $stmt = $this->db->prepare("INSERT INTO players(name,last_seen) VALUES(:name,:time) ON CONFLICT(name) DO UPDATE SET last_seen=:time");
        $stmt->bindValue(":name", $name, SQLITE3_TEXT);
        $stmt->bindValue(":time", time(), SQLITE3_INTEGER);
        $stmt->execute();
        foreach($data as $kind => $value){
            $value = hash("sha256", $value);
            $insert = $this->db->prepare("INSERT OR IGNORE INTO identifiers(name,kind,value) VALUES(:name,:kind,:value)");
            $insert->bindValue(":name", $name, SQLITE3_TEXT);
            $insert->bindValue(":kind", $kind, SQLITE3_TEXT);
            $insert->bindValue(":value", $value, SQLITE3_TEXT);
            $insert->execute();
        }
    }

    /** @param array<string,string> $data @return array<string,array<string,string>> */
    public function findMatches(array $data, string $exclude) : array{
        $matches = [];
        foreach($data as $kind => $value){
            $value = hash("sha256", $value);
            $stmt = $this->db->prepare("SELECT DISTINCT name FROM identifiers WHERE kind=:kind AND value=:value AND name<>:exclude");
            $stmt->bindValue(":kind", $kind, SQLITE3_TEXT);
            $stmt->bindValue(":value", $value, SQLITE3_TEXT);
            $stmt->bindValue(":exclude", $exclude, SQLITE3_TEXT);
            $result = $stmt->execute();
            while($row = $result->fetchArray(SQLITE3_ASSOC)) $matches[$row["name"]][$kind] = $kind;
        }
        return $matches;
    }

    /** @return array<string,array<string,string>> */
    public function findByName(string $name) : array{
        $stmt = $this->db->prepare("SELECT name FROM players WHERE name=:name COLLATE NOCASE LIMIT 1");
        $stmt->bindValue(":name", $name, SQLITE3_TEXT);
        $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
        if(!$row) return [];
        $stmt = $this->db->prepare("SELECT kind,value FROM identifiers WHERE name=:name COLLATE NOCASE");
        $stmt->bindValue(":name", $row["name"], SQLITE3_TEXT);
        $res = $stmt->execute();
        $own = [];
        while($r = $res->fetchArray(SQLITE3_ASSOC)) $own[$r["kind"]] = $r["value"];
        $out = [];
        foreach($own as $kind => $value){
            $lookup = $this->db->prepare("SELECT DISTINCT name FROM identifiers WHERE kind=:kind AND value=:value AND name<>:name");
            $lookup->bindValue(":kind", $kind, SQLITE3_TEXT);
            $lookup->bindValue(":value", $value, SQLITE3_TEXT);
            $lookup->bindValue(":name", $row["name"], SQLITE3_TEXT);
            $result = $lookup->execute();
            while($match = $result->fetchArray(SQLITE3_ASSOC)) $out[$match["name"]][$kind] = $kind;
        }
        return $out;
    }

    public function close() : void{ $this->db->close(); }
}