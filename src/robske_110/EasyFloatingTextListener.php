<?php

namespace robske_110\EasyFloatingText;

use robske_110\Utils;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\math\Vector3;
use pocketmine\tile\Sign;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\Player;
use pocketmine\entity\Entity;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TF;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;

class EasyFloatingTextListener extends Listener
{

    public function onEnable()
    {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        @mkdir($this->getDataFolder());
        $this->floatingTextConfig = new Config($this->getDataFolder() . "FloatingText.yml", Config::YAML, array());
        $this->floatingTextConfig->save();
        $this->backupFTPIDConfig = new Config($this->getDataFolder() . "tempFTPID.yml", Config::YAML, array());
        $this->backupFTPIDConfig->save();
        //initial FTPcreation & parsing of config
        $this->IndexFTC = 0;
        foreach ($this->floatingTextConfig->getAll() as $configFT) {
            $configFT = $configFT[0];
            var_dump($configFT);
            $this->FloatingTexts[$this->IndexFTC] = new FloatingText($this, $this->getServer()->getLevelByName($configFT[0]), new Vector3($configFT[1], $configFT[2], $configFT[3]), $configFT[4]);
            $this->FloatingTexts[$this->IndexFTC]->update();
            $this->IndexFTC++;
        }
    }

    public function onBreak(BlockBreakEvent $event)
    {
        if(isset($this->BlockFTP[$event->getPlayer()->getName()])){
            $Block = $event->getBlock();
            $event->setCancelled();
            $level = $event->getPlayer()->getLevel()->getName();
            $pos1 = $Block->getX();
            $pos2 = $Block->getY();
            $pos3 = $Block->getZ();
            $pos2 = $pos2 + 2.75;
            $pos1 = $pos1 + 0.5;
            $pos3 = $pos3 + 0.5;
            $parsedTFArray = array([$level, $pos1, $pos2, $pos3, $this->BlockFTP[$event->getPlayer()->getName()]]);
            $this->updateAllFloatingTexts();
            $this->IndexFTC = 0;
            foreach ($this->floatingTextConfig->getAll() as $configFT) {
                $this->IndexFTC++;
            }
            $this->floatingTextConfig->set($this->IndexFTC, $parsedTFArray);
            $this->floatingTextConfig->save();
            unset($this->BlockFTP[$event->getPlayer()->getName()]);
            $this->updateAllFloatingTexts();
            $this->IndexFTC++;
            return true;
        }
    }

    private function truncate_float($number, $behindNumber) {
        $amp = pow(10, $behindNumber);
        if($number > 0){
            return floor($number * $amp) / $amp;
        }else{
            return ceil($number * $amp) / $amp;
        }
    }

