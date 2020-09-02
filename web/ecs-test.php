<?php

use Aws\S3\S3Client;

include __DIR__ . "/../vendor/autoload.php";
//$ecs = new \AppBundle\Services\EcsDeploymentService();
?>

<html>
<head>
    <style>
        body {
            font-family: "Courier New";
        }
    </style>
</head>
<body>
<h1>S3 Test Access</h1>

<?php

    //@see https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/guide_credentials.html#credential-profiles
    //@see https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/guide_credentials_environment.html


    $stsClient = new \Aws\Sts\StsClient([
        //'profile' => 'default',
        'region' => 'eu-central-1',
        'version' => '2011-06-15',
//        'credentials' => [
//            // use your aws credentials
//            'key' => getenv('s3Key'),
//            'secret' => getenv('s3Secret'),
//        ],
    ]);

//    $result = $stsClient->assumeRole([
//        'RoleArn' => 'arn:aws:iam::aws:policy/aws-service-role/AmazonECSServiceRolePolicy',
//        'RoleSessionName' => 'session1'
//    ]);

    $s3Client = new S3Client([
        'version' => 'latest',
        'region' => 'eu-central-1',
        //'credentials' => [
            // use your aws credentials
            //'key' => $key,
            //'secret' => $secret,
        //],
    ]);

    p_r($s3Client->listBuckets());
?>

</body>
</html>
