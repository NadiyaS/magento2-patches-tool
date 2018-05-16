<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SetPatches\Composer;

use Composer\Composer;
use Composer\Json\JsonFile;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Magento\SetPatches\Command\Apply;
use Magento\SetPatches\Command\RestoreLock;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Magento\SetPatches\Application;

class Plugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * Apply plugin modifications to Composer
     *
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
        $installer = new PatchInstaller($io, $composer);
        $composer->getInstallationManager()->addInstaller($installer);
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            ScriptEvents::POST_UPDATE_CMD => [
                ['onPostInstallUpdate', 0],
                ['onPostUpdate', 10]
            ],
            ScriptEvents::POST_INSTALL_CMD => [
                ['onPostInstallUpdate', 0]
            ],
        ];
    }

    /**
     * @param Event $event
     */
    public function onPostInstallUpdate(Event $event)
    {
        $output = new ConsoleOutput();

        $application = new \Magento\SetPatches\Application();
        $restoreCommand = $application->find(RestoreLock::NAME);
        $restoreCommand->run(new ArrayInput([]), $output);
    }

    /**
     * @param Event $event
     */
    public function onPostUpdate(Event $event)
    {
        $output = new ConsoleOutput();

        $application = new Application();
        $applyCommand = $application->find(Apply::NAME);

        $jsonFile = new JsonFile(BASE_DIR . '/composer.json');
        if ($jsonFile->exists()
            && isset($jsonFile->read()['extra']['patches'])
        ) {
            foreach (array_keys($jsonFile->read()['extra']['patches']) as $packageName ) {
                $applyCommand->run(
                    new ArrayInput([
                        '--' . Apply::OPTION_PACKAGE_NAME => $packageName,
                        '--' . Apply::OPTION_PACKAGE_VERSION => $this->getPackageVersion($packageName)
                    ]),
                    $output
                );
            }
        }
    }

    /**
     * @param string $packageName
     * @return string
     */
    private function getPackageVersion(string $packageName): string
    {
        $localRepo = $this->composer->getRepositoryManager()->getLocalRepository();

        foreach ($localRepo->getPackages() as $package) {
            if ($package->getName() === $packageName) {
                return $package->getVersion();
            }
        }
    }
}
