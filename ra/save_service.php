<?php
session_start();
if (!isset($_SESSION['adm_id'])) {
    header("Location: ../login_adm.php");
    exit();
}

require_once '../db_connect.php';

$service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
$service_name = ucfirst(strtolower(trim($_POST['service_name'])));
$service_duration = round((float)$_POST['service_duration'], 2);

// --- PRICING FIELDS ---
function get_post_num($name) {
    return isset($_POST[$name]) ? round((float)$_POST[$name], 2) : 0;
}

// Ad-hoc
$adhoc = [
    'total_price' => get_post_num('adhoc_service_price'),
    'platform' => get_post_num('adhoc_pr_platform'),
    'sinderella' => get_post_num('adhoc_pr_sind'),
    'lvl1' => get_post_num('adhoc_pr_lvl1'),
    'lvl2' => get_post_num('adhoc_pr_lvl2'),
    'lvl3' => get_post_num('adhoc_pr_lvl3'),
    'lvl4' => get_post_num('adhoc_pr_lvl4'),
    'br_basic' => get_post_num('adhoc_pr_br_basic'),
    'br_rate' => get_post_num('adhoc_pr_br_rate'),
    'br_perf' => get_post_num('adhoc_pr_br_perf'),
    'penalty24_total' => get_post_num('adhoc_service_price_resched24'),
    'penalty24_platform' => get_post_num('adhoc_pr_platform_resched24'),
    'penalty24_sind' => get_post_num('adhoc_pr_sind_resched24'),
    'penalty24_lvl1' => get_post_num('adhoc_pr_lvl1_resched24'),
    'penalty24_lvl2' => get_post_num('adhoc_pr_lvl2_resched24'),
    'penalty24_lvl3' => get_post_num('adhoc_pr_lvl3_resched24'),
    'penalty24_lvl4' => get_post_num('adhoc_pr_lvl4_resched24'),
    'penalty24_br_basic' => get_post_num('adhoc_pr_br_basic_resched24'),
    'penalty24_br_rate' => get_post_num('adhoc_pr_br_rate_resched24'),
    'penalty24_br_perf' => get_post_num('adhoc_pr_br_perf_resched24'),
    'penalty2_total' => get_post_num('adhoc_service_price_resched2'),
    'penalty2_platform' => get_post_num('adhoc_pr_platform_resched2'),
    'penalty2_sind' => get_post_num('adhoc_pr_sind_resched2'),
    'penalty2_lvl1' => get_post_num('adhoc_pr_lvl1_resched2'),
    'penalty2_lvl2' => get_post_num('adhoc_pr_lvl2_resched2'),
    'penalty2_lvl3' => get_post_num('adhoc_pr_lvl3_resched2'),
    'penalty2_lvl4' => get_post_num('adhoc_pr_lvl4_resched2'),
    'penalty2_br_basic' => get_post_num('adhoc_pr_br_basic_resched2'),
    'penalty2_br_rate' => get_post_num('adhoc_pr_br_rate_resched2'),
    'penalty2_br_perf' => get_post_num('adhoc_pr_br_perf_resched2')
];

