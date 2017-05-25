<?php
/** Created By Thunder33345 **/

namespace Thunder33345\GeoLocator;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\TaskHandler;
use pocketmine\utils\TextFormat;

class GeoLocator extends PluginBase implements Listener
{
  const ENDPOINT = 'http://ip-api.com/json/';
  private $call = 0,$maxCall = 145;
  private $queue = [];
  private $format;
  /**
   * @var $cleaner null|TaskHandler
   */
  private $cleaner = null;

  public function onLoad()
  {

  }

  public function onEnable()
  {
    @mkdir($this->getDataFolder());
    $this->saveDefaultConfig();
    $this->getServer()->getPluginManager()->registerEvents($this,$this);
    $this->getServer()->getScheduler()->scheduleRepeatingTask(new RefreshingTask($this),1300);//65 sec
    $url = self::ENDPOINT.'192.168.1.1'.$this->getArgs();
    $this->getServer()->getScheduler()->scheduleAsyncTask(new GetDataTask('console',$url));
  }

  public function onDisable()
  {

  }

  public function onCommand(CommandSender $sender,Command $command,$label,array $args) { }

  public function onJoin(PlayerJoinEvent $event)
  {
    $this->queue[] = [$event->getPlayer()];
    $this->queueCleaner();
  }

  /*
   * Internal API pls don't touch
   */

  private function queueCleaner()
  {
    if(count($this->queue) <= 0) return;
    if($this->cleaner instanceof TaskHandler AND !$this->cleaner->isCancelled()) return;
    $this->cleaner = $this->getServer()->getScheduler()->scheduleRepeatingTask(new QueueCleaner($this),5);
  }

  public function clear(TaskHandler $task)
  {
    if(count($this->queue) <= 0) {
      $task->remove();
      return;
    }
    if($this->call >= $this->maxCall) return;
    $request = array_shift($this->queue);
    $player = $request[0];
    if($player instanceof Player and $player->isConnected()) $url = self::ENDPOINT.$player->getAddress().$this->getArgs(); else return;
    $this->getServer()->getScheduler()->scheduleAsyncTask(new GetDataTask($player->getName(),$url));
    $this->call++;
  }

  public function notify($playerName,$resultRaw)
  {
    $result = json_decode($resultRaw,true);
    if($result['status'] !== 'success') {
      if(($this->getConfig()->get('show-errors') == 'true') OR isset($result['message']) AND $result['message'] === 'quota') {
        $this->getLogger()->notice('Error Raw: '.$resultRaw);
        $this->getLogger()->notice('Error Array: '.print_r($result,true));
        $this->getLogger()->notice('Error Message: '.$result['message']);
      }
      return;
    }
    if(isset($this->format) OR $this->format == '') $this->format = $this->replaceColour($this->getConfig()->get('join-message'));
    $format = str_replace('%player%',$playerName,$this->format);
    foreach($result as $key => $value) $format = str_replace('%'.$key.'%',$value,$format);
    $this->getServer()->broadcastMessage($format);
  }

  private function replaceColour($string,$trigger = "![*]"): string
  {
    preg_match('/(.*)\*(.*)/',$trigger,$trim);
    preg_match_all('/'.preg_quote($trim[1]).'([A-Z a-z \_]*)'.preg_quote($trim[2]).'/',$string,$matches);
    foreach($matches[1] as $key => $colourCode){
      if(strpos($string,$matches[0][$key]) === false) continue;
      $colourCode = strtoupper($colourCode);
      if(defined(TextFormat::class."::".$colourCode)) {
        $code = constant(TextFormat::class."::".$colourCode);
        $string = str_replace($matches[0][$key],$code,$string);
      }
    }
    return $string;
  }

  public function refresh() { $this->call = 0; }

  public function getQueue() { return $this->queue; }

  private function getArgs() { return '?lang='.$this->getConfig()->get('lang').'&fields='.$this->getConfig()->get('fields'); }
}