<?php
session_start();
if (!isset($_SESSION['adm_id'])) {
    header("Location: ../login_adm.php");
    exit();
}

// Hard-coded sample data for dashboard view
$bestMaid = [
    'sind_id' => 1,
    'sind_name' => 'Sinderella One',
    'jobs_completed' => 15,
];

$topRatedMaid = [
    'sind_id' => 2,
    'sind_name' => 'Sinderella Two',
    'avg_rating' => 4.9,
    'reviews' => 12,
];

$activeCustomers = 20;    // Number of active customers in last 24h

$bookingStatusCounts = [
    'pending' => 3,
    'confirm' => 5,
    'done' => 10,
    'rated' => 8,
    'cancelled by admin' => 1,
    'cancelled by customer' => 2,
];

$avgDuration = 120;        // Average job duration in minutes


$topRatedMonth = [
    ['sind_id' => 1, 'sind_name' => 'Sinderella One', 'picture_path' => '../img/profile_photo/0001.jpg', 'avg_rating' => 4.9],
    ['sind_id' => 2, 'sind_name' => 'Sinderella Two', 'picture_path' => '../img/profile_photo/0002.jpg', 'avg_rating' => 4.7],
    ['sind_id' => 3, 'sind_name' => 'Sinderella Three', 'picture_path' => '../img/profile_photo/0003.jpg', 'avg_rating' => 4.5],
];

$topRatedWeek = [
    ['sind_id' => 2, 'sind_name' => 'Sinderella Two', 'picture_path' => '../img/profile_photo/0002.jpg', 'avg_rating' => 4.8],
    ['sind_id' => 1, 'sind_name' => 'Sinderella One', 'picture_path' => '../img/profile_photo/0001.jpg', 'avg_rating' => 4.6],
    ['sind_id' => 4, 'sind_name' => 'Sinderella Four', 'picture_path' => '../img/profile_photo/0004.jpg', 'avg_rating' => 4.4],
];

$topHoursMonth = [
    ['sind_id' => 3, 'sind_name' => 'Sinderella Three', 'picture_path' => '../img/profile_photo/0003.jpg', 'total_minutes' => 480],
    ['sind_id' => 1, 'sind_name' => 'Sinderella One', 'picture_path' => '../img/profile_photo/0001.jpg', 'total_minutes' => 450],
    ['sind_id' => 5, 'sind_name' => 'Sinderella Five', 'picture_path' => '../img/profile_photo/0005.jpg', 'total_minutes' => 420],
];

