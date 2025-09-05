<?php
require_once '../db_connect.php';
$sind_id = intval($_GET['sind_id']);
$res = $conn->query("
    SELECT r.rating_id, r.rate, r.comment, r.public, r.cust_id, c.cust_name
    FROM booking_ratings r
    JOIN customers c ON r.cust_id = c.cust_id
    WHERE r.sind_id = $sind_id
    ORDER BY r.rated_at DESC
");
if ($res->num_rows == 0) {
    echo '<tr><td colspan="4">No ratings found.</td></tr>';
} else {
    while ($row = $res->fetch_assoc()) {
        $max_stars = 5;
        $filled = intval($row['rate']);
        $empty = $max_stars - $filled;
        $stars = str_repeat('&#11088;', $filled) . str_repeat('&#9734;', $empty); // &#9734; is ☆
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['cust_name']) . '</td>';
        echo '<td>' . $stars . '</td>';
        echo '<td>' . nl2br(htmlspecialchars($row['comment'])) . '</td>';
        echo '<td style="text-align:center;"><input type="checkbox" class="public-checkbox" data-rating-id="' . $row['rating_id'] . '" ' . ($row['public'] ? 'checked' : '') . '></td>';
        echo '</tr>';
    }
}
?>