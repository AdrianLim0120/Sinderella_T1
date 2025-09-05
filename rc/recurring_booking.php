<?php
session_start();
if (!isset($_SESSION['cust_id'])) {
    header("Location: ../login_cust.php");
    exit();
}
$cust_id = $_SESSION['cust_id'];

// Restore previous values if coming back from confirm page (via POST)
$recurring_data = [
    'address' => $_POST['address'] ?? '',
    'service' => $_POST['service'] ?? '',
    'blocks' => []
];
$block_count = isset($_POST['block_count']) ? intval($_POST['block_count']) : 2;
$block_count = max(2, $block_count); // Always at least 2

for ($i = 1; $i <= $block_count; $i++) {
    $recurring_data['blocks'][$i] = [
        'date' => $_POST["date_$i"] ?? '',
        'sinderella' => $_POST["sinderella_$i"] ?? '',
        'time' => $_POST["time_$i"] ?? '',
        'addons' => isset($_POST["addons_$i"]) ? (is_array($_POST["addons_$i"]) ? $_POST["addons_$i"] : explode(',', $_POST["addons_$i"])) : []
    ];
}

// Fetch addresses
require_once '../db_connect.php';
$stmt = $conn->prepare("SELECT * FROM cust_addresses WHERE cust_id = ?");
$stmt->bind_param("i", $cust_id);
$stmt->execute();
$addresses_result = $stmt->get_result();
$addresses = [];
while ($row = $addresses_result->fetch_assoc()) $addresses[] = $row;
$stmt->close();

// Fetch recurring services
$services_query = "
    SELECT 
        s.service_id, 
        s.service_name, 
        s.service_duration, 
        p.total_price AS service_price
    FROM services s
    LEFT JOIN pricings p ON s.service_id = p.service_id AND p.service_type = 'r'
    WHERE s.service_status = 'active'
