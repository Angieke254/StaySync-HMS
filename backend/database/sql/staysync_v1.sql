DROP DATABASE IF EXISTS staysync_hms;
CREATE DATABASE staysync_hms;
USE staysync_hms;

CREATE TABLE users (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  phone VARCHAR(30) NULL,
  role ENUM('admin','front_desk','housekeeping','manager','pos_staff') NOT NULL DEFAULT 'front_desk',
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  remember_token VARCHAR(100) NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE personal_access_tokens (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  tokenable_type VARCHAR(255) NOT NULL,
  tokenable_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  token VARCHAR(64) NOT NULL UNIQUE,
  abilities TEXT NULL,
  last_used_at TIMESTAMP NULL,
  expires_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX tokenable_index (tokenable_type, tokenable_id)
);

CREATE TABLE room_types (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL UNIQUE,
  slug VARCHAR(100) NOT NULL UNIQUE,
  base_rate DECIMAL(10,2) NOT NULL,
  max_occupancy INT NOT NULL,
  description TEXT NULL,
  amenities JSON NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE rooms (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  room_type_id BIGINT UNSIGNED NOT NULL,
  room_number VARCHAR(20) NOT NULL UNIQUE,
  floor INT NULL,
  status ENUM('available','occupied','maintenance','cleaning') NOT NULL DEFAULT 'available',
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT rooms_room_type_id_fk FOREIGN KEY (room_type_id) REFERENCES room_types(id)
);

CREATE TABLE guests (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NULL UNIQUE,
  phone VARCHAR(30) NULL,
  id_type VARCHAR(50) NULL,
  id_number VARCHAR(100) NULL,
  address TEXT NULL,
  city VARCHAR(100) NULL,
  country VARCHAR(100) NULL,
  notes TEXT NULL,
  loyalty_tier VARCHAR(50) NOT NULL DEFAULT 'standard',
  total_stays INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE bookings (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  booking_reference VARCHAR(50) NOT NULL UNIQUE,
  guest_id BIGINT UNSIGNED NOT NULL,
  room_id BIGINT UNSIGNED NOT NULL,
  room_type_id BIGINT UNSIGNED NOT NULL,
  check_in_date DATE NOT NULL,
  check_out_date DATE NOT NULL,
  actual_check_in DATETIME NULL,
  actual_check_out DATETIME NULL,
  num_adults INT NOT NULL DEFAULT 1,
  num_children INT NOT NULL DEFAULT 0,
  status ENUM('confirmed','checked_in','checked_out','cancelled','no_show') NOT NULL DEFAULT 'confirmed',
  source ENUM('walk_in','phone','website','ota') NOT NULL DEFAULT 'walk_in',
  subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
  tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  total_price DECIMAL(10,2) NOT NULL DEFAULT 0,
  special_requests TEXT NULL,
  cancellation_reason TEXT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT bookings_guest_id_fk FOREIGN KEY (guest_id) REFERENCES guests(id),
  CONSTRAINT bookings_room_id_fk FOREIGN KEY (room_id) REFERENCES rooms(id),
  CONSTRAINT bookings_room_type_id_fk FOREIGN KEY (room_type_id) REFERENCES room_types(id),
  INDEX bookings_room_dates_idx (room_id, check_in_date, check_out_date),
  INDEX bookings_status_idx (status),
  INDEX bookings_guest_idx (guest_id)
);

CREATE TABLE booking_addons (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  booking_id BIGINT UNSIGNED NOT NULL,
  description VARCHAR(255) NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  unit_price DECIMAL(10,2) NOT NULL,
  total_price DECIMAL(10,2) NOT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT booking_addons_booking_id_fk FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);

CREATE TABLE folio_charges (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  booking_id BIGINT UNSIGNED NOT NULL,
  charge_type ENUM('room','restaurant','spa','minibar','laundry','other') NOT NULL,
  description VARCHAR(255) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  posted_by BIGINT UNSIGNED NULL,
  charged_at DATETIME NOT NULL,
  voided_at DATETIME NULL,
  voided_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT folio_charges_booking_id_fk FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
  CONSTRAINT folio_charges_posted_by_fk FOREIGN KEY (posted_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT folio_charges_voided_by_fk FOREIGN KEY (voided_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE payments (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  booking_id BIGINT UNSIGNED NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  payment_method ENUM('cash','card','bank_transfer','online') NOT NULL,
  transaction_reference VARCHAR(150) NULL,
  status ENUM('pending','completed','refunded') NOT NULL DEFAULT 'completed',
  paid_at DATETIME NOT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT payments_booking_id_fk FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);

CREATE TABLE room_status_logs (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  room_id BIGINT UNSIGNED NOT NULL,
  previous_status VARCHAR(30) NOT NULL,
  new_status VARCHAR(30) NOT NULL,
  changed_by BIGINT UNSIGNED NULL,
  notes TEXT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT room_status_logs_room_id_fk FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
  CONSTRAINT room_status_logs_changed_by_fk FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE housekeeping_tasks (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  room_id BIGINT UNSIGNED NOT NULL,
  assigned_to BIGINT UNSIGNED NULL,
  priority ENUM('low','normal','urgent') NOT NULL DEFAULT 'normal',
  status ENUM('pending','in_progress','completed') NOT NULL DEFAULT 'pending',
  notes TEXT NULL,
  completed_at DATETIME NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT housekeeping_tasks_room_id_fk FOREIGN KEY (room_id) REFERENCES rooms(id),
  CONSTRAINT housekeeping_tasks_assigned_to_fk FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE rate_overrides (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  room_type_id BIGINT UNSIGNED NOT NULL,
  date DATE NOT NULL,
  override_rate DECIMAL(10,2) NOT NULL,
  reason VARCHAR(255) NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT rate_overrides_room_type_id_fk FOREIGN KEY (room_type_id) REFERENCES room_types(id) ON DELETE CASCADE,
  UNIQUE KEY rate_overrides_unique_day (room_type_id, date)
);

CREATE TABLE settings (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  `key` VARCHAR(100) NOT NULL UNIQUE,
  value TEXT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE activity_logs (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NULL,
  action VARCHAR(100) NOT NULL,
  model_type VARCHAR(150) NULL,
  model_id BIGINT UNSIGNED NULL,
  description TEXT NULL,
  ip_address VARCHAR(50) NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT activity_logs_user_id_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX activity_logs_action_idx (action),
  INDEX activity_logs_model_idx (model_type, model_id)
);

INSERT INTO users (name, email, password, role) VALUES
('System Admin','admin@staysync.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi','admin'),
('Hotel Manager','manager@staysync.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi','manager'),
('Front Desk','frontdesk@staysync.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi','front_desk'),
('Housekeeping','housekeeping@staysync.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi','housekeeping'),
('POS Staff','pos@staysync.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi','pos_staff');

INSERT INTO room_types (name, slug, base_rate, max_occupancy, description, amenities) VALUES
('Standard','standard',6500,2,'Comfortable room for short stays', JSON_ARRAY('wifi','desk','tv')),
('Deluxe','deluxe',9500,3,'Larger room with city views', JSON_ARRAY('wifi','desk','tv','minibar')),
('Suite','suite',15000,4,'Separate lounge and premium amenities', JSON_ARRAY('wifi','lounge','minibar','bathtub')),
('Presidential','presidential',30000,4,'Top-floor premium suite', JSON_ARRAY('wifi','lounge','butler','kitchenette'));

INSERT INTO rooms (room_type_id, room_number, floor, status) VALUES
(1,'101',1,'available'),(1,'102',1,'available'),(1,'103',1,'maintenance'),(1,'104',1,'available'),
(2,'201',2,'occupied'),(2,'202',2,'available'),(2,'203',2,'cleaning'),(2,'204',2,'available'),
(3,'301',3,'available'),(3,'302',3,'available'),(3,'303',3,'available'),(3,'304',3,'cleaning'),
(4,'401',4,'available'),(4,'402',4,'available');

INSERT INTO guests (first_name,last_name,email,phone,country,loyalty_tier,total_stays) VALUES
('John','Doe','john@example.com','0700000001','Kenya','gold',5),
('Mary','Wanjiku','mary@example.com','0700000002','Kenya','silver',3),
('Ahmed','Ali','ahmed@example.com','0700000003','Tanzania','standard',1),
('Grace','Achieng','grace@example.com','0700000004','Kenya','standard',0),
('Daniel','Otieno','daniel@example.com','0700000005','Uganda','standard',2);

INSERT INTO bookings (booking_reference,guest_id,room_id,room_type_id,check_in_date,check_out_date,actual_check_in,actual_check_out,status,source,subtotal,tax_amount,total_price) VALUES
('SS-20260510-001',1,1,1,'2026-05-10','2026-05-12','2026-05-10 14:20:00','2026-05-12 10:15:00','checked_out','website',13000,2080,15080),
('SS-20260518-001',2,5,2,'2026-05-18','2026-05-20','2026-05-18 13:30:00',NULL,'checked_in','walk_in',19000,3040,22040),
('SS-20260519-001',3,9,3,'2026-05-19','2026-05-22',NULL,NULL,'confirmed','phone',45000,7200,52200);

INSERT INTO booking_addons (booking_id, description, quantity, unit_price, total_price) VALUES
(2,'Airport pickup',1,2500,2500),
(3,'Extra bed',1,3000,3000);

INSERT INTO folio_charges (booking_id, charge_type, description, amount, posted_by, charged_at) VALUES
(1,'room','Room charge for 2026-05-10',6500,1,'2026-05-10 14:20:00'),
(1,'room','Room charge for 2026-05-11',6500,1,'2026-05-11 09:00:00'),
(2,'room','Room charge for 2026-05-18',9500,1,'2026-05-18 13:30:00'),
(2,'restaurant','Dinner',3500,5,'2026-05-18 20:10:00');

INSERT INTO payments (booking_id, amount, payment_method, transaction_reference, status, paid_at) VALUES
(1,15080,'card','CARD-10001','completed','2026-05-12 10:00:00'),
(2,10000,'cash','CASH-20001','completed','2026-05-18 13:35:00');

INSERT INTO housekeeping_tasks (room_id, assigned_to, priority, status, notes, completed_at) VALUES
(1,4,'normal','completed','Cleaned after checkout','2026-05-12 12:00:00'),
(7,4,'urgent','in_progress','Guest arriving soon',NULL),
(12,NULL,'normal','pending','Prepare room',NULL);

INSERT INTO settings (`key`, value) VALUES
('hotel_name','StaySync Hotel'),
('hotel_address','Nairobi, Kenya'),
('hotel_phone','+254700000000'),
('hotel_email','hello@staysync.test'),
('checkout_time','11:00'),
('tax_rate','0.16'),
('currency','KES');
