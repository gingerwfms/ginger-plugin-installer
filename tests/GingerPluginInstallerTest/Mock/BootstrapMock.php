<?php
/*
 * This file is part of the codeliner/ginger-plugin-installer package.
 * (c) Alexander Miertsch <kontakt@codeliner.ws>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace GingerPluginInstallerTest\Mock;

use GingerCore\Bootstrap\BootstrapInterface;
use GingerPluginInstallerTest\Bootstrap;
/**
 * BootstrapMock
 * 
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class BootstrapMock implements BootstrapInterface
{
    public static function getServiceManager()
    {
        return Bootstrap::getServiceManager();
    }

    public static function init()
    {
        //test env is already bootstrap, so do nothing here
    }

}
