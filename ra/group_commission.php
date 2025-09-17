<?php
session_start();
if (!isset($_SESSION['adm_id'])) {
    header("Location: ../login_adm.php");
    exit();
}
require_once '../db_connect.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Group Commission (Monthly)</title>
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

        #gcTable tbody tr {
            cursor: pointer;
            transition: background-color .2s ease;
        }

        #gcTable tbody tr:hover {
            background-color: #f1f5f9;
        }

        .grand-total-wrap{
            margin-top: 12px;
            text-align: right;
            font-size: 16px;
        }

                .export-btn {
            display: flex;
            margin-left: auto; 
        }

        .btn-export {
            background-color: #76e790ff;
            color: white;
            padding: 8px 16px;
            border-radius: 10px;
            border: 1px solid #28a745;
            cursor: pointer;
            font-weight: bold;
        }

        .btn-export:hover {
            background-color: #2bb148ff; 
            border-color: #1e7e34;
        }
    </style>
</head>

<body>
    <div class="main-container">
        <?php include '../includes/menu/menu_adm.php'; ?>
        <div class="content-container">
            <?php include '../includes/header_adm.php'; ?>

            <div class="details-container">
                <h2 style="margin:0 0 10px 0;">Group Commission (Monthly)</h2>

                <div class="toolbar">
                    <div class="range-wrap">
                        <button id="btnPrev" class="btn-nav"
                            title="Older (previous month)"><strong>&lt;</strong></button>
                        <div id="rangeText">Loading…</div>
                        <button id="btnNext" class="btn-nav" title="Newer (next month)"><strong>&gt;</strong></button>
                    </div>

                    <div class="export-btn">
                        <button id="btnExport" class="btn-export">Generate Excel</button>
                    </div>
                </div>

                <table id="gcTable" class="simple">
                    <thead>
                        <tr>
                            <th style="width:80px;text-align:right;">#</th>
                            <th>Sinderella</th>
                            <th style="width:200px;" class="right">Total Commission (RM)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="3">Loading…</td>
                        </tr>
                    </tbody>
                </table>
                <div class="grand-total-wrap">
                    <strong>Grand Total:&nbsp;<span id="grandTotalText">RM 0.00</span></strong>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script>
        (function ($) {
            // monthOffset: 0 = current month; -1 = previous month; -2 = two months ago, etc.
            var monthOffset = Number(new URLSearchParams(window.location.search).get('month_offset')) || 0;
            var maxOffset = 0; // don't allow navigating to the future (same pattern as your other pages)

            var $rangeText = $('#rangeText');
            var $btnPrev = $('#btnPrev'), $btnNext = $('#btnNext');
            var dt;

            function fmtRM(v) { return 'RM ' + Number(v || 0).toFixed(2); }
            function refreshButtons() { $btnNext.prop('disabled', monthOffset >= maxOffset); }

            function setGrandTotalFromRows(rows){
                var sum = 0;
                (rows || []).forEach(function(r){
                    sum += parseFloat(r.total || 0);
                });
                $('#grandTotalText').text(fmtRM(sum));
            }

            function initTable(rows) {
                if (dt) {
                    dt.clear().rows.add(rows).draw();
                    return;
                }
                dt = $('#gcTable').DataTable({
                    data: rows,
                    columns: [
                        { data: null, render: (d, t, r, meta) => meta.row + 1, className: 'right' },
                        { data: 'sind_name' },
                        { data: 'total', render: d => fmtRM(d)}
                    ],
                    order: [[2, 'desc']],  // Highest commission first
                    pageLength: 25,
                    searching: true
                });

                $('#gcTable tbody').on('click', 'tr', function () {
                    var data = dt.row(this).data();
                    if (!data) return;
                    window.location.href = '../ra/sind_group_commission.php?sind_id='
                        + encodeURIComponent(data.sind_id)
                        + '&sind_name=' + encodeURIComponent(data.sind_name)
                        + '&month_offset=' + encodeURIComponent(monthOffset);
                });

                dt.on('draw', function(){
                    var sum = 0;
                    dt.column(2, {filter:'applied'}).data().each(function(v){
                        sum += parseFloat(v) || 0; // if the column has raw numbers
                        
                    });
                    $('#grandTotalText').text(fmtRM(sum));
                });
            }

            function loadMonth() {
                refreshButtons();
                if (dt) dt.clear().draw();
                $('#gcTable tbody').html('<tr><td colspan="3">Loading…</td></tr>');

                $.ajax({
                    url: 'ajax_group_commission_overview.php',
                    type: 'POST',
                    dataType: 'json',
                    data: { month_offset: monthOffset }
                })
                    .done(function (resp) {
                        $rangeText.text(resp.range_text || '—');
                        initTable(resp.rows || []);
                        setGrandTotalFromRows(resp.rows || []); 
                        if (!resp.rows || !resp.rows.length) {
                            $('#gcTable tbody').html('<tr><td colspan="3">No commissions in this month.</td></tr>');
                        }

                        if (typeof resp.max_offset !== 'undefined') {
                            maxOffset = Number(resp.max_offset) || 0;
                            refreshButtons();
                        }
                    })
                    .fail(function (xhr) {
                        console.error(xhr.responseText || xhr.statusText);
                        $rangeText.text('Error loading');
                        $('#gcTable tbody').html('<tr><td colspan="3">Failed to load. Please retry.</td></tr>');
                    });
            }

            $btnPrev.on('click', function () { monthOffset--; loadMonth(); });
            $btnNext.on('click', function () { if (monthOffset < maxOffset) { monthOffset++; loadMonth(); } });

            $('#btnExport').on('click', function () {
                var url = window.location = '../pdf_report/export_group_commission.php?month_offset=' + encodeURIComponent(monthOffset);
                window.open(url);
            });

            loadMonth();
        })(jQuery);
    </script>
</body>

</html>