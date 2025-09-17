<?php
require_once '../db_connect.php';

$cust_id = $_SESSION['cust_id'] ?? 0;
$block = isset($_GET['block']) ? intval($_GET['block']) : 1;

// Fetch customer addresses
$stmt = $conn->prepare("SELECT * FROM cust_addresses WHERE cust_id = ?");
$stmt->bind_param("i", $cust_id);
$stmt->execute();
$addresses_result = $stmt->get_result();
$stmt->close();

// Fetch active services
$services_query = "
    SELECT 
        s.service_id, 
        s.service_name, 
        s.service_duration, 
        p.total_price AS service_price
    FROM services s
    LEFT JOIN pricings p ON s.service_id = p.service_id AND p.service_type = 'a'
    WHERE s.service_status = 'active'
";
$services_result = $conn->query($services_query);
?>
<div class="booking-form-block">
    <label for="address_<?php echo $block; ?>"><strong>Select Address: </strong></label>
    <select id="address_<?php echo $block; ?>" name="address_<?php echo $block; ?>" required>
        <option value="">-- Select Address --</option>
        <?php while ($addr = $addresses_result->fetch_assoc()): ?>
            <option 
                value="<?php echo htmlspecialchars("{$addr['cust_address']}, {$addr['cust_postcode']}, {$addr['cust_area']}, {$addr['cust_state']}"); ?>" 
                data-area="<?php echo $addr['cust_area']; ?>" 
                data-state="<?php echo $addr['cust_state']; ?>"
                data-address-id="<?php echo $addr['cust_address_id']; ?>">
                <?php echo "{$addr['cust_postcode']}, {$addr['cust_area']}, {$addr['cust_state']}"; ?> - <?php echo $addr['cust_address']; ?>
            </option>
        <?php endwhile; ?>
    </select>
    <span id="addressSizer_<?php echo $block; ?>" style="visibility:hidden;position:absolute;white-space:pre;font-family:inherit;font-size:inherit;"></span>
    <input type="hidden" id="cust_address_id_<?php echo $block; ?>" name="cust_address_id_<?php echo $block; ?>" value="">

    <label for="booking_date_<?php echo $block; ?>"><strong>Select Date: </strong></label>
    <input type="date" id="booking_date_<?php echo $block; ?>" name="booking_date_<?php echo $block; ?>" required>

    <label for="service_<?php echo $block; ?>"><strong>Select Service: </strong></label>
    <select id="service_<?php echo $block; ?>" name="service_<?php echo $block; ?>" required>
        <?php while ($service = $services_result->fetch_assoc()): ?>
            <option value="<?php echo $service['service_id']; ?>" data-duration="<?php echo $service['service_duration']; ?>" data-price="<?php echo $service['service_price']; ?>">
                <?php echo htmlspecialchars($service['service_name']); ?> (RM <?php echo number_format($service['service_price'], 2); ?>)
            </option>
        <?php endwhile; ?>
    </select>

    <div class="addon-container">
        <h3>Add-ons</h3>
        <div id="addons_<?php echo $block; ?>">
            <!-- Add-ons will be loaded by JS -->
        </div>
    </div>

    <div class="sinderella-container">
        <h3 id="sinderellaTitle_<?php echo $block; ?>" style="color:red;">Please select address and date.</h3>
        <div id="sinderellaList_<?php echo $block; ?>">
            <!-- Sinderellas will be loaded by JS -->
        </div>
    </div>

    <input type="hidden" name="start_time_<?php echo $block; ?>" id="start_time_<?php echo $block; ?>">
    <input type="hidden" name="full_address_<?php echo $block; ?>" id="full_address_<?php echo $block; ?>">
</div>