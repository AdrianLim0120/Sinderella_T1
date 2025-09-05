<?php
require_once '../db_connect.php';
$sind_id = intval($_GET['sind_id'] ?? 0);

$stmt = $conn->prepare("SELECT AVG(rate) as avg_rating, COUNT(*) as total_reviews FROM booking_ratings WHERE sind_id = ?");
$stmt->bind_param("i", $sind_id);
$stmt->execute();
$stmt->bind_result($avg_rating, $total_reviews);
$stmt->fetch();
$stmt->close();

echo "<div style='font-size:18px;color:#F09E0B;margin-top:0;margin-bottom:10px;text-align:center;'>";
echo "&#11088;" . ($avg_rating ? round($avg_rating, 1) : 'N/A') . " <span style='color:#333;font-size:15px;'>(" . intval($total_reviews) . " review" . (intval($total_reviews) != 1 ? "s" : "") . ")</span>";
echo "</div><hr>";

$res = $conn->query("SELECT r.comment FROM booking_ratings r WHERE r.sind_id = $sind_id AND r.public = 1 AND r.comment IS NOT NULL AND r.comment != '' ORDER BY r.rated_at DESC");
if ($res->num_rows == 0) {
    echo "<div>No public comments yet.</div>";
} else {
    echo "<div style='font-weight:bold;margin-bottom:8px;'>User comments: </div>";
    echo "<ul style='padding-left:20px;'>";
    while ($row = $res->fetch_assoc()) {
        echo "<li style='margin-bottom:10px;'>" . nl2br(htmlspecialchars($row['comment'])) . "</li>";
    }
    echo "</ul>";
}