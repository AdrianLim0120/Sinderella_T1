<?php
session_start();
if (!isset($_SESSION['sind_id'])) {
    header("Location: ../login_sind.php");
    exit();
}
require_once '../db_connect.php';
$activeSindId = (int) $_SESSION['sind_id'];
?>
<!DOCTYPE html>
<html>

<head>
    <title>Group Commission</title>
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
            margin-bottom: 16px
        }

        .range-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1 1 auto;
            justify-content: center
        }

        .btn-nav {
            padding: 8px 10px;
            border-radius: 10px;
            border: 1px solid var(--line);
            background: #eef2f7;
            color: #111;
            cursor: pointer
        }

        .btn-nav:disabled {
            opacity: .5;
            cursor: not-allowed
        }

        #rangeText {
            font-size: 22px;
            font-weight: 700;
            text-align: center;
            margin-top: 18px
        }

        .table-wrap {
            overflow-x: auto
        }

        table.simple {
            width: 100%;
            border-collapse: collapse;
            font-size: 16px
        }

        table.simple thead th {
            background: #f7f7f9;
            text-align: left;
            font-weight: 700;
            border-bottom: 1px solid var(--line);
            padding: 12px 10px;
            white-space: nowrap
        }

        table.simple td {
            border-bottom: 1px solid var(--line);
            padding: 12px 10px;
            vertical-align: middle
        }

        tr.clickable {
            cursor: pointer
        }

        tr.clickable:hover {
            background: #f8fafc
        }

        .total-row td {
            font-weight: bold;
            background: #fafafa
        }

        .right {
            text-align: right
        }

        .col-idx {
            width: 70px
        }

        .col-date {
            width: 220px
        }

        .col-time {
            width: 200px
        }

        .col-downline {
            width: 260px
        }

        .col-type {
            width: 140px
        }

        .col-amount {
            width: 140px
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
            cursor: pointer
        }

        .btn-primary:hover {
            background: var(--primary-dark)
        }

        .btn-primary:disabled {
            opacity: .6;
            cursor: not-allowed
        }
    </style>
</head>

<body>
    <div class="main-container">
        <?php include '../includes/menu/menu_sind.php'; ?>
        <div class="content-container">
            <?php include '../includes/header_sind.php'; ?>
            <div class="details-container">
                <h2>Group Commission</h2>

                <div class="toolbar">
                    <div class="range-wrap">
                        <button id="btnPrev" class="btn-nav"
                            title="Older (previous month)"><strong>&lt;</strong></button>
                        <div id="rangeText">Loading…</div>
                        <button id="btnNext" class="btn-nav" title="Newer (next month)"><strong>&gt;</strong></button>
                    </div>
                    <div class="actions">
                        <button id="btnPayslip" class="btn-primary">Generate Commission Pay Slip</button>
                    </div>
                </div>

                <div class="table-wrap">
                    <table class="simple" id="gcTable">
                        <thead>
                            <tr>
                                <th class="col-idx">#</th>
                                <th class="col-date">Booking Date</th>
                                <th class="col-time">From → To</th>
                                <th class="col-downline">Downline</th>
                                <th class="col-type">Service Type</th>
                                <th>Customer</th>
                                <th class="col-amount">Amount (RM)</th>
                            </tr>
                        </thead>
                        <tbody id="gcBody">
                            <tr>
                                <td colspan="7">Loading…</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td colspan="6" class="right">Total:</td>
                                <td id="totalCell">RM 0.00</td>
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
            var monthOffset = 0, maxOffset = 0;

            var sindId = <?php echo (int) $activeSindId; ?>;

            var $btnPrev = $('#btnPrev'), $btnNext = $('#btnNext');
            var $rangeText = $('#rangeText'), $gcBody = $('#gcBody'), $totalCell = $('#totalCell');
            var $btnPayslip = $('#btnPayslip'); // <-- NEW

            function fmtRM(v) { return 'RM ' + Number(v).toFixed(2); }
            function refreshButtons() { $btnNext.prop('disabled', monthOffset >= maxOffset); }

            // Bind the PDF button to open the PDF for the current month offset + sind id
            function updateCommissionSlipButton(rowsCount) {
                var enabled = rowsCount > 0; // enable only if there are rows
                $btnPayslip.prop('disabled', !enabled);

                var payslipURL = '../pdf_report/pdf_group_commission.php'
                    + '?month_offset=' + encodeURIComponent(monthOffset);

                $btnPayslip.off('click').on('click', function (e) {
                    e.preventDefault();
                    window.open(payslipURL, '_blank', 'noopener'); // open in new tab
                });
            }

            function loadMonth() {
                refreshButtons();
                $gcBody.html('<tr><td colspan="7">Loading…</td></tr>');
                $totalCell.text('RM 0.00');
                updateCommissionSlipButton(0); // disable while loading

                $.ajax({
                    url: 'ajax_group_commission.php',
                    method: 'POST',
                    dataType: 'json',
                    data: { month_offset: monthOffset, sind_id: sindId }
                })
                    .done(function (data) {
                        $rangeText.text(data.range_text || '—');

                        if (data.rows && data.rows.length) {
                            var html = '';
                            for (var i = 0; i < data.rows.length; i++) {
                                var r = data.rows[i], idx = i + 1;
                                html += '<tr>' +
                                    '<td>' + idx + '</td>' +
                                    '<td>' + (r.booking_date || '') + '</td>' +
                                    '<td>' + (r.from_time || '') + ' → ' + (r.to_time || '') + '</td>' +
                                    '<td>' + (r.downline || '') + (r.level ? ' (' + r.level + ')' : '') + '</td>' +
                                    '<td>' + (r.service_type || '') + '</td>' +
                                    '<td>' + (r.customer_name || '') + '</td>' +
                                    '<td>' + fmtRM(r.amount || 0) + '</td>' +
                                    '</tr>';
                            }
                            $gcBody.html(html);
                        } else {
                            $gcBody.html('<tr><td colspan="7">No bookings in this month.</td></tr>');
                        }

                        $totalCell.text(fmtRM(data.total_amount || 0));

                        if (typeof data.max_offset !== 'undefined') {
                            maxOffset = Number(data.max_offset) || 0;
                            refreshButtons();
                        }

                        // enable/refresh the PDF button for the current month + sind
                        updateCommissionSlipButton(data.rows ? data.rows.length : 0);
                    })
                    .fail(function (xhr) {
                        console.error(xhr.responseText || xhr.statusText);
                        $rangeText.text('Error loading');
                        $gcBody.html('<tr><td colspan="7">Failed to load. Please retry.</td></tr>');
                        updateCommissionSlipButton(0);
                    });
            }

            $btnPrev.on('click', function () { monthOffset--; loadMonth(); });
            $btnNext.on('click', function () { if (monthOffset < maxOffset) { monthOffset++; loadMonth(); } });

            loadMonth();
        })(jQuery);
    </script>

</body>

</html>