<?php
session_start();
if (!isset($_SESSION['sind_id'])) {
    header("Location: ../login_sind.php");
    exit();
}

// Database connection
require_once '../db_connect.php';

$sind_id = $_SESSION['sind_id'];
$error_message = "";
$success_message = "";

// Handle form submission to add a downline
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $downline_phone = preg_replace('/[\s-]/', '', $_POST['downline_phone']); // Sanitize phone number

    // Check if phone number is numeric and starts with "0"
    if (!ctype_digit($downline_phone) || $downline_phone[0] !== '0') {
        $error_message = "Phone number must be numeric and start with '0'.";
    } else {
        // Check if the downline already exists for this Sinderella
        $stmt = $conn->prepare("SELECT 1 FROM sind_downline WHERE sind_id = ? AND dwln_phno = ?");
        $stmt->bind_param("is", $sind_id, $downline_phone);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error_message = "This downline is already added.";
            $stmt->close();
        } else {
            $stmt->close();
            // Check if the phone number is already used by an existing Sinderella
            $stmt = $conn->prepare("SELECT sind_id FROM sinderellas WHERE sind_phno = ?");
            $stmt->bind_param("s", $downline_phone);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $error_message = "This phone number is already in use by an existing Sinderella.";
                $stmt->close();
            } else {
                $stmt->close();
                // Insert the downline into the sind_downline table
                $stmt = $conn->prepare("INSERT INTO sind_downline (sind_id, dwln_phno, created_at) VALUES (?, ?, NOW())");
                $stmt->bind_param("is", $sind_id, $downline_phone);
                if ($stmt->execute()) {
                    $success_message = "Downline added successfully.";
                } else {
                    $error_message = "Failed to add downline. Please try again.";
                }
                $stmt->close();
            }
        }
    }
}

// Fetch the downlines for the current Sinderella
$stmt = $conn->prepare("
    SELECT sd.dwln_phno, sd.dwln_id, sd.created_at, s.sind_name, s.sind_status
    FROM sind_downline sd
    LEFT JOIN sinderellas s ON sd.dwln_id = s.sind_id
    WHERE sd.sind_id = ?
    ORDER BY sd.created_at DESC
");
$stmt->bind_param("i", $sind_id);
$stmt->execute();
$result = $stmt->get_result();
$downlines_raw = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Separate downlines into selected, not registered, and not selected
$selected = [];
$not_registered = [];
$not_selected = [];

foreach ($downlines_raw as $downline) {
    if (is_null($downline['dwln_id'])) {
        $not_registered[] = $downline;
    } elseif ($downline['dwln_id'] == -1) {
        $not_selected[] = $downline;
    } else {
        $selected[] = $downline;
    }
}

// Function to format phone numbers
function formatPhoneNumber($phone) {
    if (preg_match('/^011\d{8}$/', $phone)) {
        return preg_replace('/^(\d{3})(\d{4})(\d{4})$/', '$1-$2 $3', $phone);
    } elseif (preg_match('/^01[0-9]\d{7}$/', $phone)) {
        return preg_replace('/^(\d{3})(\d{3})(\d{4})$/', '$1-$2 $3', $phone);
    }
    return $phone; // Return as is if it doesn't match the expected format
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Downline - Sinderella</title>
    <link rel="icon" href="../img/sinderella_favicon.png"/>
    <link rel="stylesheet" href="../includes/css/styles_user.css">
    <style>
        .downline-table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 20px;
        }
        .downline-table th, .downline-table td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: center;
        }
        .downline-table th {
            background-color: #0c213b;
            color: white;
        }
        .form-container {
            margin-bottom: 20px;
        }
        .form-container label {
            display: block;
            margin-bottom: 5px;
        }
        .form-container input {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .form-container button {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .form-container button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <?php include '../includes/menu/menu_sind.php'; ?>
        <div class="content-container">
            <?php include '../includes/header_sind.php'; ?>
            <div class="profile-container">
                <h2>Manage Downline</h2>

                <!-- Form to add a downline -->
                <div class="form-container">
                    <form action="manage_downline.php" method="POST">
                        <label for="downline_phone">Downline Phone Number:</label>
                        <input type="text" id="downline_phone" name="downline_phone" placeholder="Exp: 0123456789" required>
                        <button type="submit">Add Downline</button>
                    </form>
                    <?php if ($error_message): ?>
                        <p style="color: red;"><?php echo $error_message; ?></p>
                    <?php endif; ?>
                    <?php if ($success_message): ?>
                        <p style="color: green;"><?php echo $success_message; ?></p>
                    <?php endif; ?>
                </div>

                <!-- Table to display downlines -->
                <h3>Your Downlines</h3>
                <table class="downline-table">
                    <thead>
                        <tr>
                            <th>Phone Number</th>
                            <th>Registered Name</th>
                            <th>Status</th>
                            <th>Added On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Show selected downlines (registered and selected this upline) at the top
                        foreach ($selected as $downline): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(formatPhoneNumber($downline['dwln_phno'])); ?></td>
                                <td><?php echo htmlspecialchars($downline['sind_name'] ?? 'Not Registered'); ?></td>
                                <td><?php echo htmlspecialchars($downline['sind_status'] ?? 'Not Registered'); ?></td>
                                <td><?php echo $downline['created_at'] ? htmlspecialchars($downline['created_at']) : '-'; ?></td>
                            </tr>
                        <?php endforeach; ?>

                        <?php
                        // Show not registered downlines (dwln_id is NULL)
                        foreach ($not_registered as $downline): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(formatPhoneNumber($downline['dwln_phno'])); ?></td>
                                <td colspan="2" style="color:#888;">Not registered yet</td>
                                <td><?php echo $downline['created_at'] ? htmlspecialchars($downline['created_at']) : '-'; ?></td>
                            </tr>
                        <?php endforeach; ?>

                        <?php
                        // Show not selected downlines (dwln_id is -1) at the bottom
                        foreach ($not_selected as $downline): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(formatPhoneNumber($downline['dwln_phno'])); ?></td>
                                <td colspan="2" style="color:#888;">Not selected by downline</td>
                                <td><?php echo $downline['created_at'] ? htmlspecialchars($downline['created_at']) : '-'; ?></td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (count($selected) + count($not_registered) + count($not_selected) == 0): ?>
                            <tr>
                                <td colspan="4">No downlines added yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>