<?php

/*
 * This file is part of the SpBowerBundle package.
 *
 * (c) Martin Parsiegla <martin.parsiegla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sp\BowerBundle\Assetic;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sp\BowerBundle\Bower\Bower;
use Sp\BowerBundle\Bower\BowerManager;
use Sp\BowerBundle\Bower\ConfigurationInterface;
use Sp\BowerBundle\Bower\FormulaeBuilder;
use Sp\BowerBundle\Bower\Exception\FileNotFoundException;
use Sp\BowerBundle\Bower\Exception\RuntimeException;
use Sp\BowerBundle\Bower\Package\Package;
use Sp\BowerBundle\Naming\PackageNamingStrategyInterface;
use Symfony\Bundle\AsseticBundle\Factory\Resource\ConfigurationResource;

/**
 * @author Martin Parsiegla <martin.parsiegla@gmail.com>
 */
class BowerResource extends ConfigurationResource implements \Serializable
{
    /**
     * @var \Sp\BowerBundle\Bower\Bower
     */
    protected $bower;

    /**
     * @var \Sp\BowerBundle\Bower\BowerManager
     */
    protected $bowerManager;

    /**
     * @var \Sp\BowerBundle\Naming\PackageNamingStrategyInterface
     */
    protected $namingStrategy;

    /**
     * @var FormulaeBuilder
     */
    protected $formulaeBuilder;

    /**
     * @var Collection
     */
    protected $packageResources;

    /**
     * Constructor
     *
     * @param Bower                          $bower
     * @param BowerManager                   $bowerManager
     * @param PackageNamingStrategyInterface $namingStrategy
     * @param FormulaeBuilder                $formulaeBuilder
     */
    public function __construct(
        Bower $bower,
        BowerManager $bowerManager,
        PackageNamingStrategyInterface $namingStrategy,
        FormulaeBuilder $formulaeBuilder
    )
    {
        $this->bower = $bower;
        $this->bowerManager = $bowerManager;
        $this->namingStrategy = $namingStrategy;
        $this->formulaeBuilder = $formulaeBuilder;

        $this->packageResources = new ArrayCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        $formulae = array();
        /** @var $config ConfigurationInterface */
        foreach ($this->bowerManager->getBundles() as $config) {
            try {
                $mapping = $this->bower->getDependencyMapping($config);
            } catch (FileNotFoundException $ex) {
                throw $ex;
            } catch (RuntimeException $ex) {
                throw new RuntimeException('Dependency cache keys not yet generated, run "app/console sp:bower:install" to initiate the cache: ' . $ex->getMessage());
            }

            /** @var $package Package */
            foreach ($mapping as $package) {
                $packageName = $this->namingStrategy->translateName($package->getName());
                $formulae = array_merge(
                    $this->formulaeBuilder->createPackageFormulae($package, $packageName, $config->getDirectory()),
                    $formulae
                );
            }
        }

        return $formulae;
    }

    /**
     * @param PackageResource $packageResource
     *
     * @return $this
     */
    public function addPackageResource(PackageResource $packageResource)
    {
        $this->packageResources->set($packageResource->getName(), $packageResource);

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return 'bower';
    }

    /**
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        list($this->cssFilters, $this->jsFilter, $this->packageResources) = unserialize($serialized);
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return serialize(array($this->cssFilters, $this->jsFilters, $this->packageResources));
    }

    /**
     * Creates formulae for the given package.
     *
     * @param \Sp\BowerBundle\Bower\Package\Package $package
     * @param string                                $packageName
     * @param string                                $configDir
     *
     * @return array<string,array<array>>
     */
    protected function createPackageFormulae(Package $package, $packageName, $configDir)
    {
        $formulae = array();

        /** @var PackageResource $packageResource */
        $packageResource = $this->packageResources->get($packageName);
        $cssFiles = $package->getStyles()->toArray();
        $jsFiles = $package->getScripts()->toArray();

        $nestDependencies = $this->shouldNestDependencies();
        if (null !== $packageResource && null !== $packageResource->shouldNestDependencies()) {
            $nestDependencies = $packageResource->shouldNestDependencies();
        }

        if ($nestDependencies) {
            /** @var $packageDependency Package */
            foreach ($package->getDependencies() as $packageDependency) {
                $packageDependencyName = $this->namingStrategy->translateName($packageDependency->getName());
                array_unshift($jsFiles, '@' . $packageDependencyName . '_js');
                array_unshift($cssFiles, '@' . $packageDependencyName . '_css');
            }
        }

        $formulae[$packageName . '_css'] = array($cssFiles, $this->resolveCssFilters($packageResource), array());
        $formulae[$packageName . '_js'] = array($jsFiles, $this->resolveJsFilters($packageResource), array());

        return $formulae;
    }
}