$topHoursWeek = [
    ['sind_id' => 1, 'sind_name' => 'Sinderella One', 'picture_path' => '../img/profile_photo/0001.jpg', 'total_minutes' => 260],
    ['sind_id' => 3, 'sind_name' => 'Sinderella Three', 'picture_path' => '../img/profile_photo/0003.jpg', 'total_minutes' => 240],
    ['sind_id' => 2, 'sind_name' => 'Sinderella Two', 'picture_path' => '../img/profile_photo/0002.jpg', 'total_minutes' => 220],
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard View - Admin - Sinderella</title>
    <link rel="icon" href="../img/sinderella_favicon.png" />
    <link rel="stylesheet" href="../includes/css/styles_user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: var(--primary-bg);
            margin: 0;
            color: var(--text-primary);
        }

        .main-container {
            padding: 20px;
        }

        .content-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .content-container>h2 {
            font-size: 1.75rem;
            margin-bottom: 1.5rem;
            color: var(--text-primary);
        }

        .content-container>h3 {
            font-size: 1.5rem;
            margin-top: 2.5rem;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        /* ——— KPI CARDS ——— */
        .kpi-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 2rem;
        }

        .kpi-card {
            background: var(--card-bg);
            padding: 24px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid var(--border-color);
        }

        .kpi-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .kpi-card h3 {
            margin: 0 0 10px;
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
        }

        .kpi-card h3 i {
            margin-right: 10px;
            width: 20px;
        }

        .kpi-card p {
            margin: 5px 0 0;
            color: var(--text-secondary);
            font-size: 1rem;
        }

        .kpi-card .kpi-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-top: 8px;
        }

        .kpi-card .icon-bg {
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 4rem;
            opacity: 0.07;
            transform: rotate(-15deg);
        }

        .kpi-card:nth-child(1) h3 i,
        .kpi-card:nth-child(1) .icon-bg {
            color: #17a2b8;
        }

        .kpi-card:nth-child(2) h3 i,
        .kpi-card:nth-child(2) .icon-bg {
            color: #fd7e14;
        }

        /* ——— PODIUM ——— */
        .section-title {
            margin-top: 3rem;
            text-align: center;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 2.5rem;
        }

        .podium-container {
            display: flex;
            justify-content: center;
            align-items: flex-end;
            gap: 2rem;
            margin: 2rem 0 4rem;
            flex-wrap: wrap;
            /* Allow wrapping on small screens */
        }

        .podium-card {
            position: relative;
            text-align: center;
            flex: 0 1 220px;
            background: var(--card-bg);
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid var(--border-color);
        }

        .podium-card:hover {
            transform: translateY(-8px) scale(1.03);
            box-shadow: var(--shadow-hover);
        }

        .podium-card img {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 1rem;
            box-shadow: 0 0 0 4px var(--card-bg);
        }

        .podium-card .position-label {
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            background-color: var(--card-bg);
            border-radius: 50%;
            width: 30px;
            height: 30px;
            line-height: 30px;
            font-size: 1.2rem;
            font-weight: bold;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .podium-card h4 {
            margin: .5rem 0 .25rem;
            font-size: 1.1rem;
            color: var(--text-primary);
            font-weight: 600;
        }

        .podium-card p {
            margin: 0;
            font-size: 1rem;
            color: var(--text-secondary);
        }

        .podium-card .rating-star {
            color: #ffc107;
            margin-left: 4px;
        }

        /* Gold, Silver, Bronze */
        .podium-card.position-1 {
            order: 2;
            transform: scale(1.15);
        }

        .podium-card.position-1 .position-label {
            color: var(--gold);
        }

        .podium-card.position-1 img {
            border: 4px solid var(--gold);
        }

        .podium-card.position-2 {
            order: 1;
        }

        .podium-card.position-2 .position-label {
            color: var(--silver);
        }

        .podium-card.position-2 img {
            border: 4px solid var(--silver);
        }

        .podium-card.position-3 {
            order: 3;
        }

        .podium-card.position-3 .position-label {
            color: var(--bronze);
        }

        .podium-card.position-3 img {
            border: 4px solid var(--bronze);
        }

        /* ——— STATUS TABLE ——— */
        .status-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--card-bg);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            margin-top: 1.5rem;
        }

        .status-table th,
        .status-table td {
            padding: 16px 20px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .status-table thead {
            background: var(--header-bg);
            color: var(--header-text);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-table tbody tr:last-child td {
            border-bottom: none;
        }

        .status-table tbody tr:hover {
            background-color: #f1f3f5;
        }

        .status-table td:first-child {
            font-weight: 500;
        }

        /* ——— RESPONSIVENESS ——— */
        @media (max-width: 992px) {
            .podium-container {
                gap: 1.5rem;
            }

            .podium-card {
                flex-basis: 200px;
            }

            .podium-card.position-1 {
                order: 1;
                /* Stack in order on smaller screens */
                transform: scale(1.1);
                margin-bottom: 2rem;
            }

            .podium-card.position-2,
            .podium-card.position-3 {
                order: 2;
            }
        }

        @media (max-width: 768px) {
            .content-container>h2 {
                font-size: 1.5rem;
            }

            .podium-container {
                flex-direction: column;
                align-items: center;
                gap: 2.5rem;
            }

            .podium-card {
                width: 90%;
                max-width: 300px;
            }

            /* Reset order and transform for stacked view */
            .podium-card.position-1,
            .podium-card.position-2,
            .podium-card.position-3 {
                order: 0;
                transform: scale(1);
                margin-bottom: 0;
            }

            .podium-card.position-1:hover,
            .podium-card:hover {
                transform: translateY(-5px);
            }
        }
    </style>
</head>

<body>
    <div class="main-container">
        <?php include '../includes/menu/menu_adm.php'; ?>
        <div class="content-container">
            <?php include '../includes/header_adm.php'; ?>
            <h2>Admin Dashboard <span style="font-weight: normal; color: var(--text-secondary);">(Demo View)</span></h2>

            <div class="kpi-container">
                <div class="kpi-card">
                    <h3><i class="fas fa-user-check"></i> Active Customers</h3>
                    <i class="fas fa-users icon-bg"></i>
                    <p><small>(Last 24h)</small></p>
                    <p class="kpi-value"><?php echo htmlspecialchars($activeCustomers); ?></p>
                </div>

                <div class="kpi-card">
                    <h3><i class="fas fa-clock"></i> Avg Job Duration</h3>
                    <i class="fas fa-hourglass-half icon-bg"></i>
                    <p class="kpi-value"><?php echo htmlspecialchars($avgDuration); ?> <span style="font-size: 1.5rem; font-weight: 500;">minutes</span></p>
                </div>
            </div>

            <h3 class="section-title">Top 3 Rated Maids This Month</h3>
            <div class="podium-container">
                <?php foreach ($topRatedMonth as $i => $m) : ?>
                    <div class="podium-card position-<?php echo $i + 1; ?>">
                        <span class="position-label"><?php echo $i + 1; ?></span>
                        <img src="<?php echo htmlspecialchars($m['picture_path']); ?>" alt="<?php echo htmlspecialchars($m['sind_name']); ?>">
                        <h4><?php echo htmlspecialchars($m['sind_name']); ?></h4>
                        <p><?php echo number_format($m['avg_rating'], 1); ?><i class="fas fa-star rating-star"></i></p>
                    </div>
                <?php endforeach; ?>
            </div>

            <h3 class="section-title">Top 3 Rated Maids This Week</h3>
            <div class="podium-container">
                <?php foreach ($topRatedWeek as $i => $m) : ?>
                    <div class="podium-card position-<?php echo $i + 1; ?>">
                        <span class="position-label"><?php echo $i + 1; ?></span>
                        <img src="<?php echo htmlspecialchars($m['picture_path']); ?>" alt="<?php echo htmlspecialchars($m['sind_name']); ?>">
                        <h4><?php echo htmlspecialchars($m['sind_name']); ?></h4>
                        <p><?php echo number_format($m['avg_rating'], 1); ?><i class="fas fa-star rating-star"></i></p>
                    </div>
                <?php endforeach; ?>
            </div>

            <h3 class="section-title">Top 3 Maids by Hours This Month</h3>
            <div class="podium-container">
                <?php foreach ($topHoursMonth as $i => $m) : ?>
                    <?php $h = floor($m['total_minutes'] / 60);
                    $min = $m['total_minutes'] % 60; ?>
                    <div class="podium-card position-<?php echo $i + 1; ?>">
                        <span class="position-label"><?php echo $i + 1; ?></span>
                        <img src="<?php echo htmlspecialchars($m['picture_path']); ?>" alt="<?php echo htmlspecialchars($m['sind_name']); ?>">
                        <h4><?php echo htmlspecialchars($m['sind_name']); ?></h4>
                        <p><?php echo $h; ?>h <?php echo $min; ?>m</p>
                    </div>
                <?php endforeach; ?>
            </div>

            <h3 class="section-title">Top 3 Maids by Hours This Week</h3>
            <div class="podium-container">
                <?php foreach ($topHoursWeek as $i => $m) : ?>
                    <?php $h = floor($m['total_minutes'] / 60);
                    $min = $m['total_minutes'] % 60; ?>
                    <div class="podium-card position-<?php echo $i + 1; ?>">
                        <span class="position-label"><?php echo $i + 1; ?></span>
                        <img src="<?php echo htmlspecialchars($m['picture_path']); ?>" alt="<?php echo htmlspecialchars($m['sind_name']); ?>">
                        <h4><?php echo htmlspecialchars($m['sind_name']); ?></h4>
                        <p><?php echo $h; ?>h <?php echo $min; ?>m</p>
                    </div>
                <?php endforeach; ?>
            </div>

            <h3>Bookings by Status (August 2025)</h3>
            <table class="status-table">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookingStatusCounts as $status => $count) : ?>
                        <tr>
                            <td><?php echo htmlspecialchars(ucfirst($status)); ?></td>
                            <td><?php echo htmlspecialchars($count); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>