// Recurring
$rec = [
    'total_price' => get_post_num('rec_service_price'),
    'platform' => get_post_num('rec_pr_platform'),
    'sinderella' => get_post_num('rec_pr_sind'),
    'lvl1' => get_post_num('rec_pr_lvl1'),
    'lvl2' => get_post_num('rec_pr_lvl2'),
    'lvl3' => get_post_num('rec_pr_lvl3'),
    'lvl4' => get_post_num('rec_pr_lvl4'),
    'br_basic' => get_post_num('rec_pr_br_basic'),
    'br_rate' => get_post_num('rec_pr_br_rate'),
    'br_perf' => get_post_num('rec_pr_br_perf'),
    'penalty24_total' => get_post_num('rec_service_price_resched24'),
    'penalty24_platform' => get_post_num('rec_pr_platform_resched24'),
    'penalty24_sind' => get_post_num('rec_pr_sind_resched24'),
    'penalty24_lvl1' => get_post_num('rec_pr_lvl1_resched24'),
    'penalty24_lvl2' => get_post_num('rec_pr_lvl2_resched24'),
    'penalty24_lvl3' => get_post_num('rec_pr_lvl3_resched24'),
    'penalty24_lvl4' => get_post_num('rec_pr_lvl4_resched24'),
    'penalty24_br_basic' => get_post_num('rec_pr_br_basic_resched24'),
    'penalty24_br_rate' => get_post_num('rec_pr_br_rate_resched24'),
    'penalty24_br_perf' => get_post_num('rec_pr_br_perf_resched24'),
    'penalty2_total' => get_post_num('rec_service_price_resched2'),
    'penalty2_platform' => get_post_num('rec_pr_platform_resched2'),
    'penalty2_sind' => get_post_num('rec_pr_sind_resched2'),
    'penalty2_lvl1' => get_post_num('rec_pr_lvl1_resched2'),
    'penalty2_lvl2' => get_post_num('rec_pr_lvl2_resched2'),
    'penalty2_lvl3' => get_post_num('rec_pr_lvl3_resched2'),
    'penalty2_lvl4' => get_post_num('rec_pr_lvl4_resched2'),
    'penalty2_br_basic' => get_post_num('rec_pr_br_basic_resched2'),
    'penalty2_br_rate' => get_post_num('rec_pr_br_rate_resched2'),
    'penalty2_br_perf' => get_post_num('rec_pr_br_perf_resched2')
];

// --- VALIDATION (column by column) ---
function validate_pricing($arr, $typeLabel) {
    // Total price check
    $sum = $arr['platform'] + $arr['sinderella'] + $arr['lvl1'] + $arr['lvl2'] + $arr['lvl3'] + $arr['lvl4'];
    if (number_format($sum,2) != number_format($arr['total_price'],2)) {
        echo "$typeLabel: Platform + Sinderella + Level 1-4 Referral must equal Total Price";
        exit();
    }
    // Sinderella breakdown check
    $sum_br = $arr['br_basic'] + $arr['br_rate'] + $arr['br_perf'];
    /*if (number_format($sum_br,2) != number_format($arr['sinderella'],2)) {
        echo "$typeLabel: Basic + Rating + Performance must equal Sinderella";
        exit();
    }*/
}
validate_pricing($adhoc, "Ad-hoc");
validate_pricing($rec, "Recurring");

// --- SERVICE INSERT/UPDATE ---
if ($service_id) {
    // Update
    $stmt = $conn->prepare("UPDATE services SET service_name = ?, service_duration = ? WHERE service_id = ?");
    $stmt->bind_param("sdi", $service_name, $service_duration, $service_id);
    $stmt->execute();
    $stmt->close();
} else {
    // Insert
    $stmt = $conn->prepare("INSERT INTO services (service_name, service_duration) VALUES (?, ?)");
    $stmt->bind_param("sd", $service_name, $service_duration);
    $stmt->execute();
    $service_id = $stmt->insert_id;
    $stmt->close();
}

