-- ADMIN TABLE
DROP TABLE IF EXISTS admins;
CREATE TABLE IF NOT EXISTS admins (
	adm_id INT(11) PRIMARY KEY AUTO_INCREMENT,
	adm_name VARCHAR(255) NOT NULL,
	adm_role VARCHAR(100) NOT NULL,  -- Stores 'Junior Admin', 'Senior Admin'
	adm_phno VARCHAR(11) NOT NULL,
	adm_pwd VARCHAR(255) NOT NULL,
    adm_status VARCHAR(20) NOT NULL DEFAULT 'active',  -- Stores 'active', 'inactive'
	last_login_date DATETIME
);

ALTER TABLE admins
ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;

INSERT INTO admins
(adm_name, adm_role, adm_phno, adm_pwd) VALUES
('Admin One', 'Senior Admin', '0123456789', 'pwd123');