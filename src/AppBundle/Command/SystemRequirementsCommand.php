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

use AppBundle\Services\EcsDeploymentService;
use Aws\S3\S3Client;
use Pimcore\Cache;
use Pimcore\Console\AbstractCommand;
use Pimcore\Db;
use Pimcore\File;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SystemRequirementsCommand extends AbstractCommand
{
    private $ecsDeploymentService;
    private $session;
    private $hasErrors = false;

    const OPTION_PERMISSIONS = 'permissions';
    const OPTION_DB = 'db';
    const OPTION_S3 = 's3';
    const OPTION_REDIS_CACHE = 'redis_cache';
    const OPTION_REDIS_SESSION = 'redis_session';
    const OPTION_SECRET_STORE = 'secrets';
    const OPTION_OPEN_MIGRATIONS = 'migrations';
    const OPTION_MIGRATION_VERSION = 'migration_version';

    public function __construct(string $name = null, SessionInterface $session, EcsDeploymentService $ecsDeploymentService)
    {
        parent::__construct($name);
        $this->session = $session;
        $this->ecsDeploymentService = $ecsDeploymentService;
    }

    public function configure()
    {
        $this->setName('app:system-requirements');
        $this->addOption('resource', 'r',
            InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
            'Only verify connection to certain resources. Allowed values: '.$this->getAllowedOptions()
        );
    }

    private function getAllowedOptions() : array {
        return [self::OPTION_DB, self::OPTION_PERMISSIONS, self::OPTION_REDIS_CACHE, self::OPTION_REDIS_SESSION,
            self::OPTION_S3, self::OPTION_SECRET_STORE, self::OPTION_OPEN_MIGRATIONS, self::OPTION_MIGRATION_VERSION];
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $returnCode = 0;

        try {

            $resources = $input->getOption('resource') ? : $this->getAllowedOptions();

            if (count(array_diff($resources, $this->getAllowedOptions())) > 0) {
                $this->hasErrors = true;
                $message = sprintf('Unknown resource option "%s". Use one of "%s".',
                    implode(",",
                        array_diff($resources, $this->getAllowedOptions())), implode(",",$this->getAllowedOptions()));
                $output->writeln('<error>'.$message.'</error>');
                throw new \Exception($message);
            }


            if (in_array(self::OPTION_PERMISSIONS, $resources)) {
                $this->checkFilePermissions();
            }

            if (in_array(self::OPTION_DB, $resources)) {
                $this->checkDb();
            }

            if (in_array(self::OPTION_S3, $resources)) {
                $this->checkS3();
            }

            if (in_array(self::OPTION_REDIS_CACHE, $resources)) {
                $this->checkRedisCache();
            }

            if (in_array(self::OPTION_REDIS_SESSION, $resources)) {
                $this->checkSessionStorage();
            }

            if (in_array(self::OPTION_SECRET_STORE, $resources)) {
                $this->checkParameterStoreAccess();
            }

            if (in_array(self::OPTION_MIGRATION_VERSION, $resources)) {
                $this->checkMigrationStateParameterStore();
            }

            if (in_array(self::OPTION_OPEN_MIGRATIONS, $resources)) {
                $this->checkOpenMigrations();
            }

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
        foreach ([PIMCORE_SYMFONY_CACHE_DIRECTORY
                 //    , PIMCORE_LOG_DIRECTORY
                 ] as $dir) {
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
//                    'credentials' => [
//                        // use your aws credentials
//                        'key' => getenv('s3Key'),
//                        'secret' => getenv('s3Secret'),
//                    ],
                ]);

                $bucketExists = $s3Client->doesBucketExist(getenv('s3BucketName'));
                if (!$bucketExists) {
                    $message = sprintf('Bucket "%s" not existing.', getenv('s3BucketName'));
                    try {
                        $s3Client->listBuckets();
                        $message .= '..but listBuckets works!';
                    } catch (\Exception $e) {
                        $message .= '..listBuckets: '.$e->getMessage();
                    }
                    $message .= PHP_EOL.PHP_EOL.'Please take a look at '.
                        'https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/guide_credentials_environment.html '.
                        'and ensure that you either set the correct environment variables, or that the ECS user has got the right permissions.'
                    ;
                    throw new \Exception($message);
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

    private function checkParameterStoreAccess() {
        try {

            $ssmClient = $this->ecsDeploymentService->getSssmClient();
            $value = $ssmClient->getParameter(['Name' => 'pimcoreTestParam', 'WithDecryption' => true])->get('Parameter')['Value'];

            if (strpos($value, 'IT***WORKS') > 0) {
                $this->logSuccess(('Parameter Store (SSM) access OK.'));
            } else {
                $this->logError('Parameter store access test failed. Value: '.$value);
            }

        } catch (\Throwable $e) {
            $this->logError('Migration number determination failed. '.$e->getMessage());
        }
    }

    private function checkMigrationStateParameterStore() {
        try {
            $this->ecsDeploymentService->isMigrationParamValueCurrent(true);
            $this->logSuccess('Migration version setup check ('
                .$this->ecsDeploymentService->getMigrationParamName().':' .$this->ecsDeploymentService->accessMigrationParamValue().') OK.'
            );

        } catch (\Throwable $e) {
            $this->logError('Migration number determination failed. '.$e->getMessage());
        }
    }

    private function checkOpenMigrations() {
        $numMigrations = -1;
        $migrationsLog = [];
        $getNoMigrations = 'timeout 2.5 /var/www/html/bin/console pimcore:migrations:status --show-versions | grep ">> New Migrations:"';
        exec($getNoMigrations, $migrationsLog, $migrationsStateCode);
        if (!empty($migrationsLog)) {
            $numMigrations = explode(':', $migrationsLog[0])[1];
        }

        if ($numMigrations < 0) {
            $this->logError('Cannot determine migration state.');
        } elseif ($numMigrations != 0) {
            $this->logError('There are open migrations: '.$numMigrations. ' On the live system, run ../bin/console pimcore:migrations:migrate to proceed.');
        } else {
            $this->logSuccess('There are no open migrations.');
        }

        return $numMigrations;
    }

    private function logSuccess(string $message) {
        $this->output->writeln('<info> ✅ '.$message.'</info>');
    }

    private function logError(string $message) {
        $this->hasErrors = true;
        $this->output->writeln('<error> ⛔ '.$message.'</error>');
    }
}
