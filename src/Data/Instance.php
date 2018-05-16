<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SetPatches\Data;

use Composer\Composer;
use Magento\SetPatches\LockStorage;

class Instance
{
    /**
     * @var string
     */
    private $instancePath;

    /**
     * @var Composer
     */
    private $composer;

    /**
     * @param Composer $composer
     * @param string $instancePath
     */
    public function __construct(Composer $composer, string $instancePath)
    {
        $this->instancePath = $instancePath;
        $this->composer = $composer;
    }

    /**
     * @return Composer
     */
    public function getComposer(): Composer
    {
        return $this->composer;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->instancePath;
    }
}
