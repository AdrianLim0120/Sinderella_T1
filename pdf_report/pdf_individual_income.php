<?php
session_start();

require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../vendor/autoload.php';

$target_sind_id = isset($_SESSION['sind_id']) ? (int) $_SESSION['sind_id'] : 0;
$start = isset($_GET['start']) ? $_GET['start'] : '';
$end = isset($_GET['end']) ? $_GET['end'] : '';

if (!$target_sind_id) {
    deny_pdf_access();
}

function deny_pdf_access() {
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    echo "<script>alert('You are not authorized to view this payslip.'); window.close();</script>";
    exit;
}

$validDate = function ($s) {
    return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $s);
};

if (!$validDate($start) || !$validDate($end)) {
    http_response_code(400);
    exit('Invalid or missing start/end date (expected YYYY-MM-DD).');
}

$sqlSind = "SELECT sind_name, sind_phno FROM sinderellas WHERE sind_id = ?";
$stmtSind = $conn->prepare($sqlSind);
$stmtSind->bind_param('i', $target_sind_id);
$stmtSind->execute();
$resSind = $stmtSind->get_result();
$sind = $resSind->fetch_assoc();
$stmtSind->close();

if (!$sind) {
    http_response_code(404);
    exit('Sinderella not found.');
}

$sindName = $sind['sind_name'] ?? '';
$sindPhno = $sind['sind_phno'] ?? '';


$sql = "
SELECT
    b.sind_id,
    b.booking_id,
    b.booking_date,
    s.sind_name,
    s.sind_icno,
    s.sind_phno,
    s.sind_bank_acc_no,
    s.sind_bank_name,
    s.sind_status,
    COALESCE(b.bp_sind,0) AS bp_sind
FROM bookings b
LEFT JOIN sinderellas s ON s.sind_id = b.sind_id
WHERE b.sind_id = ?
  AND b.booking_date >= ?
  AND b.booking_date <= ?
  AND b.booking_status IN ('done','rated')
ORDER BY b.booking_date ASC, b.booking_from_time ASC, b.booking_id ASC
";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    exit('Prepare failed: ' . $conn->error);
}
$stmt->bind_param('iss', $target_sind_id, $start, $end);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
$total = 0.0;
while ($r = $res->fetch_assoc()) {
    $sind_name = $r['sind_name'];
    $sind_icno = $r['sind_icno'];
    $sind_phno = $r['sind_phno'];
    $sind_bank_acc = $r['sind_bank_acc_no'];
    $sind_bank = $r['sind_bank_name'];
    $sind_status = $r['sind_status'];

    $amt = number_format((float) $r['bp_sind'], 2);
    $total += (float) $r['bp_sind'];
}
$stmt->close();

$rangeHuman = date('md', strtotime($start)) . date('md', strtotime($end));

$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'tempDir' => __DIR__ . '/../tmp',
]);

// --------- Styles ---------
$css = "
body { 
    font-family: sans-serif; 
    font-size: 12px; 
    color:#111; 
}

.hdr { 
    margin-bottom: 10px; 
}

.hdr h2 { 
    margin: 0 0 4px 0; 
    font-size: 18px;
}

.meta { 
    margin: 8px 0 8px 0; 
    font-size: 12px; 
}

.meta div { 
    margin: 2px 0; 
}

table { 
    border-collapse: collapse; 
    width: 100%; 
}

.right { 
    text-align: right; 
}

.total-row td { 
    font-weight: 700; 
    background: #fafafa; 
}

.small { 
    
    color: #555; 
}

.image-container {
    text-align: center;
    margin-bottom: 12px;
}

img {
    max-width: 60%;
    height: auto;
}

.company-info {
    margin-bottom: 40px;
    padding: 0px 60px;
}

.company-info strong {
    font-size: 16px; 
}   

hr {
    height: 2px;
    background-color: #000000;
}

#personal_income th, #personal_income td {
    padding: 4px;
    text-align: left;
}

#personal_income th {
    text-decoration: underline;
    font-weight: bold;
    font-size: 13px;
}

#personal_income td {
    font-size: 11px;
}

.total-row td {
    font-size: 18px;
    font-weight: bold;
    color: #ff0000;
}

";

$company = "Sinderella Kleen Sdn Bhd";
$site = "www.sinderella.com.my";
$reg_no = "1246103-A";

