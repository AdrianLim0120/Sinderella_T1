<?php
require_once '../db_connect.php';
$sind_id = intval($_GET['sind_id'] ?? 0);

$stmt = $conn->prepare("
    SELECT br.rate, br.comment, c.cust_name
    FROM booking_ratings br
    LEFT JOIN customers c ON br.cust_id = c.cust_id
    WHERE br.sind_id = ?
    ORDER BY br.rated_at DESC
");
$stmt->bind_param("i", $sind_id);
$stmt->execute();
$stmt->bind_result($rate, $comment, $cust_name);

$rows = '';
while ($stmt->fetch()) {
    $max_stars = 5;
    $filled = intval($rate);
    $empty = $max_stars - $filled;
    $stars = str_repeat('&#11088;', $filled) . str_repeat('&#9734;', $empty); // &#9734; is ☆
    $rows .= '<tr>';
    $rows .= '<td>' . htmlspecialchars($cust_name ?? 'Unknown') . '</td>';
    $rows .= '<td style="text-align:center;">' . $stars . '</td>';
    $rows .= '<td>' . nl2br(htmlspecialchars($comment)) . '</td>';
    $rows .= '</tr>';
}
$stmt->close();

if ($rows === '') {
    $rows = '<tr><td colspan="3" style="text-align:center;color:#888;">No ratings found.</td></tr>';
}
echo $rows;
?>