    public function updateAllFloatingTexts($playerLevelArray = NULL){
        echo("updateAllFloatingTexts()");
        $this->hideAllFTPs();
        if($playerLevelArray == NULL){
            $this->showAllFTPs();
        }else{
            foreach($this->floatingTextConfig->getAll() as $configFT){
                $configFT = $configFT[0];
                $this->FloatingTexts[$this->IndexFTC] = new FloatingText($this, $this->getServer()->getLevelByName($configFT[0]), new Vector3($configFT[1], $configFT[2], $configFT[3]), $configFT[4]);
                if(isset($this->FloatingTexts)){
                    foreach($this->getServer()->getOnlinePlayers() as $player){
                        foreach($this->FloatingTexts as $FloatingTextObject){
                            if(!isset($playerLevelArray[$player->getName()])){
                                $playerLevel = $player->getLevel()->getName();
                            }else{
                                $playerLevel = $playerLevelArray[$player->getName()];
                            }
                            $FloatingTextLevel = $FloatingTextObject->getLevel()->getName();
                            //echo("Checking "."PlayerLevel: ".$playerLevel." FTPLevel: ".$FloatingTextLevel." PlayerName: " . $player->getName() . "\n");
                            if($playerLevel == $FloatingTextLevel){
                                $FloatingTextObject->update($player);
                                //echo("Re-Created "."PlayerLevel: ".$playerLevel." FTPLevel: ".$FloatingTextLevel." PlayerName: " . $player->getName() . "\n");
                            }
                        }
                    }
                }
                $this->IndexFTC++;
            }
            if($this->pFloatingTextsC != NULL){
            foreach($this->pFloatingTextsC as $configFT){
                $configFT = $configFT[0];
                if($configFT[4] != "" || $configFT[4] != NULL){
                    $this->FloatingTexts[$this->IndexFTC] = new FloatingText($this, $this->getServer()->getLevelByName($configFT[0]), new Vector3($configFT[1], $configFT[2], $configFT[3]), $configFT[4]);
                }   
                if(isset($this->pFloatingTexts)){
                    foreach($this->getServer()->getOnlinePlayers() as $player){
                        foreach($this->pFloatingTexts as $FloatingTextObject){
                            if(!isset($playerLevelArray[$player->getName()])){
                                $playerLevel = $player->getLevel()->getName();
                            }else{
                                $playerLevel = $playerLevelArray[$player->getName()];
                            }
                            $FloatingTextLevel = $FloatingTextObject->getLevel()->getName();
                            //echo("Checking "."PlayerLevel: ".$playerLevel." FTPLevel: ".$FloatingTextLevel." PlayerName: " . $player->getName() . "\n");
                            if($playerLevel == $FloatingTextLevel){
                                $FloatingTextObject->update($player);
                                //echo("Re-Created "."PlayerLevel: ".$playerLevel." FTPLevel: ".$FloatingTextLevel." PlayerName: " . $player->getName() . "\n");
                            }
                        }
                    }
                    $this->IndexpFTC++;
                }
            }
        }
        }
    }

    public function hideAllFTPs(){
        if($this->FloatingTexts != NULL){
            foreach($this->FloatingTexts as $FloatingTextObject){
                $FloatingTextObject->setInvisible(true);
            }
        }
        if($this->pFloatingTexts != NULL){
            foreach($this->pFloatingTexts as $FloatingTextObject){
                $FloatingTextObject->setInvisible(true);
            }
        }
        unset($this->FloatingTexts);
        unset($this->pFloatingTexts);
        $this->IndexFTC = 0;
        $this->IndexpFTC = 0;
    }

    public function showAllFTPs(){
        foreach($this->floatingTextConfig->getAll() as $configFT){
            $configFT = $configFT[0];
            $this->FloatingTexts[$this->IndexFTC] = new FloatingText($this, $this->getServer()->getLevelByName($configFT[0]), new Vector3($configFT[1], $configFT[2], $configFT[3]), $configFT[4]);
            if(isset($this->FloatingTexts)){
                foreach($this->getServer()->getOnlinePlayers() as $player){
                    foreach($this->FloatingTexts as $FloatingTextObject){
                        $playerLevel = $player->getLevel()->getName();
                        $FloatingTextLevel = $FloatingTextObject->getLevel()->getName();
                        //echo("Checking "."PlayerLevel: ".$playerLevel." FTPLevel: ".$FloatingTextLevel." PlayerName: " . $player->getName() . "\n");
                        if($playerLevel == $FloatingTextLevel){
                            $FloatingTextObject->update($player);
                            //echo("Re-Created "."PlayerLevel: ".$playerLevel." FTPLevel: ".$FloatingTextLevel." PlayerName: " . $player->getName() . "\n");
                        }
                    }
                }
            }
            $this->IndexFTC++;
        }
        if($this-> != NULL){
        foreach($this->pFloatingTextsC as $configFT){
            $configFT = $configFT[0];
            if($configFT[4] != "" || $configFT[4] != NULL){
                $this->FloatingTexts[$this->IndexFTC] = new FloatingText($this, $this->getServer()->getLevelByName($configFT[0]), new Vector3($configFT[1], $configFT[2], $configFT[3]), $configFT[4]);
            }
            if(isset($this->pFloatingTexts)){
                foreach($this->getServer()->getOnlinePlayers() as $player){
                    foreach($this->FloatingTexts as $FloatingTextObject){
                        $playerLevel = $player->getLevel()->getName();
                        $FloatingTextLevel = $FloatingTextObject->getLevel()->getName();
                        //echo("Checking "."PlayerLevel: ".$playerLevel." FTPLevel: ".$FloatingTextLevel." PlayerName: " . $player->getName() . "\n");
                        if($playerLevel == $FloatingTextLevel){
                            $FloatingTextObject->update($player);
                            //echo("Re-Created "."PlayerLevel: ".$playerLevel." FTPLevel: ".$FloatingTextLevel." PlayerName: " . $player->getName() . "\n");
                        }
                    }
                }
            }
            $this->IndexpFTC++;
        }
        }
    }

