<?php
/** Created By Thunder33345 **/

namespace Thunder33345\GeoLocator;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class GetDataTask extends AsyncTask
{
  private $playerName,$url;

  public function __construct($playerName,$url)
  {
    parent::__construct();
    $this->playerName = $playerName;
    $this->url = $url;
  }

  public function onRun()
  {
    $curl = curl_init($this->url);
    curl_setopt($curl,CURLOPT_RETURNTRANSFER ,true);
    $result = curl_exec($curl);
    $this->setResult($result,true);
    curl_close($curl);
  }

  public function onCompletion(Server $server)
  {
    $plugin = $server->getPluginManager()->getPlugin('GeoLocator');
    if(!$plugin instanceof GeoLocator) return;
    if(!$plugin->isEnabled()) return;
    $plugin->notify($this->playerName,$this->getResult());
  }
}