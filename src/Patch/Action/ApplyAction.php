<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SetPatches\Patch\Action;

use Composer\Package\PackageInterface;
use Magento\SetPatches\Data\Patch;
use Magento\SetPatches\Data\Instance;
use Magento\SetPatches\Patch\LockStorageFactory;
use Magento\SetPatches\OutputMatcher;
use Magento\SetPatches\Shell;

/**
 * Provides apply methods for patches.
 */
class ApplyAction implements ActionInterface
{
    /**
     * @var Shell
     */
    private $shell;

    /**
     * @var OutputMatcher
     */
    private $outputMatcher;

    /**
     * @var LockStorageFactory
     */
    private $lockStorageFactory;

    /**
     * @param OutputMatcher $outputMatcher
     * @param Shell $shell
     * @param LockStorageFactory $lockStorageFactory
     */
    public function __construct(
        OutputMatcher $outputMatcher,
        Shell $shell,
        LockStorageFactory $lockStorageFactory
    ) {
        $this->shell = $shell;
        $this->outputMatcher = $outputMatcher;
        $this->lockStorageFactory = $lockStorageFactory;
    }

    /**
     * @param Patch $patch
     * @param Instance $instance
     * @return bool
     * @throws \Exception
     */
    public function execute(Patch $patch, Instance $instance): bool
    {
        $localStorage = $this->lockStorageFactory->create($instance);
        try {
            if ($patch->getStatus() !== Patch::STATUS_PENDING) {
                return true;
            }

            if (is_object($patch->getAfter()) && $patch->getAfter()->getStatus() !== Patch::STATUS_DONE) {
                throw new CouldNotApplyException(sprintf(
                    'Patch %s should be applied before applying patch %s.'),
                    $patch->getAfter()->getName(),
                    $patch->getName()
                );
            }

            $output = $this->apply($patch, $instance);
            $localStorage->save($patch, $output);
            return true;
        } catch (\Exception $exception) {
            $patch->setStatus(Patch::STATUS_FAILED);
            $localStorage->save($patch);
            /** @var Patch $dependant */
            foreach ($patch->getDependants() as $dependant) {
                $dependant->setStatus(Patch::STATUS_SKIP);
                $localStorage->save($dependant);
            }
            throw $exception;
        }
    }

    /**
     * @param Patch $patch
     * @param Instance $instance
     * @return array
     * @throws CouldNotApplyException
     */
    private function apply(Patch $patch, Instance $instance)
    {
        if (!empty($patch->getRequire())) {
            foreach ($patch->getRequire() as $constraint => $version) {
                if (!$this->matchConstraint($constraint, $version, $instance)) {
                    throw new CouldNotApplyException(sprintf(
                        'Constraint %s %s was not found.',
                        $constraint,
                        $version
                    ));
                }
            }
        }

        $output = $this->shell->execute(
            'cd ' . $instance->getPath() . ' && git apply -v ' . $patch->getAbsolutePath() . ' 2>&1'
        );

        var_dump("\n ================================================= \n");
        var_dump($instance->getPath() . '/' . $patch->getAbsolutePath());
        var_dump("\n ================================================= \n");
        var_dump($output);
        var_dump("\n ================================================= \n");

        $patch->setStatus(Patch::STATUS_DONE);

        return ['modifiedFiles' => $this->outputMatcher->getModifiedFiles($output)];
    }

    /**
     * Checks whether package with specific constraint exists in the system.
     *
     * @param string $packageName
     * @param string $constraint
     * @param Instance $instance
     * @return bool True if patch with provided constraint exists, false otherwise.
     */
    private function matchConstraint(string $packageName, string $constraint, Instance $instance): bool
    {
        return $instance->getComposer()
                ->getRepositoryManager()
                ->getLocalRepository()
                ->findPackage($packageName, $constraint) instanceof PackageInterface;
    }
}
