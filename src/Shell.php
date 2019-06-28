<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SetPatches;


/**
 * Shell wrapper.
 */
class Shell
{
    /**
     * Executes specific command and returns output data.
     *
     * @param string $command
     * @return array
     */
    public function execute(string $command)
    {
        exec(
            $command,
            $output,
            $status
        );

        if ($status !== 0) {
            throw new \RuntimeException("Command $command returned code $status", $status);
        }
        return $output;
    }
}
