<?php

/*
 * This file is part of the SpBowerBundle package.
 *
 * (c) Martin Parsiegla <martin.parsiegla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sp\BowerBundle\Tests\Assetic;

use Sp\BowerBundle\Assetic\PackageResource;
use Sp\BowerBundle\Bower\Configuration;
use Sp\BowerBundle\Bower\FormulaeBuilder;
use Sp\BowerBundle\Assetic\BowerResource;
use Sp\BowerBundle\Bower\Package\DependencyMapper;
use Sp\BowerBundle\Bower\Package\Package;
use Sp\BowerBundle\Naming\PackageNamingStrategy;
use Sp\BowerBundle\Tests\Bower\AbstractBowerTest;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

/**
 * @author Martin Parsiegla <martin.parsiegla@gmail.com>
 */
class BowerResourceTest extends AbstractBowerTest
{
    /**
     * @var BowerResource
     */
    protected $bowerResource;

    /**
     * @var \Sp\BowerBundle\Bower\Bower|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $bower;

    /**
     * @var \Sp\BowerBundle\Bower\BowerManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $bowerManager;

    /**
     * @var \Sp\BowerBundle\Bower\FormulaeBuilder
     */
    protected $formulaeBuilder;

    protected function setUp()
    {
        $this->bower = $this->getMockBuilder('Sp\BowerBundle\Bower\Bower')
            ->disableOriginalConstructor()
            ->getMock();

        $this->bowerManager = $this->getMockBuilder('Sp\BowerBundle\Bower\BowerManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formulaeBuilder = new FormulaeBuilder(new PackageNamingStrategy());

        $this->bowerResource = new BowerResource(
            $this->bower,
            $this->bowerManager,
            new PackageNamingStrategy(),
            $this->formulaeBuilder
        );

        $configDir = self::$fixturesDirectory ."/config";
        $config = new Configuration($configDir);
        $bundles = array(
            'DemoBundle' => $config,
        );

        $arrayDependencyMapping = require self::$fixturesDirectory .'/dependency_mapping.php';
        $mapper = new DependencyMapper();
        $mapping = $mapper->map($arrayDependencyMapping, $config);

        $this->bowerManager->expects($this->once())->method('getBundles')->will($this->returnValue($bundles));
        $this->bower->expects($this->once())
            ->method('getDependencyMapping')
            ->with($this->equalTo($config))
            ->will($this->returnValue($mapping));
    }

    /**
     * @covers Sp\BowerBundle\Assetic\BowerResource::getContent
     */
    public function testGetContent()
    {
        $jsFilters = array('some_js_filter');
        $cssFilters = array('some_css_filter');

        $this->formulaeBuilder->setCssFilters($cssFilters);
        $this->formulaeBuilder->setJsFilters($jsFilters);
        $formulae = $this->bowerResource->getContent();

        $this->assertArrayHasKey('other_package_css', $formulae);
        $this->assertArrayHasKey('other_package_js', $formulae);
        $this->assertArrayHasKey('invalid_package_name_js', $formulae);
        $this->assertArrayHasKey('invalid_package_name_css', $formulae);
        $this->assertArrayHasKey('package_css', $formulae);
        $this->assertArrayHasKey('package_js', $formulae);

        $this->assertContains(self::$fixturesDirectory .'/components/package/package.js', $formulae['package_js'][0]);
        $this->assertEmpty($formulae['package_css'][0]);

        $this->assertContains('@package_css', $formulae['other_package_css'][0]);
        $this->assertContains(self::$fixturesDirectory .'/components/other_package/styles.css', $formulae['other_package_css'][0]);
        $this->assertEquals($cssFilters, $formulae['other_package_css'][1]);
        $this->assertContains('@package_js', $formulae['other_package_js'][0]);
        $this->assertEquals($jsFilters, $formulae['other_package_js'][1]);
    }

    public function testGetContentConsidersScriptsProperty()
    {
        $formulae = $this->bowerResource->getContent();

        $this->assertArrayHasKey('package_js', $formulae);

        $this->assertContains(self::$fixturesDirectory .'/components/other_package/main.js', $formulae['other_package_js'][0]);
        $this->assertContains(self::$fixturesDirectory .'/components/other_package/customized.js', $formulae['other_package_js'][0]);
    }

    public function testGetContentConsidersStylesProperty()
    {
        $formulae = $this->bowerResource->getContent();

        $this->assertArrayHasKey('package_css', $formulae);

        $this->assertContains(self::$fixturesDirectory .'/components/other_package/main.css', $formulae['other_package_css'][0]);
        $this->assertContains(self::$fixturesDirectory .'/components/other_package/customized.css', $formulae['other_package_css'][0]);
    }

    public function testFormulaHasPackageFilters()
    {
        $jsFilter = 'some_js_filter';
        $cssFilter = 'some_css_filter';
        $fooPackageCssFilter = 'other_package_css_filter';
        $fooPackageJsFilter = 'other_package_js_filter';
        $packageCssFilter = 'package_css_filter';

        $this->formulaeBuilder->setCssFilters(array($cssFilter));
        $this->formulaeBuilder->setJsFilters(array($jsFilter));

        $otherPackageResource = new PackageResource('other_package');
        $otherPackageResource->setCssFilters(array($fooPackageCssFilter));
        $otherPackageResource->setJsFilters(array($fooPackageJsFilter));
        $this->formulaeBuilder->addPackageResource($otherPackageResource);

        $packageResource = new PackageResource('package');
        $packageResource->setCssFilters(array($packageCssFilter));
        $this->formulaeBuilder->addPackageResource($packageResource);

        $formulae = $this->bowerResource->getContent();

        $this->assertContains('@package_css', $formulae['other_package_css'][0]);
        $this->assertContains($cssFilter, $formulae['other_package_css'][1]);
        $this->assertContains($fooPackageCssFilter, $formulae['other_package_css'][1]);
        $this->assertContains($fooPackageJsFilter, $formulae['other_package_js'][1]);
        $this->assertNotContains($packageCssFilter, $formulae['other_package_css'][1]);

        $this->assertContains($cssFilter, $formulae['other_package_css'][1]);
        $this->assertContains($packageCssFilter, $formulae['package_css'][1]);
        $this->assertNotContains($fooPackageCssFilter, $formulae['package_css'][1]);
    }
}
