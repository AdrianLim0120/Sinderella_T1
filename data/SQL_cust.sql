-- CUSTOMER TABLE [[[[ENHANCEMENT]]]]
DROP TABLE IF EXISTS customers;
CREATE TABLE IF NOT EXISTS customers (
	cust_id INT(11) PRIMARY KEY AUTO_INCREMENT,
	cust_name VARCHAR(255) NOT NULL,
	cust_phno VARCHAR(11) NOT NULL,
	cust_pwd VARCHAR(255) NOT NULL,
	cust_status VARCHAR(20) NOT NULL,  -- Stores 'active', 'inactive'
    cust_emer_name VARCHAR(255) NOT NULL,
    cust_emer_phno VARCHAR(11) NOT NULL,
	last_login_date DATETIME, 
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- INSERT INTO customers 
-- (cust_name, cust_phno, cust_pwd, cust_status) VALUES
-- ('Customer One', '0123456789', 'password', 'active');

-- CUSTOMER ADDRESS TABLE [[[[NEW FOR ENHANCEMENT]]]]
DROP TABLE IF EXISTS cust_addresses;
CREATE TABLE IF NOT EXISTS cust_addresses (
    cust_address_id INT(11) PRIMARY KEY AUTO_INCREMENT,
    cust_id INT(11) NOT NULL,
    cust_address TEXT NOT NULL,
    cust_postcode VARCHAR(5) NOT NULL,
    cust_area VARCHAR(100) NOT NULL,
    cust_state VARCHAR(100) NOT NULL,
    cust_housetype VARCHAR(100) NOT NULL DEFAULT 'house',
    cust_fm_num VARCHAR(20) NOT NULL, 
    cust_pet VARCHAR(255) NOT NULL DEFAULT 'N/A',
    FOREIGN KEY (cust_id) REFERENCES customers(cust_id)
);

-- INSERT INTO cust_addresses
-- (cust_id, cust_address, cust_postcode, cust_area, cust_state) VALUES
-- (1, '12, Jalan ABC, Taman XYZ', '43000', 'Kajang', 'Selangor'),
-- (1, '13, Jalan DEF, Taman UVW', '43000', 'Kajang', 'Selangor');


-- CUSTOMER LABEL TABLE
DROP TABLE IF EXISTS cust_label;
CREATE TABLE IF NOT EXISTS cust_label (
    clbl_id INT(11) PRIMARY KEY AUTO_INCREMENT,
    clbl_name VARCHAR(255) NOT NULL,
    clbl_color_code VARCHAR(100) NOT NULL,
    clbl_status VARCHAR(100) NOT NULL DEFAULT 'active' -- Stores 'active', 'inactive'
);

-- CUSTOMER ID+LABEL TABLE
DROP TABLE IF EXISTS cust_id_label;
CREATE TABLE IF NOT EXISTS cust_id_label (
    cust_id_label_id INT(11) PRIMARY KEY AUTO_INCREMENT,
    cust_id INT(11) NOT NULL,
    clbl_id INT(11) NOT NULL,
    FOREIGN KEY (cust_id) REFERENCES customers(cust_id),
    FOREIGN KEY (clbl_id) REFERENCES cust_label(clbl_id)
);