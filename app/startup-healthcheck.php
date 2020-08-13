<?php
// AWS ECS HEALTH CHECK (avoid calls to containers with outdated data model)
if (php_sapi_name() != 'cli') {
    $healthState = 'UNKNOWN';
    $healthStateFile = '/var/www/html/app/health_env.php';
    if (file_exists($healthStateFile)) {
        include($healthStateFile);
        $healthState = constant("PIMCORE_CONTAINER_HEALTH");
    }


    if ($healthState != 'HEALTHY') {
        header('HTTP/1.1 503 Service Temporarily Unavailable');
        header('Status: 503 Service Temporarily Unavailable');
        header('Retry-After: 30');//30 seconds

        $ecsDeploymentService = new \AppBundle\Services\EcsDeploymentService();

        $paramName = 'pimcoreDemoLiveMaintenancePage';
        try {
            $maintenanceHtml = $ecsDeploymentService->getSsmParameter($paramName);
            if (!empty($maintenanceHtml)) {
                echo $maintenanceHtml;
                exit(1);
            }
        } catch (\Exception $e) {

        }

        ?>
        <html>
        <head>
            <style>body {
                    font-family: Helvetica, Arial, sans-serif;
                }    </style>
        </head>
        <body>
        <h1 style="color:red">Wrong Pimcore Container version, default error page of container showing Use parameter
            "<?=$paramName;?>" if you want to inject a dynamic error message using AWS ParamStore.</h1>
        </body>
        </html>
        <?php
        exit(1);
    }
}