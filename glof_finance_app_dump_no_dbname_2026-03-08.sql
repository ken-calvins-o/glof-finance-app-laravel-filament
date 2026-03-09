-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: glof_finance_app
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `account_collections`
--

DROP TABLE IF EXISTS `account_collections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `account_collections` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `account_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `account_collections_account_id_foreign` (`account_id`),
  KEY `account_collections_user_id_foreign` (`user_id`),
  CONSTRAINT `account_collections_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`),
  CONSTRAINT `account_collections_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `account_collections`
--

LOCK TABLES `account_collections` WRITE;
/*!40000 ALTER TABLE `account_collections` DISABLE KEYS */;
INSERT INTO `account_collections` VALUES (1,4,3,32.00,'2026-03-08 06:55:39','2026-03-08 06:55:39'),(2,4,1,107.00,'2026-03-08 06:56:47','2026-03-08 06:56:47'),(3,4,6,2005.00,'2026-03-08 06:57:21','2026-03-08 06:57:21'),(4,4,7,125.00,'2026-03-08 06:57:54','2026-03-08 06:57:54'),(5,4,8,1000.00,'2026-03-08 06:58:29','2026-03-08 06:58:29'),(6,4,10,1021.00,'2026-03-08 06:59:07','2026-03-08 06:59:07'),(7,4,11,10.00,'2026-03-08 06:59:46','2026-03-08 06:59:46'),(8,4,12,24.00,'2026-03-08 07:00:32','2026-03-08 07:00:32'),(9,4,14,41.00,'2026-03-08 07:01:07','2026-03-08 07:01:07'),(10,4,15,6.00,'2026-03-08 07:01:46','2026-03-08 07:01:46'),(11,4,16,45.00,'2026-03-08 07:02:19','2026-03-08 07:02:19'),(12,4,17,51.00,'2026-03-08 07:02:52','2026-03-08 07:02:52'),(13,4,19,11.00,'2026-03-08 07:03:32','2026-03-08 07:03:32'),(14,4,20,2000.00,'2026-03-08 07:04:13','2026-03-08 07:04:13'),(15,4,21,10.00,'2026-03-08 07:04:51','2026-03-08 07:04:51'),(16,4,23,1.00,'2026-03-08 07:05:28','2026-03-08 07:05:28'),(17,4,24,4051.00,'2026-03-08 07:07:24','2026-03-08 07:07:24'),(18,4,26,10000.00,'2026-03-08 07:08:08','2026-03-08 07:08:08'),(19,4,28,7000.00,'2026-03-08 07:08:43','2026-03-08 07:08:43'),(20,4,30,102.00,'2026-03-08 07:09:23','2026-03-08 07:09:23'),(21,4,31,61.00,'2026-03-08 07:10:12','2026-03-08 07:10:12'),(22,3,2,55050.00,'2026-03-08 07:48:15','2026-03-08 07:48:15'),(23,3,3,4225.00,'2026-03-08 07:48:56','2026-03-08 07:48:56'),(24,3,4,14975.00,'2026-03-08 07:50:02','2026-03-08 07:50:02'),(25,3,8,7950.00,'2026-03-08 07:50:51','2026-03-08 07:50:51'),(26,3,12,15000.00,'2026-03-08 07:51:29','2026-03-08 07:51:29'),(27,3,14,30316.00,'2026-03-08 07:52:05','2026-03-08 07:52:05'),(28,3,21,55937.00,'2026-03-08 07:52:44','2026-03-08 07:52:44'),(29,3,23,35000.00,'2026-03-08 07:53:21','2026-03-08 07:53:21'),(30,3,24,7300.00,'2026-03-08 07:53:55','2026-03-08 07:53:55'),(31,3,26,18000.00,'2026-03-08 07:54:32','2026-03-08 07:54:32'),(32,3,28,29718.00,'2026-03-08 07:55:19','2026-03-08 07:55:19'),(33,3,30,442.00,'2026-03-08 07:56:37','2026-03-08 07:56:37'),(34,3,31,6950.00,'2026-03-08 07:57:13','2026-03-08 07:57:13'),(35,3,32,15750.00,'2026-03-08 07:57:52','2026-03-08 07:57:52'),(36,2,3,-63.00,'2026-03-08 08:06:37','2026-03-08 08:06:37'),(37,3,15,-5256.00,'2026-03-08 08:07:54','2026-03-08 08:07:54'),(38,2,17,-5256.00,'2026-03-08 08:08:52','2026-03-08 08:08:52'),(41,2,23,2000.00,'2026-03-08 08:31:54','2026-03-08 08:31:54'),(42,2,28,6100.00,'2026-03-08 08:33:16','2026-03-08 08:33:16'),(43,2,30,-5256.00,'2026-03-08 08:34:04','2026-03-08 08:34:04'),(44,2,31,17380.00,'2026-03-08 08:35:17','2026-03-08 08:35:17'),(45,2,32,-5050.00,'2026-03-08 08:36:00','2026-03-08 08:36:00'),(46,2,15,-5256.00,'2026-03-08 08:38:27','2026-03-08 08:38:27'),(47,2,19,-54.00,'2026-03-08 08:41:13','2026-03-08 08:41:13'),(48,2,20,-5256.00,'2026-03-08 08:43:01','2026-03-08 08:43:01'),(49,6,2,1556.00,'2026-03-08 08:44:28','2026-03-08 08:44:28'),(50,6,3,5000.00,'2026-03-08 08:45:10','2026-03-08 08:45:10'),(51,6,4,-3938.00,'2026-03-08 08:47:09','2026-03-08 08:47:09'),(52,6,5,4000.00,'2026-03-08 08:47:46','2026-03-08 08:47:46'),(53,6,6,-4462.00,'2026-03-08 08:48:51','2026-03-08 08:48:51'),(54,6,8,4000.00,'2026-03-08 08:49:51','2026-03-08 08:49:51'),(55,6,11,4000.00,'2026-03-08 08:52:54','2026-03-08 08:52:54'),(56,6,12,4000.00,'2026-03-08 08:54:11','2026-03-08 08:54:11'),(57,6,14,592.00,'2026-03-08 08:54:53','2026-03-08 08:54:53'),(58,6,15,-4462.00,'2026-03-08 08:55:48','2026-03-08 08:55:48'),(59,6,16,40.00,'2026-03-08 08:57:01','2026-03-08 08:57:01'),(60,6,17,-4462.00,'2026-03-08 08:57:46','2026-03-08 08:57:46'),(61,6,21,4040.00,'2026-03-08 08:58:39','2026-03-08 08:58:39'),(62,6,23,4000.00,'2026-03-08 08:59:20','2026-03-08 08:59:20'),(63,6,24,-4462.00,'2026-03-08 09:00:59','2026-03-08 09:00:59'),(64,6,28,686.00,'2026-03-08 09:01:46','2026-03-08 09:01:46'),(65,6,30,7000.00,'2026-03-08 09:03:34','2026-03-08 09:03:34'),(66,6,31,5000.00,'2026-03-08 09:05:52','2026-03-08 09:05:52'),(67,6,32,4000.00,'2026-03-08 09:09:25','2026-03-08 09:09:25');
/*!40000 ALTER TABLE `account_collections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `account_user`
--

DROP TABLE IF EXISTS `account_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `account_user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `account_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `billing_type` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `account_user_account_id_foreign` (`account_id`),
  KEY `account_user_user_id_foreign` (`user_id`),
  CONSTRAINT `account_user_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`),
  CONSTRAINT `account_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `account_user`
--

