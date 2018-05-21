<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SetPatches;

use Composer\Composer;
use Composer\Package\PackageInterface;
use Composer\Repository\WritableRepositoryInterface;
use Magento\SetPatches\Data\Patch;
use Magento\SetPatches\Data\Instance;

class OutputMatcher
{
    /**
     * @param array $output
     * @return array
     */
    public function getModifiedFiles(array $output)
    {
        preg_match_all('/Checking\spatch\s(?<fileName>.*)\.\.\./', implode("\n", $output), $matches);
        return $matches['fileName'] ?? [];
    }

    /**
     * @param array $output
     * @return array
     */
    public function getErrors(array $output)
    {
        preg_match_all('/error:\s(?<fileName>.*):\spatch does not apply/', implode("\n", $output), $matches);
        return $matches['fileName'] ?? [];
    }
}
