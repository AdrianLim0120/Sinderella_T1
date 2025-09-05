<?php
session_start();
require_once '../db_connect.php';

$_SESSION['sind_id'] = $_GET['sind_id'] ?? '';
$sind_id = $_GET['sind_id'] ?? ($_SESSION['sind_id'] ?? '');
$sindName = $_GET['sind_name'] ?? ($_SESSION['sind_name'] ?? '');
if (!isset($_SESSION['adm_id'])) {
    header("Location: ../login_adm.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Weekly Income</title>
    <link rel="icon" href="../img/sinderella_favicon.png" />
    <link rel="stylesheet" href="../includes/css/styles_user.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css" />

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

        .back-wrap button {
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .back-wrap button:hover {
            background-color: #0056b3;
        }

        .back-wrap {
            margin-top: 16px;
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

        .right {
            text-align: right;
        }

        .dataTables_filter {
            margin-bottom: 20px;
        }

        #detailTable tbody tr {
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        #detailTable tbody tr:hover {
            background-color: #f1f5f9;
        }
    </style>
</head>

<body>
    <div class="main-container">
        <?php include '../includes/menu/menu_adm.php'; ?>
        <div class="content-container">
            <?php include '../includes/header_adm.php'; ?>
            <div class="details-container">
                <h2 style="margin:0 0 10px 0;">Weekly Income (<?= htmlspecialchars($sindName) ?>)</h2>

                <div class="toolbar">
                    <div class="range-wrap">
                        <button id="btnPrev" class="btn-nav"
                            title="Older (previous week)"><strong>&lt;</strong></button>
                        <div id="rangeText">Loading…</div>
                        <button id="btnNext" class="btn-nav" title="Newer (next week)"><strong>&gt;</strong></button>
                    </div>
                </div>

                <table id="detailTable" class="simple">
                    <thead>
                        <tr>
                            <th style="text-align:right;">#</th>
                            <th style="width:250px;">Booking Date</th>
                            <th style="width:250px;">From → To</th>
                            <th style="width:200px;">Service Type</th>
                            <th style="width:300px;">Customer</th>
                            <th style="width:160px;" class="right">Amount (RM)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="8">Loading…</td>
                        </tr>
                    </tbody>
                </table>

                <div class="back-wrap">
                    <button onclick="window.location.href='personal_income.php'" class="btn-nav">Back</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script>
        (function ($) {
            var weekOffset = Number(new URLSearchParams(window.location.search).get('week_offset')) || 0;
            var sindId = <?php echo json_encode($sind_id); ?>;
            var maxOffset = 0; // if you later want to cap "next", set this from server

            var $rangeText = $('#rangeText');
            var $btnPrev = $('#btnPrev'), $btnNext = $('#btnNext');
            var dt;

            function fmtRM(v) { return 'RM ' + Number(v || 0).toFixed(2); }
            function refreshButtons() { $btnNext.prop('disabled', weekOffset >= maxOffset); }

            function initTable(rows) {
                if (dt) {
                    dt.clear().rows.add(rows).draw();
                    return;
                }

                dt = $('#detailTable').DataTable({
                    data: rows,
                    columns: [
                        { data: null, render: (d, t, r, meta) => meta.row + 1, className: 'right' },
                        { data: 'booking_date' },
                        { data: 'from_time', render: (d, t, r) => r.from_time + ' → ' + r.to_time },
                        { data: 'service_type' },
                        { data: 'customer_name' },
                        { data: 'bp_sind', render: d => fmtRM(d) },
                    ],
                    order: [[1, 'desc']],   // newest date first
                    pageLength: 25,
                    searching: true
                });

                $('#detailTable tbody').on('click', 'tr', function () {
                    var data = dt.row(this).data();
                    if (!data) return;
                    if (data.url) {
                        window.location.href = data.url;
                    } else if (data.booking_id) {
                        window.location.href = 'view_booking_details.php?booking_id=' + encodeURIComponent(data.booking_id);
                    }
                });
            }

            function loadWeek() {
                refreshButtons();
                if (dt) dt.clear().draw();
                $('#detailTable tbody').html('<tr><td colspan="8">Loading…</td></tr>');

                $.ajax({
                    url: '../rs/ajax_individual_income.php',   // Sinderella-side endpoint
                    type: 'POST',
                    dataType: 'json',
                    data: { sind_id: sindId, week_offset: weekOffset }
                })
                    .done(function (resp) {
                        // Expecting: { range_text: "...", rows: [...] }
                        $rangeText.text(resp.range_text || '—');
                        initTable(resp.rows || []);

                        if (!resp.rows || !resp.rows.length) {
                            $('#detailTable tbody').html('<tr><td colspan="8">No bookings in this period.</td></tr>');
                        }

                        if (typeof resp.max_offset !== 'undefined') {
                            maxOffset = Number(resp.max_offset) || 0;
                            refreshButtons();
                        }
                    })
                    .fail(function (xhr) {
                        console.error(xhr.responseText || xhr.statusText);
                        $rangeText.text('Error loading');
                        $('#detailTable tbody').html('<tr><td colspan="8">Failed to load. Please retry.</td></tr>');
                    });
            }

            $btnPrev.on('click', function () { weekOffset--; loadWeek(); });
            $btnNext.on('click', function () { if (weekOffset < maxOffset) { weekOffset++; loadWeek(); } });

            loadWeek();
        })(jQuery);
    </script>
</body>

</html>