LOCK TABLES `account_user` WRITE;
/*!40000 ALTER TABLE `account_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `account_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounts`
--

DROP TABLE IF EXISTS `accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounts`
--

LOCK TABLES `accounts` WRITE;
/*!40000 ALTER TABLE `accounts` DISABLE KEYS */;
INSERT INTO `accounts` VALUES (1,'Administration','2026-03-08 06:49:27','2026-03-08 06:49:27'),(2,'Bereavement','2026-03-08 06:49:27','2026-03-08 06:49:27'),(3,'Insurance','2026-03-08 06:49:27','2026-03-08 06:49:27'),(4,'Host/TQ Outstanding','2026-03-08 06:49:27','2026-03-08 06:49:27'),(5,'Miscellaneous','2026-03-08 06:49:28','2026-03-08 06:49:28'),(6,'Party/Rural Visit','2026-03-08 06:49:28','2026-03-08 06:49:28');
/*!40000 ALTER TABLE `accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('livewire-rate-limiter:a17961fa74e9275d529f489537f179c05d50c2f3','i:1;',1772963701),('livewire-rate-limiter:a17961fa74e9275d529f489537f179c05d50c2f3:timer','i:1772963701;',1772963701);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contributions`
--

DROP TABLE IF EXISTS `contributions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contributions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `account_id` bigint(20) unsigned NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `payment_status` varchar(255) NOT NULL DEFAULT 'Pending',
  `payment_method` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contributions`
--

LOCK TABLES `contributions` WRITE;
/*!40000 ALTER TABLE `contributions` DISABLE KEYS */;
/*!40000 ALTER TABLE `contributions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `debts`
--

DROP TABLE IF EXISTS `debts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `debts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  `outstanding_balance` decimal(10,2) NOT NULL,
  `repayment_amount` decimal(10,2) DEFAULT 0.00,
  `from_savings` tinyint(1) NOT NULL DEFAULT 0,
  `due_date` datetime DEFAULT NULL,
  `debt_status` varchar(255) NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_interest_applied_on` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `debts_last_interest_applied_on_index` (`last_interest_applied_on`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `debts`
--

LOCK TABLES `debts` WRITE;
/*!40000 ALTER TABLE `debts` DISABLE KEYS */;
INSERT INTO `debts` VALUES (1,2,NULL,387320.00,0.00,0,NULL,'Pending','2026-03-08 07:59:49','2026-03-08 07:59:49',NULL),(2,3,NULL,1252037.00,0.00,0,NULL,'Pending','2026-03-08 08:00:19','2026-03-08 08:00:19',NULL),(3,4,NULL,140589.00,0.00,0,NULL,'Pending','2026-03-08 08:00:38','2026-03-08 08:00:38',NULL),(4,10,NULL,166470.00,0.00,0,NULL,'Pending','2026-03-08 08:01:14','2026-03-08 08:01:14',NULL),(5,14,NULL,36145.00,0.00,0,NULL,'Pending','2026-03-08 08:01:50','2026-03-08 08:01:50',NULL),(6,16,NULL,259515.00,0.00,0,NULL,'Pending','2026-03-08 08:02:19','2026-03-08 08:02:19',NULL),(7,18,NULL,144386.00,0.00,0,NULL,'Pending','2026-03-08 08:02:53','2026-03-08 08:02:53',NULL),(8,19,NULL,34683.00,0.00,0,NULL,'Pending','2026-03-08 08:03:14','2026-03-08 08:03:14',NULL),(9,28,NULL,171720.00,0.00,0,NULL,'Pending','2026-03-08 08:03:41','2026-03-08 08:03:41',NULL),(10,3,2,63.00,0.00,0,NULL,'Pending','2026-03-08 08:06:37','2026-03-08 08:06:37',NULL),(11,15,3,5256.00,0.00,0,NULL,'Pending','2026-03-08 08:07:54','2026-03-08 08:07:54',NULL),(12,17,2,5256.00,0.00,0,NULL,'Pending','2026-03-08 08:08:52','2026-03-08 08:08:52',NULL),(15,30,2,5256.00,0.00,0,NULL,'Pending','2026-03-08 08:34:04','2026-03-08 08:34:04',NULL),(16,32,2,5050.00,0.00,0,NULL,'Pending','2026-03-08 08:36:00','2026-03-08 08:36:00',NULL),(17,15,2,5256.00,0.00,0,NULL,'Pending','2026-03-08 08:38:27','2026-03-08 08:38:27',NULL),(18,19,2,54.00,0.00,0,NULL,'Pending','2026-03-08 08:41:13','2026-03-08 08:41:13',NULL),(19,20,2,5256.00,0.00,0,NULL,'Pending','2026-03-08 08:43:01','2026-03-08 08:43:01',NULL),(20,4,6,3938.00,0.00,0,NULL,'Pending','2026-03-08 08:47:09','2026-03-08 08:47:09',NULL),(21,6,6,4462.00,0.00,0,NULL,'Pending','2026-03-08 08:48:51','2026-03-08 08:48:51',NULL),(22,15,6,4462.00,0.00,0,NULL,'Pending','2026-03-08 08:55:48','2026-03-08 08:55:48',NULL),(23,17,6,4462.00,0.00,0,NULL,'Pending','2026-03-08 08:57:46','2026-03-08 08:57:46',NULL),(24,24,6,4462.00,0.00,0,NULL,'Pending','2026-03-08 09:00:59','2026-03-08 09:00:59',NULL);
/*!40000 ALTER TABLE `debts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `incomes`
--

DROP TABLE IF EXISTS `incomes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `incomes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  `source` bigint(20) unsigned DEFAULT NULL,
  `origin` varchar(255) DEFAULT NULL,
  `income_amount` varchar(255) DEFAULT NULL,
  `interest_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `incomes_user_id_foreign` (`user_id`),
  KEY `incomes_account_id_foreign` (`account_id`),
  CONSTRAINT `incomes_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `incomes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `incomes`
--

LOCK TABLES `incomes` WRITE;
/*!40000 ALTER TABLE `incomes` DISABLE KEYS */;
INSERT INTO `incomes` VALUES (1,1,NULL,NULL,'Registration Fee','1000',0.00,NULL,'2026-03-08 06:49:25','2026-03-08 06:49:25'),(2,2,NULL,NULL,'Registration Fee','1000',0.00,NULL,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(3,3,NULL,NULL,'Registration Fee','1000',0.00,NULL,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(4,4,NULL,NULL,'Registration Fee','1000',0.00,NULL,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(5,5,NULL,NULL,'Registration Fee','1000',0.00,NULL,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(6,6,NULL,NULL,'Registration Fee','1000',0.00,NULL,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(7,7,NULL,NULL,'Registration Fee','1000',0.00,NULL,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(8,8,NULL,NULL,'Registration Fee','1000',0.00,NULL,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(9,9,NULL,NULL,'Registration Fee','1000',0.00,NULL,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(10,10,NULL,NULL,'Registration Fee','3000',0.00,NULL,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(11,11,NULL,NULL,'Registration Fee','5000',0.00,NULL,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(12,12,NULL,NULL,'Registration Fee','5000',0.00,NULL,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(13,13,NULL,NULL,'Registration Fee','5000',0.00,NULL,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(14,14,NULL,NULL,'Registration Fee','5000',0.00,NULL,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(15,15,NULL,NULL,'Registration Fee','5000',0.00,NULL,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(16,16,NULL,NULL,'Registration Fee','10000',0.00,NULL,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(17,17,NULL,NULL,'Registration Fee','10000',0.00,NULL,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(18,18,NULL,NULL,'Registration Fee','10000',0.00,NULL,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(19,19,NULL,NULL,'Registration Fee','10000',0.00,NULL,'2026-03-08 06:49:27','2026-03-08 06:49:27'),(20,20,NULL,NULL,'Registration Fee','10000',0.00,NULL,'2026-03-08 06:49:27','2026-03-08 06:49:27'),(21,21,NULL,NULL,'Registration Fee','10000',0.00,NULL,'2026-03-08 06:49:27','2026-03-08 06:49:27'),(22,22,NULL,NULL,'Registration Fee','10000',0.00,NULL,'2026-03-08 06:49:27','2026-03-08 06:49:27'),(23,23,NULL,NULL,'Registration Fee','10000',0.00,NULL,'2026-03-08 06:49:27','2026-03-08 06:49:27'),(24,24,NULL,NULL,'Registration Fee','20000',0.00,NULL,'2026-03-08 06:49:27','2026-03-08 06:49:27'),(25,25,NULL,NULL,'Registration Fee','20000',0.00,NULL,'2026-03-08 06:49:27','2026-03-08 06:49:27'),(26,26,NULL,NULL,'Registration Fee','20000',0.00,NULL,'2026-03-08 06:49:27','2026-03-08 06:49:27'),(27,27,NULL,NULL,'Registration Fee','20000',0.00,NULL,'2026-03-08 06:49:27','2026-03-08 06:49:27'),(28,28,NULL,NULL,'Registration Fee','20000',0.00,NULL,'2026-03-08 06:49:27','2026-03-08 06:49:27'),(29,29,NULL,NULL,'Registration Fee','20000',0.00,NULL,'2026-03-08 06:49:27','2026-03-08 06:49:27'),(30,30,NULL,NULL,'Registration Fee','20000',0.00,NULL,'2026-03-08 06:49:27','2026-03-08 06:49:27'),(31,31,NULL,NULL,'Registration Fee','20000',0.00,NULL,'2026-03-08 06:49:27','2026-03-08 06:49:27'),(32,32,NULL,NULL,'Registration Fee','20000',0.00,NULL,'2026-03-08 06:49:27','2026-03-08 06:49:27');
/*!40000 ALTER TABLE `incomes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loans`
--

DROP TABLE IF EXISTS `loans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `loans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `balance` decimal(10,2) NOT NULL,
  `apply_interest` tinyint(1) NOT NULL DEFAULT 1,
  `interest` varchar(255) NOT NULL,
  `due_date` datetime NOT NULL,
  `debt_status` varchar(255) NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loans`
--

LOCK TABLES `loans` WRITE;
/*!40000 ALTER TABLE `loans` DISABLE KEYS */;
INSERT INTO `loans` VALUES (1,2,NULL,387320.00,387320.00,0,'0','2026-03-08 13:59:34','Pending','2026-03-08 07:59:49','2026-03-08 07:59:49'),(2,3,NULL,1252037.00,1252037.00,0,'0','2026-03-08 14:00:12','Pending','2026-03-08 08:00:19','2026-03-08 08:00:19'),(3,4,NULL,140589.00,140589.00,0,'0','2026-03-08 14:00:29','Pending','2026-03-08 08:00:38','2026-03-08 08:00:38'),(4,10,NULL,166470.00,166470.00,0,'0','2026-03-08 14:01:07','Pending','2026-03-08 08:01:14','2026-03-08 08:01:14'),(5,14,NULL,36145.00,36145.00,0,'0','2026-03-08 14:01:42','Pending','2026-03-08 08:01:50','2026-03-08 08:01:50'),(6,16,NULL,259515.00,259515.00,0,'0','2026-03-08 14:02:11','Pending','2026-03-08 08:02:19','2026-03-08 08:02:19'),(7,18,NULL,144386.00,144386.00,0,'0','2026-03-08 14:02:46','Pending','2026-03-08 08:02:52','2026-03-08 08:02:52'),(8,19,NULL,34683.00,34683.00,0,'0','2026-03-08 14:03:07','Pending','2026-03-08 08:03:14','2026-03-08 08:03:14'),(9,28,NULL,171720.00,171720.00,0,'0','2026-03-08 14:03:35','Pending','2026-03-08 08:03:41','2026-03-08 08:03:41');
/*!40000 ALTER TABLE `loans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2024_10_29_111312_create_savings_table',1),(5,'2024_10_29_111313_create_accounts_table',1),(6,'2024_10_29_111314_create_contributions_table',1),(7,'2024_10_29_111315_create_loans_table',1),(8,'2024_10_29_111316_create_incomes_table',1),(9,'2024_10_31_111737_create_debts_table',1),(10,'2024_11_19_095444_create_receivables_table',1),(11,'2024_11_26_085933_create_account_user_table',1),(12,'2024_11_30_042530_create_payables_table',1),(13,'2025_01_12_194636_add_apply_interest_to_loans_table',1),(14,'2025_01_20_152332_create_months_table',1),(15,'2025_01_20_152358_create_years_table',1),(16,'2025_01_21_155440_create_monthly_receivable_table',1),(17,'2025_01_21_155449_create_monthly_payable_table',1),(18,'2025_01_21_155514_create_payable_year_table',1),(19,'2025_01_21_155524_create_receivable_year_table',1),(20,'2025_01_25_133009_add_is_general_to_payables_table',1),(21,'2025_01_25_133543_add_user_id_to_payables_table',1),(22,'2025_01_25_183503_create_month_debt_table',1),(23,'2025_01_25_183515_create_year_debt_table',1),(24,'2025_02_05_184456_create_account_collections_table',1),(25,'2026_02_11_000000_add_last_interest_applied_on_to_debts_table',1),(26,'2026_02_15_000000_add_soft_deletes_and_create_receivable_deletions_table',1),(27,'2026_02_15_000001_create_receivable_effects_table',1),(28,'2026_02_15_000002_add_deleted_at_to_receivables',1),(29,'2026_02_15_000003_add_reversion_fields_to_receivable_effects',1),(30,'2026_02_15_000004_add_account_user_to_receivable_effects',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `month_debt`
--

DROP TABLE IF EXISTS `month_debt`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `month_debt` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `month_debt`
--

LOCK TABLES `month_debt` WRITE;
/*!40000 ALTER TABLE `month_debt` DISABLE KEYS */;
/*!40000 ALTER TABLE `month_debt` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `monthly_payable`
--

DROP TABLE IF EXISTS `monthly_payable`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `monthly_payable` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `month_id` bigint(20) unsigned NOT NULL,
  `payable_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `monthly_payable_month_id_foreign` (`month_id`),
  KEY `monthly_payable_payable_id_foreign` (`payable_id`),
  CONSTRAINT `monthly_payable_month_id_foreign` FOREIGN KEY (`month_id`) REFERENCES `months` (`id`),
  CONSTRAINT `monthly_payable_payable_id_foreign` FOREIGN KEY (`payable_id`) REFERENCES `payables` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `monthly_payable`
--

LOCK TABLES `monthly_payable` WRITE;
/*!40000 ALTER TABLE `monthly_payable` DISABLE KEYS */;
/*!40000 ALTER TABLE `monthly_payable` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `monthly_receivable`
--

DROP TABLE IF EXISTS `monthly_receivable`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `monthly_receivable` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `month_id` bigint(20) unsigned DEFAULT NULL,
  `receivable_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `monthly_receivable_month_id_foreign` (`month_id`),
  KEY `monthly_receivable_receivable_id_foreign` (`receivable_id`),
  CONSTRAINT `monthly_receivable_month_id_foreign` FOREIGN KEY (`month_id`) REFERENCES `months` (`id`),
  CONSTRAINT `monthly_receivable_receivable_id_foreign` FOREIGN KEY (`receivable_id`) REFERENCES `receivables` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `monthly_receivable`
--

LOCK TABLES `monthly_receivable` WRITE;
/*!40000 ALTER TABLE `monthly_receivable` DISABLE KEYS */;
INSERT INTO `monthly_receivable` VALUES (1,10,1,'2026-03-08 06:55:39','2026-03-08 06:55:39'),(2,10,2,'2026-03-08 06:56:47','2026-03-08 06:56:47'),(3,3,3,'2026-03-08 06:57:21','2026-03-08 06:57:21'),(4,10,4,'2026-03-08 06:57:54','2026-03-08 06:57:54'),(5,10,5,'2026-03-08 06:58:29','2026-03-08 06:58:29'),(6,10,6,'2026-03-08 06:59:07','2026-03-08 06:59:07'),(7,10,7,'2026-03-08 06:59:46','2026-03-08 06:59:46'),(8,10,8,'2026-03-08 07:00:32','2026-03-08 07:00:32'),(9,10,9,'2026-03-08 07:01:06','2026-03-08 07:01:06'),(10,10,10,'2026-03-08 07:01:46','2026-03-08 07:01:46'),(11,10,11,'2026-03-08 07:02:19','2026-03-08 07:02:19'),(12,10,12,'2026-03-08 07:02:52','2026-03-08 07:02:52'),(13,10,13,'2026-03-08 07:03:32','2026-03-08 07:03:32'),(14,10,14,'2026-03-08 07:04:13','2026-03-08 07:04:13'),(15,10,15,'2026-03-08 07:04:51','2026-03-08 07:04:51'),(16,10,16,'2026-03-08 07:05:28','2026-03-08 07:05:28'),(17,10,17,'2026-03-08 07:07:24','2026-03-08 07:07:24'),(18,10,18,'2026-03-08 07:08:08','2026-03-08 07:08:08'),(19,10,19,'2026-03-08 07:08:43','2026-03-08 07:08:43'),(20,10,20,'2026-03-08 07:09:23','2026-03-08 07:09:23'),(21,10,21,'2026-03-08 07:10:12','2026-03-08 07:10:12'),(22,10,22,'2026-03-08 07:48:15','2026-03-08 07:48:15'),(23,10,23,'2026-03-08 07:48:56','2026-03-08 07:48:56'),(24,10,24,'2026-03-08 07:50:02','2026-03-08 07:50:02'),(25,10,25,'2026-03-08 07:50:51','2026-03-08 07:50:51'),(26,10,26,'2026-03-08 07:51:29','2026-03-08 07:51:29'),(27,10,27,'2026-03-08 07:52:05','2026-03-08 07:52:05'),(28,10,28,'2026-03-08 07:52:44','2026-03-08 07:52:44'),(29,10,29,'2026-03-08 07:53:21','2026-03-08 07:53:21'),(30,10,30,'2026-03-08 07:53:55','2026-03-08 07:53:55'),(31,10,31,'2026-03-08 07:54:32','2026-03-08 07:54:32'),(32,10,32,'2026-03-08 07:55:19','2026-03-08 07:55:19'),(33,10,33,'2026-03-08 07:56:37','2026-03-08 07:56:37'),(34,10,34,'2026-03-08 07:57:13','2026-03-08 07:57:13'),(35,10,35,'2026-03-08 07:57:52','2026-03-08 07:57:52'),(36,10,36,'2026-03-08 08:06:37','2026-03-08 08:06:37'),(37,10,37,'2026-03-08 08:07:54','2026-03-08 08:07:54'),(38,10,38,'2026-03-08 08:08:52','2026-03-08 08:08:52'),(39,10,39,'2026-03-08 08:09:39','2026-03-08 08:09:39'),(40,10,40,'2026-03-08 08:30:30','2026-03-08 08:30:30'),(41,10,41,'2026-03-08 08:31:53','2026-03-08 08:31:53'),(42,10,42,'2026-03-08 08:33:16','2026-03-08 08:33:16'),(43,10,43,'2026-03-08 08:34:04','2026-03-08 08:34:04'),(44,10,44,'2026-03-08 08:35:17','2026-03-08 08:35:17'),(45,10,45,'2026-03-08 08:36:00','2026-03-08 08:36:00'),(46,10,46,'2026-03-08 08:38:27','2026-03-08 08:38:27'),(47,10,47,'2026-03-08 08:41:13','2026-03-08 08:41:13'),(48,10,48,'2026-03-08 08:43:01','2026-03-08 08:43:01'),(49,10,49,'2026-03-08 08:44:28','2026-03-08 08:44:28'),(50,10,50,'2026-03-08 08:45:10','2026-03-08 08:45:10'),(51,10,51,'2026-03-08 08:47:09','2026-03-08 08:47:09'),(52,10,52,'2026-03-08 08:47:45','2026-03-08 08:47:45'),(53,10,53,'2026-03-08 08:48:51','2026-03-08 08:48:51'),(54,10,54,'2026-03-08 08:49:51','2026-03-08 08:49:51'),(55,10,55,'2026-03-08 08:52:54','2026-03-08 08:52:54'),(56,10,56,'2026-03-08 08:54:11','2026-03-08 08:54:11'),(57,10,57,'2026-03-08 08:54:53','2026-03-08 08:54:53'),(58,3,58,'2026-03-08 08:55:48','2026-03-08 08:55:48'),(59,10,59,'2026-03-08 08:57:01','2026-03-08 08:57:01'),(60,10,60,'2026-03-08 08:57:46','2026-03-08 08:57:46'),(61,10,61,'2026-03-08 08:58:39','2026-03-08 08:58:39'),(62,10,62,'2026-03-08 08:59:20','2026-03-08 08:59:20'),(63,10,63,'2026-03-08 09:00:59','2026-03-08 09:00:59'),(64,10,64,'2026-03-08 09:01:46','2026-03-08 09:01:46'),(65,10,65,'2026-03-08 09:03:34','2026-03-08 09:03:34'),(66,10,66,'2026-03-08 09:05:52','2026-03-08 09:05:52'),(67,10,67,'2026-03-08 09:09:24','2026-03-08 09:09:24');
/*!40000 ALTER TABLE `monthly_receivable` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `months`
--

DROP TABLE IF EXISTS `months`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `months` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `months`
--

LOCK TABLES `months` WRITE;
/*!40000 ALTER TABLE `months` DISABLE KEYS */;
INSERT INTO `months` VALUES (1,'January','2026-03-08 06:49:28','2026-03-08 06:49:28'),(2,'February','2026-03-08 06:49:28','2026-03-08 06:49:28'),(3,'March','2026-03-08 06:49:28','2026-03-08 06:49:28'),(4,'April','2026-03-08 06:49:28','2026-03-08 06:49:28'),(5,'May','2026-03-08 06:49:28','2026-03-08 06:49:28'),(6,'June','2026-03-08 06:49:28','2026-03-08 06:49:28'),(7,'July','2026-03-08 06:49:28','2026-03-08 06:49:28'),(8,'August','2026-03-08 06:49:28','2026-03-08 06:49:28'),(9,'September','2026-03-08 06:49:28','2026-03-08 06:49:28'),(10,'October','2026-03-08 06:49:28','2026-03-08 06:49:28'),(11,'November','2026-03-08 06:49:28','2026-03-08 06:49:28'),(12,'December','2026-03-08 06:49:28','2026-03-08 06:49:28');
/*!40000 ALTER TABLE `months` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payable_year`
--

DROP TABLE IF EXISTS `payable_year`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payable_year` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `year_id` bigint(20) unsigned NOT NULL,
  `payable_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payable_year_year_id_foreign` (`year_id`),
  KEY `payable_year_payable_id_foreign` (`payable_id`),
  CONSTRAINT `payable_year_payable_id_foreign` FOREIGN KEY (`payable_id`) REFERENCES `payables` (`id`),
  CONSTRAINT `payable_year_year_id_foreign` FOREIGN KEY (`year_id`) REFERENCES `years` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payable_year`
--

LOCK TABLES `payable_year` WRITE;
/*!40000 ALTER TABLE `payable_year` DISABLE KEYS */;
/*!40000 ALTER TABLE `payable_year` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payables`
--

DROP TABLE IF EXISTS `payables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payables` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `account_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `from_savings` tinyint(1) NOT NULL,
  `is_general` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payables_account_id_foreign` (`account_id`),
  KEY `payables_user_id_foreign` (`user_id`),
  CONSTRAINT `payables_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`),
  CONSTRAINT `payables_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payables`
--

LOCK TABLES `payables` WRITE;
/*!40000 ALTER TABLE `payables` DISABLE KEYS */;
/*!40000 ALTER TABLE `payables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `receivable_effects`
--

DROP TABLE IF EXISTS `receivable_effects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `receivable_effects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `receivable_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  `account_collection_id` bigint(20) unsigned DEFAULT NULL,
  `account_collection_prev_amount` decimal(14,2) DEFAULT NULL,
  `account_collection_post_amount` decimal(10,2) DEFAULT NULL,
  `saving_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`saving_ids`)),
  `saving_snapshots` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`saving_snapshots`)),
  `deletion_reversal_saving_id` bigint(20) unsigned DEFAULT NULL,
  `reversal_saving_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`reversal_saving_ids`)),
  `debt_id` bigint(20) unsigned DEFAULT NULL,
  `debt_prev_outstanding` decimal(14,2) DEFAULT NULL,
  `debt_created_by_receivable` tinyint(1) NOT NULL DEFAULT 0,
  `reverted` tinyint(1) NOT NULL DEFAULT 0,
  `reverted_at` timestamp NULL DEFAULT NULL,
  `reverted_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `receivable_effects_account_collection_id_foreign` (`account_collection_id`),
  KEY `idx_receivable_user_account` (`receivable_id`,`user_id`,`account_id`),
  CONSTRAINT `receivable_effects_account_collection_id_foreign` FOREIGN KEY (`account_collection_id`) REFERENCES `account_collections` (`id`) ON DELETE SET NULL,
  CONSTRAINT `receivable_effects_receivable_id_foreign` FOREIGN KEY (`receivable_id`) REFERENCES `receivables` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=135 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `receivable_effects`
--

LOCK TABLES `receivable_effects` WRITE;
/*!40000 ALTER TABLE `receivable_effects` DISABLE KEYS */;
INSERT INTO `receivable_effects` VALUES (1,1,NULL,NULL,NULL,NULL,NULL,'[3]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 06:55:39','2026-03-08 06:55:39'),(2,1,NULL,NULL,NULL,NULL,32.00,'[33]','[{\"id\":33,\"prev_balance\":\"0.00\",\"prev_net_worth\":\"0.00\",\"credit_amount\":32,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 06:55:39','2026-03-08 06:55:39'),(3,2,NULL,NULL,NULL,NULL,NULL,'[1]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 06:56:47','2026-03-08 06:56:47'),(4,2,NULL,NULL,NULL,NULL,107.00,'[34]','[{\"id\":34,\"prev_balance\":\"0.00\",\"prev_net_worth\":\"0.00\",\"credit_amount\":107,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 06:56:47','2026-03-08 06:56:47'),(5,3,NULL,NULL,NULL,NULL,NULL,'[6]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 06:57:21','2026-03-08 06:57:21'),(6,3,NULL,NULL,NULL,NULL,2005.00,'[35]','[{\"id\":35,\"prev_balance\":\"0.00\",\"prev_net_worth\":\"0.00\",\"credit_amount\":2005,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 06:57:21','2026-03-08 06:57:21'),(7,4,NULL,NULL,NULL,NULL,NULL,'[7]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 06:57:54','2026-03-08 06:57:54'),(8,4,NULL,NULL,NULL,NULL,125.00,'[36]','[{\"id\":36,\"prev_balance\":\"0.00\",\"prev_net_worth\":\"0.00\",\"credit_amount\":125,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 06:57:54','2026-03-08 06:57:54'),(9,5,NULL,NULL,NULL,NULL,NULL,'[8]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 06:58:29','2026-03-08 06:58:29'),(10,5,NULL,NULL,NULL,NULL,1000.00,'[37]','[{\"id\":37,\"prev_balance\":\"0.00\",\"prev_net_worth\":\"0.00\",\"credit_amount\":1000,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 06:58:30','2026-03-08 06:58:30'),(11,6,NULL,NULL,NULL,NULL,NULL,'[10]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 06:59:07','2026-03-08 06:59:07'),(12,6,NULL,NULL,NULL,NULL,1021.00,'[38]','[{\"id\":38,\"prev_balance\":\"0.00\",\"prev_net_worth\":\"0.00\",\"credit_amount\":1021,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 06:59:07','2026-03-08 06:59:07'),(13,7,NULL,NULL,NULL,NULL,NULL,'[11]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 06:59:46','2026-03-08 06:59:46'),(14,7,NULL,NULL,NULL,NULL,10.00,'[39]','[{\"id\":39,\"prev_balance\":\"0.00\",\"prev_net_worth\":\"0.00\",\"credit_amount\":10,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 06:59:46','2026-03-08 06:59:46'),(15,8,NULL,NULL,NULL,NULL,NULL,'[12]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:00:32','2026-03-08 07:00:32'),(16,8,NULL,NULL,NULL,NULL,24.00,'[40]','[{\"id\":40,\"prev_balance\":\"0.00\",\"prev_net_worth\":\"0.00\",\"credit_amount\":24,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:00:32','2026-03-08 07:00:32'),(17,9,NULL,NULL,NULL,NULL,NULL,'[14]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:01:06','2026-03-08 07:01:06'),(18,9,NULL,NULL,NULL,NULL,41.00,'[41]','[{\"id\":41,\"prev_balance\":\"0.00\",\"prev_net_worth\":\"0.00\",\"credit_amount\":41,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:01:07','2026-03-08 07:01:07'),(19,10,NULL,NULL,NULL,NULL,NULL,'[15]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:01:46','2026-03-08 07:01:46'),(20,10,NULL,NULL,NULL,NULL,6.00,'[42]','[{\"id\":42,\"prev_balance\":\"0.00\",\"prev_net_worth\":\"0.00\",\"credit_amount\":6,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:01:46','2026-03-08 07:01:46'),(21,11,NULL,NULL,NULL,NULL,NULL,'[16]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:02:19','2026-03-08 07:02:19'),(22,11,NULL,NULL,NULL,NULL,45.00,'[43]','[{\"id\":43,\"prev_balance\":\"0.00\",\"prev_net_worth\":\"0.00\",\"credit_amount\":45,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:02:19','2026-03-08 07:02:19'),(23,12,NULL,NULL,NULL,NULL,NULL,'[17]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:02:52','2026-03-08 07:02:52'),(24,12,NULL,NULL,NULL,NULL,51.00,'[44]','[{\"id\":44,\"prev_balance\":\"0.00\",\"prev_net_worth\":\"0.00\",\"credit_amount\":51,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:02:52','2026-03-08 07:02:52'),(25,13,NULL,NULL,NULL,NULL,NULL,'[19]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:03:32','2026-03-08 07:03:32'),(26,13,NULL,NULL,NULL,NULL,11.00,'[45]','[{\"id\":45,\"prev_balance\":\"0.00\",\"prev_net_worth\":\"0.00\",\"credit_amount\":11,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:03:32','2026-03-08 07:03:32'),(27,14,NULL,NULL,NULL,NULL,NULL,'[20]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:04:13','2026-03-08 07:04:13'),(28,14,NULL,NULL,NULL,NULL,2000.00,'[46]','[{\"id\":46,\"prev_balance\":\"0.00\",\"prev_net_worth\":\"0.00\",\"credit_amount\":2000,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:04:13','2026-03-08 07:04:13'),(29,15,NULL,NULL,NULL,NULL,NULL,'[21]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:04:51','2026-03-08 07:04:51'),(30,15,NULL,NULL,NULL,NULL,10.00,'[47]','[{\"id\":47,\"prev_balance\":\"0.00\",\"prev_net_worth\":\"0.00\",\"credit_amount\":10,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:04:51','2026-03-08 07:04:51'),(31,16,NULL,NULL,NULL,NULL,NULL,'[23]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:05:28','2026-03-08 07:05:28'),(32,16,NULL,NULL,NULL,NULL,1.00,'[48]','[{\"id\":48,\"prev_balance\":\"0.00\",\"prev_net_worth\":\"0.00\",\"credit_amount\":1,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:05:28','2026-03-08 07:05:28'),(33,17,NULL,NULL,NULL,NULL,NULL,'[24]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:07:24','2026-03-08 07:07:24'),(34,17,NULL,NULL,NULL,NULL,4051.00,'[49]','[{\"id\":49,\"prev_balance\":\"0.00\",\"prev_net_worth\":\"0.00\",\"credit_amount\":4051,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:07:24','2026-03-08 07:07:24'),(35,18,NULL,NULL,NULL,NULL,NULL,'[26]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:08:08','2026-03-08 07:08:08'),(36,18,NULL,NULL,NULL,NULL,10000.00,'[50]','[{\"id\":50,\"prev_balance\":\"0.00\",\"prev_net_worth\":\"0.00\",\"credit_amount\":10000,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:08:08','2026-03-08 07:08:08'),(37,19,NULL,NULL,NULL,NULL,NULL,'[28]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:08:43','2026-03-08 07:08:43'),(38,19,NULL,NULL,NULL,NULL,7000.00,'[51]','[{\"id\":51,\"prev_balance\":\"0.00\",\"prev_net_worth\":\"0.00\",\"credit_amount\":7000,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:08:43','2026-03-08 07:08:43'),(39,20,NULL,NULL,NULL,NULL,NULL,'[30]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:09:23','2026-03-08 07:09:23'),(40,20,NULL,NULL,NULL,NULL,102.00,'[52]','[{\"id\":52,\"prev_balance\":\"0.00\",\"prev_net_worth\":\"0.00\",\"credit_amount\":102,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:09:23','2026-03-08 07:09:23'),(41,21,NULL,NULL,NULL,NULL,NULL,'[31]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:10:12','2026-03-08 07:10:12'),(42,21,NULL,NULL,NULL,NULL,61.00,'[53]','[{\"id\":53,\"prev_balance\":\"0.00\",\"prev_net_worth\":\"0.00\",\"credit_amount\":61,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:10:12','2026-03-08 07:10:12'),(43,22,NULL,NULL,NULL,NULL,NULL,'[54,2]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:48:15','2026-03-08 07:48:15'),(44,22,NULL,NULL,NULL,NULL,55050.00,'[86]','[{\"id\":86,\"prev_balance\":\"334077.00\",\"prev_net_worth\":\"334077.00\",\"credit_amount\":55050,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:48:15','2026-03-08 07:48:15'),(45,23,NULL,NULL,NULL,NULL,NULL,'[55,33,3]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:48:56','2026-03-08 07:48:56'),(46,23,NULL,NULL,NULL,NULL,4225.00,'[87]','[{\"id\":87,\"prev_balance\":\"1284028.00\",\"prev_net_worth\":\"1284060.00\",\"credit_amount\":4225,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:48:56','2026-03-08 07:48:56'),(47,24,NULL,NULL,NULL,NULL,NULL,'[57,4]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:50:02','2026-03-08 07:50:02'),(48,24,NULL,NULL,NULL,NULL,14975.00,'[88]','[{\"id\":88,\"prev_balance\":\"99632.00\",\"prev_net_worth\":\"99632.00\",\"credit_amount\":14975,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:50:02','2026-03-08 07:50:02'),(49,25,NULL,NULL,NULL,NULL,NULL,'[61,37,8]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:50:51','2026-03-08 07:50:51'),(50,25,NULL,NULL,NULL,NULL,7950.00,'[89]','[{\"id\":89,\"prev_balance\":\"207221.00\",\"prev_net_worth\":\"208221.00\",\"credit_amount\":7950,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:50:51','2026-03-08 07:50:51'),(51,26,NULL,NULL,NULL,NULL,NULL,'[65,40,12]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:51:29','2026-03-08 07:51:29'),(52,26,NULL,NULL,NULL,NULL,15000.00,'[90]','[{\"id\":90,\"prev_balance\":\"172859.00\",\"prev_net_worth\":\"172883.00\",\"credit_amount\":15000,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:51:29','2026-03-08 07:51:29'),(53,27,NULL,NULL,NULL,NULL,NULL,'[67,41,14]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:52:05','2026-03-08 07:52:05'),(54,27,NULL,NULL,NULL,NULL,30316.00,'[91]','[{\"id\":91,\"prev_balance\":\"238104.00\",\"prev_net_worth\":\"238145.00\",\"credit_amount\":30316,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:52:05','2026-03-08 07:52:05'),(55,28,NULL,NULL,NULL,NULL,NULL,'[74,47,21]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:52:44','2026-03-08 07:52:44'),(56,28,NULL,NULL,NULL,NULL,55937.00,'[92]','[{\"id\":92,\"prev_balance\":\"176186.00\",\"prev_net_worth\":\"176196.00\",\"credit_amount\":55937,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:52:44','2026-03-08 07:52:44'),(57,29,NULL,NULL,NULL,NULL,NULL,'[76,48,23]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:53:21','2026-03-08 07:53:21'),(58,29,NULL,NULL,NULL,NULL,35000.00,'[93]','[{\"id\":93,\"prev_balance\":\"125290.00\",\"prev_net_worth\":\"125291.00\",\"credit_amount\":35000,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:53:21','2026-03-08 07:53:21'),(59,30,NULL,NULL,NULL,NULL,NULL,'[77,49,24]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:53:55','2026-03-08 07:53:55'),(60,30,NULL,NULL,NULL,NULL,7300.00,'[94]','[{\"id\":94,\"prev_balance\":\"283130.00\",\"prev_net_worth\":\"287181.00\",\"credit_amount\":7300,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:53:55','2026-03-08 07:53:55'),(61,31,NULL,NULL,NULL,NULL,NULL,'[79,50,26]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:54:32','2026-03-08 07:54:32'),(62,31,NULL,NULL,NULL,NULL,18000.00,'[95]','[{\"id\":95,\"prev_balance\":\"231854.00\",\"prev_net_worth\":\"241854.00\",\"credit_amount\":18000,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:54:32','2026-03-08 07:54:32'),(63,32,NULL,NULL,NULL,NULL,NULL,'[81,51,28]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:55:19','2026-03-08 07:55:19'),(64,32,NULL,NULL,NULL,NULL,29718.00,'[96]','[{\"id\":96,\"prev_balance\":\"217381.00\",\"prev_net_worth\":\"224381.00\",\"credit_amount\":29718,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:55:19','2026-03-08 07:55:19'),(65,33,NULL,NULL,NULL,NULL,NULL,'[83,52,30]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:56:37','2026-03-08 07:56:37'),(66,33,NULL,NULL,NULL,NULL,442.00,'[97]','[{\"id\":97,\"prev_balance\":\"4576.00\",\"prev_net_worth\":\"4678.00\",\"credit_amount\":442,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:56:37','2026-03-08 07:56:37'),(67,34,NULL,NULL,NULL,NULL,NULL,'[84,53,31]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:57:13','2026-03-08 07:57:13'),(68,34,NULL,NULL,NULL,NULL,6950.00,'[98]','[{\"id\":98,\"prev_balance\":\"143290.00\",\"prev_net_worth\":\"143351.00\",\"credit_amount\":6950,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:57:13','2026-03-08 07:57:13'),(69,35,NULL,NULL,NULL,NULL,NULL,'[85,32]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:57:51','2026-03-08 07:57:51'),(70,35,NULL,NULL,NULL,NULL,15750.00,'[99]','[{\"id\":99,\"prev_balance\":\"87794.00\",\"prev_net_worth\":\"87794.00\",\"credit_amount\":15750,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 07:57:52','2026-03-08 07:57:52'),(71,36,NULL,NULL,NULL,NULL,NULL,'[101,87,55,33,3]',NULL,NULL,NULL,10,63.00,0,0,NULL,NULL,'2026-03-08 08:06:37','2026-03-08 08:06:37'),(72,36,3,2,NULL,NULL,-63.00,'[109]','[{\"id\":109,\"prev_balance\":\"1284028.00\",\"prev_net_worth\":\"36248.00\",\"credit_amount\":0,\"debit_amount\":63}]',NULL,NULL,10,NULL,1,0,NULL,NULL,'2026-03-08 08:06:37','2026-03-08 08:06:37'),(73,37,NULL,NULL,NULL,NULL,NULL,'[68,42,15]',NULL,NULL,NULL,11,5256.00,0,0,NULL,NULL,'2026-03-08 08:07:54','2026-03-08 08:07:54'),(74,37,15,3,NULL,NULL,-5256.00,'[110]','[{\"id\":110,\"prev_balance\":\"67847.00\",\"prev_net_worth\":\"67853.00\",\"credit_amount\":0,\"debit_amount\":5256}]',NULL,NULL,11,NULL,1,0,NULL,NULL,'2026-03-08 08:07:54','2026-03-08 08:07:54'),(75,38,NULL,NULL,NULL,NULL,NULL,'[70,44,17]',NULL,NULL,NULL,12,5256.00,0,0,NULL,NULL,'2026-03-08 08:08:52','2026-03-08 08:08:52'),(76,38,17,2,NULL,NULL,-5256.00,'[111]','[{\"id\":111,\"prev_balance\":\"39103.00\",\"prev_net_worth\":\"39154.00\",\"credit_amount\":0,\"debit_amount\":5256}]',NULL,NULL,12,NULL,1,0,NULL,NULL,'2026-03-08 08:08:52','2026-03-08 08:08:52'),(77,39,NULL,NULL,NULL,NULL,NULL,'[107,72,45,19]',NULL,NULL,NULL,13,54.00,0,0,NULL,NULL,'2026-03-08 08:09:39','2026-03-08 08:09:39'),(78,39,19,3,NULL,NULL,-54.00,'[112]','[{\"id\":112,\"prev_balance\":\"119931.00\",\"prev_net_worth\":\"85259.00\",\"credit_amount\":0,\"debit_amount\":54}]',NULL,'[120]',13,NULL,1,1,'2026-03-08 08:39:56',1,'2026-03-08 08:09:39','2026-03-08 08:39:56'),(79,40,NULL,NULL,NULL,NULL,NULL,'[73,46,20]',NULL,NULL,NULL,14,5256.00,0,0,NULL,NULL,'2026-03-08 08:30:30','2026-03-08 08:30:30'),(80,40,20,3,NULL,NULL,-5256.00,'[113]','[{\"id\":113,\"prev_balance\":\"349881.00\",\"prev_net_worth\":\"351881.00\",\"credit_amount\":0,\"debit_amount\":5256}]',NULL,'[123]',14,NULL,1,1,'2026-03-08 08:43:23',1,'2026-03-08 08:30:30','2026-03-08 08:43:23'),(81,41,NULL,NULL,NULL,NULL,NULL,'[93,76,48,23]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 08:31:53','2026-03-08 08:31:53'),(82,41,NULL,NULL,NULL,NULL,2000.00,'[114]','[{\"id\":114,\"prev_balance\":\"125290.00\",\"prev_net_worth\":\"160291.00\",\"credit_amount\":2000,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 08:31:54','2026-03-08 08:31:54'),(83,42,NULL,NULL,NULL,NULL,NULL,'[108,96,81,51,28]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 08:33:16','2026-03-08 08:33:16'),(84,42,NULL,NULL,NULL,NULL,6100.00,'[115]','[{\"id\":115,\"prev_balance\":\"217381.00\",\"prev_net_worth\":\"82379.00\",\"credit_amount\":6100,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 08:33:16','2026-03-08 08:33:16'),(85,43,NULL,NULL,NULL,NULL,NULL,'[97,83,52,30]',NULL,NULL,NULL,15,5256.00,0,0,NULL,NULL,'2026-03-08 08:34:04','2026-03-08 08:34:04'),(86,43,30,2,NULL,NULL,-5256.00,'[116]','[{\"id\":116,\"prev_balance\":\"4576.00\",\"prev_net_worth\":\"5120.00\",\"credit_amount\":0,\"debit_amount\":5256}]',NULL,NULL,15,NULL,1,0,NULL,NULL,'2026-03-08 08:34:04','2026-03-08 08:34:04'),(87,44,NULL,NULL,NULL,NULL,NULL,'[98,84,53,31]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 08:35:17','2026-03-08 08:35:17'),(88,44,NULL,NULL,NULL,NULL,17380.00,'[117]','[{\"id\":117,\"prev_balance\":\"143290.00\",\"prev_net_worth\":\"150301.00\",\"credit_amount\":17380,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 08:35:17','2026-03-08 08:35:17'),(89,45,NULL,NULL,NULL,NULL,NULL,'[99,85,32]',NULL,NULL,NULL,16,5050.00,0,0,NULL,NULL,'2026-03-08 08:36:00','2026-03-08 08:36:00'),(90,45,32,2,NULL,NULL,-5050.00,'[118]','[{\"id\":118,\"prev_balance\":\"87794.00\",\"prev_net_worth\":\"103544.00\",\"credit_amount\":0,\"debit_amount\":5050}]',NULL,NULL,16,NULL,1,0,NULL,NULL,'2026-03-08 08:36:00','2026-03-08 08:36:00'),(91,46,NULL,NULL,NULL,NULL,NULL,'[110,68,42,15]',NULL,NULL,NULL,17,5256.00,0,0,NULL,NULL,'2026-03-08 08:38:27','2026-03-08 08:38:27'),(92,46,15,2,NULL,NULL,-5256.00,'[119]','[{\"id\":119,\"prev_balance\":\"67847.00\",\"prev_net_worth\":\"62597.00\",\"credit_amount\":0,\"debit_amount\":5256}]',NULL,NULL,17,NULL,1,0,NULL,NULL,'2026-03-08 08:38:27','2026-03-08 08:38:27'),(93,47,NULL,NULL,NULL,NULL,NULL,'[120,112,107,72,45,19]',NULL,NULL,NULL,18,54.00,0,0,NULL,NULL,'2026-03-08 08:41:13','2026-03-08 08:41:13'),(94,47,19,2,NULL,NULL,-54.00,'[121]','[{\"id\":121,\"prev_balance\":\"119931.00\",\"prev_net_worth\":\"85259.00\",\"credit_amount\":0,\"debit_amount\":54}]',NULL,NULL,18,NULL,1,0,NULL,NULL,'2026-03-08 08:41:13','2026-03-08 08:41:13'),(95,48,NULL,NULL,NULL,NULL,NULL,'[113,73,46,20]',NULL,NULL,NULL,19,5256.00,0,0,NULL,NULL,'2026-03-08 08:43:01','2026-03-08 08:43:01'),(96,48,20,2,NULL,NULL,-5256.00,'[122]','[{\"id\":122,\"prev_balance\":\"349881.00\",\"prev_net_worth\":\"346625.00\",\"credit_amount\":0,\"debit_amount\":5256}]',NULL,NULL,19,NULL,1,0,NULL,NULL,'2026-03-08 08:43:01','2026-03-08 08:43:01'),(97,49,NULL,NULL,NULL,NULL,NULL,'[100,86,54,2]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 08:44:27','2026-03-08 08:44:27'),(98,49,NULL,NULL,NULL,NULL,1556.00,'[124]','[{\"id\":124,\"prev_balance\":\"334077.00\",\"prev_net_worth\":\"1807.00\",\"credit_amount\":1556,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 08:44:28','2026-03-08 08:44:28'),(99,50,NULL,NULL,NULL,NULL,NULL,'[109,101,87,55,33,3]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 08:45:10','2026-03-08 08:45:10'),(100,50,NULL,NULL,NULL,NULL,5000.00,'[125]','[{\"id\":125,\"prev_balance\":\"1284028.00\",\"prev_net_worth\":\"36185.00\",\"credit_amount\":5000,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 08:45:10','2026-03-08 08:45:10'),(101,51,NULL,NULL,NULL,NULL,NULL,'[102,88,57,4]',NULL,NULL,NULL,20,3938.00,0,0,NULL,NULL,'2026-03-08 08:47:09','2026-03-08 08:47:09'),(102,51,4,6,NULL,NULL,-3938.00,'[126]','[{\"id\":126,\"prev_balance\":\"99632.00\",\"prev_net_worth\":\"-25982.00\",\"credit_amount\":0,\"debit_amount\":3938}]',NULL,NULL,20,NULL,1,0,NULL,NULL,'2026-03-08 08:47:09','2026-03-08 08:47:09'),(103,52,NULL,NULL,NULL,NULL,NULL,'[58,5]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 08:47:45','2026-03-08 08:47:45'),(104,52,NULL,NULL,NULL,NULL,4000.00,'[127]','[{\"id\":127,\"prev_balance\":\"52183.00\",\"prev_net_worth\":\"52183.00\",\"credit_amount\":4000,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 08:47:46','2026-03-08 08:47:46'),(105,53,NULL,NULL,NULL,NULL,NULL,'[59,35,6]',NULL,NULL,NULL,21,4462.00,0,0,NULL,NULL,'2026-03-08 08:48:51','2026-03-08 08:48:51'),(106,53,6,6,NULL,NULL,-4462.00,'[128]','[{\"id\":128,\"prev_balance\":\"461103.00\",\"prev_net_worth\":\"463108.00\",\"credit_amount\":0,\"debit_amount\":4462}]',NULL,NULL,21,NULL,1,0,NULL,NULL,'2026-03-08 08:48:51','2026-03-08 08:48:51'),(107,54,NULL,NULL,NULL,NULL,NULL,'[89,61,37,8]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 08:49:51','2026-03-08 08:49:51'),(108,54,NULL,NULL,NULL,NULL,4000.00,'[129]','[{\"id\":129,\"prev_balance\":\"207221.00\",\"prev_net_worth\":\"216171.00\",\"credit_amount\":4000,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 08:49:51','2026-03-08 08:49:51'),(109,55,NULL,NULL,NULL,NULL,NULL,'[64,39,11]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 08:52:54','2026-03-08 08:52:54'),(110,55,NULL,NULL,NULL,NULL,4000.00,'[130]','[{\"id\":130,\"prev_balance\":\"78447.00\",\"prev_net_worth\":\"78457.00\",\"credit_amount\":4000,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 08:52:54','2026-03-08 08:52:54'),(111,56,NULL,NULL,NULL,NULL,NULL,'[90,65,40,12]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 08:54:11','2026-03-08 08:54:11'),(112,56,NULL,NULL,NULL,NULL,4000.00,'[131]','[{\"id\":131,\"prev_balance\":\"172859.00\",\"prev_net_worth\":\"187883.00\",\"credit_amount\":4000,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 08:54:11','2026-03-08 08:54:11'),(113,57,NULL,NULL,NULL,NULL,NULL,'[104,91,67,41,14]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 08:54:53','2026-03-08 08:54:53'),(114,57,NULL,NULL,NULL,NULL,592.00,'[132]','[{\"id\":132,\"prev_balance\":\"238104.00\",\"prev_net_worth\":\"232316.00\",\"credit_amount\":592,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 08:54:53','2026-03-08 08:54:53'),(115,58,NULL,NULL,NULL,NULL,NULL,'[119,110,68,42,15]',NULL,NULL,NULL,22,4462.00,0,0,NULL,NULL,'2026-03-08 08:55:48','2026-03-08 08:55:48'),(116,58,15,6,NULL,NULL,-4462.00,'[133]','[{\"id\":133,\"prev_balance\":\"67847.00\",\"prev_net_worth\":\"57341.00\",\"credit_amount\":0,\"debit_amount\":4462}]',NULL,NULL,22,NULL,1,0,NULL,NULL,'2026-03-08 08:55:48','2026-03-08 08:55:48'),(117,59,NULL,NULL,NULL,NULL,NULL,'[105,69,43,16]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 08:57:01','2026-03-08 08:57:01'),(118,59,NULL,NULL,NULL,NULL,40.00,'[134]','[{\"id\":134,\"prev_balance\":\"290494.00\",\"prev_net_worth\":\"31024.00\",\"credit_amount\":40,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 08:57:01','2026-03-08 08:57:01'),(119,60,NULL,NULL,NULL,NULL,NULL,'[111,70,44,17]',NULL,NULL,NULL,23,4462.00,0,0,NULL,NULL,'2026-03-08 08:57:46','2026-03-08 08:57:46'),(120,60,17,6,NULL,NULL,-4462.00,'[135]','[{\"id\":135,\"prev_balance\":\"39103.00\",\"prev_net_worth\":\"33898.00\",\"credit_amount\":0,\"debit_amount\":4462}]',NULL,NULL,23,NULL,1,0,NULL,NULL,'2026-03-08 08:57:46','2026-03-08 08:57:46'),(121,61,NULL,NULL,NULL,NULL,NULL,'[92,74,47,21]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 08:58:39','2026-03-08 08:58:39'),(122,61,NULL,NULL,NULL,NULL,4040.00,'[136]','[{\"id\":136,\"prev_balance\":\"176186.00\",\"prev_net_worth\":\"232133.00\",\"credit_amount\":4040,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 08:58:39','2026-03-08 08:58:39'),(123,62,NULL,NULL,NULL,NULL,NULL,'[114,93,76,48,23]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 08:59:20','2026-03-08 08:59:20'),(124,62,NULL,NULL,NULL,NULL,4000.00,'[137]','[{\"id\":137,\"prev_balance\":\"125290.00\",\"prev_net_worth\":\"162291.00\",\"credit_amount\":4000,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 08:59:20','2026-03-08 08:59:20'),(125,63,NULL,NULL,NULL,NULL,NULL,'[94,77,49,24]',NULL,NULL,NULL,24,4462.00,0,0,NULL,NULL,'2026-03-08 09:00:59','2026-03-08 09:00:59'),(126,63,24,6,NULL,NULL,-4462.00,'[138]','[{\"id\":138,\"prev_balance\":\"283130.00\",\"prev_net_worth\":\"294481.00\",\"credit_amount\":0,\"debit_amount\":4462}]',NULL,NULL,24,NULL,1,0,NULL,NULL,'2026-03-08 09:00:59','2026-03-08 09:00:59'),(127,64,NULL,NULL,NULL,NULL,NULL,'[115,108,96,81,51,28]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 09:01:46','2026-03-08 09:01:46'),(128,64,NULL,NULL,NULL,NULL,686.00,'[139]','[{\"id\":139,\"prev_balance\":\"217381.00\",\"prev_net_worth\":\"88479.00\",\"credit_amount\":686,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 09:01:46','2026-03-08 09:01:46'),(129,65,NULL,NULL,NULL,NULL,NULL,'[116,97,83,52,30]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 09:03:34','2026-03-08 09:03:34'),(130,65,NULL,NULL,NULL,NULL,7000.00,'[140]','[{\"id\":140,\"prev_balance\":\"4576.00\",\"prev_net_worth\":\"-136.00\",\"credit_amount\":7000,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 09:03:34','2026-03-08 09:03:34'),(131,66,NULL,NULL,NULL,NULL,NULL,'[117,98,84,53,31]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 09:05:52','2026-03-08 09:05:52'),(132,66,NULL,NULL,NULL,NULL,5000.00,'[141]','[{\"id\":141,\"prev_balance\":\"143290.00\",\"prev_net_worth\":\"167681.00\",\"credit_amount\":5000,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 09:05:52','2026-03-08 09:05:52'),(133,67,NULL,NULL,NULL,NULL,NULL,'[118,99,85,32]',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 09:09:24','2026-03-08 09:09:24'),(134,67,NULL,NULL,NULL,NULL,4000.00,'[142]','[{\"id\":142,\"prev_balance\":\"87794.00\",\"prev_net_worth\":\"98494.00\",\"credit_amount\":4000,\"debit_amount\":0}]',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2026-03-08 09:09:25','2026-03-08 09:09:25');
/*!40000 ALTER TABLE `receivable_effects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `receivable_year`
--

DROP TABLE IF EXISTS `receivable_year`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `receivable_year` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `year_id` bigint(20) unsigned NOT NULL,
  `receivable_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `receivable_year_year_id_foreign` (`year_id`),
  KEY `receivable_year_receivable_id_foreign` (`receivable_id`),
  CONSTRAINT `receivable_year_receivable_id_foreign` FOREIGN KEY (`receivable_id`) REFERENCES `receivables` (`id`),
  CONSTRAINT `receivable_year_year_id_foreign` FOREIGN KEY (`year_id`) REFERENCES `years` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `receivable_year`
--

LOCK TABLES `receivable_year` WRITE;
/*!40000 ALTER TABLE `receivable_year` DISABLE KEYS */;
INSERT INTO `receivable_year` VALUES (1,2,1,'2026-03-08 06:55:39','2026-03-08 06:55:39'),(2,2,2,'2026-03-08 06:56:47','2026-03-08 06:56:47'),(3,2,3,'2026-03-08 06:57:21','2026-03-08 06:57:21'),(4,2,4,'2026-03-08 06:57:54','2026-03-08 06:57:54'),(5,2,5,'2026-03-08 06:58:29','2026-03-08 06:58:29'),(6,2,6,'2026-03-08 06:59:07','2026-03-08 06:59:07'),(7,2,7,'2026-03-08 06:59:46','2026-03-08 06:59:46'),(8,2,8,'2026-03-08 07:00:32','2026-03-08 07:00:32'),(9,2,9,'2026-03-08 07:01:07','2026-03-08 07:01:07'),(10,2,10,'2026-03-08 07:01:46','2026-03-08 07:01:46'),(11,2,11,'2026-03-08 07:02:19','2026-03-08 07:02:19'),(12,2,12,'2026-03-08 07:02:52','2026-03-08 07:02:52'),(13,2,13,'2026-03-08 07:03:32','2026-03-08 07:03:32'),(14,2,14,'2026-03-08 07:04:13','2026-03-08 07:04:13'),(15,2,15,'2026-03-08 07:04:51','2026-03-08 07:04:51'),(16,2,16,'2026-03-08 07:05:28','2026-03-08 07:05:28'),(17,2,17,'2026-03-08 07:07:24','2026-03-08 07:07:24'),(18,2,18,'2026-03-08 07:08:08','2026-03-08 07:08:08'),(19,2,19,'2026-03-08 07:08:43','2026-03-08 07:08:43'),(20,2,20,'2026-03-08 07:09:23','2026-03-08 07:09:23'),(21,2,21,'2026-03-08 07:10:12','2026-03-08 07:10:12'),(22,2,22,'2026-03-08 07:48:15','2026-03-08 07:48:15'),(23,2,23,'2026-03-08 07:48:56','2026-03-08 07:48:56'),(24,2,24,'2026-03-08 07:50:02','2026-03-08 07:50:02'),(25,2,25,'2026-03-08 07:50:51','2026-03-08 07:50:51'),(26,2,26,'2026-03-08 07:51:29','2026-03-08 07:51:29'),(27,2,27,'2026-03-08 07:52:05','2026-03-08 07:52:05'),(28,2,28,'2026-03-08 07:52:44','2026-03-08 07:52:44'),(29,2,29,'2026-03-08 07:53:21','2026-03-08 07:53:21'),(30,2,30,'2026-03-08 07:53:55','2026-03-08 07:53:55'),(31,2,31,'2026-03-08 07:54:32','2026-03-08 07:54:32'),(32,2,32,'2026-03-08 07:55:19','2026-03-08 07:55:19'),(33,2,33,'2026-03-08 07:56:37','2026-03-08 07:56:37'),(34,2,34,'2026-03-08 07:57:13','2026-03-08 07:57:13'),(35,2,35,'2026-03-08 07:57:52','2026-03-08 07:57:52'),(36,2,36,'2026-03-08 08:06:37','2026-03-08 08:06:37'),(37,2,37,'2026-03-08 08:07:54','2026-03-08 08:07:54'),(38,2,38,'2026-03-08 08:08:52','2026-03-08 08:08:52'),(39,2,39,'2026-03-08 08:09:39','2026-03-08 08:09:39'),(40,2,40,'2026-03-08 08:30:30','2026-03-08 08:30:30'),(41,2,41,'2026-03-08 08:31:54','2026-03-08 08:31:54'),(42,2,42,'2026-03-08 08:33:16','2026-03-08 08:33:16'),(43,2,43,'2026-03-08 08:34:04','2026-03-08 08:34:04'),(44,2,44,'2026-03-08 08:35:17','2026-03-08 08:35:17'),(45,2,45,'2026-03-08 08:36:00','2026-03-08 08:36:00'),(46,2,46,'2026-03-08 08:38:27','2026-03-08 08:38:27'),(47,2,47,'2026-03-08 08:41:13','2026-03-08 08:41:13'),(48,2,48,'2026-03-08 08:43:01','2026-03-08 08:43:01'),(49,2,49,'2026-03-08 08:44:28','2026-03-08 08:44:28'),(50,2,50,'2026-03-08 08:45:10','2026-03-08 08:45:10'),(51,2,51,'2026-03-08 08:47:09','2026-03-08 08:47:09'),(52,2,52,'2026-03-08 08:47:45','2026-03-08 08:47:45'),(53,2,53,'2026-03-08 08:48:51','2026-03-08 08:48:51'),(54,2,54,'2026-03-08 08:49:51','2026-03-08 08:49:51'),(55,2,55,'2026-03-08 08:52:54','2026-03-08 08:52:54'),(56,2,56,'2026-03-08 08:54:11','2026-03-08 08:54:11'),(57,2,57,'2026-03-08 08:54:53','2026-03-08 08:54:53'),(58,2,58,'2026-03-08 08:55:48','2026-03-08 08:55:48'),(59,2,59,'2026-03-08 08:57:01','2026-03-08 08:57:01'),(60,2,60,'2026-03-08 08:57:46','2026-03-08 08:57:46'),(61,2,61,'2026-03-08 08:58:39','2026-03-08 08:58:39'),(62,2,62,'2026-03-08 08:59:20','2026-03-08 08:59:20'),(63,2,63,'2026-03-08 09:00:59','2026-03-08 09:00:59'),(64,2,64,'2026-03-08 09:01:46','2026-03-08 09:01:46'),(65,2,65,'2026-03-08 09:03:34','2026-03-08 09:03:34'),(66,2,66,'2026-03-08 09:05:52','2026-03-08 09:05:52'),(67,2,67,'2026-03-08 09:09:24','2026-03-08 09:09:24');
/*!40000 ALTER TABLE `receivable_year` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `receivables`
--

DROP TABLE IF EXISTS `receivables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `receivables` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `account_id` bigint(20) unsigned NOT NULL,
  `amount_contributed` decimal(10,2) NOT NULL,
  `payment_method` varchar(255) NOT NULL DEFAULT 'Bank Transfer',
  `from_savings` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `receivables_user_id_foreign` (`user_id`),
  KEY `receivables_account_id_foreign` (`account_id`),
  CONSTRAINT `receivables_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `receivables_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `receivables`
--

LOCK TABLES `receivables` WRITE;
/*!40000 ALTER TABLE `receivables` DISABLE KEYS */;
INSERT INTO `receivables` VALUES (1,3,4,32.00,'Bank Transfer',0,'2026-03-08 06:55:39','2026-03-08 06:55:39',NULL),(2,1,4,107.00,'Bank Transfer',0,'2026-03-08 06:56:47','2026-03-08 06:56:47',NULL),(3,6,4,2005.00,'Bank Transfer',0,'2026-03-08 06:57:21','2026-03-08 06:57:21',NULL),(4,7,4,125.00,'Bank Transfer',0,'2026-03-08 06:57:54','2026-03-08 06:57:54',NULL),(5,8,4,1000.00,'Bank Transfer',0,'2026-03-08 06:58:29','2026-03-08 06:58:29',NULL),(6,10,4,1021.00,'Bank Transfer',0,'2026-03-08 06:59:07','2026-03-08 06:59:07',NULL),(7,11,4,10.00,'Bank Transfer',0,'2026-03-08 06:59:46','2026-03-08 06:59:46',NULL),(8,12,4,24.00,'Bank Transfer',0,'2026-03-08 07:00:32','2026-03-08 07:00:32',NULL),(9,14,4,41.00,'Bank Transfer',0,'2026-03-08 07:01:06','2026-03-08 07:01:06',NULL),(10,15,4,6.00,'Bank Transfer',0,'2026-03-08 07:01:46','2026-03-08 07:01:46',NULL),(11,16,4,45.00,'Bank Transfer',0,'2026-03-08 07:02:19','2026-03-08 07:02:19',NULL),(12,17,4,51.00,'Bank Transfer',0,'2026-03-08 07:02:52','2026-03-08 07:02:52',NULL),(13,19,4,11.00,'Bank Transfer',0,'2026-03-08 07:03:32','2026-03-08 07:03:32',NULL),(14,20,4,2000.00,'Bank Transfer',0,'2026-03-08 07:04:13','2026-03-08 07:04:13',NULL),(15,21,4,10.00,'Bank Transfer',0,'2026-03-08 07:04:51','2026-03-08 07:04:51',NULL),(16,23,4,1.00,'Bank Transfer',0,'2026-03-08 07:05:28','2026-03-08 07:05:28',NULL),(17,24,4,4051.00,'Bank Transfer',0,'2026-03-08 07:07:24','2026-03-08 07:07:24',NULL),(18,26,4,10000.00,'Bank Transfer',0,'2026-03-08 07:08:08','2026-03-08 07:08:08',NULL),(19,28,4,7000.00,'Bank Transfer',0,'2026-03-08 07:08:43','2026-03-08 07:08:43',NULL),(20,30,4,102.00,'Bank Transfer',0,'2026-03-08 07:09:23','2026-03-08 07:09:23',NULL),(21,31,4,61.00,'Bank Transfer',0,'2026-03-08 07:10:12','2026-03-08 07:10:12',NULL),(22,2,3,55050.00,'Bank Transfer',0,'2026-03-08 07:48:15','2026-03-08 07:48:15',NULL),(23,3,3,4225.00,'Bank Transfer',0,'2026-03-08 07:48:56','2026-03-08 07:48:56',NULL),(24,4,3,14975.00,'Bank Transfer',0,'2026-03-08 07:50:02','2026-03-08 07:50:02',NULL),(25,8,3,7950.00,'Bank Transfer',0,'2026-03-08 07:50:51','2026-03-08 07:50:51',NULL),(26,12,3,15000.00,'Bank Transfer',0,'2026-03-08 07:51:29','2026-03-08 07:51:29',NULL),(27,14,3,30316.00,'Bank Transfer',0,'2026-03-08 07:52:05','2026-03-08 07:52:05',NULL),(28,21,3,55937.00,'Bank Transfer',0,'2026-03-08 07:52:44','2026-03-08 07:52:44',NULL),(29,23,3,35000.00,'Bank Transfer',0,'2026-03-08 07:53:21','2026-03-08 07:53:21',NULL),(30,24,3,7300.00,'Bank Transfer',0,'2026-03-08 07:53:55','2026-03-08 07:53:55',NULL),(31,26,3,18000.00,'Bank Transfer',0,'2026-03-08 07:54:32','2026-03-08 07:54:32',NULL),(32,28,3,29718.00,'Bank Transfer',0,'2026-03-08 07:55:19','2026-03-08 07:55:19',NULL),(33,30,3,442.00,'Bank Transfer',0,'2026-03-08 07:56:37','2026-03-08 07:56:37',NULL),(34,31,3,6950.00,'Bank Transfer',0,'2026-03-08 07:57:13','2026-03-08 07:57:13',NULL),(35,32,3,15750.00,'Bank Transfer',0,'2026-03-08 07:57:51','2026-03-08 07:57:51',NULL),(36,3,2,-63.00,'Bank Transfer',0,'2026-03-08 08:06:37','2026-03-08 08:06:37',NULL),(37,15,3,-5256.00,'Bank Transfer',0,'2026-03-08 08:07:54','2026-03-08 08:07:54',NULL),(38,17,2,-5256.00,'Bank Transfer',0,'2026-03-08 08:08:52','2026-03-08 08:08:52',NULL),(39,19,3,-54.00,'Bank Transfer',0,'2026-03-08 08:09:39','2026-03-08 08:39:56','2026-03-08 08:39:56'),(40,20,3,-5256.00,'Bank Transfer',0,'2026-03-08 08:30:30','2026-03-08 08:43:23','2026-03-08 08:43:23'),(41,23,2,2000.00,'Bank Transfer',0,'2026-03-08 08:31:53','2026-03-08 08:31:53',NULL),(42,28,2,6100.00,'Bank Transfer',0,'2026-03-08 08:33:16','2026-03-08 08:33:16',NULL),(43,30,2,-5256.00,'Bank Transfer',0,'2026-03-08 08:34:04','2026-03-08 08:34:04',NULL),(44,31,2,17380.00,'Bank Transfer',0,'2026-03-08 08:35:17','2026-03-08 08:35:17',NULL),(45,32,2,-5050.00,'Bank Transfer',0,'2026-03-08 08:36:00','2026-03-08 08:36:00',NULL),(46,15,2,-5256.00,'Bank Transfer',0,'2026-03-08 08:38:27','2026-03-08 08:38:27',NULL),(47,19,2,-54.00,'Bank Transfer',0,'2026-03-08 08:41:13','2026-03-08 08:41:13',NULL),(48,20,2,-5256.00,'Bank Transfer',0,'2026-03-08 08:43:01','2026-03-08 08:43:01',NULL),(49,2,6,1556.00,'Bank Transfer',0,'2026-03-08 08:44:27','2026-03-08 08:44:27',NULL),(50,3,6,5000.00,'Bank Transfer',0,'2026-03-08 08:45:10','2026-03-08 08:45:10',NULL),(51,4,6,-3938.00,'Bank Transfer',0,'2026-03-08 08:47:09','2026-03-08 08:47:09',NULL),(52,5,6,4000.00,'Bank Transfer',0,'2026-03-08 08:47:45','2026-03-08 08:47:45',NULL),(53,6,6,-4462.00,'Bank Transfer',0,'2026-03-08 08:48:51','2026-03-08 08:48:51',NULL),(54,8,6,4000.00,'Bank Transfer',0,'2026-03-08 08:49:51','2026-03-08 08:49:51',NULL),(55,11,6,4000.00,'Bank Transfer',0,'2026-03-08 08:52:54','2026-03-08 08:52:54',NULL),(56,12,6,4000.00,'Bank Transfer',0,'2026-03-08 08:54:11','2026-03-08 08:54:11',NULL),(57,14,6,592.00,'Bank Transfer',0,'2026-03-08 08:54:53','2026-03-08 08:54:53',NULL),(58,15,6,-4462.00,'Bank Transfer',0,'2026-03-08 08:55:48','2026-03-08 08:55:48',NULL),(59,16,6,40.00,'Bank Transfer',0,'2026-03-08 08:57:01','2026-03-08 08:57:01',NULL),(60,17,6,-4462.00,'Bank Transfer',0,'2026-03-08 08:57:46','2026-03-08 08:57:46',NULL),(61,21,6,4040.00,'Bank Transfer',0,'2026-03-08 08:58:39','2026-03-08 08:58:39',NULL),(62,23,6,4000.00,'Bank Transfer',0,'2026-03-08 08:59:20','2026-03-08 08:59:20',NULL),(63,24,6,-4462.00,'Bank Transfer',0,'2026-03-08 09:00:59','2026-03-08 09:00:59',NULL),(64,28,6,686.00,'Bank Transfer',0,'2026-03-08 09:01:46','2026-03-08 09:01:46',NULL),(65,30,6,7000.00,'Bank Transfer',0,'2026-03-08 09:03:34','2026-03-08 09:03:34',NULL),(66,31,6,5000.00,'Bank Transfer',0,'2026-03-08 09:05:52','2026-03-08 09:05:52',NULL),(67,32,6,4000.00,'Bank Transfer',0,'2026-03-08 09:09:24','2026-03-08 09:09:24',NULL);
/*!40000 ALTER TABLE `receivables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `savings`
--

DROP TABLE IF EXISTS `savings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `savings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `credit_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `debit_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `net_worth` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `savings_user_id_foreign` (`user_id`),
  CONSTRAINT `savings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=143 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `savings`
--

LOCK TABLES `savings` WRITE;
/*!40000 ALTER TABLE `savings` DISABLE KEYS */;
INSERT INTO `savings` VALUES (1,1,1000.00,0.00,0.00,0.00,'2026-03-08 06:49:25','2026-03-08 06:49:25'),(2,2,1000.00,0.00,0.00,0.00,'2026-03-08 06:49:25','2026-03-08 06:49:25'),(3,3,1000.00,0.00,0.00,0.00,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(4,4,1000.00,0.00,0.00,0.00,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(5,5,1000.00,0.00,0.00,0.00,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(6,6,1000.00,0.00,0.00,0.00,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(7,7,1000.00,0.00,0.00,0.00,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(8,8,1000.00,0.00,0.00,0.00,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(9,9,1000.00,0.00,0.00,0.00,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(10,10,3000.00,0.00,0.00,0.00,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(11,11,5000.00,0.00,0.00,0.00,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(12,12,5000.00,0.00,0.00,0.00,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(13,13,5000.00,0.00,0.00,0.00,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(14,14,5000.00,0.00,0.00,0.00,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(15,15,5000.00,0.00,0.00,0.00,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(16,16,10000.00,0.00,0.00,0.00,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(17,17,10000.00,0.00,0.00,0.00,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(18,18,10000.00,0.00,0.00,0.00,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(19,19,10000.00,0.00,0.00,0.00,'2026-03-08 06:49:27','2026-03-08 06:49:27'),(20,20,10000.00,0.00,0.00,0.00,'2026-03-08 06:49:27','2026-03-08 06:49:27'),(21,21,10000.00,0.00,0.00,0.00,'2026-03-08 06:49:27','2026-03-08 06:49:27'),(22,22,10000.00,0.00,0.00,0.00,'2026-03-08 06:49:27','2026-03-08 06:49:27'),(23,23,10000.00,0.00,0.00,0.00,'2026-03-08 06:49:27','2026-03-08 06:49:27'),(24,24,20000.00,0.00,0.00,0.00,'2026-03-08 06:49:27','2026-03-08 06:49:27'),(25,25,20000.00,0.00,0.00,0.00,'2026-03-08 06:49:27','2026-03-08 06:49:27'),(26,26,20000.00,0.00,0.00,0.00,'2026-03-08 06:49:27','2026-03-08 06:49:27'),(27,27,20000.00,0.00,0.00,0.00,'2026-03-08 06:49:27','2026-03-08 06:49:27'),(28,28,20000.00,0.00,0.00,0.00,'2026-03-08 06:49:27','2026-03-08 06:49:27'),(29,29,20000.00,0.00,0.00,0.00,'2026-03-08 06:49:27','2026-03-08 06:49:27'),(30,30,20000.00,0.00,0.00,0.00,'2026-03-08 06:49:27','2026-03-08 06:49:27'),(31,31,20000.00,0.00,0.00,0.00,'2026-03-08 06:49:27','2026-03-08 06:49:27'),(32,32,20000.00,0.00,0.00,0.00,'2026-03-08 06:49:27','2026-03-08 06:49:27'),(33,3,32.00,0.00,0.00,32.00,'2026-03-08 06:55:39','2026-03-08 06:55:39'),(34,1,107.00,0.00,0.00,107.00,'2026-03-08 06:56:47','2026-03-08 06:56:47'),(35,6,2005.00,0.00,0.00,2005.00,'2026-03-08 06:57:21','2026-03-08 06:57:21'),(36,7,125.00,0.00,0.00,125.00,'2026-03-08 06:57:54','2026-03-08 06:57:54'),(37,8,1000.00,0.00,0.00,1000.00,'2026-03-08 06:58:29','2026-03-08 06:58:29'),(38,10,1021.00,0.00,0.00,1021.00,'2026-03-08 06:59:07','2026-03-08 06:59:07'),(39,11,10.00,0.00,0.00,10.00,'2026-03-08 06:59:46','2026-03-08 06:59:46'),(40,12,24.00,0.00,0.00,24.00,'2026-03-08 07:00:32','2026-03-08 07:00:32'),(41,14,41.00,0.00,0.00,41.00,'2026-03-08 07:01:07','2026-03-08 07:01:07'),(42,15,6.00,0.00,0.00,6.00,'2026-03-08 07:01:46','2026-03-08 07:01:46'),(43,16,45.00,0.00,0.00,45.00,'2026-03-08 07:02:19','2026-03-08 07:02:19'),(44,17,51.00,0.00,0.00,51.00,'2026-03-08 07:02:52','2026-03-08 07:02:52'),(45,19,11.00,0.00,0.00,11.00,'2026-03-08 07:03:32','2026-03-08 07:03:32'),(46,20,2000.00,0.00,0.00,2000.00,'2026-03-08 07:04:13','2026-03-08 07:04:13'),(47,21,10.00,0.00,0.00,10.00,'2026-03-08 07:04:51','2026-03-08 07:04:51'),(48,23,1.00,0.00,0.00,1.00,'2026-03-08 07:05:28','2026-03-08 07:05:28'),(49,24,4051.00,0.00,0.00,4051.00,'2026-03-08 07:07:24','2026-03-08 07:07:24'),(50,26,10000.00,0.00,0.00,10000.00,'2026-03-08 07:08:08','2026-03-08 07:08:08'),(51,28,7000.00,0.00,0.00,7000.00,'2026-03-08 07:08:43','2026-03-08 07:08:43'),(52,30,102.00,0.00,0.00,102.00,'2026-03-08 07:09:23','2026-03-08 07:09:23'),(53,31,61.00,0.00,0.00,61.00,'2026-03-08 07:10:12','2026-03-08 07:10:12'),(54,2,334077.00,0.00,334077.00,334077.00,'2026-03-08 07:12:48','2026-03-08 07:12:48'),(55,3,1284028.00,0.00,1284028.00,1284060.00,'2026-03-08 07:13:19','2026-03-08 07:13:19'),(56,1,31963.00,0.00,31963.00,32070.00,'2026-03-08 07:13:50','2026-03-08 07:13:50'),(57,4,99632.00,0.00,99632.00,99632.00,'2026-03-08 07:14:16','2026-03-08 07:14:16'),(58,5,52183.00,0.00,52183.00,52183.00,'2026-03-08 07:14:47','2026-03-08 07:14:47'),(59,6,461103.00,0.00,461103.00,463108.00,'2026-03-08 07:15:17','2026-03-08 07:15:17'),(60,7,35925.00,0.00,35925.00,36050.00,'2026-03-08 07:16:00','2026-03-08 07:16:00'),(61,8,207221.00,0.00,207221.00,208221.00,'2026-03-08 07:16:37','2026-03-08 07:16:37'),(62,9,62682.00,0.00,62682.00,62682.00,'2026-03-08 07:31:14','2026-03-08 07:31:14'),(63,10,337760.00,0.00,337760.00,338781.00,'2026-03-08 07:31:47','2026-03-08 07:31:47'),(64,11,78447.00,0.00,78447.00,78457.00,'2026-03-08 07:32:56','2026-03-08 07:32:56'),(65,12,172859.00,0.00,172859.00,172883.00,'2026-03-08 07:33:32','2026-03-08 07:33:32'),(66,13,-4217.00,0.00,-4217.00,-4217.00,'2026-03-08 07:34:08','2026-03-08 07:34:08'),(67,14,238104.00,0.00,238104.00,238145.00,'2026-03-08 07:34:34','2026-03-08 07:34:34'),(68,15,67847.00,0.00,67847.00,67853.00,'2026-03-08 07:35:03','2026-03-08 07:35:03'),(69,16,290494.00,0.00,290494.00,290539.00,'2026-03-08 07:35:29','2026-03-08 07:35:29'),(70,17,39103.00,0.00,39103.00,39154.00,'2026-03-08 07:36:11','2026-03-08 07:36:11'),(71,18,-48508.00,0.00,-48508.00,-48508.00,'2026-03-08 07:37:22','2026-03-08 07:37:22'),(72,19,119931.00,0.00,119931.00,119942.00,'2026-03-08 07:38:38','2026-03-08 07:38:38'),(73,20,349881.00,0.00,349881.00,351881.00,'2026-03-08 07:39:08','2026-03-08 07:39:08'),(74,21,176186.00,0.00,176186.00,176196.00,'2026-03-08 07:39:48','2026-03-08 07:39:48'),(75,22,-34363.00,0.00,-34363.00,-34363.00,'2026-03-08 07:40:19','2026-03-08 07:40:19'),(76,23,125290.00,0.00,125290.00,125291.00,'2026-03-08 07:40:47','2026-03-08 07:40:47'),(77,24,283130.00,0.00,283130.00,287181.00,'2026-03-08 07:41:27','2026-03-08 07:41:27'),(78,25,64219.00,0.00,64219.00,64219.00,'2026-03-08 07:41:54','2026-03-08 07:41:54'),(79,26,231854.00,0.00,231854.00,241854.00,'2026-03-08 07:42:23','2026-03-08 07:42:23'),(80,27,-98904.00,0.00,-98904.00,-98904.00,'2026-03-08 07:42:56','2026-03-08 07:42:56'),(81,28,217381.00,0.00,217381.00,224381.00,'2026-03-08 07:44:29','2026-03-08 07:44:29'),(82,29,-3911.00,0.00,-3911.00,-3911.00,'2026-03-08 07:45:03','2026-03-08 07:45:03'),(83,30,4576.00,0.00,4576.00,4678.00,'2026-03-08 07:45:31','2026-03-08 07:45:31'),(84,31,143290.00,0.00,143290.00,143351.00,'2026-03-08 07:45:58','2026-03-08 07:45:58'),(85,32,87794.00,0.00,87794.00,87794.00,'2026-03-08 07:46:22','2026-03-08 07:46:22'),(86,2,55050.00,0.00,334077.00,389127.00,'2026-03-08 07:48:15','2026-03-08 07:48:15'),(87,3,4225.00,0.00,1284028.00,1288285.00,'2026-03-08 07:48:56','2026-03-08 07:48:56'),(88,4,14975.00,0.00,99632.00,114607.00,'2026-03-08 07:50:02','2026-03-08 07:50:02'),(89,8,7950.00,0.00,207221.00,216171.00,'2026-03-08 07:50:51','2026-03-08 07:50:51'),(90,12,15000.00,0.00,172859.00,187883.00,'2026-03-08 07:51:29','2026-03-08 07:51:29'),(91,14,30316.00,0.00,238104.00,268461.00,'2026-03-08 07:52:05','2026-03-08 07:52:05'),(92,21,55937.00,0.00,176186.00,232133.00,'2026-03-08 07:52:44','2026-03-08 07:52:44'),(93,23,35000.00,0.00,125290.00,160291.00,'2026-03-08 07:53:21','2026-03-08 07:53:21'),(94,24,7300.00,0.00,283130.00,294481.00,'2026-03-08 07:53:55','2026-03-08 07:53:55'),(95,26,18000.00,0.00,231854.00,259854.00,'2026-03-08 07:54:32','2026-03-08 07:54:32'),(96,28,29718.00,0.00,217381.00,254099.00,'2026-03-08 07:55:19','2026-03-08 07:55:19'),(97,30,442.00,0.00,4576.00,5120.00,'2026-03-08 07:56:37','2026-03-08 07:56:37'),(98,31,6950.00,0.00,143290.00,150301.00,'2026-03-08 07:57:13','2026-03-08 07:57:13'),(99,32,15750.00,0.00,87794.00,103544.00,'2026-03-08 07:57:52','2026-03-08 07:57:52'),(100,2,387320.00,0.00,334077.00,1807.00,'2026-03-08 07:59:49','2026-03-08 07:59:49'),(101,3,1252037.00,0.00,1284028.00,36248.00,'2026-03-08 08:00:19','2026-03-08 08:00:19'),(102,4,140589.00,0.00,99632.00,-25982.00,'2026-03-08 08:00:38','2026-03-08 08:00:38'),(103,10,166470.00,0.00,337760.00,172311.00,'2026-03-08 08:01:14','2026-03-08 08:01:14'),(104,14,36145.00,0.00,238104.00,232316.00,'2026-03-08 08:01:50','2026-03-08 08:01:50'),(105,16,259515.00,0.00,290494.00,31024.00,'2026-03-08 08:02:19','2026-03-08 08:02:19'),(106,18,144386.00,0.00,-48508.00,-192894.00,'2026-03-08 08:02:53','2026-03-08 08:02:53'),(107,19,34683.00,0.00,119931.00,85259.00,'2026-03-08 08:03:14','2026-03-08 08:03:14'),(108,28,171720.00,0.00,217381.00,82379.00,'2026-03-08 08:03:41','2026-03-08 08:03:41'),(109,3,0.00,63.00,1284028.00,36185.00,'2026-03-08 08:06:37','2026-03-08 08:06:37'),(110,15,0.00,5256.00,67847.00,62597.00,'2026-03-08 08:07:54','2026-03-08 08:07:54'),(111,17,0.00,5256.00,39103.00,33898.00,'2026-03-08 08:08:52','2026-03-08 08:08:52'),(112,19,0.00,54.00,119931.00,85205.00,'2026-03-08 08:09:39','2026-03-08 08:09:39'),(113,20,0.00,5256.00,349881.00,346625.00,'2026-03-08 08:30:30','2026-03-08 08:30:30'),(114,23,2000.00,0.00,125290.00,162291.00,'2026-03-08 08:31:54','2026-03-08 08:31:54'),(115,28,6100.00,0.00,217381.00,88479.00,'2026-03-08 08:33:16','2026-03-08 08:33:16'),(116,30,0.00,5256.00,4576.00,-136.00,'2026-03-08 08:34:04','2026-03-08 08:34:04'),(117,31,17380.00,0.00,143290.00,167681.00,'2026-03-08 08:35:17','2026-03-08 08:35:17'),(118,32,0.00,5050.00,87794.00,98494.00,'2026-03-08 08:36:00','2026-03-08 08:36:00'),(119,15,0.00,5256.00,67847.00,57341.00,'2026-03-08 08:38:27','2026-03-08 08:38:27'),(120,19,54.00,0.00,119931.00,85259.00,'2026-03-08 08:39:56','2026-03-08 08:39:56'),(121,19,0.00,54.00,119931.00,85205.00,'2026-03-08 08:41:13','2026-03-08 08:41:13'),(122,20,0.00,5256.00,349881.00,341369.00,'2026-03-08 08:43:01','2026-03-08 08:43:01'),(123,20,5256.00,0.00,349881.00,351881.00,'2026-03-08 08:43:23','2026-03-08 08:43:23'),(124,2,1556.00,0.00,334077.00,3363.00,'2026-03-08 08:44:28','2026-03-08 08:44:28'),(125,3,5000.00,0.00,1284028.00,41185.00,'2026-03-08 08:45:10','2026-03-08 08:45:10'),(126,4,0.00,3938.00,99632.00,-29920.00,'2026-03-08 08:47:09','2026-03-08 08:47:09'),(127,5,4000.00,0.00,52183.00,56183.00,'2026-03-08 08:47:46','2026-03-08 08:47:46'),(128,6,0.00,4462.00,461103.00,458646.00,'2026-03-08 08:48:51','2026-03-08 08:48:51'),(129,8,4000.00,0.00,207221.00,220171.00,'2026-03-08 08:49:51','2026-03-08 08:49:51'),(130,11,4000.00,0.00,78447.00,82457.00,'2026-03-08 08:52:54','2026-03-08 08:52:54'),(131,12,4000.00,0.00,172859.00,191883.00,'2026-03-08 08:54:11','2026-03-08 08:54:11'),(132,14,592.00,0.00,238104.00,232908.00,'2026-03-08 08:54:53','2026-03-08 08:54:53'),(133,15,0.00,4462.00,67847.00,52879.00,'2026-03-08 08:55:48','2026-03-08 08:55:48'),(134,16,40.00,0.00,290494.00,31064.00,'2026-03-08 08:57:01','2026-03-08 08:57:01'),(135,17,0.00,4462.00,39103.00,29436.00,'2026-03-08 08:57:46','2026-03-08 08:57:46'),(136,21,4040.00,0.00,176186.00,236173.00,'2026-03-08 08:58:39','2026-03-08 08:58:39'),(137,23,4000.00,0.00,125290.00,166291.00,'2026-03-08 08:59:20','2026-03-08 08:59:20'),(138,24,0.00,4462.00,283130.00,290019.00,'2026-03-08 09:00:59','2026-03-08 09:00:59'),(139,28,686.00,0.00,217381.00,89165.00,'2026-03-08 09:01:46','2026-03-08 09:01:46'),(140,30,7000.00,0.00,4576.00,6864.00,'2026-03-08 09:03:34','2026-03-08 09:03:34'),(141,31,5000.00,0.00,143290.00,172681.00,'2026-03-08 09:05:52','2026-03-08 09:05:52'),(142,32,4000.00,0.00,87794.00,102494.00,'2026-03-08 09:09:25','2026-03-08 09:09:25');
/*!40000 ALTER TABLE `savings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('sIDjh6T69MuKcS2Znh00kUaRktdlQmSu39xSwWFA',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','YTo4OntzOjY6Il90b2tlbiI7czo0MDoiYkd0ZDMweVVWSXprcXRkUkZmVDU5UElpTnRrS1MyNnN2OVIyY3NLaiI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjE6e3M6MzoidXJsIjtzOjQ0OiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvc3RhdGljLXJlYWQtb25seS10YWJsZSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7czoxNzoicGFzc3dvcmRfaGFzaF93ZWIiO3M6NjA6IiQyeSQxMiRFVjIuOUpyeUVLc1Z0SDNhV0ppcWwudW9PU0EwWUlqQ1VjWDBGMy5OMW1HZlJYMzhIb1lFeSI7czo4OiJmaWxhbWVudCI7YTowOnt9czo2OiJ0YWJsZXMiO2E6Mzp7czo0MDoiNjIxOWZhMTQ3MmUyMWNjOTM5MzE1Y2ZmOTFiOWQwNzZfZmlsdGVycyI7YToyOntzOjEwOiJjcmVhdGVkX2F0IjthOjc6e3M6NjoiY2xhdXNlIjtOO3M6NToidmFsdWUiO047czo0OiJmcm9tIjtOO3M6NToidW50aWwiO047czoxMjoicGVyaW9kX3ZhbHVlIjtOO3M6NjoicGVyaW9kIjtzOjQ6ImRheXMiO3M6OToiZGlyZWN0aW9uIjtOO31zOjk6Im5ldF93b3J0aCI7YTo0OntzOjY6ImNsYXVzZSI7TjtzOjU6InZhbHVlIjtOO3M6NDoiZnJvbSI7TjtzOjU6InVudGlsIjtOO319czo0MDoiNjVmNTIyMDk3ZGJkOWU5YzlmODRmN2JiNzljMDE3ZmVfZmlsdGVycyI7YToxOntzOjU6InVzZXJzIjthOjE6e3M6NjoidmFsdWVzIjthOjA6e319fXM6NDE6IjEzYzEzYjUyOGIwZWMxYzIxYzc1NjdlZDViZWY3YjE5X3Blcl9wYWdlIjtzOjI6IjUwIjt9fQ==',1772972276);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'Administrator',
  `registration_fee` decimal(10,2) DEFAULT NULL,
  `member_status` varchar(255) NOT NULL DEFAULT 'Active',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'John Owegi','admin@glof.co.ke',NULL,NULL,'$2y$12$EV2.9JryEKsVtH3aWJiql.uoOSA0YIjCUcX0F3.N1mGfRX38HoYEy','Administrator',1000.00,'Active','GytUyDZheEqKBJo9Ba1o1M8w9GSCPWbuSAvE5rbsxvhb56Oqkew4hPfBAkox','2026-03-08 06:49:25','2026-03-08 06:49:25'),(2,'Jorim Nyamor',NULL,NULL,NULL,NULL,'Member',1000.00,'Active',NULL,'2026-03-08 06:49:25','2026-03-08 06:49:25'),(3,'Nelson Omolo',NULL,NULL,NULL,NULL,'Member',1000.00,'Active',NULL,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(4,'George Ochodo',NULL,NULL,NULL,NULL,'Member',1000.00,'Active',NULL,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(5,'Patrick Digolo',NULL,NULL,NULL,NULL,'Member',1000.00,'Active',NULL,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(6,'Jim Oyugi',NULL,NULL,NULL,NULL,'Member',1000.00,'Active',NULL,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(7,'Hezborne Onyango',NULL,NULL,NULL,NULL,'Member',1000.00,'Active',NULL,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(8,'Booker Odenyo',NULL,NULL,NULL,NULL,'Member',1000.00,'Active',NULL,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(9,'Dick Otieno',NULL,NULL,NULL,NULL,'Member',1000.00,'Active',NULL,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(10,'Tom Atak',NULL,NULL,NULL,NULL,'Member',3000.00,'Active',NULL,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(11,'Ibrahim Onyata',NULL,NULL,NULL,NULL,'Member',5000.00,'Active',NULL,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(12,'Sam Amenya',NULL,NULL,NULL,NULL,'Member',5000.00,'Active',NULL,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(13,'Norbert Opiyo',NULL,NULL,NULL,NULL,'Member',5000.00,'Active',NULL,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(14,'Jack Okuku',NULL,NULL,NULL,NULL,'Member',5000.00,'Active',NULL,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(15,'Martin Odipo',NULL,NULL,NULL,NULL,'Member',5000.00,'Active',NULL,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(16,'Eliud Adiedo',NULL,NULL,NULL,NULL,'Member',10000.00,'Active',NULL,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(17,'Cornel Opiyo',NULL,NULL,NULL,NULL,'Member',10000.00,'Active',NULL,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(18,'Francis Raudo',NULL,NULL,NULL,NULL,'Member',10000.00,'Active',NULL,'2026-03-08 06:49:26','2026-03-08 06:49:26'),(19,'Don Riaroh',NULL,NULL,NULL,NULL,'Member',10000.00,'Active',NULL,'2026-03-08 06:49:27','2026-03-08 06:49:27'),(20,'Jotham Arwa',NULL,NULL,NULL,NULL,'Member',10000.00,'Active',NULL,'2026-03-08 06:49:27','2026-03-08 06:49:27'),(21,'Maurice KAnjejo',NULL,NULL,NULL,NULL,'Member',10000.00,'Active',NULL,'2026-03-08 06:49:27','2026-03-08 06:49:27'),(22,'Victor Denge',NULL,NULL,NULL,NULL,'Member',10000.00,'Active',NULL,'2026-03-08 06:49:27','2026-03-08 06:49:27'),(23,'Chris Onyango',NULL,NULL,NULL,NULL,'Member',10000.00,'Active',NULL,'2026-03-08 06:49:27','2026-03-08 06:49:27'),(24,'Maurice Owiti',NULL,NULL,NULL,NULL,'Member',20000.00,'Active',NULL,'2026-03-08 06:49:27','2026-03-08 06:49:27'),(25,'William Osewe',NULL,NULL,NULL,NULL,'Member',20000.00,'Active',NULL,'2026-03-08 06:49:27','2026-03-08 06:49:27'),(26,'Jerim Otieno',NULL,NULL,NULL,NULL,'Member',20000.00,'Active',NULL,'2026-03-08 06:49:27','2026-03-08 06:49:27'),(27,'Frederick Otieno',NULL,NULL,NULL,NULL,'Member',20000.00,'Active',NULL,'2026-03-08 06:49:27','2026-03-08 06:49:27'),(28,'Daniel Olago',NULL,NULL,NULL,NULL,'Member',20000.00,'Active',NULL,'2026-03-08 06:49:27','2026-03-08 06:49:27'),(29,'Nicholas Akech',NULL,NULL,NULL,NULL,'Member',20000.00,'Active',NULL,'2026-03-08 06:49:27','2026-03-08 06:49:27'),(30,'Dr. Rae',NULL,NULL,NULL,NULL,'Member',20000.00,'Active',NULL,'2026-03-08 06:49:27','2026-03-08 06:49:27'),(31,'Cosmas Ngeso',NULL,NULL,NULL,NULL,'Member',20000.00,'Active',NULL,'2026-03-08 06:49:27','2026-03-08 06:49:27'),(32,'Ambrose Anguka',NULL,NULL,NULL,NULL,'Member',20000.00,'Active',NULL,'2026-03-08 06:49:27','2026-03-08 06:49:27');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `year_debt`
--

DROP TABLE IF EXISTS `year_debt`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `year_debt` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `year_debt`
--

LOCK TABLES `year_debt` WRITE;
/*!40000 ALTER TABLE `year_debt` DISABLE KEYS */;
/*!40000 ALTER TABLE `year_debt` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `years`
--

DROP TABLE IF EXISTS `years`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `years` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `year` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `years`
--

LOCK TABLES `years` WRITE;
/*!40000 ALTER TABLE `years` DISABLE KEYS */;
INSERT INTO `years` VALUES (1,2024,'2026-03-08 06:49:28','2026-03-08 06:49:28'),(2,2025,'2026-03-08 06:49:28','2026-03-08 06:49:28'),(3,2026,'2026-03-08 06:49:28','2026-03-08 06:49:28'),(4,2027,'2026-03-08 06:49:28','2026-03-08 06:49:28'),(5,2028,'2026-03-08 06:49:28','2026-03-08 06:49:28'),(6,2029,'2026-03-08 06:49:28','2026-03-08 06:49:28'),(7,2030,'2026-03-08 06:49:28','2026-03-08 06:49:28'),(8,2031,'2026-03-08 06:49:28','2026-03-08 06:49:28'),(9,2032,'2026-03-08 06:49:28','2026-03-08 06:49:28'),(10,2033,'2026-03-08 06:49:28','2026-03-08 06:49:28'),(11,2034,'2026-03-08 06:49:28','2026-03-08 06:49:28');
/*!40000 ALTER TABLE `years` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-08 15:26:51
