<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SetPatches\Patch;

use Magento\SetPatches\Data\Patch;
use Magento\SetPatches\Data\Instance;
use Magento\SetPatches\Patch\LockStorageFactory;
use Composer\Package\PackageInterface;

class PatchesProvider
{
    /**
     * @var JsonStorage
     */
    private $jsonStorage;

    /**
     * @var LockStorageFactory
     */
    private $lockStorageFactory;

    /**
     * @param \Magento\SetPatches\Patch\JsonStorage $jsonStorage
     * @param LockStorageFactory $lockStorageFactory
     */
    public function __construct(
        JsonStorage $jsonStorage,
        LockStorageFactory $lockStorageFactory
    ) {
        $this->jsonStorage = $jsonStorage;
        $this->lockStorageFactory = $lockStorageFactory;
    }

    /**
     * @param Instance $instance
     * @param PackageInterface $package
     * @param string $storageType
     * @return array
     */
    public function get(Instance $instance, PackageInterface $package, string $storageType = null): array
    {
        $localStorage = $this->lockStorageFactory->create($instance);
        if (!$storageType) {
            $storageType = $localStorage->applyFrom($package);
        }
        switch ($storageType) {
            case 'lock':
                return $this->getFromLock($instance, $package);
            default:
                return $this->getDiff($instance, $package);
        }
    }

    /**
     * @param Instance $instance
     * @param PackageInterface $package
     * @return array|Patch[]
     */
    private function getFromLock(Instance $instance, PackageInterface $package)
    {
        return $this->lockStorageFactory->create($instance)->get($package);
    }

    /**
     * @param Instance $instance
     * @param PackageInterface $package
     * @return array
     */
    private function getDiff(Instance $instance, PackageInterface $package)
    {
        $result = [];

        $appliedPatches = $this->lockStorageFactory->create($instance)->get($package);
        $patches = $this->jsonStorage->get($package);

        /** @var Patch $patchToApply */
        foreach ($patches as &$patchToApply) {
            /** @var Patch $skippedPatch */
            foreach ($appliedPatches as $skippedPatch) {
                if ($skippedPatch->getId() === $patchToApply->getId()
                    && $patchToApply->getAction() == $skippedPatch->getAction()
                ) {
                    $patchToApply->setStatus(Patch::STATUS_DONE);
                    continue 2;
                }
            }
            $result[$patchToApply->getId()] = $patchToApply;
        }

        $patchesToRevert = array_udiff($appliedPatches, $patches, function (Patch $appliedPatch, Patch $patchToApply) {
            if ($appliedPatch->getId() == $patchToApply->getId()) {
                return 0;
            } elseif ($appliedPatch->getId() > $patchToApply->getId()) {
                return 1;
            } else {
                return -1;
            }
        });

        /** @var Patch $patch */
        foreach ($patchesToRevert as $index => $patch) {
            $patch->setAction(Patch::ACTION_REVERT);
            $patch->setStatus(Patch::STATUS_PENDING);
        }
        /** @var Patch $patch */
        foreach ($result as $index => $patch) {
            $patch->setAction(Patch::ACTION_APPLY);
            $patch->setStatus(Patch::STATUS_PENDING);
        }

        return $result ?: array_reverse($patchesToRevert);
    }
}
