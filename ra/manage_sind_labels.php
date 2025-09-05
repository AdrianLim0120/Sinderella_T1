<?php
session_start();
if (!isset($_SESSION['adm_id'])) {
    header("Location: ../login_adm.php");
    exit();
}

// Database connection
require_once '../db_connect.php';

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_label'])) {
        $slbl_name = ucwords(strtolower(trim($_POST['slbl_name'])));
        $slbl_color_code = $_POST['slbl_color_code'];
        $slbl_status = 'active';

        $stmt = $conn->prepare("INSERT INTO sind_label (slbl_name, slbl_color_code, slbl_status) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $slbl_name, $slbl_color_code, $slbl_status);
        $stmt->execute();
        $stmt->close();

        $success_message = 'Label added successfully.';
    } elseif (isset($_POST['update_label'])) {
        $slbl_id = $_POST['slbl_id'];
        $slbl_name = ucwords(strtolower(trim($_POST['slbl_name'])));
        $slbl_color_code = $_POST['slbl_color_code'];
        $slbl_status = $_POST['slbl_status'];

        $stmt = $conn->prepare("UPDATE sind_label SET slbl_name = ?, slbl_color_code = ?, slbl_status = ? WHERE slbl_id = ?");
        $stmt->bind_param("sssi", $slbl_name, $slbl_color_code, $slbl_status, $slbl_id);
        $stmt->execute();
        $stmt->close();

        $success_message = 'Label updated successfully.';
    }
}

// Fetch labels
$labels = $conn->query("SELECT slbl_id, slbl_name, slbl_color_code, slbl_status FROM sind_label");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Labels - Sinderella</title>
    <link rel="icon" href="../img/sinderella_favicon.png"/>
    <link rel="stylesheet" href="../includes/css/styles_user.css">
    <style>
        .profile-container label {
            display: block;
            margin-top: 10px;
        }
        .profile-container input[type="text"],
        .profile-container input[type="color"],
        .profile-container select {
            width: calc(50% - 10px);
            padding: 5px;
            margin-right: 10px;
        }
        .profile-container button {
            /* margin-top: 20px; */
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
        .success-message {
            color: green;
            margin-top: 10px;
        }
        .label-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .label-table th, .label-table td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }
        .label-table th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <?php include '../includes/menu/menu_adm.php'; ?>
        <div class="content-container">
            <?php include '../includes/header_adm.php'; ?>
            <div class="profile-container">
                <h2>Manage Sinderella's Labels</h2>
                <?php if ($error_message): ?>
                    <p class="error-message"><?php echo $error_message; ?></p>
                <?php endif; ?>
                <?php if ($success_message): ?>
                    <p class="success-message"><?php echo $success_message; ?></p>
                <?php endif; ?>
                <form method="POST" action="">
                    <label for="slbl_name">Label Name</label>
                    <input type="text" id="slbl_name" name="slbl_name" required>

                    <label for="slbl_color_code">Label Color</label>
                    <input type="color" id="slbl_color_code" name="slbl_color_code" required>

                    <div class="button-container">
                        <button type="submit" name="add_label">Add Label</button>
                    </div>
                </form>
                <table class="label-table">
                    <thead>
                        <tr>
                            <th>Label Name</th>
                            <th>Label Color</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($label = $labels->fetch_assoc()): ?>
                            <tr>
                                <form method="POST" action="">
                                    <td>
                                        <input type="hidden" name="slbl_id" value="<?php echo $label['slbl_id']; ?>">
                                        <input type="text" name="slbl_name" value="<?php echo htmlspecialchars($label['slbl_name']); ?>" required>
                                        
                                        <?php if ($label['slbl_id'] == 1): ?>
                                            <br>
                                            <span style="color:#b71c1c; font-style:italic; font-size:13px;">
                                                *label for rejected 3 bookings within a month
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <input type="color" name="slbl_color_code" value="<?php echo htmlspecialchars($label['slbl_color_code']); ?>" required>
                                    </td>
                                    <td>
                                        <select name="slbl_status" required style="width:auto;">
                                            <option value="active" <?php if ($label['slbl_status'] == 'active') echo 'selected'; ?>>Active</option>
                                            <option value="inactive" <?php if ($label['slbl_status'] == 'inactive') echo 'selected'; ?>>Inactive</option>
                                        </select>
                                    </td>
                                    <td>
                                        <button type="submit" name="update_label">Update</button>
                                    </td>
                                </form>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <div class="button-container">
                    <button type="button" onclick="window.location.href='view_sinderellas.php'">Back</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

<?php
// // $conn->close();
?>