-- AgriSync Complete Database Seed Data Script (TASK-105 / Issue #80)
-- Comprehensive seed data for local demo, judging showcase, and end-to-end testing.
-- Default password for all demo accounts: password123
-- Password Hash (BCRYPT): $2y$12$vRXM.k435qJg/45DKAXVhe985nOTvCCnJJlRyndd2AO/FGPfgQGG2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
USE `agrisync`;

-- --------------------------------------------------------
-- 1. Seed Users (Demo Accounts)
-- --------------------------------------------------------
INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `phone`, `district`, `nic_number`, `business_reg_no`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Bandara Herath', 'farmer@agrisync.lk', '$2y$12$vRXM.k435qJg/45DKAXVhe985nOTvCCnJJlRyndd2AO/FGPfgQGG2', 'farmer', '0771234567', 'Nuwara Eliya', '851234567V', NULL, 1, NOW(), NOW()),
(2, 'Somasiri Silva', 'dambulla.farmer@agrisync.lk', '$2y$12$vRXM.k435qJg/45DKAXVhe985nOTvCCnJJlRyndd2AO/FGPfgQGG2', 'farmer', '0719876543', 'Matale', '199012345678', NULL, 1, NOW(), NOW()),
(3, 'Kavinda Perera', 'badulla.farmer@agrisync.lk', '$2y$12$vRXM.k435qJg/45DKAXVhe985nOTvCCnJJlRyndd2AO/FGPfgQGG2', 'farmer', '0765554321', 'Badulla', '923456789V', NULL, 1, NOW(), NOW()),
(4, 'Tharindu Jayasuriya', 'jaffna.farmer@agrisync.lk', '$2y$12$vRXM.k435qJg/45DKAXVhe985nOTvCCnJJlRyndd2AO/FGPfgQGG2', 'farmer', '0783332211', 'Jaffna', '198898765432', NULL, 1, NOW(), NOW()),
(5, 'Keells Supermarket Procurement', 'buyer@agrisync.lk', '$2y$12$vRXM.k435qJg/45DKAXVhe985nOTvCCnJJlRyndd2AO/FGPfgQGG2', 'business', '0112345678', 'Colombo', NULL, 'PV12345', 1, NOW(), NOW()),
(6, 'Cargills Food City Central Logistics', 'cargills@agrisync.lk', '$2y$12$vRXM.k435qJg/45DKAXVhe985nOTvCCnJJlRyndd2AO/FGPfgQGG2', 'business', '0119876543', 'Gampaha', NULL, 'PV67890', 1, NOW(), NOW()),
(7, 'Ceylon Agro Exports Ltd', 'export@agrisync.lk', '$2y$12$vRXM.k435qJg/45DKAXVhe985nOTvCCnJJlRyndd2AO/FGPfgQGG2', 'business', '0114445566', 'Colombo', NULL, 'PV99887', 1, NOW(), NOW()),
(8, 'AgriSync System Admin', 'admin@agrisync.lk', '$2y$12$vRXM.k435qJg/45DKAXVhe985nOTvCCnJJlRyndd2AO/FGPfgQGG2', 'admin', '0703534431', 'Colombo', '198511223344', 'ADM001', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE `password_hash` = VALUES(`password_hash`), `updated_at` = NOW();

-- --------------------------------------------------------
-- 2. Seed Farmer Profiles
-- --------------------------------------------------------
INSERT INTO `farmer_profiles` (`id`, `user_id`, `farm_name`, `farm_size_acres`, `primary_crops`, `farming_method`, `bank_account_no`, `bank_name`, `bank_branch`, `bio`, `created_at`, `updated_at`) VALUES
(1, 1, 'Highland Green Haven Farms', 4.50, 'Carrot, Potato, Leek, Cabbage', 'conventional', '8001234567', 'Bank of Ceylon', 'Nuwara Eliya', 'Experienced hill country vegetable producer supplying premium highland crops since 2012.', NOW(), NOW()),
(2, 2, 'Dambulla Agro Valley', 6.20, 'Tomato, Big Onion, Green Chilli, Brinjal', 'organic', '1009876543', 'People''s Bank', 'Dambulla', 'Dedicated to organic open-field vegetable cultivation and direct wholesale market supply.', NOW(), NOW()),
(3, 3, 'Ella Highland Organic Fields', 3.00, 'Cabbage, Capsicum, Bean', 'greenhouse', '7003456789', 'Commercial Bank', 'Badulla', 'High-altitude protected greenhouse agriculture focusing on zero pesticide cultivation.', NOW(), NOW()),
(4, 4, 'Northern Sun Agro Producers', 8.00, 'Red Onion, Chilli, Pumpkin', 'conventional', '6005556677', 'Hatton National Bank', 'Jaffna', 'Large-scale commercial red onion and pumpkin cultivator with automated drip irrigation.', NOW(), NOW())
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- --------------------------------------------------------
-- 3. Seed Harvest Listings
-- --------------------------------------------------------
INSERT INTO `harvest_listings` (`id`, `farmer_id`, `crop_type`, `quantity_kg`, `min_order_quantity`, `price_per_kg`, `harvest_date`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Carrot', 1500.00, 100.00, 210.00, DATE_ADD(CURRENT_DATE, INTERVAL 3 DAY), 'available', NOW(), NOW()),
(2, 1, 'Potato', 2500.00, 200.00, 180.00, DATE_ADD(CURRENT_DATE, INTERVAL 5 DAY), 'available', NOW(), NOW()),
(3, 2, 'Tomato', 800.00, 50.00, 240.00, DATE_ADD(CURRENT_DATE, INTERVAL 2 DAY), 'matched', NOW(), NOW()),
(4, 2, 'Leek', 1200.00, 100.00, 195.00, DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY), 'available', NOW(), NOW()),
(5, 1, 'Cabbage', 900.00, 50.00, 160.00, DATE_SUB(CURRENT_DATE, INTERVAL 2 DAY), 'sold', NOW(), NOW()),
(6, 3, 'Capsicum', 650.00, 50.00, 320.00, DATE_ADD(CURRENT_DATE, INTERVAL 4 DAY), 'available', NOW(), NOW()),
(7, 4, 'Red Onion', 3000.00, 500.00, 290.00, DATE_ADD(CURRENT_DATE, INTERVAL 6 DAY), 'available', NOW(), NOW())
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- --------------------------------------------------------
-- 4. Seed Order Requests (Commercial Buyer Pre-Orders)
-- --------------------------------------------------------
INSERT INTO `order_requests` (`id`, `business_id`, `crop_type`, `quantity_kg`, `max_price`, `min_delivery_qty`, `delivery_date`, `urgency`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 5, 'Carrot', 1000.00, 230.00, 0.00, DATE_ADD(CURRENT_DATE, INTERVAL 4 DAY), 'high', 'matching', 'Need fresh Nuwara Eliya carrots for retail store distribution.', NOW(), NOW()),
(2, 5, 'Tomato', 800.00, 250.00, 0.00, DATE_ADD(CURRENT_DATE, INTERVAL 3 DAY), 'medium', 'matched', 'Grade A tomatoes required for salad retail section.', NOW(), NOW()),
(3, 6, 'Leek', 600.00, 200.00, 0.00, DATE_ADD(CURRENT_DATE, INTERVAL 8 DAY), 'low', 'pending', 'Direct farm supply preferred for Colombo distribution centers.', NOW(), NOW()),
(4, 6, 'Cabbage', 900.00, 175.00, 0.00, CURRENT_DATE, 'high', 'fulfilled', 'Delivered to Gampaha centralized warehouse.', NOW(), NOW()),
(5, 7, 'Red Onion', 2500.00, 310.00, 0.00, DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY), 'high', 'matching', 'Export grade cured red onions for Maldives sea container shipping.', NOW(), NOW())
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- --------------------------------------------------------
-- 5. Seed Order Matches (AI Broker Autonomous Matches)
-- --------------------------------------------------------
INSERT INTO `order_matches` (`id`, `order_id`, `listing_id`, `farmer_id`, `business_id`, `matched_price`, `agent_reasoning`, `confidence_score`, `status`, `contract_agreed`, `otp_verified`, `otp_code`, `created_at`, `updated_at`) VALUES
(1, 2, 3, 2, 5, 240.00, 'Gemini AI Broker matched Keells Tomato pre-order #2 with Somasiri Silva harvest listing #3. Price of Rs. 240/kg is within buyer Rs. 250/kg max budget and provides a 22% margin above base production cost. Transit route via Dambulla-Kurunegala highway optimizes logistics.', 96, 'accepted', 1, 1, '849201', NOW(), NOW()),
(2, 4, 5, 1, 6, 160.00, 'Gemini AI Broker matched Cargills Cabbage order #4 with Bandara Herath listing #5. Order fulfilled, quality checked, and verified upon warehouse intake.', 98, 'completed', 1, 1, '571934', NOW(), NOW())
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- --------------------------------------------------------
-- 6. Seed Agent Audit Logs (Multi-Agent System)
-- --------------------------------------------------------
INSERT INTO `agent_logs` (`id`, `agent_type`, `order_id`, `action_step`, `log_data`, `created_at`, `updated_at`) VALUES
(1, 'demand_prediction', 1, 'Analyzed Western Province demand spike for carrots ahead of seasonal holiday weekend', '{"crop": "Carrot", "predicted_demand_kg": 4500, "confidence": 0.92, "macro_trend": "High Demand"}', NOW(), NOW()),
(2, 'broker', 2, 'Matched buyer order #2 with farmer #2 harvest yield #3', '{"match_score": 96, "fair_trade_validated": true, "farmer_margin_gain_pct": 22}', NOW(), NOW()),
(3, 'logistics', 2, 'Negotiated transport route from Dambulla to Colombo distribution hub', '{"distance_km": 148, "est_delivery_hours": 3.5, "co2_emission_saved_kg": 42.6}', NOW(), NOW()),
(4, 'pricing', 5, 'Calculated Fair Trade Floor Price index for Red Onion across Jaffna and Dambulla markets', '{"suggested_floor_rs": 280.00, "market_ceiling_rs": 340.00, "volatility_score": 0.14}', NOW(), NOW())
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- --------------------------------------------------------
-- 7. Seed System In-App Notifications
-- --------------------------------------------------------
INSERT INTO `notifications` (`id`, `user_id`, `message`, `link`, `is_read`, `created_at`, `updated_at`) VALUES
(1, 1, 'Welcome to AgriSync! List your harvest yield to start receiving buyer matches.', '/farmer/listings.php', 1, NOW(), NOW()),
(2, 5, 'Your pre-order for 800kg Tomatoes has been matched with a farmer in Dambulla!', '/business/matches.php', 0, NOW(), NOW()),
(3, 2, 'New buyer match proposal received for your Tomato harvest!', '/farmer/offers.php', 0, NOW(), NOW()),
(4, 8, 'System Health Check: 4 autonomous Gemini AI agent workers active and operational.', '/admin/agent_logs.php', 0, NOW(), NOW())
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- --------------------------------------------------------
-- 8. Seed Admin Audit Logs
-- --------------------------------------------------------
INSERT INTO `admin_audit_logs` (`id`, `admin_id`, `action`, `target_id`, `details`, `ip_address`, `created_at`) VALUES
(1, 8, 'deactivate_user', 4, 'Deactivated user #4 (Tharindu Jayasuriya) pending document verification.', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(2, 8, 'activate_user', 4, 'Re-activated user #4 (Tharindu Jayasuriya) following identity verification.', '127.0.0.1', NOW())
ON DUPLICATE KEY UPDATE `created_at` = NOW();

-- --------------------------------------------------------
-- 9. Seed Payments (PayHere Escrow Transactions)
-- --------------------------------------------------------
INSERT INTO `payments` (`id`, `order_match_id`, `payhere_payment_id`, `amount`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, '320025810012', 192000.00, 'paid', NOW(), NOW()),
(2, 2, '320025810013', 144000.00, 'escrow_released', NOW(), NOW())
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- --------------------------------------------------------
-- 10. Seed User Reviews (Reputation System)
-- --------------------------------------------------------
INSERT INTO `user_reviews` (`id`, `reviewer_id`, `reviewee_id`, `order_match_id`, `rating`, `comment`, `created_at`, `updated_at`) VALUES
(1, 6, 1, 2, 5, 'Bandara provided exceptional quality cabbage. Delivered exactly on time, and the quality grade was better than expected.', NOW(), NOW()),
(2, 5, 2, 1, 4, 'Good quality tomatoes, though a small percentage were slightly overripe. Fast communication.', NOW(), NOW())
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- --------------------------------------------------------
-- 11. Seed Demand Cache (AI Predictions)
-- --------------------------------------------------------
INSERT INTO `demand_cache` (`id`, `district`, `crop_type`, `prediction_json`, `created_at`) VALUES
(1, 'Nuwara Eliya', 'Carrot', '{"success":true,"forecast":{"predicted_demand_level":"High","confidence_score":92,"actionable_advice":"Strong demand predicted. Pre-list harvests 5 days early.","key_factors":["Holiday season approaching","Recent flood in competing districts"]}}', NOW()),
(2, 'Dambulla', 'Tomato', '{"success":true,"forecast":{"predicted_demand_level":"Medium","confidence_score":85,"actionable_advice":"Steady demand. Current spot prices are optimal.","key_factors":["Stable market supply","Average logistics availability"]}}', NOW())
ON DUPLICATE KEY UPDATE `created_at` = NOW();

COMMIT;