    public function onPlayerJoin(PlayerJoinEvent $event){
        $this->updateAllFloatingTexts();
    }

    //THIS IS FOR FUTURE VERSIONS, WHERE THE FTPs WILL BE CLICKABLE!
    /*
    public function onDamage(EntityDamageEvent $e){
        $entity = $e->getEntity();
        if($entity instanceof EasyFloatingTextEntity){
            $this->doOnClick($entity->namedtag->AssignedFTPid);
        }
    }
    */

    public function LevelChangeEvent(EntityLevelChangeEvent $event){
        if($event->getEntity() instanceof Player){
            $playerLevel[$event->getEntity()->getName()] = $event->getTarget()->getName();
            echo("Server_Core::LevelChange \n");
            $this->updateAllFloatingTexts($playerLevel);
        }
    }

    public function createNewFTP($levelName, $pos1, $pos2, $pos3, $Text){
        $parsedTFArray = array([$levelName, $pos1, $pos2, $pos3, $Text]);
        $ID = $this->IndexpFTC;
        $this->pFloatingTextsC[IndexpFTC] = $parsedTFArray;
        $this->updateAllFloatingTexts();
        $this->IndexpFTC++;
        return($ID);
    }

    public function removeFTP($ID){
        $oldpFTC = $this->pFloatingTextsC[IndexpFTC];
        $oldpFTC = $oldpFTC[0];
        $this->pFloatingTextsC[IndexpFTC] = array([$oldpFTC[1], $oldpFTC[2], $oldpFTC[3], $oldpFTC[4], ""]);
        $this->updateAllFloatingTexts();
        $this->IndexpFTC++;
    }

    public function changeText($ID, $newText){
        $oldpFTC = $this->pFloatingTextsC[IndexpFTC];
        $oldpFTC = $oldpFTC[0];
        $this->pFloatingTextsC[IndexpFTC] = array([$oldpFTC[1], $oldpFTC[2], $oldpFTC[3], $oldpFTC[4], $newText]);
        $this->updateAllFloatingTexts();
        $this->IndexpFTC++;
    }

