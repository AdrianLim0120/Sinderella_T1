<?php
// pdf_report/export_group_commission.php
session_start();
if (!isset($_SESSION['adm_id'])) {
    header('Location: ../login_adm.php');
    exit();
}

require_once '../db_connect.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

// ---------- 1) Resolve month range (like ajax_group_commission_overview.php) ----------
$monthOffset = isset($_GET['month_offset']) ? (int)$_GET['month_offset'] : 0;

$tz       = new DateTimeZone('Asia/Kuala_Lumpur');
$first    = new DateTime('first day of this month 00:00:00', $tz);
if ($monthOffset !== 0) {
    $first->modify(($monthOffset > 0 ? '+' : '') . $monthOffset . ' month');
}
$last     = (clone $first)->modify('last day of this month 23:59:59');

$startSql = $first->format('Y-m-d');
$endSql   = $last->format('Y-m-d');
$paymentDateLabel = (new DateTime('today', $tz))->format('d/m/Y'); // e.g. 29/08/2025

// ---------- 2) Query data (lvl1 + lvl2 commissions) ----------
$sql = "
    SELECT
        s.sind_id,
        s.sind_name,
        s.sind_phno,
        s.sind_bank_acc_no,
        s.sind_bank_name,
        s.sind_id_type,
        s.sind_icno,
        s.sind_no,
        ROUND(SUM(x.amount), 2) AS total_amount
    FROM (
        SELECT b.bp_lvl1_sind_id AS sind_id,
               SUM(COALESCE(b.bp_lvl1_amount,0)) AS amount
        FROM bookings b
        WHERE b.booking_date >= ? AND b.booking_date <= ?
          AND b.bp_lvl1_sind_id IS NOT NULL
          AND b.booking_status IN ('done','rated')
        GROUP BY b.bp_lvl1_sind_id

        UNION ALL

        SELECT b.bp_lvl2_sind_id AS sind_id,
               SUM(COALESCE(b.bp_lvl2_amount,0)) AS amount
        FROM bookings b
        WHERE b.booking_date >= ? AND b.booking_date <= ?
          AND b.bp_lvl2_sind_id IS NOT NULL
          AND b.booking_status IN ('done','rated')
        GROUP BY b.bp_lvl2_sind_id
    ) x
    INNER JOIN sinderellas s ON s.sind_id = x.sind_id
    GROUP BY s.sind_id, s.sind_name, s.sind_phno,
             s.sind_bank_acc_no, s.sind_bank_name, s.sind_id_type, s.sind_icno, s.sind_no
    ORDER BY total_amount DESC, s.sind_name ASC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ssss', $startSql, $endSql, $startSql, $endSql);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
while ($r = $res->fetch_assoc()) {
    $r['total_amount'] = (float)$r['total_amount'];
    $rows[] = $r;
}
$stmt->close();

// ---------- 3) Build spreadsheet (same style as your bank template) ----------
$ss = new Spreadsheet();
$sheet = $ss->getActiveSheet();
$sheet->setTitle('PB ECP');

$sheet->getDefaultRowDimension()->setRowHeight(18);

// (A) Top “payment date” header
$sheet->setCellValue('A1', 'PAYMENT DATE (DD/MM/YYYY):');
$sheet->setCellValue('B1', $paymentDateLabel);
$sheet->getStyle('A1:B1')->getFont()->setBold(true);

// (B) Column headers row 2
$headers = [
  'A2' => 'Payment Type/ Mode : PBB/IBG/REN',
  'B2' => 'Bene Account No.',
  'C2' => 'BIC',
  'D2' => 'Bene Full Name',
  'E2' => "ID Type:\nFor Intrabank & IBG\nNI, OI, BR, PL,\nML, PP\n\nFor Rentas\nNI, OI, BR, OT",
  'F2' => 'Bene Identification No / Passport',
  'G2' => 'Payment Amount (with 2 decimal points)',
  'H2' => 'Recipient Reference',
  'I2' => 'Other Payment Details',
  'J2' => 'Bene Email 1',
  'K2' => 'Bene Email 2',
  'L2' => 'Bene Mobile No. 1',
  'M2' => 'Bene Mobile No. 2',
  'N2' => 'Joint Bene Name',
  'O2' => 'Joint Beneficiary Identification No.',
  'P2' => "Joint ID Type:\n\nFor Intrabank & IBG\nNI, OI, BR, PL,\nML, PP\n\nFor Rentas\nNI, OI, BR, OT",
  'Q2' => 'E-mail Content Line 1',
  'R2' => 'E-mail Content Line 2',
  'S2' => 'E-mail Content Line 3',
  'T2' => 'E-mail Content Line 4',
  'U2' => 'E-mail Content Line 5'
];
foreach ($headers as $cell => $text) {
    $sheet->setCellValue($cell, $text);
}