$html = "
<html>
<head><meta charset='utf-8'></head>
<body>
    <div class='hdr'>
        <div class='image-container'>
            <img src='../img/sinderella_logo.png' alt='Sinderella Logo'>
        </div>
        <div class='company-info'>
            &emsp;&emsp;&emsp;&emsp;&emsp;&emsp;
            <strong class='left'>" . htmlspecialchars($company) . "</strong>
            &emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;
            <strong class='right'>" . htmlspecialchars($reg_no) . "</strong>
            <div>&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;" . htmlspecialchars($site) . "</div>
        </div>

        <div class='meta'>
            <table cellspacing='0' cellpadding='5' style='width: 100%; margin-bottom: 10px;'>
                <tr>
                    <td style='border: 0px solid #000; border-bottom: 0; text-align: left;' class='headertabletext'>Sinderella ID:</td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 0;' class='headertabletext'>". $target_sind_id ."</td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 0; text-align: left;' class='headertabletext'>Contact Number:</td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 0;' class='headertabletext'>". $sind_phno ."</td>
                </tr>
                <tr>
                    <td style='border: 0px solid #000; text-align: left;'>Name:</td>
                    <td style='border: 0px solid #000; border-left: 0;'>" . $sind_name . "</td>
                    <td style='border: 0px solid #000; border-left: 0; text-align: left;'>Bank:</td>
                    <td style='border: 0px solid #000; border-left: 0;'>" . $sind_bank . "</td>
                </tr>
                <tr>
                    <td style='border: 0px solid #000; border-bottom: 0; text-align: left;' width='20%'>IC No.:</td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 0;' width='30%'>" . $sind_icno . "</td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 0; text-align: left;' width='20%'>Account Number:</td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 0;' width='30%'>" . $sind_bank_acc . "</td>
                </tr>
                <tr>
                    <td style='border: 0px solid #000; text-align: left;' width='20%'>Income Week:</td>
                    <td style='border: 0px solid #000; border-left: 0;' width='30%'>" . $rangeHuman . "</td>
                    <td style='border: 0px solid #000; border-left: 0; text-align: left;' width='20%'>Current Status:</td>
                    <td style='border: 0px solid #000; border-left: 0;' width='30%'>" . $sind_status . "</td>
                </tr>
            </table>
            <hr>
        </div>";

    $sql_adhoc_service_4 = "
        SELECT
            COUNT(*) AS num,
            COALESCE(p.sinderella, 0) AS unit_price,
            COUNT(*) * COALESCE(p.sinderella, 0) AS total
        FROM bookings b
        JOIN pricings p
        ON p.service_id = b.service_id
        AND p.service_type = b.booking_type
        WHERE b.sind_id = ?
        AND b.booking_date >= ?
        AND b.booking_date <= ?
        AND b.booking_status IN ('done','rated')
        AND b.booking_type = 'a'
        AND b.service_id = 1
    ";

    $stmt1 = $conn->prepare($sql_adhoc_service_4);
    $stmt1->bind_param('iss', $target_sind_id, $start, $end);
    $stmt1->execute();
    $res = $stmt1->get_result();

    while ($ad = $res->fetch_assoc()) {
        $ad_num = number_format($ad['num']);
        $ad_unit_price = number_format($ad['unit_price'], 2);
        $ad_total = number_format($ad['total'], 2);
    }
    $stmt1->close();

    $sql_adhoc_service_2 = "
        SELECT
            COUNT(*) AS num,
            COALESCE(p.sinderella, 0) AS unit_price,
            COUNT(*) * COALESCE(p.sinderella, 0) AS total
        FROM bookings b
        JOIN pricings p
        ON p.service_id = b.service_id
        AND p.service_type = b.booking_type
        WHERE b.sind_id = ?
        AND b.booking_date >= ?
        AND b.booking_date <= ?
        AND b.booking_status IN ('done','rated')
        AND b.booking_type = 'a'
        AND b.service_id = 2
    ";
    $stmt1 = $conn->prepare($sql_adhoc_service_2);
    $stmt1->bind_param('iss', $target_sind_id, $start, $end);
    $stmt1->execute();
    $res = $stmt1->get_result();

    while ($ad = $res->fetch_assoc()) {
        $ad_num_2 = number_format($ad['num']);
        $ad_unit_price_2 = number_format($ad['unit_price'], 2);
        $ad_total_2 = number_format($ad['total'], 2);
    }
    $stmt1->close();

    $sql_ad_extra = "SELECT
                    COUNT(ba.booking_addon_id) AS num,
                    (SELECT ao_sind FROM addon WHERE ao_id = 1) AS unit_price,
                    COUNT(ba.booking_addon_id) * (SELECT ao_sind FROM addon WHERE ao_id = 1) AS total
                FROM booking_addons ba
                LEFT JOIN bookings b ON b.booking_id = ba.booking_id
                LEFT JOIN addon a ON ba.ao_id = a.ao_id
                WHERE b.sind_id = ?
                    AND b.booking_date >= ?
                    AND b.booking_date <= ?
                    AND b.booking_status IN ('done','rated')
                    AND b.booking_type ='a'
                    AND ba.ao_id = 1";

    $stmt3 = $conn->prepare($sql_ad_extra);
    $stmt3->bind_param('iss', $target_sind_id, $start, $end);
    $stmt3->execute();
    $res3 = $stmt3->get_result();

    while ($extra_ad = $res3->fetch_assoc()) {
        $extra_ad_num = number_format($extra_ad['num']);
        $extra_ad_unit_price = number_format($extra_ad['unit_price'], 2);
        $extra_ad_total = number_format($extra_ad['total'], 2);
    }
    $stmt3->close();

    $sql_ad_cleaning = "SELECT
                    COUNT(ba.booking_addon_id) AS num,
                    (SELECT ao_sind FROM addon WHERE ao_id = 2) AS unit_price,
                    COUNT(ba.booking_addon_id) * (SELECT ao_sind FROM addon WHERE ao_id = 2) AS total
                FROM booking_addons ba
                LEFT JOIN bookings b ON b.booking_id = ba.booking_id
                LEFT JOIN addon a ON ba.ao_id = a.ao_id
                WHERE b.sind_id = ?
                    AND b.booking_date >= ?
                    AND b.booking_date <= ?
                    AND b.booking_status IN ('done','rated')
                    AND b.booking_type ='a'
                    AND ba.ao_id = 2";

    $stmt5 = $conn->prepare($sql_ad_cleaning);
    $stmt5->bind_param('iss', $target_sind_id, $start, $end);
    $stmt5->execute();
    $res5 = $stmt5->get_result();

    while ($cleaning_ad = $res5->fetch_assoc()) {
        $cleaning_ad_num = number_format($cleaning_ad['num']);
        $cleaning_ad_unit_price = number_format($cleaning_ad['unit_price'], 2);
        $cleaning_ad_total = number_format($cleaning_ad['total'], 2);
    }
    $stmt5->close();

    $adhoc_total = number_format($ad_total + $extra_ad_total + $cleaning_ad_total + $ad_total_2, 2);

        $html .= "
        <div class='income'>
            <h3>Income I - Personal Income</h3>
            <table cellspacing='0' cellpadding='5' style='width: 80%; margin-bottom: 10px;' id='personal_income'>
                <tr>
                    <th style='text-align: left;'>Ad-Hoc</th>
                </tr>
                <tr>
                    <td style='border: 0px solid #000; border-bottom: 0; text-align: left;' width='30%'>4 Hours Service</td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 0; text-align: left;' width='20%'>" . $ad_num . "</td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 0; text-align: left;' width='20%'>RM " . $ad_unit_price . "</td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 0; text-align: left;' width='20%'>RM " . $ad_total . "</td>
                    <td></td>
                </tr>
                <tr>
                    <td style='border: 0px solid #000; border-bottom: 0; text-align: left;' width='30%'>2 Hours Service</td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 0; text-align: left;' width='20%'>" . $ad_num_2 . "</td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 0; text-align: left;' width='20%'>RM " . $ad_unit_price_2 . "</td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 0; text-align: left;' width='20%'>RM " . $ad_total_2 . "</td>
                    <td></td>
                </tr>
                <tr>
                    <td style='border: 0px solid #000; border-bottom: 0; text-align: left;' width='30%'>Extra Hour</td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 0; text-align: left;' width='20%'>" . $extra_ad_num . "</td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 0; text-align: left;' width='20%'>RM " . $extra_ad_unit_price . "</td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 0; text-align: left;' width='20%'>RM " . $extra_ad_total . "</td>
                    <td></td>
                </tr>
                <tr>
                    <td style='border: 0px solid #000; border-bottom: 0; text-align: left;' width='30%'>Cleaning Tools</td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 0; text-align: left;' width='20%'>" . $cleaning_ad_num . "</td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 0; text-align: left;' width='20%'>RM " . $cleaning_ad_unit_price . "</td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 1px solid #000; text-align: left;' width='10%'>RM " . $cleaning_ad_total . "</td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 0; text-align: left;' width='5%'></td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 0; text-align: left;' width='20%'>RM " . $adhoc_total . "</td>
                </tr>
                <tr>
                    <td></td>
                </tr>
                <tr>
                    <td></td>
                </tr>";
        
    $sql_rec_service_4 = "
        SELECT
            COUNT(*) AS num,
            COALESCE(p.sinderella, 0) AS unit_price,
            COUNT(*) * COALESCE(p.sinderella, 0) AS total
        FROM bookings b
        JOIN pricings p
        ON p.service_id = b.service_id
        AND p.service_type = b.booking_type
        WHERE b.sind_id = ?
        AND b.booking_date >= ?
        AND b.booking_date <= ?
        AND b.booking_status IN ('done','rated')
        AND b.booking_type = 'r'
        AND b.service_id = 1
    ";

    $stmt2 = $conn->prepare($sql_rec_service_4);
    $stmt2->bind_param('iss', $target_sind_id, $start, $end);
    $stmt2->execute();
    $res2 = $stmt2->get_result();

    while ($rec = $res2->fetch_assoc()) {
        $rec_num = number_format($rec['num']);
        $rec_unit_price = number_format($rec['unit_price'], 2, '.', '');
        $rec_total = number_format($rec['total'], 2, '.', '');
    }
    $stmt2->close();

    $sql_rec_service_2 = "
        SELECT
            COUNT(*) AS num,
            COALESCE(p.sinderella, 0) AS unit_price,
            COUNT(*) * COALESCE(p.sinderella, 0) AS total
        FROM bookings b
        JOIN pricings p
        ON p.service_id = b.service_id
        AND p.service_type = b.booking_type
        WHERE b.sind_id = ?
        AND b.booking_date >= ?
        AND b.booking_date <= ?
        AND b.booking_status IN ('done','rated')
        AND b.booking_type = 'r'
        AND b.service_id = 2
    ";

    $stmt2 = $conn->prepare($sql_rec_service_2);
    $stmt2->bind_param('iss', $target_sind_id, $start, $end);
    $stmt2->execute();
    $res2 = $stmt2->get_result();

    while ($rec = $res2->fetch_assoc()) {
        $rec_num_2 = number_format($rec['num']);
        $rec_unit_price_2 = number_format($rec['unit_price'], 2, '.', '');
        $rec_total_2 = number_format($rec['total'], 2, '.', '');
    }
    $stmt2->close();

    $sql_rec_extra = "SELECT
                    COUNT(ba.booking_addon_id) AS num,
                    (SELECT ao_sind_recurring FROM addon WHERE ao_id = 1) AS unit_price,
                    COUNT(ba.booking_addon_id) * (SELECT ao_sind_recurring FROM addon WHERE ao_id = 1) AS total
                FROM booking_addons ba
                LEFT JOIN bookings b ON b.booking_id = ba.booking_id
                LEFT JOIN addon a ON ba.ao_id = a.ao_id
                WHERE b.sind_id = ?
                    AND b.booking_date >= ?
                    AND b.booking_date <= ?
                    AND b.booking_status IN ('done','rated')
                    AND b.booking_type ='r'
                    AND ba.ao_id = 1";

    $stmt4 = $conn->prepare($sql_rec_extra);
    $stmt4->bind_param('iss', $target_sind_id, $start, $end);
    $stmt4->execute();
    $res4 = $stmt4->get_result();

    while ($extra_rec = $res4->fetch_assoc()) {
        $extra_rec_num = number_format($extra_rec['num']);
        $extra_rec_unit_price = number_format($extra_rec['unit_price'], 2);
        $extra_rec_total = number_format($extra_rec['total'], 2);
    }
    $stmt4->close();

    $sql_rec_cleaning = "SELECT
                    COUNT(ba.booking_addon_id) AS num,
                    (SELECT ao_sind_recurring FROM addon WHERE ao_id = 2) AS unit_price,
                    COUNT(ba.booking_addon_id) * (SELECT ao_sind_recurring FROM addon WHERE ao_id = 2) AS total
                FROM booking_addons ba
                LEFT JOIN bookings b ON b.booking_id = ba.booking_id
                LEFT JOIN addon a ON ba.ao_id = a.ao_id
                WHERE b.sind_id = ?
                    AND b.booking_date >= ?
                    AND b.booking_date <= ?
                    AND b.booking_status IN ('done','rated')
                    AND b.booking_type ='r'
                    AND ba.ao_id = 2";

    $stmt6 = $conn->prepare($sql_rec_cleaning);
    $stmt6->bind_param('iss', $target_sind_id, $start, $end);
    $stmt6->execute();
    $res6 = $stmt6->get_result();

    while ($cleaning_rec = $res6->fetch_assoc()) {
        $cleaning_rec_num = number_format($cleaning_rec['num']);
        $cleaning_rec_unit_price = number_format($cleaning_rec['unit_price'], 2);
        $cleaning_rec_total = number_format($cleaning_rec['total'], 2);
    }
    $stmt6->close();

    $recurring_total = number_format($rec_total + $extra_rec_total + $cleaning_rec_total + $rec_total_2, 2);
    $grand_total = number_format($recurring_total + $adhoc_total, 2);

        $html .= "
                <tr>
                    <th style='text-align: left;'>Recurring</th>
                </tr>
                <tr>
                    <td style='border: 0px solid #000; border-bottom: 0; text-align: left;' width='30%'>4 Hours Service</td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 0; text-align: left;' width='20%'>" . $rec_num . "</td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 0; text-align: left;' width='20%'>RM " . $rec_unit_price . "</td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 0; text-align: left;' width='20%'>RM " . $rec_total . "</td>
                    <td></td>
                </tr>
                <tr>
                    <td style='border: 0px solid #000; border-bottom: 0; text-align: left;' width='30%'>2 Hours Service</td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 0; text-align: left;' width='20%'>" . $rec_num_2 . "</td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 0; text-align: left;' width='20%'>RM " . $rec_unit_price_2 . "</td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 0; text-align: left;' width='20%'>RM " . $rec_total_2 . "</td>
                    <td></td>
                </tr>
                <tr>
                    <td style='border: 0px solid #000; border-bottom: 0; text-align: left;' width='30%'>Extra Hour</td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 0; text-align: left;' width='20%'>" . $extra_rec_num . "</td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 0; text-align: left;' width='20%'>RM " . $extra_rec_unit_price . "</td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 0; text-align: left;' width='20%'>RM " . $extra_rec_total . "</td>
                    <td></td>
                </tr>
                <tr>
                    <td style='border: 0px solid #000; border-bottom: 0; text-align: left;' width='30%'>Cleaning Tools</td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 0; text-align: left;' width='20%'>" . $cleaning_rec_num . "</td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 0; text-align: left;' width='20%'>RM " . $cleaning_rec_unit_price . "</td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 1px solid #000; text-align: left;' width='10%'>RM " . $cleaning_rec_total . "</td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 0; text-align: left;' width='5%'></td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 0; text-align: left;' width='20%'>RM " . $recurring_total . "</td>
                </tr>
                <tr>
                    <td></td>
                </tr>
                <tr>
                    <td></td>
                </tr>
                <tr>
                    <td></td>
                </tr>
                <tr>
                    <td></td>
                </tr>
                <tr class='total-row'>
                    <td colspan='5'>Total income of the week </td>
                    <td style='border: 2px solid #000; border-left: 0; border-right: 0; text-align: left;'>RM " . $grand_total . "</td>
                </tr>
            </table>
        </div>

    </div>
</body>
</html>
";

// --------- Output ---------
$mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
$mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);

$fname = 'Weekly_Income' . $target_sind_id . '_' . $start . '_' . $end . '.pdf';
$mpdf->SetTitle("Weekly_Income {$sindName} {$start}–{$end}");
$mpdf->Output($fname, \Mpdf\Output\Destination::INLINE); // open in browser
