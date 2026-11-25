
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `bookings`;
DROP TABLE IF EXISTS `parts`;
DROP TABLE IF EXISTS `brands`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------------
-- 1. Table structure for table `users`
-- --------------------------------------------------------------
CREATE TABLE `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(120) NOT NULL UNIQUE,
  `phone` VARCHAR(30) NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'customer') NOT NULL DEFAULT 'customer',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------
-- 2. Table structure for table `brands`
-- --------------------------------------------------------------
CREATE TABLE `brands` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(80) NOT NULL UNIQUE,
  `slug` VARCHAR(80) NOT NULL UNIQUE,
  `country` VARCHAR(60) DEFAULT 'Japan',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------
-- 3. Table structure for table `parts`
-- --------------------------------------------------------------
CREATE TABLE `parts` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `brand_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `sku` VARCHAR(50) NOT NULL UNIQUE,
  `description` TEXT NULL,
  `category` VARCHAR(60) NOT NULL DEFAULT 'Engine & Transmission',
  `stock_qty` INT NOT NULL DEFAULT 0,
  `part_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `install_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `image_path` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_parts_brand` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------
-- 4. Table structure for table `bookings`
-- --------------------------------------------------------------
CREATE TABLE `bookings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ticket_code` VARCHAR(50) NOT NULL UNIQUE,
  `customer_name` VARCHAR(100) NOT NULL,
  `vehicle_number` VARCHAR(50) NOT NULL,
  `phone` VARCHAR(30) NULL,
  `service_note` TEXT NULL,
  `preferred_date` DATE NULL,
  `status` ENUM('Pending', 'Diagnosing', 'In Progress', 'Ready for Pickup', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Pending',
  `mechanic_notes` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_booking_vehicle` (`vehicle_number`),
  INDEX `idx_booking_ticket` (`ticket_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------
-- 5. Table structure for table `orders`
-- --------------------------------------------------------------
CREATE TABLE `orders` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_code` VARCHAR(50) NOT NULL UNIQUE,
  `user_id` INT UNSIGNED NOT NULL,
  `customer_name` VARCHAR(100) NOT NULL,
  `customer_email` VARCHAR(120) NOT NULL,
  `customer_phone` VARCHAR(30) NOT NULL,
  `vehicle_number` VARCHAR(50) NULL,
  `delivery_type` ENUM('pickup_garage', 'courier_delivery') NOT NULL DEFAULT 'pickup_garage',
  `shipping_address` TEXT NULL,
  `total_parts_cost` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_install_cost` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `grand_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` ENUM('card_simulation', 'bank_transfer', 'pay_at_garage') NOT NULL DEFAULT 'pay_at_garage',
  `payment_status` ENUM('Pending', 'Paid', 'Failed') NOT NULL DEFAULT 'Pending',
  `order_status` ENUM('Pending', 'Paid', 'Packed', 'Ready', 'Installed', 'Delivered', 'Cancelled') NOT NULL DEFAULT 'Pending',
  `admin_notes` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------
-- 6. Table structure for table `order_items`
-- --------------------------------------------------------------
CREATE TABLE `order_items` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT UNSIGNED NOT NULL,
  `part_id` INT UNSIGNED NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `unit_part_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `include_install` TINYINT(1) NOT NULL DEFAULT 0,
  `unit_install_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `line_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_items_part` FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------
-- 7. Table structure for table `notifications`
-- --------------------------------------------------------------
CREATE TABLE `notifications` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NULL,
  `booking_id` INT UNSIGNED NULL,
  `vehicle_number` VARCHAR(50) NULL,
  `title` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_notify_user` (`user_id`),
  INDEX `idx_notify_vehicle` (`vehicle_number`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_notifications_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================
-- REALISTIC SEED DATA
-- Default Admin Password: admin123
-- Default Customer Password: customer123
-- ==============================================================

-- Seed Users
INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password_hash`, `role`, `created_at`) VALUES
(1, 'Workshop Chief Officer', 'admin@retrogarage.local', '+94771122334', '$2y$12$12lzCmD3a9rwILJd7H/iU.I5uKUja.pauHcO9vTHRPEDEnAVOlHxK', 'admin', NOW()),
(2, 'Kasun Perera', 'customer@demo.local', '+94771234567', '$2y$12$o2lRdajOFxFpBNYkq2gk4ej7ofjjJO4VbQYPL.fesdir5oxTVvt1S', 'customer', NOW());

-- Seed Brands
INSERT INTO `brands` (`id`, `name`, `slug`, `country`, `created_at`) VALUES
(1, 'Honda', 'honda', 'Japan', NOW()),
(2, 'Toyota', 'toyota', 'Japan', NOW()),
(3, 'Suzuki', 'suzuki', 'Japan', NOW()),
(4, 'Nissan', 'nissan', 'Japan', NOW()),
(5, 'Yamaha', 'yamaha', 'Japan', NOW());

-- Seed Auto Parts with authentic specs, stock & fees in LKR
INSERT INTO `parts` (`id`, `brand_id`, `name`, `sku`, `description`, `category`, `stock_qty`, `part_price`, `install_fee`, `image_path`, `created_at`) VALUES
(1, 1, 'Honda - Heavy Duty Exedy Clutch Kit', 'HND-CL-091', 'Stage-1 organic friction disc, reinforced pressure plate, and release bearing. High heat tolerance for Civic EK/EG & Integra.', 'Clutch & Transmission', 14, 18500.00, 4500.00, 'uploads/parts/clutch-kit.svg', NOW()),
(2, 1, 'Honda - High Flow VTEC Oil Filter', 'HND-OF-018', 'OEM micro-pore filter media with silicone anti-drainback valve. Ensures maximum oil pressure for B & K series VTEC engines.', 'Filters & Lubrication', 32, 2800.00, 1000.00, 'uploads/parts/oil-filter.svg', NOW()),
(3, 2, 'Toyota - Ceramic Front Brake Pad Set', 'TOY-BP-442', 'Low-dust, high-friction rotor-friendly ceramic compound. Superior initial bite under heavy downhill braking.', 'Brakes & Suspension', 18, 9200.00, 2200.00, 'uploads/parts/brake-pads.svg', NOW()),
(4, 2, 'Toyota - Denso Iridium Tough Spark Plugs (Set of 4)', 'TOY-SP-771', '0.4mm ultra-fine iridium center electrode. Enhances combustion efficiency and throttle response across all RPMs.', 'Ignition & Electrical', 25, 14000.00, 1500.00, 'uploads/parts/spark-plugs.svg', NOW()),
(5, 3, 'Suzuki - Alternator & AC Ribbed Drive Belt', 'SZK-BT-309', 'Heavy-duty EPDM synthetic rubber construction, heat and oil resistant. OEM fitment for Suzuki Swift, WagonR, and Alto.', 'Belts & Cooling', 8, 3400.00, 1200.00, 'uploads/parts/drive-belt.svg', NOW()),
(6, 3, 'Suzuki - Gas-Charged Front Shock Absorbers (Pair)', 'SZK-SA-512', 'Twin-tube nitrogen gas pressurized struts. Restores factory road holding and reduces body roll on uneven surfaces.', 'Brakes & Suspension', 6, 32000.00, 6000.00, 'uploads/parts/shock-absorber.svg', NOW()),
(7, 4, 'Nissan - High Pressure In-Line Fuel Filter', 'NIS-FF-105', 'Direct replacement 10-micron metallic fuel filter canister for Nissan RB20/25, SR20, and Sunny carbureted/EFI engines.', 'Fuel System', 12, 5600.00, 1800.00, 'uploads/parts/fuel-filter.svg', NOW()),
(8, 4, 'Nissan - Cast Aluminum Water Pump Assembly', 'NIS-WP-884', 'Precision machined cast impellers and heavy-duty sealed bearing for optimal coolant circulation at high speeds.', 'Belts & Cooling', 4, 16800.00, 5000.00, 'uploads/parts/water-pump.svg', NOW()),
(9, 5, 'Yamaha - Gold Series O-Ring Chain & Sprocket Kit', 'YMH-CS-990', 'Hardened steel sprockets with Japanese gold link sealed O-ring chain. Engineered for Yamaha FZ, MT-15, and R15.', 'Drivetrain & Chains', 9, 21500.00, 3500.00, 'uploads/parts/chain-sprocket.svg', NOW()),
(10, 5, 'Yamaha - Front Fork Oil & Dual Lip Dust Seal Kit', 'YMH-FO-204', 'High viscosity index fork oil paired with Japanese double-lip rubber dust and oil seals for responsive damping.', 'Brakes & Suspension', 15, 6800.00, 2800.00, 'uploads/parts/fork-seal.svg', NOW()),
(11, 1, 'Honda - Stainless Steel Braided Brake Line Kit', 'HND-BL-330', 'PTFE inner liner with woven stainless steel outer braid. Eliminates pedal squish and provides rock solid brake pedal feel.', 'Brakes & Suspension', 7, 11500.00, 3000.00, 'uploads/parts/brake-lines.svg', NOW()),
(12, 2, 'Toyota - Dual Core Aluminum Performance Radiator', 'TOY-RD-601', 'High-efficiency brazed aluminum core with polished end tanks. 35% more heat dissipation capacity over OEM stock.', 'Belts & Cooling', 3, 38500.00, 7500.00, 'uploads/parts/radiator.svg', NOW());

-- Seed Express Guest Bookings
INSERT INTO `bookings` (`id`, `ticket_code`, `customer_name`, `vehicle_number`, `phone`, `service_note`, `preferred_date`, `status`, `mechanic_notes`, `created_at`, `updated_at`) VALUES
(1, 'GRG-92-0104', 'Sunil Jayawardena', 'WP CAB-4412', '+94772345678', 'Brake shudder at 80km/h and clutch slippage on 2nd gear pull', '2026-11-25', 'In Progress', 'Inspected front rotors; resurfacing in progress. Clutch plate worn down to rivets, new Exedy clutch being fitted.', NOW() - INTERVAL 1 DAY, NOW()),
(2, 'GRG-92-0105', 'Dinesh Mendis', 'WP GA-8891', '+94719876543', 'Engine misfire under load, check alternator charging voltage', '2026-11-26', 'Diagnosing', 'Testing cylinder ignition coils with oscilloscope. Alternator charging verified steady at 14.2V.', NOW() - INTERVAL 5 HOUR, NOW()),
(3, 'GRG-92-0106', 'Nadeeka Fernando', 'SP BBD-1102', '+94785551234', 'Periodic 40,000km full engine service, oil flush and fluid check', '2026-11-24', 'Ready for Pickup', 'Full synthetic oil change completed with OEM filter. Brake pads inspected (70% life remaining). Road tested - vehicle ready.', NOW() - INTERVAL 2 DAY, NOW());

-- Seed Sample Order
INSERT INTO `orders` (`id`, `order_code`, `user_id`, `customer_name`, `customer_email`, `customer_phone`, `vehicle_number`, `delivery_type`, `shipping_address`, `total_parts_cost`, `total_install_cost`, `grand_total`, `payment_method`, `payment_status`, `order_status`, `admin_notes`, `created_at`) VALUES
(1, 'ORD-92-5001', 2, 'Kasun Perera', 'customer@demo.local', '+94771234567', 'WP CAB-4412', 'pickup_garage', 'Counter Pickup - Bay 3', 27700.00, 6700.00, 34400.00, 'pay_at_garage', 'Paid', 'Ready', 'Parts staged at Workshop Bay 3. Customer will arrive for bay install.', NOW() - INTERVAL 3 HOUR);

-- Seed Sample Order Items
INSERT INTO `order_items` (`id`, `order_id`, `part_id`, `quantity`, `unit_part_price`, `include_install`, `unit_install_fee`, `line_total`) VALUES
(1, 1, 1, 1, 18500.00, 1, 4500.00, 23000.00),
(2, 1, 3, 1, 9200.00, 1, 2200.00, 11400.00);

-- Seed Notifications
INSERT INTO `notifications` (`id`, `user_id`, `booking_id`, `vehicle_number`, `title`, `message`, `is_read`, `created_at`) VALUES
(1, NULL, 3, 'SP BBD-1102', 'Vehicle Ready for Pickup', 'Service Ticket GRG-92-0106 is complete! Your vehicle SP BBD-1102 is tested and ready at Garage Bay 1.', 0, NOW() - INTERVAL 2 HOUR),
(2, NULL, 1, 'WP CAB-4412', 'Job In Progress Update', 'Mechanic Note for Ticket GRG-92-0104: Replacement clutch kit is being aligned and bolted. Test drive scheduled for 4:00 PM.', 0, NOW() - INTERVAL 4 HOUR),
(3, 2, NULL, 'WP CAB-4412', 'Order Prepared for Installation', 'Order ORD-92-5001 (Clutch Kit + Brake Pads) is verified and reserved for your garage appointment.', 0, NOW() - INTERVAL 1 HOUR);
