<?php

/*
 * This file is part of the SpBowerBundle package.
 *
 * (c) Martin Parsiegla <martin.parsiegla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sp\BowerBundle\Bower;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Sp\BowerBundle\Assetic\PackageResource;
use Sp\BowerBundle\Bower\Package\Package;
use Sp\BowerBundle\Naming\PackageNamingStrategyInterface;

class FormulaeBuilder
{
    /**
     * @var PackageNamingStrategyInterface
     */
    protected $namingStrategy;

    /**
     * @var Collection
     */
    protected $packageResources;

    /**
     * @var Boolean
     */
    protected $nestDependencies = true;

    /**
     * @var array
     */
    protected $cssFilters = array();

    /**
     * @var array
     */
    protected $jsFilters = array();

    /**
     * Constructor
     *
     * @param PackageNamingStrategyInterface $namingStrategy
     */
    public function __construct(PackageNamingStrategyInterface $namingStrategy)
    {
        $this->namingStrategy = $namingStrategy;

        $this->packageResources = new ArrayCollection();
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
     * @param boolean $nestDependencies
     */
    public function setNestDependencies($nestDependencies)
    {
        $this->nestDependencies = $nestDependencies;
    }

    /**
     * @return boolean
     */
    public function shouldNestDependencies()
    {
        return $this->nestDependencies;
    }

    /**
     * @param array $cssFilter
     */
    public function setCssFilters(array $cssFilter)
    {
        $this->cssFilters = $cssFilter;
    }

    /**
     * @return array
     */
    public function getCssFilters()
    {
        return $this->cssFilters;
    }

    /**
     * @param array $jsFilter
     */
    public function setJsFilters(array $jsFilter)
    {
        $this->jsFilters = $jsFilter;
    }

    /**
     * @return array
     */
    public function getJsFilters()
    {
        return $this->jsFilters;
    }

    /**
     * Creates formulae for the given package.
     *
     * @param Package $package
     * @param string  $packageName
     *
     * @return array<string,array<array>>
     */
    public function createPackageFormulae(Package $package, $packageName)
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

    /**
     * @param PackageResource|null $packageResource
     *
     * @return array
     */
    protected function resolveCssFilters(PackageResource $packageResource = null)
    {
        $cssFilters = $this->getCssFilters();
        if (null !== $packageResource) {
            $cssFilters = array_merge($cssFilters, $packageResource->getCssFilters()->toArray());
        }

        return $cssFilters;
    }

    /**
     * @param PackageResource|null $packageResource
     *
     * @return array
     */
    protected function resolveJsFilters(PackageResource $packageResource = null)
    {
        $jsFilters = $this->getJsFilters();
        if (null !== $packageResource) {
            $jsFilters = array_merge($jsFilters, $packageResource->getJsFilters()->toArray());
        }

        return $jsFilters;
    }
} 