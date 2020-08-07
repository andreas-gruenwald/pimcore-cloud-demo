<?php
    $allowedIps = ['89.26.34.65']; //Elements IP Address
    $currentIp = isset($_SERVER["HTTP_X_FORWARDED_FOR"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER['REMOTE_ADDR'];
    if(!in_array($currentIp, $allowedIps) && strpos($currentIp, '192.168') !== 0) {
        die('Access not allowed for IP '.$currentIp);
    }

    if (file_exists('/var/www/html/.env')) {
        $lines = explode(PHP_EOL, file_get_contents('/var/www/html/.env'));
        foreach ($lines as $line) {
            if (strpos($line, '=') > 0) {
               if (!empty(trim($line))) {
                   putenv($line);
               }
            }
        }
    }

    $healthStateLog = [];
    exec('timeout 2.5 /var/www/html/bin/console app:system-requirements 2>&1', $healthStateLog, $healthStateCode);
    if (empty($healthStateLog)) {
        $healthStateLog[]= 'Timeout occured, probably one of the resources cannot connect.';
    }
?>
<html>
<head>

    <style>
        body {
            font-family: monospace;
        }

        .console {
            background-color:black;
            color:white;
            min-height: 3em;
        }

        .colorbox {
            min-height:50px;
            line-height:50px;
            font-weight:bold;
            min-width:200px;
            background-color: <?php echo getenv('APP_COLOR') ? : 'white' ;?>;
            padding-left:2em;
            padding-right:2em;
            text-align:center;
            position: absolute;
            right: 0px;
            top: 0px;
        }

        .clibox, .healthbox {
            padding:5px;
            background-color:black;
            color:white;
            margin: 0 0 20px 0;
            display: inline-block;
            padding:10px;
        }

        .clibox.enabled {
            color:#8bc34a;
            font-weight:bold;
            font-family: monospace;
        }

        .healthbox {
            cursor:pointer;
        }

        .healthbox.healthy {
            background-color: #e1e14b;
            color: black;

        }

        .healthbox.unhealthy {
            background-color:red;

        }

        #health-details {
            display: none;
            margin: 1em 0 2em 0;
            border: 1px solid #9e9e9e;
        }

    </style>

    <script type="text/javascript">
        // Show an element
        var show = function (elem) {
            elem.style.display = 'block';
        };

        // Hide an element
        var hide = function (elem) {
            elem.style.display = 'none';
        };

        // Toggle element visibility
        var toggle = function (elem) {

            // If the element is visible, hide it
            if (window.getComputedStyle(elem).display === 'block') {
                hide(elem);
                return;
            }

            // Otherwise, show it
            show(elem);

        };
    </script>

</head>
<body>

<h1>Docker Toolbox for Hosted Containers</h1>

<?php if (getenv('APP_COLOR')):?>
    <div class="colorbox"><?php echo getenv('APP_CONTAINER_INFO') ? : '-';?></div>
<?php endif; ?>

<?php
    $isCliEnabled = getenv('CLI_ENABLED') == 'true';
?>

<div class="clibox <?php echo $isCliEnabled ? "enabled" : "disabled";?>">
    <?php echo $isCliEnabled ? '✓ CLI is enabled.' : '✓ CLI is not active.';?>
</div>

<div class="healthbox <?=$healthStateCode <= 0 ? 'healthy' : 'unhealthy';?>" onclick="toggle(document.getElementById('health-details'))">
    <?php if ($healthStateCode <= 0) {
        echo "🌞 Health fine.";
    } else {
        echo "☁ Not Healthy";
    }?>
</div>
<div id="health-details">
    <?php
        foreach ($healthStateLog ? : [] as $i => $log) {
            echo ($i > 0 ? '</br>' : '').$log;
    }?>
</div>


<form method="POST" action="">

    Command: <input type="text" name="userInput" id="userInput" style="min-width:400px">
    <input type="submit" value="Execute"/>

    <pre class="console"><?php
        if ($ui = $_POST['userInput']) {
            echo shell_exec($ui.' 2>&1');
        } else {
            echo shell_exec('whoami');
            echo shell_exec('pwd');
        }
        ?>
    </pre>

</form>

<?php
$ecsMetaUrl = getenv('ECS_CONTAINER_METADATA_URI');
if ($ecsMetaUrl) {
    //@see https://docs.aws.amazon.com/AmazonECS/latest/developerguide/task-metadata-endpoint-v3.html
    $calls = [
        'metaUrl' => sprintf('%s', $ecsMetaUrl),
        'tasks' => sprintf('%s/task', $ecsMetaUrl),
        'stats' => sprintf('%s/stats', $ecsMetaUrl),
        'taskStats' => sprintf('%s/task/stats', $ecsMetaUrl),
    ];

    ?>

    <div class="ecs-info">
        <h2>AWS ECS provides a link to access metadata about the task and the container environment:</h2>
        <?php foreach ($calls as $name => $url) {
            echo '<h3>'.$name.'</h3>';
            echo "URL: ".$url.'<br>';
            echo "Response:";
            print_r(file_get_contents($url));
        }
        ?>
    </div>

    <?php
}
?>

</body>
</html>
