<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace AppBundle\Twig\Extension;

use AppBundle\Model\Product\Category;
use AppBundle\Website\LinkGenerator\CategoryLinkGenerator;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class DockerExtension extends AbstractExtension
{

    public function getFunctions()
    {
        return [
            new TwigFunction('app_get_env', [$this, 'getEnv']),
        ];
    }

    public function getEnv(string $value)
    {
        return getenv($value);
    }
}
