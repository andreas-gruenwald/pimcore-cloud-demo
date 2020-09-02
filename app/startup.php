<?php
use Aws\S3\S3Client;

include('startup-healthcheck.php'); //check health

if (!getenv('s3EngineEnabled')) {
    return;
}

$s3Client = new S3Client([
    'version' => 'latest',
    'region' => getenv('s3Region'),
    'credentials' => [
        // use your aws credentials
        'key' => getenv('s3Key'),
        'secret' => getenv('s3Secret'),
    ],
]);

$s3Client->registerStreamWrapper();

// set default file context
\Pimcore\File::setContext(stream_context_create([
    's3' => ['seekable' => true, 'ACL' => 'private']
]));

