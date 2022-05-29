<?php
declare(strict_types=1);

namespace ConnectTime;

use OnixUtils\OnixUtils;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use function array_slice;
use function arsort;
use function ceil;
use function count;
use function strtolower;

class ConnectTime extends PluginBase{
	use SingletonTrait;

	/** @var Config */
	protected $config;

	protected $db;

	protected function onLoad() : void{
		self::setInstance($this);
	}

	protected function onEnable() : void{
		$this->config = new Config($this->getDataFolder() . "Config.yml", Config::YAML, [
			"player" => [],
			"date" => (int) date("d")
		]);
		$this->db = $this->config->getAll();
	}

	public function check() : void{
		if((int) date("d") !== $this->db["date"]){
			$this->db["date"] = (int) date("d");
			$this->clearTodayTime();
		}
	}

	public function clearTodayTime() : void{
		foreach($this->db["player"] as $name => $data){
			$this->db["player"][$name]["today"] = 0;
		}
	}

	protected function onDisable() : void{
		$this->config->setAll($this->db);
		$this->config->save();
	}

	public function addTime(Player $player){
		if(!isset($this->db["player"][strtolower($player->getName())])){
			$this->db["player"][strtolower($player->getName())] = [
				"today" => 0,
				"total" => 0
			];
		}
		$this->db["player"][strtolower($player->getName())]["today"] += 1;
		$this->db["player"][strtolower($player->getName())]["total"] += 1;
	}

	public function getTimeForToday(string $name) : int{
		return $this->db["player"][strtolower($name)]["today"] ?? 0;
	}

	public function getTimeForTotal(string $name) : int{
		return $this->db["player"][strtolower($name)]["total"] ?? 0;
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		switch($args[0] ?? "x"){
			case "보기":
				if(trim($args[1] ?? "") !== ""){
					if(isset($this->db["player"][strtolower($args[1])])){
						OnixUtils::message($sender, $args[1] . "님의 접속시간은 " . OnixUtils::convertTimeToString($this->getTimeForTotal($args[1])) . " 입니다.");
					}else{
						OnixUtils::message($sender, $args[1] . "님은 서버에 접속한 적이 없습니다.");
					}
				}else{
					OnixUtils::message($sender, "내 접속시간은 " . OnixUtils::convertTimeToString($this->getTimeForTotal($sender->getName())) . " 입니다.");
				}
				break;
			case "순위":
				if(isset($args[1]) && is_numeric($args[1]) && (int) $args[1] > 0)
					$index = (int) $args[1];else
					$index = 1;
				$maxPage = ceil(count($this->db["player"]) / 5);
				$page = (int) floor($index);
				if($page > $maxPage)
					$page = $maxPage;
				OnixUtils::message($sender, "§d<§f 전체 §d" . (ceil(count($this->db["player"]) / 5)) . "§f페이지중 §d{$index}§f페이지 §d>");
				foreach($this->getRankPage($page) as $rank => $rankData){
					$sender->sendMessage("§d<§f{$rank}위§d> §d{$rankData["name"]}§f: " . OnixUtils::convertTimeToString($rankData["time"]));
				}
				break;
			default:
				OnixUtils::message($sender, "/접속시간 보기 [닉네임] - 접속시간을 봅니다.");
				OnixUtils::message($sender, "/접속시간 순위 - 접속시간 순위를 봅니다.");
		}
		return true;
	}

	public function getAll() : array{
		$result = [];
		foreach($this->db["player"] as $name => $data){
			$result[$name] = $data["total"];
		}
		arsort($result);
		return $result;
	}

	public function getRankPage(int $page) : array{
		$result = $this->getAll();
		arsort($result);
		$max = ceil(count($result) / 5);
		if($page > $max)
			$page = $max;
		$slice = array_slice($result, (int) (($page - 1) * 5), 5);
		$i = 0;
		$res = [];
		foreach($slice as $name => $connectTime){
			$i++;
			$rank = ($page - 1) * 5 + $i;
			$res[$rank] = ["name" => $name, "time" => $connectTime];
		}
		return $res;
	}
}