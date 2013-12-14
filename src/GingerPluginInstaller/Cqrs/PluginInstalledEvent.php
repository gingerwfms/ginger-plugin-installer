<?php
/*
 * This file is part of the codeliner/ginger-plugin-installer package.
 * (c) Alexander Miertsch <kontakt@codeliner.ws>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace GingerPluginInstaller\Cqrs;

use Malocher\Cqrs\Message\Message;
use Malocher\Cqrs\Event\EventInterface;
/**
 * PluginInstalledEvent
 * 
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class PluginInstalledEvent extends Message implements EventInterface
{
    public function getPluginNamespace()
    {
        return $this->payload['plugin_namespace'];
    }
    
    public function getPluginName()
    {
        return $this->payload['plugin_name'];
    }
    
    public function getPluginType()
    {
        return $this->payload['plugin_type'];
    }
    
    public function getPluginVersion()
    {
        return $this->payload['plugin_version'];
    }
}