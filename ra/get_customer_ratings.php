<?php
require_once '../db_connect.php';
$cust_id = intval($_GET['cust_id'] ?? 0);

// $res = $conn->query("
//     SELECT cr.rate, cr.comment, s.sind_name
//     FROM cust_ratings cr
//     JOIN sinderellas s ON cr.sind_id = s.sind_id
//     WHERE cr.cust_id = $cust_id
//     ORDER BY cr.rated_at DESC
// ");
// if ($res->num_rows == 0) {
//     echo '<tr><td colspan="3">No ratings found.</td></tr>';
// } else {
//     while ($row = $res->fetch_assoc()) {
//         $max_stars = 5;
//         $filled = intval($row['rate']);
//         $empty = $max_stars - $filled;
//         $stars = str_repeat('&#11088;', $filled) . str_repeat('&#9734;', $empty);
//         echo '<tr>';
//         echo '<td>' . htmlspecialchars($row['sind_name']) . '</td>';
//         echo '<td>' . $stars . '</td>';
//         echo '<td>' . nl2br(htmlspecialchars($row['comment'])) . '</td>';
//         echo '</tr>';
//     }
// }

$stmt = $conn->prepare("SELECT s.sind_name, cr.cmt_ppl, cr.cmt_hse, cr.rated_at
                        FROM cust_ratings cr
                        JOIN sinderellas s ON cr.sind_id = s.sind_id
                        WHERE cr.cust_id = ?
                        ORDER BY cr.rated_at DESC");
$stmt->bind_param("i", $cust_id);
$stmt->execute();
$stmt->bind_result($sind_name, $cmt_ppl, $cmt_hse, $rated_at);

$rows = [];
while ($stmt->fetch()) {
    $rows[] = [
        'sind_name' => $sind_name,
        'cmt_ppl' => $cmt_ppl,
        'cmt_hse' => $cmt_hse,
        'rated_at' => $rated_at
    ];
}
$stmt->close();

if (count($rows) === 0) {
    echo "<div>No comments yet.</div>";
} else {
    echo "<table border='1' cellpadding='6' style='width:100%; border-collapse:collapse;'>";
    echo "<thead><tr><th>Rated By</th><th>Comment to Customer</th><th>Comment to House</th></tr></thead><tbody>";
    foreach ($rows as $row) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['sind_name']) . "</td>";
        echo "<td>" . ($row['cmt_ppl'] ? nl2br(htmlspecialchars($row['cmt_ppl'])) : '<em>No comment</em>') . "</td>";
        echo "<td>" . ($row['cmt_hse'] ? nl2br(htmlspecialchars($row['cmt_hse'])) : '<em>No comment</em>') . "</td>";
        // echo "<td>" . htmlspecialchars(date('Y-m-d H:i', strtotime($row['rated_at']))) . "</td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
}
?>