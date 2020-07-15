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

use Aws\S3\S3Client;
use Pimcore\Cache;
use Pimcore\Console\AbstractCommand;
use Pimcore\Db;
use Pimcore\File;
use Pimcore\Model\Asset;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SystemRequirementsCommand extends AbstractCommand
{
    private $session;

    public function __construct(string $name = null, SessionInterface $session)
    {
        parent::__construct($name);
        $this->session = $session;
    }

    public function configure()
    {
        $this->setName('app:system-requirements');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->checkFilePermissions();
        $this->checkDb();
        $this->checkS3();
        $this->checkRedisCache();
        $this->checkSessionStorage();
        return 0;
    }

    private function checkFilePermissions() {
        $output = $this->output;

        $messageLog = [];
        foreach ([PIMCORE_SYMFONY_CACHE_DIRECTORY, PIMCORE_LOG_DIRECTORY] as $dir) {
            if (!is_writable($dir)) {
                $messageLog[$dir] = 'Not writable: ' . $dir;
            }
        }

        $hasErrors = false;
        if (!empty($messageLog)) {
            $output->writeln('<error> ~ File permissions not optimal.</error>');
            foreach ($messageLog as $dir => $log) {
                $output->write(' - '.$log);
                shell_exec('chown www-data: "'.$dir.'" -R');
                if (is_writable($dir)) {
                    $output->write('<info> fixed!</info>');
                } else {
                    $hasErrors = true;
                }
                $output->writeln("");
            }
        }

        if (!$hasErrors) {
            $output->writeln('<info>~ File permissions OK.</info>');
        }
    }

    private function checkDb() {
        $output = $this->output;
        try {
            Db::getConnection();
            $output->writeln('<info>~ DB connection OK.</info>');
        } catch (\Throwable $e) {
            $output->writeln('<error>~ DB connection is not working.');
        }
    }

    private function checkS3() {
        $output = $this->output;
        try {
            $context = File::getContext();
            $options = stream_context_get_options($context);
            if (!isset($options['s3'])) {
                $output->writeln('<error>~ Resource is not configured to use S3!');
            } else {
                $s3Client = new S3Client([
                    'version' => 'latest',
                    'region' => getenv('s3Region'),
                    'credentials' => [
                        // use your aws credentials
                        'key' => getenv('s3Key'),
                        'secret' => getenv('s3Secret'),
                    ],
                ]);

                $bucketExists = $s3Client->doesBucketExist(getenv('s3BucketName'));
                if (!$bucketExists) {
                    throw new \Exception(sprintf('Bucket "%s" not existing.', getenv('s3BucketName')));
                }

            }

            $output->writeln('<info>~ S3 connection OK.</info>');

        } catch (\Throwable $e) {
            $output->writeln('<error>~ S3 connection is not working. '.$e->getMessage());
        }
    }

    private function checkRedisCache() {
        $output = $this->output;
        try {

            if (!Cache::isEnabled()) {
                $output->writeln('<error>Cache is not enabled.</error>');
            } else {
                Cache::save('bar', 'foo', [], null, 0, true);
                $bar = Cache::load('foo');
                if ('bar' !== $bar) {
                    throw new \Exception('Basic cache test returns wrong result '.$bar);
                } else {
                    $output->writeln('<info>~ Cache setup OK.</info>');
                }
            }
        } catch (\Throwable $e) {
            $output->writeln('<error>~ Cache setup not working yet.'.$e->getMessage());
        }
    }

    private function checkSessionStorage() {
        $output = $this->output;
        try {
            $this->session->set('foo', 'bar');
            if ('bar' !== $this->session->get('foo')) {
                throw new \Exception('Basic session test returns wrong result');
            } else {
                $output->writeln('<info>~ Session setup (Redis) OK.</info>');
            }

        } catch (\Throwable $e) {
            $output->writeln('<error>~ Session Storage setup not working yet.'.$e->getMessage());
        }

    }
}
