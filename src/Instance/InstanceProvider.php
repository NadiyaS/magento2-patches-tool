<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SetPatches\Instance;

use Magento\SetPatches\Filesystem\FilesystemDriver;
use Magento\SetPatches\Data\Instance;
use Magento\SetPatches\Shell;

class InstanceProvider
{
    /**
     * @var InstanceFactory
     */
    private $instanceFactory;

    /**
     * @var Shell
     */
    private $shell;

    /**
     * @var FilesystemDriver
     */
    private $filesystemDriver;

    /**
     * @param InstanceFactory $instanceFactory
     * @param FilesystemDriver $filesystemDriver
     * @param Shell $shell
     */
    public function __construct(
        InstanceFactory $instanceFactory,
        FilesystemDriver $filesystemDriver,
        Shell $shell
    ) {
        $this->instanceFactory = $instanceFactory;
        $this->filesystemDriver = $filesystemDriver;
        $this->shell = $shell;
    }

    /**
     * @param string $path
     * @return Instance
     * @internal param string $packageName
     * @internal param string $packageVersion
     */
    public function getByPath(string $path)
    {
        return $this->instanceFactory->create($path);
    }

    /**
     * @param string $version
     * @return Instance
     */
    public function getByVersion(string $version)
    {
        $instanceDir = PACKAGE_BP . '/instances/';
        $instancePath = PACKAGE_BP . '/instances/' . $version;
        if (!$this->filesystemDriver->isDirectory($instancePath)) {
            $this->filesystemDriver->createDirectory($instancePath);
            $this->filesystemDriver->copy($instanceDir . '/composer.json', $instancePath . '/composer.json');
            $this->filesystemDriver->copy($instanceDir . '/auth.json', $instancePath . '/auth.json');

            $this->shell->execute(
                "cd $instancePath && composer require magento/project-enterprise-edition $version 2>&1"
            );
        }

        $this->filesystemDriver->createDirectory($instancePath . '_tmp');
        $this->filesystemDriver->copyDirectory($instancePath, $instancePath . '_tmp');
        return $this->instanceFactory->create(realpath($instancePath . '_tmp'));
    }

    /**
     * @param string $releaseLine
     * @return Instance[]
     */
    public function getByReleaseLine(string $releaseLine): array
    {
        $versions = $this->getReleaseLineVersions($releaseLine);
        $instances = [];
        foreach ($versions as $version) {
            $instances[$version] = $this->getByVersion($version);
        }
        return $instances;
    }

    /**
     * @return Instance
     */
    public function getRootInstance()
    {
        return $this->instanceFactory->create(realpath(PACKAGE_BP . '/../../../'));
    }

    /**
     * @param $releaseLineVersion
     * @return array
     */
    private function getReleaseLineVersions($releaseLineVersion)
    {
        $output = $this->shell->execute(
            'cd ' . PACKAGE_BP . '/instances/' . ' && composer show --all magento/project-enterprise-edition 2>&1'
        );
        $versions = [];
        foreach ($output as $row) {
            preg_match('/versions.*:\s*(?<versions>.*)/', $row, $matches);
            if (isset($matches['versions'])) {
                $versions = explode(', ', $matches['versions']);
                break;
            }
        }

        foreach ($versions as $index => $version) {
            if (
                substr($version, 0, strlen($releaseLineVersion)) !== $releaseLineVersion
                || str_contains($version, '-rc')
            ) {
                unset($versions[$index]);
            }
        }
        return $versions;
    }
}
