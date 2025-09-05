-------------------------------------------------------------------------------
------------------------------ VERIFICATION CODE ------------------------------
-------------------------------------------------------------------------------
-- VERIFICATION CODE TABLE
DROP TABLE IF EXISTS verification_codes;
CREATE TABLE IF NOT EXISTS verification_codes (
    code_id INT(11) PRIMARY KEY AUTO_INCREMENT,
    user_phno VARCHAR(11) NOT NULL,
    ver_code VARCHAR(6) NOT NULL,
    created_at DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    used BOOLEAN NOT NULL DEFAULT 0
);


-------------------------------------------------------------------------------
------------------------------ SERVICE + PRICING ------------------------------
-------------------------------------------------------------------------------

-- SERVICE PRICING TABLE
-- DROP TABLE IF EXISTS service_pricing;
-- CREATE TABLE IF NOT EXISTS service_pricing (
--     service_id INT(11) PRIMARY KEY AUTO_INCREMENT,
--     service_name VARCHAR(255) NOT NULL,
--     service_price DECIMAL(10, 2) NOT NULL,
--     service_duration DECIMAL(10, 2) NOT NULL, -- in hours
--     service_status VARCHAR(20) NOT NULL DEFAULT 'active' -- Stores 'active', 'inactive'
--     -- FOREIGN KEY (service_id) REFERENCES service_pricing(service_id)
-- );

-- INSERT INTO service_pricing (service_name, service_price, service_duration) VALUES
-- ('4 Hours Cleaning', 130.00, 4),
-- ('2 Hours Cleaning', 86.00, 2);

DROP TABLE IF EXISTS services;
CREATE TABLE IF NOT EXISTS services (
    service_id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(255) NOT NULL,
    service_duration DECIMAL(10,2) NOT NULL, -- in hours
    service_status VARCHAR(20) NOT NULL DEFAULT 'active'
);

DROP TABLE IF EXISTS pricings;
CREATE TABLE IF NOT EXISTS pricings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_id INT NOT NULL,
    service_type VARCHAR(20) NOT NULL, -- Stores 'a', 'r' (adhoc, recurring)
    total_price DECIMAL(10,2) NOT NULL,
    platform DECIMAL(10,2) NOT NULL,
    sinderella DECIMAL(10,2) NOT NULL,
    lvl1 DECIMAL(10,2) NOT NULL,
    lvl2 DECIMAL(10,2) NOT NULL,
    lvl3 DECIMAL(10,2) NOT NULL,
    lvl4 DECIMAL(10,2) NOT NULL,
    br_basic DECIMAL(10,2) NOT NULL,
    br_rate DECIMAL(10,2) NOT NULL,
    br_perf DECIMAL(10,2) NOT NULL,
    penalty24_total DECIMAL(10,2) NOT NULL,
    penalty24_platform DECIMAL(10,2) NOT NULL,
    penalty24_sind DECIMAL(10,2) NOT NULL,
    penalty24_lvl1 DECIMAL(10,2) NOT NULL,
    penalty24_lvl2 DECIMAL(10,2) NOT NULL,
    penalty24_lvl3 DECIMAL(10,2) NOT NULL,
    penalty24_lvl4 DECIMAL(10,2) NOT NULL,
    penalty24_br_basic DECIMAL(10,2) NOT NULL,
    penalty24_br_rate DECIMAL(10,2) NOT NULL,
    penalty24_br_perf DECIMAL(10,2) NOT NULL,
    penalty2_total DECIMAL(10,2) NOT NULL,
    penalty2_platform DECIMAL(10,2) NOT NULL,
    penalty2_sind DECIMAL(10,2) NOT NULL,
    penalty2_lvl1 DECIMAL(10,2) NOT NULL,
    penalty2_lvl2 DECIMAL(10,2) NOT NULL,
    penalty2_lvl3 DECIMAL(10,2) NOT NULL,
    penalty2_lvl4 DECIMAL(10,2) NOT NULL,
    penalty2_br_basic DECIMAL(10,2) NOT NULL,
    penalty2_br_rate DECIMAL(10,2) NOT NULL,
    penalty2_br_perf DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (service_id) REFERENCES services(service_id)
);