    public function onCommand(CommandSender $sender, Command $command, $label, array $args){
        switch($command->getName()) {
            case "addFTP":
                if(isset($args[0]))
                {
                    $FinalText = $args[0];
                    $IndexArgS = 0;
                    $ReachedEndArgs = false;
                    while($ReachedEndArgs == false)
                    {
                        $IndexArgS++;
                        if(isset($args[$IndexArgS]))
                        {
                            $FinalText = $FinalText." ".$args[$IndexArgS];
                        }
                        else
                        {
                            $ReachedEndArgs = true;
                        }
                    }
                    $level = $sender->getLevel()->getName();
                    $pos1 = $sender->getX();
                    $pos2 = $sender->getY();
                    $pos3 = $sender->getZ();
                    $pos1 = $this->truncate_float($pos1, 0);
                    $pos2 = $this->truncate_float($pos2, 0);
                    $pos3 = $this->truncate_float($pos3, 0);
                    $pos2 = $pos2 + 1.75;
                    $pos1 = $pos1 + 0.5;
                    $pos3 = $pos3 + 0.5;
                    $parsedTFArray = array([$level, $pos1, $pos2, $pos3, $FinalText]);
                    $this->IndexFTC = 0;
                    foreach ($this->floatingTextConfig->getAll() as $configFT) {
                        $this->IndexFTC++;
                    }
                    echo("Writing to FTID:"); 
                    var_dump($this->IndexFTC);
                    $this->floatingTextConfig->set($this->IndexFTC, $parsedTFArray);
                    $this->floatingTextConfig->save();
                    $this->updateAllFloatingTexts();
                    $this->IndexFTC++;
                    return true;
                }
            break;
            case "remFTP":
                if(isset($args[0]))
                {
                    $this->floatingTextConfig->remove($args[0]);
                    $this->floatingTextConfig->save();
                    $this->updateAllFloatingTexts();
                    $this->IndexFTC--;
                    return true;
                }
            break;
            case "blockFTP":
                if(isset($args[0])) {
                    $FinalText = $args[0];
                    $IndexArgS = 0;
                    $ReachedEndArgs = false;
                    while ($ReachedEndArgs == false) {
                        $IndexArgS++;
                        if (isset($args[$IndexArgS])) {
                            $FinalText = $FinalText . " " . $args[$IndexArgS];
                        } else {
                            $ReachedEndArgs = true;
                        }
                    }
                    $this->BlockFTP[$sender->getName()] = $FinalText;
                    var_dump($this->BlockFTP);
                    return true;
                }
            break;
            case "ShowFTP":
                $this->showAllFTPs();
                return true;
            break;
            case "HideFTP":
                $this->hideAllFTPs();
                return true;
            break;
            case "ShowFTPID":
                $this->hideAllFTPs();
                $tempIndexFTC = 0;
                foreach ($this->floatingTextConfig->getAll() as $configFT) {
                    $tempIndexFTC++;
                }
                $this->backupFTPIDConfig = new Config($this->getDataFolder() . "tempFTPID.yml", Config::YAML, array());
                $this->backupFTPIDConfig->save();
                for($tempReadoutPos = 0; $tempReadoutPos < $tempIndexFTC; $tempReadoutPos++){
                    $this->backupFTPIDConfig->set($tempReadoutPos, $this->floatingTextConfig->get($tempReadoutPos));
                    $this->backupFTPIDConfig->save();
                }
                for($tempReadoutPos = 0; $tempReadoutPos < $tempIndexFTC; $tempReadoutPos++){
                    $configFT = $this->floatingTextConfig->get($tempReadoutPos);
                    $configFT = $configFT[0];
                    $parsedTFArray = array([$configFT[0], $configFT[1], $configFT[2], $configFT[3], $configFT[4]."\n"."$tempReadoutPos"]);
                    $this->floatingTextConfig->set($tempReadoutPos, $parsedTFArray);
                    $this->floatingTextConfig->save();
                }
                $this->updateAllFloatingTexts();
                return true;
            break;
            case "HideFTPID":
                $this->hideAllFTPs();
                $tempIndexFTC = 0;
                foreach ($this->backupFTPIDConfig->getAll() as $configFT) {
                    $tempIndexFTC++;
                }
                for($tempReadoutPos = 0; $tempReadoutPos < $tempIndexFTC; $tempReadoutPos++){
                    $configFT = $this->backupFTPIDConfig->get($tempReadoutPos);
                    $configFT = $configFT[0];
                    $parsedTFArray = array([$configFT[0], $configFT[1], $configFT[2], $configFT[3], $configFT[4]]);
                    $this->floatingTextConfig->set($tempReadoutPos, $parsedTFArray);
                    $this->floatingTextConfig->save();
                }
                $this->updateAllFloatingTexts();
                return true;
            break;
        }
	}
}
//Theory is when you know something, but it doesn't work. Practice is when something works, but you don't know why. Programmers combine theory and practice: Nothing works and they don't know why!