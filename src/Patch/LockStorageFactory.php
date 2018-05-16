<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SetPatches\Patch;

use Composer\Json\JsonFile;
use Composer\Package\PackageInterface;
use Illuminate\Container\Container;
use Magento\SetPatches\Data\Instance;
use Magento\SetPatches\Patch\LockStorage;

class LockStorageFactory
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
     * @param Instance $instance
     * @return LockStorage
     */
    public function create(Instance $instance)
    {
        return $this->container->makeWith(LockStorage::class, [
            'rootLockFile' => $this->container->makeWith(JsonFile::class, [
                'path' => realpath($instance->getPath() . '/composer.lock')
            ]),
            'tmpStorage' => $this->container->makeWith(JsonFile::class, [
                'path' => $instance->getPath() . '/composer.lock.tmp'
            ])
        ]);
    }
}