";
$services_result = $conn->query($services_query);
$services = [];
while ($row = $services_result->fetch_assoc()) $services[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Recurring Booking - Sinderella</title>
    <link rel="icon" href="../img/sinderella_favicon.png"/>
    <link rel="stylesheet" href="../includes/css/styles_user.css">
    <style>
        .collapse-block { border:1px solid #ccc; border-radius:6px; margin-bottom:16px; position:relative; }
        .collapse-header { background:#f5f5f5; padding:12px; cursor:pointer; font-weight:bold; }
        .collapse-content { display:none; padding:16px; }
        .collapse-block.active .collapse-content { display:block; }
        .collapse-block.active .collapse-header { background:#e0eaff; }
        .summary-null { color:#888; }
        .confirm-btn { margin-top:20px; padding:10px 24px; background:#007bff; color:#fff; border:none; border-radius:5px; cursor:pointer; }
        .confirm-btn:disabled { background:#aaa; cursor:not-allowed; }
        .summary-table td { padding:4px 8px; }
        .addon-container { margin-top: 10px; }
        .sinderella-container { margin-top: 10px; }
        .sinderella-item { border: 1px solid #ddd; padding: 10px; border-radius: 5px; margin-top: 10px; display: flex; align-items: center; }
        .sinderella-item img { width: 60px; height: 60px; border-radius: 50%; margin-right: 15px; }
    </style>
</head>
<body>
<div class="main-container">
    <?php include '../includes/menu/menu_cust.php'; ?>
    <div class="content-container">
        <?php include '../includes/header_cust.php'; ?>
        <div class="profile-container">
        <h2>Recurring Booking</h2>
        <form id="recurringBookingForm" method="POST" action="confirm_recurring_booking.php">
            <input type="hidden" id="block_count" name="block_count" value="<?php echo $block_count; ?>">
            <input type="hidden" id="cust_address_id" name="cust_address_id" value="">
            <div style="margin-bottom:20px;">
                <label><strong>Select Address:</strong></label>
                <select id="address" name="address" required>
                    <option value="">-- Select Address --</option>
                    <?php foreach ($addresses as $addr): 
                        $val = htmlspecialchars("{$addr['cust_address']}, {$addr['cust_postcode']}, {$addr['cust_area']}, {$addr['cust_state']}");
                        ?>
                        <option value="<?php echo $val; ?>" 
                            data-area="<?php echo $addr['cust_area']; ?>" 
                            data-state="<?php echo $addr['cust_state']; ?>"
                            data-address-id="<?php echo $addr['cust_address_id']; ?>"
                            <?php if (($recurring_data['address'] ?? '') == $val) echo 'selected'; ?>>
                            <?php echo "{$addr['cust_postcode']}, {$addr['cust_area']}, {$addr['cust_state']} - {$addr['cust_address']}"; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="margin-bottom:20px;">
                <label><strong>Select Service:</strong></label>
                <select id="service" name="service" required>
                    <?php foreach ($services as $service): 
                        $aa = number_format($service['service_price'], 2);
                        $bb = number_format($service['service_price'] * 4, 2);
                        ?>
                        <option value="<?php echo $service['service_id']; ?>" data-duration="<?php echo $service['service_duration']; ?>" data-price="<?php echo $service['service_price']; ?>"
                            <?php if (($recurring_data['service'] ?? '') == $service['service_id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($service['service_name']); ?> (RM <?php echo $aa; ?> per booking)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div id="bookingBlocksContainer">
                <!-- Booking blocks will be rendered here -->
            </div>
            <button type="button" id="addMoreBtn" class="confirm-btn" style="background:#28a745;">+ Add More Booking</button><br>
            <button type="submit" class="confirm-btn" id="confirmBtn">Confirm Recurring Booking</button>
        </form>
        </div>
    </div>
</div>
<script>
let expandedBlock = null;
let bookingData = <?php echo json_encode($recurring_data['blocks']); ?>;
let blockCount = <?php echo $block_count; ?>;
let addressSelect = document.getElementById('address');
let serviceSelect = document.getElementById('service');
let bookingBlocksContainer = document.getElementById('bookingBlocksContainer');

// Store loaded Sinderellas and Add-ons for each block
let sinderellaCache = {};
let addonCache = {};

function renderBookingBlocks() {
    bookingBlocksContainer.innerHTML = '';
    for (let i = 1; i <= blockCount; i++) {
        let block = document.createElement('div');
        block.className = 'collapse-block';
        block.id = 'block' + i;

        let header = document.createElement('div');
        header.className = 'collapse-header';
        header.textContent = i + (i==1?'st':(i==2?'nd':(i==3?'rd':'th'))) + ' Booking';
        block.appendChild(header);

        // Remove button for blocks > 2
        if (i > 2) {
            let removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'delete-button';
            removeBtn.innerHTML = '&times;';
            removeBtn.title = 'Remove this booking';
            removeBtn.onclick = function(e) {
                e.stopPropagation();
                removeBookingBlock(i);
            };
            block.appendChild(removeBtn);
        }

        let summary = document.createElement('div');
        summary.className = 'block-summary';
        summary.id = 'summaryStatus' + i;
        summary.style = "margin-bottom:10px; color:#555; margin-left:12px;";
        summary.innerHTML = '(Not selected)';
        block.appendChild(summary);

        let content = document.createElement('div');
        content.className = 'collapse-content';
        content.id = 'content' + i;
        content.innerHTML = `
            <label for="date_${i}"><strong>Select Date:</strong></label>
            <input type="date" id="date_${i}" name="date_${i}" value="${bookingData[i]?.date ? bookingData[i].date : ''}">
            <div class="sinderella-container">
                <h3 id="sinderellaTitle_${i}">Available Sinderellas</h3>
                <div id="sinderellaList_${i}"></div>
            </div>
            <div class="addon-container">
                <h3>Add-ons</h3>
                <div id="addons_${i}"></div>
            </div>
            <input type="hidden" name="sinderella_${i}" id="sinderella_${i}" value="${bookingData[i]?.sinderella ? bookingData[i].sinderella : ''}">
            <input type="hidden" name="time_${i}" id="time_${i}" value="${bookingData[i]?.time ? bookingData[i].time : ''}">
            <input type="hidden" name="addons_${i}" id="addons_input_${i}" value="${bookingData[i]?.addons ? bookingData[i].addons.join(',') : ''}">
        `;
        block.appendChild(content);

        bookingBlocksContainer.appendChild(block);
    }

    // Add event listeners for collapse/expand
    for (let i = 1; i <= blockCount; i++) {
        document.getElementById('block' + i).querySelector('.collapse-header').addEventListener('click', function() {
            expandBlock(i);
        });
        let dateInput = document.getElementById('date_' + i);
        dateInput.addEventListener('change', function() {
            bookingData[i].date = this.value;
            loadSinderellas(i);
            updateSummary(i);
            // After date change, reload all blocks with same date to update disables
            for (let j = 1; j <= blockCount; j++) {
                if (j !== i && bookingData[j].date === bookingData[i].date) {
                    loadSinderellas(j);
                }
            }
        });
        // Set min/max date
        const now = new Date();
        const timezoneOffset = now.getTimezoneOffset() * 60000;
        const localNow = new Date(now.getTime() - timezoneOffset);
        const tomorrow = new Date(localNow);
        tomorrow.setDate(localNow.getDate() + 3);
        const tomorrowISO = tomorrow.toISOString().slice(0, 10);
        const maxDate = new Date(localNow);
        maxDate.setDate(localNow.getDate() + 31);
        const maxDateISO = maxDate.toISOString().slice(0, 10);
        dateInput.min = tomorrowISO;
        dateInput.max = maxDateISO;
    }

    // Load Sinderellas and Addons for each block
    for (let i = 1; i <= blockCount; i++) {
        loadSinderellas(i);
        loadAddons(i);
        updateSummary(i);
    }
    checkAllFilled();
}

function addBookingBlock() {
    blockCount++;
    bookingData[blockCount] = { date: '', sinderella: '', time: '', addons: [] };
    document.getElementById('block_count').value = blockCount;
    renderBookingBlocks();
}

function removeBookingBlock(idx) {
    // Remove the block and reindex
    for (let i = idx; i < blockCount; i++) {
        bookingData[i] = bookingData[i + 1];
    }
    delete bookingData[blockCount];
    blockCount--;
    document.getElementById('block_count').value = blockCount;
    renderBookingBlocks();
}

function expandBlock(idx) {
    // If already expanded, collapse it
    if (expandedBlock === idx) {
        document.getElementById('block' + idx).classList.remove('active');
        expandedBlock = null;
        return;
    }
    // Collapse all, then expand selected
    for (let i = 1; i <= blockCount; i++) {
        document.getElementById('block' + i).classList.remove('active');
    }
    document.getElementById('block' + idx).classList.add('active');
    expandedBlock = idx;
    loadSinderellas(idx);
    loadAddons(idx);
}

function checkAllFilled() {
    let allFilled = true;
    for (let i = 1; i <= blockCount; i++) {
        let b = bookingData[i];
        if (!b.sinderella || !b.time) allFilled = false;
    }
    // document.getElementById('confirmBtn').disabled = !allFilled;
}

function loadSinderellas(idx, callback) {
    let dateInput = document.getElementById('date_' + idx);
    let sinderellaList = document.getElementById('sinderellaList_' + idx);
    let sinderellaTitle = document.getElementById('sinderellaTitle_' + idx);
    let area = addressSelect.selectedOptions[0]?.dataset.area;
    let state = addressSelect.selectedOptions[0]?.dataset.state;
    let date = dateInput.value;
    if (!date || !area || !state) {
        sinderellaList.innerHTML = '';
        sinderellaTitle.textContent = 'Available Sinderellas';
        return;
    }
    fetch(`get_available_sinderellas.php?date=${date}&area=${encodeURIComponent(area)}&state=${encodeURIComponent(state)}`)
        .then(resp => resp.json())
        .then(data => {
            sinderellaCache[idx] = data; // cache for summary
            sinderellaList.innerHTML = '';
            if (data.length === 0) {
                sinderellaTitle.textContent = `No Sinderellas available on ${date} in ${area}, ${state}`;
                return;
            }
            sinderellaTitle.textContent = `Sinderellas available on ${date} in ${area}, ${state}`;
            data.forEach(s => {
                const div = document.createElement('div');
                div.className = 'sinderella-item';
                let ratingHtml = '';
                if (s.avg_rating !== null && s.review_count > 0) {
                    ratingHtml = `<span class="sind-rating" style="margin-left:8px;color:#F09E0B;font-size:16px;cursor:pointer;" data-sind-id="${s.sind_id}">
                        &#11088;${s.avg_rating} (${s.review_count} review${s.review_count > 1 ? 's' : ''})
                    </span>`;
                } else {
                    ratingHtml = `<span style="margin-left:8px;color:#888;font-size:14px;">No ratings</span>`;
                }
                let timeOptions = '';
                if (s.available_times && s.available_times.length > 0) {
                    s.available_times.forEach(time => {
                        // Prevent duplicate Sinderella+time+date
                        let duplicate = false;
                        for (let j = 1; j <= blockCount; j++) {
                            if (j !== idx && bookingData[j].date === date && bookingData[j].sinderella == s.sind_id && bookingData[j].time == time) {
                                duplicate = true;
                                break;
                            }
                        }
                        timeOptions += `
                            <label>
                                <input type="radio" name="sinderella_time_${idx}" value="${s.sind_id}|${time}" 
                                    ${bookingData[idx].sinderella == s.sind_id && bookingData[idx].time == time ? 'checked' : ''}
                                    ${duplicate ? 'disabled' : ''}>
                                ${formatTime(time)}${duplicate ? ' <span style="color:red;font-size:12px;">(Already selected in another block)</span>' : ''}
                            </label>
                        `;
                    });
                }
                div.innerHTML = `
                    <img src="${s.sind_profile_full_path}" alt="${s.sind_name}">
                    <div>
                        <strong>${s.sind_name}</strong> ${ratingHtml}<br>
                        ${timeOptions || '<span style="color:red;">No valid time slots</span>'}
                    </div>
                `;
                sinderellaList.appendChild(div);
            });
            sinderellaList.querySelectorAll('input[type="radio"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    let [sindId, time] = this.value.split('|');
                    bookingData[idx].sinderella = sindId;
                    bookingData[idx].time = time;
                    document.getElementById('sinderella_' + idx).value = sindId;
                    document.getElementById('time_' + idx).value = time;
                    updateSummary(idx);
                    // After selection, reload all blocks with same date to update disables
                    for (let j = 1; j <= blockCount; j++) {
                        if (j !== idx && bookingData[j].date === bookingData[idx].date) {
                            loadSinderellas(j);
                        }
                    }
                });
            });
            if (typeof callback === 'function') {
                callback();
            }
        });
}

function loadAddons(idx, callback) {
    let serviceId = serviceSelect.value;
    let addonsContainer = document.getElementById('addons_' + idx);
    if (!serviceId) {
        addonsContainer.innerHTML = '';
        return;
    }
    fetch(`get_addons.php?service_id=${serviceId}`)
        .then(resp => resp.json())
        .then(data => {
            addonCache[idx] = data; // cache for summary
            addonsContainer.innerHTML = '';
            data.forEach(addon => {
                const checked = bookingData[idx].addons && bookingData[idx].addons.includes(addon.ao_id.toString()) ? 'checked' : '';
                const addonItem = document.createElement('div');
                addonItem.className = 'addon-item';
                addonItem.innerHTML = `
                    <input type="checkbox" name="addons_${idx}[]" value="${addon.ao_id}" ${checked}>
                    ${addon.ao_desc} (RM ${parseFloat(addon.ao_price_recurring).toFixed(2)})
                `;
                addonsContainer.appendChild(addonItem);
            });
            addonsContainer.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                cb.addEventListener('change', function() {
                    let arr = [];
                    addonsContainer.querySelectorAll('input[type="checkbox"]:checked').forEach(c => arr.push(c.value));
                    bookingData[idx].addons = arr;
                    document.getElementById('addons_input_' + idx).value = arr.join(',');
                    updateSummary(idx);
                });
            });
            if (typeof callback === 'function') {
                callback();
            }
        });
}

addressSelect.addEventListener('change', function() {
    for (let i = 1; i <= blockCount; i++) {
        loadSinderellas(i);
    }
});
serviceSelect.addEventListener('change', function() {
    for (let i = 1; i <= blockCount; i++) {
        loadAddons(i);
    }
});

function formatTime(time) {
    const [hours, minutes] = time.split(':');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    const formattedHours = hours % 12 || 12;
    return `${formattedHours}:${minutes} ${ampm}`;
}

// On submit, store all values in hidden fields for POST
document.getElementById('recurringBookingForm').addEventListener('submit', function(e) {
    let errors = [];
    for (let i = 1; i <= blockCount; i++) {
        let b = bookingData[i];
        let missing = [];
        if (!b.date) missing.push("date");
        if (!b.sinderella) missing.push("Sinderella");
        if (!b.time) missing.push("time");
        if (missing.length > 0) {
            errors.push(`${i}${['st','nd','rd','th'][i-1] || 'th'} Booking: Please select ${missing.join(', ')}`);
        }
        // Set hidden fields for addons
        document.getElementById('addons_input_' + i).value = (b.addons || []).join(',');
    }
    if (errors.length > 0) {
        e.preventDefault();
        alert("Please complete the following before confirming:\n\n" + errors.join('\n'));
        return false;
    }
});

function updateSummary(idx) {
    let b = bookingData[idx];
    let summary = '';
    // Date
    let dateStr = b.date ? b.date : '';
    // Sinderella name
    let sindName = '';
    if (b.sinderella && sinderellaCache[idx]) {
        let sind = sinderellaCache[idx].find(s => String(s.sind_id) === String(b.sinderella));
        if (sind) sindName = sind.sind_name;
        else if (b.sinderella) sindName = '[Unavailable]';
    }
    // Time
    let timeStr = b.time ? formatTime(b.time) : '';
    // Add-ons
    let addonNames = [];
    if (b.addons && addonCache[idx]) {
        b.addons.forEach(aid => {
            let addon = addonCache[idx].find(a => String(a.ao_id) === String(aid));
            if (addon) addonNames.push(addon.ao_desc);
            else if (aid) addonNames.push('[Unavailable]');
        });
    }
    if (dateStr || sindName || timeStr) {
        summary = '';
        if (dateStr) summary += `Date: ${dateStr}`;
        if (sindName) summary += (summary ? ', ' : '') + `Sinderella: ${sindName}`;
        if (timeStr) summary += (summary ? ', ' : '') + `Time: ${timeStr}`;
        if (addonNames.length > 0) summary += ', Add-ons: ' + addonNames.join(', ');
        if (!summary) summary = '(Not selected)';
    } else {
        summary = '(Not selected)';
    }
    document.getElementById('summaryStatus' + idx).innerHTML = summary;
    checkAllFilled();
}

// Add more booking button
document.getElementById('addMoreBtn').addEventListener('click', function() {
    addBookingBlock();
    // Expand the newly added block
    expandBlock(blockCount);
});

// Initial render
renderBookingBlocks();

// --- Rating popup (same as add_booking) ---
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('sind-rating')) {
        const sindId = e.target.getAttribute('data-sind-id');
        let popup = document.getElementById('sindRatingPopup');
        if (!popup) {
            popup = document.createElement('div');
            popup.id = 'sindRatingPopup';
            popup.style = 'display:none; position:fixed; top:10%; left:50%; transform:translateX(-50%); background:#fff; border:1px solid #ccc; box-shadow:0 2px 10px #0002; z-index:9999; padding:20px; max-width:90vw; max-height:80vh; overflow:auto;';
            popup.innerHTML = `
                <div id="sindRatingPopupContent"></div>
                <div style="text-align:center;">
                    <button onclick="document.getElementById('sindRatingPopup').style.display='none'">Close</button>
                </div>
            `;
            document.body.appendChild(popup);
        }
        const content = document.getElementById('sindRatingPopupContent');
        content.innerHTML = 'Loading...';
        popup.style.display = 'block';

        fetch(`get_public_ratings.php?sind_id=${sindId}`)
            .then(resp => resp.text())
            .then(html => {
                content.innerHTML = html;
            })
            .catch(() => {
                content.innerHTML = '<div style="color:red;">Failed to load ratings.</div>';
            });
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const addressSelect = document.getElementById('address');
    const custAddressIdInput = document.getElementById('cust_address_id');
    // Always set on page load
    if (addressSelect.selectedIndex >= 0) {
        custAddressIdInput.value = addressSelect.options[addressSelect.selectedIndex].getAttribute('data-address-id');
    }
    addressSelect.addEventListener('change', function() {
        custAddressIdInput.value = addressSelect.options[addressSelect.selectedIndex].getAttribute('data-address-id');
    });
});
</script>
</body>
</html>