// (C) Spec row 3 (yellow)
$specs = [
  'A3' => '(M) - Char: 3 - A',
  'B3' => '(M) - Char: 20 - N',
  'C3' => '(M) - Char: 11 - A',
  'D3' => '(M) - Char: 120 - A',
  'E3' => '(O) - Char: 2 - A',
  'F3' => '(O) - Char: 29 - AN',
  'G3' => '(M) - Char: 18 - N',
  'H3' => '(O) - Char: 20 - AN',
  'I3' => '(O) - Char: 20 - AN',
  'J3' => '(O) - Char: 70 - AN',
  'K3' => '(O) - Char: 70 - AN',
  'L3' => '(O) - Char: 15 - N',
  'M3' => '(O) - Char: 15 - N',
  'N3' => '(O) - Char: 120 - A',
  'O3' => '(O) - Char: 29 - AN',
  'P3' => '(O) - Char: 2 - A',
  'Q3' => '(O) - Char: 40 - AN',
  'R3' => '(O) - Char: 40 - AN',
  'S3' => '(O) - Char: 40 - AN',
  'T3' => '(O) - Char: 40 - AN',
  'U3' => '(O) - Char: 40 - AN'
];
foreach ($specs as $cell => $text) {
    $sheet->setCellValue($cell, $text);
}

// (D) Styling to match screenshots
$yellow = 'FFF000';
$purple = '7030A0';
$red    = 'C00000';

// widths A..U
$widths = [
  'A'=>40,'B'=>25,'C'=>16,'D'=>30,'E'=>28,'F'=>26,'G'=>24,'H'=>24,'I'=>24,
  'J'=>20,'K'=>20,'L'=>22,'M'=>22,'N'=>26,'O'=>24,'P'=>28,'Q'=>24,'R'=>24,'S'=>24,'T'=>24,'U'=>24
];
foreach ($widths as $col => $w) {
    $sheet->getColumnDimension($col)->setWidth($w);
}

// header row 2
$sheet->getStyle('A2:U2')->getFont()->setBold(true);
$sheet->getStyle('A2:U2')->getAlignment()
      ->setWrapText(true)
      ->setHorizontal(Alignment::HORIZONTAL_CENTER)
      ->setVertical(Alignment::VERTICAL_CENTER);
$sheet->getRowDimension(2)->setRowHeight(160);

// yellow spec row
$sheet->getStyle('A3:U3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($yellow);
$sheet->getStyle('A3:U3')->getAlignment()
      ->setHorizontal(Alignment::HORIZONTAL_CENTER)
      ->setVertical(Alignment::VERTICAL_CENTER);

// color accents
$sheet->getStyle('E2')->getFont()->getColor()->setARGB($purple);
$sheet->getStyle('P2')->getFont()->getColor()->setARGB($purple);
$sheet->getStyle('G2')->getFont()->getColor()->setARGB($red);
$sheet->getStyle('H2')->getFont()->getColor()->setARGB($red);

// ---------- 4) Fill rows ----------
$dataStartRow = 4;
$row = $dataStartRow;

foreach ($rows as $r) {
    // A blank
    $sheet->setCellValueExplicit("B{$row}", (string)$r['sind_bank_acc_no'], DataType::TYPE_STRING);
    $sheet->setCellValue("C{$row}", $r['sind_bank_name']);
    $sheet->setCellValue("D{$row}", $r['sind_name']);
    $sheet->setCellValue("E{$row}", strtoupper((string)$r['sind_id_type']));
    $sheet->setCellValueExplicit("F{$row}", (string)$r['sind_icno'], DataType::TYPE_STRING);
    $sheet->setCellValue("G{$row}", $r['total_amount']);
    $sheet->getStyle("G{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
    $sheet->setCellValue("H{$row}", 'Commission'); // recipient reference for group commission
    $sheet->setCellValueExplicit("I{$row}", (string)$r['sind_no'], DataType::TYPE_STRING);
    // J..U left blank
    $row++;
}
$lastDataRow = $row - 1;

// alignments
$sheet->getStyle("B{$dataStartRow}:B{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle("C{$dataStartRow}:C{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle("E{$dataStartRow}:F{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle("G{$dataStartRow}:I{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// TOTAL row
$sheet->setCellValue("A{$row}", 'TOTAL:');
$sheet->getStyle("A{$row}")->getFont()->setBold(true);
$sheet->setCellValue("G{$row}", "=SUM(G{$dataStartRow}:G{$lastDataRow})");
$sheet->getStyle("G{$row}")->getNumberFormat()->setFormatCode('#,##0.00');

// borders
$sheet->getStyle("A2:U{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle("A2:U{$row}")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_MEDIUM);
$sheet->getStyle("A{$row}:U{$row}")->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM);

// ---------- 5) Output ----------
$fname = sprintf('group_commission_%s.xlsx', $first->format('Ym'));

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="'.$fname.'"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($ss);
$writer->save('php://output');
exit;
