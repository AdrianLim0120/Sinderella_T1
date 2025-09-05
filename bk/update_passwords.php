<?php
// Database connection
require_once '../db_connect.php'; //

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Array of plain text passwords and their corresponding user IDs
$passwords = [
    ['id' => 1, 'password' => 'pwd123'], // Customer One
    ['id' => 2, 'password' => 'pwd123'], // Customer Two
    ['id' => 1, 'password' => 'pwd123'], // Sinderella One
    ['id' => 2, 'password' => 'pwd123'], // Sinderella Two
    ['id' => 1, 'password' => 'pwd123'], // Admin One
    ['id' => 2, 'password' => 'pwd123'], // Admin Two
];

// Update customers table
foreach ($passwords as $entry) {
    $hashed_password = password_hash($entry['password'], PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE customers SET cust_pwd = ? WHERE cust_id = ?");
    $stmt->bind_param("si", $hashed_password, $entry['id']);
    $stmt->execute();
    $stmt->close();
}

// Update sinderellas table
foreach ($passwords as $entry) {
    $hashed_password = password_hash($entry['password'], PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE sinderellas SET sind_pwd = ? WHERE sind_id = ?");
    $stmt->bind_param("si", $hashed_password, $entry['id']);
    $stmt->execute();
    $stmt->close();
}

// Update admins table
foreach ($passwords as $entry) {
    $hashed_password = password_hash($entry['password'], PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE admins SET adm_pwd = ? WHERE adm_id = ?");
    $stmt->bind_param("si", $hashed_password, $entry['id']);
    $stmt->execute();
    $stmt->close();
}

// $conn->close();

echo "Passwords updated successfully.";
?>