// --- PRICINGS INSERT/UPDATE ---
function save_pricing($conn, $service_id, $type, $arr) {
    // Check if exists
    $stmt = $conn->prepare("SELECT id FROM pricings WHERE service_id = ? AND service_type = ?");
    $stmt->bind_param("is", $service_id, $type);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        // Update
        $stmt->close();
        $stmt = $conn->prepare("UPDATE pricings SET 
            total_price=?, platform=?, sinderella=?, lvl1=?, lvl2=?, lvl3=?, lvl4=?, br_basic=?, br_rate=?, br_perf=?,
            penalty24_total=?, penalty24_platform=?, penalty24_sind=?, penalty24_lvl1=?, penalty24_lvl2=?, penalty24_lvl3=?, penalty24_lvl4=?, penalty24_br_basic=?, penalty24_br_rate=?, penalty24_br_perf=?,
            penalty2_total=?, penalty2_platform=?, penalty2_sind=?, penalty2_lvl1=?, penalty2_lvl2=?, penalty2_lvl3=?, penalty2_lvl4=?, penalty2_br_basic=?, penalty2_br_rate=?, penalty2_br_perf=?
            WHERE service_id=? AND service_type=?");
        $stmt->bind_param("ddddddddddddddddddddddddddddddis", 
            $arr['total_price'], $arr['platform'], $arr['sinderella'], $arr['lvl1'], $arr['lvl2'], $arr['lvl3'], $arr['lvl4'], $arr['br_basic'], $arr['br_rate'], $arr['br_perf'],
            $arr['penalty24_total'], $arr['penalty24_platform'], $arr['penalty24_sind'], $arr['penalty24_lvl1'], $arr['penalty24_lvl2'], $arr['penalty24_lvl3'], $arr['penalty24_lvl4'], $arr['penalty24_br_basic'], $arr['penalty24_br_rate'], $arr['penalty24_br_perf'],
            $arr['penalty2_total'], $arr['penalty2_platform'], $arr['penalty2_sind'], $arr['penalty2_lvl1'], $arr['penalty2_lvl2'], $arr['penalty2_lvl3'], $arr['penalty2_lvl4'], $arr['penalty2_br_basic'], $arr['penalty2_br_rate'], $arr['penalty2_br_perf'],
            $service_id, $type
        );
        $stmt->execute();
        $stmt->close();
    } else {
        // Insert
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO pricings (
            service_id, service_type, total_price, platform, sinderella, lvl1, lvl2, lvl3, lvl4, br_basic, br_rate, br_perf,
            penalty24_total, penalty24_platform, penalty24_sind, penalty24_lvl1, penalty24_lvl2, penalty24_lvl3, penalty24_lvl4, penalty24_br_basic, penalty24_br_rate, penalty24_br_perf,
            penalty2_total, penalty2_platform, penalty2_sind, penalty2_lvl1, penalty2_lvl2, penalty2_lvl3, penalty2_lvl4, penalty2_br_basic, penalty2_br_rate, penalty2_br_perf
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isdddddddddddddddddddddddddddddd", 
            $service_id, $type, $arr['total_price'], $arr['platform'], $arr['sinderella'], $arr['lvl1'], $arr['lvl2'], $arr['lvl3'], $arr['lvl4'], $arr['br_basic'], $arr['br_rate'], $arr['br_perf'],
            $arr['penalty24_total'], $arr['penalty24_platform'], $arr['penalty24_sind'], $arr['penalty24_lvl1'], $arr['penalty24_lvl2'], $arr['penalty24_lvl3'], $arr['penalty24_lvl4'], $arr['penalty24_br_basic'], $arr['penalty24_br_rate'], $arr['penalty24_br_perf'],
            $arr['penalty2_total'], $arr['penalty2_platform'], $arr['penalty2_sind'], $arr['penalty2_lvl1'], $arr['penalty2_lvl2'], $arr['penalty2_lvl3'], $arr['penalty2_lvl4'], $arr['penalty2_br_basic'], $arr['penalty2_br_rate'], $arr['penalty2_br_perf']
        );
        $stmt->execute();
        $stmt->close();
    }
}
save_pricing($conn, $service_id, 'a', $adhoc);
save_pricing($conn, $service_id, 'r', $rec);

