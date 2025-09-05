<?php
require_once '../db_connect.php';
$rating_id = intval($_POST['rating_id']);
$public = intval($_POST['public']) ? 1 : 0;
$stmt = $conn->prepare("UPDATE booking_ratings SET public = ? WHERE rating_id = ?");
$stmt->bind_param("ii", $public, $rating_id);
$stmt->execute();
echo "OK";
?>