-- ADDON TABLE [[[[ENHANCEMENT]]]]
DROP TABLE IF EXISTS addon;
CREATE TABLE IF NOT EXISTS addon (
    ao_id INT(11) PRIMARY KEY AUTO_INCREMENT,
    service_id INT(11) NOT NULL,
    ao_desc VARCHAR(255) NOT NULL,
    ao_price DECIMAL(10, 2) NOT NULL,
    ao_platform DECIMAL(10, 2) NOT NULL,
    ao_sind DECIMAL(10, 2) NOT NULL,
    ao_duration DECIMAL(10, 2) NOT NULL, -- in hours
    ao_status VARCHAR(20) NOT NULL DEFAULT 'active', -- Stores 'active', 'inactive'
    -- FOREIGN KEY (service_id) REFERENCES service_pricing(service_id)
    FOREIGN KEY (service_id) REFERENCES services(service_id)
);

ALTER TABLE addon
ADD COLUMN ao_price_recurring DECIMAL(10,2) DEFAULT 0,
ADD COLUMN ao_platform_recurring DECIMAL(10,2) DEFAULT 0,
ADD COLUMN ao_sind_recurring DECIMAL(10,2) DEFAULT 0,
ADD COLUMN ao_price_resched24 DECIMAL(10,2) DEFAULT 0,
ADD COLUMN ao_platform_resched24 DECIMAL(10,2) DEFAULT 0,
ADD COLUMN ao_sind_resched24 DECIMAL(10,2) DEFAULT 0,
ADD COLUMN ao_price_resched2 DECIMAL(10,2) DEFAULT 0,
ADD COLUMN ao_platform_resched2 DECIMAL(10,2) DEFAULT 0,
ADD COLUMN ao_sind_resched2 DECIMAL(10,2) DEFAULT 0, 
ADD COLUMN ao_price_resched24_re DECIMAL(10,2) DEFAULT 0,
ADD COLUMN ao_platform_resched24_re DECIMAL(10,2) DEFAULT 0,
ADD COLUMN ao_sind_resched24_re DECIMAL(10,2) DEFAULT 0,
ADD COLUMN ao_price_resched2_re DECIMAL(10,2) DEFAULT 0,
ADD COLUMN ao_platform_resched2_re DECIMAL(10,2) DEFAULT 0,
ADD COLUMN ao_sind_resched2_re DECIMAL(10,2) DEFAULT 0;

-- INSERT INTO addon (service_id, ao_desc, ao_price, ao_platform, ao_sind, ao_duration) VALUES
-- (1, 'Extra 1 Hour', 32.50, 11.25, 21.25, 1), 
-- (1, 'Cleaning Tools', 40.00, 0.00, 40.00, 0), 
-- (2, 'Extra 1 Hour', 43.00, 9.00, 34.00, 1), 
-- (2, 'Cleaning Tools', 40.00, 0.00, 40.00, 0);

-- PRICING TABLE
-- DROP TABLE IF EXISTS pricing;
-- CREATE TABLE IF NOT EXISTS pricing (
--     pricing_id INT(11) PRIMARY KEY AUTO_INCREMENT,
--     service_id INT(11) NOT NULL,
--     pr_platform DECIMAL(10, 2) NOT NULL,
--     pr_sind DECIMAL(10, 2) NOT NULL,
--     pr_lvl1 DECIMAL(10, 2) NOT NULL,
--     pr_lvl2 DECIMAL(10, 2) NOT NULL,
--     pr_lvl3 DECIMAL(10, 2) NOT NULL,
--     pr_lvl4 DECIMAL(10, 2) NOT NULL,
--     pr_br_basic DECIMAL(10, 2) NOT NULL,
--     pr_br_rate DECIMAL(10, 2) NOT NULL,
--     pr_br_perf DECIMAL(10, 2) NOT NULL,
--     FOREIGN KEY (service_id) REFERENCES service_pricing(service_id)
-- );

