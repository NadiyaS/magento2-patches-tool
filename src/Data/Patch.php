<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SetPatches\Data;

use Composer\Package\PackageInterface;

class Patch
{
    const STATUS_SKIP = 'skip';
    const STATUS_DONE = 'done';
    const STATUS_PENDING = 'pending';
    const STATUS_FAILED = 'failed';
    const ACTION_CHECK_REVERT = 'check_revert';
    const ACTION_REVERT = 'revert';
    const ACTION_APPLY = 'apply';

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $absolutePath;

    /**
     * @var int
     */
    private $id;

    /**
     * @var array
     */
    private $require = [];

    /**
     * @var string|int|Patch
     */
    private $afterPatch;

    /**
     * @var array
     */
    private $dependants = [];

    /**
     * @var string
     */
    private $status = self::STATUS_PENDING;

    /**
     * @var string
     */
    private $action = self::ACTION_APPLY;

    /**
     * @var PackageInterface
     */
    private $package;

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @param string $action
     * @return Patch
     */
    public function setAction(string $action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     * @return Patch
     */
    public function setStatus(string $status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return string
     */
    public function getAbsolutePath(): string
    {
        return $this->absolutePath;
    }

    /**
     * @param string $absolutePath
     * @return Patch
     */
    public function setAbsolutePath(string $absolutePath)
    {
        $this->absolutePath = $absolutePath;
        return $this;
    }

    /**
     * @param string $name
     * @return Patch
     */
    public function setName(string $name): Patch
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @param int $id
     * @return Patch
     */
    public function setId(int $id): Patch
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @param array $require
     * @return Patch
     */
    public function setRequire(array $require): Patch
    {
        $this->require = $require;
        return $this;
    }

    /**
     * @param string|int|Patch $afterPatch
     * @return Patch
     */
    public function setAfter($afterPatch): Patch
    {
        $this->afterPatch = $afterPatch;
        return $this;
    }

    /**
     * @return string|int|Patch
     */
    public function getAfter()
    {
        return $this->afterPatch;
    }


    /**
     * @param array $dependants
     * @return Patch
     */
    public function setDependants(array $dependants): Patch
    {
        $this->dependants = $dependants;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return array
     */
    public function getRequire(): array
    {
        return $this->require;
    }

    /**
     * @return array
     */
    public function getDependants(): array
    {
        return $this->dependants;
    }

    /**
     * @return PackageInterface
     */
    public function getPackage(): PackageInterface
    {
        return $this->package;
    }

    /**
     * @param PackageInterface $package
     * @return $this
     */
    public function setPackage(PackageInterface $package)
    {
        $this->package = $package;
        return $this;
    }
}
