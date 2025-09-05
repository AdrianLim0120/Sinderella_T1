<?php
session_start();
if (!isset($_SESSION['sind_id'])) {
    header("Location: ../login_sind.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard View - Admin - Sinderella</title>
    <link rel="icon" href="../img/sinderella_favicon.png" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../includes/css/styles_user.css">

    <style>
        /* ——— GLOBAL STYLES & VARIABLES ——— */
        :root {
            --primary-bg: #f8f9fa;
            --card-bg: #ffffff;
            --text-primary: #343a40;
            --text-secondary: #6c757d;
            --header-bg: #343a40;
            --header-text: #ffffff;
            --border-color: #dee2e6;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --shadow-hover: 0 6px 16px rgba(0, 0, 0, 0.12);
            --border-radius: 12px;
            --gold: #d4af37;
            --silver: #c0c0c0;
            --bronze: #cd7f32;
        }
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: var(--primary-bg);
            margin: 0;
            color: var(--text-primary);
        }
        .main-container { padding: 20px; }
        .content-container { max-width: 1400px; margin: 0 auto; }
        .content-container > h2 {
            font-size: 1.75rem; margin-bottom: 1.5rem; color: var(--text-primary);
        }
        .content-container > h3 {
            font-size: 1.5rem; margin-top: 2.5rem; margin-bottom: 1rem; color: var(--text-primary);
        }

        /* ——— RANGE CONTROLS ——— */
        .range-controls {
            display:flex; align-items:center; justify-content:center; gap:.75rem;
            margin: 0 0 1.25rem;
        }
        .range-controls .label {
            min-width: 180px; text-align:center; color: var(--text-secondary); font-weight: 600;
        }
        .range-controls button {
            background: var(--card-bg); color: var(--text-primary);
            border: 1px solid var(--border-color);
            padding: .5rem .75rem; border-radius: 8px; box-shadow: var(--shadow);
            cursor: pointer; transition: transform .15s ease, box-shadow .15s ease;
        }
        .range-controls button:hover { transform: translateY(-1px); box-shadow: var(--shadow-hover); }

        /* ——— PODIUM ——— */
        .section-title {
            margin-top: 2.25rem; text-align: center; font-size: 1.5rem; font-weight: 600;
            color: var(--text-primary); margin-bottom: 1.25rem;
        }
        .podium-container {
            display: flex; justify-content: center; align-items: flex-end; gap: 2rem;
            margin: 1.5rem 0 2.25rem; flex-wrap: wrap;
        }
        .podium-card {
            position: relative; text-align: center; flex: 0 1 220px; background: var(--card-bg);
            padding: 20px; border-radius: var(--border-radius); box-shadow: var(--shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease; border: 1px solid var(--border-color);
        }
        .podium-card:hover { transform: translateY(-8px) scale(1.03); box-shadow: var(--shadow-hover); }
        .podium-card img {
            width: 90px; height: 90px; border-radius: 50%; object-fit: cover; margin-bottom: 1rem;
            box-shadow: 0 0 0 4px var(--card-bg);
        }
        .podium-card .position-label {
            position: absolute; top: -15px; left: 50%; transform: translateX(-50%);
            background-color: var(--card-bg); border-radius: 50%; width: 30px; height: 30px;
            line-height: 30px; font-size: 1.2rem; font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,.1);
        }
        .podium-card h4 { margin: .5rem 0 .25rem; font-size: 1.1rem; color: var(--text-primary); font-weight: 600; }
        .podium-card p { margin: 0; font-size: 1rem; color: var(--text-secondary); }
        .podium-card .rating-star { color: #ffc107; margin-left: 4px; }

        /* Gold, Silver, Bronze */
        .podium-card.position-1 { order: 2; transform: scale(1.15); }
        .podium-card.position-1 .position-label { color: var(--gold); }
        .podium-card.position-1 img { border: 4px solid var(--gold); }
        .podium-card.position-2 { order: 1; }
        .podium-card.position-2 .position-label { color: var(--silver); }
        .podium-card.position-2 img { border: 4px solid var(--silver); }
        .podium-card.position-3 { order: 3; }
        .podium-card.position-3 .position-label { color: var(--bronze); }
        .podium-card.position-3 img { border: 4px solid var(--bronze); }

        /* ——— RESPONSIVENESS ——— */
        @media (max-width: 992px) {
            .podium-container { gap: 1.5rem; }
            .podium-card { flex-basis: 200px; }
            .podium-card.position-1 { order: 1; transform: scale(1.1); margin-bottom: 2rem; }
            .podium-card.position-2, .podium-card.position-3 { order: 2; }
        }
        @media (max-width: 768px) {
            .content-container>h2 { font-size: 1.5rem; }
            .podium-container { flex-direction: column; align-items: center; gap: 2.5rem; }
            .podium-card { width: 90%; max-width: 300px; }
            .podium-card.position-1, .podium-card.position-2, .podium-card.position-3 { order: 0; transform: scale(1); margin-bottom: 0; }
            .podium-card.position-1:hover, .podium-card:hover { transform: translateY(-5px); }
        }
        .empty-state { text-align:center; color: var(--text-secondary); padding: 1rem 0; }
    </style>
</head>

<body>
<div class="main-container">
    <?php include '../includes/menu/menu_sind.php'; ?>
    <div class="content-container">
        <?php include '../includes/header_sind.php'; ?>

        <h2>Sinderella Dashboard <span style="font-weight: normal; color: var(--text-secondary);">(Live)</span></h2>

        <!-- MONTH CONTROLS -->
        <div class="range-controls" id="controls-month">
            <button type="button" id="month-prev" aria-label="Previous month"><i class="fa-solid fa-chevron-left"></i></button>
            <div class="label"><span id="label-month">Loading…</span></div>
            <button type="button" id="month-next" aria-label="Next month"><i class="fa-solid fa-chevron-right"></i></button>
        </div>

        <h3 class="section-title">Top 3 Rated Maids (This Month)</h3>
        <div class="podium-container" id="podium-month-rated">
            <div class="empty-state">Loading…</div>
        </div>

        <h3 class="section-title">Top 3 Maids by Hours (This Month)</h3>
        <div class="podium-container" id="podium-month-hours">
            <div class="empty-state">Loading…</div>
        </div>

        <!-- WEEK CONTROLS -->
        <div class="range-controls" id="controls-week">
            <button type="button" id="week-prev" aria-label="Previous week"><i class="fa-solid fa-chevron-left"></i></button>
            <div class="label"><span id="label-week">Loading…</span></div>
            <button type="button" id="week-next" aria-label="Next week"><i class="fa-solid fa-chevron-right"></i></button>
        </div>

        <h3 class="section-title">Top 3 Rated Maids (This Week)</h3>
        <div class="podium-container" id="podium-week-rated">
            <div class="empty-state">Loading…</div>
        </div>

        <h3 class="section-title">Top 3 Maids by Hours (This Week)</h3>
        <div class="podium-container" id="podium-week-hours">
            <div class="empty-state">Loading…</div>
        </div>

    </div>
</div>

<script>
(() => {
    const API_URL = 'dashboard_counts.php';
    const DEFAULT_AVATAR = '../img/profile_photo/default.jpg';

    let monthOffset = 0;
    let weekOffset  = 0;

    const ids = {
        month: {
            label:   document.getElementById('label-month'),
            rated:   document.getElementById('podium-month-rated'),
            hours:   document.getElementById('podium-month-hours'),
            prevBtn: document.getElementById('month-prev'),
            nextBtn: document.getElementById('month-next'),
        },
        week: {
            label:   document.getElementById('label-week'),
            rated:   document.getElementById('podium-week-rated'),
            hours:   document.getElementById('podium-week-hours'),
            prevBtn: document.getElementById('week-prev'),
            nextBtn: document.getElementById('week-next'),
        }
    };

    function formatMinutes(total) {
        total = Number(total) || 0;
        const h = Math.floor(total / 60);
        const m = total % 60;
        return `${h}h ${m}m`;
    }

    function node(html) {
        const t = document.createElement('template');
        t.innerHTML = html.trim();
        return t.content.firstElementChild;
    }

    function renderEmpty(container, message='No data for this range.') {
        container.innerHTML = '';
        container.appendChild(node(`<div class="empty-state">${message}</div>`));
    }

    function escapeHTML(str) {
        const div = document.createElement('div');
        div.textContent = str ?? '';
        return div.innerHTML;
    }

    function renderPodium(container, items, type) {
        // type: 'rating' or 'hours'
        container.innerHTML = '';
        if (!items || !items.length) {
            renderEmpty(container);
            return;
        }
        items.slice(0, 3).forEach((item, i) => {
            const pos = i + 1;
            const imgSrc = item.sind_profile_path || item.picture_path || DEFAULT_AVATAR;
            const name = item.sind_name || `Sinderella #${item.sind_id}`;

            let meta = '';
            if (type === 'rating') {
                const avg = Number(item.avg_rating ?? 0).toFixed(1);
                meta = `${avg}<i class="fas fa-star rating-star"></i>`;
            } else {
                meta = `${formatMinutes(item.minutes)}`;
            }

            const card = node(`
                <div class="podium-card position-${pos}">
                    <span class="position-label">${pos}</span>
                    <img src="${escapeHTML(imgSrc)}" alt="${escapeHTML(name)}" onerror="this.src='${DEFAULT_AVATAR}'">
                    <h4>${escapeHTML(name)}</h4>
                    <p>${meta}</p>
                </div>
            `);
            container.appendChild(card);
        });
    }

    async function fetchPeriod(period, offset) {
        const url = new URL(API_URL, window.location.href);
        url.searchParams.set('period', period);
        url.searchParams.set('offset', String(offset));

        const target = period === 'month' ? ids.month : ids.week;
        // show loading
        target.label.textContent = 'Loading…';
        renderEmpty(target.rated, 'Loading…');
        renderEmpty(target.hours, 'Loading…');

        try {
            const res = await fetch(url.toString(), { credentials: 'same-origin' });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const data = await res.json();
            if (!data.ok) throw new Error(data.error || 'Unknown API error');

            target.label.textContent = data.range?.label || '';

            renderPodium(target.rated, data.topRating, 'rating');
            renderPodium(target.hours,  data.topHours,  'hours');
        } catch (err) {
            console.error('Fetch error', err);
            target.label.textContent = 'Error';
            renderEmpty(target.rated, 'Failed to load data.');
            renderEmpty(target.hours, 'Failed to load data.');
        }
    }

    // Hook up controls
    ids.month.prevBtn.addEventListener('click', () => { monthOffset -= 1; fetchPeriod('month', monthOffset); });
    ids.month.nextBtn.addEventListener('click', () => { monthOffset += 1; fetchPeriod('month', monthOffset); });
    ids.week.prevBtn.addEventListener('click',  () => { weekOffset  -= 1; fetchPeriod('week',  weekOffset);  });
    ids.week.nextBtn.addEventListener('click',  () => { weekOffset  += 1; fetchPeriod('week',  weekOffset);  });

    // Initial loads
    fetchPeriod('month', monthOffset);
    fetchPeriod('week',  weekOffset);
})();
</script>

</body>
</html>