// --- ADD-ONS ---
$addon_id = isset($_POST['addon_id']) ? $_POST['addon_id'] : [];
$addon_desc = isset($_POST['addon_desc']) ? $_POST['addon_desc'] : [];
$addon_duration = isset($_POST['addon_duration']) ? $_POST['addon_duration'] : [];
$addon_price = isset($_POST['addon_price']) ? $_POST['addon_price'] : [];
$addon_platform = isset($_POST['addon_platform']) ? $_POST['addon_platform'] : [];
$addon_sind = isset($_POST['addon_sind']) ? $_POST['addon_sind'] : [];
$addon_price_recurring = isset($_POST['addon_price_recurring']) ? $_POST['addon_price_recurring'] : [];
$addon_platform_recurring = isset($_POST['addon_platform_recurring']) ? $_POST['addon_platform_recurring'] : [];
$addon_sind_recurring = isset($_POST['addon_sind_recurring']) ? $_POST['addon_sind_recurring'] : [];
$addon_price_resched24 = isset($_POST['addon_price_resched24']) ? $_POST['addon_price_resched24'] : [];
$addon_platform_resched24 = isset($_POST['addon_platform_resched24']) ? $_POST['addon_platform_resched24'] : [];
$addon_sind_resched24 = isset($_POST['addon_sind_resched24']) ? $_POST['addon_sind_resched24'] : [];
$addon_price_resched2 = isset($_POST['addon_price_resched2']) ? $_POST['addon_price_resched2'] : [];
$addon_platform_resched2 = isset($_POST['addon_platform_resched2']) ? $_POST['addon_platform_resched2'] : [];
$addon_sind_resched2 = isset($_POST['addon_sind_resched2']) ? $_POST['addon_sind_resched2'] : [];
$addon_price_resched24_re = isset($_POST['addon_price_resched24_re']) ? $_POST['addon_price_resched24_re'] : [];
$addon_platform_resched24_re = isset($_POST['addon_platform_resched24_re']) ? $_POST['addon_platform_resched24_re'] : [];
$addon_sind_resched24_re = isset($_POST['addon_sind_resched24_re']) ? $_POST['addon_sind_resched24_re'] : [];
$addon_price_resched2_re = isset($_POST['addon_price_resched2_re']) ? $_POST['addon_price_resched2_re'] : [];
$addon_platform_resched2_re = isset($_POST['addon_platform_resched2_re']) ? $_POST['addon_platform_resched2_re'] : [];
$addon_sind_resched2_re = isset($_POST['addon_sind_resched2_re']) ? $_POST['addon_sind_resched2_re'] : [];

