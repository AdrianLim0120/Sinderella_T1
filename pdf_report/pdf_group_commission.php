<?php
session_start();

require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../vendor/autoload.php';

$target_sind_id = isset($_SESSION['sind_id']) ? (int) $_SESSION['sind_id'] : 0;
$monthOffset = isset($_GET['month_offset']) ? (int) $_GET['month_offset'] : 0;

$sqlSind = "SELECT 
                sind_name, 
                sind_phno,
                sind_icno,
                sind_phno,
                sind_bank_acc_no,
                sind_bank_name,
                sind_status
            FROM sinderellas WHERE sind_id = ?";
$stmtSind = $conn->prepare($sqlSind);
$stmtSind->bind_param('i', $target_sind_id);
$stmtSind->execute();
$resSind = $stmtSind->get_result();
$sind = $resSind->fetch_assoc();
$stmtSind->close();

$sind_name = $sind['sind_name'] ?? '';
$sind_phno = $sind['sind_phno'] ?? '';
$sind_icno = $sind['sind_icno'] ?? '';
$sind_bank_acc = $sind['sind_bank_acc_no'] ?? '';
$sind_bank = $sind['sind_bank_name'] ?? '';
$sind_status = $sind['sind_status'] ?? '';

if (!$sind) {
    http_response_code(404);
    exit('Sinderella not found.');
}

$monthOffset = isset($_GET['month_offset']) ? (int) $_GET['month_offset'] : 0;

$today = new DateTime('today');

$thisMonthFirst = (new DateTime(date('Y-m-01')));   // first of current month
$baseMonth = (clone $thisMonthFirst)->modify('-1 month'); // previous month

if ($monthOffset !== 0) {
    $baseMonth->modify(($monthOffset > 0 ? '+' : '') . $monthOffset . ' month');
}

$start = (clone $baseMonth)->modify('first day of this month');
$end = (clone $baseMonth)->modify('last day of this month');

$startStr = $start->format('Y-m-d');
$endStr = $end->format('Y-m-d');
$rangeText = $start->format('D, d M Y') . '  →  ' . $end->format('D, d M Y');
$monthYear = $start->format('F Y');

