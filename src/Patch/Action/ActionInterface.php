<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SetPatches\Patch\Action;

use Magento\SetPatches\Data\Patch;
use Magento\SetPatches\Data\Instance;

interface ActionInterface
{
    /**
     * @param Patch $patch
     * @param Instance $instance
     * @return bool
     */
    public function execute(Patch $patch, Instance $instance);
}