// Save each add-on
for ($i = 0; $i < count($addon_desc); $i++) {
    $desc = ucfirst(strtolower(trim($addon_desc[$i])));
    $duration = isset($addon_duration[$i]) ? round((float)$addon_duration[$i], 2) : 0;
    $price = isset($addon_price[$i]) ? round((float)$addon_price[$i], 2) : 0;
    $platform = isset($addon_platform[$i]) ? round((float)$addon_platform[$i], 2) : 0;
    $sind = isset($addon_sind[$i]) ? round((float)$addon_sind[$i], 2) : 0;
    $price_rec = isset($addon_price_recurring[$i]) ? round((float)$addon_price_recurring[$i], 2) : 0;
    $platform_rec = isset($addon_platform_recurring[$i]) ? round((float)$addon_platform_recurring[$i], 2) : 0;
    $sind_rec = isset($addon_sind_recurring[$i]) ? round((float)$addon_sind_recurring[$i], 2) : 0;
    $price_24 = isset($addon_price_resched24[$i]) ? round((float)$addon_price_resched24[$i], 2) : 0;
    $platform_24 = isset($addon_platform_resched24[$i]) ? round((float)$addon_platform_resched24[$i], 2) : 0;
    $sind_24 = isset($addon_sind_resched24[$i]) ? round((float)$addon_sind_resched24[$i], 2) : 0;
    $price_2 = isset($addon_price_resched2[$i]) ? round((float)$addon_price_resched2[$i], 2) : 0;
    $platform_2 = isset($addon_platform_resched2[$i]) ? round((float)$addon_platform_resched2[$i], 2) : 0;
    $sind_2 = isset($addon_sind_resched2[$i]) ? round((float)$addon_sind_resched2[$i], 2) : 0;
    $price_resched24_re = isset($addon_price_resched24_re[$i]) ? round((float)$addon_price_resched24_re[$i], 2) : 0;
    $platform_resched24_re = isset($addon_platform_resched24_re[$i]) ? round((float)$addon_platform_resched24_re[$i], 2) : 0;
    $sind_resched24_re = isset($addon_sind_resched24_re[$i]) ? round((float)$addon_sind_resched24_re[$i], 2) : 0;
    $price_resched2_re = isset($addon_price_resched2_re[$i]) ? round((float)$addon_price_resched2_re[$i], 2) : 0;
    $platform_resched2_re = isset($addon_platform_resched2_re[$i]) ? round((float)$addon_platform_resched2_re[$i], 2) : 0;
    $sind_resched2_re = isset($addon_sind_resched2_re[$i]) ? round((float)$addon_sind_resched2_re[$i], 2) : 0;

    // Validation for each add-on scenario
    $scenarios = [
        ['label' => 'Ad-hoc', 'price' => $price, 'platform' => $platform, 'sind' => $sind],
        ['label' => 'Recurring', 'price' => $price_rec, 'platform' => $platform_rec, 'sind' => $sind_rec],
        ['label' => 'Penalty <24h', 'price' => $price_24, 'platform' => $platform_24, 'sind' => $sind_24],
        ['label' => 'Penalty <2h', 'price' => $price_2, 'platform' => $platform_2, 'sind' => $sind_2],
    ];
    foreach ($scenarios as $sc) {
        if (number_format($sc['platform'] + $sc['sind'], 2) != number_format($sc['price'], 2)) {
            echo "Add-on " . ($i+1) . " ({$sc['label']}): Price does not match Platform + Sinderella. Price: {$sc['price']}, Platform+Sinderella: " . number_format($sc['platform'] + $sc['sind'], 2);
            exit();
        }
        if ($sc['price'] < 0 || $sc['platform'] < 0 || $sc['sind'] < 0) {
            echo "Add-on " . ($i+1) . " ({$sc['label']}): Negative numbers are not allowed.";
            exit();
        }
    }

    if (!empty($addon_id[$i])) {
        // Update
        $stmt = $conn->prepare("UPDATE addon SET ao_desc=?, ao_price=?, ao_platform=?, ao_sind=?, ao_duration=?, ao_price_recurring=?, ao_platform_recurring=?, ao_sind_recurring=?, ao_price_resched24=?, ao_platform_resched24=?, ao_sind_resched24=?, ao_price_resched2=?, ao_platform_resched2=?, ao_sind_resched2=?, ao_price_resched24_re=?, ao_platform_resched24_re=?, ao_sind_resched24_re=?, ao_price_resched2_re=?, ao_platform_resched2_re=?, ao_sind_resched2_re=? WHERE ao_id=?");
        $stmt->bind_param("sdddddddddddddddddddi", $desc, $price, $platform, $sind, $duration, $price_rec, $platform_rec, $sind_rec, $price_24, $platform_24, $sind_24, $price_2, $platform_2, $sind_2, $price_resched24_re, $platform_resched24_re, $sind_resched24_re, $price_resched2_re, $platform_resched2_re, $sind_resched2_re, $addon_id[$i]);
    } else {
        // Insert
        $stmt = $conn->prepare("INSERT INTO addon (service_id, ao_desc, ao_price, ao_platform, ao_sind, ao_duration, ao_price_recurring, ao_platform_recurring, ao_sind_recurring, ao_price_resched24, ao_platform_resched24, ao_sind_resched24, ao_price_resched2, ao_platform_resched2, ao_sind_resched2, ao_price_resched24_re, ao_platform_resched24_re, ao_sind_resched24_re, ao_price_resched2_re, ao_platform_resched2_re, ao_sind_resched2_re) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isddddddddddddddddddd", $service_id, $desc, $price, $platform, $sind, $duration, $price_rec, $platform_rec, $sind_rec, $price_24, $platform_24, $sind_24, $price_2, $platform_2, $sind_2, $price_resched24_re, $platform_resched24_re, $sind_resched24_re, $price_resched2_re, $platform_resched2_re, $sind_resched2_re);
    }
    $stmt->execute();
    $stmt->close();
}

header("Location: manage_pricing.php");
exit();
?>