$rangeHuman = date('md', strtotime($startStr)) . date('md', strtotime($endStr));

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

        $sql_ad = "
            SELECT
                /* counts (each booking matched to its pricing row by service_id) */
                SUM(CASE WHEN b.bp_lvl1_sind_id = ? THEN 1 ELSE 0 END) AS num_l1,
                SUM(CASE WHEN b.bp_lvl2_sind_id = ? THEN 1 ELSE 0 END) AS num_l2,

                /* unit prices (they can differ by service_id; we’ll show one representative) */
                MAX(p.lvl1) AS unit_price_l1,
                MAX(p.lvl2) AS unit_price_l2,

                /* totals = sum the price for exactly those bookings that belong to the level */
                SUM(CASE WHEN b.bp_lvl1_sind_id = ? THEN COALESCE(p.lvl1,0) ELSE 0 END) AS total_l1,
                SUM(CASE WHEN b.bp_lvl2_sind_id = ? THEN COALESCE(p.lvl2,0) ELSE 0 END) AS total_l2
            FROM pricings p
            /* KEY CHANGE: join by both service_type and service_id so each booking
            matches exactly one pricing row */
            LEFT JOIN bookings b
            ON b.booking_type   = p.service_type
            AND b.service_id     = p.service_id
            AND b.booking_status IN ('done','rated')
            AND b.booking_date BETWEEN ? AND ?
            WHERE p.service_type = 'a'
        ";
        $stmt1 = $conn->prepare($sql_ad);
        $stmt1->bind_param('iiiiss',
            $target_sind_id, $target_sind_id,  // counts for L1 & L2
            $target_sind_id, $target_sind_id,  // totals for L1 & L2
            $startStr, $endStr                 // date range
        );
        $stmt1->execute();
        $res = $stmt1->get_result();

        $ad_numl1 = $ad_numl2 = 0;
        $ad_unit_price_l1 = $ad_unit_price_l2 = 0.0;
        $ad_total_l1 = $ad_total_l2 = 0.0;

        if ($ad = $res->fetch_assoc()) {
            $ad_numl1        = number_format((int)$ad['num_l1']);
            $ad_numl2        = number_format((int)$ad['num_l2']);
            $ad_unit_price_l1= number_format((float)$ad['unit_price_l1'], 2);
            $ad_unit_price_l2= number_format((float)$ad['unit_price_l2'], 2);
            $ad_total_l1     = number_format((float)$ad['total_l1'], 2);
            $ad_total_l2     = number_format((float)$ad['total_l2'], 2);
        }
        $stmt1->close();

        $ad_subtotal = number_format((float)$ad_total_l1 + (float)$ad_total_l2, 2);

        $html .= "
        <div class='income'>
            <h3>Income I - Monthly Commission</h3>
            <table cellspacing='0' cellpadding='5' style='width: 80%; margin-bottom: 10px;' id='personal_income'>
                <tr>
                    <th style='text-align: left;'>Ad-Hoc</th>
                </tr>
                <tr>
                    <td style='border: 0px solid #000; border-bottom: 0; text-align: left;' width='30%'>Level 1</td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 0; text-align: left;' width='20%'>" . $ad_numl1 . "</td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 0; text-align: left;' width='20%'>RM " . $ad_unit_price_l1 . "</td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 0; text-align: left;' width='20%'>RM " . $ad_total_l1 . "</td>
                    <td></td>
                </tr>
                <tr>
                    <td style='border: 0px solid #000; border-bottom: 0; text-align: left;' width='30%'>Level 2</td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 0; text-align: left;' width='20%'>" . $ad_numl2 . "</td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 0; text-align: left;' width='20%'>RM " . $ad_unit_price_l2 . "</td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 1px solid #000; text-align: left;' width='10%'>RM " . $ad_total_l2 . "</td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 0; text-align: left;' width='5%'></td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 0; text-align: left;' width='20%'>RM " . $ad_subtotal . "</td>
                </tr>
                <tr>
                    <td></td>
                </tr>
                <tr>
                    <td></td>
                </tr>";

        $sql_rec = "
            SELECT
                SUM(CASE WHEN b.bp_lvl1_sind_id = ? THEN 1 ELSE 0 END) AS num_l1,
                SUM(CASE WHEN b.bp_lvl2_sind_id = ? THEN 1 ELSE 0 END) AS num_l2,
                MAX(p.lvl1) AS unit_price_l1,
                MAX(p.lvl2) AS unit_price_l2,
                SUM(CASE WHEN b.bp_lvl1_sind_id = ? THEN COALESCE(p.lvl1,0) ELSE 0 END) AS total_l1,
                SUM(CASE WHEN b.bp_lvl2_sind_id = ? THEN COALESCE(p.lvl2,0) ELSE 0 END) AS total_l2
            FROM pricings p
            LEFT JOIN bookings b
            ON b.booking_type   = p.service_type
            AND b.service_id     = p.service_id
            AND b.booking_status IN ('done','rated')
            AND b.booking_date BETWEEN ? AND ?
            WHERE p.service_type = 'r'
        ";
        $stmt2 = $conn->prepare($sql_rec);
        $stmt2->bind_param('iiiiss',
            $target_sind_id, $target_sind_id,
            $target_sind_id, $target_sind_id,
            $startStr, $endStr
        );
        $stmt2->execute();
        $res = $stmt2->get_result();

        $rec_numl1 = $rec_numl2 = 0;
        $rec_unit_price_l1 = $rec_unit_price_l2 = 0.0;
        $rec_total_l1 = $rec_total_l2 = 0.0;

        if ($rec = $res->fetch_assoc()) {
            $rec_numl1        = number_format((int)$rec['num_l1']);
            $rec_numl2        = number_format((int)$rec['num_l2']);
            $rec_unit_price_l1= number_format((float)$rec['unit_price_l1'], 2);
            $rec_unit_price_l2= number_format((float)$rec['unit_price_l2'], 2);
            $rec_total_l1     = number_format((float)$rec['total_l1'], 2);
            $rec_total_l2     = number_format((float)$rec['total_l2'], 2);
        }
        $stmt2->close();

        $rec_subtotal = number_format((float)$rec_total_l1 + (float)$rec_total_l2, 2);
        $grand_total  = number_format((float)$ad_subtotal + (float)$rec_subtotal, 2);

        $html .= "
                <tr>
                    <th style='text-align: left;'>Recurring</th>
                </tr>
                <tr>
                    <td style='border: 0px solid #000; border-bottom: 0; text-align: left;' width='30%'>Level 1</td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 0; text-align: left;' width='20%'>" . $rec_numl1 . "</td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 0; text-align: left;' width='20%'>RM " . $rec_unit_price_l1 . "</td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 0; text-align: left;' width='20%'>RM " . $rec_total_l1 . "</td>
                    <td></td>
                </tr>
                <tr>
                    <td style='border: 0px solid #000; border-bottom: 0; text-align: left;' width='30%'>Level 2</td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 0; text-align: left;' width='20%'>" . $rec_numl2 . "</td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 0; text-align: left;' width='20%'>RM " . $rec_unit_price_l2 . "</td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 1px solid #000; text-align: left;' width='10%'>RM " . $rec_total_l2 . "</td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 0; text-align: left;' width='5%'></td>
                    <td style='border: 0px solid #000; border-left: 0; border-bottom: 0; text-align: left;' width='20%'>RM " . $rec_subtotal . "</td>
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
                    <td colspan='5'>Total income of " . $monthYear . "</td>
                    <td style='border: 2px solid #000; border-left: 0; border-right: 0; text-align: left;'>RM " . $grand_total . "</td>
                </tr>
            </table>
            <hr>
        </div>";

        $sql = "
        SELECT
            b.booking_id,
            b.booking_date,
            b.booking_type,
            s.sind_name AS downline_name,
            CASE
                WHEN b.bp_lvl1_sind_id = ? THEN 'Level 1'
                WHEN b.bp_lvl2_sind_id = ? THEN 'Level 2'
                ELSE ''
            END AS level_label,
            CASE
                WHEN b.bp_lvl1_sind_id = ? THEN COALESCE(b.bp_lvl1_amount,0)
                WHEN b.bp_lvl2_sind_id = ? THEN COALESCE(b.bp_lvl2_amount,0)
                ELSE 0
            END AS commission_amount
        FROM bookings b
        JOIN customers   c ON b.cust_id = c.cust_id
        JOIN sinderellas s ON s.sind_id = b.sind_id
        WHERE (b.bp_lvl1_sind_id = ? OR b.bp_lvl2_sind_id = ?)
        AND b.booking_date >= ? AND b.booking_date <= ?
        AND (b.booking_status = 'rated' OR b.booking_status = 'done')
        ORDER BY b.booking_date ASC, b.booking_from_time ASC, b.booking_id ASC
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['error' => 'Prepare failed: ' . $conn->error, 'rows' => [], 'total_amount' => 0, 'range_text' => $rangeText, 'startStr' => $startStr, 'endStr' => $endStr]);
        exit;
    }

    if (
        !$stmt->bind_param(
            "iiiiiiss",
            $target_sind_id,
            $target_sind_id,   
            $target_sind_id,
            $target_sind_id,  
            $target_sind_id,
            $target_sind_id,  
            $startStr,
            $endStr  
        )
    ) {
        echo json_encode(['error' => 'bind_param failed: ' . $stmt->error, 'rows' => [], 'total_amount' => 0, 'range_text' => $rangeText, 'startStr' => $startStr, 'endStr' => $endStr]);
        exit;
    }

    if (!$stmt->execute()) {
        echo json_encode(['error' => 'Execute failed: ' . $stmt->error, 'rows' => [], 'total_amount' => 0, 'range_text' => $rangeText, 'startStr' => $startStr, 'endStr' => $endStr]);
        exit;
    }

    $res = $stmt->get_result();

    $rows = [];
    $total = 0.0;

    while ($r = $res->fetch_assoc()) {
        $dateObj = DateTime::createFromFormat('Y-m-d', $r['booking_date']);
        $dateFmt = $dateObj ? $dateObj->format('Y-m-d (l)') : $r['booking_date'];

        $rows[] = [
            'booking_id' => (int) $r['booking_id'],
            'booking_date' => $dateFmt,
            'downline' => $r['downline_name'],
            'level' => $r['level_label'],
            'service_type' => ($r['booking_type'] === 'r') ? 'Recurring' : 'Ad-hoc',
            'amount' => (float) $r['commission_amount'],
        ];
        $total += (float) $r['commission_amount'];
    }
    $stmt->close();

    // helper for money formatting
    $fmt2 = function($v){ return number_format((float)$v, 2, '.', ''); };

    // build the commission table HTML
    $commissionTableHtml = "
    <table cellspacing='0' cellpadding='6' style='width:70%; border:1px solid black; margin-left: auto; margin-right: auto;'>
    <thead>
        <tr>
            <th style='text-align:left; border:1px solid black;'>Downline Name (Level)</th>
            <th style='text-align:left; border:1px solid black;'>Service Type</th>
            <th style='text-align:right; border:1px solid black;'>Commission (RM)</th>
        </tr>
    </thead>
    <tbody>
    ";

    if (!empty($rows)) {
        foreach ($rows as $r) {
            $commissionTableHtml .= "
            <tr>
            <td style='border:1px solid black; border-bottom:none; border-top:none;'>".htmlspecialchars($r['downline'])." (".htmlspecialchars($r['level']).")</td>
            <td style='border:1px solid black; border-bottom:none; border-top:none;'>".htmlspecialchars($r['service_type'])."</td>
            <td class='right' style='text-align:right; border:1px solid black; border-bottom:none; border-top:none;'>RM ".$fmt2($r['amount'])."</td>
            </tr>";
        }
    } else {
        $commissionTableHtml .= "
            <tr><td colspan='3'>No commissions in this period.</td></tr>";
    }

    $commissionTableHtml .= "
    </tbody>
    <tfoot>
        <tr class='total'>
        <td colspan='2' style='text-align:right; font-weight:bold; border:1px solid black;'>Total Commission:</td>
        <td class='right' style='text-align:right; font-weight:bold; border:1px solid black;'>RM ".$fmt2($total)."</td>
        </tr>
    </tfoot>
    </table>
    ";

    // If you're composing a larger HTML string for mPDF:
    $html .= $commissionTableHtml;

        $html .= "
    </div>
</body>
</html>
";

// --------- Output ---------
$mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
$mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);

$fname = 'Commission_' . $target_sind_id . '_' . $startStr . '_' . $endStr . '.pdf';
$mpdf->SetTitle("Commission {$sind_name} {$startStr}–{$endStr}");
$mpdf->Output($fname, \Mpdf\Output\Destination::INLINE); // open in browser
