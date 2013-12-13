<?php
/*
 * This file is part of the codeliner/ginger-plugin-installer package.
 * (c) Alexander Miertsch <kontakt@codeliner.ws>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace GingerPluginInstallerTest\Mock;

use Composer\Repository\InstalledRepositoryInterface;
use Composer\Package\PackageInterface;
/**
 *  ComposerRepositoryMock
 * 
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class ComposerRepositoryMock implements InstalledRepositoryInterface
{
    protected $packages = array();
    
    public function addPackage(PackageInterface $package)
    {
        $this->packages[$package->getName()] = $package;
    }

    public function count()
    {
        
    }

    public function findPackage($name, $version)
    {
        
    }

    public function findPackages($name, $version = null)
    {
        
    }

    public function getCanonicalPackages()
    {
        
    }

    public function getPackages()
    {
        return $this->packages;
    }

    public function hasPackage(PackageInterface $package)
    {
        return isset($this->packages[$package->getName()]);
    }

    public function reload()
    {
        
    }

    public function removePackage(PackageInterface $package)
    {
        unset($this->packages[$package->getName()]);
    }

    public function search($query, $mode = 0)
    {
        
    }

    public function write()
    {
        
    }

}
