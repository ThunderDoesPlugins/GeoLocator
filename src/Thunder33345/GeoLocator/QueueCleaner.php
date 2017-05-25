<?php
/* Made By Thunder33345 */

namespace Thunder33345\GeoLocator;

use pocketmine\scheduler\PluginTask;

class QueueCleaner extends PluginTask
{
  private $loader,$server;

  public function __construct(GeoLocator $loader)
  {
    parent::__construct($loader);
    $this->loader = $loader;
    $this->server = $loader->getServer();
  }

  public function onRun($currentTick) { $this->loader->clear($this->getHandler()); }
}