-- INSERT INTO pricing (service_id, pr_platform, pr_sind, pr_lvl1, pr_lvl2, pr_lvl3, pr_lvl4, pr_br_basic, pr_br_rate, pr_br_perf) VALUES
-- (1, 33.00, 85.00, 7.00, 5.00, 0.00, 0.00, 42.50, 25.50, 17.00),
-- (2, 18.00, 68.00, 0.00, 0.00, 0.00, 0.00, 34.00, 20.40, 13.60);

-------------------------------------------------------------------------------
---------------------------------- BOOKINGS -----------------------------------
-------------------------------------------------------------------------------
-- BOOKING TABLE
DROP TABLE IF EXISTS bookings;
CREATE TABLE IF NOT EXISTS bookings (
    booking_id INT(11) PRIMARY KEY AUTO_INCREMENT,
    booking_type CHAR(1) NOT NULL DEFAULT 'a'
    cust_id INT(11) NOT NULL,
    sind_id INT(11) NOT NULL,
    booking_date DATE NOT NULL,
    booking_from_time TIME NOT NULL,
    booking_to_time TIME NOT NULL,
    service_id INT(11) NOT NULL,
    full_address VARCHAR(255),
    cust_address_id INT(11) NOT NULL,
    booked_at DATETIME NOT NULL,
    booking_status VARCHAR(20) NOT NULL,  -- Stores 'pending', 'confirm', 'done', 'rated', 'cancelled by admin', 'cancelled by customer'
    bp_total DECIMAL(10,2),
    bp_platform DECIMAL(10,2),
    bp_sind DECIMAL(10,2),
    bp_lvl1_sind_id DECIMAL(10,2),
    bp_lvl1_amount DECIMAL(10,2),
    bp_lvl2_sind_id DECIMAL(10,2),
    bp_lvl2_amount DECIMAL(10,2),
    bp_lvl3_sind_id DECIMAL(10,2),
    bp_lvl3_amount DECIMAL(10,2),
    bp_lvl4_sind_id DECIMAL(10,2),
    bp_lvl4_amount DECIMAL(10,2),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cust_id) REFERENCES customers(cust_id),
    FOREIGN KEY (sind_id) REFERENCES sinderellas(sind_id),
    -- FOREIGN KEY (service_id) REFERENCES service_pricing(service_id)
    FOREIGN KEY (service_id) REFERENCES services(service_id),
    FOREIGN KEY (cust_address_id) REFERENCES cust_address(cust_address_id);
);

-- BOOKING ADDON TABLE
DROP TABLE IF EXISTS booking_addons;
CREATE TABLE IF NOT EXISTS booking_addons (
    booking_addon_id INT(11) PRIMARY KEY AUTO_INCREMENT,
    booking_id INT(11) NOT NULL,
    ao_id INT(11) NOT NULL,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id),
    FOREIGN KEY (ao_id) REFERENCES addon(ao_id)
);

--BOOKING RECURRING TABLE
DROP TABLE IF EXISTS booking_recurring;
CREATE TABLE IF NOT EXISTS booking_recurring (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    recurring_id INT(11) NOT NULL,
    cust_id INT(11) NOT NULL,
    booking_id INT(11) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cust_id) REFERENCES customers(cust_id),
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id)
);

-- DROP TABLE IF EXISTS booking_recurring;
-- CREATE TABLE IF NOT EXISTS booking_recurring (
--     recurring_id INT(11) PRIMARY KEY AUTO_INCREMENT,
--     cust_id INT(11) NOT NULL,
--     booking_id1 INT(11) NOT NULL,
--     booking_id2 INT(11) NOT NULL,
--     booking_id3 INT(11) NOT NULL,
--     booking_id4 INT(11) NOT NULL,
--     created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
--     FOREIGN KEY (cust_id) REFERENCES customers(cust_id),
--     FOREIGN KEY (booking_id1) REFERENCES bookings(booking_id),
--     FOREIGN KEY (booking_id2) REFERENCES bookings(booking_id),
--     FOREIGN KEY (booking_id3) REFERENCES bookings(booking_id),
--     FOREIGN KEY (booking_id4) REFERENCES bookings(booking_id)
-- );

  -- BOOKING PAYMENTS TABLE
