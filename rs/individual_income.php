<?php
session_start();
if (!isset($_SESSION['sind_id'])) {
    header("Location: ../login_sind.php");
    exit();
}

require_once '../db_connect.php';

$VIEW_DETAILS_PATH = 'view_booking_details.php';
$activeSindId = (int) $_SESSION['sind_id'];
?>
<!DOCTYPE html>
<html>

<head>
    <title>Individual Income</title>
    <meta charset="utf-8">
    <link rel="icon" href="../img/sinderella_favicon.png" />
    <link rel="stylesheet" href="../includes/css/styles_user.css">
    <style>
        .details-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .details-container h2 {
            margin-top: 0;
        }

        .details-container label {
            display: block;
            margin-top: 10px;
        }

        .details-container button {
            margin-top: 20px;
            padding: 10px 20px;
            color: black;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .details-container button:hover {
            background-color: #0056b3;
        }

        .details-container td {
            padding: 8px;
        }

        .toolbar {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
            justify-content: center;
        }

        .range-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1 1 auto;
            justify-content: center;
        }

        .btn-nav {
            padding: 8px 10px;
            border-radius: 10px;
            border: 1px solid var(--line);
            background: #eef2f7;
            color: #111;
            cursor: pointer;
        }

        .btn-nav:disabled {
            opacity: .5;
            cursor: not-allowed;
        }

        #rangeText {
            font-size: 22px;
            font-weight: 700;
            text-align: center;
            margin-top: 18px;
        }

        .actions {
            display: flex;
            gap: 10px;
        }

        .actions button {
            color: #fff;
        }

        .btn-primary {
            padding: 10px 14px;
            border-radius: 10px;
            border: none;
            background: var(--primary);
            background-color: #007bff;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-primary:disabled {
            opacity: .6;
            cursor: not-allowed;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table.simple {
            width: 100%;
            border-collapse: collapse;
            font-size: 16px;
        }

        table.simple thead th {
            background: #f7f7f9;
            text-align: left;
            font-weight: 700;
            border-bottom: 1px solid var(--line);
            padding: 12px 10px;
            white-space: nowrap;
        }

        table.simple td {
            border-bottom: 1px solid var(--line);
            padding: 12px 10px;
            vertical-align: middle;
        }

        tr.clickable {
            cursor: pointer;
        }

        tr.clickable:hover {
            background: #f8fafc;
        }

        .total-row td {
            font-weight: bold;
            background: #fafafa;
        }

        .right {
            text-align: right;
        }
    </style>
</head>

<body>
    <div class="main-container">
        <?php include '../includes/menu/menu_sind.php'; ?>
        <div class="content-container">
            <?php include '../includes/header_sind.php'; ?>
            <div class="details-container">
                <h2>Individual Income</h2>

                <div class="toolbar">
                    <div class="range-wrap">
                        <button id="btnPrev" class="btn-nav"
                            title="Older (previous week)"><strong>&lt;</strong></button>
                        <div id="rangeText">Loading…</div>
                        <button id="btnNext" class="btn-nav" title="Newer (next week)"><strong>&gt;</strong></button>
                    </div>

                    <div class="actions">
                        <button id="btnPayslip" class="btn-primary">
                            Generate Income Pay Slip
                        </button>
                    </div>
                </div>

                <div class="table-wrap">
                    <table class="simple" id="incomeTable">
                        <thead>
                            <tr>
                                <th style="width:70px">#</th>
                                <th style="width:250px">Booking Date</th>
                                <th style="width:160px">From → To</th>
                                <th style="width:140px">Service Type</th>
                                <th style="width:160px">Customer</th>
                                <th style="width:120px">Amount (RM)</th>
                            </tr>
                        </thead>
                        <tbody id="incomeBody">
                            <tr>
                                <td colspan="6">Loading…</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td colspan="5" class="right">Total:</td>
                                <td class="left" id="totalCell">RM 0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        (function ($) {
            var weekOffset = 0, maxOffset = 0;

            var sindId = <?php echo (int) $activeSindId; ?>;
            var detailsPath = <?php echo json_encode($VIEW_DETAILS_PATH); ?>;

            var $btnPrev = $('#btnPrev');
            var $btnNext = $('#btnNext');
            var $rangeText = $('#rangeText');
            var $incomeBody = $('#incomeBody');
            var $totalCell = $('#totalCell');
            var $btnPayslip = $('#btnPayslip');  

            var currentStart = null, currentEnd = null;

            function fmtRM(v) { return 'RM ' + Number(v || 0).toFixed(2); }
            function refreshButtons() { $btnNext.prop('disabled', weekOffset >= maxOffset); }

            function computeWeekRangeStrings(offset) {
                var today = new Date(); today.setHours(0, 0, 0, 0);
                var day = today.getDay();            
                var diffToMon = (day === 0 ? -6 : 1 - day);
                var thisMon = new Date(today); thisMon.setDate(today.getDate() + diffToMon);

                var baseMon = new Date(thisMon); baseMon.setDate(thisMon.getDate() - 7);

                var start = new Date(baseMon); start.setDate(baseMon.getDate() + (offset * 7));
                var end = new Date(start); end.setDate(start.getDate() + 6);

                var pad = n => (n < 10 ? '0' + n : '' + n);
                var toYmd = d => d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
                return { start: toYmd(start), end: toYmd(end) };
            }

            function updatePayslipButton(rowsCount) {
                var enabled = !!(currentStart && currentEnd && rowsCount > 0);
                $btnPayslip.prop('disabled', !enabled);
                if (enabled) {
                    var payslipURL = '../pdf_report/pdf_individual_income.php'
                        + '?start=' + encodeURIComponent(currentStart)
                        + '&end=' + encodeURIComponent(currentEnd);
                    $btnPayslip.off('click').on('click', function () {
                        window.open(payslipURL, '_blank', 'noopener');
                    });
                } else {
                    $btnPayslip.off('click');
                }
            }

            function loadWeek() {
                refreshButtons();
                $incomeBody.html('<tr><td colspan="6">Loading…</td></tr>');
                $totalCell.text('RM 0.00');
                updatePayslipButton(0); // disable while loading

                $.ajax({
                    url: 'ajax_individual_income.php',
                    method: 'POST',
                    dataType: 'json',
                    data: { week_offset: weekOffset, sind_id: sindId }
                })
                    .done(function (data) {
                        // 1) Show the range text (supports either range_text or month_text)
                        $rangeText.text(data.range_text || data.month_text || '—');

                        // 2) Capture start/end for PDF button: prefer API values; fallback to computed
                        if (data.start_date && data.end_date) {
                            currentStart = data.start_date;  // expected format: YYYY-MM-DD
                            currentEnd = data.end_date;
                        } else {
                            var r = computeWeekRangeStrings(weekOffset);
                            currentStart = r.start;
                            currentEnd = r.end;
                        }

                        // 3) Render rows
                        if (data.rows && data.rows.length) {
                            var html = '';
                            for (var i = 0; i < data.rows.length; i++) {
                                var r = data.rows[i], idx = i + 1, bid = r.booking_id;
                                html += '<tr class="clickable" onclick="window.location.href=\'' + detailsPath + '?booking_id=' + bid + '\'">' +
                                    '<td>' + idx + '</td>' +
                                    '<td>' + (r.booking_date || '') + '</td>' +
                                    '<td>' + (r.from_time || '') + ' → ' + (r.to_time || '') + '</td>' +
                                    '<td>' + (r.service_type || '') + '</td>' +
                                    '<td>' + (r.customer_name || '') + '</td>' +
                                    '<td>' + fmtRM(r.bp_sind) + '</td>' +
                                    '</tr>';
                            }
                            $incomeBody.html(html);
                        } else {
                            $incomeBody.html('<tr><td colspan="6">No bookings in this period.</td></tr>');
                        }

                        // 4) Total + enable PDF button
                        $totalCell.text(fmtRM(data.total_bp_sind || 0));
                        updatePayslipButton(data.rows ? data.rows.length : 0);

                        // 5) Respect server-provided navigation limit if present
                        if (typeof data.max_offset !== 'undefined') {
                            maxOffset = Number(data.max_offset) || 0;
                            refreshButtons();
                        }
                    })
                    .fail(function (xhr) {
                        console.error(xhr.responseText || xhr.statusText);
                        $rangeText.text('Error loading');
                        $incomeBody.html('<tr><td colspan="6">Failed to load. Please retry.</td></tr>');
                        updatePayslipButton(0);
                    });
            }

            $btnPrev.on('click', function () { weekOffset--; loadWeek(); });
            $btnNext.on('click', function () { if (weekOffset < maxOffset) { weekOffset++; loadWeek(); } });

            // initial
            loadWeek();

        })(jQuery);
    </script>

</body>

</html>