<?php
use Aws\S3\S3Client;

if (!getenv('s3EngineEnabled')) {
    return;
}

$s3Client = new S3Client([
    'version' => 'latest',
    'region' => getenv('s3Region'), //'us-east-2', // choose your favorite region
    'credentials' => [
        // use your aws credentials
        'key' => getenv('s3Key'), //'AKIAJOAFDIFXXXXXXXXXX',
        'secret' => getenv('s3Secret'), //'uw7fGn0if9KvQR09O+n7E8+XXXXXXXXXX',
    ],
]);

$s3Client->registerStreamWrapper();

// set default file context
\Pimcore\File::setContext(stream_context_create([
    's3' => ['seekable' => true, 'ACL' => 'private']
]));

