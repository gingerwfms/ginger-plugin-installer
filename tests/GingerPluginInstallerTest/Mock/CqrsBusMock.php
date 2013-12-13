<?php
/*
 * This file is part of the codeliner/ginger-plugin-installer package.
 * (c) Alexander Miertsch <kontakt@codeliner.ws>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace GingerPluginInstallerTest\Mock;

use Malocher\Cqrs\Bus\AbstractBus;
/**
 *  CqrsBusMock
 * 
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class CqrsBusMock extends AbstractBus
{
    public function getName()
    {
        return 'mock-bus';
    }
}
