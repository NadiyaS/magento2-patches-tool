<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SetPatches\Patch;

use Composer\Json\JsonFile;
use Composer\Package\PackageInterface;
use Magento\SetPatches\Data\Patch;

class LockStorage
{
    const PROCESSED_PATCHES = 'processedPatches';

    /**
     * @var JsonFile[]
     */
    private $rootLockFile;

    /**
     * @var JsonFile
     */
    private $tmpStorage;

    /**
     * @var PatchStructureBuilder
     */
    private $patchStructureBuilder;

    /**
     * @param JsonFile $rootLockFile
     * @param JsonFile $tmpStorage
     * @param PatchStructureBuilder $patchStructureBuilder
     */
    public function __construct(
        JsonFile $rootLockFile,
        JsonFile $tmpStorage,
        PatchStructureBuilder $patchStructureBuilder
    ) {
        $this->rootLockFile = $rootLockFile;
        $this->tmpStorage = $tmpStorage;
        $this->patchStructureBuilder = $patchStructureBuilder;
    }

    /**
     * @param PackageInterface $package
     * @return bool
     */
    public function applyFrom(PackageInterface $package)
    {
        if ($this->rootLockFile->exists()) {
            $data =  $this->rootLockFile->read();
            if (isset($data[$package->getName()])) {
                return 'lock';
            }
        }
        return 'json';
    }

    /**
     * @param PackageInterface $package
     * @return Patch[]
     */
    public function get(PackageInterface $package): array
    {
        if (!$this->rootLockFile->exists()) {
            return [];
        }
        $lockData = $this->rootLockFile->read();

        if (!isset($lockData[$package->getName()][self::PROCESSED_PATCHES])) {
            return [];
        }

        $processedPatches = [];

        foreach ($lockData[$package->getName()][self::PROCESSED_PATCHES] as $key => $processedPatch) {
            unset($processedPatch['status']);
            $processedPatches[$key] = $processedPatch;
        }

        $patches = $this->patchStructureBuilder->build($processedPatches, $package);

        return $patches;
    }

    /**
     * @param Patch $patch
     * @param array $output
     * @return void
     */
    public function save(Patch $patch, array $output = [])
    {
        $this->initTmpStorage();

        $lockData = $this->tmpStorage->read();
        $lockData[$patch->getPackage()->getName()][self::PROCESSED_PATCHES][$patch->getId()] = [];
        $newData = &$lockData[$patch->getPackage()->getName()][self::PROCESSED_PATCHES][$patch->getId()];

        $newData['id'] = $patch->getId();
        $newData['action'] = $patch->getAction();
        $newData['status'] = $patch->getStatus();
        $newData['name'] = $patch->getName();
        $newData['output'] = $output;
        $newData['require'] = $patch->getRequire();
        $newData['after'] = $patch->getAfter();

        $this->tmpStorage->write($lockData);
    }

    /**
     * @return void
     */
    private function initTmpStorage()
    {
        if (!$this->tmpStorage->exists()) {
            if (!$this->tmpStorage->exists()) {
                $json = '{}';
                if ($this->rootLockFile->exists()) {
                    $json = file_get_contents($this->rootLockFile->getPath());
                }
                file_put_contents($this->tmpStorage->getPath(), $json);
            }
        }
    }
}
