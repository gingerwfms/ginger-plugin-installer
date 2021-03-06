<?php
/*
 * This file is part of the codeliner/ginger-plugin-installer package.
 * (c) Alexander Miertsch <kontakt@codeliner.ws>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace GingerPluginInstaller\Composer;

use GingerPluginInstaller\Exception;
use GingerPluginInstaller\Cqrs;
use Composer\Installer\LibraryInstaller;
use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
/**
 * Composer plugin class
 * 
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class GingerInstaller extends LibraryInstaller
{
    /**
     * @var Composer
     */
    protected $composer;
    
    /**
     * @var ServiceLocatorInterface 
     */
    protected $serviceManager;
    
    protected $packageTypes = array(
        'ginger-frontend-plugin',
        'ginger-backend-plugin'
    );


    public function __construct(IOInterface $io, Composer $composer)
    {
        parent::__construct($io, $composer);
        
        $this->composer = $composer;
    }
    
    public function supports($packageType)
    {
        return in_array($packageType, $this->packageTypes);
    }
    
    public function getInstallPath(PackageInterface $package)
    {
        return 'plugin/' . $package->getPrettyName();
    }
    
    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        $this->initGingerBackend();
        
        $extra = $package->getExtra();
        
        if (!isset($extra['plugin-namespace'])) {
            throw new Exception\RuntimeException(
                sprintf(
                    'Missing the key -plugin-namespace- in the -extra- property of the plugin -%s- composer.json.',
                    $package->getName()
                )
            );
        }
        
        parent::install($repo, $package);
        
        $pluginInstalledEvent = new Cqrs\PluginInstalledEvent(array(
            'plugin_name' => $package->getName(),
            'plugin_type' => $package->getType(),
            'plugin_version' => $package->getVersion(),
            'plugin_namespace' => $extra['plugin-namespace'],
        ));
        
        $this->getServiceManager()->get('malocher.cqrs.gate')
            ->getBus()
            ->publishEvent($pluginInstalledEvent);
    }
    
    public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
    {
        $this->initGingerBackend();
        
        $newExtra = $target->getExtra();
        
        if (!isset($newExtra['plugin-namespace'])) {
            throw new Exception\RuntimeException(
                sprintf(
                    'Missing the key -plugin-namespace- in the -extra- property of the new plugin -%s- composer.json.',
                    $target->getName()
                )
            );
        }
        
        parent::update($repo, $initial, $target);
        
        $pluginUpdatedEvent = new Cqrs\PluginUpdatedEvent(array(
            'plugin_name' => $initial->getName(),
            'plugin_type' => $initial->getType(),
            'plugin_namespace' => $newExtra['plugin-namespace'],
            'old_plugin_version' => $initial->getVersion(),
            'new_plugin_version' => $target->getVersion(),
        ));
        
        $this->getServiceManager()->get('malocher.cqrs.gate')
            ->getBus()
            ->publishEvent($pluginUpdatedEvent);
    }
    
    public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        $this->initGingerBackend();
        
        $extra = $package->getExtra();
        
        $uninstallPluginCommand = new Cqrs\UninstallPluginCommand(array(
            'plugin_name' => $package->getName(),
            'plugin_type' => $package->getType(),
            'plugin_namespace' => $extra['plugin-namespace'],
            'plugin_version' => $package->getVersion()
        ));
        
        $this->getServiceManager()->get('malocher.cqrs.gate')
            ->getBus()
            ->invokeCommand($uninstallPluginCommand);
        
        parent::uninstall($repo, $package);
    }
    
    /**
     * @return ServiceLocatorInterface
     */
    protected function getServiceManager()
    {
        if (is_null($this->serviceManager)) {
            $this->initGingerBackend();
        }
        
        return $this->serviceManager;
    }


    protected function initGingerBackend()
    {
        $extra = $this->composer->getPackage()->getExtra();
        
        if (!isset($extra['bootstrap'])) {
            throw new Exception\RuntimeException('No Bootstrap defined. Please add the -bootstrap- definition to the -extra- property of the Ginger WfMS composer.json');
        }
        
        $bootstrapClass = $extra['bootstrap'];
        
        if (!class_exists($bootstrapClass)) {
            $this->composer->getAutoloadGenerator()->createLoader(
                $this->composer->getPackage()->getAutoload()
            )->register();
        }
        
        $bootstrapClass::init();
        
        $this->serviceManager = $bootstrapClass::getServiceManager();
    }
}
