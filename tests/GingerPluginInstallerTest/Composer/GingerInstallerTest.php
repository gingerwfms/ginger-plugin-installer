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
use GingerPluginInstaller\Composer\GingerInstaller;
use PHPUnit_Framework_TestCase;
use Composer\Util\Filesystem;
use Composer\Package\Package;
use Composer\Package\RootPackage;
use Composer\Composer;
use Composer\Config;
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
    private $dm;
    private $repository;
    private $io;
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
        $this->config = new Config();
        $this->composer->setConfig($this->config);
        
        $this->vendorDir = realpath(sys_get_temp_dir()) . DIRECTORY_SEPARATOR . 'ginger-test-vendor';
        $this->ensureDirectoryExistsAndClear($this->vendorDir);

        $this->binDir = realpath(sys_get_temp_dir()) . DIRECTORY_SEPARATOR . 'ginger-test-bin';
        $this->ensureDirectoryExistsAndClear($this->binDir);

        $this->config->merge(array(
            'config' => array(
                'extra' => array(
                    'bootstrap' => 'GingerPluginInstallerTest\Mock\BootstrapMock',
                ),
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

        $package->setType('ginger-backend-path');
        $result = $installer->getInstallPath($package);
        $this->assertEquals('plugin', $result);
    }
}
