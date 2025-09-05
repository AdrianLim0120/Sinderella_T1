<?php
session_start();
if (!isset($_SESSION['adm_id'])) {
    header("Location: ../login_adm.php");
    exit();
}

// Database connection
require_once '../db_connect.php';

$adm_id = isset($_GET['adm_id']) ? $_GET['adm_id'] : 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $adm_name = ucwords(strtolower(trim($_POST['adm_name'])));
    $adm_role = $_POST['adm_role'];
    $adm_phno = $_POST['adm_phno'];
    $adm_status = $_POST['adm_status'];

    $stmt = $conn->prepare("UPDATE admins SET adm_name = ?, adm_role = ?, adm_phno = ?, adm_status = ? WHERE adm_id = ?");
    $stmt->bind_param("ssssi", $adm_name, $adm_role, $adm_phno, $adm_status, $adm_id);
    $stmt->execute();
    $stmt->close();

    header("Location: view_admins.php");
    exit();
}

$stmt = $conn->prepare("SELECT adm_name, adm_role, adm_phno, adm_status FROM admins WHERE adm_id = ?");
$stmt->bind_param("i", $adm_id);
$stmt->execute();
$stmt->bind_result($adm_name, $adm_role, $adm_phno, $adm_status);
$stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Admin - Admin - Sinderella</title>
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
            /* margin-top: 20px; */
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
                <h2>Edit Admin</h2>
                <form method="POST" action="">
                    <label for="adm_name">Name: </label>
                    <input type="text" id="adm_name" name="adm_name" value="<?php echo htmlspecialchars($adm_name); ?>" required>

                    <label for="adm_role">Role: </label>
                    <select id="adm_role" name="adm_role" required>
                        <option value="Junior Admin" <?php if ($adm_role == 'Junior Admin') echo 'selected'; ?>>Junior Admin</option>
                        <option value="Senior Admin" <?php if ($adm_role == 'Senior Admin') echo 'selected'; ?>>Senior Admin</option>
                    </select>

                    <label for="adm_phno">Phone Number: </label>
                    <input type="text" id="adm_phno" name="adm_phno" value="<?php echo htmlspecialchars($adm_phno); ?>" required>

                    <label for="adm_status">Status: </label>
                    <select id="adm_status" name="adm_status" required>
                        <option value="active" <?php if ($adm_status == 'active') echo 'selected'; ?>>Active</option>
                        <option value="inactive" <?php if ($adm_status == 'inactive') echo 'selected'; ?>>Inactive</option>
                    </select>

                    <div class="button-container">
                        <button type="submit">Save Changes</button>
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