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
use Aws\Ssm\SsmClient;
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
    private $hasErrors = false;

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
        $returnCode = 0;
        try {
            $this->checkFilePermissions();
            $this->checkDb();
            $this->checkS3();
            $this->checkRedisCache();
            $this->checkSessionStorage();
            $this->checkParameterStoreAccess();
            $this->checkMigrationState();
        } finally {
            if ($this->hasErrors) {
                $returnCode = 100;
                return $returnCode;
            }
        }

        return $returnCode;
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
            $this->logError('File permissions not optimal');
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
            $this->logSuccess('File permissions OK.');
        }
    }

    private function checkDb() {
        $output = $this->output;
        try {
            Db::getConnection();
            $this->logSuccess('DB connection OK.');
        } catch (\Throwable $e) {
            $this->logError('DB connection is not working.');
        }
    }

    private function checkS3() {
        $output = $this->output;
        try {
            $context = File::getContext();
            $options = stream_context_get_options($context);
            if (!isset($options['s3'])) {
                $this->logError('Resource is not configured to use S3!');
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

            $this->logSuccess('S3 connection OK.');

        } catch (\Throwable $e) {
            $this->logError('S3 connection is not working. '.$e->getMessage());
        }
    }

    private function checkRedisCache() {
        $output = $this->output;
        try {

            if (!Cache::isEnabled()) {
                $this->logError('Cache is not enabled.');
            } else {
                Cache::save('bar', 'foo', [], null, 0, true);
                $bar = Cache::load('foo');
                if ('bar' !== $bar) {
                    throw new \Exception('Basic cache test returns wrong result '.$bar);
                } else {
                    $this->logSuccess('Cache setup OK.');
                }
            }
        } catch (\Throwable $e) {
            $this->logError('Cache setup not working yet.'.$e->getMessage());
        }
    }

    private function checkSessionStorage() {
        $output = $this->output;
        try {
            $this->session->set('foo', 'bar');
            if ('bar' !== $this->session->get('foo')) {
                throw new \Exception('Basic session test returns wrong result');
            } else {
                $this->logSuccess('Session setup (Redis) OK.');
            }

        } catch (\Throwable $e) {
            $this->logError('Session Storage setup not working yet.'.$e->getMessage());
        }
    }

    private function getSssmClient() : SsmClient {
        $ssmClient = new SsmClient([
            'version' => 'latest',
            'region' => getenv('s3Region'), //'us-east-2', // choose your favorite region
            'credentials' => [
                // use your aws credentials
                'key' => 'AKIAWBAR2ZIEPAO6XI2S', //getenv('s3Key'), //'AKIAJOAFDIFXXXXXXXXXX',
                'secret' => 'hCL9RviyAHCt4m+K4L6KX7UyAixY6KJiy01i/t+m', //getenv('s3Secret'), //'uw7fGn0if9KvQR09O+n7E8+XXXXXXXXXX',
            ],
        ]);
        return $ssmClient;
    }

    private function checkParameterStoreAccess() {
        try {

            $ssmClient = $this->getSssmClient();
            $value = $ssmClient->getParameter(['Name' => 'pimcoreTestParam', 'WithDecryption' => true])->get('Parameter')['Value'];

            if (strpos($value, 'IT***WORKS') > 0) {
                $this->logSuccess(('Test parameter "pimcoreTestParam" was successfully retrieved with value '.$value));
            } else {
                $this->logError('Parameter store access test failed. Value: '.$value);
            }

        } catch (\Throwable $e) {
            $this->logError('Migration number determination failed. '.$e->getMessage());
        }
    }

    private function checkMigrationState() {
        try {

            $ssmClient = $this->getSssmClient();
            $migrationVersionValue = $ssmClient->getParameter(['Name' => 'pimcoreDemoLiveMigrationsVersion', 'WithDecryption' => true])
                ->get('Parameter')['Value'];

            if ($migrationVersionValue == 'V101') {
                $this->logInfo('Migration version setup check ('.$migrationVersionValue.') OK.');
                return;
            }

            $this->logError('Migration version does not match: '.$migrationVersionValue);

        } catch (\Throwable $e) {
            $this->logError('Migration number determination failed. '.$e->getMessage());
        }
    }

    private function logSuccess(string $message) {
        $this->output->writeln('<info>~ '.$message.'</info>');
    }

    private function logError(string $message) {
        $this->hasErrors = true;
        $this->output->writeln('<error>~ '.$message.'</error>');
    }
}
