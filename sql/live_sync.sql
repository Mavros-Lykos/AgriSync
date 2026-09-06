-- MySQL dump 10.13  Distrib 8.0.43, for Win64 (x86_64)
--
-- Host: localhost    Database: agrisync
-- ------------------------------------------------------
-- Server version	8.0.43

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admin_audit_logs`
--

DROP TABLE IF EXISTS `admin_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_audit_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `admin_id` int NOT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_id` int DEFAULT NULL,
  `details` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `admin_id_index` (`admin_id`),
  KEY `action_index` (`action`),
  KEY `created_at_index` (`created_at`),
  CONSTRAINT `fk_audit_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_audit_logs`
--

LOCK TABLES `admin_audit_logs` WRITE;
/*!40000 ALTER TABLE `admin_audit_logs` DISABLE KEYS */;
INSERT INTO `admin_audit_logs` VALUES (1,8,'deactivate_user',4,'Deactivated user #4 (Tharindu Jayasuriya) pending document verification.','127.0.0.1','2026-09-04 05:31:06'),(2,8,'activate_user',4,'Re-activated user #4 (Tharindu Jayasuriya) following identity verification.','127.0.0.1','2026-09-05 05:31:06');
/*!40000 ALTER TABLE `admin_audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `agent_logs`
--

DROP TABLE IF EXISTS `agent_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agent_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `agent_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `order_id` int DEFAULT NULL,
  `action_step` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `log_data` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `agent_type_index` (`agent_type`),
  KEY `order_id_index` (`order_id`),
  CONSTRAINT `fk_log_order` FOREIGN KEY (`order_id`) REFERENCES `order_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agent_logs`
--

