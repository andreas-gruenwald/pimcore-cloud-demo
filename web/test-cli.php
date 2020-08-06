<?php
    $allowedIps = ['89.26.34.65']; //Elements IP Address
    $currentIp = isset($_SERVER["HTTP_X_FORWARDED_FOR"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER['REMOTE_ADDR'];
    if(!in_array($currentIp, $allowedIps) && strpos($currentIp, '192.168') !== 0) {
        die('Access not allowed for IP '.$currentIp);
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
            background-color: <?php echo getenv('APP_COLOR') ? : 'white' ;?>
            padding-left:2em;
            padding-right:2em;
            text-align:center;
            position: absolute;
            right: 0px;
            top: 0px;
        }

        .clibox {
            padding:5px;
            background-color:black;
            color:white;
            margin: 0 0 20px 0;
            display: inline-block;
        }

        .clibox.enabled {
            color:green;
            font-weight:bold;
            font-family: monospace;
        }

    </style>
</head>
<body>

<h1>Docker Toolbox for Hosted Containers</h1>

<?php if (getenv('APP_COLOR')):?>
    <div class="colorbox">Your app's color is <?php echo getenv('APP_COLOR');?>.</div>
<?php endif; ?>

<div class="clibox"><?php echo getenv('CLI_ENABLED') == 'true' ? 'CLI is enabled.' : 'CLI is deactivated.';?></div>


<form method="POST" action="">

    Command: <input type="text" name="userInput" id="userInput" style="min-width:400px">
    <input type="submit" value="Execute"/>

    <pre class="console"><?php
        if ($ui = $_POST['userInput']) {
            echo shell_exec($ui.' 2>&1');
        } else {
            echo shell_exec('whoami').'/sh:';
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
        <?php foreach ($calls as $name => $command) {

            echo '<h3>'.$name.'</h3>';
            p_r(file_get_contents($url));
        }
        ?>
    </div>

    <?php
}
?>

</body>
</html>
