<?php


namespace AppBundle\Services;


use Aws\Ssm\SsmClient;

class EcsDeploymentService
{
    public function getSsmParameter(string $parameterName) : string {
        if (empty($parameterName)) {
            throw new \Exception(
                'Empty parameter name "'.$parameterName.'" in environment. Please check your docker configuration.');
        }

        $value = $this->getSssmClient()
            ->getParameter(['Name' =>$parameterName, 'WithDecryption' => true])
            ->get('Parameter')['Value'];
        return (string)$value;
    }

    public function getMigrationParamName() : string {
        $parameterName = getenv('APP_MIGRATION_PARAM_NAME');
        if (empty($parameterName)) {
            throw new \Exception(
                'Empty parameter name "'.$parameterName.'" in environment. Please check your docker configuration.');
        }
        return $parameterName;
    }

    public function accessMigrationParamValue() : string {
        $parameterName = $this->getMigrationParamName();
        return $this->getSsmParameter($parameterName);
    }

    public function isMigrationParamValueCurrent(bool $throwException = true) : bool {
        $currentValue = $this->accessMigrationParamValue();
        $expectedValue = getenv('APP_MIGRATION_CURRENT_VERSION') ? : 'NOT_CONFIGURED_PLEASE_CHANGE';
        if ($currentValue == $expectedValue) {
            return true;
        }
        throw new \Exception(sprintf('Migration Param values do not match (container: %s, paramStore:%s',
            $expectedValue, $currentValue))
        ;
        return false;
    }

    public function updateMigrationParamValue(string $value) {
        $paramName = $this->getMigrationParamName();
        $this->getSssmClient()->putParameter([$paramName => $value]);
    }

    public function getSssmClient() : SsmClient {
        $ssmClient = new SsmClient([
            'version' => 'latest',
            'region' => getenv('ssmRegion'), //'us-east-2', // choose your favorite region
            'credentials' => [
                'key' => getenv('ssmKey'),
                'secret' => getenv('ssmSecret')
            ]
        ]);
        return $ssmClient;
    }
}