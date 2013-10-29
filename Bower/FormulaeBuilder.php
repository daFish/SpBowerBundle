<?php
/**
 * Created by PhpStorm.
 * User: mstoehr
 * Date: 28.10.13
 * Time: 21:33
 */

namespace Sp\BowerBundle\Bower;

use Sp\BowerBundle\Bower\Package\Package;

class BowerFormulae
{
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
} 