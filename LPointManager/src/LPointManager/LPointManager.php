<?php

namespace LPointManager;

use LPoint\LPoint;

use LPointManager\event\CreateAccountEvent;

use pocketmine\plugin\PluginBase;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;

use pocketmine\utils\Config;

use pocketmine\command\CommandSender;
use pocketmine\command\Command;

use pocketmine\Player;

class LPointManager extends PluginBase implements Listener{
	
	public $prefix = "§l§b[ §7LPointManager §b] §f";
	
	public function onEnable(){
		if($this->getServer()->getPluginManager()->getPlugin("LPoint") === null){
			$this->getLogger()->notice("LPoint 플러그인이 없습니다.");
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}
		
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		
		@mkdir($this->getDataFolder());
		$this->PointSetting = (new Config($this->getDataFolder() . "PointSetting.yml", Config::YAML));
		$this->ps = $this->PointSetting->getAll();
		
		if(!isset($this->ps ["first-join"])){
			$this->ps ["first-join"] = 1000;
			$this->saveAll();
		}
	}
	
	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		$api = LPoint::getInstance();
		$name = $sender->getName();
		if($command->getName() === "포인트" or $command->getName() === "내포인트"){
			if(!$sender instanceof Player){
				$sender->sendMessage("{$this->prefix}인 게임에서 사용해주세요.");
				return true;
			}
			
			$sender->sendMessage("{$this->prefix}내 포인트 : {$api->getPoint($sender)}, 내 포인트 순위 : {$api->getRank($sender)}위");
			return true;
		}elseif($command->getName() === "포인트설정"){
			if(!$sender->isOp()){
				$sender->sendMessage("{$this->prefix}권한이 부족하여 이 명령어를 실행할 수 없습니다.");
				return true;
			}
			if(!isset($args[0]) or !isset($args[1])){
				$sender->sendMessage("{$this->prefix}/포인트설정 <플레이어> <포인트>");
				return true;
			}
			$target = (string)$args[0];
			$point = (int)$args[1];
			$point = abs($point);
			if(!$api->isJoined($target)){
				$sender->sendMessage("{$this->prefix}{$target}님은 서버에 접속한 적이 없습니다.");
				return true;
			}
			
			$api->setPoint($target, $point);
			$sender->sendMessage("{$this->prefix}{$target}님의 포인트를 {$point}P로 설정했습니다.");
			if(($player = $this->getServer()->getPlayer($target)) !== null){
				$player->sendMessage("{$this->prefix}{$name}님이 당신의 포인트를 {$point}P로 설정했습니다.");
			}
			return true;
		}elseif($command->getName() === "포인트추가"){
			if(!$sender->isOp()){
				$sender->sendMessage("{$this->prefix}권한이 부족하여 이 명령어를 실행할 수 없습니다.");
				return true;
			}
			if(!isset($args[0]) or !isset($args[1])){
				$sender->sendMessage("{$this->prefix}/포인트추가 <플레이어> <포인트>");
				return true;
			}
			$target = (string)$args[0];
			$point = (int)$args[1];
			$point = abs($point);
			if(!$api->isJoined($target)){
				$sender->sendMessage("{$this->prefix}{$target}님은 서버에 접속한 적이 없습니다.");
				return true;
			}
			$api->addPoint($target, $point);
			$sender->sendMessage("{$this->prefix}{$target}님의 포인트({$point}P)를 추가했습니다.");
			if(($player = $this->getServer()->getPlayer($target)) !== null){
				$player->sendMessage("{$this->prefix}{$name}님이 당신의 포인트({$point}P)를 추가했습니다.");
			}
			return true;
		}elseif($command->getName() === "포인트뺏기"){
			if(!$sender->isOp()){
				$sender->sendMessage("{$this->prefix}권한이 부족하여 이 명령어를 실행할 수 없습니다.");
				return true;
			}
			if(!isset($args[0]) or !isset($args[1])){
				$sender->sendMessage("{$this->prefix}/포인트뺏기 <플레이어> <포인트>");
				return true;
			}
			$target = (string)$args[0];
			$point = (int)$args[1];
			$point = abs($point);
			if(!$api->isJoined($target)){
				$sender->sendMessage("{$this->prefix}{$target}님은 서버에 접속한 적이 없습니다.");
				return true;
			}
			if($api->getPoint($target) < $point){
				$sender->sendMessage("{$this->prefix}{$target}님이 보유하신 포인트가 적어 뺏을 수 없습니다.");
				return true;
			}
			$api->subtractPoint($target, $point);
			$sender->sendMessage("{$this->prefix}{$target}님의 포인트({$point}P)를 빼앗았습니다.");
			if(($player = $this->getServer()->getPlayer($target)) !== null){
				$player->sendMessage("{$this->prefix}{$name}님이 당신의 포인트({$point}P)를 빼앗았습니다.");
			}
			return true;
		}elseif($command->getName() === "포인트보내기"){
			if(!$sender instanceof Player){
				$sender->sendMessage("{$this->prefix}인 게임에서 사용해주세요.");
				return true;
			}
			if(!isset($args[0]) or !isset($args[1])){
				$sender->sendMessage("{$this->prefix}/포인트주기 <플레이어> <포인트>");
				return true;
			}
			$target = (string)$args[0];
			$point = (int)$args[1];
			$point = abs($point);
			if(!$api->isJoined($target)){
				$sender->sendMessage("{$this->prefix}{$target}님은 서버에 접속한 적이 없습니다.");
				return true;
			}
			if($api->getPoint($sender) < $point){
				$sender->sendMessage("{$this->prefix}보유하신 포인트가 부족합니다.");
				return true;
			}
			$api->subtractPoint($sender, $point);
			$api->addPoint($target, $point);
			$sender->sendMessage("{$this->prefix}{$target}님에게 {$point}P를 보냈습니다.");
			if(($player = $this->getServer()->getPlayer($target)) !== null){
				$player->sendMessage("{$this->prefix}{$name}님에게 포인트({$point}P)를 받았습니다.");
			}
			return true;
		}elseif($command->getName() === "포인트순위"){
			if(!isset($args[0])){
				$page = 1;
			}else{
				$maxplayer = $api->countPlayer();
				$maxpage = ($maxplayer/5);
				$maxpage = (int)$maxpage;
				$maxpage = $maxpage+1;
				$page = (int)$args[0];
				$page = abs($page);
				$page = $page === 0 ? 1 : $page;
				$page = $page >= $maxpage ? $maxpage : $page;
			}
			$text = "§l§a===== [ 포인트 순위 ] =====";
			for($i=1; $i<=5; $i++){
				$rank = (($page-1)*5)+$i;
				if($api->countPlayer() >= $rank){
					$rank = (($page-1)*5)+$i;
					$text = "{$text}\n§l§b[ §7{$rank}위 §b]§f {$api->getRanker($rank)} - {$api->getPoint($api->getRanker($rank))}";
				}
			}
			$text = "{$text}\n§l§a========================";
			$sender->sendMessage($text);
			return true;
		}
	}
	
	public function CreateAccount(PlayerJoinEvent $event){
		$api = LPoint::getInstance();
		$player = $event->getPlayer();
		$name = $player->getName();
		if(!$api->isJoined($player)){
			$point = (int)$this->ps ["first-join"];
			$api->setPoint($player, $point);
			$ev = new CreateAccountEvent($name);
			$ev->call();
		}
	}
	
	public function saveAll(){
		$this->PointSetting->setAll($this->ps);
	    $this->PointSetting->save();
	}
	
}