<?php
    $healthStateLog = [];
    exec('timeout 1.5 /var/www/html/bin/console app:system-requirements 2>&1', $healthStateLog, $healthStateCode);
    if (empty($healthStateLog)) {
        $healthStateLog[]= 'Timeout occured, probably one of the resources cannot connect.';
    }

    if ($healthStateCode != 0) {
        header('HTTP/1.1 503 Service Temporarily Unavailable');
        header('Status: 503 Service Temporarily Unavailable');
        header('Retry-After: 30');//300 seconds
    }

    $filename = '/var/www/html/app/health_env.php';
    if (!file_exists($filename)) {
        touch($filename);
    }
    file_put_contents($filename,
        sprintf('<?php define("PIMCORE_CONTAINER_HEALTH", "%s");',($healthStateCode == 0 ? 'HEALTHY' : 'UNHEALTHY'))
    );
?>
<html>
<head>
    <style>
        body {
            font-family: Helvetica, Arial, sans-serif;
        }
    </style>
</head>
<body>
    <h1 style="<?=$healthStateCode != 0 ? "color:red": "color:green";?>">Health Check <?=$healthStateCode != 0 ? ' ERROR' : 'OK';?></h1>
</body>
</html>
