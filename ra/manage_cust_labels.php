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
        $clbl_name = ucwords(strtolower(trim($_POST['clbl_name'])));
        $clbl_color_code = $_POST['clbl_color_code'];
        $clbl_status = 'active';

        $stmt = $conn->prepare("INSERT INTO cust_label (clbl_name, clbl_color_code, clbl_status) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $clbl_name, $clbl_color_code, $clbl_status);
        $stmt->execute();
        $stmt->close();

        $success_message = 'Label added successfully.';
    } elseif (isset($_POST['update_label'])) {
        $clbl_id = $_POST['clbl_id'];
        $clbl_name = ucwords(strtolower(trim($_POST['clbl_name'])));
        $clbl_color_code = $_POST['clbl_color_code'];
        $clbl_status = $_POST['clbl_status'];

        $stmt = $conn->prepare("UPDATE cust_label SET clbl_name = ?, clbl_color_code = ?, clbl_status = ? WHERE clbl_id = ?");
        $stmt->bind_param("sssi", $clbl_name, $clbl_color_code, $clbl_status, $clbl_id);
        $stmt->execute();
        $stmt->close();

        $success_message = 'Label updated successfully.';
    }
}

// Fetch labels
$labels = $conn->query("SELECT clbl_id, clbl_name, clbl_color_code, clbl_status FROM cust_label");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Customer Labels - Admin - Sinderella</title>
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
                <h2>Manage Customer Labels</h2>
                <?php if ($error_message): ?>
                    <p class="error-message"><?php echo $error_message; ?></p>
                <?php endif; ?>
                <?php if ($success_message): ?>
                    <p class="success-message"><?php echo $success_message; ?></p>
                <?php endif; ?>
                <form method="POST" action="">
                    <label for="clbl_name">Label Name</label>
                    <input type="text" id="clbl_name" name="clbl_name" required>

                    <label for="clbl_color_code">Label Color</label>
                    <input type="color" id="clbl_color_code" name="clbl_color_code" required>

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
                                        <input type="hidden" name="clbl_id" value="<?php echo $label['clbl_id']; ?>">
                                        <input type="text" name="clbl_name" value="<?php echo htmlspecialchars($label['clbl_name']); ?>" required>
                                    </td>
                                    <td>
                                        <input type="color" name="clbl_color_code" value="<?php echo htmlspecialchars($label['clbl_color_code']); ?>" required>
                                    </td>
                                    <td>
                                        <select name="clbl_status" required style="width:auto;">
                                            <option value="active" <?php if ($label['clbl_status'] == 'active') echo 'selected'; ?>>Active</option>
                                            <option value="inactive" <?php if ($label['clbl_status'] == 'inactive') echo 'selected'; ?>>Inactive</option>
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
                    <button type="button" onclick="window.location.href='view_customers.php'">Back</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

<?php
// // $conn->close();
?>