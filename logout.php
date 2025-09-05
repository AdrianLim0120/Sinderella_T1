<?php
session_start();
session_unset();
session_destroy();
?>
<!DOCTYPE html>
<html>
<head>
    <script>
        sessionStorage.clear();
        window.location.href = "index.php";
    </script>
</head>
<body>
Logging out...
</body>
</html>