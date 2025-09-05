<?php
session_start();
if (!isset($_SESSION['adm_id'])) {
    header("Location: ./login_adm.php");
    exit();
}
$today = new DateTime('today', new DateTimeZone('Asia/Kuala_Lumpur'));
$startDefault = (clone $today)->modify('first day of this month')->format('Y-m-d');
$endDefault = (clone $today)->modify('last day of this month')->format('Y-m-d');
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard – Admin</title>
    <link rel="icon" href="../img/sinderella_favicon.png" />
    <link rel="stylesheet" href="../includes/css/styles_user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --page-bg: #f6f8fb;
            --card-bg: #ffffff;
            --card-br: #e9eef5;
            --heading: #0f172a;
            --muted: #64748b;
            --shadow: 0 8px 18px rgba(15, 23, 42, .08);
            --gap: 18px;
            --info: #0ea5e9;
            --ok: #16a34a;
            --lav: #7c3aed;
            --pending: #334155;
            --warn: #f59e0b;
            --bad: #ef4444;
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: var(--gap);
            margin-bottom: 26px;
        }

        @media (max-width:980px) {
            .kpi-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width:640px) {
            .kpi-grid {
                grid-template-columns: 1fr;
            }
        }

        .kpi-card {
            --accent: var(--info);
            --soft: rgba(14, 165, 233, .12);
            position: relative;
            background:
                radial-gradient(140px 140px at 92% 8%, var(--soft) 0%, transparent 60%), linear-gradient(180deg, #fff 0%, #fbfdff 100%);
            border: 1px solid var(--card-br);
            border-radius: 16px;
            padding: 18px 20px;
            box-shadow: 0 1px 0 rgba(15, 23, 42, .03), 0 10px 22px rgba(15, 23, 42, .07);
            overflow: hidden;
            transition: transform .18s, box-shadow .18s, border-color .18s;
        }

        .kpi-card::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, var(--accent) 0%, transparent 65%);
        }

        .kpi-card::after {
            content: "";
            position: absolute;
            right: 10px;
            top: 10px;
            width: 84px;
            height: 84px;
            background: radial-gradient(circle, var(--soft) 0%, transparent 65%);
            pointer-events: none;
        }

        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 0 rgba(15, 23, 42, .03), 0 16px 28px rgba(15, 23, 42, .10);
            border-color: rgba(15, 23, 42, .08);
        }

        .kpi-card .title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            color: #0b1220;
            letter-spacing: .2px;
        }

        .kpi-card .sub {
            font-size: 12px;
            color: var(--muted);
            margin-top: 2px;
        }

        .kpi-card .value {
            margin-top: 10px;
            font-size: 36px;
            font-weight: 800;
            letter-spacing: .2px;
            font-variant-numeric: tabular-nums;
        }

        .kpi-icon {
            position: absolute;
            right: 14px;
            top: 12px;
            font-size: 56px;
            opacity: .16;
            pointer-events: none;
        }

        .ongoing {
            --accent: var(--info);
            --soft: rgba(14, 165, 233, .14);
        }

        .completed {
            --accent: var(--ok);
            --soft: rgba(22, 163, 74, .14);
        }

        .rated {
            --accent: var(--lav);
            --soft: rgba(124, 58, 237, .14);
        }

        .pending {
            --accent: var(--pending);
            --soft: rgba(51, 65, 85, .12);
        }

        .late {
            --accent: var(--warn);
            --soft: rgba(245, 158, 11, .16);
        }

        .overdue {
            --accent: var(--bad);
            --soft: rgba(239, 68, 68, .14);
        }

        .ongoing .value {
            color: var(--info);
        }

        .completed .value {
            color: var(--ok);
        }

        .rated .value {
            color: var(--lav);
        }

        .pending .value {
            color: var(--pending);
        }

        .late .value {
            color: var(--warn);
        }

        .overdue .value {
            color: var(--bad);
        }

        .ongoing .kpi-icon {
            color: var(--info);
        }

        .completed .kpi-icon {
            color: var(--ok);
        }

        .rated .kpi-icon {
            color: var(--lav);
        }

        .pending .kpi-icon {
            color: var(--pending);
        }

        .late .kpi-icon {
            color: var(--warn);
        }

        .overdue .kpi-icon {
            color: var(--bad);
        }

        .panel {
            position: relative;
            background: var(--card-bg);
            border: 1px solid var(--card-br);
            border-radius: 16px;
            padding: 18px;
            box-shadow: 0 12px 28px rgba(15, 23, 42, .06);
            overflow: hidden;
        }

        .panel::before {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            background: radial-gradient(220px 220px at 100% 0%, rgba(14, 165, 233, .05), transparent 60%);
        }

        .row {
            display: flex;
            gap: var(--gap);
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .row>* {
            flex: 1 1 220px;
        }

        label {
            font-size: 12px;
            color: var(--muted);
            display: block;
            margin-bottom: 6px;
        }

        input[type="date"] {
            width: 80%;
            background: #fff;
            color: #0b1220;
            border: 1px solid var(--card-br);
            border-radius: 12px;
            padding: 10px 12px 10px 40px;
        }

        .panel .row>div {
            position: relative;
        }

        .panel .row>div::after {
            content: "\f133";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            position: absolute;
            left: 12px;
            top: 30px;
            color: var(--muted);
            opacity: .7;
            pointer-events: none;
        }

        .panel .row>div:last-of-type::after {
            content: none !important;
        }

        .panel button {
            align-self: flex-end;
            height: 42px;
            padding: 0 18px;
            border-radius: 12px;
            border: 0;
            background: linear-gradient(180deg, #0ea5e9 0%, #0284c7 100%);
            color: #fff;
            font-weight: 700;
            box-shadow: 0 8px 16px rgba(2, 132, 199, .25);
        }

        .panel table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 12px;
            overflow: hidden;
            border-radius: 12px;
        }

        thead th {
            position: sticky;
            top: 0;
            background: #f8fafc;
            border-bottom: 1px solid var(--card-br);
            color: var(--muted);
            font-size: 13px;
            font-weight: 700;
            text-align: left;
            padding: 12px;
        }

        tbody td {
            padding: 14px 12px;
            border-bottom: 1px solid var(--card-br);
            font-weight: 600;
        }

        tbody tr:last-child td {
            border-bottom: 0;
        }

        .dashboard {
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, .1);
        }

        .dashboard h2 {
            margin-top: 0;
        }

        .insights-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: var(--gap);
            margin-top: 18px;
        }

        @media (max-width:960px) {
            .insights-grid {
                grid-template-columns: 1fr;
            }
        }

        .insight-card {
            background: var(--card-bg);
            border: 1px solid var(--card-br);
            border-radius: 16px;
            padding: 16px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .07);
        }

        .ins-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 10px;
        }

        .ins-title .t {
            font-weight: 800;
            color: #0f172a;
            text-align: center;

        }

        .ins-title .s {
            font-size: 12px;
            color: var(--muted);
            margin-top: 2px;
            text-align: center;

        }

        .ins-nav {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            border: 1px solid var(--card-br);
            background: #f8fafc;
            color: #0f172a;
            cursor: pointer;
        }

        .ins-rows {
            display: grid;
            gap: 10px;
        }

        .ins-row {
            padding: 10px 12px;
            border: 1px solid var(--card-br);
            border-radius: 12px;
            background: linear-gradient(180deg, #fff 0%, #fbfdff 100%);
        }

        .ins-row .row-title {
            font-weight: 800;
            margin-bottom: 8px;
            color: #0f172a;
        }

        .ins-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: grid;
            gap: 6px;
        }

        .ins-list li {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            border-radius: 10px;
            border: 1px solid var(--card-br);
            border-radius: 12px;
            padding: 10px 12px;
            background: #f8fafc;
        }

        .ins-list .who {
            color: #0f172a;
            font-weight: 700;
        }

        .ins-list .metric {
            font-weight: 900;
            font-variant-numeric: tabular-nums;
        }

        .ins-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .ins-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .rank-badge {
            width: 26px;
            height: 26px;
            border-radius: 999px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 12px;
            letter-spacing: .2px;
        }

        .rank-badge.r1 {
            background: linear-gradient(180deg, #fde68a 0%, #f59e0b 100%);
            color: #1f2937;
            box-shadow: inset 0 0 0 2px rgba(245, 158, 11, .25);
        }

        .rank-badge.r2 {
            background: linear-gradient(180deg, #e5e7eb 0%, #9ca3af 100%);
            color: #1f2937;
            box-shadow: inset 0 0 0 2px rgba(156, 163, 175, .25);
        }

        .rank-badge.r3 {
            background: linear-gradient(180deg, #fcd5b5 0%, #f97316 100%);
            color: #1f2937;
            box-shadow: inset 0 0 0 2px rgba(249, 115, 22, .25);
        }

        /* Progress bar */
        .bar {
            width: 120px;
            height: 6px;
            border-radius: 999px;
            background: #e5e7eb;
            overflow: hidden;
        }

        .bar>span {
            display: block;
            height: 100%;
            width: 0;
            transition: width .35s ease;
            border-radius: inherit;
        }

        .bar-lav>span {
            background: var(--lav);
        }

        /* ratings */
        .bar-ok>span {
            background: var(--ok);
        }

        /* hours  */

        @media (max-width: 420px) {
            .bar {
                width: 90px;
            }
        }
    </style>
</head>

<body>
    <div class="main-container">
        <?php include '../includes/menu/menu_adm.php'; ?>
        <div class="content-container">
            <?php include '../includes/header_adm.php'; ?>
            <div class="dashboard">
                <h2>Admin Dashboard <span style="font-weight: normal; color: var(--text-secondary);"></span>
                </h2>

                <!-- KPI Cards -->
                <div class="kpi-grid" id="kpis">
                    <div class="kpi-card ongoing"><i class="kpi-icon fa-solid fa-arrows-rotate"></i>
                        <div class="title">Ongoing <span class="sub">(today)</span></div>
                        <div class="value" data-key="ongoing">–</div>
                    </div>
                    <div class="kpi-card completed"><i class="kpi-icon fa-solid fa-circle-check"></i>
                        <div class="title">Completed <span class="sub">(today)</span></div>
                        <div class="value" data-key="completed">–</div>
                    </div>
                    <div class="kpi-card rated"><i class="kpi-icon fa-solid fa-star"></i>
                        <div class="title">Rated <span class="sub">(today)</span></div>
                        <div class="value" data-key="rated">–</div>
                    </div>
                    <div class="kpi-card pending"><i class="kpi-icon fa-regular fa-clock"></i>
                        <div class="title">Haven't Start <span class="sub">(today)</span></div>
                        <div class="value" data-key="pending">–</div>
                    </div>
                    <div class="kpi-card late"><i class="kpi-icon fa-solid fa-hourglass-half"></i>
                        <div class="title">Late <span class="sub">(today)</span></div>
                        <div class="value" data-key="late">–</div>
                    </div>
                    <div class="kpi-card overdue"><i class="kpi-icon fa-solid fa-triangle-exclamation"></i>
                        <div class="title">Overdue <span class="sub">(today)</span></div>
                        <div class="value" data-key="overdue">–</div>
                    </div>
                </div>

                <!-- Range filter -->
                <div class="panel">
                    <div class="row">
                        <div><label for="start">Start date</label><input type="date" id="start" /></div>
                        <div><label for="end">End date</label><input type="date" id="end" /></div>
                        <div style="flex:0 0 auto"><button id="apply">Apply</button></div>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Count</th>
                            </tr>
                        </thead>
                        <tbody id="summary">
                            <tr>
                                <td>Pending (Haven’t Make Payment)</td>
                                <td>–</td>
                            </tr>
                            <tr>
                                <td>Paid</td>
                                <td>–</td>
                            </tr>
                            <tr>
                                <td>Confirmed</td>
                                <td>–</td>
                            </tr>
                            <tr>
                                <td>Completed</td>
                                <td>–</td>
                            </tr>
                            <tr>
                                <td>Rated</td>
                                <td>–</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Insights -->
                <div class="insights-grid">
                    <!-- WEEK -->
                    <div class="insight-card" id="ins-week">
                        <div class="ins-head">
                            <button class="ins-nav prev" data-period="week" aria-label="Previous week"><i
                                    class="fa-solid fa-chevron-left"></i></button>
                            <div class="ins-title">
                                <div class="t">Week</div>
                                <div class="s" id="week-range">—</div>
                            </div>
                            <button class="ins-nav next" data-period="week" aria-label="Next week"><i
                                    class="fa-solid fa-chevron-right"></i></button>
                        </div>
                        <div class="ins-rows">
                            <div class="ins-row">
                                <div class="row-title">Top Rating</div>
                                <ul class="ins-list" id="week-rating-list">
                                    <li><span class="who">—</span><span class="metric">—</span></li>
                                </ul>
                            </div>
                            <div class="ins-row">
                                <div class="row-title">Most Service Hours</div>
                                <ul class="ins-list" id="week-hours-list">
                                    <li><span class="who">—</span><span class="metric">—</span></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- MONTH -->
                    <div class="insight-card" id="ins-month">
                        <div class="ins-head">
                            <button class="ins-nav prev" data-period="month" aria-label="Previous month"><i
                                    class="fa-solid fa-chevron-left"></i></button>
                            <div class="ins-title">
                                <div class="t">Month</div>
                                <div class="s" id="month-range">—</div>
                            </div>
                            <button class="ins-nav next" data-period="month" aria-label="Next month"><i
                                    class="fa-solid fa-chevron-right"></i></button>
                        </div>
                        <div class="ins-rows">
                            <div class="ins-row">
                                <div class="row-title">Top Rating</div>
                                <ul class="ins-list" id="month-rating-list">
                                    <li><span class="who">—</span><span class="metric">—</span></li>
                                </ul>
                            </div>
                            <div class="ins-row">
                                <div class="row-title">Most Service Hours</div>
                                <ul class="ins-list" id="month-hours-list">
                                    <li><span class="who">—</span><span class="metric">—</span></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        (function () {
            const api = (act, params = {}) => {
                const q = new URLSearchParams({ action: act, ...params }).toString();
                return fetch('dashboard_counts.php?' + q, { credentials: 'same-origin' }).then(r => r.json());
            };

            // KPIs
            function refreshKpis() {
                api('overview').then(res => {
                    if (!res || !res.ok) return;
                    const data = res.data || {};
                    document.querySelectorAll('#kpis .value').forEach(el => {
                        const key = el.getAttribute('data-key');
                        el.textContent = (data[key] ?? 0);
                    });
                });
            }

            // Range status table
            const startInp = document.getElementById('start');
            const endInp = document.getElementById('end');
            function fmtDate(d) { return d.toISOString().slice(0, 10); }
            const today = new Date();
            const sevenAgo = new Date(Date.now() - 6 * 24 * 3600 * 1000);
            startInp.value = fmtDate(sevenAgo);
            endInp.value = fmtDate(today);

            function refreshRange() {
                const start = startInp.value, end = endInp.value;
                if (!start || !end) return;
                api('range', { start, end }).then(res => {
                    if (!res || !res.ok) return;
                    const d = res.data || {};
                    const tbody = document.getElementById('summary');
                    tbody.innerHTML = `
                        <tr><td>Pending (Haven’t Make Payment)</td><td>${d.pending ?? 0}</td></tr>
                        <tr><td>Paid</td><td>${d.paid ?? 0}</td></tr>
                        <tr><td>Confirmed</td><td>${d.confirm ?? 0}</td></tr>
                        <tr><td>Completed</td><td>${d.done ?? 0}</td></tr>
                        <tr><td>Rated</td><td>${d.rated ?? 0}</td></tr>
                    `;
                });
            }
            document.getElementById('apply').addEventListener('click', refreshRange);

            const offsets = { week: 0, month: 0 };
            const fmtHM = (mins) => {
                mins = Number(mins || 0);
                const h = Math.floor(mins / 60), m = mins % 60;
                return `${h}h ${String(m).padStart(2, '0')}m`;
            };

            function renderList(elId, rows, mode) {
                const el = document.getElementById(elId);
                if (!rows || rows.length === 0) {
                    el.innerHTML = `<li><span class="who">No data</span><span class="metric">—</span></li>`;
                    return;
                }

                // scale for the progress bar
                let maxVal = 1;
                if (mode === 'rating') {
                    maxVal = 5; // rating out of 5
                } else {
                    maxVal = rows.reduce((m, r) => Math.max(m, Number(r.minutes || 0)), 0) || 1;
                }

                el.innerHTML = rows.map((r, i) => {
                    const name = (r.sind_name && r.sind_name.trim()) ? r.sind_name : `Sinderella #${r.sind_id}`;
                    const rank = i + 1;
                    const rankClass = rank === 1 ? 'r1' : rank === 2 ? 'r2' : 'r3';

                    let metricText, pct, barClass;
                    if (mode === 'rating') {
                        const avg = Number(r.avg_rating || 0);
                        const cnt = Number(r.rating_count || 0);
                        metricText = `${avg.toFixed(2)} ★  (${cnt})`;
                        pct = Math.max(0, Math.min(100, (avg / maxVal) * 100));
                        barClass = 'bar-lav';
                    } else {
                        const mins = Number(r.minutes || 0);
                        const h = Math.floor(mins / 60), m = mins % 60;
                        metricText = `${h}h ${String(m).padStart(2, '0')}m`;
                        // keep tiny bars visible with a small floor
                        pct = Math.max(8, Math.min(100, (mins / maxVal) * 100));
                        barClass = 'bar-ok';
                    }

                    return `
          <li>
            <div class="ins-left">
              <span class="rank-badge ${rankClass}">${rank}</span>
              <span class="who">${name}</span>
            </div>
            <div class="ins-right">
              <span class="metric">${metricText}</span>
              <span class="bar ${barClass}"><span style="width:${pct}%"></span></span>
            </div>
          </li>
        `;
                }).join('');
            }


            function renderInsights(period, res) {
                if (!res || !res.ok) return;
                const rng = res.range || {};
                if (period === 'week') {
                    document.getElementById('week-range').textContent = rng.label || '—';
                    renderList('week-rating-list', res.topRating, 'rating');
                    renderList('week-hours-list', res.topHours, 'hours');
                } else {
                    document.getElementById('month-range').textContent = rng.label || '—';
                    renderList('month-rating-list', res.topRating, 'rating');
                    renderList('month-hours-list', res.topHours, 'hours');
                }
            }

            function loadInsights(period) {
                api('insights', { period, offset: offsets[period] }).then(res => renderInsights(period, res));
            }

            document.querySelectorAll('.ins-nav').forEach(btn => {
                btn.addEventListener('click', () => {
                    const period = btn.dataset.period;
                    if (btn.classList.contains('prev')) offsets[period] -= 1;
                    else offsets[period] += 1;
                    loadInsights(period);
                });
            });

            // initial load
            refreshKpis();
            refreshRange();
            loadInsights('week');
            loadInsights('month');
        })();
    </script>
</body>

</html>