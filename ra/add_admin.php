<?php
session_start();
if (!isset($_SESSION['adm_id'])) {
    header("Location: ../login_adm.php");
    exit();
}

// Database connection
require_once '../db_connect.php';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $adm_name = ucwords(strtolower(trim($_POST['adm_name'])));
    $adm_role = $_POST['adm_role'];
    $adm_phno = $_POST['adm_phno'];
    $adm_pwd = bin2hex(random_bytes(4)); // Generate a random password

    // Check if phone number is already used
    $stmt = $conn->prepare("SELECT COUNT(*) FROM admins WHERE adm_phno = ?");
    $stmt->bind_param("s", $adm_phno);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        $error_message = 'The phone number is already used by another admin.';
    } else {
        $stmt = $conn->prepare("INSERT INTO admins (adm_name, adm_role, adm_phno, adm_pwd) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $adm_name, $adm_role, $adm_phno, $adm_pwd);
        $stmt->execute();
        $stmt->close();

        header("Location: view_admins.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Admin - Admin - Sinderella</title>
    <link rel="icon" href="../img/sinderella_favicon.png"/>
    <link rel="stylesheet" href="../includes/css/styles_user.css">
    <style>
        /* .profile-container {
            width: 100%;
            max-width: 800px;
            margin: auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #f9f9f9;
        } */
        .profile-container label {
            display: block;
            margin-top: 10px;
        }
        .profile-container input[type="text"],
        .profile-container input[type="number"],
        .profile-container select {
            width: calc(50% - 10px);
            padding: 5px;
            margin-right: 10px;
        }
        .profile-container button {
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .profile-container button:hover {
            background-color: #0056b3;
        }
        .button-container {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .error-message {
            color: red;
            margin-top: 10px;
        }
        .profile-container label {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <?php include '../includes/menu/menu_adm.php'; ?>
        <div class="content-container">
            <?php include '../includes/header_adm.php'; ?>
            <div class="profile-container">
                <h2>Add Admin</h2>
                <?php if ($error_message): ?>
                    <p class="error-message"><?php echo $error_message; ?></p>
                <?php endif; ?>
                <form method="POST" action="">
                    <label for="adm_name">Name: </label>
                    <input type="text" id="adm_name" name="adm_name" required>

                    <label for="adm_role">Role: </label>
                    <select id="adm_role" name="adm_role" required>
                        <option value="Junior Admin">Junior Admin</option>
                        <option value="Senior Admin">Senior Admin</option>
                    </select>

                    <label for="adm_phno">Phone Number: </label>
                    <input type="text" id="adm_phno" name="adm_phno" required>

                    <div class="button-container">
                        <button type="submit">Add Admin</button>
                        <button type="button" onclick="window.location.href='view_admins.php'">Back</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

<?php
// // $conn->close();
?>