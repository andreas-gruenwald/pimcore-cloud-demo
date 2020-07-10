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

namespace AppBundle\Command;

use Pimcore\Console\AbstractCommand;
use Pimcore\Db;
use Pimcore\Model\Asset;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SystemRequirementsCommand extends AbstractCommand
{
    public function configure()
    {
        $this->setName('app:system-requirements');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            Db::getConnection();
            $output->writeln('<info>DB connection OK.</info>');
        } catch (\Throwable $e) {
            $output->writeln('<error> DB connection is not working.');
        }

    }
}
