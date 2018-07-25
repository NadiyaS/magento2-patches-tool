<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SetPatches\Patch\Action;

use Magento\SetPatches\Data\Patch;
use Magento\SetPatches\Data\Instance;
use Magento\SetPatches\Patch\LockStorageFactory;
use Magento\SetPatches\OutputMatcher;
use Magento\SetPatches\Shell;

class RevertAction implements ActionInterface
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
    public function __construct(OutputMatcher $outputMatcher, Shell $shell, LockStorageFactory $lockStorageFactory)
    {
        $this->shell = $shell;
        $this->outputMatcher = $outputMatcher;
        $this->lockStorageFactory = $lockStorageFactory;
    }

    /**
     * @param Patch $patch
     * @param Instance $instance
     * @return array|bool
     * @throws \Exception
     */
    public function execute(Patch $patch, Instance $instance)
    {
        $localStorage = $this->lockStorageFactory->create($instance);
        try {
            $output = $this->shell->execute(
                'cd ' . $instance->getPath() . ' && git apply -R --check -v ' . $patch->getAbsolutePath() . ' 2>&1'
            );
            $revertedFiles = ['revertedFiles' => $this->outputMatcher->getModifiedFiles($output)];
            if (!$this->outputMatcher->getErrors($output) && $patch->getAction() !== Patch::ACTION_CHECK_REVERT) {
                $this->shell->execute(
                    'cd ' . $instance->getPath() . ' && git apply -R -v ' . $patch->getAbsolutePath() . ' 2>&1'
                );
            }
            $patch->setStatus(Patch::STATUS_DONE);
            $localStorage->save($patch, $revertedFiles);
        } catch (\Exception $exception) {
            if (preg_match('/Command.*returned code 1/', $exception->getMessage())) {
                throw new CouldNotRevertException();
            }
            throw $exception;
        }

        return true;
    }
}