DROP TABLE IF EXISTS booking_payments;
CREATE TABLE IF NOT EXISTS booking_payments (
    payment_id INT(11) PRIMARY KEY AUTO_INCREMENT,
    booking_id INT(11) NOT NULL,
    bill_code VARCHAR(20) NOT NULL,
    payment_amount DECIMAL(10, 2) NOT NULL,
    payment_status VARCHAR(20) NOT NULL,  -- Stores 'paid', 'unpaid', 'refunded'
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id)
);

DROP TABLE IF EXISTS booking_cancellation;
CREATE TABLE booking_cancellation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    cancellation_reason VARCHAR(255) NOT NULL,
    cancelled_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

DROP TABLE IF EXISTS booking_checkinout;
CREATE TABLE IF NOT EXISTS booking_checkinout (
    checkinout_id INT(11) PRIMARY KEY AUTO_INCREMENT,
    booking_id INT(11) NOT NULL,
    checkin_photo_path VARCHAR(255),
    checkin_time DATETIME,
    checkout_photo_path VARCHAR(255),
    checkout_time DATETIME,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id)
);

-- CUSTOMER RATINGS TO SINDERELLA
DROP TABLE IF EXISTS booking_ratings;
CREATE TABLE IF NOT EXISTS booking_ratings (
    rating_id INT(11) PRIMARY KEY AUTO_INCREMENT,
    booking_id INT(11) NOT NULL,
    sind_id INT(11) NOT NULL,
    cust_id INT(11) NOT NULL,
    rate INT(1) NOT NULL CHECK (rate BETWEEN 1 AND 5),
    comment TEXT,
    rated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id),
    FOREIGN KEY (sind_id) REFERENCES sinderellas(sind_id),
    FOREIGN KEY (cust_id) REFERENCES customers(cust_id)
);
ALTER TABLE booking_ratings
ADD COLUMN public TINYINT(1) NOT NULL DEFAULT 0 AFTER comment;

-- SINDERELLA COMMENT TO CUSTOMERS
DROP TABLE IF EXISTS cust_ratings;
CREATE TABLE IF NOT EXISTS cust_ratings (
    rating_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    sind_id INT NOT NULL,
    cust_id INT NOT NULL,
    -- rate INT(1) NOT NULL CHECK (rate BETWEEN 1 AND 5),
    -- comment TEXT,
    cmt_ppl TEXT,
    cmt_hse TEXT,
    rated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id),
    FOREIGN KEY (sind_id) REFERENCES sinderellas(sind_id),
    FOREIGN KEY (cust_id) REFERENCES customers(cust_id)
);

-- BOOKING RATING LINKS (to store token)
DROP TABLE IF EXISTS booking_rating_links;
CREATE TABLE IF NOT EXISTS booking_rating_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) NOT NULL DEFAULT 0,
    UNIQUE KEY (token)
);

-------------------------------------------------------------------------------
----------------------------------- OTHERS ------------------------------------
-------------------------------------------------------------------------------
-- MASTER TABLE (NUMBER)
DROP TABLE IF EXISTS master_number;
CREATE TABLE IF NOT EXISTS master_number (
    master_id INT(11) PRIMARY KEY AUTO_INCREMENT,
    master_desc VARCHAR(255) NOT NULL,
    master_amount DECIMAL(10, 2) NOT NULL
);

INSERT INTO master_number (master_desc, master_amount) VALUES
('Sind Registration Fee', 80.00);







-- EVENT TO CLEAN UP SINDERELLA OLD SCEDULE ***TBC
CREATE EVENT cleanup_old_schedule
ON SCHEDULE EVERY 1 DAY
DO
  DELETE FROM sind_available_time
  WHERE available_date < CURDATE() - INTERVAL 2 MONTH;