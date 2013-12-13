<?php
/*
 * This file is part of the codeliner/ginger-plugin-installer package.
 * (c) Alexander Miertsch <kontakt@codeliner.ws>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace GingerPluginInstallerTest\Composer;

use GingerPluginInstallerTest\TestCase;
use GingerPluginInstallerTest\Bootstrap;
use GingerPluginInstaller\Composer\GingerInstaller;
use Composer\Util\Filesystem;
use Composer\Package\Package;
use Composer\Package\RootPackage;
use Composer\Composer;
use Composer\Config;
use Malocher\Cqrs\Gate;
use GingerPluginInstallerTest\Mock\CqrsBusMock;
use GingerPluginInstaller\Cqrs\PluginInstalledEvent;
use GingerPluginInstaller\Cqrs\PluginUpdatedEvent;
use GingerPluginInstaller\Cqrs\UninstallPluginCommand;
use GingerPluginInstallerTest\Mock\ComposerRepositoryMock;
/**
 *  GingerInstallerTest
 * 
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class GingerInstallerTest extends TestCase
{
    private $composer;
    private $config;
    private $vendorDir;
    private $binDir;
    private $pluginDir = "plugin";
    private $dm;
    private $repository;
    private $io;
    /**
     *
     * @var Filesystem
     */
    private $fs;
    
    /**
     * setUp
     *
     * @return void
     */
    public function setUp()
    {
        $this->fs = new Filesystem;

        $this->composer = new Composer();
        
        $root = new RootPackage('gingerwfms/ginger-wfms', '1.0.0', '1.0.0');
        
        $root->setExtra(array(
            'bootstrap' => 'GingerPluginInstallerTest\Mock\BootstrapMock',            
        ));
        
        $this->composer->setPackage($root);
        
        $this->config = new Config();
        $this->composer->setConfig($this->config);
        
        $this->vendorDir = realpath(sys_get_temp_dir()) . DIRECTORY_SEPARATOR . 'ginger-test-vendor';
        $this->ensureDirectoryExistsAndClear($this->vendorDir);

        $this->binDir = realpath(sys_get_temp_dir()) . DIRECTORY_SEPARATOR . 'ginger-test-bin';
        $this->ensureDirectoryExistsAndClear($this->binDir);

        $this->config->merge(array(
            'config' => array(
                'vendor-dir' => $this->vendorDir,
                'bin-dir' => $this->binDir,
            )
        ));

        $this->dm = $this->getMockBuilder('Composer\Downloader\DownloadManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->composer->setDownloadManager($this->dm);

        $this->repository = $this->getMock('Composer\Repository\InstalledRepositoryInterface');
        $this->io = $this->getMock('Composer\IO\IOInterface');
    }
    
    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown()
    {
        $this->fs->removeDirectory($this->vendorDir);
        $this->fs->removeDirectory($this->binDir);
        $this->fs->removeDirectory($this->pluginDir);
    }
    
    /**
     * testSupports
     *
     * @return void
     *
     * @dataProvider dataForTestSupport
     */
    public function testSupports($type, $expected)
    {
        $installer = new GingerInstaller($this->io, $this->composer);
        $this->assertSame($expected, $installer->supports($type), sprintf('Failed to show support for %s', $type));
    }
    
   /**
    * dataForTestSupport
    */
    public function dataForTestSupport()
    {
        return array(
            array('ginger-frontend-plugin', true),
            array('ginger-backend-plugin', true),
            array('ginger-plugin', false),
        );
    }
    
    /**
     * testInstallPath
     */
    public function testInstallPath()
    {
        $installer = new GingerInstaller($this->io, $this->composer);
        $package = new Package('gingerwfms/wf-configurator-backend', '1.0.0', '1.0.0');

        $package->setType('ginger-backend-plugin');
        $result = $installer->getInstallPath($package);
        $this->assertEquals('plugin', $result);
    }
    
    public function testInstall()
    {
        $this->fs->ensureDirectoryExists($this->pluginDir);
        
        $installer = new GingerInstaller($this->io, $this->composer);
        
        $package = new Package('gingerwfms/wf-configurator-backend', '1.0.0', '1.0.0');
        
        $package->setType('ginger-backend-plugin');
        
        $package->setExtra(array('plugin-namespace' => 'WfConfiguratorBackend'));
        
        $gate = new Gate();
        
        $mockBus = new CqrsBusMock();
        
        $pluginNamespace = '';
        $pluginName = '';
        $pluginVersion = '';
        
        $mockBus->registerEventListener(
            'GingerPluginInstaller\Cqrs\PluginInstalledEvent', 
            function(PluginInstalledEvent $event) use (&$pluginNamespace, &$pluginName, &$pluginVersion) {
                $pluginNamespace = $event->getPluginNamespace();
                $pluginName = $event->getPluginName();
                $pluginVersion = $event->getPluginVersion();
            }
        );
        
        $gate->attach($mockBus);
        
        $gate->setDefaultBusName($mockBus->getName());
        
        Bootstrap::getServiceManager()->setAllowOverride(true);
        Bootstrap::getServiceManager()->setService('malocher.cqrs.gate', $gate);
        
        $installer->install($this->repository, $package);
        
        $this->assertSame('WfConfiguratorBackend', $pluginNamespace);
        $this->assertSame('gingerwfms/wf-configurator-backend', $pluginName);
        $this->assertSame('1.0.0', $pluginVersion);
    }
    
    public function testUpdate()
    {
        $this->fs->ensureDirectoryExists($this->pluginDir);
        
        $installer = new GingerInstaller($this->io, $this->composer);
        
        $oldPackage = new Package('gingerwfms/wf-configurator-backend', '1.0.0', '1.0.0');
        
        $oldPackage->setType('ginger-backend-plugin');
        
        $oldPackage->setExtra(array('plugin-namespace' => 'WfConfiguratorBackend'));
        
        $newPackage = new Package('gingerwfms/wf-configurator-backend', '2.0.0', '2.0.0');

        $newPackage->setType('ginger-backend-plugin');
        
        $newPackage->setExtra(array('plugin-namespace' => 'WfConfiguratorBackend'));
                
        $gate = new Gate();
        
        $mockBus = new CqrsBusMock();
        
        $pluginNamespace = '';
        $pluginName = '';
        $pluginOldVersion = '';
        $pluginNewVersion = '';
        
        $mockBus->registerEventListener(
            'GingerPluginInstaller\Cqrs\PluginUpdatedEvent', 
            function(PluginUpdatedEvent $event) use (&$pluginNamespace, &$pluginName, &$pluginOldVersion, &$pluginNewVersion) {
                $pluginNamespace = $event->getPluginNamespace();
                $pluginName = $event->getPluginName();
                $pluginOldVersion = $event->getOldPluginVersion();
                $pluginNewVersion = $event->getNewPluginVersion();
            }
        );
        
        $gate->attach($mockBus);
        
        $gate->setDefaultBusName($mockBus->getName());
        
        Bootstrap::getServiceManager()->setAllowOverride(true);
        Bootstrap::getServiceManager()->setService('malocher.cqrs.gate', $gate);
        
        $repository = new ComposerRepositoryMock();
        
        $repository->addPackage($oldPackage);
        
        $installer->update($repository, $oldPackage, $newPackage);
        
        $this->assertSame('WfConfiguratorBackend', $pluginNamespace);
        $this->assertSame('gingerwfms/wf-configurator-backend', $pluginName);
        $this->assertSame('1.0.0', $pluginOldVersion);
        $this->assertSame('2.0.0', $pluginNewVersion);
    }
    
    public function testUninstall()
    {
        $this->fs->ensureDirectoryExists($this->pluginDir);
        
        $installer = new GingerInstaller($this->io, $this->composer);
        
        $repository = new ComposerRepositoryMock();
        
        $package = new Package('gingerwfms/wf-configurator-backend', '1.0.0', '1.0.0');
        
        $package->setType('ginger-backend-plugin');
        
        $package->setExtra(array('plugin-namespace' => 'WfConfiguratorBackend'));
        
        $repository->addPackage($package);
        
        $gate = new Gate();
        
        $mockBus = new CqrsBusMock();
        
        $pluginNamespace = '';
        $pluginName = '';
        $pluginVersion = '';
        
        $mockBus->mapCommand(
            'GingerPluginInstaller\Cqrs\UninstallPluginCommand', 
            function(UninstallPluginCommand $command) use (&$pluginNamespace, &$pluginName, &$pluginVersion) {
                $pluginNamespace = $command->getPluginNamespace();
                $pluginName = $command->getPluginName();
                $pluginVersion = $command->getPluginVersion();
            }
        );
        
        $gate->attach($mockBus);
        
        $gate->setDefaultBusName($mockBus->getName());
        
        Bootstrap::getServiceManager()->setAllowOverride(true);
        Bootstrap::getServiceManager()->setService('malocher.cqrs.gate', $gate);
        
        $installer->uninstall($repository, $package);
        
        $this->assertSame('WfConfiguratorBackend', $pluginNamespace);
        $this->assertSame('gingerwfms/wf-configurator-backend', $pluginName);
        $this->assertSame('1.0.0', $pluginVersion);
    }
}
