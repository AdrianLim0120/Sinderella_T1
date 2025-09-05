<?php
require_once '../db_connect.php';
$cust_id = intval($_GET['cust_id'] ?? 0);

// $stmt = $conn->prepare("SELECT ROUND(AVG(rate),1) as avg_rating, COUNT(*) as total_reviews FROM cust_ratings WHERE cust_id = ?");
// $stmt->bind_param("i", $cust_id);
// $stmt->execute();
// $stmt->bind_result($avg_rating, $total_reviews);
// $stmt->fetch();
// $stmt->close();

// echo "<div style='font-size:18px;color:#F09E0B;margin-top:0;margin-bottom:10px;text-align:center;'>";
// echo "&#11088;" . ($avg_rating ? $avg_rating : 'N/A') . " <span style='color:#333;font-size:15px;'>(" . intval($total_reviews) . " review" . (intval($total_reviews) != 1 ? "s" : "") . ")</span>";
// echo "</div><hr>";

// $res = $conn->query("SELECT cr.comment FROM cust_ratings cr WHERE cr.cust_id = $cust_id AND cr.comment IS NOT NULL AND cr.comment != '' ORDER BY cr.rated_at DESC");
// if ($res->num_rows == 0) {
//     echo "<div>No public comments yet.</div>";
// } else {
//     echo "<div style='font-weight:bold;margin-bottom:8px;'>User comments: </div>";
//     echo "<ul style='padding-left:20px;'>";
//     while ($row = $res->fetch_assoc()) {
//         echo "<li style='margin-bottom:10px;'>" . nl2br(htmlspecialchars($row['comment'])) . "</li>";
//     }
//     echo "</ul>";
// }

$stmt = $conn->prepare("SELECT cmt_hse FROM cust_ratings WHERE cust_id = ? AND cmt_hse IS NOT NULL AND TRIM(cmt_hse) != '' ORDER BY rated_at DESC");
$stmt->bind_param("i", $cust_id);
$stmt->execute();
$stmt->bind_result($cmt_hse);

$comments = [];
while ($stmt->fetch()) {
    $comments[] = $cmt_hse;
}
$stmt->close();

if (count($comments) === 0) {
    echo "<div>No comment yet.</div>";
} else {
    echo "<div style='font-weight:bold;margin-bottom:8px;'>House comments:</div>";
    echo "<ul style='padding-left:20px;'>";
    foreach ($comments as $cmt) {
        echo "<li style='margin-bottom:10px;'>" . nl2br(htmlspecialchars($cmt)) . "</li>";
    }
    echo "</ul>";
}
?>