LOCK TABLES `agent_logs` WRITE;
/*!40000 ALTER TABLE `agent_logs` DISABLE KEYS */;
INSERT INTO `agent_logs` VALUES (1,'demand_prediction',1,'Analyzed Western Province demand spike for carrots ahead of seasonal holiday weekend','{\"crop\": \"Carrot\", \"confidence\": 0.92, \"macro_trend\": \"High Demand\", \"predicted_demand_kg\": 4500}','2026-09-05 05:31:06','2026-09-05 05:31:06'),(2,'broker',2,'Matched buyer order #2 with farmer #2 harvest yield #3','{\"match_score\": 96, \"fair_trade_validated\": true, \"farmer_margin_gain_pct\": 22}','2026-09-05 05:31:06','2026-09-05 05:31:06'),(3,'logistics',2,'Negotiated transport route from Dambulla to Colombo distribution hub','{\"distance_km\": 148, \"est_delivery_hours\": 3.5, \"co2_emission_saved_kg\": 42.6}','2026-09-05 05:31:06','2026-09-05 05:31:06'),(4,'pricing',5,'Calculated Fair Trade Floor Price index for Red Onion across Jaffna and Dambulla markets','{\"volatility_score\": 0.14, \"market_ceiling_rs\": 340.0, \"suggested_floor_rs\": 280.0}','2026-09-05 05:31:06','2026-09-05 05:31:06'),(5,'demand_predictor',NULL,'1. Ingested Real Demand Query','{\"season\": \"Maha Season\", \"target_crop\": \"Tomato\", \"current_month\": \"September\", \"target_district\": \"Nuwara Eliya\", \"real_30_day_demand\": {\"total_orders\": 1, \"total_volume_kg\": 800, \"avg_max_price_lkr\": 250}, \"real_30_day_supply\": {\"total_supply_kg\": 0, \"avg_listing_price_lkr\": 0, \"total_active_listings\": 0}}','2026-09-05 06:14:26','2026-09-05 06:14:26'),(6,'demand_predictor',NULL,'2. Generated & Cached Demand Advisory','{\"crop\": \"Tomato\", \"district\": \"Nuwara Eliya\", \"confidence\": 86, \"used_gemini\": false, \"demand_level\": \"High\", \"execution_time_ms\": 1462}','2026-09-05 06:14:27','2026-09-05 06:14:27'),(7,'demand_predictor',NULL,'1. Ingested Real Demand Query','{\"season\": \"Maha Season\", \"target_crop\": \"Cabbage\", \"current_month\": \"September\", \"target_district\": \"Nuwara Eliya\", \"real_30_day_demand\": {\"total_orders\": 1, \"total_volume_kg\": 900, \"avg_max_price_lkr\": 175}, \"real_30_day_supply\": {\"total_supply_kg\": 0, \"avg_listing_price_lkr\": 0, \"total_active_listings\": 0}}','2026-09-05 06:15:35','2026-09-05 06:15:35'),(8,'demand_predictor',NULL,'2. Generated & Cached Demand Advisory','{\"crop\": \"Cabbage\", \"district\": \"Nuwara Eliya\", \"confidence\": 86, \"used_gemini\": false, \"demand_level\": \"Medium\", \"execution_time_ms\": 937}','2026-09-05 06:15:36','2026-09-05 06:15:36'),(9,'broker',1,'1. Order Ingested','{\"district\": \"Colombo\", \"crop_type\": \"Carrot\", \"max_price\": 230, \"quantity_kg\": 1000, \"business_name\": \"Keells Supermarket Procurement\", \"delivery_date\": \"2026-09-09\"}','2026-09-05 06:28:23','2026-09-05 06:28:23'),(10,'broker',1,'2. Database Candidate Search','{\"crop_queried\": \"Carrot\", \"candidate_ids\": [1], \"candidates_found_count\": 1}','2026-09-05 06:28:23','2026-09-05 06:28:23'),(11,'broker',1,'3. Proximity & Fair-Trade Evaluation','{\"evaluated_candidates\": [{\"farmer_id\": 1, \"listing_id\": 1, \"farmer_name\": \"Bandara Herath\", \"quantity_kg\": 1500, \"available_kg\": 1500, \"harvest_date\": \"2026-09-08\", \"composite_score\": 0.71, \"farmer_district\": \"Nuwara Eliya\", \"freshness_score\": 1, \"proximity_score\": 0.75, \"listing_price_per_kg\": 210}]}','2026-09-05 06:28:23','2026-09-05 06:28:23'),(12,'broker',1,'4. AI Reasoning & Decision','{\"used_ai\": false, \"reasoning\": \"AI Broker selected Farmer Bandara Herath located in connected economic corridor (Nuwara Eliya) with 1500kg available unreserved stock. The matched price of Rs. 210.00/kg preserves fair-trade margins while fulfilling the buyer\'s requested delivery timeline with minimal food miles (SDG 8 & 12).\", \"confidence_score\": 90, \"execution_time_ms\": 1077, \"recommended_price\": 210, \"selected_listing_id\": 1}','2026-09-05 06:28:24','2026-09-05 06:28:24'),(13,'broker',1,'5. Matches Finalized & Reservations Committed','{\"business_id\": 5, \"matches_count\": 1, \"total_matched_quantity\": 1000}','2026-09-05 06:28:24','2026-09-05 06:28:24'),(14,'demand_predictor',NULL,'0. Served from Cache','{\"crop\": \"Tomato\", \"district\": \"Dambulla\", \"execution_time_ms\": 1}','2026-09-05 06:30:54','2026-09-05 06:30:54'),(15,'demand_predictor',NULL,'0. Served from Cache','{\"crop\": \"Carrot\", \"district\": \"Nuwara Eliya\", \"execution_time_ms\": 1}','2026-09-05 06:30:54','2026-09-05 06:30:54'),(16,'demand_predictor',NULL,'1. Ingested Real Demand Query','{\"season\": \"Maha Season\", \"target_crop\": \"Big Onion\", \"current_month\": \"September\", \"target_district\": \"Matale\", \"real_30_day_demand\": {\"total_orders\": 0, \"total_volume_kg\": 0, \"avg_max_price_lkr\": 0}, \"real_30_day_supply\": {\"total_supply_kg\": 100, \"avg_listing_price_lkr\": 190, \"total_active_listings\": 1}}','2026-09-05 06:30:54','2026-09-05 06:30:54'),(17,'demand_predictor',NULL,'2. Generated & Cached Demand Advisory','{\"crop\": \"Big Onion\", \"district\": \"Matale\", \"confidence\": 86, \"used_gemini\": false, \"demand_level\": \"High\", \"execution_time_ms\": 1011}','2026-09-05 06:30:55','2026-09-05 06:30:55'),(18,'broker',1,'1. Order Ingested','{\"district\": \"Colombo\", \"crop_type\": \"Carrot\", \"max_price\": 230, \"quantity_kg\": 1000, \"business_name\": \"Keells Supermarket Procurement\", \"delivery_date\": \"2026-09-09\"}','2026-09-05 06:30:55','2026-09-05 06:30:55'),(19,'broker',1,'2. Database Candidate Search','{\"crop_queried\": \"Carrot\", \"candidate_ids\": [1], \"candidates_found_count\": 1}','2026-09-05 06:30:55','2026-09-05 06:30:55'),(20,'broker',1,'3. Proximity & Fair-Trade Evaluation','{\"evaluated_candidates\": [{\"farmer_id\": 1, \"listing_id\": 1, \"farmer_name\": \"Bandara Herath\", \"quantity_kg\": 1500, \"available_kg\": 500, \"harvest_date\": \"2026-09-08\", \"composite_score\": 0.61, \"farmer_district\": \"Nuwara Eliya\", \"freshness_score\": 1, \"proximity_score\": 0.75, \"listing_price_per_kg\": 210}]}','2026-09-05 06:30:55','2026-09-05 06:30:55'),(21,'broker',1,'4. AI Reasoning & Decision','{\"used_ai\": false, \"reasoning\": \"AI Broker selected Farmer Bandara Herath located in connected economic corridor (Nuwara Eliya) with 500kg available unreserved stock. The matched price of Rs. 210.00/kg preserves fair-trade margins while fulfilling the buyer\'s requested delivery timeline with minimal food miles (SDG 8 & 12).\", \"confidence_score\": 90, \"execution_time_ms\": 854, \"recommended_price\": 210, \"selected_listing_id\": 1}','2026-09-05 06:30:56','2026-09-05 06:30:56'),(22,'broker',1,'5. Matches Finalized & Reservations Committed','{\"business_id\": 5, \"matches_count\": 1, \"total_matched_quantity\": 500}','2026-09-05 06:30:56','2026-09-05 06:30:56'),(23,'demand_predictor',NULL,'0. Served from Cache','{\"crop\": \"Tomato\", \"district\": \"Dambulla\", \"execution_time_ms\": 2}','2026-09-05 06:31:42','2026-09-05 06:31:42'),(24,'demand_predictor',NULL,'0. Served from Cache','{\"crop\": \"Carrot\", \"district\": \"Nuwara Eliya\", \"execution_time_ms\": 1}','2026-09-05 06:31:42','2026-09-05 06:31:42'),(25,'demand_predictor',NULL,'0. Served from Cache','{\"crop\": \"Big Onion\", \"district\": \"Matale\", \"execution_time_ms\": 1}','2026-09-05 06:31:42','2026-09-05 06:31:42'),(26,'broker',1,'1. Order Ingested','{\"district\": \"Colombo\", \"crop_type\": \"Carrot\", \"max_price\": 230, \"quantity_kg\": 1000, \"business_name\": \"Keells Supermarket Procurement\", \"delivery_date\": \"2026-09-09\"}','2026-09-05 06:31:42','2026-09-05 06:31:42'),(27,'broker',1,'2. Database Candidate Search','{\"crop_queried\": \"Carrot\", \"candidate_ids\": [], \"candidates_found_count\": 0}','2026-09-05 06:31:42','2026-09-05 06:31:42'),(28,'broker',1,'2b. No Matching Listings Available','{\"message\": \"No active harvest listings found matching crop criteria\"}','2026-09-05 06:31:42','2026-09-05 06:31:42'),(29,'demand_predictor',NULL,'0. Served from Cache','{\"crop\": \"Tomato\", \"district\": \"Dambulla\", \"execution_time_ms\": 1}','2026-09-05 06:32:01','2026-09-05 06:32:01'),(30,'demand_predictor',NULL,'0. Served from Cache','{\"crop\": \"Carrot\", \"district\": \"Nuwara Eliya\", \"execution_time_ms\": 1}','2026-09-05 06:32:01','2026-09-05 06:32:01'),(31,'demand_predictor',NULL,'0. Served from Cache','{\"crop\": \"Big Onion\", \"district\": \"Matale\", \"execution_time_ms\": 1}','2026-09-05 06:32:01','2026-09-05 06:32:01'),(32,'broker',1,'1. Order Ingested','{\"district\": \"Colombo\", \"crop_type\": \"Carrot\", \"max_price\": 230, \"quantity_kg\": 1000, \"business_name\": \"Keells Supermarket Procurement\", \"delivery_date\": \"2026-09-09\"}','2026-09-05 06:32:01','2026-09-05 06:32:01'),(33,'broker',1,'2. Database Candidate Search','{\"crop_queried\": \"Carrot\", \"candidate_ids\": [], \"candidates_found_count\": 0}','2026-09-05 06:32:01','2026-09-05 06:32:01'),(34,'broker',1,'2b. No Matching Listings Available','{\"message\": \"No active harvest listings found matching crop criteria\"}','2026-09-05 06:32:01','2026-09-05 06:32:01'),(35,'demand_predictor',NULL,'0. Served from Cache','{\"crop\": \"Tomato\", \"district\": \"Dambulla\", \"execution_time_ms\": 2}','2026-09-05 06:32:33','2026-09-05 06:32:33'),(36,'demand_predictor',NULL,'0. Served from Cache','{\"crop\": \"Carrot\", \"district\": \"Nuwara Eliya\", \"execution_time_ms\": 1}','2026-09-05 06:32:33','2026-09-05 06:32:33'),(37,'demand_predictor',NULL,'0. Served from Cache','{\"crop\": \"Big Onion\", \"district\": \"Matale\", \"execution_time_ms\": 1}','2026-09-05 06:32:33','2026-09-05 06:32:33'),(38,'broker',1,'1. Order Ingested','{\"district\": \"Colombo\", \"crop_type\": \"Carrot\", \"max_price\": 230, \"quantity_kg\": 1000, \"business_name\": \"Keells Supermarket Procurement\", \"delivery_date\": \"2026-09-09\"}','2026-09-05 06:32:33','2026-09-05 06:32:33'),(39,'broker',1,'2. Database Candidate Search','{\"crop_queried\": \"Carrot\", \"candidate_ids\": [1], \"candidates_found_count\": 1}','2026-09-05 06:32:33','2026-09-05 06:32:33'),(40,'broker',1,'3. Proximity & Fair-Trade Evaluation','{\"evaluated_candidates\": [{\"farmer_id\": 1, \"listing_id\": 1, \"farmer_name\": \"Bandara Herath\", \"quantity_kg\": 1500, \"available_kg\": 1500, \"harvest_date\": \"2026-09-08\", \"composite_score\": 0.71, \"farmer_district\": \"Nuwara Eliya\", \"freshness_score\": 1, \"proximity_score\": 0.75, \"listing_price_per_kg\": 210}]}','2026-09-05 06:32:33','2026-09-05 06:32:33'),(41,'broker',1,'4. AI Reasoning & Decision','{\"used_ai\": false, \"reasoning\": \"AI Broker selected Farmer Bandara Herath located in connected economic corridor (Nuwara Eliya) with 1500kg available unreserved stock. The matched price of Rs. 210.00/kg preserves fair-trade margins while fulfilling the buyer\'s requested delivery timeline with minimal food miles (SDG 8 & 12).\", \"confidence_score\": 90, \"execution_time_ms\": 3835, \"recommended_price\": 210, \"selected_listing_id\": 1}','2026-09-05 06:32:37','2026-09-05 06:32:37'),(42,'broker',1,'5. Matches Finalized & Reservations Committed','{\"business_id\": 5, \"matches_count\": 1, \"total_matched_quantity\": 1000}','2026-09-05 06:32:37','2026-09-05 06:32:37'),(43,'broker',8,'1. Order Ingested','{\"district\": \"Colombo\", \"crop_type\": \"Papaya\", \"max_price\": 90, \"quantity_kg\": 250, \"business_name\": \"Keells Supermarket Procurement\", \"delivery_date\": \"2026-09-08\"}','2026-09-05 06:35:04','2026-09-05 06:35:04'),(44,'broker',8,'2. Database Candidate Search','{\"crop_queried\": \"Papaya\", \"candidate_ids\": [], \"candidates_found_count\": 0}','2026-09-05 06:35:04','2026-09-05 06:35:04'),(45,'broker',8,'2b. No Matching Listings Available','{\"message\": \"No active harvest listings found matching crop criteria\"}','2026-09-05 06:35:04','2026-09-05 06:35:04');
/*!40000 ALTER TABLE `agent_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `demand_cache`
--

DROP TABLE IF EXISTS `demand_cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `demand_cache` (
  `id` int NOT NULL AUTO_INCREMENT,
  `district` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `crop_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prediction_json` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `crop_district_created_idx` (`crop_type`,`district`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `demand_cache`
--

LOCK TABLES `demand_cache` WRITE;
/*!40000 ALTER TABLE `demand_cache` DISABLE KEYS */;
INSERT INTO `demand_cache` VALUES (1,'Nuwara Eliya','Carrot','{\"success\":true,\"forecast\":{\"predicted_demand_level\":\"High\",\"confidence_score\":92,\"actionable_advice\":\"Strong demand predicted. Pre-list harvests 5 days early.\",\"key_factors\":[\"Holiday season approaching\",\"Recent flood in competing districts\"]}}','2026-09-05 05:31:06'),(2,'Dambulla','Tomato','{\"success\":true,\"forecast\":{\"predicted_demand_level\":\"Medium\",\"confidence_score\":85,\"actionable_advice\":\"Steady demand. Current spot prices are optimal.\",\"key_factors\":[\"Stable market supply\",\"Average logistics availability\"]}}','2026-09-05 05:31:06'),(3,'Nuwara Eliya','Tomato','{\"predicted_demand_level\":\"High\",\"confidence_score\":86,\"market_trend\":\"Rising\",\"predicted_price_range\":{\"min\":180,\"max\":290,\"currency\":\"LKR\"},\"key_factors\":[\"Active Maha Season production schedule in Nuwara Eliya\",\"Elevated procurement demand from wholesale and supermarket retail chains\",\"Favorable harvest windows minimizing post-harvest spoilage\"],\"actionable_advice\":\"Strong commercial demand projected for Tomato. Farmers in Nuwara Eliya are advised to list harvests 7-10 days in advance on AgriSync to secure guaranteed pre-orders above regional spot prices.\",\"recommended_crops_next_cycle\":[\"Big Onion\",\"Bell Pepper\",\"Tomato\"],\"used_gemini\":false}','2026-09-05 06:14:27'),(4,'Nuwara Eliya','Cabbage','{\"predicted_demand_level\":\"Medium\",\"confidence_score\":86,\"market_trend\":\"Stable\",\"predicted_price_range\":{\"min\":120,\"max\":195,\"currency\":\"LKR\"},\"key_factors\":[\"Active Maha Season production schedule in Nuwara Eliya\",\"Elevated procurement demand from wholesale and supermarket retail chains\",\"Favorable harvest windows minimizing post-harvest spoilage\"],\"actionable_advice\":\"Strong commercial demand projected for Cabbage. Farmers in Nuwara Eliya are advised to list harvests 7-10 days in advance on AgriSync to secure guaranteed pre-orders above regional spot prices.\",\"recommended_crops_next_cycle\":[\"Big Onion\",\"Bell Pepper\",\"Tomato\"],\"used_gemini\":false}','2026-09-05 06:15:36'),(5,'Matale','Big Onion','{\"predicted_demand_level\":\"High\",\"confidence_score\":86,\"market_trend\":\"Rising\",\"predicted_price_range\":{\"min\":180,\"max\":290,\"currency\":\"LKR\"},\"key_factors\":[\"Active Maha Season production schedule in Matale\",\"Elevated procurement demand from wholesale and supermarket retail chains\",\"Favorable harvest windows minimizing post-harvest spoilage\"],\"actionable_advice\":\"Strong commercial demand projected for Big Onion. Farmers in Matale are advised to list harvests 7-10 days in advance on AgriSync to secure guaranteed pre-orders above regional spot prices.\",\"recommended_crops_next_cycle\":[\"Big Onion\",\"Bell Pepper\",\"Tomato\"],\"used_gemini\":false}','2026-09-05 06:30:55');
/*!40000 ALTER TABLE `demand_cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `farmer_profiles`
--

DROP TABLE IF EXISTS `farmer_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `farmer_profiles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `farm_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `farm_size_acres` decimal(6,2) DEFAULT NULL,
  `primary_crops` text COLLATE utf8mb4_unicode_ci,
  `farming_method` enum('organic','conventional','greenhouse','hydroponic') COLLATE utf8mb4_unicode_ci DEFAULT 'conventional',
  `bank_account_no` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_branch` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bio` text COLLATE utf8mb4_unicode_ci,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id_unique` (`user_id`),
  CONSTRAINT `fk_farmer_profile_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `farmer_profiles`
--

LOCK TABLES `farmer_profiles` WRITE;
/*!40000 ALTER TABLE `farmer_profiles` DISABLE KEYS */;
INSERT INTO `farmer_profiles` VALUES (1,1,'Highland Green Haven Farms',4.50,'Carrot, Potato, Leek, Cabbage','conventional','8001234567','Bank of Ceylon','Nuwara Eliya','Experienced hill country vegetable producer supplying premium highland crops since 2012.','','2026-09-05 05:31:06','2026-09-05 06:16:10'),(2,2,'Dambulla Agro Valley',6.20,'Tomato, Big Onion, Green Chilli, Brinjal','organic','1009876543','People\'s Bank','Dambulla','Dedicated to organic open-field vegetable cultivation and direct wholesale market supply.',NULL,'2026-09-05 05:31:06','2026-09-05 05:31:06'),(3,3,'Ella Highland Organic Fields',3.00,'Cabbage, Capsicum, Bean','greenhouse','7003456789','Commercial Bank','Badulla','High-altitude protected greenhouse agriculture focusing on zero pesticide cultivation.',NULL,'2026-09-05 05:31:06','2026-09-05 05:31:06'),(4,4,'Northern Sun Agro Producers',8.00,'Red Onion, Chilli, Pumpkin','conventional','6005556677','Hatton National Bank','Jaffna','Large-scale commercial red onion and pumpkin cultivator with automated drip irrigation.',NULL,'2026-09-05 05:31:06','2026-09-05 05:31:06');
/*!40000 ALTER TABLE `farmer_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `harvest_listings`
--

DROP TABLE IF EXISTS `harvest_listings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `harvest_listings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `farmer_id` int NOT NULL,
  `crop_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity_kg` decimal(10,2) NOT NULL,
  `min_order_quantity` decimal(10,2) NOT NULL DEFAULT '0.00',
  `quantity_reserved` decimal(10,2) NOT NULL DEFAULT '0.00',
  `price_per_kg` decimal(10,2) NOT NULL,
  `harvest_date` date NOT NULL,
  `quality_grade` enum('A','B','C') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'B',
  `certifications` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('available','matched','sold','expired') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `farmer_id_index` (`farmer_id`),
  KEY `crop_type_index` (`crop_type`),
  KEY `quality_grade_index` (`quality_grade`),
  KEY `status_index` (`status`),
  CONSTRAINT `fk_harvest_farmer` FOREIGN KEY (`farmer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `harvest_listings`
--

LOCK TABLES `harvest_listings` WRITE;
/*!40000 ALTER TABLE `harvest_listings` DISABLE KEYS */;
INSERT INTO `harvest_listings` VALUES (1,1,'Carrot',1500.00,100.00,1000.00,210.00,'2026-09-08','B',NULL,NULL,'matched','2026-09-05 05:31:06','2026-09-05 06:32:37'),(2,1,'Potato',2500.00,200.00,0.00,180.00,'2026-09-10','B',NULL,NULL,'available','2026-09-05 05:31:06','2026-09-05 05:31:06'),(3,2,'Tomato',800.00,50.00,0.00,240.00,'2026-09-07','B',NULL,NULL,'matched','2026-09-05 05:31:06','2026-09-05 05:31:06'),(4,2,'Leek',1200.00,100.00,0.00,195.00,'2026-09-12','B',NULL,NULL,'available','2026-09-05 05:31:06','2026-09-05 05:31:06'),(5,1,'Cabbage',900.00,50.00,0.00,160.00,'2026-09-03','B',NULL,NULL,'sold','2026-09-05 05:31:06','2026-09-05 05:31:06'),(6,3,'Capsicum',650.00,50.00,0.00,320.00,'2026-09-09','B',NULL,NULL,'available','2026-09-05 05:31:06','2026-09-05 05:31:06'),(7,4,'Red Onion',3000.00,500.00,0.00,290.00,'2026-09-11','B',NULL,NULL,'available','2026-09-05 05:31:06','2026-09-05 05:31:06'),(8,1,'Big Onion',100.00,100.00,0.00,190.00,'2026-09-05','B',NULL,NULL,'available','2026-09-05 06:15:21','2026-09-05 06:15:21'),(9,1,'Papaya',500.00,100.00,0.00,100.00,'2026-09-05','A',NULL,NULL,'available','2026-09-05 06:34:30','2026-09-05 06:34:30'),(10,1,'Carrot',100.00,10.00,0.00,50.00,'2023-12-01','A',NULL,NULL,'available','2026-09-06 09:31:21','2026-09-06 09:31:21'),(11,1,'Carrot',500.00,50.00,0.00,120.00,'2024-10-10','A','Organic',NULL,'available','2026-09-06 09:31:55','2026-09-06 09:31:55'),(12,1,'Tomato',100.00,10.00,0.00,150.00,'2024-10-15','A',NULL,NULL,'available','2026-09-06 09:41:32','2026-09-06 09:41:32'),(13,1,'Green Beans',333.00,111.00,0.00,111.00,'2026-09-06','B',NULL,NULL,'available','2026-09-06 09:43:40','2026-09-06 09:43:40');
/*!40000 ALTER TABLE `harvest_listings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id_index` (`user_id`),
  KEY `is_read_index` (`is_read`),
  CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,1,'Welcome to AgriSync! List your harvest yield to start receiving buyer matches.','/farmer/listings.php',1,'2026-09-05 05:31:06','2026-09-05 05:31:06'),(2,5,'Your pre-order for 800kg Tomatoes has been matched with a farmer in Dambulla!','/business/matches.php',1,'2026-09-05 05:31:06','2026-09-05 06:18:15'),(3,2,'New buyer match proposal received for your Tomato harvest!','/farmer/offers.php',0,'2026-09-05 05:31:06','2026-09-05 05:31:06'),(4,8,'System Health Check: 4 autonomous Gemini AI agent workers active and operational.','/admin/agent_logs.php',0,'2026-09-05 05:31:06','2026-09-05 05:31:06'),(5,1,'🌾 AI Broker matched 1000kg of your Carrot harvest with Keells Supermarket Procurement for Rs. 210.00/kg!','/farmer/offers.php',0,'2026-09-05 06:28:24','2026-09-05 06:28:24'),(6,5,'✨ AI Broker completed multi-farmer matching for your Carrot order (#ORD-1) totaling 1000kg across 1 producer listing(s)!','/business/orders.php',1,'2026-09-05 06:28:24','2026-09-05 06:52:26'),(7,1,'🌾 AI Broker matched 500kg of your Carrot harvest with Keells Supermarket Procurement for Rs. 210.00/kg!','/farmer/offers.php',0,'2026-09-05 06:30:56','2026-09-05 06:30:56'),(8,5,'✨ AI Broker completed multi-farmer matching for your Carrot order (#ORD-1) totaling 500kg across 1 producer listing(s)!','/business/orders.php',1,'2026-09-05 06:30:56','2026-09-05 06:52:26'),(9,1,'🌾 AI Broker matched 1000kg of your Carrot harvest with Keells Supermarket Procurement for Rs. 210.00/kg!','/farmer/offers.php',0,'2026-09-05 06:32:37','2026-09-05 06:32:37'),(10,5,'✨ AI Broker completed multi-farmer matching for your Carrot order (#ORD-1) totaling 1000kg across 1 producer listing(s)!','/business/orders.php',1,'2026-09-05 06:32:37','2026-09-05 06:52:26');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_matches`
--

DROP TABLE IF EXISTS `order_matches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_matches` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `listing_id` int NOT NULL,
  `farmer_id` int NOT NULL,
  `business_id` int NOT NULL,
  `matched_price` decimal(10,2) NOT NULL,
  `matched_quantity` decimal(10,2) NOT NULL DEFAULT '0.00',
  `agent_reasoning` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `confidence_score` int NOT NULL,
  `status` enum('proposed','accepted','contract_signed','in_transit','delivered','completed','rejected','expired') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'proposed',
  `contract_agreed` tinyint(1) NOT NULL DEFAULT '0',
  `otp_verified` tinyint(1) NOT NULL DEFAULT '0',
  `otp_code` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_match` (`order_id`,`listing_id`),
  KEY `farmer_id_index` (`farmer_id`),
  KEY `business_id_index` (`business_id`),
  KEY `status_index` (`status`),
  KEY `fk_match_listing` (`listing_id`),
  CONSTRAINT `fk_match_business` FOREIGN KEY (`business_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_match_farmer` FOREIGN KEY (`farmer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_match_listing` FOREIGN KEY (`listing_id`) REFERENCES `harvest_listings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_match_order` FOREIGN KEY (`order_id`) REFERENCES `order_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_matches`
--

LOCK TABLES `order_matches` WRITE;
/*!40000 ALTER TABLE `order_matches` DISABLE KEYS */;
INSERT INTO `order_matches` VALUES (1,2,3,2,5,240.00,0.00,'Gemini AI Broker matched Keells Tomato pre-order #2 with Somasiri Silva harvest listing #3. Price of Rs. 240/kg is within buyer Rs. 250/kg max budget and provides a 22% margin above base production cost. Transit route via Dambulla-Kurunegala highway optimizes logistics.',96,'contract_signed',1,1,'889900','2026-09-05 05:31:06','2026-09-05 07:00:03'),(2,4,5,1,6,160.00,0.00,'Gemini AI Broker matched Cargills Cabbage order #4 with Bandara Herath listing #5. Order fulfilled, quality checked, and verified upon warehouse intake.',98,'completed',1,1,'571934','2026-09-05 05:31:06','2026-09-05 05:31:06'),(5,1,1,1,5,210.00,1000.00,'AI Broker selected Farmer Bandara Herath located in connected economic corridor (Nuwara Eliya) with 1500kg available unreserved stock. The matched price of Rs. 210.00/kg preserves fair-trade margins while fulfilling the buyer\'s requested delivery timeline with minimal food miles (SDG 8 & 12).',90,'accepted',0,0,NULL,'2026-09-05 06:32:37','2026-09-05 07:00:03');
/*!40000 ALTER TABLE `order_matches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_requests`
--

DROP TABLE IF EXISTS `order_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `business_id` int NOT NULL,
  `crop_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity_kg` decimal(10,2) NOT NULL,
  `max_price` decimal(10,2) NOT NULL,
  `min_delivery_qty` decimal(10,2) DEFAULT '0.00',
  `delivery_date` date NOT NULL,
  `urgency` enum('low','medium','high') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `status` enum('pending','matching','matched','in_transit','delivered','fulfilled','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `business_id_index` (`business_id`),
  KEY `crop_type_index` (`crop_type`),
  KEY `status_index` (`status`),
  CONSTRAINT `fk_order_business` FOREIGN KEY (`business_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_requests`
--

LOCK TABLES `order_requests` WRITE;
/*!40000 ALTER TABLE `order_requests` DISABLE KEYS */;
INSERT INTO `order_requests` VALUES (1,5,'Carrot',1000.00,230.00,0.00,'2026-09-09','high','matched','Need fresh Nuwara Eliya carrots for retail store distribution.','2026-09-05 05:31:06','2026-09-05 06:32:37'),(2,5,'Tomato',800.00,250.00,0.00,'2026-09-08','medium','matched','Grade A tomatoes required for salad retail section.','2026-09-05 05:31:06','2026-09-05 05:31:06'),(3,6,'Leek',600.00,200.00,0.00,'2026-09-13','low','pending','Direct farm supply preferred for Colombo distribution centers.','2026-09-05 05:31:06','2026-09-05 05:31:06'),(4,6,'Cabbage',900.00,175.00,0.00,'2026-09-05','high','fulfilled','Delivered to Gampaha centralized warehouse.','2026-09-05 05:31:06','2026-09-05 05:31:06'),(5,7,'Red Onion',2500.00,310.00,0.00,'2026-09-12','high','matching','Export grade cured red onions for Maldives sea container shipping.','2026-09-05 05:31:06','2026-09-05 05:31:06'),(6,5,'Carrot',100.00,200.00,0.00,'2026-09-08','medium','pending','','2026-09-05 06:17:23','2026-09-05 06:17:23'),(7,5,'Carrot',500.00,250.00,0.00,'2026-09-08','medium','pending','','2026-09-05 06:22:41','2026-09-05 06:22:41'),(8,5,'Papaya',250.00,90.00,0.00,'2026-09-08','medium','pending','','2026-09-05 06:35:04','2026-09-05 06:35:04');
/*!40000 ALTER TABLE `order_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_match_id` int NOT NULL,
  `payhere_payment_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','paid','escrow_released','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_match_id_index` (`order_match_id`),
  KEY `status_index` (`status`),
  CONSTRAINT `fk_payment_match` FOREIGN KEY (`order_match_id`) REFERENCES `order_matches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (1,1,'320025810012',192000.00,'paid','2026-09-05 05:31:06','2026-09-05 05:31:06'),(2,2,'320025810013',144000.00,'escrow_released','2026-09-05 05:31:06','2026-09-05 05:31:06'),(5,5,'TEST_PAYHERE_REF_1788591603',100000.00,'paid','2026-09-05 07:00:03','2026-09-05 07:00:03');
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_reviews`
--

DROP TABLE IF EXISTS `user_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_reviews` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reviewer_id` int NOT NULL,
  `reviewee_id` int NOT NULL,
  `order_match_id` int NOT NULL,
  `rating` tinyint(1) NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_order_reviewer` (`order_match_id`,`reviewer_id`),
  KEY `reviewer_id_index` (`reviewer_id`),
  KEY `reviewee_id_index` (`reviewee_id`),
  KEY `order_match_id_index` (`order_match_id`),
  CONSTRAINT `fk_review_match` FOREIGN KEY (`order_match_id`) REFERENCES `order_matches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_review_reviewee` FOREIGN KEY (`reviewee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_review_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_reviews`
--

LOCK TABLES `user_reviews` WRITE;
/*!40000 ALTER TABLE `user_reviews` DISABLE KEYS */;
INSERT INTO `user_reviews` VALUES (1,6,1,2,5,'Bandara provided exceptional quality cabbage. Delivered exactly on time, and the quality grade was better than expected.','2026-09-05 05:31:06','2026-09-05 05:31:06'),(2,5,2,1,4,'Good quality tomatoes, though a small percentage were slightly overripe. Fast communication.','2026-09-05 05:31:06','2026-09-05 05:31:06');
/*!40000 ALTER TABLE `user_reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('farmer','business','admin') COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `district` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nic_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `business_reg_no` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `reset_token` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `average_rating` decimal(3,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_unique` (`email`),
  KEY `role_index` (`role`),
  KEY `district_index` (`district`),
  KEY `reset_token_index` (`reset_token`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Bandara Herath','farmer@agrisync.lk','$2y$12$vRXM.k435qJg/45DKAXVhe985nOTvCCnJJlRyndd2AO/FGPfgQGG2','farmer','0771234567','Nuwara Eliya','851234567V',NULL,1,NULL,NULL,0.00,'2026-09-05 05:31:06','2026-09-05 06:16:10'),(2,'Somasiri Silva','dambulla.farmer@agrisync.lk','$2y$12$vRXM.k435qJg/45DKAXVhe985nOTvCCnJJlRyndd2AO/FGPfgQGG2','farmer','0719876543','Matale','199012345678',NULL,1,NULL,NULL,0.00,'2026-09-05 05:31:06','2026-09-05 05:31:06'),(3,'Kavinda Perera','badulla.farmer@agrisync.lk','$2y$12$vRXM.k435qJg/45DKAXVhe985nOTvCCnJJlRyndd2AO/FGPfgQGG2','farmer','0765554321','Badulla','923456789V',NULL,1,NULL,NULL,0.00,'2026-09-05 05:31:06','2026-09-05 05:31:06'),(4,'Tharindu Jayasuriya','jaffna.farmer@agrisync.lk','$2y$12$vRXM.k435qJg/45DKAXVhe985nOTvCCnJJlRyndd2AO/FGPfgQGG2','farmer','0783332211','Jaffna','198898765432',NULL,1,NULL,NULL,0.00,'2026-09-05 05:31:06','2026-09-05 05:31:06'),(5,'Keells Supermarket Procurement','buyer@agrisync.lk','$2y$12$vRXM.k435qJg/45DKAXVhe985nOTvCCnJJlRyndd2AO/FGPfgQGG2','business','0112345678','Colombo',NULL,'PV12345',1,NULL,NULL,0.00,'2026-09-05 05:31:06','2026-09-05 05:31:06'),(6,'Cargills Food City Central Logistics','cargills@agrisync.lk','$2y$12$vRXM.k435qJg/45DKAXVhe985nOTvCCnJJlRyndd2AO/FGPfgQGG2','business','0119876543','Gampaha',NULL,'PV67890',1,NULL,NULL,0.00,'2026-09-05 05:31:06','2026-09-05 05:31:06'),(7,'Ceylon Agro Exports Ltd','export@agrisync.lk','$2y$12$vRXM.k435qJg/45DKAXVhe985nOTvCCnJJlRyndd2AO/FGPfgQGG2','business','0114445566','Colombo',NULL,'PV99887',1,NULL,NULL,0.00,'2026-09-05 05:31:06','2026-09-05 05:31:06'),(8,'AgriSync System Admin','admin@agrisync.lk','$2y$12$vRXM.k435qJg/45DKAXVhe985nOTvCCnJJlRyndd2AO/FGPfgQGG2','admin','0703534431','Colombo','198511223344','ADM001',1,NULL,NULL,0.00,'2026-09-05 05:31:06','2026-09-05 05:31:06');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-06 15:49:17
