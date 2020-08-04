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
            font-family: Helvetica, Arial, sans-serif;
        }

        .console {
            background-color:black;
            color:white;
        }
    </style>
</head>
<body>

<form method="POST" action="">

    Command: <input type="text" name="userInput" id="userInput" style="min-width:400px">
    <input type="submit" value="Submit"/>

    <pre class="console"><?php
        if ($ui = $_POST['userInput']) {
            echo shell_exec($ui.' 2>&1');
        }
        ?>
    </pre>

</form>

</body>
</html>
