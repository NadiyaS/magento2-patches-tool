<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SetPatches\Command;

use Composer\Json\JsonFile;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class BackupLock extends Command
{
    const NAME = 'backupLock';

    /**
     * @var JsonFile
     */
    private $rootLockFile;

    /**
     * @var JsonFile
     */
    private $rootTmpLockFile;

    /**
     * @param JsonFile $rootLockFile
     * @param JsonFile $rootTmpLockFile
     */
    public function __construct(JsonFile $rootLockFile, JsonFile $rootTmpLockFile)
    {
        $this->rootLockFile = $rootLockFile;
        $this->rootTmpLockFile = $rootTmpLockFile;
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(static::NAME)
            ->setDescription('Backup lock file');

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->rootTmpLockFile->exists()) {
            return 0;
        }
        $this->rootTmpLockFile->write($this->rootLockFile->read());
        return 0;
    }
}
