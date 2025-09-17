<?php
// admin/individual_income_overview.php
session_start();
if (!isset($_SESSION['adm_id'])) {
    header("Location: ../login_adm.php");
    exit();
}
require_once '../db_connect.php';
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Individual Income Overview</title>
    <link rel="icon" href="../img/sinderella_favicon.png" />
    <link rel="stylesheet" href="../includes/css/styles_user.css">
    <!-- DataTables (search + sort) -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
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
            justify-content: space-between; 
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

        .dataTables_filter {
            margin-bottom: 20px;
        }

        #sindTable tbody tr {
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        #sindTable tbody tr:hover {
            background-color: #f1f5f9;
        }

        .right {
            text-align: right
        }

        .grand-total-wrap {
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
                <h2 style="margin:0 0 10px 0;">Individual Income Overview</h2>
                <div class="toolbar">
                    <div class="range-wrap">
                        <button id="btnPrev" class="btn-nav"
                            title="Older (previous week)"><strong>&lt;</strong></button>
                        <div id="rangeText">Loading…</div>
                        <button id="btnNext" class="btn-nav" title="Newer (next week)"><strong>&gt;</strong></button>
                    </div>
                    
                    <div class="export-btn">
                        <button id="btnExport" class="btn-export">Generate Excel</button>
                    </div>
                </div>

                <table id="sindTable" class="simple">
                    <thead>
                        <tr>
                            <th style="width:80px; text-align:right;">#</th>
                            <th>Sinderella</th>
                            <th>Phone No.</th>
                            <th style="width:140px">Bookings</th>
                            <th style="width:160px">Total Amount (RM)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="4">Loading…</td>
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
            var weekOffset = 0, maxOffset = 0;

            var $rangeText = $('#rangeText');
            var $btnPrev = $('#btnPrev'), $btnNext = $('#btnNext');
            var dt;

            function fmtRM(v) { return 'RM ' + Number(v).toFixed(2); }
            function refreshButtons() { $btnNext.prop('disabled', weekOffset >= maxOffset); }

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
                dt = $('#sindTable').DataTable({
                    data: rows,
                    columns: [
                        { data: null, render: (d, t, r, meta) => meta.row + 1, className: 'right' },
                        { data: 'sind_name' },
                        { data: 'phone_no' },
                        { data: 'bookings' },
                        { data: 'total', render: d => fmtRM(d) }
                    ],
                    order: [[3, 'desc']], // sort by Total desc
                    pageLength: 25,
                    searching: true
                });

                $('#sindTable tbody').on('click', 'tr', function () {
                    var data = dt.row(this).data();
                    if (!data) return;
                    window.location.href = 'sind_individual_income.php?sind_id='
                        + encodeURIComponent(data.sind_id)
                        + '&sind_name=' + encodeURIComponent(data.sind_name)
                        + '&week_offset=' + encodeURIComponent(weekOffset);
                });

                dt.on('draw', function(){
                    var sum = 0;
                    dt.column(4, {filter:'applied'}).data().each(function(v){
                        sum += parseFloat(v) || 0; // if the column has raw numbers
                        
                    });
                    $('#grandTotalText').text(fmtRM(sum));
                });
            }

            function loadWeek() {
                refreshButtons();
                if (dt) dt.clear().draw();
                $('#sindTable tbody').html('<tr><td colspan="4">Loading…</td></tr>');

                $.ajax({
                    url: 'ajax_individual_income_overview.php',
                    type: 'POST',
                    dataType: 'json',
                    data: { week_offset: weekOffset }
                })
                    .done(function (resp) {
                        $rangeText.text(resp.range_text || '—');
                        initTable(resp.rows || []);
                        setGrandTotalFromRows(resp.rows || []); 
                        if (!resp.rows || !resp.rows.length) {
                            $('#sindTable tbody').html('<tr><td colspan="4">No bookings in this period.</td></tr>');
                        }
                    })
                    .fail(function (xhr) {
                        console.error(xhr.responseText || xhr.statusText);
                        $rangeText.text('Error loading');
                        $('#sindTable tbody').html('<tr><td colspan="4">Failed to load. Please retry.</td></tr>');
                    });
            }

            $btnPrev.on('click', function () { weekOffset--; loadWeek(); });
            $btnNext.on('click', function () { if (weekOffset < maxOffset) { weekOffset++; loadWeek(); } });

            $('#btnExport').on('click', function () {
                var url = window.location = '../pdf_report/export_individual_income.php?week_offset=' + encodeURIComponent(weekOffset);
                window.open(url);
            });

            loadWeek();
        })(jQuery);
    </script>
</body>

</html>