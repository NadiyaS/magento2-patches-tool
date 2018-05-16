<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SetPatches\Instance;

use Composer\Composer;
use Illuminate\Container\Container;
use Magento\SetPatches\Data\Instance;

class InstanceFactory
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $instancePath
     * @return Instance
     */
    public function create($instancePath): Instance
    {
        $composer = $this->container->make(Composer::class);
        return $this->container->makeWith(Instance::class, [
            'instancePath' => $instancePath,
            'composer' => $composer,
        ]);
    }
}
