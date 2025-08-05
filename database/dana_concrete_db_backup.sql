-- MySQL dump 10.13  Distrib 8.4.5, for Linux (x86_64)
--
-- Host: localhost    Database: dana_concrete_db
-- ------------------------------------------------------
-- Server version	8.4.5-0ubuntu0.2

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
-- Table structure for table `bins_silos`
--

DROP TABLE IF EXISTS `bins_silos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bins_silos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `type` enum('چاو','سایلۆ','تەنکی','عەمبار') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `material_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT '0.00',
  `total_value` decimal(12,2) DEFAULT '0.00',
  `average_price` decimal(15,10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bins_silos`
--

LOCK TABLES `bins_silos` WRITE;
/*!40000 ALTER TABLE `bins_silos` DISABLE KEYS */;
INSERT INTO `bins_silos` VALUES (1,'چاوی ١(لمی کەسارە)','چاو','لمی کەسارە',358972.50,3589725.00,10.0000000000),(2,'چاوی ٢(لمی ڕەش)','چاو','لمی ڕەش',3150212.00,32289673.00,10.2500000000),(3,'چاوی ٣ (غەسالە)','چاو','چەو',1011265.00,5898708.76,5.8330000148),(4,'چاوی ٤ (کەسارە)','چاو','چەو',39255.00,228974.42,5.8330000000),(5,'سایلۆی ١','سایلۆ','چیمەنتۆ',80020.00,5154.49,0.0644149681),(6,'سایلۆی ٢','سایلۆ','چیمەنتۆ',65205.00,4126.68,0.0632877932),(7,'تەنکی دەرمان ١','تەنکی','دەرمان',6908.80,2210.82,0.3200000000),(8,'تەکی گاز ١','تەنکی','گاز',4000.00,2180000.00,545.0000000000);
/*!40000 ALTER TABLE `bins_silos` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `bins_silos_no_negative_stock` BEFORE UPDATE ON `bins_silos` FOR EACH ROW BEGIN
    IF NEW.amount < 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stock cannot be negative!';
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_bins_silos_no_negative_amount` BEFORE UPDATE ON `bins_silos` FOR EACH ROW BEGIN
  IF NEW.amount < 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'بڕی ستۆک نابێت منفی بێت!';
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `cars`
--

DROP TABLE IF EXISTS `cars`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cars` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cars`
--

LOCK TABLES `cars` WRITE;
/*!40000 ALTER TABLE `cars` DISABLE KEYS */;
INSERT INTO `cars` VALUES (10,'P1'),(11,'P2'),(12,'P3'),(13,'M10'),(14,'M11'),(15,'M12'),(16,'M13'),(17,'M14'),(18,'M15'),(19,'M16'),(20,'M17'),(21,'M18'),(23,'M19'),(24,'M20'),(25,'بێ پەمپ '),(26,'N1'),(27,'T1'),(28,'C2');
/*!40000 ALTER TABLE `cars` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cash_box`
--

DROP TABLE IF EXISTS `cash_box`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cash_box` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `type` enum('deposit','withdraw') COLLATE utf8mb4_general_ci NOT NULL,
  `amount_iqd` decimal(20,2) DEFAULT '0.00',
  `amount_usd` decimal(14,2) DEFAULT '0.00',
  `currency` enum('دینار','دۆلار') COLLATE utf8mb4_general_ci NOT NULL,
  `note` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cash_box`
--

LOCK TABLES `cash_box` WRITE;
/*!40000 ALTER TABLE `cash_box` DISABLE KEYS */;
INSERT INTO `cash_box` VALUES (1,'2025-08-02','deposit',0.00,75000.00,'دۆلار','',2,'2025-08-02 13:34:59'),(2,'2025-08-02','deposit',6000000.00,0.00,'دینار','',2,'2025-08-02 13:35:23'),(9,'2025-08-04','deposit',600000.00,0.00,'دینار','گەڕاندنەوەی قەرزی کڕیار',NULL,'2025-08-04 09:15:43'),(10,'2025-08-04','deposit',75000.00,0.00,'دینار','گەڕاندنەوەی قەرزی کڕیار',NULL,'2025-08-04 09:16:21'),(11,'2025-08-04','deposit',0.00,1900.00,'دۆلار','گەڕاندنەوەی قەرزی کڕیار',NULL,'2025-08-04 09:19:32'),(12,'2025-08-04','deposit',75000.00,0.00,'دینار','گەڕاندنەوەی قەرزی کڕیار',NULL,'2025-08-04 09:19:32'),(13,'2025-08-04','deposit',0.00,90.00,'دۆلار','گەڕاندنەوەی قەرزی کڕیار',NULL,'2025-08-04 09:19:57'),(14,'2025-08-02','deposit',0.00,2700.00,'دۆلار','فرۆشتن: invoice A-0007, A-0008, A-0009, A-0010, A-0011, A-0012',NULL,'2025-08-05 02:05:59'),(16,'2025-08-02','deposit',0.00,450.00,'دۆلار','فرۆشتن: invoice A-0014',NULL,'2025-08-05 02:11:02'),(17,'2025-08-03','deposit',0.00,1300.00,'دۆلار','فرۆشتن: invoice A-0042, A-0044, A-0045',NULL,'2025-08-05 02:22:47'),(18,'2025-08-03','deposit',0.00,2100.00,'دۆلار','فرۆشتن: invoice A-0064, A-0065, A-0069, A-0070, A-0071, A-0073',NULL,'2025-08-05 02:27:47'),(19,'2025-08-03','deposit',0.00,1200.00,'دۆلار','فرۆشتن: invoice A-0074, A-0075, A-0076',NULL,'2025-08-05 02:30:04'),(20,'2025-08-03','deposit',80000.00,0.00,'دینار','فرۆشتن: invoice A-0074, A-0075, A-0076',NULL,'2025-08-05 02:30:04'),(21,'2025-08-02','withdraw',16000.00,0.00,'دینار','پارەدانی کڕینی KR-0001',2,'2025-08-05 10:27:16');
/*!40000 ALTER TABLE `cash_box` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_before_withdraw_cash_box` BEFORE INSERT ON `cash_box` FOR EACH ROW BEGIN
    DECLARE total_usd DECIMAL(20,2) DEFAULT 0;
    DECLARE dollar_rate DECIMAL(20,2) DEFAULT 150000;
    DECLARE usd_to_check DECIMAL(20,2) DEFAULT 0;

    -- وەرگرتنی نرخەکەی دۆلار لە settings
    SELECT CAST(value AS DECIMAL(20,2)) INTO dollar_rate FROM settings WHERE name = 'usd_iqd_rate' LIMIT 1;

    -- هەژمارکردنی قاسەی گشتی بە دۆلار (هەموو مامەڵەکان)
    SELECT
        IFNULL(SUM(
            CASE
                WHEN currency = 'دۆلار' AND type = 'deposit' THEN amount_usd
                WHEN currency = 'دۆلار' AND type = 'withdraw' THEN -amount_usd
                WHEN currency = 'دینار' AND type = 'deposit' THEN amount_iqd / (dollar_rate / 100)
                WHEN currency = 'دینار' AND type = 'withdraw' THEN -amount_iqd / (dollar_rate / 100)
                ELSE 0
            END
        ), 0)
    INTO total_usd
    FROM cash_box;

    -- بڕی مامەڵەی نوێ بە دۆلار
    IF NEW.currency = 'دۆلار' THEN
        SET usd_to_check = NEW.amount_usd;
    ELSE
        SET usd_to_check = NEW.amount_iqd / (dollar_rate / 100);
    END IF;

    -- چێککردنی قاسەی گشتی بە دۆلار
    IF NEW.type = 'withdraw' AND usd_to_check > total_usd THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'بڕی پێویست لە قاسەی گشتی (دۆلار) نییە!';
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_before_update_cash_box` BEFORE UPDATE ON `cash_box` FOR EACH ROW BEGIN
    DECLARE total_usd DECIMAL(20,2) DEFAULT 0;
    DECLARE dollar_rate DECIMAL(20,2) DEFAULT 150000;
    DECLARE usd_old DECIMAL(20,2) DEFAULT 0;
    DECLARE usd_new DECIMAL(20,2) DEFAULT 0;

    -- وەرگرتنی نرخەکەی دۆلار لە settings
    SELECT CAST(value AS DECIMAL(20,2)) INTO dollar_rate FROM settings WHERE name = 'usd_iqd_rate' LIMIT 1;

    -- هەژمارکردنی قاسەی گشتی بە دۆلار (پێش update)
    SELECT
        IFNULL(SUM(
            CASE
                WHEN currency = 'دۆلار' AND type = 'deposit' THEN amount_usd
                WHEN currency = 'دۆلار' AND type = 'withdraw' THEN -amount_usd
                WHEN currency = 'دینار' AND type = 'deposit' THEN amount_iqd / dollar_rate
                WHEN currency = 'دینار' AND type = 'withdraw' THEN -amount_iqd / dollar_rate
                ELSE 0
            END
        ), 0)
    INTO total_usd
    FROM cash_box;

    -- بڕی مامەڵەی کۆن بە دۆلار
    IF OLD.currency = 'دۆلار' THEN
        SET usd_old = 
            CASE WHEN OLD.type = 'deposit' THEN OLD.amount_usd ELSE -OLD.amount_usd END;
    ELSE
        SET usd_old = 
            CASE WHEN OLD.type = 'deposit' THEN OLD.amount_iqd / dollar_rate ELSE -OLD.amount_iqd / dollar_rate END;
    END IF;

    -- بڕی مامەڵەی نوێ بە دۆلار
    IF NEW.currency = 'دۆلار' THEN
        SET usd_new = 
            CASE WHEN NEW.type = 'deposit' THEN NEW.amount_usd ELSE -NEW.amount_usd END;
    ELSE
        SET usd_new = 
            CASE WHEN NEW.type = 'deposit' THEN NEW.amount_iqd / dollar_rate ELSE -NEW.amount_iqd / dollar_rate END;
    END IF;

    -- چێککردنی قاسەی گشتی بە دۆلار پاش update
    IF (total_usd - usd_old + usd_new) < 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'نوێکردنەوەی ئەم مامەڵەیە قاسەی گشتی منفی دەکات!';
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_before_delete_cash_box` BEFORE DELETE ON `cash_box` FOR EACH ROW BEGIN
    DECLARE total_usd DECIMAL(20,2) DEFAULT 0;
    DECLARE dollar_rate DECIMAL(20,2) DEFAULT 150000;
    DECLARE usd_to_remove DECIMAL(20,2) DEFAULT 0;

    -- وەرگرتنی نرخەکەی دۆلار لە settings
    SELECT CAST(value AS DECIMAL(20,2)) INTO dollar_rate FROM settings WHERE name = 'usd_iqd_rate' LIMIT 1;

    -- هەژمارکردنی قاسەی گشتی بە دۆلار (پێش سڕینەوە)
    SELECT
        IFNULL(SUM(
            CASE
                WHEN currency = 'دۆلار' AND type = 'deposit' THEN amount_usd
                WHEN currency = 'دۆلار' AND type = 'withdraw' THEN -amount_usd
                WHEN currency = 'دینار' AND type = 'deposit' THEN amount_iqd / dollar_rate
                WHEN currency = 'دینار' AND type = 'withdraw' THEN -amount_iqd / dollar_rate
                ELSE 0
            END
        ), 0)
    INTO total_usd
    FROM cash_box;

    -- بڕی مامەڵەی سڕدرێنەوە بە دۆلار
    IF OLD.currency = 'دۆلار' THEN
        SET usd_to_remove = 
            CASE WHEN OLD.type = 'deposit' THEN -OLD.amount_usd ELSE OLD.amount_usd END;
    ELSE
        SET usd_to_remove = 
            CASE WHEN OLD.type = 'deposit' THEN -OLD.amount_iqd / dollar_rate ELSE OLD.amount_iqd / dollar_rate END;
    END IF;

    -- چێککردنی قاسەی گشتی بە دۆلار پاش سڕینەوە
    IF (total_usd + usd_to_remove) < 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'سڕینەوەی ئەم مامەڵەیە قاسەی گشتی منفی دەکات!';
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `company`
--

DROP TABLE IF EXISTS `company`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `company` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `opening_debt_usd` decimal(14,2) DEFAULT '0.00',
  `opening_debt_iqd` decimal(20,2) DEFAULT '0.00',
  `currency_type` enum('دینار','دۆلار') COLLATE utf8mb4_general_ci DEFAULT 'دینار',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `company`
--

LOCK TABLES `company` WRITE;
/*!40000 ALTER TABLE `company` DISABLE KEYS */;
INSERT INTO `company` VALUES (1,'دەشتی',0.00,38416000.00,'دینار'),(2,'سۆران',0.00,9915000.00,'دینار'),(3,'مامە کاوە',40664.00,0.00,'دۆلار'),(4,'حاجی فاروق چیمەنتۆ',189256.00,0.00,'دۆلار'),(5,'دانا کۆنکرێت',25228.00,0.00,'دۆلار'),(6,'تڕێلەکەی خۆمان',0.00,5912000.00,'دینار'),(9,'کاک ئاوات',0.00,21887000.00,'دینار'),(10,'دەرمانی شڤا',2310.00,0.00,'دۆلار'),(11,'دەرمانی MHA',4608.00,0.00,'دۆلار'),(12,'کەسارەکەی خۆمان',0.00,0.00,'دینار');
/*!40000 ALTER TABLE `company` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `concrete_formulas`
--

DROP TABLE IF EXISTS `concrete_formulas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `concrete_formulas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('عەرزی تێکەڵ','عەرزی سادە','سەقف','پایە') COLLATE utf8mb4_unicode_ci NOT NULL,
  `strength_type` enum('kg','mpa') COLLATE utf8mb4_unicode_ci NOT NULL,
  `strength_kg` enum('150','200','250','300','350','400','450') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `strength_mpa` enum('15','18','21','25','30','35','40','45','50','55') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `black_sand_kg` decimal(10,2) NOT NULL DEFAULT '0.00',
  `brown_sand_kg` decimal(10,2) NOT NULL DEFAULT '0.00',
  `gravel_bin3_kg` decimal(10,2) NOT NULL DEFAULT '0.00',
  `gravel_bin4_kg` decimal(10,2) NOT NULL DEFAULT '0.00',
  `cement_cem1_kg` decimal(10,2) NOT NULL DEFAULT '0.00',
  `cement_cem2_kg` decimal(10,2) NOT NULL DEFAULT '0.00',
  `water_kg` decimal(10,2) NOT NULL DEFAULT '0.00',
  `additive_kg` decimal(10,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `concrete_formulas`
--

LOCK TABLES `concrete_formulas` WRITE;
/*!40000 ALTER TABLE `concrete_formulas` DISABLE KEYS */;
INSERT INTO `concrete_formulas` VALUES (17,'SAQF 350','سەقف','kg','350',NULL,1070.00,0.00,445.00,440.00,0.00,265.00,160.00,2.00),(18,'SAQF 300','سەقف','kg','300',NULL,1110.00,0.00,440.00,440.00,0.00,250.00,145.00,2.00),(19,'SAQF 400 ','سەقف','kg','400',NULL,1100.00,0.00,0.00,860.00,0.00,280.00,140.00,2.50),(21,'PAYA 25 MEGA','پایە','kg',NULL,'25',690.00,580.00,0.00,700.00,265.00,0.00,150.00,2.60),(22,'PAYA 30 MEGA','پایە','kg',NULL,'30',820.00,400.00,0.00,720.00,285.00,0.00,160.00,3.00),(23,'PAYA 35 MEGA','پایە','kg',NULL,'35',775.00,400.00,0.00,740.00,310.00,0.00,160.00,3.00),(24,'ARZY 15 MEGA','عەرزی سادە','kg',NULL,'15',0.00,1200.00,450.00,400.00,160.00,0.00,185.00,1.00),(25,'ARZY 18 MEGA','عەرزی تێکەڵ','kg',NULL,'18',0.00,1150.00,420.00,400.00,200.00,0.00,165.00,1.50),(26,'ARZY 21 MEGA','عەرزی تێکەڵ','kg',NULL,'21',400.00,700.00,300.00,550.00,230.00,0.00,155.00,2.30),(27,'ARZY 25 MEGA','عەرزی تێکەڵ','kg',NULL,'25',440.00,645.00,350.00,510.00,240.00,0.00,155.00,2.40),(30,'ARZY 30 MEGA','عەرزی تێکەڵ','kg',NULL,'30',530.00,510.00,330.00,540.00,280.00,0.00,150.00,3.00),(31,'ARZY 35 MEGA','عەرزی تێکەڵ','kg',NULL,'35',520.00,500.00,0.00,860.00,310.00,0.00,150.00,3.10),(32,'SAQF 40 M','سەقف','kg',NULL,'40',940.00,0.00,0.00,880.00,0.00,390.00,135.00,4.60),(33,'SAQF 35 MEGA','سەقف','kg',NULL,'35',1020.00,0.00,450.00,450.00,0.00,310.00,150.00,3.00),(34,'ARZY 18 M','عەرزی تێکەڵ','kg',NULL,'18',400.00,690.00,440.00,440.00,0.00,225.00,160.00,2.50),(35,'ARZY 21 M','عەرزی تێکەڵ','kg',NULL,'21',400.00,670.00,300.00,565.00,0.00,250.00,150.00,2.50),(36,'ARZY 25 M','عەرزی تێکەڵ','kg',NULL,'25',500.00,525.00,300.00,580.00,0.00,275.00,150.00,3.00),(37,'ARZY 30 M','عەرزی تێکەڵ','kg',NULL,'30',525.00,500.00,0.00,850.00,0.00,320.00,150.00,3.80),(38,'ARZY 35 M','عەرزی تێکەڵ','kg',NULL,'35',460.00,500.00,0.00,870.00,0.00,360.00,150.00,4.00),(39,'PAYA 25 M','عەرزی تێکەڵ','kg',NULL,'25',600.00,590.00,375.00,375.00,0.00,290.00,160.00,3.00),(40,'PAYA 30 M','عەرزی تێکەڵ','kg',NULL,'30',1200.00,0.00,0.00,720.00,0.00,310.00,160.00,3.50),(41,'PAYA 35 M','عەرزی تێکەڵ','kg',NULL,'35',1150.00,0.00,0.00,730.00,0.00,350.00,160.00,4.20),(42,'Chawy lws saqf 350','سەقف','kg','350',NULL,1050.00,0.00,930.00,0.00,0.00,265.00,135.00,2.50),(47,'PAYA 40 M','پایە','kg',NULL,'40',1085.00,0.00,0.00,785.00,0.00,375.00,150.00,4.20),(48,'20','عەرزی تێکەڵ','kg',NULL,'30',530.00,520.00,0.00,850.00,290.00,0.00,150.00,3.00);
/*!40000 ALTER TABLE `concrete_formulas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `concrete_receipts`
--

DROP TABLE IF EXISTS `concrete_receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `concrete_receipts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `receipt_number` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` int DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `meter_amount` decimal(10,2) NOT NULL,
  `price_per_meter` decimal(10,2) DEFAULT NULL,
  `formulas_id` int NOT NULL,
  `pump_car_id` int DEFAULT NULL,
  `pump_driver_id` int DEFAULT NULL,
  `mixer_car_id` int DEFAULT NULL,
  `mixer_driver_id` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `receiver_name` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `payment_status` enum('paid','unpaid') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unpaid',
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `formulas_id` (`formulas_id`),
  KEY `pump_car_id` (`pump_car_id`),
  KEY `pump_driver_id` (`pump_driver_id`),
  KEY `mixer_car_id` (`mixer_car_id`),
  KEY `mixer_driver_id` (`mixer_driver_id`),
  CONSTRAINT `concrete_receipts_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `concrete_receipts_ibfk_2` FOREIGN KEY (`formulas_id`) REFERENCES `concrete_formulas` (`id`),
  CONSTRAINT `concrete_receipts_ibfk_5` FOREIGN KEY (`pump_car_id`) REFERENCES `cars` (`id`),
  CONSTRAINT `concrete_receipts_ibfk_6` FOREIGN KEY (`pump_driver_id`) REFERENCES `employees` (`id`),
  CONSTRAINT `concrete_receipts_ibfk_7` FOREIGN KEY (`mixer_car_id`) REFERENCES `cars` (`id`),
  CONSTRAINT `concrete_receipts_ibfk_8` FOREIGN KEY (`mixer_driver_id`) REFERENCES `employees` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=120 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `concrete_receipts`
--

LOCK TABLES `concrete_receipts` WRITE;
/*!40000 ALTER TABLE `concrete_receipts` DISABLE KEYS */;
INSERT INTO `concrete_receipts` VALUES (4,'A-0001',37,'ڕاپەڕین',10.00,45.00,27,11,27,23,29,'2025-08-02 01:00:55','2025-08-02 04:44:07','وەستا قالە','','unpaid'),(5,'A-0002',37,'ڕاپەڕین',10.00,45.00,27,11,27,16,37,'2025-08-02 01:02:12','2025-08-02 04:44:07','وەستا قالە','','unpaid'),(6,'A-0003',37,'ڕاپەڕین',10.00,45.00,27,11,27,18,33,'2025-08-02 01:06:49','2025-08-02 04:44:07','وەستا قالە','','unpaid'),(7,'A-0004',37,'ڕاپەڕین',10.00,45.00,27,11,27,20,34,'2025-08-02 01:25:25','2025-08-02 04:44:07','وەستا قالە','','unpaid'),(8,'A-0005',37,'ڕاپەڕین',10.00,45.00,27,11,27,19,41,'2025-08-02 01:26:26','2025-08-02 04:44:07','وەستا قالە','','unpaid'),(9,'A-0006',37,'ڕاپەڕین',10.00,45.00,27,11,27,14,39,'2025-08-02 01:27:08','2025-08-02 04:44:07','وەستا قالە','','unpaid'),(10,'A-0007',113,'بەختیاری',10.00,45.00,27,11,26,15,42,'2025-08-02 01:30:02','2025-08-03 15:43:37','هاورێ مجید','3150$واصڵ\n','paid'),(11,'A-0008',113,'بەختیاری',10.00,45.00,27,11,26,17,31,'2025-08-02 01:34:35','2025-08-03 15:43:37','هاورێ مجید','3150$واصڵ\n','paid'),(12,'A-0009',113,'بەختیاری',10.00,45.00,27,11,26,13,38,'2025-08-02 01:45:30','2025-08-03 15:43:37','هاورێ مجید','3150$واصڵ\n','paid'),(13,'A-0010',113,'بەختیاری',10.00,45.00,27,11,26,21,30,'2025-08-02 01:52:27','2025-08-03 15:43:37','هاورێ مجید','3150$واصڵ\n','paid'),(14,'A-0011',113,'بەختیاری',10.00,45.00,27,11,26,23,29,'2025-08-02 02:02:38','2025-08-03 15:43:37','هاورێ مجید','3150$واصڵ\n','paid'),(15,'A-0012',113,'بەختیاری',10.00,45.00,27,11,26,16,37,'2025-08-02 02:11:22','2025-08-03 15:43:37','هاورێ مجید','3150$واصڵ\n','paid'),(16,'A-0013',37,'ڕاپەڕین',8.00,45.00,27,11,26,18,33,'2025-08-02 02:19:03','2025-08-02 04:44:07','وەستا قالە','','unpaid'),(17,'A-0014',113,'بەختیاری',10.00,45.00,27,10,26,20,34,'2025-08-02 03:04:20','2025-08-03 15:43:37','هاورێ مجید','3150$واصڵ\n','paid'),(18,'A-0015',105,'ڕاپەڕین',10.00,47.00,19,11,27,19,41,'2025-08-02 03:16:41','2025-08-02 04:44:06','ک.تۆفیق','','unpaid'),(19,'A-0016',105,'ڕاپەڕین',10.00,47.00,19,11,27,14,39,'2025-08-02 03:24:52','2025-08-02 04:44:06','ک.تۆفیق','','unpaid'),(20,'A-0017',105,'ڕاپەڕین',10.00,47.00,19,11,27,15,42,'2025-08-02 03:26:47','2025-08-02 04:44:06','ک.تۆفیق','','unpaid'),(21,'A-0018',105,'ڕاپەڕین',10.00,47.00,19,11,27,17,31,'2025-08-02 04:03:30','2025-08-02 04:44:06','ک.تۆفیق','','unpaid'),(22,'A-0019',50,'مه عملى جيمنتو ماس',8.00,45.00,27,NULL,NULL,13,38,'2025-08-02 04:17:22','2025-08-02 14:44:49','','','unpaid'),(23,'A-0020',105,'ڕاپەڕین',4.00,NULL,19,11,27,23,29,'2025-08-02 05:01:02',NULL,'ک.تۆفیق',NULL,'unpaid'),(25,'A-0022',50,'معمل جيمنتو ماس',8.50,45.00,27,NULL,NULL,18,33,'2025-08-02 06:35:46','2025-08-02 14:44:49','فه رهاد','','unpaid'),(26,'A-0023',34,'پیرەمەگرون',9.00,41.00,26,10,26,19,41,'2025-08-02 06:58:59','2025-08-02 14:45:15','حاكم احمد','','unpaid'),(27,'A-0024',117,'ڕاپەڕین',9.00,47.00,42,11,27,14,39,'2025-08-02 10:42:54','2025-08-02 14:45:59','وه ستا ديار','','unpaid'),(28,'A-0025',117,'ڕاپەڕین',9.50,47.00,42,11,27,20,34,'2025-08-02 10:49:31','2025-08-02 14:45:59','وه ستا ديار','','unpaid'),(29,'A-0026',118,'تافكا ',11.50,46.00,42,11,27,17,31,'2025-08-02 12:32:53','2025-08-02 14:42:43','','','unpaid'),(30,'A-0027',118,'تافكا ',11.50,46.00,42,11,27,18,33,'2025-08-02 12:40:04','2025-08-02 14:42:43','','','unpaid'),(31,'A-0028',119,'پیرەمەگرون',10.00,47.00,30,10,26,21,30,'2025-08-02 12:48:39','2025-08-03 07:40:56','وستا ازاد','','unpaid'),(32,'A-0029',119,'پیرەمەگرون',10.00,47.00,30,10,26,15,42,'2025-08-02 12:52:28','2025-08-03 07:40:56','وستا ازاد','','unpaid'),(33,'A-0030',119,'پیرەمەگرون',10.00,47.00,30,10,26,23,29,'2025-08-02 12:56:41','2025-08-03 07:40:56','وستا ازاد','','unpaid'),(34,'A-0031',119,'پیرەمەگرون',10.00,47.00,30,10,26,16,37,'2025-08-02 13:01:58','2025-08-03 07:40:56','وستا ازاد','','unpaid'),(35,'A-0032',119,'پیرەمەگرون',10.00,47.00,30,10,26,13,38,'2025-08-02 13:06:42','2025-08-03 07:40:56','وستا ازاد','','unpaid'),(36,'A-0033',119,'پیرەمەگرون',10.00,47.00,30,10,26,19,41,'2025-08-02 13:15:37','2025-08-03 07:40:56','وستا ازاد','','unpaid'),(37,'A-0034',119,'پیرەمەگرون',10.00,47.00,30,10,26,14,39,'2025-08-02 13:19:56','2025-08-03 07:40:56','وستا ازاد','','unpaid'),(38,'A-0035',119,'پیرەمەگرون',10.00,47.00,30,10,26,20,34,'2025-08-02 13:20:35','2025-08-03 07:40:56','وستا ازاد','','unpaid'),(39,'A-0036',119,'پیرەمەگرون',10.00,47.00,30,10,26,21,30,'2025-08-02 13:43:26','2025-08-03 07:40:56','وستا ازاد','','unpaid'),(40,'A-0037',119,'پیرەمەگرون',10.00,47.00,30,10,26,15,42,'2025-08-02 13:52:41','2025-08-03 07:40:56','وستا ازاد','','unpaid'),(41,'A-0038',118,'تافكا ',1.50,46.00,42,11,27,23,29,'2025-08-02 14:00:17','2025-08-02 14:42:43','','','unpaid'),(42,'A-0039',119,'پیرەمەگرون',9.00,47.00,30,10,26,16,37,'2025-08-02 14:35:48','2025-08-03 07:40:56','وستا ازاد','','unpaid'),(43,'A-0040',37,'قه لياسان',6.00,45.00,42,11,27,13,38,'2025-08-02 14:58:46','2025-08-03 15:48:56','','','unpaid'),(44,'A-0041',119,'پیرەمەگرون',3.00,47.00,30,10,26,19,41,'2025-08-02 15:19:10','2025-08-03 07:40:56','وستا ازاد','','unpaid'),(46,'A-0042',145,'بەرامبەر حەسەن تەپە',10.00,43.33,26,10,26,13,38,'2025-08-03 02:49:24','2025-08-04 08:45:03','و . هیوا ','1300 دۆلار واسڵ','paid'),(47,'A-0043',146,'پیرەمەگرون',11.00,45.00,27,11,27,17,31,'2025-08-03 03:04:36','2025-08-03 20:46:56','ک . ئاسۆ','','unpaid'),(48,'A-0044',145,'بەرامبەر حەسەن تەپە',10.00,43.33,26,10,26,14,39,'2025-08-03 03:08:05','2025-08-04 08:45:03','و . هیوا ','1300 دۆلار واسڵ','paid'),(49,'A-0045',145,'بەرامبەر حەسەن تەپە',10.00,43.33,26,10,26,18,33,'2025-08-03 03:17:45','2025-08-04 08:45:03','و . هیوا ','1300 دۆلار واسڵ','paid'),(50,'A-0046',146,'پیرەمەگرون',2.00,45.00,27,11,27,16,37,'2025-08-03 03:57:16','2025-08-03 20:46:56','ک . ئاسۆ','','unpaid'),(51,'A-0047',147,'دوكان',8.00,53.00,38,11,27,23,29,'2025-08-03 06:07:08','2025-08-03 20:45:45','و, شوانه','','unpaid'),(52,'A-0048',147,'دوكان',7.50,53.00,38,11,27,19,41,'2025-08-03 06:14:42','2025-08-03 20:45:45','و, شوانه','','unpaid'),(53,'A-0049',147,'دوكان',8.00,53.00,38,11,27,15,42,'2025-08-03 06:19:56','2025-08-03 20:45:45','و, شوانه','','unpaid'),(54,'A-0050',147,'دوكان',8.00,53.00,38,11,27,13,38,'2025-08-03 06:25:06','2025-08-03 20:45:45','و, شوانه','','unpaid'),(55,'A-0051',147,'دوكان',7.50,53.00,38,11,27,20,34,'2025-08-03 06:32:44','2025-08-03 20:45:45','و, شوانه','','unpaid'),(56,'A-0052',107,'كه له ونان',2.00,45.00,41,10,26,14,39,'2025-08-03 06:42:16','2025-08-03 17:46:50',NULL,'','unpaid'),(57,'A-0053',147,'دوكان',7.50,53.00,38,11,27,18,33,'2025-08-03 06:57:33','2025-08-03 20:45:45','و.شوانا','','unpaid'),(58,'A-0054',147,'دوكان',7.50,53.00,38,11,27,17,31,'2025-08-03 07:03:05','2025-08-03 20:45:45','و.شوانا','','unpaid'),(59,'A-0055',147,'دوكان',7.50,53.00,38,11,27,16,37,'2025-08-03 07:04:42','2025-08-03 20:45:45','و.شوانا','','unpaid'),(60,'A-0056',147,'دوكان',8.00,53.00,38,11,27,21,30,'2025-08-03 07:17:28','2025-08-03 20:45:45','و.شوانا','','unpaid'),(61,'A-0057',147,'دوكان',7.50,53.00,38,11,27,14,39,'2025-08-03 07:21:21','2025-08-03 20:45:45','و.شوانا','','unpaid'),(62,'A-0058',147,'دوكان',8.00,53.00,38,11,27,23,29,'2025-08-03 08:09:08','2025-08-03 20:45:45','و.شوانا','','unpaid'),(63,'A-0059',147,'دوكان',7.50,53.00,38,11,27,19,41,'2025-08-03 08:18:21','2025-08-03 20:45:45','و.شوانا','','unpaid'),(64,'A-0060',147,'دوكان',7.50,53.00,38,11,27,15,42,'2025-08-03 08:26:22','2025-08-03 20:45:45','و.شوانا','','unpaid'),(65,'A-0061',147,'دوكان',5.00,53.00,38,11,27,20,34,'2025-08-03 09:22:57','2025-08-03 20:45:45','و.شوانا','','unpaid'),(66,'A-0062',99,'کێلە سپی',5.00,45.00,27,11,27,13,38,'2025-08-03 11:51:02','2025-08-03 20:47:14','کاک کاروان ','','unpaid'),(67,'A-0063',181,'ڕاپەڕین',9.00,45.00,42,10,26,16,37,'2025-08-03 12:31:32','2025-08-03 20:46:33','و. كرمانج','','unpaid'),(68,'A-0064',57,'دارتو مولانا',10.00,45.00,42,11,27,17,31,'2025-08-03 12:34:56','2025-08-03 17:50:00','','2,100$ لای توانای برامە','paid'),(69,'A-0065',57,'دارتو مولانا',10.00,45.00,42,11,27,20,33,'2025-08-03 12:37:13','2025-08-03 17:50:00','','2,100$ لای توانای برامە','paid'),(70,'A-0066',181,'ڕاپەڕین',9.00,45.00,42,10,26,14,39,'2025-08-03 12:42:15','2025-08-03 20:46:33','و. كرمانج','','unpaid'),(71,'A-0067',181,'ڕاپەڕین',9.00,45.00,42,10,26,23,29,'2025-08-03 12:50:40','2025-08-03 20:46:33','و. كرمانج','','unpaid'),(72,'A-0068',181,'ڕاپەڕین',8.00,45.00,42,10,26,19,41,'2025-08-03 12:53:03','2025-08-03 20:46:33','و. كرمانج','','unpaid'),(73,'A-0069',57,'دارتو مولانا',10.00,45.00,42,11,27,15,42,'2025-08-03 12:57:48','2025-08-03 17:50:00','','2,100$ لای توانای برامە','paid'),(74,'A-0070',57,'دارتو مولانا',10.00,45.00,42,11,27,13,38,'2025-08-03 13:39:10','2025-08-03 17:50:00','','2,100$ لای توانای برامە','paid'),(75,'A-0071',57,'دارتو مولانا',6.00,45.00,42,11,27,17,31,'2025-08-03 14:16:45','2025-08-03 17:50:00','','2,100$ لای توانای برامە','paid'),(76,'A-0072',186,'پیرەمەگرون',3.00,45.00,27,NULL,NULL,16,37,'2025-08-03 14:23:59','2025-08-03 17:42:19',NULL,'','unpaid'),(77,'A-0073',57,'دارتو مولانا',1.00,45.00,42,11,27,20,33,'2025-08-03 15:04:27','2025-08-03 17:50:00','','2,100$ لای توانای برامە','paid'),(78,'A-0074',60,'پیرەمەگرون',10.00,45.00,42,10,26,14,39,'2025-08-03 15:07:34','2025-08-03 17:48:45','','1,200$+80,000 لای مام شاڵاو پەمپە','paid'),(79,'A-0075',60,'پیرەمەگرون',9.00,45.00,42,10,26,15,42,'2025-08-03 15:16:05','2025-08-03 17:48:45','','1,200$+80,000 لای مام شاڵاو پەمپە','paid'),(80,'A-0076',60,'پیرەمەگرون',9.00,45.00,42,10,26,13,38,'2025-08-03 15:24:29','2025-08-03 17:48:45','','1,200$+80,000 لای مام شاڵاو پەمپە','paid'),(81,'A-0077',107,'كه له ونان',2.00,45.00,41,11,27,20,33,'2025-08-03 16:25:58','2025-08-03 17:46:50',NULL,'','unpaid'),(82,'A-0078',173,'گەرەکی خاک',4.00,44.00,42,10,26,20,34,'2025-08-04 05:36:14','2025-08-04 11:14:37','و. ئازاد','','unpaid'),(83,'A-0079',68,'زيوه',8.00,48.00,27,NULL,NULL,16,37,'2025-08-04 06:03:57','2025-08-04 11:13:45','','','unpaid'),(84,'A-0080',68,'زيوه',2.00,48.00,27,NULL,NULL,17,31,'2025-08-04 07:08:49','2025-08-04 11:13:45','','','unpaid'),(85,'A-0081',188,'کەندەکەوە',10.00,45.00,27,10,26,18,33,'2025-08-04 09:59:41','2025-08-04 11:14:57','و. عباس','','unpaid'),(86,'A-0082',188,'کەندەکەوە',10.00,45.00,27,10,26,14,39,'2025-08-04 10:09:07','2025-08-04 11:14:57','و. عباس','','unpaid'),(87,'A-0083',189,'پیرەمەگرون',2.00,45.00,21,NULL,NULL,20,34,'2025-08-04 10:13:50','2025-08-04 11:16:28','و. عبدالله','30$ کرێی میکسەر','unpaid'),(88,'A-0084',187,'موغاغ',10.00,45.00,42,11,27,16,37,'2025-08-04 10:23:44','2025-08-04 11:15:29','وەستا سمایل','','unpaid'),(89,'A-0085',187,'موغاغ',10.00,45.00,42,11,27,19,41,'2025-08-04 10:30:41','2025-08-04 11:15:29','وەستا سمایل','','unpaid'),(90,'A-0086',187,'موغاغ',10.00,45.00,42,11,27,17,31,'2025-08-04 10:39:00','2025-08-04 11:15:29','وەستا سمایل','','unpaid'),(91,'A-0087',191,'پیرەمەگرون',5.00,46.00,27,11,27,23,29,'2025-08-04 11:13:14','2025-08-04 17:12:21','و.حیدەر','','unpaid'),(92,'A-0088',192,'ڕاپەڕین',5.50,47.00,19,10,26,18,33,'2025-08-04 11:25:55','2025-08-04 17:11:53','و.حیدەر','','unpaid'),(93,'A-0089',107,'مه عمل خومان',2.00,NULL,41,11,27,16,37,'2025-08-04 12:48:12',NULL,'و.حیدەر',NULL,'unpaid'),(94,'A-0090',189,'پیرەمەگرون',6.50,NULL,26,NULL,NULL,17,31,'2025-08-04 12:57:59',NULL,'و. عبدالله',NULL,'unpaid'),(95,'A-0091',193,'معمل چیمەنتۆی ماس',8.00,48.00,38,10,26,14,39,'2025-08-04 13:04:18','2025-08-04 17:12:57','کاک شێرکۆ','','unpaid'),(96,'A-0092',193,'معمل چیمەنتۆی ماس',7.50,48.00,38,10,26,16,37,'2025-08-04 13:10:58','2025-08-04 17:12:57',NULL,'','unpaid'),(97,'A-0093',193,'معمل چیمەنتۆی ماس',7.50,48.00,38,10,26,19,41,'2025-08-04 13:18:52','2025-08-04 17:12:57','','','unpaid'),(98,'A-0094',193,'معمل چیمەنتۆی ماس',8.00,48.00,38,10,26,13,38,'2025-08-04 13:23:23','2025-08-04 17:12:57',NULL,'','unpaid'),(99,'A-0095',193,'معمل چیمەنتۆی ماس',10.00,48.00,38,10,26,20,34,'2025-08-04 13:29:50','2025-08-04 17:12:57',NULL,'','unpaid'),(100,'A-0096',193,'معمل چیمەنتۆی ماس',7.50,48.00,38,10,26,18,33,'2025-08-04 13:37:20','2025-08-04 17:12:57','','','unpaid'),(101,'A-0097',193,'معمل چیمەنتۆی ماس',10.00,48.00,38,10,26,23,29,'2025-08-04 13:43:45','2025-08-04 17:12:57',NULL,'','unpaid'),(102,'A-0098',193,'معمل چیمەنتۆی ماس',9.00,48.00,38,10,26,21,32,'2025-08-04 13:56:12','2025-08-04 17:12:57','','','unpaid'),(103,'A-0099',193,'معمل چیمەنتۆی ماس',10.00,48.00,38,10,26,17,31,'2025-08-04 14:15:34','2025-08-04 17:12:57','','','unpaid'),(104,'A-0100',30,'پیرەمەگرون',2.00,46.00,27,NULL,NULL,14,39,'2025-08-04 15:08:21','2025-08-04 17:13:34','','25 $ کرێی گاز','unpaid'),(105,'A-0101',113,'کانی با',7.50,46.00,42,10,26,23,29,'2025-08-05 01:28:50','2025-08-05 05:52:45','وەستا هاوڕێ','100000 کرێی پەمپ','unpaid'),(106,'A-0102',113,'کانی با',7.00,46.00,42,10,26,14,39,'2025-08-05 01:33:37','2025-08-05 05:52:45','وەستا هاوڕێ','100000 کرێی پەمپ','unpaid'),(107,'A-0103',194,'هەواری شار',9.00,44.00,42,11,27,23,33,'2025-08-05 01:38:58','2025-08-05 05:53:17','وەستا هێمن','','unpaid'),(108,'A-0104',194,'هەواری شار',9.00,44.00,42,11,27,13,38,'2025-08-05 01:42:22','2025-08-05 05:53:17','وەستا هێمن','','unpaid'),(109,'A-0105',194,'هەواری شار',9.00,44.00,42,11,27,20,34,'2025-08-05 01:48:14','2025-08-05 05:53:17','وەستا هێمن','','unpaid'),(110,'A-0106',194,'هەواری شار',9.00,44.00,42,11,27,17,31,'2025-08-05 01:56:39','2025-08-05 05:53:17','وەستا هێمن','','unpaid'),(111,'A-0107',194,'هەواری شار',1.50,44.00,42,11,27,19,41,'2025-08-05 03:52:54','2025-08-05 05:53:17','وەستا هێمن','','unpaid'),(112,'A-0108',50,'معمل چیمەنتۆی ماس',7.00,45.00,27,11,27,23,29,'2025-08-05 04:20:31','2025-08-05 05:53:39','وەستا هێمن','','unpaid'),(113,'A-0109',195,'گوندی قەرەچەتان',9.00,45.00,42,10,26,13,38,'2025-08-05 04:43:48','2025-08-05 05:53:55','حاجی کاکەمەند','','unpaid'),(114,'A-0110',195,'گوندی قەرەچەتان',12.00,45.00,42,10,26,18,33,'2025-08-05 05:45:50','2025-08-05 05:53:55','حاجی کاکەمەند','','unpaid'),(115,'A-0111',107,'معمل خومان',2.00,NULL,41,11,27,14,39,'2025-08-05 06:46:37',NULL,'',NULL,'unpaid'),(116,'A-0112',195,'گوندی قەرەچەتان',6.00,NULL,27,10,26,20,34,'2025-08-05 06:55:24',NULL,'حاجی کاکەمەند',NULL,'unpaid'),(117,'A-0113',196,'کانی کوردە',9.00,NULL,26,10,26,17,31,'2025-08-05 07:48:11',NULL,'ئارام عمر علی',NULL,'unpaid'),(118,'A-0114',196,'کانی کوردە',9.00,NULL,26,10,26,23,29,'2025-08-05 07:56:13',NULL,'ئارام عمر علی',NULL,'unpaid'),(119,'A-0115',29,'كه نا كاوا',6.00,NULL,22,10,26,13,38,'2025-08-05 10:33:14',NULL,'حاجي بيستون',NULL,'unpaid');
/*!40000 ALTER TABLE `concrete_receipts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_debt_payments`
--

DROP TABLE IF EXISTS `customer_debt_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_debt_payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `customer_id` int NOT NULL,
  `date` date NOT NULL,
  `dolar_rate` decimal(10,2) DEFAULT '0.00',
  `paid_usd` decimal(14,2) DEFAULT '0.00',
  `paid_iqd` decimal(20,2) DEFAULT '0.00',
  `discount` decimal(14,2) DEFAULT '0.00',
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `from_opening_debt_usd` decimal(14,2) DEFAULT '0.00',
  `from_sales_usd` decimal(14,2) DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  CONSTRAINT `customer_debt_payments_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_debt_payments`
--

LOCK TABLES `customer_debt_payments` WRITE;
/*!40000 ALTER TABLE `customer_debt_payments` DISABLE KEYS */;
INSERT INTO `customer_debt_payments` VALUES (1,160,'2025-08-04',139650.00,0.00,600000.00,0.35,'',430.00,0.00),(2,116,'2025-08-04',139400.00,0.00,75000.00,0.00,'',53.80,0.00),(3,170,'2025-08-04',139440.00,1900.00,75000.00,0.21,'',1954.00,0.00),(4,163,'2025-08-04',139400.00,90.00,0.00,0.00,'',90.00,0.00);
/*!40000 ALTER TABLE `customer_debt_payments` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_after_insert_customer_debt_payments` AFTER INSERT ON `customer_debt_payments` FOR EACH ROW BEGIN
    IF NEW.paid_usd > 0 THEN
        INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
        VALUES (NEW.date, 'deposit', 0, NEW.paid_usd, 'دۆلار', 'گەڕاندنەوەی قەرزی کڕیار', NULL);
    END IF;
    IF NEW.paid_iqd > 0 THEN
        INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
        VALUES (NEW.date, 'deposit', NEW.paid_iqd, 0, 'دینار', 'گەڕاندنەوەی قەرزی کڕیار', NULL);
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_before_update_customer_debt_payments` BEFORE UPDATE ON `customer_debt_payments` FOR EACH ROW BEGIN
    -- سڕینەوەی deposit کۆن
    IF OLD.paid_usd > 0 THEN
        DELETE FROM cash_box
        WHERE `date` = OLD.date AND `type` = 'deposit' AND amount_usd = OLD.paid_usd AND currency = 'دۆلار' AND note = 'گەڕاندنەوەی قەرزی کڕیار';
    END IF;
    IF OLD.paid_iqd > 0 THEN
        DELETE FROM cash_box
        WHERE `date` = OLD.date AND `type` = 'deposit' AND amount_iqd = OLD.paid_iqd AND currency = 'دینار' AND note = 'گەڕاندنەوەی قەرزی کڕیار';
    END IF;
    -- زیادکردنی deposit نوێ
    IF NEW.paid_usd > 0 THEN
        INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
        VALUES (NEW.date, 'deposit', 0, NEW.paid_usd, 'دۆلار', 'گەڕاندنەوەی قەرزی کڕیار', NULL);
    END IF;
    IF NEW.paid_iqd > 0 THEN
        INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
        VALUES (NEW.date, 'deposit', NEW.paid_iqd, 0, 'دینار', 'گەڕاندنەوەی قەرزی کڕیار', NULL);
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_before_delete_customer_debt_payments` BEFORE DELETE ON `customer_debt_payments` FOR EACH ROW BEGIN
    -- سڕینەوەی deposit لە cash_box
    IF OLD.paid_usd > 0 THEN
        DELETE FROM cash_box
        WHERE `date` = OLD.date AND `type` = 'deposit' AND amount_usd = OLD.paid_usd AND currency = 'دۆلار' AND note = 'گەڕاندنەوەی قەرزی کڕیار';
    END IF;
    IF OLD.paid_iqd > 0 THEN
        DELETE FROM cash_box
        WHERE `date` = OLD.date AND `type` = 'deposit' AND amount_iqd = OLD.paid_iqd AND currency = 'دینار' AND note = 'گەڕاندنەوەی قەرزی کڕیار';
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mobile1` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mobile2` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `opening_debt_usd` decimal(14,2) DEFAULT '0.00',
  `opening_debt_iqd` decimal(20,2) DEFAULT '0.00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=197 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES (24,'ک.ڕەوەند هاوڕێ  سەرچناری','07702137981','',13864.00,0.00),(25,'و.ئازاد ','07718322616','07701575737',2102.00,0.00),(26,'و.ڕێبواری شمشە','07719937996','',2654.00,0.00),(27,'و.هەرێم','07730578181','',267.00,0.00),(28,'حاجی بێستوون','07730243443','',5400.00,0.00),(29,'ک.عبدالواحد موهەندیس','07701404077','',1440.00,0.00),(30,'حاجی هەمزە','07728609323','',540.00,0.00),(31,'و.ڕزگار','07725220604','',0.00,0.00),(32,'حاجی هێمن ','07711155952','',0.00,0.00),(33,'و.کمال ','07725424376','',0.00,0.00),(34,'و.کاروان','07701418007','',337.50,0.00),(35,'و.ئاوات','07702209014','',0.00,0.00),(36,'وەستا صدیق ','07701545876','',2003.00,0.00),(37,'و.قالە','07708684707','',0.00,0.00),(38,'و.مەریوان','07701426246','',0.00,0.00),(39,'و.دیار حاجی ئاوا','07740909849','',2484.00,0.00),(40,'و.سورکێو','07730658074','',1050.00,0.00),(42,'و.احمد','07725430571','',0.00,0.00),(43,'و.ئەردەڵان','07704233272','',4000.00,0.00),(44,'و.ئاسۆ زەرزی ','07721063147','',0.00,0.00),(45,'و.عزیز','07725252437','',0.00,0.00),(46,'و.بابان','07701426451','',0.00,0.00),(47,'و.ئەسوەد','07702289390','',0.00,0.00),(48,'و.بەسام','07706639203','',0.00,0.00),(49,'و.دلێر','07736957805','',0.00,0.00),(50,'و.فەرهاد','07701535161','',4305.00,0.00),(51,'و.حسن','07701209273','',0.00,0.00),(52,'کامەران سلیم قادر','07740864880','',768.00,0.00),(53,'و.کارمەند','07705018075','',0.00,0.00),(54,'و.کرمانج','07740879864','',0.00,0.00),(55,'و.مەهدی','07701569138','',0.00,0.00),(56,'و.موڕاد','07713650909','',0.00,0.00),(57,'و.ئومێد','07701583780','',0.00,0.00),(58,'و.قەیسەر','07716633425','',663.00,0.00),(59,'و.ڕیاز','07710190190','',0.00,0.00),(60,'و.سەرباز ','07732641814','',0.00,0.00),(61,'و.شەماڵ','07704029867','',0.00,0.00),(62,'و.شەوکەت','07703609259','',0.00,0.00),(63,'و.شاخەوان','07719939628','',0.00,0.00),(64,'و.شێرکۆ','07701544332','',0.00,0.00),(65,'و.شوانەی دوکان','07719708949','',0.00,0.00),(66,'و.شوانەی ڕاپەڕین','07728531796','',45.00,0.00),(67,'و.وەلید','07701422611','',0.00,0.00),(68,'و.یەحیا','07821349856','',0.00,0.00),(69,'ک.ڕاستی  معمل هێلکە','07731935663','',26591.00,0.00),(70,'ک.ئاودێر کۆماندۆ','07701449525','',5367.00,0.00),(71,'حاکم احمد ','07718322117','',5573.00,0.00),(73,'و.هیوا','07717528480','',585.00,0.00),(75,'كاك فاتيح','07725180064','',0.00,0.00),(76,'وەستا جيهاد','07725183571','',0.00,0.00),(77,'و.سەروەر','07701435098','',230.00,0.00),(78,'كاك هاورێ','00000000','',0.00,0.00),(79,'كاك ئارام','07719713616','',0.00,0.00),(80,'كاك محمد','07709625973','',0.00,0.00),(81,'ك. هاوڕێ','07725043618','',0.00,0.00),(82,'كاك محمد بارام','07736307070','',0.00,0.00),(83,'و.سالار','07702211444','',0.00,0.00),(84,'كاك ئاسۆ','07722130770','',0.00,0.00),(85,'عدنان حسن محمد','07701457479','',0.00,0.00),(86,'كاك ريبوار','07725264725','',0.00,0.00),(87,'مامۆستا هاوڕێ ','07725416006','',0.00,0.00),(88,'كاك ئاودێر','07708686067','',0.00,0.00),(89,'ك.ئاسۆ','0773555990','',0.00,0.00),(90,'مەلا کارزان','07701334223','',0.00,0.00),(91,'و.ريبوار ','07708101451','',0.00,0.00),(92,'حاجی محمد کارگەی تەنکی ','07701525275','',5005.00,0.00),(93,'كاك ئارام','07722141660','',0.00,0.00),(94,'كاك سۆران','07701543657','',0.00,0.00),(95,'كاک بەرهەم','07701913611','',0.00,0.00),(96,'كاك عیماد مەعمەل درع','07736991001','',1441.00,0.00),(97,'كاك بێستوون کۆپتەر','07704451074','',0.00,0.00),(99,'كاك ازار ','07700580808','',0.00,0.00),(100,'كاك ازر','07732303131','',0.00,0.00),(101,'كاك هاوكار دراوسێ','07725323040','',6384.00,0.00),(102,'كاك حه سو ','07717344090','',0.00,0.00),(104,'كاك مەریوان','07719908315','',0.00,0.00),(105,'ک.تۆفیق اسماعیل','07725260195','',20955.00,0.00),(106,'وه ستا امانج ','07705183132','',0.00,0.00),(107,'دانا کۆنکرێت','07731445414','',0.00,0.00),(108,'وه ستا هيمن ','07838545000','',0.00,0.00),(109,'كاك ئاکۆ','07725341849','',0.00,0.00),(110,'وەستا دڵشاد','07717530206','',0.00,0.00),(111,'كاك سامان ئاوی ژیان','07701525351','',9141.00,0.00),(112,'كاك اسماعيل','07501435661','',0.00,0.00),(113,'هاوڕێ مجید','07700494705','',0.00,0.00),(114,'ک.ئاسۆس مهندس','07725112257','',1200.00,0.00),(116,'كاك شاڵاو پەمپ','07748168525','',529.20,0.00),(117,'محمد احمد','07725290417','',0.00,0.00),(118,'حاجي محمود','07725196494','',0.00,0.00),(119,'هێدی وا‌حد علي','07729969698','',0.00,0.00),(120,'مامۆستا جلیلی دوکان','07732652031','',2488.00,0.00),(121,'وەستا فەرمان قەڵاتی','07501851192','',192.00,0.00),(122,'بەیتەکانی نزیک خۆمان','07502432688','',250.00,0.00),(123,'کاک لەوەند-داربەڕوو','07734121414','',88.00,0.00),(124,'خاڵە کاروانی پەنچەر چی','07701439343','',1622.00,0.00),(125,'کابرای دەعم-تاسڵوجە','07703994544','',132.00,0.00),(126,'وەستا اسماعیل م','07717582811','',275.00,0.00),(127,'کاک گۆران ئاسنگەر','07501207678','',100.00,0.00),(128,'کاک ڕەوکانی کانی میران','07725044706','',13.00,0.00),(129,'کاک ڕێباز قایکەن','07725198045','',80.00,0.00),(130,'کاک یادگاری ئاسایش','07717584065','',180.00,0.00),(131,'مام فتاح سەوزە','07721063135','',67.00,0.00),(132,'کاک نزار قایکەن','07725172724','',225.00,0.00),(133,'وەستا حسن بچکۆل+وەستا دڵشاد','07701536762','07701516793',1509.00,0.00),(134,'وەستا وریا','07708052728','',400.00,0.00),(135,'وەستا احمد شارستانی','07723902010','',300.00,0.00),(136,'وەستا خلیل ','07708663798','',1032.00,0.00),(137,'کاک حسن دەواجین','07501529434','',1624.00,0.00),(138,'بەژدار مام احمد ','07702054848','',180.00,0.00),(139,'کاک ڕۆژگار مقاول','07701565647','',2965.00,0.00),(140,'وەستا علی','07701931597','',553.00,0.00),(141,'کاک هەڵمەتی خۆمان','07721526666','',14839.00,0.00),(142,'کۆژین ئەفەندی','07704224946','',50.00,0.00),(143,'مام وەهاب','07725234609','',909.00,0.00),(144,'کاک گۆرانە موهەندیس','07702252624','',1700.00,0.00),(145,'کاک ئەرسەلان نوسینگە','07701361831','',0.00,0.00),(146,'ک.ئاسۆ زەرزی','07701063147','',0.00,0.00),(147,'هێمن حسن شێخۆ','07741525532','',2192.00,0.00),(148,'کاک هاوکاری  میکسەر','07740823861','',47.00,0.00),(149,'ڕەوەزی برادەرم','07719706620','',272.00,0.00),(150,'کۆمپانیای قەیوان','07700823197','',2150.00,0.00),(151,'کاک میرانی بیران','07701530350','',94.00,0.00),(152,'مزگەوتی تەقوا','07736801515','',5492.00,0.00),(153,'کاک مەریوانی چەقش','07725321319','',580.00,0.00),(154,'م.سامان  قازانی','07719908092','',2062.00,0.00),(155,'و.ئاری کارەبا','07703002173','',258.00,0.00),(156,'کاک شێرکۆ/بەرزانی خۆمان','07719425688','',528.00,0.00),(157,'ئارام عمر علی','07725252858','',651.00,0.00),(158,'وەستا هانای مۆبیلیات','07701954256','',748.00,0.00),(159,'م.سۆران + ڕاستگۆ/و.ئازاد','07748213397','',690.00,0.00),(160,'کاک هێمن موغاخ','07719702022','',0.00,0.00),(161,'کاک عمر موغاخ/بەرزانی خۆمان','07719473119','',430.00,0.00),(162,'بەرزان عزیز  موغاخی','07731464747','',352.00,0.00),(163,'پێشڕەو مقاول','07725047522','',0.00,0.00),(164,'م.کمال','07725184678','',552.00,0.00),(165,'کاک سەربەستی  پەمپ','07769716198','',1935.00,0.00),(166,'کاک ئاواتی محامی','07701544158','',2116.00,0.00),(167,'کاک سەرخێڵ','07702077070','',2574.00,0.00),(168,'مامە کاوە چیمانتۆ','07701546853','',1035.00,0.00),(169,'کاک سۆران - کاک پشتیوان','07701574669','',598.00,0.00),(170,'حاجی سیروان ڕۆماکس','07725213456','',0.00,0.00),(171,'کاک ئارام تاقە شەمسی','07725247638','',2265.00,0.00),(172,'کاک بەختیار-کاک شیروانی خۆمان','07730972627','',2025.00,0.00),(173,'کاک هێرش گەبەیی','07722044858','',1474.00,0.00),(174,'کاک دڵشاد برای حاجی فاروق','07721045538','',840.00,0.00),(175,'کاک هەڤاڵ  قەرەچەتانی','07736959093','07718323431',344.00,0.00),(176,'کاک سێبور/ئامانجی خۆمان','07701570654','',176.00,0.00),(177,'کاک جبار شۆفڵ','07701577627','',276.00,0.00),(178,'کاک اسماعیل خەستەخانەی بازیان','07766836575','',9701.00,0.00),(179,'کاک هونەری  کارەبا','07736993996','',1620.00,0.00),(180,'مزگەوتی تەلانیەکان','07703524849','',638.00,0.00),(181,'کاک شوان مقاول','07719926814','',1401.00,0.00),(182,'کاک ڕێدیار تاسڵوجە','07711559661','',495.00,0.00),(183,'مزگەوتی کەلەباش ','07719430971','',1422.00,0.00),(184,'ئەکرەم نوسینگە','07701524406','',2262.00,0.00),(185,'کاک سۆران موهەندیس','07725263729','07701543656',1364.00,0.00),(186,'کاک دانا گولیجەیی','07725246605','',0.00,0.00),(187,'کاک ئارس موهەندیس','07725180726','',0.00,0.00),(188,'عباس رەمەزان محمد','07511798279','',0.00,0.00),(189,'و.عبدالله عباس','07703335531','',0.00,0.00),(190,'حاجی جوامێر ئومێد','07731332020','',211.00,0.00),(191,'و. حیدەر شمشە','07701204538','',0.00,0.00),(192,'شێخ لوقمان','07748180209','',0.00,0.00),(193,'کاک شێرکۆ معمل چیمەنتۆی ماس','07701539539','',0.00,0.00),(194,'وەستا هێمن شمشە','07721064753','',0.00,0.00),(195,'حاجی کاکەمەند قەرەچەتانی','07701555064','',0.00,0.00),(196,'سەردار عبدللە','07725232532','',0.00,0.00);
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `debt_payments`
--

DROP TABLE IF EXISTS `debt_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `debt_payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int NOT NULL,
  `date` date NOT NULL,
  `amount_usd` decimal(14,2) DEFAULT '0.00',
  `amount_iqd` decimal(20,2) DEFAULT '0.00',
  `note` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `dollar_rate` decimal(10,2) DEFAULT '150000.00',
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `debt_payments_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`) ON DELETE CASCADE,
  CONSTRAINT `debt_payments_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `debt_payments`
--

LOCK TABLES `debt_payments` WRITE;
/*!40000 ALTER TABLE `debt_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `debt_payments` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_after_insert_debt_payments` AFTER INSERT ON `debt_payments` FOR EACH ROW BEGIN
    -- هەرکاتێک پارە دەگێڕیتەوە بۆ کۆمپانیا، پارە لە قاسە دەکەیتەوە
    IF NEW.amount_usd > 0 THEN
        INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
        VALUES (NEW.date, 'withdraw', 0, NEW.amount_usd, 'دۆلار', CONCAT('گەڕاندنەوەی قەرزی کۆمپانیا: ', NEW.company_id), NEW.created_by);
    END IF;
    IF NEW.amount_iqd > 0 THEN
        INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
        VALUES (NEW.date, 'withdraw', NEW.amount_iqd, 0, 'دینار', CONCAT('گەڕاندنەوەی قەرزی کۆمپانیا: ', NEW.company_id), NEW.created_by);
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_before_update_debt_payments` BEFORE UPDATE ON `debt_payments` FOR EACH ROW BEGIN
    -- DELETE مامەڵەی کۆن
    IF OLD.amount_usd > 0 THEN
        DELETE FROM cash_box
        WHERE `date` = OLD.date AND `type` = 'withdraw' AND amount_usd = OLD.amount_usd AND currency = 'دۆلار' AND note = CONCAT('گەڕاندنەوەی قەرزی کۆمپانیا: ', OLD.company_id);
    END IF;
    IF OLD.amount_iqd > 0 THEN
        DELETE FROM cash_box
        WHERE `date` = OLD.date AND `type` = 'withdraw' AND amount_iqd = OLD.amount_iqd AND currency = 'دینار' AND note = CONCAT('گەڕاندنەوەی قەرزی کۆمپانیا: ', OLD.company_id);
    END IF;

    -- INSERT مامەڵەی نوێ
    IF NEW.amount_usd > 0 THEN
        INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
        VALUES (NEW.date, 'withdraw', 0, NEW.amount_usd, 'دۆلار', CONCAT('گەڕاندنەوەی قەرزی کۆمپانیا: ', NEW.company_id), NEW.created_by);
    END IF;
    IF NEW.amount_iqd > 0 THEN
        INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
        VALUES (NEW.date, 'withdraw', NEW.amount_iqd, 0, 'دینار', CONCAT('گەڕاندنەوەی قەرزی کۆمپانیا: ', NEW.company_id), NEW.created_by);
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_before_delete_debt_payments` BEFORE DELETE ON `debt_payments` FOR EACH ROW BEGIN
    IF OLD.amount_usd > 0 THEN
        DELETE FROM cash_box
        WHERE `date` = OLD.date AND `type` = 'withdraw' AND amount_usd = OLD.amount_usd AND currency = 'دۆلار' AND note = CONCAT('گەڕاندنەوەی قەرزی کۆمپانیا: ', OLD.company_id);
    END IF;
    IF OLD.amount_iqd > 0 THEN
        DELETE FROM cash_box
        WHERE `date` = OLD.date AND `type` = 'withdraw' AND amount_iqd = OLD.amount_iqd AND currency = 'دینار' AND note = CONCAT('گەڕاندنەوەی قەرزی کۆمپانیا: ', OLD.company_id);
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `drivers`
--

DROP TABLE IF EXISTS `drivers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `drivers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `load_capacity` decimal(10,2) DEFAULT NULL COMMENT 'بەتاڵەی بارهەڵگر بە کیلۆگرام',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `drivers`
--

LOCK TABLES `drivers` WRITE;
/*!40000 ALTER TABLE `drivers` DISABLE KEYS */;
INSERT INTO `drivers` VALUES (4,'داستان علی',NULL),(5,'کاک دەشتی',NULL),(6,'سۆران',NULL),(7,'ڕامیار جلال',NULL),(8,'ئاوات',NULL),(9,'دیلان  کمال',NULL),(10,'کارزان کمال',NULL),(11,'محمد سەردار',NULL),(12,'محمد علی',NULL),(13,'کاک عیماد شۆفێر',NULL),(14,'کاک شاخەوان',NULL),(15,'شاخەوان علی حاجی',NULL),(16,'ڕەنجە',NULL),(17,'مەتین',NULL),(18,'هاوکار',NULL);
/*!40000 ALTER TABLE `drivers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_payments`
--

DROP TABLE IF EXISTS `employee_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `salary` decimal(15,2) NOT NULL,
  `karwanhisabi` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `bonus` decimal(15,2) DEFAULT '0.00',
  `pay_month` varchar(7) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `total` decimal(15,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  CONSTRAINT `employee_payments_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_payments`
--

LOCK TABLES `employee_payments` WRITE;
/*!40000 ALTER TABLE `employee_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_payments` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_after_insert_employee_payments` AFTER INSERT ON `employee_payments` FOR EACH ROW BEGIN
    INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
    VALUES (NEW.created_at, 'withdraw', NEW.total, 0, 'دینار', CONCAT('پارەدان بە کارمەند: ', NEW.employee_id), NULL);
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_after_insert_employee_payment_cash_box` AFTER INSERT ON `employee_payments` FOR EACH ROW BEGIN
    INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
    VALUES (NEW.created_at, 'withdraw', NEW.total, 0, 'دینار', CONCAT('پارەدان بۆ کارمەند: ', NEW.employee_id), NULL);
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_before_update_employee_payments` BEFORE UPDATE ON `employee_payments` FOR EACH ROW BEGIN
    DECLARE difference DECIMAL(15,2);
    
    -- Calculate the difference
    SET difference = NEW.total - OLD.total;
    
    -- If there's a difference, handle it
    IF difference != 0 THEN
        IF difference > 0 THEN
            -- New amount is higher - withdraw the difference
            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NOW(), 'withdraw', difference, 0, 'دینار', CONCAT('زیادکردنی پارەدان بە کارمەند: ', NEW.employee_id), NULL);
        ELSE
            -- New amount is lower - deposit the difference (return money)
            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NOW(), 'deposit', ABS(difference), 0, 'دینار', CONCAT('کەمکردنی پارەدان بە کارمەند: ', NEW.employee_id), NULL);
        END IF;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_before_update_employee_payment_cash_box` BEFORE UPDATE ON `employee_payments` FOR EACH ROW BEGIN
    DECLARE difference DECIMAL(15,2);
    
    -- Calculate the difference
    SET difference = NEW.total - OLD.total;
    
    -- If there's a difference, handle it
    IF difference != 0 THEN
        IF difference > 0 THEN
            -- New amount is higher - withdraw the difference
            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NOW(), 'withdraw', difference, 0, 'دینار', CONCAT('زیادکردنی پارەدان بە کارمەند: ', NEW.employee_id), NULL);
        ELSE
            -- New amount is lower - deposit the difference (return money)
            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NOW(), 'deposit', ABS(difference), 0, 'دینار', CONCAT('کەمکردنی پارەدان بە کارمەند: ', NEW.employee_id), NULL);
        END IF;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_before_delete_employee_payments` BEFORE DELETE ON `employee_payments` FOR EACH ROW BEGIN
    INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
    VALUES (NOW(), 'deposit', OLD.total, 0, 'دینار', CONCAT('گەڕانەوەی پارەدان بە کارمەند: ', OLD.employee_id), NULL);
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_before_delete_employee_payment_cash_box` BEFORE DELETE ON `employee_payments` FOR EACH ROW BEGIN
    DELETE FROM cash_box
    WHERE `date` = OLD.created_at
      AND `type` = 'withdraw'
      AND amount_iqd = OLD.total
      AND currency = 'دینار'
      AND note = CONCAT('پارەدان بۆ کارمەند: ', OLD.employee_id);
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employees` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `mobile` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('شۆفێر','موحاسیب','وەکیل') COLLATE utf8mb4_general_ci NOT NULL,
  `salary` decimal(15,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
INSERT INTO `employees` VALUES (4,'شاخەوان','07709245698','شۆفێر',500000.00),(22,'بازیان','07731445414','شۆفێر',2000000.00),(23,'دانا','07729950101','موحاسیب',1150000.00),(24,'شاخەوان','07736943309','شۆفێر',750000.00),(25,'بەرزان','07719702022','شۆفێر',1150000.00),(26,'شاڵاو','07748168525','شۆفێر',1150000.00),(27,'سەربەست','07769716198','شۆفێر',1150000.00),(28,'سەردار','07732708916','شۆفێر',1000000.00),(29,'طارق','07701967088','شۆفێر',750000.00),(30,'عماد','07824865268','شۆفێر',750000.00),(31,'علاوی','07824864286','شۆفێر',750000.00),(32,'احمد(ابو روەیدا)','07824872364','شۆفێر',750000.00),(33,'ئامانج','07748214162','شۆفێر',750000.00),(34,'وشیار','07741599776','شۆفێر',750000.00),(35,'شڤان','07719682166','شۆفێر',1000000.00),(37,'عادل','07701790359','شۆفێر',750000.00),(38,'ڕزگار','07738887562','شۆفێر',750000.00),(39,'هۆژین','07717528480','شۆفێر',750000.00),(41,'هاوکار میکسەر','07740823861','شۆفێر',750000.00),(42,'ڕەوەند','07725451420','شۆفێر',750000.00);
/*!40000 ALTER TABLE `employees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_by_unit`
--

DROP TABLE IF EXISTS `inventory_by_unit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_by_unit` (
  `id` int NOT NULL AUTO_INCREMENT,
  `material_id` int NOT NULL,
  `unit_type` enum('carton','piece','barrel','bag','liter') COLLATE utf8mb4_general_ci NOT NULL,
  `quantity` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `material_unit_unique` (`material_id`,`unit_type`),
  CONSTRAINT `inventory_by_unit_ibfk_1` FOREIGN KEY (`material_id`) REFERENCES `inventory_materials` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_by_unit`
--

LOCK TABLES `inventory_by_unit` WRITE;
/*!40000 ALTER TABLE `inventory_by_unit` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_by_unit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_log`
--

DROP TABLE IF EXISTS `inventory_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `material_id` int NOT NULL,
  `operation_type` enum('INSERT','UPDATE','DELETE','ADJUSTMENT') COLLATE utf8mb4_general_ci NOT NULL,
  `quantity` decimal(15,2) NOT NULL,
  `unit_type` enum('carton','piece','barrel','bag','liter') COLLATE utf8mb4_general_ci NOT NULL,
  `reference_id` int DEFAULT NULL,
  `reference_table` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_material_id` (`material_id`),
  KEY `idx_operation_type` (`operation_type`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `inventory_log_ibfk_1` FOREIGN KEY (`material_id`) REFERENCES `inventory_materials` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_log`
--

LOCK TABLES `inventory_log` WRITE;
/*!40000 ALTER TABLE `inventory_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_materials`
--

DROP TABLE IF EXISTS `inventory_materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_materials` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `unit_type` enum('carton','piece','barrel','bag','liter') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'piece',
  `pieces_per_carton` int DEFAULT NULL COMMENT 'Number of pieces in one carton',
  `bags_per_barrel` int DEFAULT NULL COMMENT 'Number of bags in one barrel',
  `liters_per_bag` decimal(10,2) DEFAULT NULL COMMENT 'Number of liters in one bag',
  `liters_per_barrel` decimal(10,2) DEFAULT NULL COMMENT 'Total liters in one barrel',
  `current_quantity` decimal(15,2) NOT NULL DEFAULT '0.00',
  `currency_type` enum('دینار','دۆلار') COLLATE utf8mb4_general_ci DEFAULT 'دینار',
  `purchase_price_usd` decimal(15,2) DEFAULT '0.00',
  `purchase_price_iqd` decimal(15,2) DEFAULT '0.00',
  `price_per_piece` decimal(15,2) DEFAULT '0.00' COMMENT 'Price per individual piece',
  `price_per_liter` decimal(15,2) DEFAULT '0.00' COMMENT 'Price per liter',
  `price_per_bag` decimal(15,2) DEFAULT '0.00' COMMENT 'Price per bag',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_materials`
--

LOCK TABLES `inventory_materials` WRITE;
/*!40000 ALTER TABLE `inventory_materials` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_materials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `list_materials`
--

DROP TABLE IF EXISTS `list_materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `list_materials` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `unit_type` enum('کارتۆن','دانە','بەرمیل','دەبە','لیتر') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'دانە',
  `quantity` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `currency_type` enum('دینار','دۆلار') COLLATE utf8mb4_general_ci DEFAULT 'دینار',
  `purchase_price_usd` decimal(15,2) DEFAULT '0.00',
  `purchase_price_iqd` decimal(15,2) DEFAULT '0.00',
  `pieces_per_carton` int DEFAULT NULL COMMENT 'ژمارەی دانە لە کارتۆن',
  `buckets_per_barrel` int DEFAULT NULL COMMENT 'ژمارەی دەبە لە بەرمیل',
  `liters_per_bucket` decimal(10,2) DEFAULT NULL COMMENT 'ژمارەی لیتر لە دەبە',
  `liters_per_barrel` decimal(10,2) DEFAULT NULL COMMENT 'کۆی لیتر لە بەرمیل',
  `price_per_piece_usd` decimal(15,2) DEFAULT '0.00' COMMENT 'نرخی دانە بە دۆلار',
  `price_per_piece_iqd` decimal(15,2) DEFAULT '0.00' COMMENT 'نرخی دانە بە دینار',
  `price_per_bucket_usd` decimal(15,2) DEFAULT '0.00' COMMENT 'نرخی دەبە بە دۆلار',
  `price_per_bucket_iqd` decimal(15,2) DEFAULT '0.00' COMMENT 'نرخی دەبە بە دینار',
  `price_per_liter_usd` decimal(15,2) DEFAULT '0.00' COMMENT 'نرخی لیتر بە دۆلار',
  `price_per_liter_iqd` decimal(15,2) DEFAULT '0.00' COMMENT 'نرخی لیتر بە دینار',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `list_materials`
--

LOCK TABLES `list_materials` WRITE;
/*!40000 ALTER TABLE `list_materials` DISABLE KEYS */;
INSERT INTO `list_materials` VALUES (8,'تایەی پاقلاوە 315-80-22.5','دانە',8.00,'2025-08-04 11:06:47','دۆلار',216.00,0.00,1,1,1.00,1.00,216.00,0.00,0.00,0.00,0.00,0.00),(9,'پاتری indigo','دانە',4.00,'2025-08-04 11:07:43','دۆلار',130.00,0.00,1,1,1.00,1.00,130.00,0.00,0.00,0.00,0.00,0.00),(10,'تێزاب','دەبە',30.00,'2025-08-04 11:11:44','دۆلار',17.00,0.00,1,1,20.00,20.00,0.00,0.00,0.00,0.00,0.85,0.00),(11,'خاوێنکەرەوەی مێگا','کارتۆن',10.00,'2025-08-04 11:15:17','دینار',0.00,1750.00,12,1,1.00,1.00,0.00,145.83,0.00,0.00,0.00,0.00),(12,'هەزەی حەوزی پەمپ','دانە',1.00,'2025-08-04 11:17:20','دۆلار',150.00,0.00,1,1,1.00,1.00,150.00,0.00,0.00,0.00,0.00,0.00),(13,'دەرمانی شینی پاکەرەوە','کارتۆن',3.00,'2025-08-04 11:20:15','دینار',0.00,9000.00,4,1,1.00,1.00,0.00,2250.00,0.00,0.00,0.00,0.00),(14,'سلیکۆنی ئەڵمانی','کارتۆن',9.00,'2025-08-04 11:21:55','دینار',0.00,60000.00,24,1,1.00,1.00,0.00,2500.00,0.00,0.00,0.00,0.00),(15,'گریسی حراری ','کارتۆن',7.00,'2025-08-04 11:22:51','دینار',0.00,55000.00,12,1,1.00,1.00,0.00,4583.33,0.00,0.00,0.00,0.00),(16,'سپرای بەخ','کارتۆن',9.00,'2025-08-04 11:24:16','دینار',0.00,30000.00,24,1,1.00,1.00,0.00,1250.00,0.00,0.00,0.00,0.00),(17,'بۆیاخی سپی','کارتۆن',22.00,'2025-08-04 11:26:36','دینار',0.00,66000.00,24,1,1.00,1.00,0.00,2750.00,0.00,0.00,0.00,0.00),(18,'بانسی کارتۆنی سەوز','کارتۆن',20.00,'2025-08-04 11:29:36','دۆلار',30.00,0.00,4,1,1.00,1.00,7.50,0.00,0.00,0.00,0.00,0.00),(19,'فلینجە','دانە',3.00,'2025-08-04 11:32:50','دۆلار',50.00,0.00,1,1,1.00,1.00,50.00,0.00,0.00,0.00,0.00,0.00),(20,'زاهی قاب شۆردن','کارتۆن',107.00,'2025-08-04 11:37:56','دینار',0.00,9000.00,12,1,1.00,1.00,0.00,750.00,0.00,0.00,0.00,0.00),(21,'هایدرۆلیک ','بەرمیل',130.00,'2025-08-04 11:41:26','دۆلار',600.00,0.00,1,10,20.00,200.00,0.00,0.00,60.00,0.00,3.00,0.00),(22,'ماتۆڕی بەرینی مەعمەل','دانە',1.00,'2025-08-04 11:41:47','دۆلار',450.00,0.00,1,1,1.00,1.00,450.00,0.00,0.00,0.00,0.00,0.00),(23,'گریس ','دەبە',12.00,'2025-08-04 11:44:08','دینار',0.00,35000.00,1,1,12.00,12.00,0.00,0.00,0.00,0.00,0.00,2916.67),(24,'سیوەیلی 140','دەبە',1.00,'2025-08-04 11:45:01','دینار',0.00,100000.00,1,1,18.00,18.00,0.00,0.00,0.00,0.00,0.00,5555.56),(25,'وەلفی حەماوە','دانە',6.00,'2025-08-04 11:45:28','دینار',0.00,30000.00,1,1,1.00,1.00,0.00,30000.00,0.00,0.00,0.00,0.00),(26,'قایشی پانکەی مان','دانە',1.00,'2025-08-04 11:46:26','دینار',0.00,35000.00,1,1,1.00,1.00,0.00,35000.00,0.00,0.00,0.00,0.00),(27,'بۆڵبرینی تایمی مان','دانە',1.00,'2025-08-04 11:47:10','دینار',0.00,25000.00,1,1,1.00,1.00,0.00,25000.00,0.00,0.00,0.00,0.00),(28,'حۆقنەی ئەکترۆز','دانە',2.00,'2025-08-04 11:47:52','دۆلار',25000.00,0.00,1,1,1.00,1.00,25000.00,0.00,0.00,0.00,0.00,0.00),(29,'تاقم واشەیری پەمپی هەوا','دانە',1.00,'2025-08-04 11:48:48','دینار',0.00,20000.00,1,1,1.00,1.00,0.00,20000.00,0.00,0.00,0.00,0.00),(30,'تەخریجی هەندر برێک ','دانە',1.00,'2025-08-04 11:51:20','دینار',0.00,50000.00,1,1,1.00,1.00,0.00,50000.00,0.00,0.00,0.00,0.00),(31,'کۆندەیسەری دیسکی ئەکترۆز ','دانە',2.00,'2025-08-04 11:52:10','دینار',0.00,37500.00,1,1,1.00,1.00,0.00,37500.00,0.00,0.00,0.00,0.00),(32,'کۆندەیسەری ئیستۆپ پێشەوە','دانە',1.00,'2025-08-04 11:52:43','دینار',0.00,25000.00,1,1,1.00,1.00,0.00,25000.00,0.00,0.00,0.00,0.00),(33,'فلتەری گازی ئەکترۆز و ئەکسۆر','کارتۆن',9.00,'2025-08-04 12:07:21','دینار',0.00,144000.00,12,1,1.00,1.00,0.00,12000.00,0.00,0.00,0.00,0.00),(34,'فلتەری گازی شانسی ئەکسۆر','دانە',1.00,'2025-08-04 12:10:03','دینار',0.00,20000.00,1,1,1.00,1.00,0.00,20000.00,0.00,0.00,0.00,0.00),(35,'فلتەری ڕۆنی مان','دانە',2.00,'2025-08-04 12:11:30','دینار',0.00,10000.00,1,1,1.00,1.00,0.00,10000.00,0.00,0.00,0.00,0.00),(36,'فلتەری گازی مان ','دانە',3.00,'2025-08-04 12:12:21','دینار',0.00,12000.00,1,1,1.00,1.00,0.00,12000.00,0.00,0.00,0.00,0.00),(37,'فلتەری ڕۆنی شۆفڵ','دانە',2.00,'2025-08-04 12:14:01','دینار',0.00,10000.00,1,1,1.00,1.00,0.00,10000.00,0.00,0.00,0.00,0.00),(38,'فلتەری گازی شۆفڵ','دانە',4.00,'2025-08-04 12:14:36','دینار',0.00,4000.00,1,1,1.00,1.00,0.00,4000.00,0.00,0.00,0.00,0.00),(39,'ڕۆنی 15×40','بەرمیل',80.00,'2025-08-04 12:17:53','دۆلار',600.00,0.00,1,10,20.00,200.00,0.00,0.00,60.00,0.00,3.00,0.00),(40,'هایدرۆلیکی سوکان ','کارتۆن',48.00,'2025-08-04 12:18:40','دینار',0.00,12.00,12,1,1.00,1.00,0.00,1.00,0.00,0.00,0.00,0.00),(41,'قایشی ژێروپەری مەعمەلی گەورە','دانە',1.00,'2025-08-04 12:19:16','دینار',0.00,1500000.00,1,1,1.00,1.00,0.00,1500000.00,0.00,0.00,0.00,0.00),(42,'کەپس کردنی سۆندەی سوکان','دانە',1.00,'2025-08-05 10:25:29','دینار',0.00,8000.00,1,1,1.00,1.00,0.00,8000.00,0.00,0.00,0.00,0.00);
/*!40000 ALTER TABLE `list_materials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `locations`
--

DROP TABLE IF EXISTS `locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `locations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `locations`
--

LOCK TABLES `locations` WRITE;
/*!40000 ALTER TABLE `locations` DISABLE KEYS */;
INSERT INTO `locations` VALUES (3,'کەنجاڕە'),(4,'کارگە و چەو و لمی عطا'),(5,'ماس'),(6,'لاڤارج'),(7,'غەسالەی نەورۆز'),(8,'-'),(9,'کەسارەکەی خۆمان');
/*!40000 ALTER TABLE `locations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `materials`
--

DROP TABLE IF EXISTS `materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `materials` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `type` enum('black_sand','brown_sand','gravel','cement','medicine','gas') COLLATE utf8mb4_general_ci NOT NULL,
  `unit` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `materials`
--

LOCK TABLES `materials` WRITE;
/*!40000 ALTER TABLE `materials` DISABLE KEYS */;
INSERT INTO `materials` VALUES (1,'لمی ڕەش','black_sand','کیلۆگرام'),(2,'چەو','gravel','کیلۆگرام'),(3,'چیمەنتۆ','cement','کیلۆگرام'),(4,'دەرمان','medicine','کیلۆگرام'),(5,'لمی کەسارە','brown_sand','کیلۆگرام'),(6,'گاز','gas','کیلۆگرام');
/*!40000 ALTER TABLE `materials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notes`
--

DROP TABLE IF EXISTS `notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `customer_id` int NOT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `recipient` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meter_amount` decimal(10,2) NOT NULL,
  `formula_id` int NOT NULL,
  `mixer_car_id` int DEFAULT NULL,
  `mixer_driver_id` int DEFAULT NULL,
  `pump_car_id` int DEFAULT NULL,
  `pump_driver_id` int DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `formula_id` (`formula_id`),
  KEY `mixer_car_id` (`mixer_car_id`),
  KEY `mixer_driver_id` (`mixer_driver_id`),
  KEY `pump_car_id` (`pump_car_id`),
  KEY `pump_driver_id` (`pump_driver_id`),
  KEY `date` (`date`),
  KEY `is_read` (`is_read`),
  CONSTRAINT `notes_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notes_ibfk_2` FOREIGN KEY (`formula_id`) REFERENCES `concrete_formulas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notes_ibfk_3` FOREIGN KEY (`mixer_car_id`) REFERENCES `cars` (`id`) ON DELETE SET NULL,
  CONSTRAINT `notes_ibfk_4` FOREIGN KEY (`mixer_driver_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `notes_ibfk_5` FOREIGN KEY (`pump_car_id`) REFERENCES `cars` (`id`) ON DELETE SET NULL,
  CONSTRAINT `notes_ibfk_6` FOREIGN KEY (`pump_driver_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=72 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notes`
--

LOCK TABLES `notes` WRITE;
/*!40000 ALTER TABLE `notes` DISABLE KEYS */;
INSERT INTO `notes` VALUES (50,'2025-08-02','03:30:00',37,'ڕاپەڕین','وەستا قالە',60.00,27,NULL,NULL,11,NULL,1,'2025-08-01 18:58:45','2025-08-01 20:00:41'),(51,'2025-08-02','04:00:00',113,'بەختیاری','هاورێ مجید',60.00,27,NULL,NULL,11,26,1,'2025-08-01 19:00:52','2025-08-01 20:00:41'),(52,'2025-08-02','05:00:00',105,'ڕاپەڕین','ک.تۆفیق',30.00,19,NULL,NULL,11,27,1,'2025-08-01 19:04:48','2025-08-01 20:00:40'),(55,'2025-08-03','05:30:00',146,'پیرەمەگرون','ک . ئاسۆ',11.00,27,NULL,NULL,11,27,1,'2025-08-02 18:00:11','2025-08-03 02:45:54'),(56,'2025-08-03','05:30:00',145,'بەرامبەر حەسەن تەپە','و . هیوا ٠۷۷۱۷٥۲۸٤۸٠',30.00,26,NULL,NULL,10,26,1,'2025-08-02 18:01:58','2025-08-03 02:45:40'),(58,'2025-08-03','17:20:00',186,'پیرەمەگرون','',3.00,27,NULL,NULL,NULL,NULL,1,'2025-08-03 14:20:31','2025-08-03 14:20:45'),(60,'2025-08-04','13:00:00',187,'موغاغ','وەستا سمایل',30.00,42,NULL,NULL,11,27,1,'2025-08-04 09:44:20','2025-08-04 09:56:50'),(62,'2025-08-04','13:10:00',189,'پیرەمەگرون','و. عبدالله',2.00,21,NULL,NULL,NULL,NULL,1,'2025-08-04 10:09:57','2025-08-04 10:10:22'),(63,'2025-08-04','14:10:00',191,'پیرەمەگرون','و.حیدەر',5.00,27,23,29,NULL,NULL,1,'2025-08-04 11:11:47','2025-08-04 11:12:02'),(64,'2025-08-04','14:20:00',192,'ڕاپەڕین','',5.50,42,NULL,NULL,10,26,1,'2025-08-04 11:21:38','2025-08-04 11:22:12'),(65,'2025-08-04','15:56:00',193,'معمل چیمەنتۆی ماس','کاک شێرکۆ',60.00,38,NULL,NULL,10,26,1,'2025-08-04 12:58:23','2025-08-04 12:58:33'),(66,'2025-08-05','04:00:00',194,'هەواری شار','وەستا هێمن',36.00,42,NULL,NULL,11,27,1,'2025-08-04 17:16:40','2025-08-05 01:11:41'),(67,'2025-08-05','04:00:00',113,'کانی با','وەستا هاوڕێ',14.50,42,NULL,NULL,10,26,1,'2025-08-04 17:18:52','2025-08-05 01:11:08'),(68,'2025-08-05','07:30:00',195,'گوندی قەرەچەتان','حاجی کاکەمەند',18.00,42,NULL,NULL,NULL,NULL,1,'2025-08-04 17:24:49','2025-08-05 01:14:45'),(69,'2025-08-05','07:15:00',50,'معمل چیمەنتۆی ماس','',7.00,27,NULL,NULL,NULL,NULL,1,'2025-08-04 19:06:06','2025-08-05 01:14:47'),(71,'2025-08-05','10:15:00',196,'کانی کوردە','ئارام عمر علی',18.00,26,NULL,NULL,10,26,1,'2025-08-05 07:01:57','2025-08-05 07:16:17');
/*!40000 ALTER TABLE `notes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `table_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `record_id` int DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `seen` tinyint(1) DEFAULT '0',
  `old_values` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON format of old values before change',
  `new_values` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON format of new values after change',
  `additional_info` text COLLATE utf8mb4_unicode_ci COMMENT 'Additional context information',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=214 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (4,2,'delete','concrete_receipts',24,'پسوڵەی کۆنکرێت سڕایەوە (شماره: A-0021, کڕیار: و.فەرهاد, فۆرمۆلا: ARZY 25 MEGA, بڕ: 8.50 م³)','2025-08-02 10:37:29',0,'{\"receipt_number\":\"A-0021\",\"customer_id\":50,\"customer_name\":\"و.فەرهاد\",\"location\":\"معمل جيمنتو ماس\",\"meter_amount\":\"8.50\",\"formulas_id\":27,\"formula_name\":\"ARZY 25 MEGA\",\"pump_car_id\":null,\"pump_car_name\":\"هیچ سەیارەیەک نییە\",\"pump_driver_id\":null,\"pump_driver_name\":\"هیچ شۆفێرێک نییە\",\"mixer_car_id\":18,\"mixer_car_name\":\"M15\",\"mixer_driver_id\":33,\"mixer_driver_name\":\"Unknown\",\"receiver_name\":\"ک.تۆفیق\"}',NULL,'{\"action_type\":\"concrete_receipt_deletion\",\"receipt_type\":\"concrete_delivery\",\"amount_m3\":\"8.50\",\"delivery_components\":{\"pump_car\":\"هیچ سەیارەیەک نییە\",\"mixer_car\":\"M15\"}}','130.193.208.83'),(5,7,'update','concrete_receipts',44,'پسوڵەی کۆنکرێت نوێکرایەوە (شماره: A-0041, کڕیار: هێدی وا‌حد علي, فۆرمۆلا: ARZY 30 MEGA, بڕ: 3 م³)','2025-08-02 15:22:03',0,'{\"receipt_number\":\"A-0041\",\"customer_id\":119,\"customer_name\":\"هێدی وا‌حد علي\",\"location\":\"پیرەمەگرون\",\"meter_amount\":\"2.00\",\"formulas_id\":30,\"formula_name\":\"ARZY 30 MEGA\",\"pump_car_id\":10,\"pump_car_name\":\"P1\",\"pump_driver_id\":26,\"pump_driver_name\":\"Unknown\",\"mixer_car_id\":19,\"mixer_car_name\":\"M16\",\"mixer_driver_id\":41,\"mixer_driver_name\":\"Unknown\",\"receiver_name\":\"وستا ازاد\"}','{\"receipt_number\":\"A-0041\",\"customer_id\":\"119\",\"customer_name\":\"هێدی وا‌حد علي\",\"location\":\"پیرەمەگرون\",\"meter_amount\":\"3\",\"formulas_id\":\"30\",\"formula_name\":\"ARZY 30 MEGA\",\"pump_car_id\":\"10\",\"pump_car_name\":\"P1\",\"pump_driver_id\":\"26\",\"pump_driver_name\":\"Unknown\",\"mixer_car_id\":\"19\",\"mixer_car_name\":\"M16\",\"mixer_driver_id\":\"41\",\"mixer_driver_name\":\"Unknown\",\"receiver_name\":\"وستا ازاد\"}','{\"action_type\":\"concrete_receipt_update\",\"receipt_type\":\"concrete_delivery\",\"amount_m3\":\"3\",\"delivery_components\":{\"pump_car\":\"P1\",\"mixer_car\":\"M16\"}}','130.193.207.128'),(6,8,'delete','concrete_receipts',45,'پسوڵەی کۆنکرێت سڕایەوە (شماره: A-0042, کڕیار: و.ئاسۆ زەرزی , فۆرمۆلا: ARZY 25 MEGA, بڕ: 11.00 م³)','2025-08-02 17:58:34',0,'{\"receipt_number\":\"A-0042\",\"customer_id\":44,\"customer_name\":\"و.ئاسۆ زەرزی \",\"location\":\"پیرەمەگرون\",\"meter_amount\":\"11.00\",\"formulas_id\":27,\"formula_name\":\"ARZY 25 MEGA\",\"pump_car_id\":11,\"pump_car_name\":\"P2\",\"pump_driver_id\":27,\"pump_driver_name\":\"Unknown\",\"mixer_car_id\":18,\"mixer_car_name\":\"M15\",\"mixer_driver_id\":33,\"mixer_driver_name\":\"Unknown\",\"receiver_name\":\"و. ئاسۆ\"}',NULL,'{\"action_type\":\"concrete_receipt_deletion\",\"receipt_type\":\"concrete_delivery\",\"amount_m3\":\"11.00\",\"delivery_components\":{\"pump_car\":\"P2\",\"mixer_car\":\"M15\"}}','185.56.194.100'),(7,2,'insert','sales',1,'فرۆشتنێکی نوێ زیادکرا (invoice: A-0041, A-0039, A-0037, A-0036, A-0035, A-0034, A-0033, A-0032, A-0031, A-0030, A-0029, A-0028, کڕیار: هێدی وا‌حد علي, فۆرمۆلا: ARZY 30 MEGA, بڕ: 112 م³)','2025-08-03 02:18:44',0,NULL,'{\"customer_id\":\"119\",\"customer_name\":\"هێدی وا‌حد علي\",\"formula_id\":\"30\",\"formula_name\":\"ARZY 30 MEGA\",\"recipient\":\"وستا ازاد\",\"location\":\"پیرەمەگرون\",\"quantity\":\"112\",\"price_per_unit\":\"47\",\"total_price\":\"5264.0000\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0\",\"amount_paid_iq\":\"0\",\"order_date\":\"2025-08-02\",\"invoice_number\":\"A-0041, A-0039, A-0037, A-0036, A-0035, A-0034, A-0033, A-0032, A-0031, A-0030, A-0029, A-0028\",\"notes\":\"\"}','{\"action_type\":\"sale_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0}','130.193.207.162'),(8,2,'update','sales',1,'فرۆشتنەکە نوێکرایەوە (invoice: A-0041, A-0039, A-0037, A-0036, A-0035, A-0034, A-0033, A-0032, A-0031, A-0030, A-0029, A-0028, کڕیار: هێدی وا‌حد علي, فۆرمۆلا: ARZY 30 MEGA)','2025-08-03 02:19:17',0,'{\"customer_id\":119,\"customer_name\":\"هێدی وا‌حد علي\",\"recipient\":\"وستا ازاد\",\"location\":\"پیرەمەگرون\",\"quantity\":\"112.00\",\"price_per_unit\":\"47.00\",\"total_price\":\"5264.00\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0.00\",\"amount_paid_iq\":\"0.00\",\"dolar_rate\":null,\"remaining_amount\":null,\"invoice_number\":\"A-0041, A-0039, A-0037, A-0036, A-0035, A-0034, A-0033, A-0032, A-0031, A-0030, A-0029, A-0028\",\"order_date\":\"2025-08-02\",\"notes\":\"\",\"formula_id\":30,\"formula_name\":\"ARZY 30 MEGA\",\"discount\":\"0.00\"}','{\"customer_id\":\"119\",\"customer_name\":\"هێدی وا‌حد علي\",\"recipient\":\"وستا ازاد\",\"location\":\"پیرەمەگرون\",\"quantity\":\"112.00\",\"price_per_unit\":\"47\",\"total_price\":\"5264.0000\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0.00\",\"amount_paid_iq\":\"0.00\",\"dolar_rate\":\"139450\",\"remaining_amount\":\"5264.0000\",\"invoice_number\":\"A-0041, A-0039, A-0037, A-0036, A-0035, A-0034, A-0033, A-0032, A-0031, A-0030, A-0029, A-0028\",\"order_date\":\"2025-08-02\",\"notes\":\"\",\"formula_id\":\"30\",\"formula_name\":\"ARZY 30 MEGA\",\"discount\":\"0.00\"}','{\"action_type\":\"sale_update\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":\"5264.0000\"}','130.193.207.162'),(9,2,'update','sales',1,'فرۆشتنەکە نوێکرایەوە (invoice: A-0041, A-0039, A-0037, A-0036, A-0035, A-0034, A-0033, A-0032, A-0031, A-0030, A-0029, A-0028, کڕیار: هێدی وا‌حد علي, فۆرمۆلا: ARZY 30 MEGA)','2025-08-03 02:19:50',0,'{\"customer_id\":119,\"customer_name\":\"هێدی وا‌حد علي\",\"recipient\":\"وستا ازاد\",\"location\":\"پیرەمەگرون\",\"quantity\":\"112.00\",\"price_per_unit\":\"47.00\",\"total_price\":\"5264.00\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0.00\",\"amount_paid_iq\":\"0.00\",\"dolar_rate\":\"139450.00\",\"remaining_amount\":\"5264.00\",\"invoice_number\":\"A-0041, A-0039, A-0037, A-0036, A-0035, A-0034, A-0033, A-0032, A-0031, A-0030, A-0029, A-0028\",\"order_date\":\"2025-08-02\",\"notes\":\"\",\"formula_id\":30,\"formula_name\":\"ARZY 30 MEGA\",\"discount\":\"0.00\"}','{\"customer_id\":\"119\",\"customer_name\":\"هێدی وا‌حد علي\",\"recipient\":\"وەستا ئازاد\",\"location\":\"پیرەمەگروون\",\"quantity\":\"112.00\",\"price_per_unit\":\"47.00\",\"total_price\":\"5264.00\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0.00\",\"amount_paid_iq\":\"0.00\",\"dolar_rate\":\"139450\",\"remaining_amount\":\"5264.00\",\"invoice_number\":\"A-0041, A-0039, A-0037, A-0036, A-0035, A-0034, A-0033, A-0032, A-0031, A-0030, A-0029, A-0028\",\"order_date\":\"2025-08-02\",\"notes\":\"\",\"formula_id\":\"30\",\"formula_name\":\"ARZY 30 MEGA\",\"discount\":\"0.00\"}','{\"action_type\":\"sale_update\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":\"5264.00\"}','130.193.207.162'),(10,2,'delete','sales',1,'فرۆشتنەکە سڕایەوە (invoice: A-0041, A-0039, A-0037, A-0036, A-0035, A-0034, A-0033, A-0032, A-0031, A-0030, A-0029, A-0028, کڕیار: هێدی وا‌حد علي, فۆرمۆلا: ARZY 30 MEGA)','2025-08-03 02:25:48',0,'{\"customer_id\":119,\"customer_name\":\"هێدی وا‌حد علي\",\"recipient\":\"وەستا ئازاد\",\"location\":\"پیرەمەگروون\",\"quantity\":\"112.00\",\"price_per_unit\":\"47.00\",\"total_price\":\"5264.00\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0.00\",\"amount_paid_iq\":\"0.00\",\"dolar_rate\":\"139450.00\",\"remaining_amount\":\"5264.00\",\"invoice_number\":\"A-0041, A-0039, A-0037, A-0036, A-0035, A-0034, A-0033, A-0032, A-0031, A-0030, A-0029, A-0028\",\"order_date\":\"2025-08-02\",\"notes\":\"\",\"formula_id\":30,\"formula_name\":\"ARZY 30 MEGA\",\"discount\":\"0.00\"}',NULL,'{\"action_type\":\"sale_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":\"5264.00\"}','130.193.207.162'),(11,7,'update','concrete_receipts',47,'پسوڵەی کۆنکرێت نوێکرایەوە (شماره: A-0043, کڕیار: ک.ئاسۆ زەرزی, فۆرمۆلا: ARZY 25 MEGA, بڕ: 11 م³)','2025-08-03 03:10:37',0,'{\"receipt_number\":\"A-0043\",\"customer_id\":146,\"customer_name\":\"ک.ئاسۆ زەرزی\",\"location\":\"پیرەمەگرون\",\"meter_amount\":\"10.00\",\"formulas_id\":27,\"formula_name\":\"ARZY 25 MEGA\",\"pump_car_id\":11,\"pump_car_name\":\"P2\",\"pump_driver_id\":27,\"pump_driver_name\":\"Unknown\",\"mixer_car_id\":14,\"mixer_car_name\":\"M11\",\"mixer_driver_id\":39,\"mixer_driver_name\":\"Unknown\",\"receiver_name\":\"ک . ئاسۆ\"}','{\"receipt_number\":\"A-0043\",\"customer_id\":\"146\",\"customer_name\":\"ک.ئاسۆ زەرزی\",\"location\":\"پیرەمەگرون\",\"meter_amount\":\"11\",\"formulas_id\":\"27\",\"formula_name\":\"ARZY 25 MEGA\",\"pump_car_id\":\"11\",\"pump_car_name\":\"P2\",\"pump_driver_id\":\"27\",\"pump_driver_name\":\"Unknown\",\"mixer_car_id\":\"17\",\"mixer_car_name\":\"M14\",\"mixer_driver_id\":\"31\",\"mixer_driver_name\":\"Unknown\",\"receiver_name\":\"ک . ئاسۆ\"}','{\"action_type\":\"concrete_receipt_update\",\"receipt_type\":\"concrete_delivery\",\"amount_m3\":\"11\",\"delivery_components\":{\"pump_car\":\"P2\",\"mixer_car\":\"M14\"}}','130.193.209.129'),(12,2,'update','concrete_receipts',49,'پسوڵەی کۆنکرێت نوێکرایەوە (شماره: A-0045, کڕیار: کاک ئەرسەلان نوسینگە, فۆرمۆلا: ARZY 21 MEGA, بڕ: 10.00 م³)','2025-08-03 03:20:56',0,'{\"receipt_number\":\"A-0045\",\"customer_id\":145,\"customer_name\":\"کاک ئەرسەلان نوسینگە\",\"location\":\"بەرامبەر حەسەن تەپە\",\"meter_amount\":\"10.00\",\"formulas_id\":26,\"formula_name\":\"ARZY 21 MEGA\",\"pump_car_id\":10,\"pump_car_name\":\"P1\",\"pump_driver_id\":26,\"pump_driver_name\":\"Unknown\",\"mixer_car_id\":18,\"mixer_car_name\":\"M15\",\"mixer_driver_id\":33,\"mixer_driver_name\":\"Unknown\",\"receiver_name\":\"و . هیوا ٠۷۷۱۷٥۲۸٤۸٠\"}','{\"receipt_number\":\"A-0045\",\"customer_id\":\"145\",\"customer_name\":\"کاک ئەرسەلان نوسینگە\",\"location\":\"بەرامبەر حەسەن تەپە\",\"meter_amount\":\"10.00\",\"formulas_id\":\"26\",\"formula_name\":\"ARZY 21 MEGA\",\"pump_car_id\":\"10\",\"pump_car_name\":\"P1\",\"pump_driver_id\":\"26\",\"pump_driver_name\":\"Unknown\",\"mixer_car_id\":\"18\",\"mixer_car_name\":\"M15\",\"mixer_driver_id\":\"33\",\"mixer_driver_name\":\"Unknown\",\"receiver_name\":\"و . هیوا \"}','{\"action_type\":\"concrete_receipt_update\",\"receipt_type\":\"concrete_delivery\",\"amount_m3\":\"10.00\",\"delivery_components\":{\"pump_car\":\"P1\",\"mixer_car\":\"M15\"}}','130.193.207.162'),(13,2,'update','concrete_receipts',48,'پسوڵەی کۆنکرێت نوێکرایەوە (شماره: A-0044, کڕیار: کاک ئەرسەلان نوسینگە, فۆرمۆلا: ARZY 21 MEGA, بڕ: 10.00 م³)','2025-08-03 03:23:11',0,'{\"receipt_number\":\"A-0044\",\"customer_id\":145,\"customer_name\":\"کاک ئەرسەلان نوسینگە\",\"location\":\"بەرامبەر حەسەن تەپە\",\"meter_amount\":\"10.00\",\"formulas_id\":26,\"formula_name\":\"ARZY 21 MEGA\",\"pump_car_id\":10,\"pump_car_name\":\"P1\",\"pump_driver_id\":26,\"pump_driver_name\":\"Unknown\",\"mixer_car_id\":14,\"mixer_car_name\":\"M11\",\"mixer_driver_id\":39,\"mixer_driver_name\":\"Unknown\",\"receiver_name\":\"و . هیوا ٠۷۷۱۷٥۲۸٤۸٠\"}','{\"receipt_number\":\"A-0044\",\"customer_id\":\"145\",\"customer_name\":\"کاک ئەرسەلان نوسینگە\",\"location\":\"بەرامبەر حەسەن تەپە\",\"meter_amount\":\"10.00\",\"formulas_id\":\"26\",\"formula_name\":\"ARZY 21 MEGA\",\"pump_car_id\":\"10\",\"pump_car_name\":\"P1\",\"pump_driver_id\":\"26\",\"pump_driver_name\":\"Unknown\",\"mixer_car_id\":\"14\",\"mixer_car_name\":\"M11\",\"mixer_driver_id\":\"39\",\"mixer_driver_name\":\"Unknown\",\"receiver_name\":\"و . هیوا \"}','{\"action_type\":\"concrete_receipt_update\",\"receipt_type\":\"concrete_delivery\",\"amount_m3\":\"10.00\",\"delivery_components\":{\"pump_car\":\"P1\",\"mixer_car\":\"M11\"}}','130.193.207.162'),(14,2,'update','concrete_receipts',46,'پسوڵەی کۆنکرێت نوێکرایەوە (شماره: A-0042, کڕیار: کاک ئەرسەلان نوسینگە, فۆرمۆلا: ARZY 21 MEGA, بڕ: 10.00 م³)','2025-08-03 03:23:19',0,'{\"receipt_number\":\"A-0042\",\"customer_id\":145,\"customer_name\":\"کاک ئەرسەلان نوسینگە\",\"location\":\"بەرامبەر حەسەن تەپە\",\"meter_amount\":\"10.00\",\"formulas_id\":26,\"formula_name\":\"ARZY 21 MEGA\",\"pump_car_id\":10,\"pump_car_name\":\"P1\",\"pump_driver_id\":26,\"pump_driver_name\":\"Unknown\",\"mixer_car_id\":13,\"mixer_car_name\":\"M10\",\"mixer_driver_id\":38,\"mixer_driver_name\":\"Unknown\",\"receiver_name\":\"و . هیوا ٠۷۷۱۷٥۲۸٤۸٠\"}','{\"receipt_number\":\"A-0042\",\"customer_id\":\"145\",\"customer_name\":\"کاک ئەرسەلان نوسینگە\",\"location\":\"بەرامبەر حەسەن تەپە\",\"meter_amount\":\"10.00\",\"formulas_id\":\"26\",\"formula_name\":\"ARZY 21 MEGA\",\"pump_car_id\":\"10\",\"pump_car_name\":\"P1\",\"pump_driver_id\":\"26\",\"pump_driver_name\":\"Unknown\",\"mixer_car_id\":\"13\",\"mixer_car_name\":\"M10\",\"mixer_driver_id\":\"38\",\"mixer_driver_name\":\"Unknown\",\"receiver_name\":\"و . هیوا \"}','{\"action_type\":\"concrete_receipt_update\",\"receipt_type\":\"concrete_delivery\",\"amount_m3\":\"10.00\",\"delivery_components\":{\"pump_car\":\"P1\",\"mixer_car\":\"M10\"}}','130.193.207.162'),(15,2,'insert','purchases',1,'کڕینێکی نوێ زیادکرا (invoice: 1103, کۆمپانیا: دەشتی, مادە: لمی ڕەش, بڕ: 33680 کگم)','2025-08-03 06:59:46',0,NULL,'{\"company_id\":\"1\",\"company_name\":\"دەشتی\",\"driver\":\"داستان علی\",\"location\":\"کەنجاڕە\",\"material_id\":\"1\",\"material_name\":\"لمی ڕەش\",\"amount_iqd\":\"345220.00\",\"kg\":\"33680\",\"price\":\"0\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139450\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0\",\"remaining_iqd\":\"345000\",\"bin_id\":\"1\",\"price_per_kg_iqd\":\"10250\",\"price_per_kg_usd\":\"0\",\"invoice_number\":\"1103\",\"date\":\"2025-08-02\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":345000}','130.193.207.162'),(16,2,'insert','purchases',2,'کڕینێکی نوێ زیادکرا (invoice: 1105, کۆمپانیا: دەشتی, مادە: لمی ڕەش, بڕ: 35280 کگم)','2025-08-03 07:08:10',0,NULL,'{\"company_id\":\"1\",\"company_name\":\"دەشتی\",\"driver\":\"کاک دەشتی\",\"location\":\"کەنجاڕە\",\"material_id\":\"1\",\"material_name\":\"لمی ڕەش\",\"amount_iqd\":\"361620.00\",\"kg\":\"35280\",\"price\":\"0\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139450\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0\",\"remaining_iqd\":\"361000\",\"bin_id\":\"1\",\"price_per_kg_iqd\":\"10250\",\"price_per_kg_usd\":\"0\",\"invoice_number\":\"1105\",\"date\":\"2025-08-02\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":361000}','130.193.207.162'),(17,2,'insert','purchases',3,'کڕینێکی نوێ زیادکرا (invoice: 1426, کۆمپانیا: سۆران, مادە: چەو, بڕ: 30325 کگم)','2025-08-03 07:22:41',0,NULL,'{\"company_id\":\"2\",\"company_name\":\"سۆران\",\"driver\":\"سۆران\",\"location\":\"کارگە و چەو و لمی عطا\",\"material_id\":\"2\",\"material_name\":\"چەو\",\"amount_iqd\":\"176885.73\",\"kg\":\"30325\",\"price\":\"0\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139450\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0\",\"remaining_iqd\":\"176000\",\"bin_id\":\"3\",\"price_per_kg_iqd\":\"5833\",\"price_per_kg_usd\":\"0\",\"invoice_number\":\"1426\",\"date\":\"2025-08-02\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":176000}','130.193.207.162'),(18,2,'insert','sales',2,'فرۆشتنێکی نوێ زیادکرا (invoice: A-0012, A-0011, A-0010, A-0009, A-0008, A-0007, کڕیار: هاوڕێ مجید, فۆرمۆلا: ARZY 25 MEGA, بڕ: 64 م³)','2025-08-03 07:50:07',0,NULL,'{\"customer_id\":\"113\",\"customer_name\":\"هاوڕێ مجید\",\"formula_id\":\"27\",\"formula_name\":\"ARZY 25 MEGA\",\"recipient\":\"هاورێ مجید\",\"location\":\"بەختیاری\",\"quantity\":\"64\",\"price_per_unit\":\"45.0000\",\"total_price\":\"2880.0000\",\"payment_type\":\"نەقد\",\"amount_paid_usd\":\"2880\",\"amount_paid_iq\":\"0\",\"order_date\":\"2025-08-02\",\"invoice_number\":\"A-0012, A-0011, A-0010, A-0009, A-0008, A-0007\",\"notes\":\"\"}','{\"action_type\":\"sale_creation\",\"payment_status\":\"paid\",\"currency_used\":\"USD\",\"total_paid\":2880}','130.193.209.129'),(19,2,'insert','sales',3,'فرۆشتنێکی نوێ زیادکرا (invoice: A-0014, کڕیار: هاوڕێ مجید, فۆرمۆلا: ARZY 25 MEGA, بڕ: 6 م³)','2025-08-03 07:52:17',0,NULL,'{\"customer_id\":\"113\",\"customer_name\":\"هاوڕێ مجید\",\"formula_id\":\"27\",\"formula_name\":\"ARZY 25 MEGA\",\"recipient\":\"هاورێ مجید\",\"location\":\"بەختیاری\",\"quantity\":\"6\",\"price_per_unit\":\"45.0000\",\"total_price\":\"270.0000\",\"payment_type\":\"نەقد\",\"amount_paid_usd\":\"270\",\"amount_paid_iq\":\"0\",\"order_date\":\"2025-08-02\",\"invoice_number\":\"A-0014\",\"notes\":\"\"}','{\"action_type\":\"sale_creation\",\"payment_status\":\"paid\",\"currency_used\":\"USD\",\"total_paid\":270}','130.193.209.129'),(20,2,'insert','sales',5,'فرۆشتنێکی نوێ زیادکرا (invoice: A-0023, کڕیار: و.کاروان, فۆرمۆلا: ARZY 21 MEGA, بڕ: 9 م³)','2025-08-03 07:56:52',0,NULL,'{\"customer_id\":\"34\",\"customer_name\":\"و.کاروان\",\"formula_id\":\"26\",\"formula_name\":\"ARZY 21 MEGA\",\"recipient\":\"حاكم احمد\",\"location\":\"پیرەمەگرون\",\"quantity\":\"9\",\"price_per_unit\":\"41\",\"total_price\":\"369.0000\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0\",\"amount_paid_iq\":\"0\",\"order_date\":\"2025-08-02\",\"invoice_number\":\"A-0023\",\"notes\":\"\"}','{\"action_type\":\"sale_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0}','130.193.209.129'),(21,2,'insert','sales',6,'فرۆشتنێکی نوێ زیادکرا (invoice: A-0022, A-0019, کڕیار: و.فەرهاد, فۆرمۆلا: ARZY 25 MEGA, بڕ: 16.5 م³)','2025-08-03 07:57:47',0,NULL,'{\"customer_id\":\"50\",\"customer_name\":\"و.فەرهاد\",\"formula_id\":\"27\",\"formula_name\":\"ARZY 25 MEGA\",\"recipient\":\"فەرهاد\",\"location\":\"معمل جيمنتو ماس, مه عملى جيمنتو ماس\",\"quantity\":\"16.5\",\"price_per_unit\":\"45\",\"total_price\":\"742.5000\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0\",\"amount_paid_iq\":\"0\",\"order_date\":\"2025-08-02\",\"invoice_number\":\"A-0022, A-0019\",\"notes\":\"\"}','{\"action_type\":\"sale_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0}','130.193.209.129'),(22,2,'insert','sales',7,'فرۆشتنێکی نوێ زیادکرا (invoice: A-0025, A-0024, کڕیار: محمد احمد, فۆرمۆلا: Chawy lws saqf 350, بڕ: 18.5 م³)','2025-08-03 08:01:29',0,NULL,'{\"customer_id\":\"117\",\"customer_name\":\"محمد احمد\",\"formula_id\":\"42\",\"formula_name\":\"Chawy lws saqf 350\",\"recipient\":\"وه ستا ديار\",\"location\":\"ڕاپەڕین\",\"quantity\":\"18.5\",\"price_per_unit\":\"47\",\"total_price\":\"869.5000\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0\",\"amount_paid_iq\":\"0\",\"order_date\":\"2025-08-02\",\"invoice_number\":\"A-0025, A-0024\",\"notes\":\"\"}','{\"action_type\":\"sale_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0}','130.193.209.129'),(23,2,'insert','sales',8,'فرۆشتنێکی نوێ زیادکرا (invoice: A-0038, A-0027, A-0026, کڕیار: حاجي محمود, فۆرمۆلا: Chawy lws saqf 350, بڕ: 24.5 م³)','2025-08-03 08:02:46',0,NULL,'{\"customer_id\":\"118\",\"customer_name\":\"حاجي محمود\",\"formula_id\":\"42\",\"formula_name\":\"Chawy lws saqf 350\",\"recipient\":\"\",\"location\":\"تافگا\",\"quantity\":\"24.5\",\"price_per_unit\":\"46\",\"total_price\":\"1127.0000\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0\",\"amount_paid_iq\":\"0\",\"order_date\":\"2025-08-02\",\"invoice_number\":\"A-0038, A-0027, A-0026\",\"notes\":\"\"}','{\"action_type\":\"sale_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0}','130.193.209.129'),(24,2,'update','sales',8,'فرۆشتنەکە نوێکرایەوە (invoice: A-0038, A-0027, A-0026, کڕیار: حاجي محمود, فۆرمۆلا: Chawy lws saqf 350)','2025-08-03 08:11:25',0,'{\"customer_id\":118,\"customer_name\":\"حاجي محمود\",\"recipient\":\"\",\"location\":\"تافگا\",\"quantity\":\"24.50\",\"price_per_unit\":\"46.00\",\"total_price\":\"1127.00\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0.00\",\"amount_paid_iq\":\"0.00\",\"dolar_rate\":null,\"remaining_amount\":null,\"invoice_number\":\"A-0038, A-0027, A-0026\",\"order_date\":\"2025-08-02\",\"notes\":\"\",\"formula_id\":42,\"formula_name\":\"Chawy lws saqf 350\",\"discount\":\"0.00\"}','{\"customer_id\":\"118\",\"customer_name\":\"حاجي محمود\",\"recipient\":\"\",\"location\":\"تافگا\",\"quantity\":\"24.50\",\"price_per_unit\":\"46.00\",\"total_price\":\"1127.0000\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0.00\",\"amount_paid_iq\":\"0.00\",\"dolar_rate\":\"139450\",\"remaining_amount\":\"1127.0000\",\"invoice_number\":\"A-0038, A-0027, A-0026\",\"order_date\":\"2025-08-02\",\"notes\":\"\",\"formula_id\":\"42\",\"formula_name\":\"Chawy lws saqf 350\",\"discount\":\"0.00\"}','{\"action_type\":\"sale_update\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":\"1127.0000\"}','130.193.209.129'),(25,2,'update','sales',7,'فرۆشتنەکە نوێکرایەوە (invoice: A-0025, A-0024, کڕیار: محمد احمد, فۆرمۆلا: Chawy lws saqf 350)','2025-08-03 08:12:23',0,'{\"customer_id\":117,\"customer_name\":\"محمد احمد\",\"recipient\":\"وه ستا ديار\",\"location\":\"ڕاپەڕین\",\"quantity\":\"18.50\",\"price_per_unit\":\"47.00\",\"total_price\":\"869.50\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0.00\",\"amount_paid_iq\":\"0.00\",\"dolar_rate\":null,\"remaining_amount\":null,\"invoice_number\":\"A-0025, A-0024\",\"order_date\":\"2025-08-02\",\"notes\":\"\",\"formula_id\":42,\"formula_name\":\"Chawy lws saqf 350\",\"discount\":\"0.00\"}','{\"customer_id\":\"117\",\"customer_name\":\"محمد احمد\",\"recipient\":\"وه ستا ديار\",\"location\":\"ڕاپەڕین\",\"quantity\":\"18.50\",\"price_per_unit\":\"47.0\",\"total_price\":\"869.5000\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0.00\",\"amount_paid_iq\":\"0.00\",\"dolar_rate\":\"139450\",\"remaining_amount\":\"869.5000\",\"invoice_number\":\"A-0025, A-0024\",\"order_date\":\"2025-08-02\",\"notes\":\"\",\"formula_id\":\"42\",\"formula_name\":\"Chawy lws saqf 350\",\"discount\":\"0.00\"}','{\"action_type\":\"sale_update\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":\"869.5000\"}','130.193.209.129'),(26,2,'update','sales',6,'فرۆشتنەکە نوێکرایەوە (invoice: A-0022, A-0019, کڕیار: و.فەرهاد, فۆرمۆلا: ARZY 25 MEGA)','2025-08-03 08:12:32',0,'{\"customer_id\":50,\"customer_name\":\"و.فەرهاد\",\"recipient\":\"فەرهاد\",\"location\":\"معمل جيمنتو ماس, مه عملى جيمنتو ماس\",\"quantity\":\"16.50\",\"price_per_unit\":\"45.00\",\"total_price\":\"742.50\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0.00\",\"amount_paid_iq\":\"0.00\",\"dolar_rate\":null,\"remaining_amount\":null,\"invoice_number\":\"A-0022, A-0019\",\"order_date\":\"2025-08-02\",\"notes\":\"\",\"formula_id\":27,\"formula_name\":\"ARZY 25 MEGA\",\"discount\":\"0.00\"}','{\"customer_id\":\"50\",\"customer_name\":\"و.فەرهاد\",\"recipient\":\"فەرهاد\",\"location\":\"معمل جيمنتو ماس, مه عملى جيمنتو ماس\",\"quantity\":\"16.50\",\"price_per_unit\":\"45.0\",\"total_price\":\"742.5000\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0.00\",\"amount_paid_iq\":\"0.00\",\"dolar_rate\":\"139450\",\"remaining_amount\":\"742.5000\",\"invoice_number\":\"A-0022, A-0019\",\"order_date\":\"2025-08-02\",\"notes\":\"\",\"formula_id\":\"27\",\"formula_name\":\"ARZY 25 MEGA\",\"discount\":\"0.00\"}','{\"action_type\":\"sale_update\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":\"742.5000\"}','130.193.209.129'),(27,2,'update','sales',5,'فرۆشتنەکە نوێکرایەوە (invoice: A-0023, کڕیار: و.کاروان, فۆرمۆلا: ARZY 21 MEGA)','2025-08-03 08:12:41',0,'{\"customer_id\":34,\"customer_name\":\"و.کاروان\",\"recipient\":\"حاكم احمد\",\"location\":\"پیرەمەگرون\",\"quantity\":\"9.00\",\"price_per_unit\":\"41.00\",\"total_price\":\"369.00\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0.00\",\"amount_paid_iq\":\"0.00\",\"dolar_rate\":null,\"remaining_amount\":null,\"invoice_number\":\"A-0023\",\"order_date\":\"2025-08-02\",\"notes\":\"\",\"formula_id\":26,\"formula_name\":\"ARZY 21 MEGA\",\"discount\":\"0.00\"}','{\"customer_id\":\"34\",\"customer_name\":\"و.کاروان\",\"recipient\":\"حاكم احمد\",\"location\":\"پیرەمەگرون\",\"quantity\":\"9.00\",\"price_per_unit\":\"41.0\",\"total_price\":\"369.0000\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0.00\",\"amount_paid_iq\":\"0.00\",\"dolar_rate\":\"139450\",\"remaining_amount\":\"369.0000\",\"invoice_number\":\"A-0023\",\"order_date\":\"2025-08-02\",\"notes\":\"\",\"formula_id\":\"26\",\"formula_name\":\"ARZY 21 MEGA\",\"discount\":\"0.00\"}','{\"action_type\":\"sale_update\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":\"369.0000\"}','130.193.209.129'),(28,2,'insert','purchases',4,'کڕینێکی نوێ زیادکرا (invoice: 503555, کۆمپانیا: مامە کاوە, مادە: چیمەنتۆ, بڕ: 31050 کگم)','2025-08-03 12:23:14',0,NULL,'{\"company_id\":\"3\",\"company_name\":\"مامە کاوە\",\"driver\":\"ڕامیار جلال\",\"location\":\"لاڤارج\",\"material_id\":\"3\",\"material_name\":\"چیمەنتۆ\",\"amount_iqd\":\"0\",\"kg\":\"31050\",\"price\":\"2002.73\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139450\",\"type\":\"دۆلار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"2002.73\",\"remaining_iqd\":\"0\",\"bin_id\":\"5\",\"price_per_kg_iqd\":\"0\",\"price_per_kg_usd\":\"64.5\",\"invoice_number\":\"503555\",\"date\":\"2025-08-01\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":2002.73}','130.193.209.66'),(29,2,'insert','purchases',5,'کڕینێکی نوێ زیادکرا (invoice: 503562, کۆمپانیا: مامە کاوە, مادە: چیمەنتۆ, بڕ: 31150 کگم)','2025-08-03 12:26:37',0,NULL,'{\"company_id\":\"3\",\"company_name\":\"مامە کاوە\",\"driver\":\"ئاوات\",\"location\":\"لاڤارج\",\"material_id\":\"3\",\"material_name\":\"چیمەنتۆ\",\"amount_iqd\":\"0\",\"kg\":\"31150\",\"price\":\"2009.17\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139450\",\"type\":\"دۆلار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"2009.17\",\"remaining_iqd\":\"0\",\"bin_id\":\"5\",\"price_per_kg_iqd\":\"0\",\"price_per_kg_usd\":\"64.5\",\"invoice_number\":\"503562\",\"date\":\"2025-08-01\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":2009.17}','130.193.209.66'),(30,2,'insert','purchases',6,'کڕینێکی نوێ زیادکرا (invoice: 503689, کۆمپانیا: مامە کاوە, مادە: چیمەنتۆ, بڕ: 31050 کگم)','2025-08-03 12:28:34',0,NULL,'{\"company_id\":\"3\",\"company_name\":\"مامە کاوە\",\"driver\":\"ئاوات\",\"location\":\"لاڤارج\",\"material_id\":\"3\",\"material_name\":\"چیمەنتۆ\",\"amount_iqd\":\"0\",\"kg\":\"31050\",\"price\":\"2002.73\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139450\",\"type\":\"دۆلار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"2002.73\",\"remaining_iqd\":\"0\",\"bin_id\":\"5\",\"price_per_kg_iqd\":\"0\",\"price_per_kg_usd\":\"64.5\",\"invoice_number\":\"503689\",\"date\":\"2025-08-02\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":2002.73}','130.193.209.66'),(31,2,'insert','purchases',7,'کڕینێکی نوێ زیادکرا (invoice: 82515723, کۆمپانیا: حاجی فاروق چیمەنتۆ, مادە: چیمەنتۆ, بڕ: 31600 کگم)','2025-08-03 12:32:36',0,NULL,'{\"company_id\":\"4\",\"company_name\":\"حاجی فاروق چیمەنتۆ\",\"driver\":\"دیلان  کمال\",\"location\":\"ماس\",\"material_id\":\"3\",\"material_name\":\"چیمەنتۆ\",\"amount_iqd\":\"0\",\"kg\":\"31600\",\"price\":\"2006.60\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139450\",\"type\":\"دۆلار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"2006.60\",\"remaining_iqd\":\"0\",\"bin_id\":\"6\",\"price_per_kg_iqd\":\"0\",\"price_per_kg_usd\":\"63.5\",\"invoice_number\":\"82515723\",\"date\":\"2025-08-02\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":2006.6}','130.193.209.66'),(32,2,'insert','purchases',8,'کڕینێکی نوێ زیادکرا (invoice: 82515808, کۆمپانیا: حاجی فاروق چیمەنتۆ, مادە: چیمەنتۆ, بڕ: 31680 کگم)','2025-08-03 12:34:30',0,NULL,'{\"company_id\":\"4\",\"company_name\":\"حاجی فاروق چیمەنتۆ\",\"driver\":\"دیلان  کمال\",\"location\":\"ماس\",\"material_id\":\"3\",\"material_name\":\"چیمەنتۆ\",\"amount_iqd\":\"0\",\"kg\":\"31680\",\"price\":\"2011.68\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139450\",\"type\":\"دۆلار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"2011.68\",\"remaining_iqd\":\"0\",\"bin_id\":\"6\",\"price_per_kg_iqd\":\"0\",\"price_per_kg_usd\":\"63.5\",\"invoice_number\":\"82515808\",\"date\":\"2025-08-02\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":2011.68}','130.193.209.66'),(33,2,'insert','purchases',9,'کڕینێکی نوێ زیادکرا (invoice: 503902, کۆمپانیا: مامە کاوە, مادە: چیمەنتۆ, بڕ: 31100 کگم)','2025-08-03 12:36:38',0,NULL,'{\"company_id\":\"3\",\"company_name\":\"مامە کاوە\",\"driver\":\"ڕامیار جلال\",\"location\":\"لاڤارج\",\"material_id\":\"3\",\"material_name\":\"چیمەنتۆ\",\"amount_iqd\":\"0\",\"kg\":\"31100\",\"price\":\"2005.95\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139450\",\"type\":\"دۆلار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"2005.95\",\"remaining_iqd\":\"0\",\"bin_id\":\"5\",\"price_per_kg_iqd\":\"0\",\"price_per_kg_usd\":\"64.5\",\"invoice_number\":\"503902\",\"date\":\"2025-08-03\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":2005.95}','130.193.209.66'),(34,2,'insert','purchases',10,'کڕینێکی نوێ زیادکرا (invoice: 83516201, کۆمپانیا: حاجی فاروق چیمەنتۆ, مادە: چیمەنتۆ, بڕ: 39420 کگم)','2025-08-03 12:39:27',0,NULL,'{\"company_id\":\"4\",\"company_name\":\"حاجی فاروق چیمەنتۆ\",\"driver\":\"کارزان کمال\",\"location\":\"ماس\",\"material_id\":\"3\",\"material_name\":\"چیمەنتۆ\",\"amount_iqd\":\"0\",\"kg\":\"39420\",\"price\":\"2503.17\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139450\",\"type\":\"دۆلار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"2503.17\",\"remaining_iqd\":\"0\",\"bin_id\":\"6\",\"price_per_kg_iqd\":\"0\",\"price_per_kg_usd\":\"63.5\",\"invoice_number\":\"83516201\",\"date\":\"2025-08-03\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":2503.17}','130.193.209.66'),(35,2,'insert','purchases',11,'کڕینێکی نوێ زیادکرا (invoice: 83516262, کۆمپانیا: حاجی فاروق چیمەنتۆ, مادە: چیمەنتۆ, بڕ: 39280 کگم)','2025-08-03 12:52:56',0,NULL,'{\"company_id\":\"4\",\"company_name\":\"حاجی فاروق چیمەنتۆ\",\"driver\":\"کارزان کمال\",\"location\":\"ماس\",\"material_id\":\"3\",\"material_name\":\"چیمەنتۆ\",\"amount_iqd\":\"0\",\"kg\":\"39280\",\"price\":\"2494.28\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139450\",\"type\":\"دۆلار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"2494.28\",\"remaining_iqd\":\"0\",\"bin_id\":\"6\",\"price_per_kg_iqd\":\"0\",\"price_per_kg_usd\":\"63.5\",\"invoice_number\":\"83516262\",\"date\":\"2025-08-03\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":2494.28}','130.193.209.66'),(36,7,'update','concrete_receipts',76,'پسوڵەی کۆنکرێت نوێکرایەوە (شماره: A-0072, کڕیار: کاک دانا گولیجەیی, فۆرمۆلا: ARZY 25 MEGA, بڕ: 3.00 م³)','2025-08-03 14:26:56',0,'{\"receipt_number\":\"A-0072\",\"customer_id\":186,\"customer_name\":\"کاک دانا گولیجەیی\",\"location\":\"پیرەمەگرون\",\"meter_amount\":\"3.00\",\"formulas_id\":27,\"formula_name\":\"ARZY 25 MEGA\",\"pump_car_id\":11,\"pump_car_name\":\"P2\",\"pump_driver_id\":27,\"pump_driver_name\":\"Unknown\",\"mixer_car_id\":16,\"mixer_car_name\":\"M13\",\"mixer_driver_id\":37,\"mixer_driver_name\":\"Unknown\",\"receiver_name\":\"\"}','{\"receipt_number\":\"A-0072\",\"customer_id\":\"186\",\"customer_name\":\"کاک دانا گولیجەیی\",\"location\":\"پیرەمەگرون\",\"meter_amount\":\"3.00\",\"formulas_id\":\"27\",\"formula_name\":\"ARZY 25 MEGA\",\"pump_car_id\":\"\",\"pump_car_name\":\"هیچ سەیارەیەک نییە\",\"pump_driver_id\":\"\",\"pump_driver_name\":\"هیچ شۆفێرێک نییە\",\"mixer_car_id\":\"16\",\"mixer_car_name\":\"M13\",\"mixer_driver_id\":\"37\",\"mixer_driver_name\":\"Unknown\",\"receiver_name\":\"\"}','{\"action_type\":\"concrete_receipt_update\",\"receipt_type\":\"concrete_delivery\",\"amount_m3\":\"3.00\",\"delivery_components\":{\"pump_car\":\"هیچ سەیارەیەک نییە\",\"mixer_car\":\"M13\"}}','130.193.209.129'),(37,2,'insert','purchases',12,'کڕینێکی نوێ زیادکرا (invoice: 1434, کۆمپانیا: سۆران, مادە: چەو, بڕ: 30435 کگم)','2025-08-03 15:06:25',0,NULL,'{\"company_id\":\"2\",\"company_name\":\"سۆران\",\"driver\":\"سۆران\",\"location\":\"کارگە و چەو و لمی عطا\",\"material_id\":\"2\",\"material_name\":\"چەو\",\"amount_iqd\":\"177527.35\",\"kg\":\"30435\",\"price\":\"0\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139450\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0\",\"remaining_iqd\":\"177000\",\"bin_id\":\"4\",\"price_per_kg_iqd\":\"5833\",\"price_per_kg_usd\":\"0\",\"invoice_number\":\"1434\",\"date\":\"2025-08-02\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":177000}','130.193.207.162'),(38,2,'delete','purchases',12,'کڕینەکە سڕایەوە (invoice: 1434, کۆمپانیا: سۆران, مادە: چەو)','2025-08-03 15:08:17',0,'{\"company_id\":2,\"company_name\":\"سۆران\",\"driver\":\"سۆران\",\"location\":\"کارگە و چەو و لمی عطا\",\"material_id\":2,\"material_name\":\"چەو\",\"amount_iqd\":\"177527.35\",\"kg\":\"30435.00\",\"price\":\"0.00\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139450\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0.00\",\"remaining_iqd\":\"177000\",\"bin_id\":4,\"price_per_kg_iqd\":\"5833.00\",\"price_per_kg_usd\":\"0.00\",\"invoice_number\":\"1434\",\"date\":\"2025-08-02\"}',NULL,'{\"action_type\":\"purchase_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":177000}','130.193.207.162'),(39,2,'insert','purchases',13,'کڕینێکی نوێ زیادکرا (invoice: 1434, کۆمپانیا: سۆران, مادە: چەو, بڕ: 30435 کگم)','2025-08-03 15:09:46',0,NULL,'{\"company_id\":\"2\",\"company_name\":\"سۆران\",\"driver\":\"سۆران\",\"location\":\"کارگە و چەو و لمی عطا\",\"material_id\":\"2\",\"material_name\":\"چەو\",\"amount_iqd\":\"177527.35\",\"kg\":\"30435\",\"price\":\"0\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139450\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0\",\"remaining_iqd\":\"177000\",\"bin_id\":\"4\",\"price_per_kg_iqd\":\"5833\",\"price_per_kg_usd\":\"0\",\"invoice_number\":\"1434\",\"date\":\"2025-08-03\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":177000}','130.193.207.162'),(40,2,'delete','purchases',3,'کڕینەکە سڕایەوە (invoice: 1426, کۆمپانیا: سۆران, مادە: چەو)','2025-08-03 15:10:14',0,'{\"company_id\":2,\"company_name\":\"سۆران\",\"driver\":\"سۆران\",\"location\":\"کارگە و چەو و لمی عطا\",\"material_id\":2,\"material_name\":\"چەو\",\"amount_iqd\":\"176885.73\",\"kg\":\"30325.00\",\"price\":\"0.00\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139450\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0.00\",\"remaining_iqd\":\"176000\",\"bin_id\":3,\"price_per_kg_iqd\":\"5833.00\",\"price_per_kg_usd\":\"0.00\",\"invoice_number\":\"1426\",\"date\":\"2025-08-02\"}',NULL,'{\"action_type\":\"purchase_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":176000}','130.193.207.162'),(41,2,'insert','purchases',14,'کڕینێکی نوێ زیادکرا (invoice: 1426, کۆمپانیا: سۆران, مادە: چەو, بڕ: 30325 کگم)','2025-08-03 15:11:04',0,NULL,'{\"company_id\":\"2\",\"company_name\":\"سۆران\",\"driver\":\"سۆران\",\"location\":\"کارگە و چەو و لمی عطا\",\"material_id\":\"2\",\"material_name\":\"چەو\",\"amount_iqd\":\"176885.73\",\"kg\":\"30325\",\"price\":\"0\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139450\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0\",\"remaining_iqd\":\"176000\",\"bin_id\":\"4\",\"price_per_kg_iqd\":\"5833\",\"price_per_kg_usd\":\"0\",\"invoice_number\":\"1426\",\"date\":\"2025-08-02\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":176000}','130.193.207.162'),(42,2,'insert','purchases',15,'کڕینێکی نوێ زیادکرا (invoice: 1107, کۆمپانیا: دەشتی, مادە: لمی ڕەش, بڕ: 34220 کگم)','2025-08-03 15:12:09',0,NULL,'{\"company_id\":\"1\",\"company_name\":\"دەشتی\",\"driver\":\"داستان علی\",\"location\":\"کەنجاڕە\",\"material_id\":\"1\",\"material_name\":\"لمی ڕەش\",\"amount_iqd\":\"350755.00\",\"kg\":\"34220\",\"price\":\"0\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139450\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0\",\"remaining_iqd\":\"350000\",\"bin_id\":\"1\",\"price_per_kg_iqd\":\"10250\",\"price_per_kg_usd\":\"0\",\"invoice_number\":\"1107\",\"date\":\"2025-08-03\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":350000}','130.193.207.162'),(43,2,'insert','sales',11,'فرۆشتنێکی نوێ زیادکرا (invoice: A-0020, A-0018, A-0017, A-0016, A-0015, کڕیار: ک.تۆفیق اسماعیل, فۆرمۆلا: SAQF 400 , بڕ: 44 م³)','2025-08-03 15:23:06',1,NULL,'{\"customer_id\":\"105\",\"customer_name\":\"ک.تۆفیق اسماعیل\",\"formula_id\":\"19\",\"formula_name\":\"SAQF 400 \",\"recipient\":\"ک.تۆفیق\",\"location\":\"ڕاپەڕین\",\"quantity\":\"44\",\"price_per_unit\":\"47\",\"total_price\":\"2068.0000\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0\",\"amount_paid_iq\":\"0\",\"remaining_amount\":\"2068.0000\",\"dolar_rate\":\"139450\",\"discount\":\"0\",\"order_date\":\"2025-08-02\",\"invoice_number\":\"A-0020, A-0018, A-0017, A-0016, A-0015\",\"notes\":\"\"}','{\"action_type\":\"sale_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0}','130.193.207.162'),(44,2,'insert','sales',12,'فرۆشتنێکی نوێ زیادکرا (invoice: A-0041, A-0039, A-0037, A-0036, A-0035, A-0034, A-0033, A-0032, A-0031, A-0030, A-0029, A-0028, کڕیار: هێدی وا‌حد علي, فۆرمۆلا: ARZY 30 MEGA, بڕ: 112 م³)','2025-08-03 15:26:27',1,NULL,'{\"customer_id\":\"119\",\"customer_name\":\"هێدی وا‌حد علي\",\"formula_id\":\"30\",\"formula_name\":\"ARZY 30 MEGA\",\"recipient\":\"وەستا ئازاد\",\"location\":\"پیرەمەگرون\",\"quantity\":\"112\",\"price_per_unit\":\"47\",\"total_price\":\"5264.0000\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0\",\"amount_paid_iq\":\"0\",\"remaining_amount\":\"5264.0000\",\"dolar_rate\":\"139450\",\"discount\":\"0\",\"order_date\":\"2025-08-02\",\"invoice_number\":\"A-0041, A-0039, A-0037, A-0036, A-0035, A-0034, A-0033, A-0032, A-0031, A-0030, A-0029, A-0028\",\"notes\":\"\"}','{\"action_type\":\"sale_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0}','130.193.207.162'),(45,2,'insert','sales',13,'فرۆشتنێکی نوێ زیادکرا (invoice: A-0005, A-0004, A-0003, A-0002, A-0001, کڕیار: و.قالە, فۆرمۆلا: ARZY 25 MEGA, بڕ: 50 م³)','2025-08-03 15:35:46',1,NULL,'{\"customer_id\":\"37\",\"customer_name\":\"و.قالە\",\"formula_id\":\"27\",\"formula_name\":\"ARZY 25 MEGA\",\"recipient\":\"وەستا قالە\",\"location\":\"ڕاپەڕین\",\"quantity\":\"50\",\"price_per_unit\":\"45\",\"total_price\":\"2250.0000\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0\",\"amount_paid_iq\":\"0\",\"remaining_amount\":\"2250.0000\",\"dolar_rate\":\"139450\",\"discount\":\"0\",\"order_date\":\"2025-08-02\",\"invoice_number\":\"A-0005, A-0004, A-0003, A-0002, A-0001\",\"notes\":\"\"}','{\"action_type\":\"sale_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0}','130.193.207.162'),(46,2,'insert','sales',14,'فرۆشتنێکی نوێ زیادکرا (invoice: A-0013, A-0006, کڕیار: و.قالە, فۆرمۆلا: ARZY 25 MEGA, بڕ: 18 م³)','2025-08-03 15:39:00',1,NULL,'{\"customer_id\":\"37\",\"customer_name\":\"و.قالە\",\"formula_id\":\"27\",\"formula_name\":\"ARZY 25 MEGA\",\"recipient\":\"وەستا قالە\",\"location\":\"ڕاپەڕین\",\"quantity\":\"18\",\"price_per_unit\":\"45\",\"total_price\":\"810.0000\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0\",\"amount_paid_iq\":\"0\",\"remaining_amount\":\"810.0000\",\"dolar_rate\":\"139450\",\"discount\":\"0\",\"order_date\":\"2025-08-02\",\"invoice_number\":\"A-0013, A-0006\",\"notes\":\"\"}','{\"action_type\":\"sale_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0}','130.193.207.162'),(47,2,'insert','sales',15,'فرۆشتنێکی نوێ زیادکرا (invoice: A-0040, کڕیار: و.قالە, فۆرمۆلا: Chawy lws saqf 350, بڕ: 6 م³)','2025-08-03 15:49:39',1,NULL,'{\"customer_id\":\"37\",\"customer_name\":\"و.قالە\",\"formula_id\":\"42\",\"formula_name\":\"Chawy lws saqf 350\",\"recipient\":\"\",\"location\":\"قلیاسان\",\"quantity\":\"6\",\"price_per_unit\":\"45\",\"total_price\":\"270.0000\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0\",\"amount_paid_iq\":\"0\",\"remaining_amount\":\"270.0000\",\"dolar_rate\":\"139450\",\"discount\":\"0\",\"order_date\":\"2025-08-02\",\"invoice_number\":\"A-0040\",\"notes\":\"\"}','{\"action_type\":\"sale_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0}','130.193.207.162'),(48,7,'update','concrete_receipts',56,'پسوڵەی کۆنکرێت نوێکرایەوە (شماره: A-0052, کڕیار: ك. خومان , فۆرمۆلا: PAYA 35 M, بڕ: 2.00 م³)','2025-08-03 16:26:32',1,'{\"receipt_number\":\"A-0052\",\"customer_id\":107,\"customer_name\":\"ك. خومان \",\"location\":\"كه له ونان\",\"meter_amount\":\"2.00\",\"formulas_id\":38,\"formula_name\":\"ARZY 35 M\",\"pump_car_id\":10,\"pump_car_name\":\"P1\",\"pump_driver_id\":26,\"pump_driver_name\":\"Unknown\",\"mixer_car_id\":14,\"mixer_car_name\":\"M11\",\"mixer_driver_id\":39,\"mixer_driver_name\":\"Unknown\",\"receiver_name\":\"\"}','{\"receipt_number\":\"A-0052\",\"customer_id\":\"107\",\"customer_name\":\"ك. خومان \",\"location\":\"كه له ونان\",\"meter_amount\":\"2.00\",\"formulas_id\":\"41\",\"formula_name\":\"PAYA 35 M\",\"pump_car_id\":\"10\",\"pump_car_name\":\"P1\",\"pump_driver_id\":\"26\",\"pump_driver_name\":\"Unknown\",\"mixer_car_id\":\"14\",\"mixer_car_name\":\"M11\",\"mixer_driver_id\":\"39\",\"mixer_driver_name\":\"Unknown\",\"receiver_name\":\"\"}','{\"action_type\":\"concrete_receipt_update\",\"receipt_type\":\"concrete_delivery\",\"amount_m3\":\"2.00\",\"delivery_components\":{\"pump_car\":\"P1\",\"mixer_car\":\"M11\"}}','130.193.209.129'),(49,1,'update','concrete_receipts',81,'پسوڵەی کۆنکرێت نوێکرایەوە (شماره: A-0077, کڕیار: ك. خومان , فۆرمۆلا: PAYA 35 M, بڕ: 2.00 م³)','2025-08-03 17:46:12',1,'{\"receipt_number\":\"A-0077\",\"customer_id\":107,\"customer_name\":\"ك. خومان \",\"location\":\"كه له ونان\",\"meter_amount\":\"2.00\",\"formulas_id\":42,\"formula_name\":\"Chawy lws saqf 350\",\"pump_car_id\":11,\"pump_car_name\":\"P2\",\"pump_driver_id\":27,\"pump_driver_name\":\"Unknown\",\"mixer_car_id\":20,\"mixer_car_name\":\"M17\",\"mixer_driver_id\":33,\"mixer_driver_name\":\"Unknown\",\"receiver_name\":\"\"}','{\"receipt_number\":\"A-0077\",\"customer_id\":\"107\",\"customer_name\":\"ك. خومان \",\"location\":\"كه له ونان\",\"meter_amount\":\"2.00\",\"formulas_id\":\"41\",\"formula_name\":\"PAYA 35 M\",\"pump_car_id\":\"11\",\"pump_car_name\":\"P2\",\"pump_driver_id\":\"27\",\"pump_driver_name\":\"Unknown\",\"mixer_car_id\":\"20\",\"mixer_car_name\":\"M17\",\"mixer_driver_id\":\"33\",\"mixer_driver_name\":\"Unknown\",\"receiver_name\":\"\"}','{\"action_type\":\"concrete_receipt_update\",\"receipt_type\":\"concrete_delivery\",\"amount_m3\":\"2.00\",\"delivery_components\":{\"pump_car\":\"P2\",\"mixer_car\":\"M17\"}}','130.193.218.178'),(50,2,'insert','sales',16,'فرۆشتنێکی نوێ زیادکرا (invoice: A-0042, A-0044, A-0045, کڕیار: کاک ئەرسەلان نوسینگە, فۆرمۆلا: ARZY 21 MEGA, بڕ: 30 م³)','2025-08-04 09:01:11',0,NULL,'{\"customer_id\":\"145\",\"customer_name\":\"کاک ئەرسەلان نوسینگە\",\"formula_id\":\"26\",\"formula_name\":\"ARZY 21 MEGA\",\"recipient\":\"و . هیوا \",\"location\":\"بەرامبەر حەسەن تەپە\",\"quantity\":\"30\",\"price_per_unit\":\"43.34\",\"total_price\":\"1300.2000\",\"payment_type\":\"نەقد\",\"amount_paid_usd\":\"1300\",\"amount_paid_iq\":\"0\",\"remaining_amount\":\"0.0000\",\"dolar_rate\":\"139400\",\"discount\":\"0.2\",\"order_date\":\"2025-08-03\",\"invoice_number\":\"A-0042, A-0044, A-0045\",\"notes\":\"1300 دۆلار واسڵ\"}','{\"action_type\":\"sale_creation\",\"payment_status\":\"paid\",\"currency_used\":\"USD\",\"total_paid\":1300}','130.193.207.162'),(51,2,'insert','sales',17,'فرۆشتنێکی نوێ زیادکرا (invoice: A-0043, A-0046, کڕیار: ک.ئاسۆ زەرزی, فۆرمۆلا: ARZY 25 MEGA, بڕ: 13 م³)','2025-08-04 09:01:47',0,NULL,'{\"customer_id\":\"146\",\"customer_name\":\"ک.ئاسۆ زەرزی\",\"formula_id\":\"27\",\"formula_name\":\"ARZY 25 MEGA\",\"recipient\":\"ک . ئاسۆ\",\"location\":\"پیرەمەگرون\",\"quantity\":\"13\",\"price_per_unit\":\"45\",\"total_price\":\"585.0000\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0\",\"amount_paid_iq\":\"0\",\"remaining_amount\":\"585.0000\",\"dolar_rate\":\"139400\",\"discount\":\"0\",\"order_date\":\"2025-08-03\",\"invoice_number\":\"A-0043, A-0046\",\"notes\":\"\"}','{\"action_type\":\"sale_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0}','130.193.207.162'),(52,2,'insert','sales',18,'فرۆشتنێکی نوێ زیادکرا (invoice: A-0047, A-0048, A-0049, A-0050, A-0051, A-0053, A-0054, A-0055, A-0056, A-0057, کڕیار: هێمن حسن شێخۆ, فۆرمۆلا: ARZY 35 M, بڕ: 77 م³)','2025-08-04 09:02:13',0,NULL,'{\"customer_id\":\"147\",\"customer_name\":\"هێمن حسن شێخۆ\",\"formula_id\":\"38\",\"formula_name\":\"ARZY 35 M\",\"recipient\":\"و, شوانه, و.شوانا\",\"location\":\"دوكان\",\"quantity\":\"77\",\"price_per_unit\":\"53\",\"total_price\":\"4081.0000\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0\",\"amount_paid_iq\":\"0\",\"remaining_amount\":\"4081.0000\",\"dolar_rate\":\"139400\",\"discount\":\"0\",\"order_date\":\"2025-08-03\",\"invoice_number\":\"A-0047, A-0048, A-0049, A-0050, A-0051, A-0053, A-0054, A-0055, A-0056, A-0057\",\"notes\":\"\"}','{\"action_type\":\"sale_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0}','130.193.207.162'),(53,2,'insert','sales',19,'فرۆشتنێکی نوێ زیادکرا (invoice: A-0052, A-0077, کڕیار: دانا کۆنکرێت, فۆرمۆلا: PAYA 35 M, بڕ: 4 م³)','2025-08-04 09:02:48',0,NULL,'{\"customer_id\":\"107\",\"customer_name\":\"دانا کۆنکرێت\",\"formula_id\":\"41\",\"formula_name\":\"PAYA 35 M\",\"recipient\":\"\",\"location\":\"كه له ونان\",\"quantity\":\"4\",\"price_per_unit\":\"45\",\"total_price\":\"180.0000\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0\",\"amount_paid_iq\":\"0\",\"remaining_amount\":\"180.0000\",\"dolar_rate\":\"139400\",\"discount\":\"0\",\"order_date\":\"2025-08-03\",\"invoice_number\":\"A-0052, A-0077\",\"notes\":\"\"}','{\"action_type\":\"sale_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0}','130.193.207.162'),(54,2,'insert','sales',20,'فرۆشتنێکی نوێ زیادکرا (invoice: A-0062, کڕیار: كاك ازار , فۆرمۆلا: ARZY 25 MEGA, بڕ: 5 م³)','2025-08-04 09:03:28',0,NULL,'{\"customer_id\":\"99\",\"customer_name\":\"كاك ازار \",\"formula_id\":\"27\",\"formula_name\":\"ARZY 25 MEGA\",\"recipient\":\"کاک کاروان \",\"location\":\"کێلە سپی\",\"quantity\":\"5\",\"price_per_unit\":\"45\",\"total_price\":\"225.0000\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0\",\"amount_paid_iq\":\"0\",\"remaining_amount\":\"225.0000\",\"dolar_rate\":\"139400\",\"discount\":\"0\",\"order_date\":\"2025-08-03\",\"invoice_number\":\"A-0062\",\"notes\":\"\"}','{\"action_type\":\"sale_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0}','130.193.207.162'),(55,2,'insert','sales',21,'فرۆشتنێکی نوێ زیادکرا (invoice: A-0063, A-0066, A-0067, A-0068, کڕیار: کاک شوان مقاول, فۆرمۆلا: Chawy lws saqf 350, بڕ: 35 م³)','2025-08-04 09:05:02',0,NULL,'{\"customer_id\":\"181\",\"customer_name\":\"کاک شوان مقاول\",\"formula_id\":\"42\",\"formula_name\":\"Chawy lws saqf 350\",\"recipient\":\"و. كرمانج\",\"location\":\"ڕاپەڕین\",\"quantity\":\"35\",\"price_per_unit\":\"45\",\"total_price\":\"1575.0000\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0\",\"amount_paid_iq\":\"0\",\"remaining_amount\":\"1575.0000\",\"dolar_rate\":\"139400\",\"discount\":\"0\",\"order_date\":\"2025-08-03\",\"invoice_number\":\"A-0063, A-0066, A-0067, A-0068\",\"notes\":\"\"}','{\"action_type\":\"sale_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0}','130.193.207.162'),(56,2,'insert','sales',22,'فرۆشتنێکی نوێ زیادکرا (invoice: A-0064, A-0065, A-0069, A-0070, A-0071, A-0073, کڕیار: و.ئومێد, فۆرمۆلا: Chawy lws saqf 350, بڕ: 47 م³)','2025-08-04 09:06:38',0,NULL,'{\"customer_id\":\"57\",\"customer_name\":\"و.ئومێد\",\"formula_id\":\"42\",\"formula_name\":\"Chawy lws saqf 350\",\"recipient\":\"\",\"location\":\"دارتو مولانا\",\"quantity\":\"47\",\"price_per_unit\":\"45\",\"total_price\":\"2115.0000\",\"payment_type\":\"نەقد\",\"amount_paid_usd\":\"2100\",\"amount_paid_iq\":\"0\",\"remaining_amount\":\"0.0000\",\"dolar_rate\":\"139400\",\"discount\":\"15\",\"order_date\":\"2025-08-03\",\"invoice_number\":\"A-0064, A-0065, A-0069, A-0070, A-0071, A-0073\",\"notes\":\"2,100$ لای توانای برامە\"}','{\"action_type\":\"sale_creation\",\"payment_status\":\"paid\",\"currency_used\":\"USD\",\"total_paid\":2100}','130.193.207.162'),(57,2,'insert','sales',23,'فرۆشتنێکی نوێ زیادکرا (invoice: A-0072, کڕیار: کاک دانا گولیجەیی, فۆرمۆلا: ARZY 25 MEGA, بڕ: 3 م³)','2025-08-04 09:07:21',0,NULL,'{\"customer_id\":\"186\",\"customer_name\":\"کاک دانا گولیجەیی\",\"formula_id\":\"27\",\"formula_name\":\"ARZY 25 MEGA\",\"recipient\":\"\",\"location\":\"پیرەمەگرون\",\"quantity\":\"3\",\"price_per_unit\":\"45\",\"total_price\":\"135.0000\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0\",\"amount_paid_iq\":\"0\",\"remaining_amount\":\"135.0000\",\"dolar_rate\":\"139400\",\"discount\":\"0\",\"order_date\":\"2025-08-03\",\"invoice_number\":\"A-0072\",\"notes\":\"\"}','{\"action_type\":\"sale_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0}','130.193.207.162'),(58,2,'insert','sales',24,'فرۆشتنێکی نوێ زیادکرا (invoice: A-0074, A-0075, A-0076, کڕیار: و.سەرباز , فۆرمۆلا: Chawy lws saqf 350, بڕ: 28 م³)','2025-08-04 09:10:18',0,NULL,'{\"customer_id\":\"60\",\"customer_name\":\"و.سەرباز \",\"formula_id\":\"42\",\"formula_name\":\"Chawy lws saqf 350\",\"recipient\":\"\",\"location\":\"پیرەمەگرون\",\"quantity\":\"28\",\"price_per_unit\":\"45\",\"total_price\":\"1260.0000\",\"payment_type\":\"نەقد\",\"amount_paid_usd\":\"1200\",\"amount_paid_iq\":\"80000\",\"remaining_amount\":\"-0.0000\",\"dolar_rate\":\"139400\",\"discount\":\"2.6112\",\"order_date\":\"2025-08-03\",\"invoice_number\":\"A-0074, A-0075, A-0076\",\"notes\":\"1,200$+80,000 لای مام شاڵاو پەمپە\"}','{\"action_type\":\"sale_creation\",\"payment_status\":\"paid\",\"currency_used\":\"USD\",\"total_paid\":81200}','130.193.207.162'),(59,2,'insert','customer_debt_payments',1,'پارەدان بۆ قەرزی کڕیار زیادکرا (کڕیار: کاک هێمن موغاخ, تەلەفۆن: 07719702022)','2025-08-04 09:15:43',0,NULL,'{\"customer_id\":\"160\",\"customer_name\":\"کاک هێمن موغاخ\",\"customer_phone\":\"07719702022\",\"date\":\"2025-08-04\",\"dolar_rate\":139650,\"paid_usd\":0,\"paid_iqd\":600000,\"discount\":0.35,\"note\":\"\",\"from_opening_debt_usd\":429.99554242749736,\"from_sales_usd\":0}','{\"action_type\":\"customer_debt_payment\",\"payment_method\":\"IQD\",\"total_paid_usd_equivalent\":429.99554242749736,\"debt_reduction_type\":\"opening_debt\"}','130.193.207.162'),(60,2,'insert','customer_debt_payments',2,'پارەدان بۆ قەرزی کڕیار زیادکرا (کڕیار: كاك شاڵاو پەمپ, تەلەفۆن: 07748168525)','2025-08-04 09:16:21',0,NULL,'{\"customer_id\":\"116\",\"customer_name\":\"كاك شاڵاو پەمپ\",\"customer_phone\":\"07748168525\",\"date\":\"2025-08-04\",\"dolar_rate\":139400,\"paid_usd\":0,\"paid_iqd\":75000,\"discount\":0,\"note\":\"\",\"from_opening_debt_usd\":53.80200860832138,\"from_sales_usd\":0}','{\"action_type\":\"customer_debt_payment\",\"payment_method\":\"IQD\",\"total_paid_usd_equivalent\":53.80200860832138,\"debt_reduction_type\":\"opening_debt\"}','130.193.207.162'),(61,2,'insert','customer_debt_payments',3,'پارەدان بۆ قەرزی کڕیار زیادکرا (کڕیار: حاجی سیروان ڕۆماکس, تەلەفۆن: 07725213456)','2025-08-04 09:19:32',0,NULL,'{\"customer_id\":\"170\",\"customer_name\":\"حاجی سیروان ڕۆماکس\",\"customer_phone\":\"07725213456\",\"date\":\"2025-08-04\",\"dolar_rate\":139440,\"paid_usd\":1900,\"paid_iqd\":75000,\"discount\":0.21,\"note\":\"\",\"from_opening_debt_usd\":1953.9965748709124,\"from_sales_usd\":0}','{\"action_type\":\"customer_debt_payment\",\"payment_method\":\"USD\",\"total_paid_usd_equivalent\":1953.9965748709124,\"debt_reduction_type\":\"opening_debt\"}','130.193.207.162'),(62,2,'insert','customer_debt_payments',4,'پارەدان بۆ قەرزی کڕیار زیادکرا (کڕیار: پێشڕەو مقاول, تەلەفۆن: 07725047522)','2025-08-04 09:19:57',0,NULL,'{\"customer_id\":\"163\",\"customer_name\":\"پێشڕەو مقاول\",\"customer_phone\":\"07725047522\",\"date\":\"2025-08-04\",\"dolar_rate\":139400,\"paid_usd\":90,\"paid_iqd\":0,\"discount\":0,\"note\":\"\",\"from_opening_debt_usd\":90,\"from_sales_usd\":0}','{\"action_type\":\"customer_debt_payment\",\"payment_method\":\"USD\",\"total_paid_usd_equivalent\":90,\"debt_reduction_type\":\"opening_debt\"}','130.193.207.162'),(63,2,'insert','purchases',16,'کڕینێکی نوێ زیادکرا (invoice: 1108, کۆمپانیا: دەشتی, مادە: لمی ڕەش, بڕ: 33950 کگم)','2025-08-04 10:50:29',0,NULL,'{\"company_id\":\"1\",\"company_name\":\"دەشتی\",\"driver\":\"داستان علی\",\"location\":\"کەنجاڕە\",\"material_id\":\"1\",\"material_name\":\"لمی ڕەش\",\"amount_iqd\":\"347987.50\",\"kg\":\"33950\",\"price\":\"0\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0\",\"remaining_iqd\":\"347000\",\"bin_id\":\"1\",\"price_per_kg_iqd\":\"10250\",\"price_per_kg_usd\":\"0\",\"invoice_number\":\"1108\",\"date\":\"2025-08-03\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":347000}','130.193.207.162'),(64,2,'insert','purchases',17,'کڕینێکی نوێ زیادکرا (invoice: 1109, کۆمپانیا: دەشتی, مادە: لمی ڕەش, بڕ: 33320 کگم)','2025-08-04 10:53:58',0,NULL,'{\"company_id\":\"1\",\"company_name\":\"دەشتی\",\"driver\":\"کاک دەشتی\",\"location\":\"کەنجاڕە\",\"material_id\":\"1\",\"material_name\":\"لمی ڕەش\",\"amount_iqd\":\"341530.00\",\"kg\":\"33320\",\"price\":\"0\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0\",\"remaining_iqd\":\"341000\",\"bin_id\":\"1\",\"price_per_kg_iqd\":\"10250\",\"price_per_kg_usd\":\"0\",\"invoice_number\":\"1109\",\"date\":\"2025-08-03\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":341000}','130.193.207.162'),(65,2,'insert','purchases',18,'کڕینێکی نوێ زیادکرا (invoice: 1110, کۆمپانیا: دەشتی, مادە: لمی ڕەش, بڕ: 34220 کگم)','2025-08-04 10:56:04',0,NULL,'{\"company_id\":\"1\",\"company_name\":\"دەشتی\",\"driver\":\"داستان علی\",\"location\":\"کەنجاڕە\",\"material_id\":\"1\",\"material_name\":\"لمی ڕەش\",\"amount_iqd\":\"350755.00\",\"kg\":\"34220\",\"price\":\"0\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0\",\"remaining_iqd\":\"350000\",\"bin_id\":\"1\",\"price_per_kg_iqd\":\"10250\",\"price_per_kg_usd\":\"0\",\"invoice_number\":\"1110\",\"date\":\"2025-08-04\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":350000}','130.193.207.162'),(66,2,'insert','purchases',19,'کڕینێکی نوێ زیادکرا (invoice: 1111, کۆمپانیا: دەشتی, مادە: لمی ڕەش, بڕ: 30480 کگم)','2025-08-04 10:58:08',0,NULL,'{\"company_id\":\"1\",\"company_name\":\"دەشتی\",\"driver\":\"محمد سەردار\",\"location\":\"کەنجاڕە\",\"material_id\":\"1\",\"material_name\":\"لمی ڕەش\",\"amount_iqd\":\"312420.00\",\"kg\":\"30480\",\"price\":\"0\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0\",\"remaining_iqd\":\"312000\",\"bin_id\":\"1\",\"price_per_kg_iqd\":\"10250\",\"price_per_kg_usd\":\"0\",\"invoice_number\":\"1111\",\"date\":\"2025-08-04\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":312000}','130.193.207.162'),(67,2,'insert','purchases',20,'کڕینێکی نوێ زیادکرا (invoice: 1112, کۆمپانیا: دەشتی, مادە: لمی ڕەش, بڕ: 32200 کگم)','2025-08-04 10:59:35',0,NULL,'{\"company_id\":\"1\",\"company_name\":\"دەشتی\",\"driver\":\"محمد علی\",\"location\":\"کەنجاڕە\",\"material_id\":\"1\",\"material_name\":\"لمی ڕەش\",\"amount_iqd\":\"330050.00\",\"kg\":\"32200\",\"price\":\"0\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0\",\"remaining_iqd\":\"330000\",\"bin_id\":\"1\",\"price_per_kg_iqd\":\"10250\",\"price_per_kg_usd\":\"0\",\"invoice_number\":\"1112\",\"date\":\"2025-08-04\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":330000}','130.193.207.162'),(68,2,'insert','purchases',21,'کڕینێکی نوێ زیادکرا (invoice: 1437, کۆمپانیا: سۆران, مادە: چەو, بڕ: 30225 کگم)','2025-08-04 11:03:09',0,NULL,'{\"company_id\":\"2\",\"company_name\":\"سۆران\",\"driver\":\"سۆران\",\"location\":\"کارگە و چەو و لمی عطا\",\"material_id\":\"2\",\"material_name\":\"چەو\",\"amount_iqd\":\"176302.43\",\"kg\":\"30225\",\"price\":\"0\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0\",\"remaining_iqd\":\"176000\",\"bin_id\":\"4\",\"price_per_kg_iqd\":\"5833\",\"price_per_kg_usd\":\"0\",\"invoice_number\":\"1437\",\"date\":\"2025-08-04\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":176000}','130.193.207.162'),(69,7,'update','concrete_receipts',96,'پسوڵەی کۆنکرێت نوێکرایەوە (شماره: A-0092, کڕیار: کاک شێرکۆ معمل چیمەنتۆی ماس, فۆرمۆلا: ARZY 35 M, بڕ: 7.5 م³)','2025-08-04 13:12:14',0,'{\"receipt_number\":\"A-0092\",\"customer_id\":193,\"customer_name\":\"کاک شێرکۆ معمل چیمەنتۆی ماس\",\"location\":\"معمل چیمەنتۆی ماس\",\"meter_amount\":\"8.00\",\"formulas_id\":38,\"formula_name\":\"ARZY 35 M\",\"pump_car_id\":10,\"pump_car_name\":\"P1\",\"pump_driver_id\":26,\"pump_driver_name\":\"Unknown\",\"mixer_car_id\":16,\"mixer_car_name\":\"M13\",\"mixer_driver_id\":37,\"mixer_driver_name\":\"Unknown\",\"receiver_name\":\"\"}','{\"receipt_number\":\"A-0092\",\"customer_id\":\"193\",\"customer_name\":\"کاک شێرکۆ معمل چیمەنتۆی ماس\",\"location\":\"معمل چیمەنتۆی ماس\",\"meter_amount\":\"7.5\",\"formulas_id\":\"38\",\"formula_name\":\"ARZY 35 M\",\"pump_car_id\":\"10\",\"pump_car_name\":\"P1\",\"pump_driver_id\":\"26\",\"pump_driver_name\":\"Unknown\",\"mixer_car_id\":\"16\",\"mixer_car_name\":\"M13\",\"mixer_driver_id\":\"37\",\"mixer_driver_name\":\"Unknown\",\"receiver_name\":\"\"}','{\"action_type\":\"concrete_receipt_update\",\"receipt_type\":\"concrete_delivery\",\"amount_m3\":\"7.5\",\"delivery_components\":{\"pump_car\":\"P1\",\"mixer_car\":\"M13\"}}','130.193.209.129'),(70,7,'update','concrete_receipts',99,'پسوڵەی کۆنکرێت نوێکرایەوە (شماره: A-0095, کڕیار: کاک شێرکۆ معمل چیمەنتۆی ماس, فۆرمۆلا: ARZY 35 M, بڕ: 10 م³)','2025-08-04 13:32:10',0,'{\"receipt_number\":\"A-0095\",\"customer_id\":193,\"customer_name\":\"کاک شێرکۆ معمل چیمەنتۆی ماس\",\"location\":\"معمل چیمەنتۆی ماس\",\"meter_amount\":\"7.50\",\"formulas_id\":38,\"formula_name\":\"ARZY 35 M\",\"pump_car_id\":10,\"pump_car_name\":\"P1\",\"pump_driver_id\":26,\"pump_driver_name\":\"Unknown\",\"mixer_car_id\":20,\"mixer_car_name\":\"M17\",\"mixer_driver_id\":34,\"mixer_driver_name\":\"Unknown\",\"receiver_name\":\"\"}','{\"receipt_number\":\"A-0095\",\"customer_id\":\"193\",\"customer_name\":\"کاک شێرکۆ معمل چیمەنتۆی ماس\",\"location\":\"معمل چیمەنتۆی ماس\",\"meter_amount\":\"10\",\"formulas_id\":\"38\",\"formula_name\":\"ARZY 35 M\",\"pump_car_id\":\"10\",\"pump_car_name\":\"P1\",\"pump_driver_id\":\"26\",\"pump_driver_name\":\"Unknown\",\"mixer_car_id\":\"20\",\"mixer_car_name\":\"M17\",\"mixer_driver_id\":\"34\",\"mixer_driver_name\":\"Unknown\",\"receiver_name\":\"\"}','{\"action_type\":\"concrete_receipt_update\",\"receipt_type\":\"concrete_delivery\",\"amount_m3\":\"10\",\"delivery_components\":{\"pump_car\":\"P1\",\"mixer_car\":\"M17\"}}','130.193.209.129'),(71,7,'update','concrete_receipts',98,'پسوڵەی کۆنکرێت نوێکرایەوە (شماره: A-0094, کڕیار: کاک شێرکۆ معمل چیمەنتۆی ماس, فۆرمۆلا: ARZY 35 M, بڕ: 8 م³)','2025-08-04 13:42:07',0,'{\"receipt_number\":\"A-0094\",\"customer_id\":193,\"customer_name\":\"کاک شێرکۆ معمل چیمەنتۆی ماس\",\"location\":\"معمل چیمەنتۆی ماس\",\"meter_amount\":\"10.00\",\"formulas_id\":38,\"formula_name\":\"ARZY 35 M\",\"pump_car_id\":10,\"pump_car_name\":\"P1\",\"pump_driver_id\":26,\"pump_driver_name\":\"Unknown\",\"mixer_car_id\":13,\"mixer_car_name\":\"M10\",\"mixer_driver_id\":38,\"mixer_driver_name\":\"Unknown\",\"receiver_name\":\"\"}','{\"receipt_number\":\"A-0094\",\"customer_id\":\"193\",\"customer_name\":\"کاک شێرکۆ معمل چیمەنتۆی ماس\",\"location\":\"معمل چیمەنتۆی ماس\",\"meter_amount\":\"8\",\"formulas_id\":\"38\",\"formula_name\":\"ARZY 35 M\",\"pump_car_id\":\"10\",\"pump_car_name\":\"P1\",\"pump_driver_id\":\"26\",\"pump_driver_name\":\"Unknown\",\"mixer_car_id\":\"13\",\"mixer_car_name\":\"M10\",\"mixer_driver_id\":\"38\",\"mixer_driver_name\":\"Unknown\",\"receiver_name\":\"\"}','{\"action_type\":\"concrete_receipt_update\",\"receipt_type\":\"concrete_delivery\",\"amount_m3\":\"8\",\"delivery_components\":{\"pump_car\":\"P1\",\"mixer_car\":\"M10\"}}','130.193.209.129'),(72,7,'update','concrete_receipts',101,'پسوڵەی کۆنکرێت نوێکرایەوە (شماره: A-0097, کڕیار: کاک شێرکۆ معمل چیمەنتۆی ماس, فۆرمۆلا: ARZY 35 M, بڕ: 2 م³)','2025-08-04 13:47:13',0,'{\"receipt_number\":\"A-0097\",\"customer_id\":193,\"customer_name\":\"کاک شێرکۆ معمل چیمەنتۆی ماس\",\"location\":\"معمل چیمەنتۆی ماس\",\"meter_amount\":\"8.00\",\"formulas_id\":38,\"formula_name\":\"ARZY 35 M\",\"pump_car_id\":10,\"pump_car_name\":\"P1\",\"pump_driver_id\":26,\"pump_driver_name\":\"Unknown\",\"mixer_car_id\":23,\"mixer_car_name\":\"M19\",\"mixer_driver_id\":29,\"mixer_driver_name\":\"Unknown\",\"receiver_name\":\"\"}','{\"receipt_number\":\"A-0097\",\"customer_id\":\"193\",\"customer_name\":\"کاک شێرکۆ معمل چیمەنتۆی ماس\",\"location\":\"معمل چیمەنتۆی ماس\",\"meter_amount\":\"2\",\"formulas_id\":\"38\",\"formula_name\":\"ARZY 35 M\",\"pump_car_id\":\"10\",\"pump_car_name\":\"P1\",\"pump_driver_id\":\"26\",\"pump_driver_name\":\"Unknown\",\"mixer_car_id\":\"23\",\"mixer_car_name\":\"M19\",\"mixer_driver_id\":\"29\",\"mixer_driver_name\":\"Unknown\",\"receiver_name\":\"\"}','{\"action_type\":\"concrete_receipt_update\",\"receipt_type\":\"concrete_delivery\",\"amount_m3\":\"2\",\"delivery_components\":{\"pump_car\":\"P1\",\"mixer_car\":\"M19\"}}','130.193.209.129'),(73,7,'update','concrete_receipts',101,'پسوڵەی کۆنکرێت نوێکرایەوە (شماره: A-0097, کڕیار: کاک شێرکۆ معمل چیمەنتۆی ماس, فۆرمۆلا: ARZY 35 M, بڕ: 10 م³)','2025-08-04 13:48:35',0,'{\"receipt_number\":\"A-0097\",\"customer_id\":193,\"customer_name\":\"کاک شێرکۆ معمل چیمەنتۆی ماس\",\"location\":\"معمل چیمەنتۆی ماس\",\"meter_amount\":\"2.00\",\"formulas_id\":38,\"formula_name\":\"ARZY 35 M\",\"pump_car_id\":10,\"pump_car_name\":\"P1\",\"pump_driver_id\":26,\"pump_driver_name\":\"Unknown\",\"mixer_car_id\":23,\"mixer_car_name\":\"M19\",\"mixer_driver_id\":29,\"mixer_driver_name\":\"Unknown\",\"receiver_name\":null}','{\"receipt_number\":\"A-0097\",\"customer_id\":\"193\",\"customer_name\":\"کاک شێرکۆ معمل چیمەنتۆی ماس\",\"location\":\"معمل چیمەنتۆی ماس\",\"meter_amount\":\"10\",\"formulas_id\":\"38\",\"formula_name\":\"ARZY 35 M\",\"pump_car_id\":\"10\",\"pump_car_name\":\"P1\",\"pump_driver_id\":\"26\",\"pump_driver_name\":\"Unknown\",\"mixer_car_id\":\"23\",\"mixer_car_name\":\"M19\",\"mixer_driver_id\":\"29\",\"mixer_driver_name\":\"Unknown\",\"receiver_name\":\"\"}','{\"action_type\":\"concrete_receipt_update\",\"receipt_type\":\"concrete_delivery\",\"amount_m3\":\"10\",\"delivery_components\":{\"pump_car\":\"P1\",\"mixer_car\":\"M19\"}}','130.193.209.129'),(74,2,'insert','purchases',22,'کڕینێکی نوێ زیادکرا (invoice: 5118, کۆمپانیا: تڕێلەکەی خۆمان, مادە: چەو, بڕ: 32580 کگم)','2025-08-04 14:22:53',0,NULL,'{\"company_id\":\"6\",\"company_name\":\"تڕێلەکەی خۆمان\",\"driver\":\"کاک عیماد شۆفێر\",\"location\":\"غەسالەی نەورۆز\",\"material_id\":\"2\",\"material_name\":\"چەو\",\"amount_iqd\":\"190039.14\",\"kg\":\"32580\",\"price\":\"0\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0\",\"remaining_iqd\":\"190000\",\"bin_id\":\"4\",\"price_per_kg_iqd\":\"5833\",\"price_per_kg_usd\":\"0\",\"invoice_number\":\"5118\",\"date\":\"2025-08-03\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":190000}','130.193.209.66'),(75,2,'insert','purchases',23,'کڕینێکی نوێ زیادکرا (invoice: 5144, کۆمپانیا: تڕێلەکەی خۆمان, مادە: چەو, بڕ: 32500 کگم)','2025-08-04 14:25:02',0,NULL,'{\"company_id\":\"6\",\"company_name\":\"تڕێلەکەی خۆمان\",\"driver\":\"کاک عیماد شۆفێر\",\"location\":\"غەسالەی نەورۆز\",\"material_id\":\"2\",\"material_name\":\"چەو\",\"amount_iqd\":\"189572.50\",\"kg\":\"32500\",\"price\":\"0\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0\",\"remaining_iqd\":\"189000\",\"bin_id\":\"4\",\"price_per_kg_iqd\":\"5833\",\"price_per_kg_usd\":\"0\",\"invoice_number\":\"5144\",\"date\":\"2025-08-03\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":189000}','130.193.209.66'),(76,2,'delete','purchases',15,'کڕینەکە سڕایەوە (invoice: 1107, کۆمپانیا: دەشتی, مادە: لمی ڕەش)','2025-08-04 15:06:30',0,'{\"company_id\":1,\"company_name\":\"دەشتی\",\"driver\":\"داستان علی\",\"location\":\"کەنجاڕە\",\"material_id\":1,\"material_name\":\"لمی ڕەش\",\"amount_iqd\":\"350755.00\",\"kg\":\"34220.00\",\"price\":\"0.00\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139450\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0.00\",\"remaining_iqd\":\"350000\",\"bin_id\":1,\"price_per_kg_iqd\":\"10250.00\",\"price_per_kg_usd\":\"0.00\",\"invoice_number\":\"1107\",\"date\":\"2025-08-03\"}',NULL,'{\"action_type\":\"purchase_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":350000}','130.193.209.66'),(77,2,'delete','purchases',16,'کڕینەکە سڕایەوە (invoice: 1108, کۆمپانیا: دەشتی, مادە: لمی ڕەش)','2025-08-04 15:06:34',0,'{\"company_id\":1,\"company_name\":\"دەشتی\",\"driver\":\"داستان علی\",\"location\":\"کەنجاڕە\",\"material_id\":1,\"material_name\":\"لمی ڕەش\",\"amount_iqd\":\"347987.50\",\"kg\":\"33950.00\",\"price\":\"0.00\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0.00\",\"remaining_iqd\":\"347000\",\"bin_id\":1,\"price_per_kg_iqd\":\"10250.00\",\"price_per_kg_usd\":\"0.00\",\"invoice_number\":\"1108\",\"date\":\"2025-08-03\"}',NULL,'{\"action_type\":\"purchase_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":347000}','130.193.209.66'),(78,2,'delete','purchases',13,'کڕینەکە سڕایەوە (invoice: 1434, کۆمپانیا: سۆران, مادە: چەو)','2025-08-04 15:06:39',0,'{\"company_id\":2,\"company_name\":\"سۆران\",\"driver\":\"سۆران\",\"location\":\"کارگە و چەو و لمی عطا\",\"material_id\":2,\"material_name\":\"چەو\",\"amount_iqd\":\"177527.35\",\"kg\":\"30435.00\",\"price\":\"0.00\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139450\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0.00\",\"remaining_iqd\":\"177000\",\"bin_id\":4,\"price_per_kg_iqd\":\"5833.00\",\"price_per_kg_usd\":\"0.00\",\"invoice_number\":\"1434\",\"date\":\"2025-08-03\"}',NULL,'{\"action_type\":\"purchase_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":177000}','130.193.209.66'),(79,2,'delete','purchases',17,'کڕینەکە سڕایەوە (invoice: 1109, کۆمپانیا: دەشتی, مادە: لمی ڕەش)','2025-08-04 15:06:43',0,'{\"company_id\":1,\"company_name\":\"دەشتی\",\"driver\":\"کاک دەشتی\",\"location\":\"کەنجاڕە\",\"material_id\":1,\"material_name\":\"لمی ڕەش\",\"amount_iqd\":\"341530.00\",\"kg\":\"33320.00\",\"price\":\"0.00\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0.00\",\"remaining_iqd\":\"341000\",\"bin_id\":1,\"price_per_kg_iqd\":\"10250.00\",\"price_per_kg_usd\":\"0.00\",\"invoice_number\":\"1109\",\"date\":\"2025-08-03\"}',NULL,'{\"action_type\":\"purchase_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":341000}','130.193.209.66'),(80,2,'delete','purchases',22,'کڕینەکە سڕایەوە (invoice: 5118, کۆمپانیا: تڕێلەکەی خۆمان, مادە: چەو)','2025-08-04 15:06:46',0,'{\"company_id\":6,\"company_name\":\"تڕێلەکەی خۆمان\",\"driver\":\"کاک عیماد شۆفێر\",\"location\":\"غەسالەی نەورۆز\",\"material_id\":2,\"material_name\":\"چەو\",\"amount_iqd\":\"190039.14\",\"kg\":\"32580.00\",\"price\":\"0.00\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0.00\",\"remaining_iqd\":\"190000\",\"bin_id\":4,\"price_per_kg_iqd\":\"5833.00\",\"price_per_kg_usd\":\"0.00\",\"invoice_number\":\"5118\",\"date\":\"2025-08-03\"}',NULL,'{\"action_type\":\"purchase_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":190000}','130.193.209.66'),(81,2,'delete','purchases',21,'کڕینەکە سڕایەوە (invoice: 1437, کۆمپانیا: سۆران, مادە: چەو)','2025-08-04 15:06:51',0,'{\"company_id\":2,\"company_name\":\"سۆران\",\"driver\":\"سۆران\",\"location\":\"کارگە و چەو و لمی عطا\",\"material_id\":2,\"material_name\":\"چەو\",\"amount_iqd\":\"176302.43\",\"kg\":\"30225.00\",\"price\":\"0.00\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0.00\",\"remaining_iqd\":\"176000\",\"bin_id\":4,\"price_per_kg_iqd\":\"5833.00\",\"price_per_kg_usd\":\"0.00\",\"invoice_number\":\"1437\",\"date\":\"2025-08-04\"}',NULL,'{\"action_type\":\"purchase_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":176000}','130.193.209.66'),(82,2,'delete','purchases',20,'کڕینەکە سڕایەوە (invoice: 1112, کۆمپانیا: دەشتی, مادە: لمی ڕەش)','2025-08-04 15:06:58',0,'{\"company_id\":1,\"company_name\":\"دەشتی\",\"driver\":\"محمد علی\",\"location\":\"کەنجاڕە\",\"material_id\":1,\"material_name\":\"لمی ڕەش\",\"amount_iqd\":\"330050.00\",\"kg\":\"32200.00\",\"price\":\"0.00\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0.00\",\"remaining_iqd\":\"330000\",\"bin_id\":1,\"price_per_kg_iqd\":\"10250.00\",\"price_per_kg_usd\":\"0.00\",\"invoice_number\":\"1112\",\"date\":\"2025-08-04\"}',NULL,'{\"action_type\":\"purchase_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":330000}','130.193.209.66'),(83,2,'delete','purchases',19,'کڕینەکە سڕایەوە (invoice: 1111, کۆمپانیا: دەشتی, مادە: لمی ڕەش)','2025-08-04 15:07:00',0,'{\"company_id\":1,\"company_name\":\"دەشتی\",\"driver\":\"محمد سەردار\",\"location\":\"کەنجاڕە\",\"material_id\":1,\"material_name\":\"لمی ڕەش\",\"amount_iqd\":\"312420.00\",\"kg\":\"30480.00\",\"price\":\"0.00\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0.00\",\"remaining_iqd\":\"312000\",\"bin_id\":1,\"price_per_kg_iqd\":\"10250.00\",\"price_per_kg_usd\":\"0.00\",\"invoice_number\":\"1111\",\"date\":\"2025-08-04\"}',NULL,'{\"action_type\":\"purchase_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":312000}','130.193.209.66'),(84,2,'delete','purchases',18,'کڕینەکە سڕایەوە (invoice: 1110, کۆمپانیا: دەشتی, مادە: لمی ڕەش)','2025-08-04 15:07:03',0,'{\"company_id\":1,\"company_name\":\"دەشتی\",\"driver\":\"داستان علی\",\"location\":\"کەنجاڕە\",\"material_id\":1,\"material_name\":\"لمی ڕەش\",\"amount_iqd\":\"350755.00\",\"kg\":\"34220.00\",\"price\":\"0.00\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0.00\",\"remaining_iqd\":\"350000\",\"bin_id\":1,\"price_per_kg_iqd\":\"10250.00\",\"price_per_kg_usd\":\"0.00\",\"invoice_number\":\"1110\",\"date\":\"2025-08-04\"}',NULL,'{\"action_type\":\"purchase_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":350000}','130.193.209.66'),(85,2,'delete','purchases',1,'کڕینەکە سڕایەوە (invoice: 1103, کۆمپانیا: دەشتی, مادە: لمی ڕەش)','2025-08-04 15:07:07',0,'{\"company_id\":1,\"company_name\":\"دەشتی\",\"driver\":\"داستان علی\",\"location\":\"کەنجاڕە\",\"material_id\":1,\"material_name\":\"لمی ڕەش\",\"amount_iqd\":\"345220.00\",\"kg\":\"33680.00\",\"price\":\"0.00\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139450\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0.00\",\"remaining_iqd\":\"345000\",\"bin_id\":1,\"price_per_kg_iqd\":\"10250.00\",\"price_per_kg_usd\":\"0.00\",\"invoice_number\":\"1103\",\"date\":\"2025-08-02\"}',NULL,'{\"action_type\":\"purchase_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":345000}','130.193.209.66'),(86,2,'delete','purchases',2,'کڕینەکە سڕایەوە (invoice: 1105, کۆمپانیا: دەشتی, مادە: لمی ڕەش)','2025-08-04 15:07:10',0,'{\"company_id\":1,\"company_name\":\"دەشتی\",\"driver\":\"کاک دەشتی\",\"location\":\"کەنجاڕە\",\"material_id\":1,\"material_name\":\"لمی ڕەش\",\"amount_iqd\":\"361620.00\",\"kg\":\"35280.00\",\"price\":\"0.00\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139450\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0.00\",\"remaining_iqd\":\"361000\",\"bin_id\":1,\"price_per_kg_iqd\":\"10250.00\",\"price_per_kg_usd\":\"0.00\",\"invoice_number\":\"1105\",\"date\":\"2025-08-02\"}',NULL,'{\"action_type\":\"purchase_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":361000}','130.193.209.66'),(87,2,'delete','purchases',14,'کڕینەکە سڕایەوە (invoice: 1426, کۆمپانیا: سۆران, مادە: چەو)','2025-08-04 15:07:15',0,'{\"company_id\":2,\"company_name\":\"سۆران\",\"driver\":\"سۆران\",\"location\":\"کارگە و چەو و لمی عطا\",\"material_id\":2,\"material_name\":\"چەو\",\"amount_iqd\":\"176885.73\",\"kg\":\"30325.00\",\"price\":\"0.00\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139450\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0.00\",\"remaining_iqd\":\"176000\",\"bin_id\":4,\"price_per_kg_iqd\":\"5833.00\",\"price_per_kg_usd\":\"0.00\",\"invoice_number\":\"1426\",\"date\":\"2025-08-02\"}',NULL,'{\"action_type\":\"purchase_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":176000}','130.193.209.66'),(88,2,'delete','sales',24,'فرۆشتنەکە سڕایەوە (invoice: A-0074, A-0075, A-0076, کڕیار: و.سەرباز , فۆرمۆلا: Chawy lws saqf 350)','2025-08-04 15:10:12',0,'{\"customer_id\":60,\"customer_name\":\"و.سەرباز \",\"recipient\":\"\",\"location\":\"پیرەمەگرون\",\"quantity\":\"28.00\",\"price_per_unit\":\"45.00\",\"total_price\":\"1260.00\",\"payment_type\":\"نەقد\",\"amount_paid_usd\":\"1200.00\",\"amount_paid_iq\":\"80000.00\",\"dolar_rate\":\"139400.00\",\"remaining_amount\":\"0.00\",\"invoice_number\":\"A-0074, A-0075, A-0076\",\"order_date\":\"2025-08-03\",\"notes\":\"1,200$+80,000 لای مام شاڵاو پەمپە\",\"formula_id\":42,\"formula_name\":\"Chawy lws saqf 350\",\"discount\":\"2.61\"}',NULL,'{\"action_type\":\"sale_deletion\",\"payment_status\":\"paid\",\"currency_used\":\"USD\",\"total_paid\":81200,\"remaining_debt\":\"0.00\"}','130.193.209.66'),(89,2,'delete','sales',23,'فرۆشتنەکە سڕایەوە (invoice: A-0072, کڕیار: کاک دانا گولیجەیی, فۆرمۆلا: ARZY 25 MEGA)','2025-08-04 15:10:14',0,'{\"customer_id\":186,\"customer_name\":\"کاک دانا گولیجەیی\",\"recipient\":\"\",\"location\":\"پیرەمەگرون\",\"quantity\":\"3.00\",\"price_per_unit\":\"45.00\",\"total_price\":\"135.00\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0.00\",\"amount_paid_iq\":\"0.00\",\"dolar_rate\":\"139400.00\",\"remaining_amount\":\"135.00\",\"invoice_number\":\"A-0072\",\"order_date\":\"2025-08-03\",\"notes\":\"\",\"formula_id\":27,\"formula_name\":\"ARZY 25 MEGA\",\"discount\":\"0.00\"}',NULL,'{\"action_type\":\"sale_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":\"135.00\"}','130.193.209.66'),(90,2,'delete','sales',22,'فرۆشتنەکە سڕایەوە (invoice: A-0064, A-0065, A-0069, A-0070, A-0071, A-0073, کڕیار: و.ئومێد, فۆرمۆلا: Chawy lws saqf 350)','2025-08-04 15:10:16',0,'{\"customer_id\":57,\"customer_name\":\"و.ئومێد\",\"recipient\":\"\",\"location\":\"دارتو مولانا\",\"quantity\":\"47.00\",\"price_per_unit\":\"45.00\",\"total_price\":\"2115.00\",\"payment_type\":\"نەقد\",\"amount_paid_usd\":\"2100.00\",\"amount_paid_iq\":\"0.00\",\"dolar_rate\":\"139400.00\",\"remaining_amount\":\"0.00\",\"invoice_number\":\"A-0064, A-0065, A-0069, A-0070, A-0071, A-0073\",\"order_date\":\"2025-08-03\",\"notes\":\"2,100$ لای توانای برامە\",\"formula_id\":42,\"formula_name\":\"Chawy lws saqf 350\",\"discount\":\"15.00\"}',NULL,'{\"action_type\":\"sale_deletion\",\"payment_status\":\"paid\",\"currency_used\":\"USD\",\"total_paid\":2100,\"remaining_debt\":\"0.00\"}','130.193.209.66'),(91,2,'delete','sales',21,'فرۆشتنەکە سڕایەوە (invoice: A-0063, A-0066, A-0067, A-0068, کڕیار: کاک شوان مقاول, فۆرمۆلا: Chawy lws saqf 350)','2025-08-04 15:10:18',0,'{\"customer_id\":181,\"customer_name\":\"کاک شوان مقاول\",\"recipient\":\"و. كرمانج\",\"location\":\"ڕاپەڕین\",\"quantity\":\"35.00\",\"price_per_unit\":\"45.00\",\"total_price\":\"1575.00\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0.00\",\"amount_paid_iq\":\"0.00\",\"dolar_rate\":\"139400.00\",\"remaining_amount\":\"1575.00\",\"invoice_number\":\"A-0063, A-0066, A-0067, A-0068\",\"order_date\":\"2025-08-03\",\"notes\":\"\",\"formula_id\":42,\"formula_name\":\"Chawy lws saqf 350\",\"discount\":\"0.00\"}',NULL,'{\"action_type\":\"sale_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":\"1575.00\"}','130.193.209.66'),(92,2,'delete','sales',16,'فرۆشتنەکە سڕایەوە (invoice: A-0042, A-0044, A-0045, کڕیار: کاک ئەرسەلان نوسینگە, فۆرمۆلا: ARZY 21 MEGA)','2025-08-04 15:10:20',0,'{\"customer_id\":145,\"customer_name\":\"کاک ئەرسەلان نوسینگە\",\"recipient\":\"و . هیوا \",\"location\":\"بەرامبەر حەسەن تەپە\",\"quantity\":\"30.00\",\"price_per_unit\":\"43.34\",\"total_price\":\"1300.20\",\"payment_type\":\"نەقد\",\"amount_paid_usd\":\"1300.00\",\"amount_paid_iq\":\"0.00\",\"dolar_rate\":\"139400.00\",\"remaining_amount\":\"0.00\",\"invoice_number\":\"A-0042, A-0044, A-0045\",\"order_date\":\"2025-08-03\",\"notes\":\"1300 دۆلار واسڵ\",\"formula_id\":26,\"formula_name\":\"ARZY 21 MEGA\",\"discount\":\"0.20\"}',NULL,'{\"action_type\":\"sale_deletion\",\"payment_status\":\"paid\",\"currency_used\":\"USD\",\"total_paid\":1300,\"remaining_debt\":\"0.00\"}','130.193.209.66'),(93,2,'delete','sales',17,'فرۆشتنەکە سڕایەوە (invoice: A-0043, A-0046, کڕیار: ک.ئاسۆ زەرزی, فۆرمۆلا: ARZY 25 MEGA)','2025-08-04 15:10:22',0,'{\"customer_id\":146,\"customer_name\":\"ک.ئاسۆ زەرزی\",\"recipient\":\"ک . ئاسۆ\",\"location\":\"پیرەمەگرون\",\"quantity\":\"13.00\",\"price_per_unit\":\"45.00\",\"total_price\":\"585.00\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0.00\",\"amount_paid_iq\":\"0.00\",\"dolar_rate\":\"139400.00\",\"remaining_amount\":\"585.00\",\"invoice_number\":\"A-0043, A-0046\",\"order_date\":\"2025-08-03\",\"notes\":\"\",\"formula_id\":27,\"formula_name\":\"ARZY 25 MEGA\",\"discount\":\"0.00\"}',NULL,'{\"action_type\":\"sale_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":\"585.00\"}','130.193.209.66'),(94,2,'delete','sales',18,'فرۆشتنەکە سڕایەوە (invoice: A-0047, A-0048, A-0049, A-0050, A-0051, A-0053, A-0054, A-0055, A-0056, A-0057, کڕیار: هێمن حسن شێخۆ, فۆرمۆلا: ARZY 35 M)','2025-08-04 15:10:24',0,'{\"customer_id\":147,\"customer_name\":\"هێمن حسن شێخۆ\",\"recipient\":\"و, شوانه, و.شوانا\",\"location\":\"دوكان\",\"quantity\":\"77.00\",\"price_per_unit\":\"53.00\",\"total_price\":\"4081.00\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0.00\",\"amount_paid_iq\":\"0.00\",\"dolar_rate\":\"139400.00\",\"remaining_amount\":\"4081.00\",\"invoice_number\":\"A-0047, A-0048, A-0049, A-0050, A-0051, A-0053, A-0054, A-0055, A-0056, A-0057\",\"order_date\":\"2025-08-03\",\"notes\":\"\",\"formula_id\":38,\"formula_name\":\"ARZY 35 M\",\"discount\":\"0.00\"}',NULL,'{\"action_type\":\"sale_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":\"4081.00\"}','130.193.209.66'),(95,2,'delete','sales',19,'فرۆشتنەکە سڕایەوە (invoice: A-0052, A-0077, کڕیار: دانا کۆنکرێت, فۆرمۆلا: PAYA 35 M)','2025-08-04 15:10:26',0,'{\"customer_id\":107,\"customer_name\":\"دانا کۆنکرێت\",\"recipient\":\"\",\"location\":\"كه له ونان\",\"quantity\":\"4.00\",\"price_per_unit\":\"45.00\",\"total_price\":\"180.00\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0.00\",\"amount_paid_iq\":\"0.00\",\"dolar_rate\":\"139400.00\",\"remaining_amount\":\"180.00\",\"invoice_number\":\"A-0052, A-0077\",\"order_date\":\"2025-08-03\",\"notes\":\"\",\"formula_id\":41,\"formula_name\":\"PAYA 35 M\",\"discount\":\"0.00\"}',NULL,'{\"action_type\":\"sale_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":\"180.00\"}','130.193.209.66'),(96,2,'delete','sales',20,'فرۆشتنەکە سڕایەوە (invoice: A-0062, کڕیار: كاك ازار , فۆرمۆلا: ARZY 25 MEGA)','2025-08-04 15:10:28',0,'{\"customer_id\":99,\"customer_name\":\"كاك ازار \",\"recipient\":\"کاک کاروان \",\"location\":\"کێلە سپی\",\"quantity\":\"5.00\",\"price_per_unit\":\"45.00\",\"total_price\":\"225.00\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0.00\",\"amount_paid_iq\":\"0.00\",\"dolar_rate\":\"139400.00\",\"remaining_amount\":\"225.00\",\"invoice_number\":\"A-0062\",\"order_date\":\"2025-08-03\",\"notes\":\"\",\"formula_id\":27,\"formula_name\":\"ARZY 25 MEGA\",\"discount\":\"0.00\"}',NULL,'{\"action_type\":\"sale_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":\"225.00\"}','130.193.209.66'),(97,2,'delete','sales',2,'فرۆشتنەکە سڕایەوە (invoice: A-0012, A-0011, A-0010, A-0009, A-0008, A-0007, کڕیار: هاوڕێ مجید, فۆرمۆلا: ARZY 25 MEGA)','2025-08-04 15:10:30',0,'{\"customer_id\":113,\"customer_name\":\"هاوڕێ مجید\",\"recipient\":\"هاورێ مجید\",\"location\":\"بەختیاری\",\"quantity\":\"64.00\",\"price_per_unit\":\"45.00\",\"total_price\":\"2880.00\",\"payment_type\":\"نەقد\",\"amount_paid_usd\":\"2880.00\",\"amount_paid_iq\":\"0.00\",\"dolar_rate\":null,\"remaining_amount\":null,\"invoice_number\":\"A-0012, A-0011, A-0010, A-0009, A-0008, A-0007\",\"order_date\":\"2025-08-02\",\"notes\":\"\",\"formula_id\":27,\"formula_name\":\"ARZY 25 MEGA\",\"discount\":\"0.00\"}',NULL,'{\"action_type\":\"sale_deletion\",\"payment_status\":\"paid\",\"currency_used\":\"USD\",\"total_paid\":2880,\"remaining_debt\":null}','130.193.209.66'),(98,2,'delete','sales',3,'فرۆشتنەکە سڕایەوە (invoice: A-0014, کڕیار: هاوڕێ مجید, فۆرمۆلا: ARZY 25 MEGA)','2025-08-04 15:10:32',0,'{\"customer_id\":113,\"customer_name\":\"هاوڕێ مجید\",\"recipient\":\"هاورێ مجید\",\"location\":\"بەختیاری\",\"quantity\":\"6.00\",\"price_per_unit\":\"45.00\",\"total_price\":\"270.00\",\"payment_type\":\"نەقد\",\"amount_paid_usd\":\"270.00\",\"amount_paid_iq\":\"0.00\",\"dolar_rate\":null,\"remaining_amount\":null,\"invoice_number\":\"A-0014\",\"order_date\":\"2025-08-02\",\"notes\":\"\",\"formula_id\":27,\"formula_name\":\"ARZY 25 MEGA\",\"discount\":\"0.00\"}',NULL,'{\"action_type\":\"sale_deletion\",\"payment_status\":\"paid\",\"currency_used\":\"USD\",\"total_paid\":270,\"remaining_debt\":null}','130.193.209.66'),(99,2,'delete','sales',5,'فرۆشتنەکە سڕایەوە (invoice: A-0023, کڕیار: و.کاروان, فۆرمۆلا: ARZY 21 MEGA)','2025-08-04 15:10:34',0,'{\"customer_id\":34,\"customer_name\":\"و.کاروان\",\"recipient\":\"حاكم احمد\",\"location\":\"پیرەمەگرون\",\"quantity\":\"9.00\",\"price_per_unit\":\"41.00\",\"total_price\":\"369.00\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0.00\",\"amount_paid_iq\":\"0.00\",\"dolar_rate\":\"139450.00\",\"remaining_amount\":\"369.00\",\"invoice_number\":\"A-0023\",\"order_date\":\"2025-08-02\",\"notes\":\"\",\"formula_id\":26,\"formula_name\":\"ARZY 21 MEGA\",\"discount\":\"0.00\"}',NULL,'{\"action_type\":\"sale_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":\"369.00\"}','130.193.209.66'),(100,2,'delete','sales',6,'فرۆشتنەکە سڕایەوە (invoice: A-0022, A-0019, کڕیار: و.فەرهاد, فۆرمۆلا: ARZY 25 MEGA)','2025-08-04 15:10:37',0,'{\"customer_id\":50,\"customer_name\":\"و.فەرهاد\",\"recipient\":\"فەرهاد\",\"location\":\"معمل جيمنتو ماس, مه عملى جيمنتو ماس\",\"quantity\":\"16.50\",\"price_per_unit\":\"45.00\",\"total_price\":\"742.50\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0.00\",\"amount_paid_iq\":\"0.00\",\"dolar_rate\":\"139450.00\",\"remaining_amount\":\"742.50\",\"invoice_number\":\"A-0022, A-0019\",\"order_date\":\"2025-08-02\",\"notes\":\"\",\"formula_id\":27,\"formula_name\":\"ARZY 25 MEGA\",\"discount\":\"0.00\"}',NULL,'{\"action_type\":\"sale_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":\"742.50\"}','130.193.209.66'),(101,2,'delete','sales',7,'فرۆشتنەکە سڕایەوە (invoice: A-0025, A-0024, کڕیار: محمد احمد, فۆرمۆلا: Chawy lws saqf 350)','2025-08-04 15:10:39',0,'{\"customer_id\":117,\"customer_name\":\"محمد احمد\",\"recipient\":\"وه ستا ديار\",\"location\":\"ڕاپەڕین\",\"quantity\":\"18.50\",\"price_per_unit\":\"47.00\",\"total_price\":\"869.50\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0.00\",\"amount_paid_iq\":\"0.00\",\"dolar_rate\":\"139450.00\",\"remaining_amount\":\"869.50\",\"invoice_number\":\"A-0025, A-0024\",\"order_date\":\"2025-08-02\",\"notes\":\"\",\"formula_id\":42,\"formula_name\":\"Chawy lws saqf 350\",\"discount\":\"0.00\"}',NULL,'{\"action_type\":\"sale_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":\"869.50\"}','130.193.209.66'),(102,2,'delete','sales',8,'فرۆشتنەکە سڕایەوە (invoice: A-0038, A-0027, A-0026, کڕیار: حاجي محمود, فۆرمۆلا: Chawy lws saqf 350)','2025-08-04 15:10:41',0,'{\"customer_id\":118,\"customer_name\":\"حاجي محمود\",\"recipient\":\"\",\"location\":\"تافگا\",\"quantity\":\"24.50\",\"price_per_unit\":\"46.00\",\"total_price\":\"1127.00\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0.00\",\"amount_paid_iq\":\"0.00\",\"dolar_rate\":\"139450.00\",\"remaining_amount\":\"1127.00\",\"invoice_number\":\"A-0038, A-0027, A-0026\",\"order_date\":\"2025-08-02\",\"notes\":\"\",\"formula_id\":42,\"formula_name\":\"Chawy lws saqf 350\",\"discount\":\"0.00\"}',NULL,'{\"action_type\":\"sale_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":\"1127.00\"}','130.193.209.66'),(103,2,'delete','sales',11,'فرۆشتنەکە سڕایەوە (invoice: A-0020, A-0018, A-0017, A-0016, A-0015, کڕیار: ک.تۆفیق اسماعیل, فۆرمۆلا: SAQF 400 )','2025-08-04 15:10:43',0,'{\"customer_id\":105,\"customer_name\":\"ک.تۆفیق اسماعیل\",\"recipient\":\"ک.تۆفیق\",\"location\":\"ڕاپەڕین\",\"quantity\":\"44.00\",\"price_per_unit\":\"47.00\",\"total_price\":\"2068.00\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0.00\",\"amount_paid_iq\":\"0.00\",\"dolar_rate\":\"139450.00\",\"remaining_amount\":\"2068.00\",\"invoice_number\":\"A-0020, A-0018, A-0017, A-0016, A-0015\",\"order_date\":\"2025-08-02\",\"notes\":\"\",\"formula_id\":19,\"formula_name\":\"SAQF 400 \",\"discount\":\"0.00\"}',NULL,'{\"action_type\":\"sale_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":\"2068.00\"}','130.193.209.66'),(104,2,'delete','sales',12,'فرۆشتنەکە سڕایەوە (invoice: A-0041, A-0039, A-0037, A-0036, A-0035, A-0034, A-0033, A-0032, A-0031, A-0030, A-0029, A-0028, کڕیار: هێدی وا‌حد علي, فۆرمۆلا: ARZY 30 MEGA)','2025-08-04 15:10:45',0,'{\"customer_id\":119,\"customer_name\":\"هێدی وا‌حد علي\",\"recipient\":\"وەستا ئازاد\",\"location\":\"پیرەمەگرون\",\"quantity\":\"112.00\",\"price_per_unit\":\"47.00\",\"total_price\":\"5264.00\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0.00\",\"amount_paid_iq\":\"0.00\",\"dolar_rate\":\"139450.00\",\"remaining_amount\":\"5264.00\",\"invoice_number\":\"A-0041, A-0039, A-0037, A-0036, A-0035, A-0034, A-0033, A-0032, A-0031, A-0030, A-0029, A-0028\",\"order_date\":\"2025-08-02\",\"notes\":\"\",\"formula_id\":30,\"formula_name\":\"ARZY 30 MEGA\",\"discount\":\"0.00\"}',NULL,'{\"action_type\":\"sale_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":\"5264.00\"}','130.193.209.66'),(105,2,'delete','sales',13,'فرۆشتنەکە سڕایەوە (invoice: A-0005, A-0004, A-0003, A-0002, A-0001, کڕیار: و.قالە, فۆرمۆلا: ARZY 25 MEGA)','2025-08-04 15:10:48',0,'{\"customer_id\":37,\"customer_name\":\"و.قالە\",\"recipient\":\"وەستا قالە\",\"location\":\"ڕاپەڕین\",\"quantity\":\"50.00\",\"price_per_unit\":\"45.00\",\"total_price\":\"2250.00\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0.00\",\"amount_paid_iq\":\"0.00\",\"dolar_rate\":\"139450.00\",\"remaining_amount\":\"2250.00\",\"invoice_number\":\"A-0005, A-0004, A-0003, A-0002, A-0001\",\"order_date\":\"2025-08-02\",\"notes\":\"\",\"formula_id\":27,\"formula_name\":\"ARZY 25 MEGA\",\"discount\":\"0.00\"}',NULL,'{\"action_type\":\"sale_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":\"2250.00\"}','130.193.209.66'),(106,2,'delete','sales',14,'فرۆشتنەکە سڕایەوە (invoice: A-0013, A-0006, کڕیار: و.قالە, فۆرمۆلا: ARZY 25 MEGA)','2025-08-04 15:10:50',0,'{\"customer_id\":37,\"customer_name\":\"و.قالە\",\"recipient\":\"وەستا قالە\",\"location\":\"ڕاپەڕین\",\"quantity\":\"18.00\",\"price_per_unit\":\"45.00\",\"total_price\":\"810.00\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0.00\",\"amount_paid_iq\":\"0.00\",\"dolar_rate\":\"139450.00\",\"remaining_amount\":\"810.00\",\"invoice_number\":\"A-0013, A-0006\",\"order_date\":\"2025-08-02\",\"notes\":\"\",\"formula_id\":27,\"formula_name\":\"ARZY 25 MEGA\",\"discount\":\"0.00\"}',NULL,'{\"action_type\":\"sale_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":\"810.00\"}','130.193.209.66'),(107,2,'delete','sales',15,'فرۆشتنەکە سڕایەوە (invoice: A-0040, کڕیار: و.قالە, فۆرمۆلا: Chawy lws saqf 350)','2025-08-04 15:10:52',0,'{\"customer_id\":37,\"customer_name\":\"و.قالە\",\"recipient\":\"\",\"location\":\"قلیاسان\",\"quantity\":\"6.00\",\"price_per_unit\":\"45.00\",\"total_price\":\"270.00\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0.00\",\"amount_paid_iq\":\"0.00\",\"dolar_rate\":\"139450.00\",\"remaining_amount\":\"270.00\",\"invoice_number\":\"A-0040\",\"order_date\":\"2025-08-02\",\"notes\":\"\",\"formula_id\":42,\"formula_name\":\"Chawy lws saqf 350\",\"discount\":\"0.00\"}',NULL,'{\"action_type\":\"sale_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":\"270.00\"}','130.193.209.66'),(108,2,'delete','purchases',35,'کڕینەکە سڕایەوە (invoice: 1437, کۆمپانیا: سۆران, مادە: چەو)','2025-08-04 15:11:16',0,'{\"company_id\":2,\"company_name\":\"سۆران\",\"driver\":\"سۆران\",\"location\":\"کارگە و چەو و لمی عطا\",\"material_id\":2,\"material_name\":\"چەو\",\"amount_iqd\":\"176302.43\",\"kg\":\"30225.00\",\"price\":\"0.00\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0.00\",\"remaining_iqd\":\"176000\",\"bin_id\":4,\"price_per_kg_iqd\":\"5833.00\",\"price_per_kg_usd\":\"0.00\",\"invoice_number\":\"1437\",\"date\":\"2025-08-04\"}',NULL,'{\"action_type\":\"purchase_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":176000}','130.193.209.66'),(109,2,'delete','purchases',26,'کڕینەکە سڕایەوە (invoice: 1110, کۆمپانیا: دەشتی, مادە: لمی ڕەش)','2025-08-04 15:11:20',0,'{\"company_id\":1,\"company_name\":\"دەشتی\",\"driver\":\"داستان علی\",\"location\":\"کەنجاڕە\",\"material_id\":1,\"material_name\":\"لمی ڕەش\",\"amount_iqd\":\"350755.00\",\"kg\":\"34220.00\",\"price\":\"0.00\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0.00\",\"remaining_iqd\":\"350000\",\"bin_id\":1,\"price_per_kg_iqd\":\"10250.00\",\"price_per_kg_usd\":\"0.00\",\"invoice_number\":\"1110\",\"date\":\"2025-08-04\"}',NULL,'{\"action_type\":\"purchase_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":350000}','130.193.209.66'),(110,2,'delete','purchases',25,'کڕینەکە سڕایەوە (invoice: 1111, کۆمپانیا: دەشتی, مادە: لمی ڕەش)','2025-08-04 15:11:21',0,'{\"company_id\":1,\"company_name\":\"دەشتی\",\"driver\":\"محمد سەردار\",\"location\":\"کەنجاڕە\",\"material_id\":1,\"material_name\":\"لمی ڕەش\",\"amount_iqd\":\"312420.00\",\"kg\":\"30480.00\",\"price\":\"0.00\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0.00\",\"remaining_iqd\":\"312000\",\"bin_id\":1,\"price_per_kg_iqd\":\"10250.00\",\"price_per_kg_usd\":\"0.00\",\"invoice_number\":\"1111\",\"date\":\"2025-08-04\"}',NULL,'{\"action_type\":\"purchase_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":312000}','130.193.209.66'),(111,2,'delete','purchases',24,'کڕینەکە سڕایەوە (invoice: 1112, کۆمپانیا: دەشتی, مادە: لمی ڕەش)','2025-08-04 15:11:23',0,'{\"company_id\":1,\"company_name\":\"دەشتی\",\"driver\":\"محمد علی\",\"location\":\"کەنجاڕە\",\"material_id\":1,\"material_name\":\"لمی ڕەش\",\"amount_iqd\":\"330050.00\",\"kg\":\"32200.00\",\"price\":\"0.00\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0.00\",\"remaining_iqd\":\"330000\",\"bin_id\":1,\"price_per_kg_iqd\":\"10250.00\",\"price_per_kg_usd\":\"0.00\",\"invoice_number\":\"1112\",\"date\":\"2025-08-04\"}',NULL,'{\"action_type\":\"purchase_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":330000}','130.193.209.66'),(112,2,'delete','purchases',29,'کڕینەکە سڕایەوە (invoice: 1109, کۆمپانیا: دەشتی, مادە: لمی ڕەش)','2025-08-04 15:11:25',0,'{\"company_id\":1,\"company_name\":\"دەشتی\",\"driver\":\"کاک دەشتی\",\"location\":\"کەنجاڕە\",\"material_id\":1,\"material_name\":\"لمی ڕەش\",\"amount_iqd\":\"341530.00\",\"kg\":\"33320.00\",\"price\":\"0.00\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0.00\",\"remaining_iqd\":\"341000\",\"bin_id\":1,\"price_per_kg_iqd\":\"10250.00\",\"price_per_kg_usd\":\"0.00\",\"invoice_number\":\"1109\",\"date\":\"2025-08-03\"}',NULL,'{\"action_type\":\"purchase_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":341000}','130.193.209.66'),(113,2,'delete','purchases',40,'کڕینەکە سڕایەوە (invoice: 5144, کۆمپانیا: تڕێلەکەی خۆمان, مادە: چەو)','2025-08-04 15:11:26',0,'{\"company_id\":6,\"company_name\":\"تڕێلەکەی خۆمان\",\"driver\":\"کاک عیماد شۆفێر\",\"location\":\"غەسالەی نەورۆز\",\"material_id\":2,\"material_name\":\"چەو\",\"amount_iqd\":\"189572.50\",\"kg\":\"32500.00\",\"price\":\"0.00\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0.00\",\"remaining_iqd\":\"189000\",\"bin_id\":4,\"price_per_kg_iqd\":\"5833.00\",\"price_per_kg_usd\":\"0.00\",\"invoice_number\":\"5144\",\"date\":\"2025-08-03\"}',NULL,'{\"action_type\":\"purchase_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":189000}','130.193.209.66'),(114,2,'delete','purchases',39,'کڕینەکە سڕایەوە (invoice: 5144, کۆمپانیا: تڕێلەکەی خۆمان, مادە: چەو)','2025-08-04 15:11:28',0,'{\"company_id\":6,\"company_name\":\"تڕێلەکەی خۆمان\",\"driver\":\"کاک عیماد شۆفێر\",\"location\":\"غەسالەی نەورۆز\",\"material_id\":2,\"material_name\":\"چەو\",\"amount_iqd\":\"189572.50\",\"kg\":\"32500.00\",\"price\":\"0.00\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0.00\",\"remaining_iqd\":\"189000\",\"bin_id\":4,\"price_per_kg_iqd\":\"5833.00\",\"price_per_kg_usd\":\"0.00\",\"invoice_number\":\"5144\",\"date\":\"2025-08-03\"}',NULL,'{\"action_type\":\"purchase_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":189000}','130.193.209.66'),(115,2,'delete','purchases',38,'کڕینەکە سڕایەوە (invoice: 5144, کۆمپانیا: تڕێلەکەی خۆمان, مادە: چەو)','2025-08-04 15:11:32',0,'{\"company_id\":6,\"company_name\":\"تڕێلەکەی خۆمان\",\"driver\":\"کاک عیماد شۆفێر\",\"location\":\"غەسالەی نەورۆز\",\"material_id\":2,\"material_name\":\"چەو\",\"amount_iqd\":\"189572.50\",\"kg\":\"32500.00\",\"price\":\"0.00\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0.00\",\"remaining_iqd\":\"189000\",\"bin_id\":4,\"price_per_kg_iqd\":\"5833.00\",\"price_per_kg_usd\":\"0.00\",\"invoice_number\":\"5144\",\"date\":\"2025-08-03\"}',NULL,'{\"action_type\":\"purchase_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":189000}','130.193.209.66'),(116,2,'delete','purchases',37,'کڕینەکە سڕایەوە (invoice: 5144, کۆمپانیا: تڕێلەکەی خۆمان, مادە: چەو)','2025-08-04 15:11:35',0,'{\"company_id\":6,\"company_name\":\"تڕێلەکەی خۆمان\",\"driver\":\"کاک عیماد شۆفێر\",\"location\":\"غەسالەی نەورۆز\",\"material_id\":2,\"material_name\":\"چەو\",\"amount_iqd\":\"189572.50\",\"kg\":\"32500.00\",\"price\":\"0.00\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0.00\",\"remaining_iqd\":\"189000\",\"bin_id\":4,\"price_per_kg_iqd\":\"5833.00\",\"price_per_kg_usd\":\"0.00\",\"invoice_number\":\"5144\",\"date\":\"2025-08-03\"}',NULL,'{\"action_type\":\"purchase_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":189000}','130.193.209.66'),(117,2,'delete','purchases',34,'کڕینەکە سڕایەوە (invoice: 5118, کۆمپانیا: تڕێلەکەی خۆمان, مادە: چەو)','2025-08-04 15:11:37',0,'{\"company_id\":6,\"company_name\":\"تڕێلەکەی خۆمان\",\"driver\":\"کاک عیماد شۆفێر\",\"location\":\"غەسالەی نەورۆز\",\"material_id\":2,\"material_name\":\"چەو\",\"amount_iqd\":\"190039.14\",\"kg\":\"32580.00\",\"price\":\"0.00\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0.00\",\"remaining_iqd\":\"190000\",\"bin_id\":4,\"price_per_kg_iqd\":\"5833.00\",\"price_per_kg_usd\":\"0.00\",\"invoice_number\":\"5118\",\"date\":\"2025-08-03\"}',NULL,'{\"action_type\":\"purchase_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":190000}','130.193.209.66'),(118,2,'delete','purchases',9,'کڕینەکە سڕایەوە (invoice: 503902, کۆمپانیا: مامە کاوە, مادە: چیمەنتۆ)','2025-08-04 15:11:39',0,'{\"company_id\":3,\"company_name\":\"مامە کاوە\",\"driver\":\"ڕامیار جلال\",\"location\":\"لاڤارج\",\"material_id\":3,\"material_name\":\"چیمەنتۆ\",\"amount_iqd\":\"0.00\",\"kg\":\"31100.00\",\"price\":\"2005.95\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139450\",\"type\":\"دۆلار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"2005.95\",\"remaining_iqd\":\"0\",\"bin_id\":5,\"price_per_kg_iqd\":\"0.00\",\"price_per_kg_usd\":\"64.50\",\"invoice_number\":\"503902\",\"date\":\"2025-08-03\"}',NULL,'{\"action_type\":\"purchase_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":2005.95}','130.193.209.66'),(119,2,'delete','purchases',10,'کڕینەکە سڕایەوە (invoice: 83516201, کۆمپانیا: حاجی فاروق چیمەنتۆ, مادە: چیمەنتۆ)','2025-08-04 15:11:40',0,'{\"company_id\":4,\"company_name\":\"حاجی فاروق چیمەنتۆ\",\"driver\":\"کارزان کمال\",\"location\":\"ماس\",\"material_id\":3,\"material_name\":\"چیمەنتۆ\",\"amount_iqd\":\"0.00\",\"kg\":\"39420.00\",\"price\":\"2503.17\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139450\",\"type\":\"دۆلار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"2503.17\",\"remaining_iqd\":\"0\",\"bin_id\":6,\"price_per_kg_iqd\":\"0.00\",\"price_per_kg_usd\":\"63.50\",\"invoice_number\":\"83516201\",\"date\":\"2025-08-03\"}',NULL,'{\"action_type\":\"purchase_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":2503.17}','130.193.209.66'),(120,2,'delete','purchases',11,'کڕینەکە سڕایەوە (invoice: 83516262, کۆمپانیا: حاجی فاروق چیمەنتۆ, مادە: چیمەنتۆ)','2025-08-04 15:11:42',0,'{\"company_id\":4,\"company_name\":\"حاجی فاروق چیمەنتۆ\",\"driver\":\"کارزان کمال\",\"location\":\"ماس\",\"material_id\":3,\"material_name\":\"چیمەنتۆ\",\"amount_iqd\":\"0.00\",\"kg\":\"39280.00\",\"price\":\"2494.28\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139450\",\"type\":\"دۆلار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"2494.28\",\"remaining_iqd\":\"0\",\"bin_id\":6,\"price_per_kg_iqd\":\"0.00\",\"price_per_kg_usd\":\"63.50\",\"invoice_number\":\"83516262\",\"date\":\"2025-08-03\"}',NULL,'{\"action_type\":\"purchase_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":2494.28}','130.193.209.66'),(121,2,'delete','purchases',23,'کڕینەکە سڕایەوە (invoice: 5144, کۆمپانیا: تڕێلەکەی خۆمان, مادە: چەو)','2025-08-04 15:11:44',0,'{\"company_id\":6,\"company_name\":\"تڕێلەکەی خۆمان\",\"driver\":\"کاک عیماد شۆفێر\",\"location\":\"غەسالەی نەورۆز\",\"material_id\":2,\"material_name\":\"چەو\",\"amount_iqd\":\"189572.50\",\"kg\":\"32500.00\",\"price\":\"0.00\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0.00\",\"remaining_iqd\":\"189000\",\"bin_id\":4,\"price_per_kg_iqd\":\"5833.00\",\"price_per_kg_usd\":\"0.00\",\"invoice_number\":\"5144\",\"date\":\"2025-08-03\"}',NULL,'{\"action_type\":\"purchase_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":189000}','130.193.209.66'),(122,2,'delete','purchases',30,'کڕینەکە سڕایەوە (invoice: 1108, کۆمپانیا: دەشتی, مادە: لمی ڕەش)','2025-08-04 15:11:46',0,'{\"company_id\":1,\"company_name\":\"دەشتی\",\"driver\":\"داستان علی\",\"location\":\"کەنجاڕە\",\"material_id\":1,\"material_name\":\"لمی ڕەش\",\"amount_iqd\":\"347987.50\",\"kg\":\"33950.00\",\"price\":\"0.00\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0.00\",\"remaining_iqd\":\"347000\",\"bin_id\":1,\"price_per_kg_iqd\":\"10250.00\",\"price_per_kg_usd\":\"0.00\",\"invoice_number\":\"1108\",\"date\":\"2025-08-03\"}',NULL,'{\"action_type\":\"purchase_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":347000}','130.193.209.66'),(123,2,'delete','purchases',32,'کڕینەکە سڕایەوە (invoice: 1434, کۆمپانیا: سۆران, مادە: چەو)','2025-08-04 15:11:48',0,'{\"company_id\":2,\"company_name\":\"سۆران\",\"driver\":\"سۆران\",\"location\":\"کارگە و چەو و لمی عطا\",\"material_id\":2,\"material_name\":\"چەو\",\"amount_iqd\":\"177527.35\",\"kg\":\"30435.00\",\"price\":\"0.00\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139450\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0.00\",\"remaining_iqd\":\"177000\",\"bin_id\":4,\"price_per_kg_iqd\":\"5833.00\",\"price_per_kg_usd\":\"0.00\",\"invoice_number\":\"1434\",\"date\":\"2025-08-03\"}',NULL,'{\"action_type\":\"purchase_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":177000}','130.193.209.66'),(124,2,'delete','purchases',33,'کڕینەکە سڕایەوە (invoice: 1107, کۆمپانیا: دەشتی, مادە: لمی ڕەش)','2025-08-04 15:11:49',0,'{\"company_id\":1,\"company_name\":\"دەشتی\",\"driver\":\"داستان علی\",\"location\":\"کەنجاڕە\",\"material_id\":1,\"material_name\":\"لمی ڕەش\",\"amount_iqd\":\"350755.00\",\"kg\":\"34220.00\",\"price\":\"0.00\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139450\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0.00\",\"remaining_iqd\":\"350000\",\"bin_id\":1,\"price_per_kg_iqd\":\"10250.00\",\"price_per_kg_usd\":\"0.00\",\"invoice_number\":\"1107\",\"date\":\"2025-08-03\"}',NULL,'{\"action_type\":\"purchase_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":350000}','130.193.209.66'),(125,2,'delete','purchases',6,'کڕینەکە سڕایەوە (invoice: 503689, کۆمپانیا: مامە کاوە, مادە: چیمەنتۆ)','2025-08-04 15:11:51',0,'{\"company_id\":3,\"company_name\":\"مامە کاوە\",\"driver\":\"ئاوات\",\"location\":\"لاڤارج\",\"material_id\":3,\"material_name\":\"چیمەنتۆ\",\"amount_iqd\":\"0.00\",\"kg\":\"31050.00\",\"price\":\"2002.73\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139450\",\"type\":\"دۆلار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"2002.73\",\"remaining_iqd\":\"0\",\"bin_id\":5,\"price_per_kg_iqd\":\"0.00\",\"price_per_kg_usd\":\"64.50\",\"invoice_number\":\"503689\",\"date\":\"2025-08-02\"}',NULL,'{\"action_type\":\"purchase_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":2002.73}','130.193.209.66'),(126,2,'delete','purchases',7,'کڕینەکە سڕایەوە (invoice: 82515723, کۆمپانیا: حاجی فاروق چیمەنتۆ, مادە: چیمەنتۆ)','2025-08-04 15:11:53',0,'{\"company_id\":4,\"company_name\":\"حاجی فاروق چیمەنتۆ\",\"driver\":\"دیلان  کمال\",\"location\":\"ماس\",\"material_id\":3,\"material_name\":\"چیمەنتۆ\",\"amount_iqd\":\"0.00\",\"kg\":\"31600.00\",\"price\":\"2006.60\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139450\",\"type\":\"دۆلار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"2006.60\",\"remaining_iqd\":\"0\",\"bin_id\":6,\"price_per_kg_iqd\":\"0.00\",\"price_per_kg_usd\":\"63.50\",\"invoice_number\":\"82515723\",\"date\":\"2025-08-02\"}',NULL,'{\"action_type\":\"purchase_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":2006.6}','130.193.209.66'),(127,2,'delete','purchases',8,'کڕینەکە سڕایەوە (invoice: 82515808, کۆمپانیا: حاجی فاروق چیمەنتۆ, مادە: چیمەنتۆ)','2025-08-04 15:11:55',0,'{\"company_id\":4,\"company_name\":\"حاجی فاروق چیمەنتۆ\",\"driver\":\"دیلان  کمال\",\"location\":\"ماس\",\"material_id\":3,\"material_name\":\"چیمەنتۆ\",\"amount_iqd\":\"0.00\",\"kg\":\"31680.00\",\"price\":\"2011.68\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139450\",\"type\":\"دۆلار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"2011.68\",\"remaining_iqd\":\"0\",\"bin_id\":6,\"price_per_kg_iqd\":\"0.00\",\"price_per_kg_usd\":\"63.50\",\"invoice_number\":\"82515808\",\"date\":\"2025-08-02\"}',NULL,'{\"action_type\":\"purchase_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":2011.68}','130.193.209.66'),(128,2,'delete','purchases',27,'کڕینەکە سڕایەوە (invoice: 1103, کۆمپانیا: دەشتی, مادە: لمی ڕەش)','2025-08-04 15:11:56',0,'{\"company_id\":1,\"company_name\":\"دەشتی\",\"driver\":\"داستان علی\",\"location\":\"کەنجاڕە\",\"material_id\":1,\"material_name\":\"لمی ڕەش\",\"amount_iqd\":\"345220.00\",\"kg\":\"33680.00\",\"price\":\"0.00\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139450\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0.00\",\"remaining_iqd\":\"345000\",\"bin_id\":1,\"price_per_kg_iqd\":\"10250.00\",\"price_per_kg_usd\":\"0.00\",\"invoice_number\":\"1103\",\"date\":\"2025-08-02\"}',NULL,'{\"action_type\":\"purchase_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":345000}','130.193.209.66'),(129,2,'delete','purchases',28,'کڕینەکە سڕایەوە (invoice: 1105, کۆمپانیا: دەشتی, مادە: لمی ڕەش)','2025-08-04 15:11:59',0,'{\"company_id\":1,\"company_name\":\"دەشتی\",\"driver\":\"کاک دەشتی\",\"location\":\"کەنجاڕە\",\"material_id\":1,\"material_name\":\"لمی ڕەش\",\"amount_iqd\":\"361620.00\",\"kg\":\"35280.00\",\"price\":\"0.00\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139450\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0.00\",\"remaining_iqd\":\"361000\",\"bin_id\":1,\"price_per_kg_iqd\":\"10250.00\",\"price_per_kg_usd\":\"0.00\",\"invoice_number\":\"1105\",\"date\":\"2025-08-02\"}',NULL,'{\"action_type\":\"purchase_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":361000}','130.193.209.66'),(130,2,'delete','purchases',31,'کڕینەکە سڕایەوە (invoice: 1434, کۆمپانیا: سۆران, مادە: چەو)','2025-08-04 15:12:01',0,'{\"company_id\":2,\"company_name\":\"سۆران\",\"driver\":\"سۆران\",\"location\":\"کارگە و چەو و لمی عطا\",\"material_id\":2,\"material_name\":\"چەو\",\"amount_iqd\":\"177527.35\",\"kg\":\"30435.00\",\"price\":\"0.00\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139450\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0.00\",\"remaining_iqd\":\"177000\",\"bin_id\":4,\"price_per_kg_iqd\":\"5833.00\",\"price_per_kg_usd\":\"0.00\",\"invoice_number\":\"1434\",\"date\":\"2025-08-02\"}',NULL,'{\"action_type\":\"purchase_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":177000}','130.193.209.66'),(131,2,'delete','purchases',36,'کڕینەکە سڕایەوە (invoice: 1426, کۆمپانیا: سۆران, مادە: چەو)','2025-08-04 15:12:03',0,'{\"company_id\":2,\"company_name\":\"سۆران\",\"driver\":\"سۆران\",\"location\":\"کارگە و چەو و لمی عطا\",\"material_id\":2,\"material_name\":\"چەو\",\"amount_iqd\":\"176885.73\",\"kg\":\"30325.00\",\"price\":\"0.00\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139450\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0.00\",\"remaining_iqd\":\"176000\",\"bin_id\":4,\"price_per_kg_iqd\":\"5833.00\",\"price_per_kg_usd\":\"0.00\",\"invoice_number\":\"1426\",\"date\":\"2025-08-02\"}',NULL,'{\"action_type\":\"purchase_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":176000}','130.193.209.66'),(132,2,'delete','purchases',4,'کڕینەکە سڕایەوە (invoice: 503555, کۆمپانیا: مامە کاوە, مادە: چیمەنتۆ)','2025-08-04 15:12:05',0,'{\"company_id\":3,\"company_name\":\"مامە کاوە\",\"driver\":\"ڕامیار جلال\",\"location\":\"لاڤارج\",\"material_id\":3,\"material_name\":\"چیمەنتۆ\",\"amount_iqd\":\"0.00\",\"kg\":\"31050.00\",\"price\":\"2002.73\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139450\",\"type\":\"دۆلار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"2002.73\",\"remaining_iqd\":\"0\",\"bin_id\":5,\"price_per_kg_iqd\":\"0.00\",\"price_per_kg_usd\":\"64.50\",\"invoice_number\":\"503555\",\"date\":\"2025-08-01\"}',NULL,'{\"action_type\":\"purchase_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":2002.73}','130.193.209.66'),(133,2,'delete','purchases',5,'کڕینەکە سڕایەوە (invoice: 503562, کۆمپانیا: مامە کاوە, مادە: چیمەنتۆ)','2025-08-04 15:12:07',0,'{\"company_id\":3,\"company_name\":\"مامە کاوە\",\"driver\":\"ئاوات\",\"location\":\"لاڤارج\",\"material_id\":3,\"material_name\":\"چیمەنتۆ\",\"amount_iqd\":\"0.00\",\"kg\":\"31150.00\",\"price\":\"2009.17\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139450\",\"type\":\"دۆلار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"2009.17\",\"remaining_iqd\":\"0\",\"bin_id\":5,\"price_per_kg_iqd\":\"0.00\",\"price_per_kg_usd\":\"64.50\",\"invoice_number\":\"503562\",\"date\":\"2025-08-01\"}',NULL,'{\"action_type\":\"purchase_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":2009.17}','130.193.209.66'),(134,2,'insert','purchases',41,'کڕینێکی نوێ زیادکرا (invoice: 50355, کۆمپانیا: مامە کاوە, مادە: چیمەنتۆ, بڕ: 31050 کگم)','2025-08-04 17:36:12',0,NULL,'{\"company_id\":\"3\",\"company_name\":\"مامە کاوە\",\"driver\":\"ڕامیار جلال\",\"location\":\"لاڤارج\",\"material_id\":\"3\",\"material_name\":\"چیمەنتۆ\",\"amount_iqd\":\"0\",\"kg\":\"31050\",\"price\":\"2002.73\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دۆلار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"2002.73\",\"remaining_iqd\":\"0\",\"bin_id\":\"5\",\"price_per_kg_iqd\":\"0\",\"price_per_kg_usd\":\"64.5\",\"invoice_number\":\"50355\",\"date\":\"2025-08-01\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":2002.73}','130.193.207.162'),(135,2,'insert','purchases',42,'کڕینێکی نوێ زیادکرا (invoice: 503562, کۆمپانیا: مامە کاوە, مادە: چیمەنتۆ, بڕ: 31150 کگم)','2025-08-04 17:37:37',0,NULL,'{\"company_id\":\"3\",\"company_name\":\"مامە کاوە\",\"driver\":\"ئاوات\",\"location\":\"لاڤارج\",\"material_id\":\"3\",\"material_name\":\"چیمەنتۆ\",\"amount_iqd\":\"0\",\"kg\":\"31150\",\"price\":\"2009.17\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دۆلار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"2009.17\",\"remaining_iqd\":\"0\",\"bin_id\":\"5\",\"price_per_kg_iqd\":\"0\",\"price_per_kg_usd\":\"64.5\",\"invoice_number\":\"503562\",\"date\":\"2025-08-01\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":2009.17}','130.193.207.162'),(136,2,'insert','purchases',43,'کڕینێکی نوێ زیادکرا (invoice: 503689, کۆمپانیا: مامە کاوە, مادە: چیمەنتۆ, بڕ: 31050 کگم)','2025-08-04 17:38:35',0,NULL,'{\"company_id\":\"3\",\"company_name\":\"مامە کاوە\",\"driver\":\"ئاوات\",\"location\":\"لاڤارج\",\"material_id\":\"3\",\"material_name\":\"چیمەنتۆ\",\"amount_iqd\":\"0\",\"kg\":\"31050\",\"price\":\"2002.73\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دۆلار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"2002.73\",\"remaining_iqd\":\"0\",\"bin_id\":\"5\",\"price_per_kg_iqd\":\"0\",\"price_per_kg_usd\":\"64.5\",\"invoice_number\":\"503689\",\"date\":\"2025-08-02\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":2002.73}','130.193.207.162'),(137,2,'insert','purchases',44,'کڕینێکی نوێ زیادکرا (invoice: 82515723, کۆمپانیا: حاجی فاروق چیمەنتۆ, مادە: چیمەنتۆ, بڕ: 31600 کگم)','2025-08-04 17:39:47',0,NULL,'{\"company_id\":\"4\",\"company_name\":\"حاجی فاروق چیمەنتۆ\",\"driver\":\"دیلان  کمال\",\"location\":\"ماس\",\"material_id\":\"3\",\"material_name\":\"چیمەنتۆ\",\"amount_iqd\":\"0\",\"kg\":\"31600\",\"price\":\"2006.60\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دۆلار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"2006.60\",\"remaining_iqd\":\"0\",\"bin_id\":\"6\",\"price_per_kg_iqd\":\"0\",\"price_per_kg_usd\":\"63.5\",\"invoice_number\":\"82515723\",\"date\":\"2025-08-02\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":2006.6}','130.193.207.162'),(138,2,'insert','purchases',45,'کڕینێکی نوێ زیادکرا (invoice: 82515808, کۆمپانیا: حاجی فاروق چیمەنتۆ, مادە: چیمەنتۆ, بڕ: 31680 کگم)','2025-08-04 17:42:11',0,NULL,'{\"company_id\":\"4\",\"company_name\":\"حاجی فاروق چیمەنتۆ\",\"driver\":\"دیلان  کمال\",\"location\":\"ماس\",\"material_id\":\"3\",\"material_name\":\"چیمەنتۆ\",\"amount_iqd\":\"0\",\"kg\":\"31680\",\"price\":\"2011.68\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دۆلار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"2011.68\",\"remaining_iqd\":\"0\",\"bin_id\":\"6\",\"price_per_kg_iqd\":\"0\",\"price_per_kg_usd\":\"63.5\",\"invoice_number\":\"82515808\",\"date\":\"2025-08-02\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":2011.68}','130.193.207.162'),(139,2,'insert','purchases',46,'کڕینێکی نوێ زیادکرا (invoice: 503902, کۆمپانیا: مامە کاوە, مادە: چیمەنتۆ, بڕ: 31100 کگم)','2025-08-04 17:43:57',0,NULL,'{\"company_id\":\"3\",\"company_name\":\"مامە کاوە\",\"driver\":\"ڕامیار جلال\",\"location\":\"لاڤارج\",\"material_id\":\"3\",\"material_name\":\"چیمەنتۆ\",\"amount_iqd\":\"0\",\"kg\":\"31100\",\"price\":\"2005.95\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دۆلار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"2005.95\",\"remaining_iqd\":\"0\",\"bin_id\":\"5\",\"price_per_kg_iqd\":\"0\",\"price_per_kg_usd\":\"64.5\",\"invoice_number\":\"503902\",\"date\":\"2025-08-03\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":2005.95}','130.193.207.162'),(140,2,'insert','purchases',47,'کڕینێکی نوێ زیادکرا (invoice: 83516201, کۆمپانیا: حاجی فاروق چیمەنتۆ, مادە: چیمەنتۆ, بڕ: 39420 کگم)','2025-08-04 17:45:31',0,NULL,'{\"company_id\":\"4\",\"company_name\":\"حاجی فاروق چیمەنتۆ\",\"driver\":\"کارزان کمال\",\"location\":\"ماس\",\"material_id\":\"3\",\"material_name\":\"چیمەنتۆ\",\"amount_iqd\":\"0\",\"kg\":\"39420\",\"price\":\"2503.17\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دۆلار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"2503.17\",\"remaining_iqd\":\"0\",\"bin_id\":\"6\",\"price_per_kg_iqd\":\"0\",\"price_per_kg_usd\":\"63.5\",\"invoice_number\":\"83516201\",\"date\":\"2025-08-03\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":2503.17}','130.193.207.162'),(141,2,'insert','purchases',48,'کڕینێکی نوێ زیادکرا (invoice: 83516262, کۆمپانیا: حاجی فاروق چیمەنتۆ, مادە: چیمەنتۆ, بڕ: 39280 کگم)','2025-08-04 17:47:10',0,NULL,'{\"company_id\":\"4\",\"company_name\":\"حاجی فاروق چیمەنتۆ\",\"driver\":\"کارزان کمال\",\"location\":\"ماس\",\"material_id\":\"3\",\"material_name\":\"چیمەنتۆ\",\"amount_iqd\":\"0\",\"kg\":\"39280\",\"price\":\"2494.28\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دۆلار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"2494.28\",\"remaining_iqd\":\"0\",\"bin_id\":\"6\",\"price_per_kg_iqd\":\"0\",\"price_per_kg_usd\":\"63.5\",\"invoice_number\":\"83516262\",\"date\":\"2025-08-03\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":2494.28}','130.193.207.162'),(142,2,'insert','purchases',49,'کڕینێکی نوێ زیادکرا (invoice: -, کۆمپانیا: کەسارەکەی خۆمان, مادە: چەو, بڕ: 36400 کگم)','2025-08-04 18:02:06',0,NULL,'{\"company_id\":\"12\",\"company_name\":\"کەسارەکەی خۆمان\",\"driver\":\"کاک عیماد شۆفێر\",\"location\":\"کەسارەکەی خۆمان\",\"material_id\":\"2\",\"material_name\":\"چەو\",\"amount_iqd\":\"212321.20\",\"kg\":\"36400\",\"price\":\"0\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0\",\"remaining_iqd\":\"212000\",\"bin_id\":\"4\",\"price_per_kg_iqd\":\"5833\",\"price_per_kg_usd\":\"0\",\"invoice_number\":\"-\",\"date\":\"2025-08-03\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":212000}','130.193.207.162'),(143,2,'insert','purchases',50,'کڕینێکی نوێ زیادکرا (invoice: 2, کۆمپانیا: کەسارەکەی خۆمان, مادە: چەو, بڕ: 36400 کگم)','2025-08-04 18:03:41',0,NULL,'{\"company_id\":\"12\",\"company_name\":\"کەسارەکەی خۆمان\",\"driver\":\"کاک عیماد شۆفێر\",\"location\":\"کەسارەکەی خۆمان\",\"material_id\":\"2\",\"material_name\":\"چەو\",\"amount_iqd\":\"212321.20\",\"kg\":\"36400\",\"price\":\"0\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0\",\"remaining_iqd\":\"212000\",\"bin_id\":\"4\",\"price_per_kg_iqd\":\"5833\",\"price_per_kg_usd\":\"0\",\"invoice_number\":\"2\",\"date\":\"2025-08-02\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":212000}','130.193.207.162'),(144,2,'delete','purchases',49,'کڕینەکە سڕایەوە (invoice: -, کۆمپانیا: کەسارەکەی خۆمان, مادە: چەو)','2025-08-04 18:05:06',0,'{\"company_id\":12,\"company_name\":\"کەسارەکەی خۆمان\",\"driver\":\"کاک عیماد شۆفێر\",\"location\":\"کەسارەکەی خۆمان\",\"material_id\":2,\"material_name\":\"چەو\",\"amount_iqd\":\"212321.20\",\"kg\":\"36400.00\",\"price\":\"0.00\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0.00\",\"remaining_iqd\":\"212000\",\"bin_id\":4,\"price_per_kg_iqd\":\"5833.00\",\"price_per_kg_usd\":\"0.00\",\"invoice_number\":\"-\",\"date\":\"2025-08-03\"}',NULL,'{\"action_type\":\"purchase_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":212000}','130.193.207.162'),(145,2,'insert','purchases',51,'کڕینێکی نوێ زیادکرا (invoice: 1, کۆمپانیا: کەسارەکەی خۆمان, مادە: چەو, بڕ: 36400 کگم)','2025-08-04 18:05:54',0,NULL,'{\"company_id\":\"12\",\"company_name\":\"کەسارەکەی خۆمان\",\"driver\":\"کاک عیماد شۆفێر\",\"location\":\"کەسارەکەی خۆمان\",\"material_id\":\"2\",\"material_name\":\"چەو\",\"amount_iqd\":\"212321.20\",\"kg\":\"36400\",\"price\":\"0\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0\",\"remaining_iqd\":\"212000\",\"bin_id\":\"4\",\"price_per_kg_iqd\":\"5833\",\"price_per_kg_usd\":\"0\",\"invoice_number\":\"1\",\"date\":\"2025-08-02\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":212000}','130.193.207.162'),(146,2,'insert','purchases',52,'کڕینێکی نوێ زیادکرا (invoice: 1426, کۆمپانیا: سۆران, مادە: چەو, بڕ: 30325 کگم)','2025-08-04 18:08:17',0,NULL,'{\"company_id\":\"2\",\"company_name\":\"سۆران\",\"driver\":\"سۆران\",\"location\":\"کارگە و چەو و لمی عطا\",\"material_id\":\"2\",\"material_name\":\"چەو\",\"amount_iqd\":\"176885.73\",\"kg\":\"30325\",\"price\":\"0\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0\",\"remaining_iqd\":\"176000\",\"bin_id\":\"3\",\"price_per_kg_iqd\":\"5833\",\"price_per_kg_usd\":\"0\",\"invoice_number\":\"1426\",\"date\":\"2025-08-02\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":176000}','130.193.207.162'),(147,2,'insert','purchases',53,'کڕینێکی نوێ زیادکرا (invoice: 1105, کۆمپانیا: دەشتی, مادە: لمی ڕەش, بڕ: 35280 کگم)','2025-08-04 18:10:14',0,NULL,'{\"company_id\":\"1\",\"company_name\":\"دەشتی\",\"driver\":\"کاک دەشتی\",\"location\":\"کەنجاڕە\",\"material_id\":\"1\",\"material_name\":\"لمی ڕەش\",\"amount_iqd\":\"361620.00\",\"kg\":\"35280\",\"price\":\"0\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0\",\"remaining_iqd\":\"361000\",\"bin_id\":\"2\",\"price_per_kg_iqd\":\"10250\",\"price_per_kg_usd\":\"0\",\"invoice_number\":\"1105\",\"date\":\"2025-08-02\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":361000}','130.193.207.162'),(148,2,'insert','purchases',54,'کڕینێکی نوێ زیادکرا (invoice: 1103, کۆمپانیا: دەشتی, مادە: لمی ڕەش, بڕ: 33680 کگم)','2025-08-04 18:11:47',0,NULL,'{\"company_id\":\"1\",\"company_name\":\"دەشتی\",\"driver\":\"داستان علی\",\"location\":\"کەنجاڕە\",\"material_id\":\"1\",\"material_name\":\"لمی ڕەش\",\"amount_iqd\":\"345220.00\",\"kg\":\"33680\",\"price\":\"0\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0\",\"remaining_iqd\":\"345000\",\"bin_id\":\"2\",\"price_per_kg_iqd\":\"10250\",\"price_per_kg_usd\":\"0\",\"invoice_number\":\"1103\",\"date\":\"2025-08-02\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":345000}','130.193.207.162'),(149,2,'insert','purchases',55,'کڕینێکی نوێ زیادکرا (invoice: 5118, کۆمپانیا: تڕێلەکەی خۆمان, مادە: چەو, بڕ: 32580 کگم)','2025-08-04 18:18:33',0,NULL,'{\"company_id\":\"6\",\"company_name\":\"تڕێلەکەی خۆمان\",\"driver\":\"کاک عیماد شۆفێر\",\"location\":\"غەسالەی نەورۆز\",\"material_id\":\"2\",\"material_name\":\"چەو\",\"amount_iqd\":\"190039.14\",\"kg\":\"32580\",\"price\":\"0\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0\",\"remaining_iqd\":\"190000\",\"bin_id\":\"3\",\"price_per_kg_iqd\":\"5833\",\"price_per_kg_usd\":\"0\",\"invoice_number\":\"5118\",\"date\":\"2025-08-03\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":190000}','130.193.207.162'),(150,2,'insert','purchases',56,'کڕینێکی نوێ زیادکرا (invoice: 1434, کۆمپانیا: سۆران, مادە: چەو, بڕ: 30435 کگم)','2025-08-04 18:22:07',0,NULL,'{\"company_id\":\"2\",\"company_name\":\"سۆران\",\"driver\":\"سۆران\",\"location\":\"کارگە و چەو و لمی عطا\",\"material_id\":\"2\",\"material_name\":\"چەو\",\"amount_iqd\":\"177527.35\",\"kg\":\"30435\",\"price\":\"0\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0\",\"remaining_iqd\":\"177000\",\"bin_id\":\"3\",\"price_per_kg_iqd\":\"5833\",\"price_per_kg_usd\":\"0\",\"invoice_number\":\"1434\",\"date\":\"2025-08-03\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":177000}','130.193.207.162'),(151,2,'insert','purchases',57,'کڕینێکی نوێ زیادکرا (invoice: 1107, کۆمپانیا: دەشتی, مادە: لمی ڕەش, بڕ: 34220 کگم)','2025-08-04 18:24:46',0,NULL,'{\"company_id\":\"1\",\"company_name\":\"دەشتی\",\"driver\":\"داستان علی\",\"location\":\"کەنجاڕە\",\"material_id\":\"1\",\"material_name\":\"لمی ڕەش\",\"amount_iqd\":\"350755.00\",\"kg\":\"34220\",\"price\":\"0\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0\",\"remaining_iqd\":\"350000\",\"bin_id\":\"2\",\"price_per_kg_iqd\":\"10250\",\"price_per_kg_usd\":\"0\",\"invoice_number\":\"1107\",\"date\":\"2025-08-03\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":350000}','130.193.207.162'),(152,2,'insert','purchases',58,'کڕینێکی نوێ زیادکرا (invoice: 1108, کۆمپانیا: دەشتی, مادە: لمی ڕەش, بڕ: 33950 کگم)','2025-08-04 18:29:36',0,NULL,'{\"company_id\":\"1\",\"company_name\":\"دەشتی\",\"driver\":\"داستان علی\",\"location\":\"کەنجاڕە\",\"material_id\":\"1\",\"material_name\":\"لمی ڕەش\",\"amount_iqd\":\"347987.50\",\"kg\":\"33950\",\"price\":\"0\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0\",\"remaining_iqd\":\"347000\",\"bin_id\":\"2\",\"price_per_kg_iqd\":\"10250\",\"price_per_kg_usd\":\"0\",\"invoice_number\":\"1108\",\"date\":\"2025-08-03\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":347000}','130.193.207.162'),(153,2,'insert','purchases',59,'کڕینێکی نوێ زیادکرا (invoice: 1109, کۆمپانیا: دەشتی, مادە: لمی ڕەش, بڕ: 33320 کگم)','2025-08-04 18:31:35',0,NULL,'{\"company_id\":\"1\",\"company_name\":\"دەشتی\",\"driver\":\"کاک دەشتی\",\"location\":\"کەنجاڕە\",\"material_id\":\"1\",\"material_name\":\"لمی ڕەش\",\"amount_iqd\":\"341530.00\",\"kg\":\"33320\",\"price\":\"0\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0\",\"remaining_iqd\":\"341000\",\"bin_id\":\"2\",\"price_per_kg_iqd\":\"10250\",\"price_per_kg_usd\":\"0\",\"invoice_number\":\"1109\",\"date\":\"2025-08-03\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":341000}','130.193.207.162'),(154,2,'insert','purchases',60,'کڕینێکی نوێ زیادکرا (invoice: 3, کۆمپانیا: کەسارەکەی خۆمان, مادە: لمی کەسارە, بڕ: 42000 کگم)','2025-08-04 18:33:27',0,NULL,'{\"company_id\":\"12\",\"company_name\":\"کەسارەکەی خۆمان\",\"driver\":\"کاک عیماد شۆفێر\",\"location\":\"کەسارەکەی خۆمان\",\"material_id\":\"5\",\"material_name\":\"لمی کەسارە\",\"amount_iqd\":\"420000.00\",\"kg\":\"42000\",\"price\":\"0\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0\",\"remaining_iqd\":\"420000.00\",\"bin_id\":\"1\",\"price_per_kg_iqd\":\"10000\",\"price_per_kg_usd\":\"0\",\"invoice_number\":\"3\",\"date\":\"2025-08-03\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":420000}','130.193.207.162'),(155,2,'insert','purchases',61,'کڕینێکی نوێ زیادکرا (invoice: 4, کۆمپانیا: کەسارەکەی خۆمان, مادە: لمی کەسارە, بڕ: 42000 کگم)','2025-08-04 18:35:46',0,NULL,'{\"company_id\":\"12\",\"company_name\":\"کەسارەکەی خۆمان\",\"driver\":\"کاک عیماد شۆفێر\",\"location\":\"کەسارەکەی خۆمان\",\"material_id\":\"5\",\"material_name\":\"لمی کەسارە\",\"amount_iqd\":\"420000.00\",\"kg\":\"42000\",\"price\":\"0\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0\",\"remaining_iqd\":\"420000.00\",\"bin_id\":\"1\",\"price_per_kg_iqd\":\"10000\",\"price_per_kg_usd\":\"0\",\"invoice_number\":\"4\",\"date\":\"2025-08-03\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":420000}','130.193.207.162'),(156,2,'insert','purchases',62,'کڕینێکی نوێ زیادکرا (invoice: 1110, کۆمپانیا: دەشتی, مادە: لمی ڕەش, بڕ: 34220 کگم)','2025-08-04 18:37:04',0,NULL,'{\"company_id\":\"1\",\"company_name\":\"دەشتی\",\"driver\":\"داستان علی\",\"location\":\"کەنجاڕە\",\"material_id\":\"1\",\"material_name\":\"لمی ڕەش\",\"amount_iqd\":\"350755.00\",\"kg\":\"34220\",\"price\":\"0\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0\",\"remaining_iqd\":\"350000\",\"bin_id\":\"2\",\"price_per_kg_iqd\":\"10250\",\"price_per_kg_usd\":\"0\",\"invoice_number\":\"1110\",\"date\":\"2025-08-04\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":350000}','130.193.207.162'),(157,2,'insert','purchases',63,'کڕینێکی نوێ زیادکرا (invoice: 1111, کۆمپانیا: دەشتی, مادە: لمی ڕەش, بڕ: 30480 کگم)','2025-08-04 18:38:15',0,NULL,'{\"company_id\":\"1\",\"company_name\":\"دەشتی\",\"driver\":\"داستان علی\",\"location\":\"کەنجاڕە\",\"material_id\":\"1\",\"material_name\":\"لمی ڕەش\",\"amount_iqd\":\"312420.00\",\"kg\":\"30480\",\"price\":\"0\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0\",\"remaining_iqd\":\"312000\",\"bin_id\":\"2\",\"price_per_kg_iqd\":\"10250\",\"price_per_kg_usd\":\"0\",\"invoice_number\":\"1111\",\"date\":\"2025-08-04\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":312000}','130.193.207.162'),(158,2,'insert','purchases',64,'کڕینێکی نوێ زیادکرا (invoice: 1112, کۆمپانیا: دەشتی, مادە: لمی ڕەش, بڕ: 32200 کگم)','2025-08-04 18:40:28',0,NULL,'{\"company_id\":\"1\",\"company_name\":\"دەشتی\",\"driver\":\"محمد علی\",\"location\":\"کەنجاڕە\",\"material_id\":\"1\",\"material_name\":\"لمی ڕەش\",\"amount_iqd\":\"330050.00\",\"kg\":\"32200\",\"price\":\"0\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0\",\"remaining_iqd\":\"330000\",\"bin_id\":\"2\",\"price_per_kg_iqd\":\"10250\",\"price_per_kg_usd\":\"0\",\"invoice_number\":\"1112\",\"date\":\"2025-08-04\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":330000}','130.193.207.162'),(159,2,'insert','purchases',65,'کڕینێکی نوێ زیادکرا (invoice: 1437, کۆمپانیا: سۆران, مادە: چەو, بڕ: 30225 کگم)','2025-08-04 18:42:14',0,NULL,'{\"company_id\":\"2\",\"company_name\":\"سۆران\",\"driver\":\"سۆران\",\"location\":\"کارگە و چەو و لمی عطا\",\"material_id\":\"2\",\"material_name\":\"چەو\",\"amount_iqd\":\"176302.43\",\"kg\":\"30225\",\"price\":\"0\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0\",\"remaining_iqd\":\"176000\",\"bin_id\":\"3\",\"price_per_kg_iqd\":\"5833\",\"price_per_kg_usd\":\"0\",\"invoice_number\":\"1437\",\"date\":\"2025-08-04\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":176000}','130.193.207.162'),(160,2,'insert','purchases',66,'کڕینێکی نوێ زیادکرا (invoice: 5144, کۆمپانیا: تڕێلەکەی خۆمان, مادە: چەو, بڕ: 32500 کگم)','2025-08-04 18:44:44',0,NULL,'{\"company_id\":\"6\",\"company_name\":\"تڕێلەکەی خۆمان\",\"driver\":\"کاک عیماد شۆفێر\",\"location\":\"غەسالەی نەورۆز\",\"material_id\":\"2\",\"material_name\":\"چەو\",\"amount_iqd\":\"189572.50\",\"kg\":\"32500\",\"price\":\"0\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0\",\"remaining_iqd\":\"189000\",\"bin_id\":\"3\",\"price_per_kg_iqd\":\"5833\",\"price_per_kg_usd\":\"0\",\"invoice_number\":\"5144\",\"date\":\"2025-08-04\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":189000}','130.193.207.162'),(161,2,'insert','purchases',67,'کڕینێکی نوێ زیادکرا (invoice: 5, کۆمپانیا: کەسارەکەی خۆمان, مادە: لمی کەسارە, بڕ: 42000 کگم)','2025-08-04 18:46:15',0,NULL,'{\"company_id\":\"12\",\"company_name\":\"کەسارەکەی خۆمان\",\"driver\":\"کاک عیماد شۆفێر\",\"location\":\"کەسارەکەی خۆمان\",\"material_id\":\"5\",\"material_name\":\"لمی کەسارە\",\"amount_iqd\":\"420000.00\",\"kg\":\"42000\",\"price\":\"0\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0\",\"remaining_iqd\":\"420000.00\",\"bin_id\":\"1\",\"price_per_kg_iqd\":\"10000\",\"price_per_kg_usd\":\"0\",\"invoice_number\":\"5\",\"date\":\"2025-08-04\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":420000}','130.193.207.162'),(162,2,'insert','purchases',68,'کڕینێکی نوێ زیادکرا (invoice: 6, کۆمپانیا: کەسارەکەی خۆمان, مادە: لمی کەسارە, بڕ: 42000 کگم)','2025-08-04 18:49:10',0,NULL,'{\"company_id\":\"12\",\"company_name\":\"کەسارەکەی خۆمان\",\"driver\":\"کاک عیماد شۆفێر\",\"location\":\"کەسارەکەی خۆمان\",\"material_id\":\"5\",\"material_name\":\"لمی کەسارە\",\"amount_iqd\":\"420000.00\",\"kg\":\"42000\",\"price\":\"0\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0\",\"remaining_iqd\":\"420000.00\",\"bin_id\":\"1\",\"price_per_kg_iqd\":\"10000\",\"price_per_kg_usd\":\"0\",\"invoice_number\":\"6\",\"date\":\"2025-08-04\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":420000}','130.193.207.162'),(163,2,'insert','sales',25,'فرۆشتنێکی نوێ زیادکرا (invoice: A-0001, A-0002, A-0003, A-0004, A-0005, A-0006, کڕیار: و.قالە, فۆرمۆلا: ARZY 25 MEGA, بڕ: 60 م³)','2025-08-04 19:00:47',0,NULL,'{\"customer_id\":\"37\",\"customer_name\":\"و.قالە\",\"formula_id\":\"27\",\"formula_name\":\"ARZY 25 MEGA\",\"recipient\":\"وەستا قالە\",\"location\":\"ڕاپەڕین\",\"quantity\":\"60\",\"price_per_unit\":\"45\",\"total_price\":\"2700.0000\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0\",\"amount_paid_iq\":\"0\",\"remaining_amount\":\"2700.0000\",\"dolar_rate\":\"139400\",\"discount\":\"0\",\"order_date\":\"2025-08-02\",\"invoice_number\":\"A-0001, A-0002, A-0003, A-0004, A-0005, A-0006\",\"notes\":\"\"}','{\"action_type\":\"sale_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0}','130.193.207.162'),(164,2,'delete','sales',25,'فرۆشتنەکە سڕایەوە (invoice: A-0001, A-0002, A-0003, A-0004, A-0005, A-0006, کڕیار: و.قالە, فۆرمۆلا: ARZY 25 MEGA)','2025-08-04 19:03:49',0,'{\"customer_id\":37,\"customer_name\":\"و.قالە\",\"recipient\":\"وەستا قالە\",\"location\":\"ڕاپەڕین\",\"quantity\":\"60.00\",\"price_per_unit\":\"45.00\",\"total_price\":\"2700.00\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0.00\",\"amount_paid_iq\":\"0.00\",\"dolar_rate\":\"139400.00\",\"remaining_amount\":\"2700.00\",\"invoice_number\":\"A-0001, A-0002, A-0003, A-0004, A-0005, A-0006\",\"order_date\":\"2025-08-02\",\"notes\":\"\",\"formula_id\":27,\"formula_name\":\"ARZY 25 MEGA\",\"discount\":\"0.00\"}',NULL,'{\"action_type\":\"sale_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":\"2700.00\"}','130.193.207.162'),(165,2,'delete','sales',26,'فرۆشتنەکە سڕایەوە (invoice: A-0001, A-0002, A-0003, A-0004, A-0005, A-0006, کڕیار: و.قالە, فۆرمۆلا: ARZY 25 MEGA)','2025-08-04 19:06:20',0,'{\"customer_id\":37,\"customer_name\":\"و.قالە\",\"recipient\":\"وەستا قالە\",\"location\":\"ڕاپەڕین\",\"quantity\":\"60.00\",\"price_per_unit\":\"45.00\",\"total_price\":\"2700.00\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0.00\",\"amount_paid_iq\":\"0.00\",\"dolar_rate\":\"139400.00\",\"remaining_amount\":\"2700.00\",\"invoice_number\":\"A-0001, A-0002, A-0003, A-0004, A-0005, A-0006\",\"order_date\":\"2025-08-02\",\"notes\":\"\",\"formula_id\":27,\"formula_name\":\"ARZY 25 MEGA\",\"discount\":\"0.00\"}',NULL,'{\"action_type\":\"sale_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":\"2700.00\"}','130.193.207.162'),(166,2,'insert','sales',27,'فرۆشتنێکی نوێ زیادکرا (invoice: A-0001, A-0002, A-0003, A-0004, A-0005, A-0006, کڕیار: و.قالە, فۆرمۆلا: ARZY 25 MEGA, بڕ: 60 م³)','2025-08-04 19:07:05',0,NULL,'{\"customer_id\":\"37\",\"customer_name\":\"و.قالە\",\"formula_id\":\"27\",\"formula_name\":\"ARZY 25 MEGA\",\"recipient\":\"وەستا قالە\",\"location\":\"ڕاپەڕین\",\"quantity\":\"60\",\"price_per_unit\":\"45\",\"total_price\":\"2700.0000\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0\",\"amount_paid_iq\":\"0\",\"remaining_amount\":\"2700.0000\",\"dolar_rate\":\"139400\",\"discount\":\"0\",\"order_date\":\"2025-08-02\",\"invoice_number\":\"A-0001, A-0002, A-0003, A-0004, A-0005, A-0006\",\"notes\":\"\"}','{\"action_type\":\"sale_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0}','130.193.207.162'),(167,2,'delete','sales',27,'فرۆشتنەکە سڕایەوە (invoice: A-0001, A-0002, A-0003, A-0004, A-0005, A-0006, کڕیار: و.قالە, فۆرمۆلا: ARZY 25 MEGA)','2025-08-04 19:07:15',0,'{\"customer_id\":37,\"customer_name\":\"و.قالە\",\"recipient\":\"وەستا قالە\",\"location\":\"ڕاپەڕین\",\"quantity\":\"60.00\",\"price_per_unit\":\"45.00\",\"total_price\":\"2700.00\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0.00\",\"amount_paid_iq\":\"0.00\",\"dolar_rate\":\"139400.00\",\"remaining_amount\":\"2700.00\",\"invoice_number\":\"A-0001, A-0002, A-0003, A-0004, A-0005, A-0006\",\"order_date\":\"2025-08-02\",\"notes\":\"\",\"formula_id\":27,\"formula_name\":\"ARZY 25 MEGA\",\"discount\":\"0.00\"}',NULL,'{\"action_type\":\"sale_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":\"2700.00\"}','130.193.207.162'),(168,2,'insert','sales',28,'فرۆشتنێکی نوێ زیادکرا (invoice: A-0001, A-0002, A-0003, A-0004, A-0005, A-0006, کڕیار: و.قالە, فۆرمۆلا: ARZY 25 MEGA, بڕ: 60 م³)','2025-08-04 19:24:25',0,NULL,'{\"customer_id\":\"37\",\"customer_name\":\"و.قالە\",\"formula_id\":\"27\",\"formula_name\":\"ARZY 25 MEGA\",\"recipient\":\"وەستا قالە\",\"location\":\"ڕاپەڕین\",\"quantity\":\"60\",\"price_per_unit\":\"45\",\"total_price\":\"2700.0000\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0\",\"amount_paid_iq\":\"0\",\"remaining_amount\":\"2700.0000\",\"dolar_rate\":\"139400\",\"discount\":\"0\",\"order_date\":\"2025-08-02\",\"invoice_number\":\"A-0001, A-0002, A-0003, A-0004, A-0005, A-0006\",\"notes\":\"\"}','{\"action_type\":\"sale_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0}','130.193.207.162'),(169,2,'insert','sales',29,'فرۆشتنێکی نوێ زیادکرا (invoice: A-0013, کڕیار: و.قالە, فۆرمۆلا: ARZY 25 MEGA, بڕ: 8 م³)','2025-08-04 19:26:24',0,NULL,'{\"customer_id\":\"37\",\"customer_name\":\"و.قالە\",\"formula_id\":\"27\",\"formula_name\":\"ARZY 25 MEGA\",\"recipient\":\"وەستا قالە\",\"location\":\"ڕاپەڕین\",\"quantity\":\"8\",\"price_per_unit\":\"45\",\"total_price\":\"360.0000\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0\",\"amount_paid_iq\":\"0\",\"remaining_amount\":\"360.0000\",\"dolar_rate\":\"139400\",\"discount\":\"0\",\"order_date\":\"2025-08-02\",\"invoice_number\":\"A-0013\",\"notes\":\"\"}','{\"action_type\":\"sale_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0}','130.193.207.162'),(170,2,'insert','sales',30,'فرۆشتنێکی نوێ زیادکرا (invoice: A-0040, کڕیار: و.قالە, فۆرمۆلا: Chawy lws saqf 350, بڕ: 6 م³)','2025-08-04 19:27:42',0,NULL,'{\"customer_id\":\"37\",\"customer_name\":\"و.قالە\",\"formula_id\":\"42\",\"formula_name\":\"Chawy lws saqf 350\",\"recipient\":\"\",\"location\":\"قه لياسان\",\"quantity\":\"6\",\"price_per_unit\":\"45\",\"total_price\":\"270.0000\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0\",\"amount_paid_iq\":\"0\",\"remaining_amount\":\"270.0000\",\"dolar_rate\":\"139400\",\"discount\":\"0\",\"order_date\":\"2025-08-02\",\"invoice_number\":\"A-0040\",\"notes\":\"\"}','{\"action_type\":\"sale_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0}','130.193.207.162'),(171,2,'insert','sales',31,'فرۆشتنێکی نوێ زیادکرا (invoice: A-0007, A-0008, A-0009, A-0010, A-0011, A-0012, کڕیار: هاوڕێ مجید, فۆرمۆلا: ARZY 25 MEGA, بڕ: 60 م³)','2025-08-05 02:05:59',0,NULL,'{\"customer_id\":\"113\",\"customer_name\":\"هاوڕێ مجید\",\"formula_id\":\"27\",\"formula_name\":\"ARZY 25 MEGA\",\"recipient\":\"هاورێ مجید\",\"location\":\"بەختیاری\",\"quantity\":\"60\",\"price_per_unit\":\"45.0000\",\"total_price\":\"2700.0000\",\"payment_type\":\"نەقد\",\"amount_paid_usd\":\"2700\",\"amount_paid_iq\":\"0\",\"remaining_amount\":\"0.0000\",\"dolar_rate\":\"139400\",\"discount\":\"0\",\"order_date\":\"2025-08-02\",\"invoice_number\":\"A-0007, A-0008, A-0009, A-0010, A-0011, A-0012\",\"notes\":\"3150$واصڵ\"}','{\"action_type\":\"sale_creation\",\"payment_status\":\"paid\",\"currency_used\":\"USD\",\"total_paid\":2700}','130.193.207.162'),(172,2,'insert','sales',32,'فرۆشتنێکی نوێ زیادکرا (invoice: A-0014, کڕیار: هاوڕێ مجید, فۆرمۆلا: ARZY 25 MEGA, بڕ: 10 م³)','2025-08-05 02:06:42',0,NULL,'{\"customer_id\":\"113\",\"customer_name\":\"هاوڕێ مجید\",\"formula_id\":\"27\",\"formula_name\":\"ARZY 25 MEGA\",\"recipient\":\"هاورێ مجید\",\"location\":\"بەختیاری\",\"quantity\":\"10\",\"price_per_unit\":\"45.0000\",\"total_price\":\"450.0000\",\"payment_type\":\"نەقد\",\"amount_paid_usd\":\"450\",\"amount_paid_iq\":\"0\",\"remaining_amount\":\"0.0000\",\"dolar_rate\":\"139400\",\"discount\":\"0\",\"order_date\":\"2025-08-02\",\"invoice_number\":\"A-0014\",\"notes\":\"3150$واصڵ\"}','{\"action_type\":\"sale_creation\",\"payment_status\":\"paid\",\"currency_used\":\"USD\",\"total_paid\":450}','130.193.207.162'),(173,2,'delete','sales',32,'فرۆشتنەکە سڕایەوە (invoice: A-0014, کڕیار: هاوڕێ مجید, فۆرمۆلا: ARZY 25 MEGA)','2025-08-05 02:08:43',0,'{\"customer_id\":113,\"customer_name\":\"هاوڕێ مجید\",\"recipient\":\"هاورێ مجید\",\"location\":\"بەختیاری\",\"quantity\":\"10.00\",\"price_per_unit\":\"45.00\",\"total_price\":\"450.00\",\"payment_type\":\"نەقد\",\"amount_paid_usd\":\"450.00\",\"amount_paid_iq\":\"0.00\",\"dolar_rate\":\"139400.00\",\"remaining_amount\":\"0.00\",\"invoice_number\":\"A-0014\",\"order_date\":\"2025-08-02\",\"notes\":\"3150$واصڵ\",\"formula_id\":27,\"formula_name\":\"ARZY 25 MEGA\",\"discount\":\"0.00\"}',NULL,'{\"action_type\":\"sale_deletion\",\"payment_status\":\"paid\",\"currency_used\":\"USD\",\"total_paid\":450,\"remaining_debt\":\"0.00\"}','130.193.207.162'),(174,2,'insert','sales',33,'فرۆشتنێکی نوێ زیادکرا (invoice: A-0014, کڕیار: هاوڕێ مجید, فۆرمۆلا: ARZY 25 MEGA, بڕ: 10 م³)','2025-08-05 02:11:02',0,NULL,'{\"customer_id\":\"113\",\"customer_name\":\"هاوڕێ مجید\",\"formula_id\":\"27\",\"formula_name\":\"ARZY 25 MEGA\",\"recipient\":\"هاورێ مجید\",\"location\":\"بەختیاری\",\"quantity\":\"10\",\"price_per_unit\":\"45.0000\",\"total_price\":\"450.0000\",\"payment_type\":\"نەقد\",\"amount_paid_usd\":\"450\",\"amount_paid_iq\":\"0\",\"remaining_amount\":\"0.0000\",\"dolar_rate\":\"139400\",\"discount\":\"0\",\"order_date\":\"2025-08-02\",\"invoice_number\":\"A-0014\",\"notes\":\"3150$واصڵ\"}','{\"action_type\":\"sale_creation\",\"payment_status\":\"paid\",\"currency_used\":\"USD\",\"total_paid\":450}','130.193.207.162'),(175,2,'insert','sales',34,'فرۆشتنێکی نوێ زیادکرا (invoice: A-0015, A-0016, A-0017, A-0018, A-0020, کڕیار: ک.تۆفیق اسماعیل, فۆرمۆلا: SAQF 400 , بڕ: 44 م³)','2025-08-05 02:13:29',0,NULL,'{\"customer_id\":\"105\",\"customer_name\":\"ک.تۆفیق اسماعیل\",\"formula_id\":\"19\",\"formula_name\":\"SAQF 400 \",\"recipient\":\"ک.تۆفیق\",\"location\":\"ڕاپەڕین\",\"quantity\":\"44\",\"price_per_unit\":\"47\",\"total_price\":\"2068.0000\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0\",\"amount_paid_iq\":\"0\",\"remaining_amount\":\"2068.0000\",\"dolar_rate\":\"139400\",\"discount\":\"0\",\"order_date\":\"2025-08-02\",\"invoice_number\":\"A-0015, A-0016, A-0017, A-0018, A-0020\",\"notes\":\"\"}','{\"action_type\":\"sale_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0}','130.193.207.162'),(176,2,'insert','sales',35,'فرۆشتنێکی نوێ زیادکرا (invoice: A-0019, A-0022, کڕیار: و.فەرهاد, فۆرمۆلا: ARZY 25 MEGA, بڕ: 16.5 م³)','2025-08-05 02:15:09',0,NULL,'{\"customer_id\":\"50\",\"customer_name\":\"و.فەرهاد\",\"formula_id\":\"27\",\"formula_name\":\"ARZY 25 MEGA\",\"recipient\":\"فه رهاد\",\"location\":\"مه عملى جيمنتو ماس, معمل جيمنتو ماس\",\"quantity\":\"16.5\",\"price_per_unit\":\"45\",\"total_price\":\"742.5000\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0\",\"amount_paid_iq\":\"0\",\"remaining_amount\":\"742.5000\",\"dolar_rate\":\"139400\",\"discount\":\"0\",\"order_date\":\"2025-08-02\",\"invoice_number\":\"A-0019, A-0022\",\"notes\":\"\"}','{\"action_type\":\"sale_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0}','130.193.207.162'),(177,2,'insert','sales',36,'فرۆشتنێکی نوێ زیادکرا (invoice: A-0023, کڕیار: و.کاروان, فۆرمۆلا: ARZY 21 MEGA, بڕ: 9 م³)','2025-08-05 02:15:34',0,NULL,'{\"customer_id\":\"34\",\"customer_name\":\"و.کاروان\",\"formula_id\":\"26\",\"formula_name\":\"ARZY 21 MEGA\",\"recipient\":\"حاكم احمد\",\"location\":\"پیرەمەگرون\",\"quantity\":\"9\",\"price_per_unit\":\"41\",\"total_price\":\"369.0000\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0\",\"amount_paid_iq\":\"0\",\"remaining_amount\":\"369.0000\",\"dolar_rate\":\"139400\",\"discount\":\"0\",\"order_date\":\"2025-08-02\",\"invoice_number\":\"A-0023\",\"notes\":\"\"}','{\"action_type\":\"sale_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0}','130.193.207.162'),(178,2,'insert','sales',37,'فرۆشتنێکی نوێ زیادکرا (invoice: A-0024, A-0025, کڕیار: محمد احمد, فۆرمۆلا: Chawy lws saqf 350, بڕ: 18.5 م³)','2025-08-05 02:16:08',0,NULL,'{\"customer_id\":\"117\",\"customer_name\":\"محمد احمد\",\"formula_id\":\"42\",\"formula_name\":\"Chawy lws saqf 350\",\"recipient\":\"وه ستا ديار\",\"location\":\"ڕاپەڕین\",\"quantity\":\"18.5\",\"price_per_unit\":\"47\",\"total_price\":\"869.5000\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0\",\"amount_paid_iq\":\"0\",\"remaining_amount\":\"869.5000\",\"dolar_rate\":\"139400\",\"discount\":\"0\",\"order_date\":\"2025-08-02\",\"invoice_number\":\"A-0024, A-0025\",\"notes\":\"\"}','{\"action_type\":\"sale_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0}','130.193.207.162'),(179,2,'insert','sales',38,'فرۆشتنێکی نوێ زیادکرا (invoice: A-0026, A-0027, A-0038, کڕیار: حاجي محمود, فۆرمۆلا: Chawy lws saqf 350, بڕ: 24.5 م³)','2025-08-05 02:16:38',0,NULL,'{\"customer_id\":\"118\",\"customer_name\":\"حاجي محمود\",\"formula_id\":\"42\",\"formula_name\":\"Chawy lws saqf 350\",\"recipient\":\"\",\"location\":\"تافكا \",\"quantity\":\"24.5\",\"price_per_unit\":\"46\",\"total_price\":\"1127.0000\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0\",\"amount_paid_iq\":\"0\",\"remaining_amount\":\"1127.0000\",\"dolar_rate\":\"139400\",\"discount\":\"0\",\"order_date\":\"2025-08-02\",\"invoice_number\":\"A-0026, A-0027, A-0038\",\"notes\":\"\"}','{\"action_type\":\"sale_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0}','130.193.207.162'),(180,2,'insert','sales',39,'فرۆشتنێکی نوێ زیادکرا (invoice: A-0028, A-0029, A-0030, A-0031, A-0032, A-0033, A-0034, A-0035, A-0036, A-0037, کڕیار: هێدی وا‌حد علي, فۆرمۆلا: ARZY 30 MEGA, بڕ: 100 م³)','2025-08-05 02:17:26',0,NULL,'{\"customer_id\":\"119\",\"customer_name\":\"هێدی وا‌حد علي\",\"formula_id\":\"30\",\"formula_name\":\"ARZY 30 MEGA\",\"recipient\":\"وەستا ازاد\",\"location\":\"پیرەمەگرون\",\"quantity\":\"100\",\"price_per_unit\":\"47\",\"total_price\":\"4700.0000\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0\",\"amount_paid_iq\":\"0\",\"remaining_amount\":\"4700.0000\",\"dolar_rate\":\"139400\",\"discount\":\"0\",\"order_date\":\"2025-08-02\",\"invoice_number\":\"A-0028, A-0029, A-0030, A-0031, A-0032, A-0033, A-0034, A-0035, A-0036, A-0037\",\"notes\":\"\"}','{\"action_type\":\"sale_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0}','130.193.207.162'),(181,2,'insert','sales',40,'فرۆشتنێکی نوێ زیادکرا (invoice: A-0042, A-0044, A-0045, کڕیار: کاک ئەرسەلان نوسینگە, فۆرمۆلا: ARZY 21 MEGA, بڕ: 30 م³)','2025-08-05 02:22:47',0,NULL,'{\"customer_id\":\"145\",\"customer_name\":\"کاک ئەرسەلان نوسینگە\",\"formula_id\":\"26\",\"formula_name\":\"ARZY 21 MEGA\",\"recipient\":\"و . هیوا \",\"location\":\"بەرامبەر حەسەن تەپە\",\"quantity\":\"30\",\"price_per_unit\":\"43.34\",\"total_price\":\"1300.2000\",\"payment_type\":\"نەقد\",\"amount_paid_usd\":\"1300\",\"amount_paid_iq\":\"0\",\"remaining_amount\":\"0.0000\",\"dolar_rate\":\"139400\",\"discount\":\"0.2\",\"order_date\":\"2025-08-03\",\"invoice_number\":\"A-0042, A-0044, A-0045\",\"notes\":\"1300 دۆلار واسڵ\"}','{\"action_type\":\"sale_creation\",\"payment_status\":\"paid\",\"currency_used\":\"USD\",\"total_paid\":1300}','130.193.207.162'),(182,2,'insert','sales',41,'فرۆشتنێکی نوێ زیادکرا (invoice: A-0043, A-0046, کڕیار: ک.ئاسۆ زەرزی, فۆرمۆلا: ARZY 25 MEGA, بڕ: 13 م³)','2025-08-05 02:23:21',0,NULL,'{\"customer_id\":\"146\",\"customer_name\":\"ک.ئاسۆ زەرزی\",\"formula_id\":\"27\",\"formula_name\":\"ARZY 25 MEGA\",\"recipient\":\"ک . ئاسۆ\",\"location\":\"پیرەمەگرون\",\"quantity\":\"13\",\"price_per_unit\":\"45\",\"total_price\":\"585.0000\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0\",\"amount_paid_iq\":\"0\",\"remaining_amount\":\"585.0000\",\"dolar_rate\":\"139400\",\"discount\":\"0\",\"order_date\":\"2025-08-03\",\"invoice_number\":\"A-0043, A-0046\",\"notes\":\"\"}','{\"action_type\":\"sale_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0}','130.193.207.162'),(183,2,'insert','sales',42,'فرۆشتنێکی نوێ زیادکرا (invoice: A-0047, A-0048, A-0049, A-0050, A-0051, A-0053, A-0054, A-0055, A-0056, A-0057, کڕیار: هێمن حسن شێخۆ, فۆرمۆلا: ARZY 35 M, بڕ: 77 م³)','2025-08-05 02:23:51',0,NULL,'{\"customer_id\":\"147\",\"customer_name\":\"هێمن حسن شێخۆ\",\"formula_id\":\"38\",\"formula_name\":\"ARZY 35 M\",\"recipient\":\"و, شوانه, و.شوانا\",\"location\":\"دوكان\",\"quantity\":\"77\",\"price_per_unit\":\"53\",\"total_price\":\"4081.0000\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0\",\"amount_paid_iq\":\"0\",\"remaining_amount\":\"4081.0000\",\"dolar_rate\":\"139400\",\"discount\":\"0\",\"order_date\":\"2025-08-03\",\"invoice_number\":\"A-0047, A-0048, A-0049, A-0050, A-0051, A-0053, A-0054, A-0055, A-0056, A-0057\",\"notes\":\"\"}','{\"action_type\":\"sale_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0}','130.193.207.162'),(184,2,'insert','sales',43,'فرۆشتنێکی نوێ زیادکرا (invoice: A-0052, A-0077, کڕیار: دانا کۆنکرێت, فۆرمۆلا: PAYA 35 M, بڕ: 4 م³)','2025-08-05 02:24:17',0,NULL,'{\"customer_id\":\"107\",\"customer_name\":\"دانا کۆنکرێت\",\"formula_id\":\"41\",\"formula_name\":\"PAYA 35 M\",\"recipient\":\"\",\"location\":\"كه له ونان\",\"quantity\":\"4\",\"price_per_unit\":\"45\",\"total_price\":\"180.0000\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0\",\"amount_paid_iq\":\"0\",\"remaining_amount\":\"180.0000\",\"dolar_rate\":\"139400\",\"discount\":\"0\",\"order_date\":\"2025-08-03\",\"invoice_number\":\"A-0052, A-0077\",\"notes\":\"\"}','{\"action_type\":\"sale_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0}','130.193.207.162'),(185,2,'insert','sales',44,'فرۆشتنێکی نوێ زیادکرا (invoice: A-0062, کڕیار: كاك ازار , فۆرمۆلا: ARZY 25 MEGA, بڕ: 5 م³)','2025-08-05 02:24:35',0,NULL,'{\"customer_id\":\"99\",\"customer_name\":\"كاك ازار \",\"formula_id\":\"27\",\"formula_name\":\"ARZY 25 MEGA\",\"recipient\":\"کاک کاروان \",\"location\":\"کێلە سپی\",\"quantity\":\"5\",\"price_per_unit\":\"45\",\"total_price\":\"225.0000\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0\",\"amount_paid_iq\":\"0\",\"remaining_amount\":\"225.0000\",\"dolar_rate\":\"139400\",\"discount\":\"0\",\"order_date\":\"2025-08-03\",\"invoice_number\":\"A-0062\",\"notes\":\"\"}','{\"action_type\":\"sale_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0}','130.193.207.162'),(186,2,'insert','sales',45,'فرۆشتنێکی نوێ زیادکرا (invoice: A-0063, A-0066, A-0067, A-0068, کڕیار: کاک شوان مقاول, فۆرمۆلا: Chawy lws saqf 350, بڕ: 35 م³)','2025-08-05 02:24:55',0,NULL,'{\"customer_id\":\"181\",\"customer_name\":\"کاک شوان مقاول\",\"formula_id\":\"42\",\"formula_name\":\"Chawy lws saqf 350\",\"recipient\":\"و. كرمانج\",\"location\":\"ڕاپەڕین\",\"quantity\":\"35\",\"price_per_unit\":\"45\",\"total_price\":\"1575.0000\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0\",\"amount_paid_iq\":\"0\",\"remaining_amount\":\"1575.0000\",\"dolar_rate\":\"139400\",\"discount\":\"0\",\"order_date\":\"2025-08-03\",\"invoice_number\":\"A-0063, A-0066, A-0067, A-0068\",\"notes\":\"\"}','{\"action_type\":\"sale_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0}','130.193.207.162'),(187,2,'insert','sales',46,'فرۆشتنێکی نوێ زیادکرا (invoice: A-0064, A-0065, A-0069, A-0070, A-0071, A-0073, کڕیار: و.ئومێد, فۆرمۆلا: Chawy lws saqf 350, بڕ: 47 م³)','2025-08-05 02:27:47',0,NULL,'{\"customer_id\":\"57\",\"customer_name\":\"و.ئومێد\",\"formula_id\":\"42\",\"formula_name\":\"Chawy lws saqf 350\",\"recipient\":\"\",\"location\":\"دارتو مولانا\",\"quantity\":\"47\",\"price_per_unit\":\"45\",\"total_price\":\"2115.0000\",\"payment_type\":\"نەقد\",\"amount_paid_usd\":\"2100\",\"amount_paid_iq\":\"0\",\"remaining_amount\":\"0.0000\",\"dolar_rate\":\"139400\",\"discount\":\"15\",\"order_date\":\"2025-08-03\",\"invoice_number\":\"A-0064, A-0065, A-0069, A-0070, A-0071, A-0073\",\"notes\":\"2,100$ لای توانای برامە\"}','{\"action_type\":\"sale_creation\",\"payment_status\":\"paid\",\"currency_used\":\"USD\",\"total_paid\":2100}','130.193.207.162'),(188,2,'insert','sales',47,'فرۆشتنێکی نوێ زیادکرا (invoice: A-0072, کڕیار: کاک دانا گولیجەیی, فۆرمۆلا: ARZY 25 MEGA, بڕ: 3 م³)','2025-08-05 02:28:25',0,NULL,'{\"customer_id\":\"186\",\"customer_name\":\"کاک دانا گولیجەیی\",\"formula_id\":\"27\",\"formula_name\":\"ARZY 25 MEGA\",\"recipient\":\"\",\"location\":\"پیرەمەگرون\",\"quantity\":\"3\",\"price_per_unit\":\"45\",\"total_price\":\"135.0000\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0\",\"amount_paid_iq\":\"0\",\"remaining_amount\":\"135.0000\",\"dolar_rate\":\"139400\",\"discount\":\"0\",\"order_date\":\"2025-08-03\",\"invoice_number\":\"A-0072\",\"notes\":\"\"}','{\"action_type\":\"sale_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0}','130.193.207.162'),(189,2,'insert','sales',48,'فرۆشتنێکی نوێ زیادکرا (invoice: A-0074, A-0075, A-0076, کڕیار: و.سەرباز , فۆرمۆلا: Chawy lws saqf 350, بڕ: 28 م³)','2025-08-05 02:30:04',0,NULL,'{\"customer_id\":\"60\",\"customer_name\":\"و.سەرباز \",\"formula_id\":\"42\",\"formula_name\":\"Chawy lws saqf 350\",\"recipient\":\"\",\"location\":\"پیرەمەگرون\",\"quantity\":\"28\",\"price_per_unit\":\"45\",\"total_price\":\"1260.0000\",\"payment_type\":\"نەقد\",\"amount_paid_usd\":\"1200\",\"amount_paid_iq\":\"80000\",\"remaining_amount\":\"-0.0000\",\"dolar_rate\":\"139400\",\"discount\":\"2.6112\",\"order_date\":\"2025-08-03\",\"invoice_number\":\"A-0074, A-0075, A-0076\",\"notes\":\"1,200$+80,000 لای مام شاڵاو پەمپە\"}','{\"action_type\":\"sale_creation\",\"payment_status\":\"paid\",\"currency_used\":\"USD\",\"total_paid\":81200}','130.193.207.162'),(190,7,'update','concrete_receipts',113,'پسوڵەی کۆنکرێت نوێکرایەوە (شماره: A-0109, کڕیار: حاجی کاکەمەند قەرەچەتانی, فۆرمۆلا: Chawy lws saqf 350, بڕ: 9.00 م³)','2025-08-05 04:44:05',0,'{\"receipt_number\":\"A-0109\",\"customer_id\":195,\"customer_name\":\"حاجی کاکەمەند قەرەچەتانی\",\"location\":\"گوندی قەرەچەتان\",\"meter_amount\":\"9.00\",\"formulas_id\":42,\"formula_name\":\"Chawy lws saqf 350\",\"pump_car_id\":11,\"pump_car_name\":\"P2\",\"pump_driver_id\":27,\"pump_driver_name\":\"Unknown\",\"mixer_car_id\":13,\"mixer_car_name\":\"M10\",\"mixer_driver_id\":38,\"mixer_driver_name\":\"Unknown\",\"receiver_name\":\"حاجی کاکەمەند\"}','{\"receipt_number\":\"A-0109\",\"customer_id\":\"195\",\"customer_name\":\"حاجی کاکەمەند قەرەچەتانی\",\"location\":\"گوندی قەرەچەتان\",\"meter_amount\":\"9.00\",\"formulas_id\":\"42\",\"formula_name\":\"Chawy lws saqf 350\",\"pump_car_id\":\"10\",\"pump_car_name\":\"P1\",\"pump_driver_id\":\"26\",\"pump_driver_name\":\"Unknown\",\"mixer_car_id\":\"13\",\"mixer_car_name\":\"M10\",\"mixer_driver_id\":\"38\",\"mixer_driver_name\":\"Unknown\",\"receiver_name\":\"حاجی کاکەمەند\"}','{\"action_type\":\"concrete_receipt_update\",\"receipt_type\":\"concrete_delivery\",\"amount_m3\":\"9.00\",\"delivery_components\":{\"pump_car\":\"P1\",\"mixer_car\":\"M10\"}}','130.193.209.129'),(191,2,'delete','sales',42,'فرۆشتنەکە سڕایەوە (invoice: A-0047, A-0048, A-0049, A-0050, A-0051, A-0053, A-0054, A-0055, A-0056, A-0057, کڕیار: هێمن حسن شێخۆ, فۆرمۆلا: ARZY 35 M)','2025-08-05 05:57:53',0,'{\"customer_id\":147,\"customer_name\":\"هێمن حسن شێخۆ\",\"recipient\":\"و, شوانه, و.شوانا\",\"location\":\"دوكان\",\"quantity\":\"77.00\",\"price_per_unit\":\"53.00\",\"total_price\":\"4081.00\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0.00\",\"amount_paid_iq\":\"0.00\",\"dolar_rate\":\"139400.00\",\"remaining_amount\":\"4081.00\",\"invoice_number\":\"A-0047, A-0048, A-0049, A-0050, A-0051, A-0053, A-0054, A-0055, A-0056, A-0057\",\"order_date\":\"2025-08-03\",\"notes\":\"\",\"formula_id\":38,\"formula_name\":\"ARZY 35 M\",\"discount\":\"0.00\"}',NULL,'{\"action_type\":\"sale_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":\"4081.00\"}','130.193.207.162'),(192,2,'delete','sales',49,'فرۆشتنەکە سڕایەوە (invoice: A-0047, A-0048, A-0049, A-0050, A-0051, A-0053, A-0054, A-0055, A-0056, A-0057, کڕیار: هێمن حسن شێخۆ, فۆرمۆلا: ARZY 35 M)','2025-08-05 05:59:34',0,'{\"customer_id\":147,\"customer_name\":\"هێمن حسن شێخۆ\",\"recipient\":\"و, شوانه, و.شوانا\",\"location\":\"دوكان\",\"quantity\":\"77.00\",\"price_per_unit\":\"53.00\",\"total_price\":\"4081.00\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0.00\",\"amount_paid_iq\":\"0.00\",\"dolar_rate\":\"139400.00\",\"remaining_amount\":\"4081.00\",\"invoice_number\":\"A-0047, A-0048, A-0049, A-0050, A-0051, A-0053, A-0054, A-0055, A-0056, A-0057\",\"order_date\":\"2025-08-03\",\"notes\":\"\",\"formula_id\":38,\"formula_name\":\"ARZY 35 M\",\"discount\":\"0.00\"}',NULL,'{\"action_type\":\"sale_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":\"4081.00\"}','130.193.207.162'),(193,2,'insert','sales',50,'فرۆشتنێکی نوێ زیادکرا (invoice: A-0047, A-0048, A-0049, A-0050, A-0051, A-0053, A-0054, A-0055, A-0056, A-0057, A-0058, A-0059, A-0060, A-0061, کڕیار: هێمن حسن شێخۆ, فۆرمۆلا: ARZY 35 M, بڕ: 105 م³)','2025-08-05 06:07:19',0,NULL,'{\"customer_id\":\"147\",\"customer_name\":\"هێمن حسن شێخۆ\",\"formula_id\":\"38\",\"formula_name\":\"ARZY 35 M\",\"recipient\":\"و, شوانه, و.شوانا\",\"location\":\"دوكان\",\"quantity\":\"105\",\"price_per_unit\":\"53\",\"total_price\":\"5565.0000\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0\",\"amount_paid_iq\":\"0\",\"remaining_amount\":\"5565.0000\",\"dolar_rate\":\"139400\",\"discount\":\"0\",\"order_date\":\"2025-08-03\",\"invoice_number\":\"A-0047, A-0048, A-0049, A-0050, A-0051, A-0053, A-0054, A-0055, A-0056, A-0057, A-0058, A-0059, A-0060, A-0061\",\"notes\":\"\"}','{\"action_type\":\"sale_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0}','130.193.207.162'),(194,2,'delete','sales',39,'فرۆشتنەکە سڕایەوە (invoice: A-0028, A-0029, A-0030, A-0031, A-0032, A-0033, A-0034, A-0035, A-0036, A-0037, کڕیار: هێدی وا‌حد علي, فۆرمۆلا: ARZY 30 MEGA)','2025-08-05 06:09:15',0,'{\"customer_id\":119,\"customer_name\":\"هێدی وا‌حد علي\",\"recipient\":\"وەستا ازاد\",\"location\":\"پیرەمەگرون\",\"quantity\":\"100.00\",\"price_per_unit\":\"47.00\",\"total_price\":\"4700.00\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0.00\",\"amount_paid_iq\":\"0.00\",\"dolar_rate\":\"139400.00\",\"remaining_amount\":\"4700.00\",\"invoice_number\":\"A-0028, A-0029, A-0030, A-0031, A-0032, A-0033, A-0034, A-0035, A-0036, A-0037\",\"order_date\":\"2025-08-02\",\"notes\":\"\",\"formula_id\":30,\"formula_name\":\"ARZY 30 MEGA\",\"discount\":\"0.00\"}',NULL,'{\"action_type\":\"sale_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":\"4700.00\"}','130.193.207.162'),(195,2,'insert','sales',51,'فرۆشتنێکی نوێ زیادکرا (invoice: A-0028, A-0029, A-0030, A-0031, A-0032, A-0033, A-0034, A-0035, A-0036, A-0037, A-0039, A-0041, کڕیار: هێدی وا‌حد علي, فۆرمۆلا: ARZY 30 MEGA, بڕ: 112 م³)','2025-08-05 06:11:14',0,NULL,'{\"customer_id\":\"119\",\"customer_name\":\"هێدی وا‌حد علي\",\"formula_id\":\"30\",\"formula_name\":\"ARZY 30 MEGA\",\"recipient\":\"وستا ازاد\",\"location\":\"پیرەمەگرون\",\"quantity\":\"112\",\"price_per_unit\":\"47\",\"total_price\":\"5264.0000\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0\",\"amount_paid_iq\":\"0\",\"remaining_amount\":\"5264.0000\",\"dolar_rate\":\"139400\",\"discount\":\"0\",\"order_date\":\"2025-08-02\",\"invoice_number\":\"A-0028, A-0029, A-0030, A-0031, A-0032, A-0033, A-0034, A-0035, A-0036, A-0037, A-0039, A-0041\",\"notes\":\"\"}','{\"action_type\":\"sale_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0}','130.193.207.162'),(196,7,'update','concrete_receipts',116,'پسوڵەی کۆنکرێت نوێکرایەوە (شماره: A-0112, کڕیار: حاجی کاکەمەند قەرەچەتانی, فۆرمۆلا: ARZY 25 MEGA, بڕ: 6.00 م³)','2025-08-05 06:58:53',0,'{\"receipt_number\":\"A-0112\",\"customer_id\":195,\"customer_name\":\"حاجی کاکەمەند قەرەچەتانی\",\"location\":\"گوندی قەرەچەتان\",\"meter_amount\":\"6.00\",\"formulas_id\":27,\"formula_name\":\"ARZY 25 MEGA\",\"pump_car_id\":10,\"pump_car_name\":\"P1\",\"pump_driver_id\":26,\"pump_driver_name\":\"Unknown\",\"mixer_car_id\":13,\"mixer_car_name\":\"M10\",\"mixer_driver_id\":38,\"mixer_driver_name\":\"Unknown\",\"receiver_name\":\"حاجی کاکەمەند\"}','{\"receipt_number\":\"A-0112\",\"customer_id\":\"195\",\"customer_name\":\"حاجی کاکەمەند قەرەچەتانی\",\"location\":\"گوندی قەرەچەتان\",\"meter_amount\":\"6.00\",\"formulas_id\":\"27\",\"formula_name\":\"ARZY 25 MEGA\",\"pump_car_id\":\"10\",\"pump_car_name\":\"P1\",\"pump_driver_id\":\"26\",\"pump_driver_name\":\"Unknown\",\"mixer_car_id\":\"20\",\"mixer_car_name\":\"M17\",\"mixer_driver_id\":\"34\",\"mixer_driver_name\":\"Unknown\",\"receiver_name\":\"حاجی کاکەمەند\"}','{\"action_type\":\"concrete_receipt_update\",\"receipt_type\":\"concrete_delivery\",\"amount_m3\":\"6.00\",\"delivery_components\":{\"pump_car\":\"P1\",\"mixer_car\":\"M17\"}}','130.193.209.129'),(197,2,'delete','sales',36,'فرۆشتنەکە سڕایەوە (invoice: A-0023, کڕیار: و.کاروان, فۆرمۆلا: ARZY 21 MEGA)','2025-08-05 08:23:25',0,'{\"customer_id\":34,\"customer_name\":\"و.کاروان\",\"recipient\":\"حاكم احمد\",\"location\":\"پیرەمەگرون\",\"quantity\":\"9.00\",\"price_per_unit\":\"41.00\",\"total_price\":\"369.00\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0.00\",\"amount_paid_iq\":\"0.00\",\"dolar_rate\":\"139400.00\",\"remaining_amount\":\"369.00\",\"invoice_number\":\"A-0023\",\"order_date\":\"2025-08-02\",\"notes\":\"\",\"formula_id\":26,\"formula_name\":\"ARZY 21 MEGA\",\"discount\":\"0.00\"}',NULL,'{\"action_type\":\"sale_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":\"369.00\"}','130.193.207.162'),(198,2,'insert','sales',52,'فرۆشتنێکی نوێ زیادکرا (invoice: A-0023, کڕیار: حاکم احمد , فۆرمۆلا: ARZY 21 MEGA, بڕ: 9 م³)','2025-08-05 08:24:04',0,NULL,'{\"customer_id\":\"71\",\"customer_name\":\"حاکم احمد \",\"formula_id\":\"26\",\"formula_name\":\"ARZY 21 MEGA\",\"recipient\":\"و.کاروان\",\"location\":\"پیرەمەگرون\",\"quantity\":\"9\",\"price_per_unit\":\"41\",\"total_price\":\"369.0000\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0\",\"amount_paid_iq\":\"0\",\"remaining_amount\":\"369.0000\",\"dolar_rate\":\"139400\",\"discount\":\"0\",\"order_date\":\"2025-08-02\",\"invoice_number\":\"A-0023\",\"notes\":\"\"}','{\"action_type\":\"sale_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0}','130.193.207.162'),(199,2,'insert','purchases',69,'کڕینێکی نوێ زیادکرا (invoice: 1113, کۆمپانیا: دەشتی, مادە: لمی ڕەش, بڕ: 32730 کگم)','2025-08-05 09:18:55',0,NULL,'{\"company_id\":\"1\",\"company_name\":\"دەشتی\",\"driver\":\"شاخەوان علی حاجی\",\"location\":\"کەنجاڕە\",\"material_id\":\"1\",\"material_name\":\"لمی ڕەش\",\"amount_iqd\":\"335482.50\",\"kg\":\"32730\",\"price\":\"0\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0\",\"remaining_iqd\":\"335000\",\"bin_id\":\"2\",\"price_per_kg_iqd\":\"10250\",\"price_per_kg_usd\":\"0\",\"invoice_number\":\"1113\",\"date\":\"2025-08-04\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":335000}','130.193.209.66'),(200,2,'insert','purchases',70,'کڕینێکی نوێ زیادکرا (invoice: 1114, کۆمپانیا: دەشتی, مادە: لمی ڕەش, بڕ: 32620 کگم)','2025-08-05 09:20:30',0,NULL,'{\"company_id\":\"1\",\"company_name\":\"دەشتی\",\"driver\":\"ڕەنجە\",\"location\":\"کەنجاڕە\",\"material_id\":\"1\",\"material_name\":\"لمی ڕەش\",\"amount_iqd\":\"334355.00\",\"kg\":\"32620\",\"price\":\"0\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0\",\"remaining_iqd\":\"334000\",\"bin_id\":\"2\",\"price_per_kg_iqd\":\"10250\",\"price_per_kg_usd\":\"0\",\"invoice_number\":\"1114\",\"date\":\"2025-08-04\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":334000}','130.193.209.66'),(201,2,'insert','purchases',71,'کڕینێکی نوێ زیادکرا (invoice: 1115, کۆمپانیا: دەشتی, مادە: لمی ڕەش, بڕ: 31120 کگم)','2025-08-05 09:22:33',0,NULL,'{\"company_id\":\"1\",\"company_name\":\"دەشتی\",\"driver\":\"مەتین\",\"location\":\"کەنجاڕە\",\"material_id\":\"1\",\"material_name\":\"لمی ڕەش\",\"amount_iqd\":\"318980.00\",\"kg\":\"31120\",\"price\":\"0\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0\",\"remaining_iqd\":\"318000\",\"bin_id\":\"2\",\"price_per_kg_iqd\":\"10250\",\"price_per_kg_usd\":\"0\",\"invoice_number\":\"1115\",\"date\":\"2025-08-04\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":318000}','130.193.209.66'),(202,2,'insert','purchases',72,'کڕینێکی نوێ زیادکرا (invoice: 1116, کۆمپانیا: دەشتی, مادە: لمی ڕەش, بڕ: 32000 کگم)','2025-08-05 09:24:47',0,NULL,'{\"company_id\":\"1\",\"company_name\":\"دەشتی\",\"driver\":\"هاوکار\",\"location\":\"کەنجاڕە\",\"material_id\":\"1\",\"material_name\":\"لمی ڕەش\",\"amount_iqd\":\"328000.00\",\"kg\":\"32000\",\"price\":\"0\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0\",\"remaining_iqd\":\"328000.00\",\"bin_id\":\"2\",\"price_per_kg_iqd\":\"10250\",\"price_per_kg_usd\":\"0\",\"invoice_number\":\"1116\",\"date\":\"2025-08-05\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":328000}','130.193.209.66'),(203,2,'insert','purchases',73,'کڕینێکی نوێ زیادکرا (invoice: 1442, کۆمپانیا: سۆران, مادە: چەو, بڕ: 30855 کگم)','2025-08-05 09:29:47',0,NULL,'{\"company_id\":\"2\",\"company_name\":\"سۆران\",\"driver\":\"سۆران\",\"location\":\"کارگە و چەو و لمی عطا\",\"material_id\":\"2\",\"material_name\":\"چەو\",\"amount_iqd\":\"179977.21\",\"kg\":\"30855\",\"price\":\"0\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0\",\"remaining_iqd\":\"179000\",\"bin_id\":\"3\",\"price_per_kg_iqd\":\"5833\",\"price_per_kg_usd\":\"0\",\"invoice_number\":\"1442\",\"date\":\"2025-08-05\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":179000}','130.193.209.66'),(204,2,'insert','purchases',74,'کڕینێکی نوێ زیادکرا (invoice: 5148, کۆمپانیا: تڕێلەکەی خۆمان, مادە: چەو, بڕ: 32300 کگم)','2025-08-05 09:33:06',0,NULL,'{\"company_id\":\"6\",\"company_name\":\"تڕێلەکەی خۆمان\",\"driver\":\"کاک عیماد شۆفێر\",\"location\":\"غەسالەی نەورۆز\",\"material_id\":\"2\",\"material_name\":\"چەو\",\"amount_iqd\":\"188405.90\",\"kg\":\"32300\",\"price\":\"0\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0\",\"remaining_iqd\":\"188000\",\"bin_id\":\"3\",\"price_per_kg_iqd\":\"5833\",\"price_per_kg_usd\":\"0\",\"invoice_number\":\"5148\",\"date\":\"2025-08-05\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":188000}','130.193.209.66'),(205,2,'delete','purchases',72,'کڕینەکە سڕایەوە (invoice: 1116, کۆمپانیا: دەشتی, مادە: لمی ڕەش)','2025-08-05 09:34:11',0,'{\"company_id\":1,\"company_name\":\"دەشتی\",\"driver\":\"هاوکار\",\"location\":\"کەنجاڕە\",\"material_id\":1,\"material_name\":\"لمی ڕەش\",\"amount_iqd\":\"328000.00\",\"kg\":\"32000.00\",\"price\":\"0.00\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0.00\",\"remaining_iqd\":\"328000\",\"bin_id\":2,\"price_per_kg_iqd\":\"10250.00\",\"price_per_kg_usd\":\"0.00\",\"invoice_number\":\"1116\",\"date\":\"2025-08-05\"}',NULL,'{\"action_type\":\"purchase_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":328000}','130.193.209.66'),(206,2,'insert','purchases',75,'کڕینێکی نوێ زیادکرا (invoice: 1116, کۆمپانیا: دەشتی, مادە: لمی ڕەش, بڕ: 32000 کگم)','2025-08-05 09:37:05',0,NULL,'{\"company_id\":\"1\",\"company_name\":\"دەشتی\",\"driver\":\"هاوکار\",\"location\":\"کەنجاڕە\",\"material_id\":\"1\",\"material_name\":\"لمی ڕەش\",\"amount_iqd\":\"328000.00\",\"kg\":\"32000\",\"price\":\"0\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0\",\"remaining_iqd\":\"328000.00\",\"bin_id\":\"2\",\"price_per_kg_iqd\":\"10250\",\"price_per_kg_usd\":\"0\",\"invoice_number\":\"1116\",\"date\":\"2025-08-04\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":328000}','130.193.209.66'),(207,2,'delete','purchases',75,'کڕینەکە سڕایەوە (invoice: 1116, کۆمپانیا: دەشتی, مادە: لمی ڕەش)','2025-08-05 09:37:51',0,'{\"company_id\":1,\"company_name\":\"دەشتی\",\"driver\":\"هاوکار\",\"location\":\"کەنجاڕە\",\"material_id\":1,\"material_name\":\"لمی ڕەش\",\"amount_iqd\":\"328000.00\",\"kg\":\"32000.00\",\"price\":\"0.00\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0.00\",\"remaining_iqd\":\"328000\",\"bin_id\":2,\"price_per_kg_iqd\":\"10250.00\",\"price_per_kg_usd\":\"0.00\",\"invoice_number\":\"1116\",\"date\":\"2025-08-04\"}',NULL,'{\"action_type\":\"purchase_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":328000}','130.193.209.66'),(208,2,'insert','purchases',76,'کڕینێکی نوێ زیادکرا (invoice: 1116, کۆمپانیا: دەشتی, مادە: لمی ڕەش, بڕ: 32000 کگم)','2025-08-05 09:38:51',0,NULL,'{\"company_id\":\"1\",\"company_name\":\"دەشتی\",\"driver\":\"هاوکار\",\"location\":\"کەنجاڕە\",\"material_id\":\"1\",\"material_name\":\"لمی ڕەش\",\"amount_iqd\":\"328000.00\",\"kg\":\"32000\",\"price\":\"0\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0\",\"remaining_iqd\":\"328000.00\",\"bin_id\":\"2\",\"price_per_kg_iqd\":\"10250\",\"price_per_kg_usd\":\"0\",\"invoice_number\":\"1116\",\"date\":\"2025-08-04\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":328000}','130.193.209.66'),(209,2,'delete','purchases',76,'کڕینەکە سڕایەوە (invoice: 1116, کۆمپانیا: دەشتی, مادە: لمی ڕەش)','2025-08-05 09:39:38',0,'{\"company_id\":1,\"company_name\":\"دەشتی\",\"driver\":\"هاوکار\",\"location\":\"کەنجاڕە\",\"material_id\":1,\"material_name\":\"لمی ڕەش\",\"amount_iqd\":\"328000.00\",\"kg\":\"32000.00\",\"price\":\"0.00\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0.00\",\"remaining_iqd\":\"328000\",\"bin_id\":2,\"price_per_kg_iqd\":\"10250.00\",\"price_per_kg_usd\":\"0.00\",\"invoice_number\":\"1116\",\"date\":\"2025-08-04\"}',NULL,'{\"action_type\":\"purchase_deletion\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":328000}','130.193.209.66'),(210,2,'insert','purchases',77,'کڕینێکی نوێ زیادکرا (invoice: 1116, کۆمپانیا: دەشتی, مادە: لمی ڕەش, بڕ: 32000 کگم)','2025-08-05 09:40:32',0,NULL,'{\"company_id\":\"1\",\"company_name\":\"دەشتی\",\"driver\":\"هاوکار\",\"location\":\"کەنجاڕە\",\"material_id\":\"1\",\"material_name\":\"لمی ڕەش\",\"amount_iqd\":\"328000.00\",\"kg\":\"32000\",\"price\":\"0\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0\",\"remaining_iqd\":\"328000.00\",\"bin_id\":\"2\",\"price_per_kg_iqd\":\"10250\",\"price_per_kg_usd\":\"0\",\"invoice_number\":\"1116\",\"date\":\"2025-08-05\"}','{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":328000}','130.193.209.66'),(211,2,'insert','other_expenses',18,'خەرجی تر زیادکرا (invoice: , جۆر: بەکارهێنانی کاڵای کۆگا, کەس: هیچ کەسێک نییە, کارمەند: دانا, سەیارە: P2)','2025-08-05 10:59:04',0,NULL,'{\"person_id\":null,\"person_name\":\"هیچ کەسێک نییە\",\"employee_id\":\"23\",\"employee_name\":\"دانا\",\"car_id\":\"11\",\"car_name\":\"P2\",\"gas_liters\":0,\"expense_type\":\"بەکارهێنانی کاڵای کۆگا\",\"material_id\":\"42\",\"material_name\":\"Unknown\",\"material_quantity\":1,\"material_purchase_price_iqd\":8000,\"material_purchase_price_usd\":0,\"material_total_cost\":8000,\"gas_purchase_price_input\":0,\"gas_total_cost\":0,\"payment_type\":\"نەقد\",\"currency_type\":\"دینار\",\"invoice_number\":\"\",\"amount_iqd\":0,\"amount_usd\":0,\"paid_iqd\":0,\"paid_usd\":0,\"exchange_rate\":139400,\"remaining_iqd\":0,\"remaining_usd\":0,\"date\":\"2025-08-05\"}','{\"action_type\":\"other_expense_creation\",\"payment_status\":\"paid\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":0,\"expense_category\":\"بەکارهێنانی کاڵای کۆگا\"}','130.193.209.66'),(212,2,'delete','other_expenses',18,'خەرجی تر سڕایەوە (ID: 18, جۆر: بەکارهێنانی کاڵای کۆگا, کەس: هیچ کەسێک نییە, کارمەند: دانا, سەیارە: P2)','2025-08-05 11:05:26',0,'{\"person_id\":null,\"person_name\":\"هیچ کەسێک نییە\",\"employee_id\":23,\"employee_name\":\"دانا\",\"car_id\":11,\"car_name\":\"P2\",\"gas_liters\":\"0.00\",\"expense_type\":\"بەکارهێنانی کاڵای کۆگا\",\"material_id\":42,\"material_name\":\"Unknown\",\"material_quantity\":\"1.00\",\"material_purchase_price_iqd\":\"8000.00\",\"material_purchase_price_usd\":\"0.00\",\"material_total_cost\":\"8000.00\",\"gas_purchase_price_input\":\"0.00\",\"gas_total_cost\":\"0.00\",\"payment_type\":\"نەقد\",\"currency_type\":\"دینار\",\"invoice_number\":\"\",\"amount_iqd\":\"0.00\",\"amount_usd\":\"0.00\",\"paid_iqd\":\"0.00\",\"paid_usd\":\"0.00\",\"exchange_rate\":\"139400.00\",\"remaining_iqd\":\"0.00\",\"remaining_usd\":\"0.00\",\"date\":\"2025-08-05\"}',NULL,'{\"action_type\":\"other_expense_deletion\",\"payment_status\":\"paid\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":0,\"expense_category\":\"بەکارهێنانی کاڵای کۆگا\"}','130.193.209.66'),(213,2,'insert','other_expenses',19,'خەرجی تر زیادکرا (invoice: , جۆر: بەکارهێنانی کاڵای کۆگا, کەس: هیچ کەسێک نییە, کارمەند: دانا, سەیارە: P2)','2025-08-05 11:07:22',0,NULL,'{\"person_id\":null,\"person_name\":\"هیچ کەسێک نییە\",\"employee_id\":\"23\",\"employee_name\":\"دانا\",\"car_id\":\"11\",\"car_name\":\"P2\",\"gas_liters\":0,\"expense_type\":\"بەکارهێنانی کاڵای کۆگا\",\"material_id\":\"42\",\"material_name\":\"Unknown\",\"material_quantity\":1,\"material_purchase_price_iqd\":8000,\"material_purchase_price_usd\":0,\"material_total_cost\":8000,\"gas_purchase_price_input\":0,\"gas_total_cost\":0,\"payment_type\":\"نەقد\",\"currency_type\":\"دینار\",\"invoice_number\":\"\",\"amount_iqd\":0,\"amount_usd\":0,\"paid_iqd\":0,\"paid_usd\":0,\"exchange_rate\":139400,\"remaining_iqd\":0,\"remaining_usd\":0,\"date\":\"2025-08-02\"}','{\"action_type\":\"other_expense_creation\",\"payment_status\":\"paid\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":0,\"expense_category\":\"بەکارهێنانی کاڵای کۆگا\"}','130.193.209.66');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `other_expense_persons`
--

DROP TABLE IF EXISTS `other_expense_persons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `other_expense_persons` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `expense_usd` decimal(15,2) DEFAULT '0.00',
  `expense_iqd` decimal(15,2) DEFAULT '0.00',
  `opening_debt_usd` decimal(15,2) DEFAULT '0.00',
  `opening_debt_iqd` decimal(15,2) DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `other_expense_persons`
--

LOCK TABLES `other_expense_persons` WRITE;
/*!40000 ALTER TABLE `other_expense_persons` DISABLE KEYS */;
INSERT INTO `other_expense_persons` VALUES (19,'هەڵگورد',0.00,0.00,4446.00,0.00),(20,'جامچی ئاوات',0.00,0.00,0.00,25000.00),(21,'کاک دارای تایە و پاتری',0.00,0.00,4402.00,0.00),(22,'سکراپ چی حاجی فاروق',0.00,0.00,2830.00,0.00),(23,'شوێنکاری دارای تەبرید',0.00,0.00,0.00,315000.00),(24,'کۆمپانیای شوینک',0.00,0.00,40.00,0.00),(25,'فرۆشگای ئەردەڵان',0.00,0.00,0.00,213000.00),(26,'شوێنکاری هەرێم',0.00,0.00,0.00,160000.00),(27,'فرۆشگای دیوان - سۆران لەزگە',0.00,0.00,0.00,75000.00),(28,'وایەرمەنی وەستا هاوکار',0.00,0.00,0.00,70000.00),(29,'وەستا بارامی حداد',0.00,0.00,0.00,330000.00),(30,'کۆگای پارسا - کمالی  پەمپ',0.00,0.00,280.00,0.00),(31,'پێشانگای سیامەند',0.00,0.00,0.00,150000.00),(32,'وەستا ڕەوەند ئاسنگەر',0.00,0.00,0.00,75000.00),(33,'هۆشیار حداد کەرکوک',0.00,0.00,0.00,275000.00),(34,'کاک هاوکاری دراوسێ',0.00,0.00,0.00,190000.00),(35,'ئازاد سۆندەچی',0.00,0.00,0.00,0.00),(36,'پێشانگای محمد',0.00,0.00,0.00,0.00);
/*!40000 ALTER TABLE `other_expense_persons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `other_expenses`
--

DROP TABLE IF EXISTS `other_expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `other_expenses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `purpose` text COLLATE utf8mb4_general_ci NOT NULL,
  `person_id` int DEFAULT NULL,
  `employee_id` int DEFAULT NULL,
  `car_id` int DEFAULT NULL,
  `gas_liters` decimal(10,2) DEFAULT NULL,
  `payment_type` enum('نەقد','قەرز') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `currency_type` enum('دینار','دۆلار') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `invoice_number` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `amount_iqd` decimal(20,2) DEFAULT '0.00',
  `amount_usd` decimal(14,2) DEFAULT '0.00',
  `paid_iqd` decimal(20,2) DEFAULT '0.00',
  `paid_usd` decimal(14,2) DEFAULT '0.00',
  `exchange_rate` decimal(10,2) DEFAULT '150000.00',
  `remaining_iqd` decimal(20,2) DEFAULT '0.00',
  `remaining_usd` decimal(14,2) DEFAULT '0.00',
  `date` date NOT NULL,
  `expense_type` enum('بەکارهێنانی کاڵای کۆگا','بەکارهێنانی گاز','خەرجی تر','خواردنگە','ئۆفیس') COLLATE utf8mb4_general_ci DEFAULT 'خەرجی تر',
  `material_quantity` decimal(10,2) DEFAULT NULL COMMENT 'بڕی عەدەدی کاڵا',
  `gas_purchase_price_input` decimal(15,2) DEFAULT NULL COMMENT 'ئینپوتی نرخی کڕینی گاز',
  `material_purchase_price_iqd` decimal(15,2) DEFAULT NULL COMMENT 'نرخی کڕینی کاڵا بە دینار',
  `material_purchase_price_usd` decimal(15,2) DEFAULT NULL COMMENT 'نرخی کڕینی کاڵا بە دۆلار',
  `material_id` int DEFAULT NULL COMMENT 'ناسنامەی کاڵا لە کۆگا',
  `material_total_cost` decimal(15,2) DEFAULT NULL COMMENT 'کۆی نرخی کاڵای بەکارهاتوو',
  `gas_total_cost` decimal(15,2) DEFAULT NULL COMMENT 'کۆی نرخی گازی بەکارهاتوو',
  `base_material_quantity` decimal(15,2) DEFAULT NULL COMMENT 'بڕی بنەڕەتی کاڵا (دانە/لیتر)',
  `usage_unit_type` enum('کارتۆن','دانە','بەرمیل','دەبە','لیتر') COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'یەکەی بەکارهێنان',
  `created_by` int DEFAULT NULL COMMENT 'ناسنامەی کارمەندێک کە خەرجییەکەی تۆمارکردووە',
  PRIMARY KEY (`id`),
  KEY `person_id` (`person_id`),
  KEY `employee_id` (`employee_id`),
  KEY `car_id` (`car_id`),
  KEY `other_expenses_ibfk_4` (`created_by`),
  CONSTRAINT `other_expenses_ibfk_1` FOREIGN KEY (`person_id`) REFERENCES `other_expense_persons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `other_expenses_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `other_expenses_ibfk_3` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE SET NULL,
  CONSTRAINT `other_expenses_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `other_expenses`
--

LOCK TABLES `other_expenses` WRITE;
/*!40000 ALTER TABLE `other_expenses` DISABLE KEYS */;
INSERT INTO `other_expenses` VALUES (19,'کەپسکردنی سۆندەی سوکان',NULL,23,11,0.00,'نەقد','دینار','',0.00,0.00,0.00,0.00,139400.00,0.00,0.00,'2025-08-02','بەکارهێنانی کاڵای کۆگا',1.00,0.00,8000.00,0.00,42,8000.00,0.00,1.00,'دانە',NULL);
/*!40000 ALTER TABLE `other_expenses` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_after_insert_other_expenses` AFTER INSERT ON `other_expenses` FOR EACH ROW BEGIN
    -- Handle cash box operations for cash payments
    IF NEW.payment_type = 'نەقد' THEN
        IF NEW.currency_type = 'دۆلار' AND NEW.paid_usd > 0 THEN
            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NEW.date, 'withdraw', 0, NEW.paid_usd, 'دۆلار', CONCAT('خەرجی تر: invoice ', NEW.invoice_number), NULL);
        END IF;
        IF NEW.currency_type = 'دینار' AND NEW.paid_iqd > 0 THEN
            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NEW.date, 'withdraw', NEW.paid_iqd, 0, 'دینار', CONCAT('خەرجی تر: invoice ', NEW.invoice_number), NULL);
        END IF;
    END IF;
    
    -- Handle gas consumption for gas usage expenses
    IF NEW.expense_type = 'بەکارهێنانی گاز' AND NEW.gas_liters IS NOT NULL AND NEW.gas_liters > 0 THEN
        UPDATE bins_silos
        SET amount = amount - NEW.gas_liters
        WHERE type = 'تەنکی' AND material_type = 'گاز'
        LIMIT 1;
    END IF;
    
    -- Handle material consumption for warehouse material usage
    IF NEW.expense_type = 'بەکارهێنانی کاڵای کۆگا' AND NEW.material_id IS NOT NULL AND NEW.base_material_quantity IS NOT NULL AND NEW.base_material_quantity > 0 THEN
        UPDATE list_materials
        SET quantity = quantity - NEW.base_material_quantity
        WHERE id = NEW.material_id;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_before_update_other_expenses` BEFORE UPDATE ON `other_expenses` FOR EACH ROW BEGIN
    -- Handle cash box operations for old record
    IF OLD.payment_type = 'نەقد' THEN
        IF OLD.currency_type = 'دۆلار' AND OLD.paid_usd > 0 THEN
            DELETE FROM cash_box
            WHERE `date` = OLD.date AND `type` = 'withdraw' AND amount_usd = OLD.paid_usd AND currency = 'دۆلار' AND note = CONCAT('خەرجی تر: invoice ', OLD.invoice_number);
        END IF;
        IF OLD.currency_type = 'دینار' AND OLD.paid_iqd > 0 THEN
            DELETE FROM cash_box
            WHERE `date` = OLD.date AND `type` = 'withdraw' AND amount_iqd = OLD.paid_iqd AND currency = 'دینار' AND note = CONCAT('خەرجی تر: invoice ', OLD.invoice_number);
        END IF;
    END IF;
    
    -- Handle gas changes
    IF OLD.expense_type = 'بەکارهێنانی گاز' AND OLD.gas_liters IS NOT NULL AND OLD.gas_liters > 0 THEN
        -- Restore old gas amount
        UPDATE bins_silos
        SET amount = amount + OLD.gas_liters
        WHERE type = 'تەنکی' AND material_type = 'گاز'
        LIMIT 1;
    END IF;
    
    -- Handle material changes
    IF OLD.expense_type = 'بەکارهێنانی کاڵای کۆگا' AND OLD.material_id IS NOT NULL AND OLD.base_material_quantity IS NOT NULL AND OLD.base_material_quantity > 0 THEN
        -- Restore old material quantity
        UPDATE list_materials
        SET quantity = quantity + OLD.base_material_quantity
        WHERE id = OLD.material_id;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_after_update_other_expenses` AFTER UPDATE ON `other_expenses` FOR EACH ROW BEGIN
    -- Handle cash box operations for new record
    IF NEW.payment_type = 'نەقد' THEN
        IF NEW.currency_type = 'دۆلار' AND NEW.paid_usd > 0 THEN
            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NEW.date, 'withdraw', 0, NEW.paid_usd, 'دۆلار', CONCAT('خەرجی تر: invoice ', NEW.invoice_number), NULL);
        END IF;
        IF NEW.currency_type = 'دینار' AND NEW.paid_iqd > 0 THEN
            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NEW.date, 'withdraw', NEW.paid_iqd, 0, 'دینار', CONCAT('خەرجی تر: invoice ', NEW.invoice_number), NULL);
        END IF;
    END IF;
    
    -- Handle new gas consumption
    IF NEW.expense_type = 'بەکارهێنانی گاز' AND NEW.gas_liters IS NOT NULL AND NEW.gas_liters > 0 THEN
        UPDATE bins_silos
        SET amount = amount - NEW.gas_liters
        WHERE type = 'تەنکی' AND material_type = 'گاز'
        LIMIT 1;
    END IF;
    
    -- Handle new material consumption
    IF NEW.expense_type = 'بەکارهێنانی کاڵای کۆگا' AND NEW.material_id IS NOT NULL AND NEW.base_material_quantity IS NOT NULL AND NEW.base_material_quantity > 0 THEN
        UPDATE list_materials
        SET quantity = quantity - NEW.base_material_quantity
        WHERE id = NEW.material_id;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_before_delete_other_expenses` BEFORE DELETE ON `other_expenses` FOR EACH ROW BEGIN
    -- Handle cash box operations reversal
    IF OLD.payment_type = 'نەقد' THEN
        IF OLD.currency_type = 'دۆلار' AND OLD.paid_usd > 0 THEN
            DELETE FROM cash_box
            WHERE `date` = OLD.date AND `type` = 'withdraw' AND amount_usd = OLD.paid_usd AND currency = 'دۆلار' AND note = CONCAT('خەرجی تر: invoice ', OLD.invoice_number);
        END IF;
        IF OLD.currency_type = 'دینار' AND OLD.paid_iqd > 0 THEN
            DELETE FROM cash_box
            WHERE `date` = OLD.date AND `type` = 'withdraw' AND amount_iqd = OLD.paid_iqd AND currency = 'دینار' AND note = CONCAT('خەرجی تر: invoice ', OLD.invoice_number);
        END IF;
    END IF;
    
    -- Handle gas restoration
    IF OLD.expense_type = 'بەکارهێنانی گاز' AND OLD.gas_liters IS NOT NULL AND OLD.gas_liters > 0 THEN
        UPDATE bins_silos
        SET amount = amount + OLD.gas_liters
        WHERE type = 'تەنکی' AND material_type = 'گاز'
        LIMIT 1;
    END IF;
    
    -- Handle material restoration
    IF OLD.expense_type = 'بەکارهێنانی کاڵای کۆگا' AND OLD.material_id IS NOT NULL AND OLD.base_material_quantity IS NOT NULL AND OLD.base_material_quantity > 0 THEN
        UPDATE list_materials
        SET quantity = quantity + OLD.base_material_quantity
        WHERE id = OLD.material_id;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=91 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'add_user','زیادکردنی بەکارهێنەر'),(2,'edit_user','دەستکاری بەکارهێنەر'),(3,'delete_user','سڕینەوەی بەکارهێنەر'),(4,'view_users','بینینی لیستی بەکارهێنەران'),(5,'view_dashboard','بینینی داشبۆرد'),(6,'add_material','زیادکردنی مەواد'),(7,'add_company','زیادکردنی کۆمپانیا'),(8,'edit_company','دەسکاری کۆمپانیا'),(9,'delete_company','سڕینەوەی کۆمپانیا'),(10,'add_purchase','زیادکردنی کڕین'),(11,'edit_purchase','دەستکاری کڕین'),(12,'delete_purchase','سڕینەوەی کڕین'),(13,'view_purchase','بینینی کڕینەکان'),(14,'add_debt','زیادکردنی دانەوەی قەرز'),(15,'update_debt','دەستکاری دانەوەی قەرز'),(16,'delete_debt','سڕینەوەی دانەوەی قەرز'),(17,'view_debt','بینینی مێژووی دانەوەی قەرز'),(18,'add_employee','زیادکردنی کارمەند'),(19,'edit_employee','دەستکاری کارمەند'),(20,'delete_employee','سڕینەوەی کارمەند'),(21,'view_employee','بینینی لیستی کارمەندەکان'),(22,'view_employee_payment','بینینی پارەدان بە کارمەند'),(23,'add_payment','زیادکردنی پارەدان بە کارمەند'),(24,'delete_payment','سڕینەوەی پارەدان بە کارمەند'),(25,'edit_payment','دەستکاری پارەدان بە کارمەند'),(26,'view_car','بینینی سەیارەکان'),(27,'edit_car','دەستکاری سەیارەکان'),(28,'delete_car','سڕینەوەی سەیارەکان'),(29,'add_car','زیادکردنی سەیارە'),(30,'view_person_other_expenses','بینینی لیستی کەسانی خەرجی تر'),(31,'delete_person_other_expenses','سڕینەوەی کەسانی خەرجی تر'),(32,'edit_person_other_expenses','دەستکاری کەسانی خەرجی تر'),(33,'update_person_other_expenses','نوێکردنەوەی کەسانی خەرجی تر'),(34,'view_person_other_expenses_profile','بینینی پرۆفایلی کەس'),(35,'delete_person_other_expenses_profile','سڕینەوەی پرۆفایلی کەس'),(36,'edit_person_other_expenses_profile','دەستکاری پرۆفایلی کەس'),(37,'update_person_other_expenses_profile','نوێکردنەوەی پرۆفایلی کەس'),(38,'view_materials','بینینی لیستی مەواد'),(39,'view_accounts','بینینی هەژمارەکان'),(40,'view_vouchers','بینینی پسوڵەکان'),(54,'view_other_expenses','بینینی لیستی خەرجی تر'),(55,'add_other_expenses','زیادکردنی خەرجی تر'),(56,'edit_other_expenses','دەستکاری خەرجی تر'),(57,'delete_other_expenses','سڕینەوەی خەرجی تر'),(58,'view_concrete_formulas','بینینی فۆرمولای کۆنکرێت'),(59,'add_concrete_formulas','زیادکردنی فۆرمولای کۆنکرێت'),(60,'edit_concrete_formulas','دەستکاری فۆرمولای کۆنکرێت'),(61,'delete_concrete_formulas','سڕینەوەی فۆرمولای کۆنکرێت'),(62,'view_sale','بینینی فرۆشتن'),(63,'edit_sale','دەستکاری فرۆشتن'),(64,'delete_sale','سڕینەوەی فرۆشتن'),(65,'update_sale','نوێکردنەوەی فرۆشتن'),(66,'view_customer','بینینی کڕیار'),(67,'add_customer','زیادکردنی کڕیار'),(68,'delete_customer','سڕینەوەی کڕیار'),(69,'update_customer','نوێکردنەوەی کڕیار'),(70,'add_sale','زیادکردنی فرۆشتن'),(71,'view_concrete_receipts','بینینی پسوڵەی کۆنکرێت'),(72,'add_concrete_receipts','زیادکردنی پسوڵەی کۆنکرێت'),(73,'edit_concrete_receipts','دەستکاری پسوڵەی کۆنکرێت'),(74,'delete_concrete_receipts','سڕینەوەی پسوڵەی کۆنکرێت'),(75,'print_concrete_receipts','چاپکردنی پسوڵەی کۆنکرێت'),(76,'view_income_from_cars','بینینی داهاتی سەیارەکان'),(77,'view_reports','بینینی راپۆرتەکان'),(78,'view_notifications','بینینی ئاگادارکردنەوەکان'),(79,'view_summery_concrete_receipts','بینینی پوختەی پسووڵەکانی کۆنکرێت'),(80,'view_bins_silos','بینینی بین/سایلۆکان'),(81,'view_cash_box','بینینی قاسەکە'),(82,'view_notes','بینینی تێبینیەکان'),(83,'add_notes','زیادکردنی تێبینیەکان'),(84,'update_notes','نوێکردنەوەی تێبینیەکان'),(85,'delete_notes','سڕینەوەی تێبینیەکان'),(86,'mark_notes_read','خوێندنی تێبینیەکان'),(87,'view_concrete_prices','بینینی نرخەکانی کۆنکرێت'),(88,'set_concrete_prices','دانانی نرخی کۆنکرێت'),(89,'edit_concrete_prices','دەستکاری نرخی کۆنکرێت'),(90,'show_add_sale_button','نیشاندانی دووگمەی زیادکردنی فرۆشتن');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `person_other_expenses_debt_payments`
--

DROP TABLE IF EXISTS `person_other_expenses_debt_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `person_other_expenses_debt_payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `person_id` int DEFAULT NULL,
  `date` date DEFAULT NULL,
  `amount_usd` decimal(15,2) DEFAULT '0.00',
  `amount_iqd` decimal(15,2) DEFAULT '0.00',
  `note` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `person_id` (`person_id`),
  CONSTRAINT `person_other_expenses_debt_payments_ibfk_1` FOREIGN KEY (`person_id`) REFERENCES `other_expense_persons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `person_other_expenses_debt_payments`
--

LOCK TABLES `person_other_expenses_debt_payments` WRITE;
/*!40000 ALTER TABLE `person_other_expenses_debt_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `person_other_expenses_debt_payments` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_after_insert_person_other_expenses_debt_payments` AFTER INSERT ON `person_other_expenses_debt_payments` FOR EACH ROW BEGIN
    -- بۆ دۆلار
    IF NEW.amount_usd > 0 THEN
        INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
        VALUES (NEW.date, 'withdraw', 0, NEW.amount_usd, 'دۆلار', CONCAT('گەڕاندنەوەی قەرزی کەسانی تر: ', NEW.person_id), NULL);
    END IF;
    -- بۆ دینار
    IF NEW.amount_iqd > 0 THEN
        INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
        VALUES (NEW.date, 'withdraw', NEW.amount_iqd, 0, 'دینار', CONCAT('گەڕاندنەوەی قەرزی کەسانی تر: ', NEW.person_id), NULL);
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_before_update_person_other_expenses_debt_payments` BEFORE UPDATE ON `person_other_expenses_debt_payments` FOR EACH ROW BEGIN
    IF OLD.amount_usd > 0 THEN
        DELETE FROM cash_box
        WHERE `date` = OLD.date AND `type` = 'withdraw' AND amount_usd = OLD.amount_usd AND currency = 'دۆلار' AND note = CONCAT('گەڕاندنەوەی قەرزی کەسانی تر: ', OLD.person_id);
    END IF;
    IF OLD.amount_iqd > 0 THEN
        DELETE FROM cash_box
        WHERE `date` = OLD.date AND `type` = 'withdraw' AND amount_iqd = OLD.amount_iqd AND currency = 'دینار' AND note = CONCAT('گەڕاندنەوەی قەرزی کەسانی تر: ', OLD.person_id);
    END IF;
    IF NEW.amount_usd > 0 THEN
        INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
        VALUES (NEW.date, 'withdraw', 0, NEW.amount_usd, 'دۆلار', CONCAT('گەڕاندنەوەی قەرزی کەسانی تر: ', NEW.person_id), NULL);
    END IF;
    IF NEW.amount_iqd > 0 THEN
        INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
        VALUES (NEW.date, 'withdraw', NEW.amount_iqd, 0, 'دینار', CONCAT('گەڕاندنەوەی قەرزی کەسانی تر: ', NEW.person_id), NULL);
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_before_delete_person_other_expenses_debt_payments` BEFORE DELETE ON `person_other_expenses_debt_payments` FOR EACH ROW BEGIN
    IF OLD.amount_usd > 0 THEN
        DELETE FROM cash_box
        WHERE `date` = OLD.date AND `type` = 'withdraw' AND amount_usd = OLD.amount_usd AND currency = 'دۆلار' AND note = CONCAT('گەڕاندنەوەی قەرزی کەسانی تر: ', OLD.person_id);
    END IF;
    IF OLD.amount_iqd > 0 THEN
        DELETE FROM cash_box
        WHERE `date` = OLD.date AND `type` = 'withdraw' AND amount_iqd = OLD.amount_iqd AND currency = 'دینار' AND note = CONCAT('گەڕاندنەوەی قەرزی کەسانی تر: ', OLD.person_id);
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `purchase_materials`
--

DROP TABLE IF EXISTS `purchase_materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_materials` (
  `id` int NOT NULL AUTO_INCREMENT,
  `receipt_number` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `material_id` int NOT NULL,
  `person_id` int NOT NULL,
  `unit_type` enum('کارتۆن','دانە','بەرمیل','دەبە','لیتر') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'دانە',
  `quantity` decimal(15,2) NOT NULL DEFAULT '0.00',
  `transfer_loss` decimal(15,2) NOT NULL DEFAULT '0.00',
  `other_loss` decimal(15,2) NOT NULL DEFAULT '0.00',
  `usd_to_iqd_rate` decimal(15,2) NOT NULL DEFAULT '0.00',
  `price_per_unit_usd` decimal(15,2) DEFAULT '0.00',
  `price_per_unit_iqd` decimal(15,2) DEFAULT '0.00',
  `total_price_usd` decimal(15,2) DEFAULT '0.00',
  `total_price_iqd` decimal(15,2) DEFAULT '0.00',
  `currency_type` enum('دینار','دۆلار') COLLATE utf8mb4_general_ci DEFAULT 'دینار',
  `purchase_date` date NOT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `base_quantity` decimal(15,2) DEFAULT '0.00' COMMENT 'بڕی بنەڕەتی بە دانە',
  `base_price_per_unit_usd` decimal(15,2) DEFAULT '0.00' COMMENT 'نرخی بنەڕەتی بە دانە بە دۆلار',
  `base_price_per_unit_iqd` decimal(15,2) DEFAULT '0.00' COMMENT 'نرخی بنەڕەتی بە دانە بە دینار',
  `payment_type` enum('نەقد','قەرز') COLLATE utf8mb4_general_ci DEFAULT 'نەقد',
  `paid_amount_usd` decimal(15,2) DEFAULT '0.00',
  `paid_amount_iqd` decimal(15,2) DEFAULT '0.00',
  `remaining_amount_usd` decimal(15,2) DEFAULT '0.00',
  `remaining_amount_iqd` decimal(15,2) DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `material_id` (`material_id`),
  KEY `person_id` (`person_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_receipt_number` (`receipt_number`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_materials`
--

LOCK TABLES `purchase_materials` WRITE;
/*!40000 ALTER TABLE `purchase_materials` DISABLE KEYS */;
INSERT INTO `purchase_materials` VALUES (20,'KR-0001',42,35,'دانە',2.00,0.00,0.00,139400.00,0.00,8000.00,0.00,16000.00,'دینار','2025-08-02','',2,'2025-08-05 10:27:16','2025-08-05 10:27:16',2.00,0.00,8000.00,'نەقد',0.00,0.00,0.00,0.00);
/*!40000 ALTER TABLE `purchase_materials` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `after_purchase_materials_insert` AFTER INSERT ON `purchase_materials` FOR EACH ROW BEGIN
    DECLARE current_balance_usd DECIMAL(20,2) DEFAULT 0;
    DECLARE dollar_rate DECIMAL(20,2) DEFAULT 150000;
    DECLARE withdrawal_usd DECIMAL(20,2) DEFAULT 0;
    
    -- Update the quantity in list_materials by adding the purchased base quantity
    UPDATE list_materials 
    SET quantity = quantity + NEW.base_quantity
    WHERE id = NEW.material_id;
    
    -- If it's a cash payment, withdraw money from cash_box
    IF NEW.payment_type = 'نەقد' THEN
        -- Check if there's enough money in cash_box before withdrawing
        -- Get dollar rate from settings
        SELECT CAST(value AS DECIMAL(20,2)) INTO dollar_rate FROM settings WHERE name = 'usd_iqd_rate' LIMIT 1;
        
        -- Calculate current cash box balance in USD
        SELECT
            IFNULL(SUM(
                CASE
                    WHEN currency = 'دۆلار' AND type = 'deposit' THEN amount_usd
                    WHEN currency = 'دۆلار' AND type = 'withdraw' THEN -amount_usd
                    WHEN currency = 'دینار' AND type = 'deposit' THEN amount_iqd / dollar_rate
                    WHEN currency = 'دینار' AND type = 'withdraw' THEN -amount_iqd / dollar_rate
                    ELSE 0
                END
            ), 0)
        INTO current_balance_usd
        FROM cash_box;
        
        -- Calculate the withdrawal amount in USD (use total price, not paid amount)
        IF NEW.currency_type = 'دۆلار' THEN
            SET withdrawal_usd = NEW.total_price_usd;
        ELSE
            SET withdrawal_usd = NEW.total_price_iqd / dollar_rate;
        END IF;
        
        -- Check if there's enough balance
        IF (current_balance_usd - withdrawal_usd) < 0 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'قاسەی گشتی بەردەست نییە بۆ ئەم کڕینە!';
        END IF;
        
        -- Proceed with withdrawal (use total price, not paid amount)
        INSERT INTO cash_box (date, type, amount_usd, amount_iqd, currency, note, created_by)
        VALUES (
            NEW.purchase_date,
            'withdraw',
            NEW.total_price_usd,
            NEW.total_price_iqd,
            NEW.currency_type,
            CONCAT('پارەدانی کڕینی ', NEW.receipt_number),
            NEW.created_by
        );
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `after_purchase_materials_update` AFTER UPDATE ON `purchase_materials` FOR EACH ROW BEGIN
    DECLARE current_balance_usd DECIMAL(20,2) DEFAULT 0;
    DECLARE dollar_rate DECIMAL(20,2) DEFAULT 150000;
    DECLARE withdrawal_usd DECIMAL(20,2) DEFAULT 0;
    
    -- First, subtract the old base quantity from the material
    UPDATE list_materials 
    SET quantity = quantity - OLD.base_quantity
    WHERE id = OLD.material_id;
    
    -- Then, add the new base quantity to the material
    UPDATE list_materials 
    SET quantity = quantity + NEW.base_quantity
    WHERE id = NEW.material_id;
    
    -- Handle cash box changes based on payment type changes
    IF OLD.payment_type = 'نەقد' AND NEW.payment_type = 'قەرز' THEN
        -- Changed from cash to credit, return money to cash_box (use total price, not paid amount)
        INSERT INTO cash_box (date, type, amount_usd, amount_iqd, currency, note, created_by)
        VALUES (
            NEW.purchase_date,
            'deposit',
            OLD.total_price_usd,
            OLD.total_price_iqd,
            OLD.currency_type,
            CONCAT('گەڕاندنەوەی پارەی کڕینی ', NEW.receipt_number, ' (گۆڕانکاری بۆ قەرز)'),
            NEW.created_by
        );
    ELSEIF OLD.payment_type = 'قەرز' AND NEW.payment_type = 'نەقد' THEN
        -- Changed from credit to cash, check balance and withdraw money from cash_box
        -- Get dollar rate from settings
        SELECT CAST(value AS DECIMAL(20,2)) INTO dollar_rate FROM settings WHERE name = 'usd_iqd_rate' LIMIT 1;
        
        -- Calculate current cash box balance in USD
        SELECT
            IFNULL(SUM(
                CASE
                    WHEN currency = 'دۆلار' AND type = 'deposit' THEN amount_usd
                    WHEN currency = 'دۆلار' AND type = 'withdraw' THEN -amount_usd
                    WHEN currency = 'دینار' AND type = 'deposit' THEN amount_iqd / dollar_rate
                    WHEN currency = 'دینار' AND type = 'withdraw' THEN -amount_iqd / dollar_rate
                    ELSE 0
                END
            ), 0)
        INTO current_balance_usd
        FROM cash_box;
        
        -- Calculate the withdrawal amount in USD (use total price, not paid amount)
        IF NEW.currency_type = 'دۆلار' THEN
            SET withdrawal_usd = NEW.total_price_usd;
        ELSE
            SET withdrawal_usd = NEW.total_price_iqd / dollar_rate;
        END IF;
        
        -- Check if there's enough balance
        IF (current_balance_usd - withdrawal_usd) < 0 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'قاسەی گشتی بەردەست نییە بۆ ئەم کڕینە!';
        END IF;
        
        -- Proceed with withdrawal (use total price, not paid amount)
        INSERT INTO cash_box (date, type, amount_usd, amount_iqd, currency, note, created_by)
        VALUES (
            NEW.purchase_date,
            'withdraw',
            NEW.total_price_usd,
            NEW.total_price_iqd,
            NEW.currency_type,
            CONCAT('پارەدانی کڕینی ', NEW.receipt_number, ' (گۆڕانکاری بۆ نەقد)'),
            NEW.created_by
        );
    ELSEIF OLD.payment_type = 'نەقد' AND NEW.payment_type = 'نەقد' THEN
        -- Both are cash, check if paid amounts changed
        IF OLD.paid_amount_usd != NEW.paid_amount_usd OR OLD.paid_amount_iqd != NEW.paid_amount_iqd THEN
            -- Calculate the difference
            IF (NEW.paid_amount_usd - OLD.paid_amount_usd) > 0 OR (NEW.paid_amount_iqd - OLD.paid_amount_iqd) > 0 THEN
                -- More money was paid, check balance and withdraw the difference
                -- Get dollar rate from settings
                SELECT CAST(value AS DECIMAL(20,2)) INTO dollar_rate FROM settings WHERE name = 'usd_iqd_rate' LIMIT 1;
                
                -- Calculate current cash box balance in USD
                SELECT
                    IFNULL(SUM(
                        CASE
                            WHEN currency = 'دۆلار' AND type = 'deposit' THEN amount_usd
                            WHEN currency = 'دۆلار' AND type = 'withdraw' THEN -amount_usd
                            WHEN currency = 'دینار' AND type = 'deposit' THEN amount_iqd / dollar_rate
                            WHEN currency = 'دینار' AND type = 'withdraw' THEN -amount_iqd / dollar_rate
                            ELSE 0
                        END
                    ), 0)
                INTO current_balance_usd
                FROM cash_box;
                
                -- Calculate the additional withdrawal amount in USD
                IF NEW.currency_type = 'دۆلار' THEN
                    SET withdrawal_usd = (NEW.paid_amount_usd - OLD.paid_amount_usd);
                ELSE
                    SET withdrawal_usd = (NEW.paid_amount_iqd - OLD.paid_amount_iqd) / dollar_rate;
                END IF;
                
                -- Check if there's enough balance
                IF (current_balance_usd - withdrawal_usd) < 0 THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'قاسەی گشتی بەردەست نییە بۆ زیادکردنی پارەدانی!';
                END IF;
                
                -- Proceed with withdrawal
                INSERT INTO cash_box (date, type, amount_usd, amount_iqd, currency, note, created_by)
                VALUES (
                    NEW.purchase_date,
                    'withdraw',
                    (NEW.paid_amount_usd - OLD.paid_amount_usd),
                    (NEW.paid_amount_iqd - OLD.paid_amount_iqd),
                    NEW.currency_type,
                    CONCAT('زیادکردنی پارەدانی کڕینی ', NEW.receipt_number),
                    NEW.created_by
                );
            ELSEIF (NEW.paid_amount_usd - OLD.paid_amount_usd) < 0 OR (NEW.paid_amount_iqd - OLD.paid_amount_iqd) < 0 THEN
                -- Less money was paid, return the difference
                INSERT INTO cash_box (date, type, amount_usd, amount_iqd, currency, note, created_by)
                VALUES (
                    NEW.purchase_date,
                    'deposit',
                    ABS(NEW.paid_amount_usd - OLD.paid_amount_usd),
                    ABS(NEW.paid_amount_iqd - OLD.paid_amount_iqd),
                    NEW.currency_type,
                    CONCAT('کەمکردنی پارەدانی کڕینی ', NEW.receipt_number),
                    NEW.created_by
                );
            END IF;
        END IF;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `after_purchase_materials_delete` AFTER DELETE ON `purchase_materials` FOR EACH ROW BEGIN
    -- Update the quantity in list_materials by subtracting the deleted base quantity
    UPDATE list_materials 
    SET quantity = quantity - OLD.base_quantity
    WHERE id = OLD.material_id;
    
    -- If it was a cash payment, return the money to cash_box (use total price, not paid amount)
    IF OLD.payment_type = 'نەقد' THEN
        INSERT INTO cash_box (date, type, amount_usd, amount_iqd, currency, note, created_by)
        VALUES (
            OLD.purchase_date,
            'deposit',
            OLD.total_price_usd,
            OLD.total_price_iqd,
            OLD.currency_type,
            CONCAT('گەڕاندنەوەی پارەی کڕینی ', OLD.receipt_number),
            OLD.created_by
        );
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `purchases`
--

DROP TABLE IF EXISTS `purchases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchases` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `invoice_number` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `driver` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `location` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `material_id` int NOT NULL,
  `kg` decimal(10,2) DEFAULT '0.00',
  `price` decimal(10,2) NOT NULL,
  `payment_type` enum('نەقد','قەرز') COLLATE utf8mb4_general_ci NOT NULL,
  `exchange_rate` decimal(10,0) DEFAULT '150000',
  `company_id` int NOT NULL,
  `type` enum('دینار','دۆلار') COLLATE utf8mb4_general_ci NOT NULL,
  `paid_usd` decimal(10,0) DEFAULT '0',
  `paid_iqd` decimal(10,0) DEFAULT '0',
  `remaining_usd` decimal(10,2) DEFAULT '0.00',
  `remaining_iqd` decimal(10,0) DEFAULT NULL,
  `bin_id` int DEFAULT NULL,
  `amount_iqd` decimal(12,2) DEFAULT '0.00',
  `price_per_kg_iqd` decimal(10,2) DEFAULT '0.00',
  `price_per_kg_usd` decimal(10,2) DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `idx_bin_id` (`bin_id`),
  KEY `fk_purchases_company` (`company_id`),
  CONSTRAINT `fk_purchases_bin` FOREIGN KEY (`bin_id`) REFERENCES `bins_silos` (`id`),
  CONSTRAINT `fk_purchases_company` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=78 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchases`
--

LOCK TABLES `purchases` WRITE;
/*!40000 ALTER TABLE `purchases` DISABLE KEYS */;
INSERT INTO `purchases` VALUES (41,'2025-08-01','50355','ڕامیار جلال','لاڤارج',3,31050.00,2002.73,'قەرز',139400,3,'دۆلار',0,0,2002.73,0,5,0.00,0.00,64.50),(42,'2025-08-01','503562','ئاوات','لاڤارج',3,31150.00,2009.17,'قەرز',139400,3,'دۆلار',0,0,2009.17,0,5,0.00,0.00,64.50),(43,'2025-08-02','503689','ئاوات','لاڤارج',3,31050.00,2002.73,'قەرز',139400,3,'دۆلار',0,0,2002.73,0,5,0.00,0.00,64.50),(44,'2025-08-02','82515723','دیلان  کمال','ماس',3,31600.00,2006.60,'قەرز',139400,4,'دۆلار',0,0,2006.60,0,6,0.00,0.00,63.50),(45,'2025-08-02','82515808','دیلان  کمال','ماس',3,31680.00,2011.68,'قەرز',139400,4,'دۆلار',0,0,2011.68,0,6,0.00,0.00,63.50),(46,'2025-08-03','503902','ڕامیار جلال','لاڤارج',3,31100.00,2005.95,'قەرز',139400,3,'دۆلار',0,0,2005.95,0,5,0.00,0.00,64.50),(47,'2025-08-03','83516201','کارزان کمال','ماس',3,39420.00,2503.17,'قەرز',139400,4,'دۆلار',0,0,2503.17,0,6,0.00,0.00,63.50),(48,'2025-08-03','83516262','کارزان کمال','ماس',3,39280.00,2494.28,'قەرز',139400,4,'دۆلار',0,0,2494.28,0,6,0.00,0.00,63.50),(50,'2025-08-02','2','کاک عیماد شۆفێر','کەسارەکەی خۆمان',2,36400.00,0.00,'قەرز',139400,12,'دینار',0,0,0.00,212000,4,212321.20,5833.00,0.00),(51,'2025-08-02','1','کاک عیماد شۆفێر','کەسارەکەی خۆمان',2,36400.00,0.00,'قەرز',139400,12,'دینار',0,0,0.00,212000,4,212321.20,5833.00,0.00),(52,'2025-08-02','1426','سۆران','کارگە و چەو و لمی عطا',2,30325.00,0.00,'قەرز',139400,2,'دینار',0,0,0.00,176000,3,176885.73,5833.00,0.00),(53,'2025-08-02','1105','کاک دەشتی','کەنجاڕە',1,35280.00,0.00,'قەرز',139400,1,'دینار',0,0,0.00,361000,2,361620.00,10250.00,0.00),(54,'2025-08-02','1103','داستان علی','کەنجاڕە',1,33680.00,0.00,'قەرز',139400,1,'دینار',0,0,0.00,345000,2,345220.00,10250.00,0.00),(55,'2025-08-03','5118','کاک عیماد شۆفێر','غەسالەی نەورۆز',2,32580.00,0.00,'قەرز',139400,6,'دینار',0,0,0.00,190000,3,190039.14,5833.00,0.00),(56,'2025-08-03','1434','سۆران','کارگە و چەو و لمی عطا',2,30435.00,0.00,'قەرز',139400,2,'دینار',0,0,0.00,177000,3,177527.35,5833.00,0.00),(57,'2025-08-03','1107','داستان علی','کەنجاڕە',1,34220.00,0.00,'قەرز',139400,1,'دینار',0,0,0.00,350000,2,350755.00,10250.00,0.00),(58,'2025-08-03','1108','داستان علی','کەنجاڕە',1,33950.00,0.00,'قەرز',139400,1,'دینار',0,0,0.00,347000,2,347987.50,10250.00,0.00),(59,'2025-08-03','1109','کاک دەشتی','کەنجاڕە',1,33320.00,0.00,'قەرز',139400,1,'دینار',0,0,0.00,341000,2,341530.00,10250.00,0.00),(60,'2025-08-03','3','کاک عیماد شۆفێر','کەسارەکەی خۆمان',5,42000.00,0.00,'قەرز',139400,12,'دینار',0,0,0.00,420000,1,420000.00,10000.00,0.00),(61,'2025-08-03','4','کاک عیماد شۆفێر','کەسارەکەی خۆمان',5,42000.00,0.00,'قەرز',139400,12,'دینار',0,0,0.00,420000,1,420000.00,10000.00,0.00),(62,'2025-08-04','1110','داستان علی','کەنجاڕە',1,34220.00,0.00,'قەرز',139400,1,'دینار',0,0,0.00,350000,2,350755.00,10250.00,0.00),(63,'2025-08-04','1111','داستان علی','کەنجاڕە',1,30480.00,0.00,'قەرز',139400,1,'دینار',0,0,0.00,312000,2,312420.00,10250.00,0.00),(64,'2025-08-04','1112','محمد علی','کەنجاڕە',1,32200.00,0.00,'قەرز',139400,1,'دینار',0,0,0.00,330000,2,330050.00,10250.00,0.00),(65,'2025-08-04','1437','سۆران','کارگە و چەو و لمی عطا',2,30225.00,0.00,'قەرز',139400,2,'دینار',0,0,0.00,176000,3,176302.43,5833.00,0.00),(66,'2025-08-04','5144','کاک عیماد شۆفێر','غەسالەی نەورۆز',2,32500.00,0.00,'قەرز',139400,6,'دینار',0,0,0.00,189000,3,189572.50,5833.00,0.00),(67,'2025-08-04','5','کاک عیماد شۆفێر','کەسارەکەی خۆمان',5,42000.00,0.00,'قەرز',139400,12,'دینار',0,0,0.00,420000,1,420000.00,10000.00,0.00),(68,'2025-08-04','6','کاک عیماد شۆفێر','کەسارەکەی خۆمان',5,42000.00,0.00,'قەرز',139400,12,'دینار',0,0,0.00,420000,1,420000.00,10000.00,0.00),(69,'2025-08-04','1113','شاخەوان علی حاجی','کەنجاڕە',1,32730.00,0.00,'قەرز',139400,1,'دینار',0,0,0.00,335000,2,335482.50,10250.00,0.00),(70,'2025-08-04','1114','ڕەنجە','کەنجاڕە',1,32620.00,0.00,'قەرز',139400,1,'دینار',0,0,0.00,334000,2,334355.00,10250.00,0.00),(71,'2025-08-04','1115','مەتین','کەنجاڕە',1,31120.00,0.00,'قەرز',139400,1,'دینار',0,0,0.00,318000,2,318980.00,10250.00,0.00),(73,'2025-08-05','1442','سۆران','کارگە و چەو و لمی عطا',2,30855.00,0.00,'قەرز',139400,2,'دینار',0,0,0.00,179000,3,179977.21,5833.00,0.00),(74,'2025-08-05','5148','کاک عیماد شۆفێر','غەسالەی نەورۆز',2,32300.00,0.00,'قەرز',139400,6,'دینار',0,0,0.00,188000,3,188405.90,5833.00,0.00),(77,'2025-08-05','1116','هاوکار','کەنجاڕە',1,32000.00,0.00,'قەرز',139400,1,'دینار',0,0,0.00,328000,2,328000.00,10250.00,0.00);
/*!40000 ALTER TABLE `purchases` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_after_insert_purchase_cash_box` AFTER INSERT ON `purchases` FOR EACH ROW BEGIN
    IF NEW.payment_type = 'نەقد' THEN
        IF NEW.type = 'دۆلار' THEN
            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NEW.date, 'withdraw', 0, NEW.paid_usd, 'دۆلار', CONCAT('کڕین: invoice ', NEW.invoice_number), NULL);
        ELSE
            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NEW.date, 'withdraw', NEW.paid_iqd, 0, 'دینار', CONCAT('کڕین: invoice ', NEW.invoice_number), NULL);
        END IF;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `after_purchase_insert` AFTER INSERT ON `purchases` FOR EACH ROW BEGIN
  DECLARE total_value_add DECIMAL(20,6);

  IF NEW.type = 'دینار' THEN
    SET total_value_add = (NEW.kg / 1000) * NEW.price_per_kg_iqd;
  ELSEIF NEW.type = 'دۆلار' THEN
    SET total_value_add = (NEW.kg / 1000) * NEW.price_per_kg_usd;
  ELSE
    SET total_value_add = 0;
  END IF;

  IF NEW.bin_id IS NOT NULL THEN
    UPDATE bins_silos
    SET
      amount = amount + NEW.kg,
      total_value = total_value + total_value_add
    WHERE id = NEW.bin_id;

    -- average_price بۆ هەر ١kg
    UPDATE bins_silos
    SET
      average_price = 
        CASE 
          WHEN amount > 0 
          THEN total_value / amount
          ELSE 0
        END
    WHERE id = NEW.bin_id;
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_before_update_purchase_cash_box` BEFORE UPDATE ON `purchases` FOR EACH ROW BEGIN
    IF OLD.payment_type = 'نەقد' THEN
        IF OLD.type = 'دۆلار' THEN
            DELETE FROM cash_box
            WHERE `date` = OLD.date AND `type` = 'withdraw' AND amount_usd = OLD.paid_usd AND currency = 'دۆلار' AND note = CONCAT('کڕین: invoice ', OLD.invoice_number);
        ELSE
            DELETE FROM cash_box
            WHERE `date` = OLD.date AND `type` = 'withdraw' AND amount_iqd = OLD.paid_iqd AND currency = 'دینار' AND note = CONCAT('کڕین: invoice ', OLD.invoice_number);
        END IF;
    END IF;
    IF NEW.payment_type = 'نەقد' THEN
        IF NEW.type = 'دۆلار' THEN
            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NEW.date, 'withdraw', 0, NEW.paid_usd, 'دۆلار', CONCAT('کڕین: invoice ', NEW.invoice_number), NULL);
        ELSE
            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NEW.date, 'withdraw', NEW.paid_iqd, 0, 'دینار', CONCAT('کڕین: invoice ', NEW.invoice_number), NULL);
        END IF;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `after_purchase_update` AFTER UPDATE ON `purchases` FOR EACH ROW BEGIN
  DECLARE old_total_value DECIMAL(20,6);
  DECLARE new_total_value DECIMAL(20,6);

  -- هەژمارکردنی بڕی کۆن
  IF OLD.type = 'دینار' THEN
    SET old_total_value = (OLD.kg / 1000) * OLD.price_per_kg_iqd;
  ELSEIF OLD.type = 'دۆلار' THEN
    SET old_total_value = (OLD.kg / 1000) * OLD.price_per_kg_usd;
  ELSE
    SET old_total_value = 0;
  END IF;

  -- هەژمارکردنی بڕی نوێ
  IF NEW.type = 'دینار' THEN
    SET new_total_value = (NEW.kg / 1000) * NEW.price_per_kg_iqd;
  ELSEIF NEW.type = 'دۆلار' THEN
    SET new_total_value = (NEW.kg / 1000) * NEW.price_per_kg_usd;
  ELSE
    SET new_total_value = 0;
  END IF;

  -- گەڕاندنەوەی بڕی کۆن بۆ stock
  IF OLD.bin_id IS NOT NULL THEN
    UPDATE bins_silos
    SET
      amount = amount - OLD.kg,
      total_value = total_value - old_total_value
    WHERE id = OLD.bin_id;

    UPDATE bins_silos
    SET
      average_price = 
        CASE 
          WHEN amount > 0 
          THEN total_value / amount
          ELSE 0
        END
    WHERE id = OLD.bin_id;
  END IF;

  -- زیادکردنی بڕی نوێ بۆ stock
  IF NEW.bin_id IS NOT NULL THEN
    UPDATE bins_silos
    SET
      amount = amount + NEW.kg,
      total_value = total_value + new_total_value
    WHERE id = NEW.bin_id;

    UPDATE bins_silos
    SET
      average_price = 
        CASE 
          WHEN amount > 0 
          THEN total_value / amount
          ELSE 0
        END
    WHERE id = NEW.bin_id;
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_before_delete_purchase_cash_box` BEFORE DELETE ON `purchases` FOR EACH ROW BEGIN
    IF OLD.payment_type = 'نەقد' THEN
        IF OLD.type = 'دۆلار' THEN
            DELETE FROM cash_box
            WHERE `date` = OLD.date AND `type` = 'withdraw' AND amount_usd = OLD.paid_usd AND currency = 'دۆلار' AND note = CONCAT('کڕین: invoice ', OLD.invoice_number);
        ELSE
            DELETE FROM cash_box
            WHERE `date` = OLD.date AND `type` = 'withdraw' AND amount_iqd = OLD.paid_iqd AND currency = 'دینار' AND note = CONCAT('کڕین: invoice ', OLD.invoice_number);
        END IF;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `after_purchase_delete` AFTER DELETE ON `purchases` FOR EACH ROW BEGIN
  DECLARE total_value_sub DECIMAL(20,6);

  IF OLD.type = 'دینار' THEN
    SET total_value_sub = (OLD.kg / 1000) * OLD.price_per_kg_iqd;
  ELSEIF OLD.type = 'دۆلار' THEN
    SET total_value_sub = (OLD.kg / 1000) * OLD.price_per_kg_usd;
  ELSE
    SET total_value_sub = 0;
  END IF;

  IF OLD.bin_id IS NOT NULL THEN
    UPDATE bins_silos
    SET
      amount = amount - OLD.kg,
      total_value = total_value - total_value_sub
    WHERE id = OLD.bin_id;

    UPDATE bins_silos
    SET
      average_price = 
        CASE 
          WHEN amount > 0 
          THEN total_value / amount
          ELSE 0
        END
    WHERE id = OLD.bin_id;
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `recycle_bin_purchases`
--

DROP TABLE IF EXISTS `recycle_bin_purchases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recycle_bin_purchases` (
  `id` int NOT NULL AUTO_INCREMENT,
  `original_id` int NOT NULL,
  `date` date NOT NULL,
  `invoice_number` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `driver` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `location` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `material_id` int NOT NULL,
  `kg` decimal(10,2) DEFAULT '0.00',
  `price` decimal(10,2) NOT NULL,
  `payment_type` enum('نەقد','قەرز') COLLATE utf8mb4_general_ci NOT NULL,
  `exchange_rate` decimal(10,0) DEFAULT '150000',
  `company_id` int NOT NULL,
  `type` enum('دینار','دۆلار') COLLATE utf8mb4_general_ci NOT NULL,
  `paid_usd` decimal(10,0) DEFAULT '0',
  `paid_iqd` decimal(10,0) DEFAULT '0',
  `remaining_usd` decimal(10,2) DEFAULT '0.00',
  `remaining_iqd` decimal(10,0) DEFAULT NULL,
  `bin_id` int DEFAULT NULL,
  `amount_iqd` decimal(12,2) DEFAULT '0.00',
  `price_per_kg_iqd` decimal(10,2) DEFAULT '0.00',
  `price_per_kg_usd` decimal(10,2) DEFAULT '0.00',
  `deleted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recycle_bin_purchases`
--

LOCK TABLES `recycle_bin_purchases` WRITE;
/*!40000 ALTER TABLE `recycle_bin_purchases` DISABLE KEYS */;
INSERT INTO `recycle_bin_purchases` VALUES (47,72,'2025-08-05','1116','هاوکار','کەنجاڕە',1,32000.00,0.00,'قەرز',139400,1,'دینار',0,0,0.00,328000,2,328000.00,10250.00,0.00,'2025-08-05 09:34:11'),(48,75,'2025-08-04','1116','هاوکار','کەنجاڕە',1,32000.00,0.00,'قەرز',139400,1,'دینار',0,0,0.00,328000,2,328000.00,10250.00,0.00,'2025-08-05 09:37:51'),(49,76,'2025-08-04','1116','هاوکار','کەنجاڕە',1,32000.00,0.00,'قەرز',139400,1,'دینار',0,0,0.00,328000,2,328000.00,10250.00,0.00,'2025-08-05 09:39:38');
/*!40000 ALTER TABLE `recycle_bin_purchases` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recycle_bin_sales`
--

DROP TABLE IF EXISTS `recycle_bin_sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recycle_bin_sales` (
  `id` int NOT NULL AUTO_INCREMENT,
  `original_id` int NOT NULL,
  `customer_id` int DEFAULT NULL,
  `recipient` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `quantity` decimal(10,2) DEFAULT NULL,
  `price_per_unit` decimal(10,2) DEFAULT NULL,
  `total_price` decimal(10,2) DEFAULT NULL,
  `payment_type` enum('نەقد','قەرز') COLLATE utf8mb4_general_ci NOT NULL,
  `amount_paid_usd` decimal(10,2) DEFAULT NULL,
  `amount_paid_iq` decimal(10,2) DEFAULT NULL,
  `dolar_rate` decimal(10,2) DEFAULT NULL,
  `remaining_amount` decimal(10,2) DEFAULT NULL,
  `invoice_number` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `order_date` date NOT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `formula_id` int NOT NULL,
  `discount` decimal(10,2) DEFAULT '0.00',
  `deleted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recycle_bin_sales`
--

LOCK TABLES `recycle_bin_sales` WRITE;
/*!40000 ALTER TABLE `recycle_bin_sales` DISABLE KEYS */;
INSERT INTO `recycle_bin_sales` VALUES (33,36,34,'حاكم احمد','پیرەمەگرون',9.00,41.00,369.00,'قەرز',0.00,0.00,139400.00,369.00,'A-0023','2025-08-02','',26,0.00,'2025-08-05 08:23:25');
/*!40000 ALTER TABLE `recycle_bin_sales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_permissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `role` enum('admin','user','accountant','manager') COLLATE utf8mb4_general_ci NOT NULL,
  `permission_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `permission_id` (`permission_id`),
  CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=822 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_permissions`
--

LOCK TABLES `role_permissions` WRITE;
/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
INSERT INTO `role_permissions` VALUES (9,'admin',6),(11,'admin',3),(12,'admin',2),(13,'admin',1),(14,'admin',7),(15,'admin',8),(18,'admin',9),(19,'admin',13),(20,'admin',12),(21,'admin',10),(22,'admin',16),(23,'admin',15),(24,'admin',14),(26,'admin',17),(28,'admin',18),(31,'admin',21),(32,'admin',19),(33,'admin',20),(35,'admin',23),(36,'admin',24),(37,'admin',25),(38,'admin',26),(39,'admin',28),(40,'admin',27),(41,'admin',29),(42,'admin',11),(43,'admin',30),(44,'admin',31),(45,'admin',37),(46,'admin',36),(47,'admin',35),(48,'admin',34),(49,'admin',33),(50,'admin',32),(51,'admin',38),(52,'admin',39),(55,'admin',5),(56,'admin',55),(58,'admin',56),(59,'admin',57),(60,'admin',22),(61,'admin',54),(62,'admin',58),(63,'admin',59),(64,'admin',60),(65,'admin',61),(69,'admin',64),(70,'admin',65),(71,'admin',66),(72,'admin',67),(73,'admin',68),(81,'admin',62),(82,'admin',70),(83,'admin',69),(86,'admin',71),(87,'admin',72),(88,'admin',73),(89,'admin',74),(90,'admin',75),(613,'manager',72),(614,'manager',67),(626,'manager',74),(627,'manager',68),(640,'manager',73),(649,'manager',75),(650,'manager',69),(658,'manager',71),(659,'manager',66),(671,'manager',40),(737,'admin',4),(741,'user',71),(742,'user',75),(744,'admin',78),(745,'user',72),(746,'user',67),(747,'user',73),(748,'user',74),(750,'admin',40),(752,'admin',77),(755,'admin',80),(756,'admin',79),(759,'manager',5),(761,'manager',61),(762,'manager',60),(763,'manager',59),(764,'manager',79),(772,'admin',81),(782,'user',86),(783,'user',82),(784,'manager',85),(785,'manager',84),(786,'manager',83),(787,'manager',82),(788,'user',79),(789,'manager',89),(790,'manager',88),(791,'manager',87),(792,'admin',76),(795,'accountant',5),(797,'accountant',17),(798,'accountant',40),(799,'accountant',54),(800,'accountant',62),(801,'manager',62),(802,'accountant',71),(803,'accountant',72),(804,'accountant',73),(806,'accountant',75),(807,'accountant',79),(809,'accountant',87),(810,'accountant',89),(811,'accountant',88),(813,'accountant',13),(814,'admin',90),(818,'admin',90),(819,'accountant',90),(820,'manager',90),(821,'user',90);
/*!40000 ALTER TABLE `role_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'admin','ئەدمین'),(2,'user','بەکارهێنەر'),(3,'accountant','موحاسیب'),(4,'manager','بەڕێوەبەر');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales`
--

DROP TABLE IF EXISTS `sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales` (
  `id` int NOT NULL AUTO_INCREMENT,
  `customer_id` int DEFAULT NULL,
  `recipient` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `quantity` decimal(10,2) DEFAULT NULL,
  `price_per_unit` decimal(10,2) DEFAULT NULL,
  `total_price` decimal(10,2) DEFAULT NULL,
  `payment_type` enum('نەقد','قەرز') COLLATE utf8mb4_general_ci NOT NULL,
  `amount_paid_usd` decimal(10,2) DEFAULT NULL,
  `amount_paid_iq` decimal(10,2) DEFAULT NULL,
  `dolar_rate` decimal(10,2) DEFAULT NULL,
  `remaining_amount` decimal(10,2) DEFAULT NULL,
  `invoice_number` varchar(500) COLLATE utf8mb4_general_ci NOT NULL,
  `order_date` date NOT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `formula_id` int NOT NULL,
  `discount` decimal(10,2) DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `formula_id` (`formula_id`),
  CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`formula_id`) REFERENCES `concrete_formulas` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales`
--

LOCK TABLES `sales` WRITE;
/*!40000 ALTER TABLE `sales` DISABLE KEYS */;
INSERT INTO `sales` VALUES (28,37,'وەستا قالە','ڕاپەڕین',60.00,45.00,2700.00,'قەرز',0.00,0.00,139400.00,2700.00,'A-0001, A-0002, A-0003, A-0004, A-0005, A-0006','2025-08-02','',27,0.00),(29,37,'وەستا قالە','ڕاپەڕین',8.00,45.00,360.00,'قەرز',0.00,0.00,139400.00,360.00,'A-0013','2025-08-02','',27,0.00),(30,37,'','قه لياسان',6.00,45.00,270.00,'قەرز',0.00,0.00,139400.00,270.00,'A-0040','2025-08-02','',42,0.00),(31,113,'هاورێ مجید','بەختیاری',60.00,45.00,2700.00,'نەقد',2700.00,0.00,139400.00,0.00,'A-0007, A-0008, A-0009, A-0010, A-0011, A-0012','2025-08-02','3150$واصڵ',27,0.00),(33,113,'هاورێ مجید','بەختیاری',10.00,45.00,450.00,'نەقد',450.00,0.00,139400.00,0.00,'A-0014','2025-08-02','3150$واصڵ',27,0.00),(34,105,'ک.تۆفیق','ڕاپەڕین',44.00,47.00,2068.00,'قەرز',0.00,0.00,139400.00,2068.00,'A-0015, A-0016, A-0017, A-0018, A-0020','2025-08-02','',19,0.00),(35,50,'فه رهاد','مه عملى جيمنتو ماس, معمل جيمنتو ماس',16.50,45.00,742.50,'قەرز',0.00,0.00,139400.00,742.50,'A-0019, A-0022','2025-08-02','',27,0.00),(37,117,'وه ستا ديار','ڕاپەڕین',18.50,47.00,869.50,'قەرز',0.00,0.00,139400.00,869.50,'A-0024, A-0025','2025-08-02','',42,0.00),(38,118,'','تافكا ',24.50,46.00,1127.00,'قەرز',0.00,0.00,139400.00,1127.00,'A-0026, A-0027, A-0038','2025-08-02','',42,0.00),(40,145,'و . هیوا ','بەرامبەر حەسەن تەپە',30.00,43.34,1300.20,'نەقد',1300.00,0.00,139400.00,0.00,'A-0042, A-0044, A-0045','2025-08-03','1300 دۆلار واسڵ',26,0.20),(41,146,'ک . ئاسۆ','پیرەمەگرون',13.00,45.00,585.00,'قەرز',0.00,0.00,139400.00,585.00,'A-0043, A-0046','2025-08-03','',27,0.00),(43,107,'','كه له ونان',4.00,45.00,180.00,'قەرز',0.00,0.00,139400.00,180.00,'A-0052, A-0077','2025-08-03','',41,0.00),(44,99,'کاک کاروان ','کێلە سپی',5.00,45.00,225.00,'قەرز',0.00,0.00,139400.00,225.00,'A-0062','2025-08-03','',27,0.00),(45,181,'و. كرمانج','ڕاپەڕین',35.00,45.00,1575.00,'قەرز',0.00,0.00,139400.00,1575.00,'A-0063, A-0066, A-0067, A-0068','2025-08-03','',42,0.00),(46,57,'','دارتو مولانا',47.00,45.00,2115.00,'نەقد',2100.00,0.00,139400.00,0.00,'A-0064, A-0065, A-0069, A-0070, A-0071, A-0073','2025-08-03','2,100$ لای توانای برامە',42,15.00),(47,186,'','پیرەمەگرون',3.00,45.00,135.00,'قەرز',0.00,0.00,139400.00,135.00,'A-0072','2025-08-03','',27,0.00),(48,60,'','پیرەمەگرون',28.00,45.00,1260.00,'نەقد',1200.00,80000.00,139400.00,0.00,'A-0074, A-0075, A-0076','2025-08-03','1,200$+80,000 لای مام شاڵاو پەمپە',42,2.61),(50,147,'و, شوانه, و.شوانا','دوكان',105.00,53.00,5565.00,'قەرز',0.00,0.00,139400.00,5565.00,'A-0047, A-0048, A-0049, A-0050, A-0051, A-0053, A-0054, A-0055, A-0056, A-0057, A-0058, A-0059, A-0060, A-0061','2025-08-03','',38,0.00),(51,119,'وستا ازاد','پیرەمەگرون',112.00,47.00,5264.00,'قەرز',0.00,0.00,139400.00,5264.00,'A-0028, A-0029, A-0030, A-0031, A-0032, A-0033, A-0034, A-0035, A-0036, A-0037, A-0039, A-0041','2025-08-02','',30,0.00),(52,71,'و.کاروان','پیرەمەگرون',9.00,41.00,369.00,'قەرز',0.00,0.00,139400.00,369.00,'A-0023','2025-08-02','',26,0.00);
/*!40000 ALTER TABLE `sales` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_after_insert_sale_cash_box` AFTER INSERT ON `sales` FOR EACH ROW BEGIN
    IF NEW.payment_type = 'نەقد' THEN
        IF NEW.amount_paid_usd > 0 THEN
            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NEW.order_date, 'deposit', 0, NEW.amount_paid_usd, 'دۆلار', CONCAT('فرۆشتن: invoice ', NEW.invoice_number), NULL);
        END IF;
        IF NEW.amount_paid_iq > 0 THEN
            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NEW.order_date, 'deposit', NEW.amount_paid_iq, 0, 'دینار', CONCAT('فرۆشتن: invoice ', NEW.invoice_number), NULL);
        END IF;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_after_insert_sale` AFTER INSERT ON `sales` FOR EACH ROW BEGIN
    DECLARE v_black_sand_kg DECIMAL(10,2);
    DECLARE v_brown_sand_kg DECIMAL(10,2);
    DECLARE v_gravel_bin3_kg DECIMAL(10,2);
    DECLARE v_gravel_bin4_kg DECIMAL(10,2);
    DECLARE v_cement_kg DECIMAL(10,2);
    DECLARE v_cement_cem2_kg DECIMAL(10,2);
    DECLARE v_additive_kg DECIMAL(10,2);
    DECLARE v_total_volume DECIMAL(10,2);
    DECLARE v_current_black_sand DECIMAL(10,2);
    DECLARE v_current_brown_sand DECIMAL(10,2);
    DECLARE v_current_gravel_bin3 DECIMAL(10,2);
    DECLARE v_current_gravel_bin4 DECIMAL(10,2);
    DECLARE v_current_cement DECIMAL(10,2);
    DECLARE v_current_cement2 DECIMAL(10,2);
    DECLARE v_current_additive DECIMAL(10,2);
    DECLARE v_error_message VARCHAR(255);

    -- هەژمارکردنی مەتری سێجا
    SET v_total_volume = NEW.quantity;

    -- وەرگرتنی ڕێژەکانی فۆرمۆلاکە
    SELECT black_sand_kg, brown_sand_kg, gravel_bin3_kg, gravel_bin4_kg, cement_cem1_kg, cement_cem2_kg, additive_kg
    INTO v_black_sand_kg, v_brown_sand_kg, v_gravel_bin3_kg, v_gravel_bin4_kg, v_cement_kg, v_cement_cem2_kg, v_additive_kg
    FROM concrete_formulas 
    WHERE id = NEW.formula_id;

    -- لێکدانی ڕێژەکان بۆ قەبارەی فرۆشراو
    SET v_black_sand_kg = v_black_sand_kg * v_total_volume;
    SET v_brown_sand_kg = v_brown_sand_kg * v_total_volume;
    SET v_gravel_bin3_kg = v_gravel_bin3_kg * v_total_volume;
    SET v_gravel_bin4_kg = v_gravel_bin4_kg * v_total_volume;
    SET v_cement_kg = v_cement_kg * v_total_volume;
    SET v_cement_cem2_kg = v_cement_cem2_kg * v_total_volume;
    SET v_additive_kg = v_additive_kg * v_total_volume;

    -- وەرگرتنی بڕی ئێستای ماتریاڵەکان (بە kg)
    SELECT amount INTO v_current_black_sand FROM bins_silos WHERE id = 2;
    SELECT amount INTO v_current_brown_sand FROM bins_silos WHERE id = 1;
    SELECT amount INTO v_current_gravel_bin3 FROM bins_silos WHERE id = 3;
    SELECT amount INTO v_current_gravel_bin4 FROM bins_silos WHERE id = 4;
    SELECT amount INTO v_current_cement FROM bins_silos WHERE id = 5;
    SELECT amount INTO v_current_cement2 FROM bins_silos WHERE id = 6;
    SELECT amount INTO v_current_additive FROM bins_silos WHERE id = 7;

    -- چێککردنی بڕی پێویست لە هەموو ماتریاڵەکان (بە kg)
    IF v_black_sand_kg > v_current_black_sand THEN
        SET v_error_message = CONCAT('بڕی پێویست لە لمی ڕەش نییە. بڕی پێویست: ', ROUND(v_black_sand_kg, 2), ' kg، بڕی بەردەست: ', ROUND(v_current_black_sand, 2), ' kg');
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_error_message;
    END IF;

    IF v_brown_sand_kg > v_current_brown_sand THEN
        SET v_error_message = CONCAT('بڕی پێویست لە لمی کەسارە نییە. بڕی پێویست: ', ROUND(v_brown_sand_kg, 2), ' kg، بڕی بەردەست: ', ROUND(v_current_brown_sand, 2), ' kg');
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_error_message;
    END IF;

    IF v_gravel_bin3_kg > v_current_gravel_bin3 THEN
        SET v_error_message = CONCAT('بڕی پێویست لە چەوی بینی ٣ نییە. بڕی پێویست: ', ROUND(v_gravel_bin3_kg, 2), ' kg، بڕی بەردەست: ', ROUND(v_current_gravel_bin3, 2), ' kg');
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_error_message;
    END IF;

    IF v_gravel_bin4_kg > v_current_gravel_bin4 THEN
        SET v_error_message = CONCAT('بڕی پێویست لە چەوی بینی ٤ نییە. بڕی پێویست: ', ROUND(v_gravel_bin4_kg, 2), ' kg، بڕی بەردەست: ', ROUND(v_current_gravel_bin4, 2), ' kg');
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_error_message;
    END IF;

    IF v_cement_kg > v_current_cement THEN
        SET v_error_message = CONCAT('بڕی پێویست لە چیمەنتۆی سایلۆی ١ نییە. بڕی پێویست: ', ROUND(v_cement_kg, 2), ' kg، بڕی بەردەست: ', ROUND(v_current_cement, 2), ' kg');
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_error_message;
    END IF;
    
    IF v_cement_cem2_kg > v_current_cement2 THEN
        SET v_error_message = CONCAT('بڕی پێویست لە چیمەنتۆی سایلۆی ٢ نییە. بڕی پێویست: ', ROUND(v_cement_cem2_kg, 2), ' kg، بڕی بەردەست: ', ROUND(v_current_cement2, 2), ' kg');
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_error_message;
    END IF;
    
    IF v_additive_kg > v_current_additive THEN
        SET v_error_message = CONCAT('بڕی پێویست لە ماددەی زیادکراو نییە. بڕی پێویست: ', ROUND(v_additive_kg, 2), ' kg، بڕی بەردەست: ', ROUND(v_current_additive, 2), ' kg');
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_error_message;
    END IF;

    -- کەمکردنەوەی ماتریاڵەکان (بە kg)
    IF v_black_sand_kg > 0 THEN
        UPDATE bins_silos 
        SET amount = amount - v_black_sand_kg, 
            total_value = (amount * average_price)
        WHERE id = 2;
    END IF;

    IF v_brown_sand_kg > 0 THEN
        UPDATE bins_silos 
        SET amount = amount - v_brown_sand_kg, 
            total_value = (amount * average_price)
        WHERE id = 1;
    END IF;

    IF v_gravel_bin3_kg > 0 THEN
        UPDATE bins_silos 
        SET amount = amount - v_gravel_bin3_kg, 
            total_value = (amount * average_price)
        WHERE id = 3;
    END IF;

    IF v_gravel_bin4_kg > 0 THEN
        UPDATE bins_silos 
        SET amount = amount - v_gravel_bin4_kg, 
            total_value = (amount * average_price)
        WHERE id = 4;
    END IF;

    IF v_cement_kg > 0 THEN
        UPDATE bins_silos 
        SET amount = amount - v_cement_kg, 
            total_value = (amount * average_price)
        WHERE id = 5;
    END IF;
    
    IF v_cement_cem2_kg > 0 THEN
        UPDATE bins_silos 
        SET amount = amount - v_cement_cem2_kg, 
            total_value = (amount * average_price)
        WHERE id = 6;
    END IF;
    
    IF v_additive_kg > 0 THEN
        UPDATE bins_silos 
        SET amount = amount - v_additive_kg, 
            total_value = (amount * average_price)
        WHERE id = 7;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_before_update_sale_cash_box` BEFORE UPDATE ON `sales` FOR EACH ROW BEGIN
    -- سڕینەوەی مامەڵەی کۆن
    IF OLD.payment_type = 'نەقد' THEN
        IF OLD.amount_paid_usd > 0 THEN
            DELETE FROM cash_box
            WHERE `date` = OLD.order_date AND `type` = 'deposit' AND amount_usd = OLD.amount_paid_usd AND currency = 'دۆلار' AND note = CONCAT('فرۆشتن: invoice ', OLD.invoice_number);
        END IF;
        IF OLD.amount_paid_iq > 0 THEN
            DELETE FROM cash_box
            WHERE `date` = OLD.order_date AND `type` = 'deposit' AND amount_iqd = OLD.amount_paid_iq AND currency = 'دینار' AND note = CONCAT('فرۆشتن: invoice ', OLD.invoice_number);
        END IF;
    END IF;
    -- زیادکردنی مامەڵەی نوێ
    IF NEW.payment_type = 'نەقد' THEN
        IF NEW.amount_paid_usd > 0 THEN
            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NEW.order_date, 'deposit', 0, NEW.amount_paid_usd, 'دۆلار', CONCAT('فرۆشتن: invoice ', NEW.invoice_number), NULL);
        END IF;
        IF NEW.amount_paid_iq > 0 THEN
            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NEW.order_date, 'deposit', NEW.amount_paid_iq, 0, 'دینار', CONCAT('فرۆشتن: invoice ', NEW.invoice_number), NULL);
        END IF;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_after_update_sale` AFTER UPDATE ON `sales` FOR EACH ROW BEGIN
    -- Variables for old values
    DECLARE v_old_black_sand_kg DECIMAL(10,2);
    DECLARE v_old_brown_sand_kg DECIMAL(10,2);
    DECLARE v_old_gravel_bin3_kg DECIMAL(10,2);
    DECLARE v_old_gravel_bin4_kg DECIMAL(10,2);
    DECLARE v_old_cement_kg DECIMAL(10,2);
    DECLARE v_old_cement_cem2_kg DECIMAL(10,2);
    DECLARE v_old_additive_kg DECIMAL(10,2);
    DECLARE v_old_total_volume DECIMAL(10,2);

    -- Variables for new values
    DECLARE v_new_black_sand_kg DECIMAL(10,2);
    DECLARE v_new_brown_sand_kg DECIMAL(10,2);
    DECLARE v_new_gravel_bin3_kg DECIMAL(10,2);
    DECLARE v_new_gravel_bin4_kg DECIMAL(10,2);
    DECLARE v_new_cement_kg DECIMAL(10,2);
    DECLARE v_new_cement_cem2_kg DECIMAL(10,2);
    DECLARE v_new_additive_kg DECIMAL(10,2);
    DECLARE v_new_total_volume DECIMAL(10,2);

    -- هەژمارکردنی بڕی کۆن
    SET v_old_total_volume = OLD.quantity;
    SELECT black_sand_kg, brown_sand_kg, gravel_bin3_kg, gravel_bin4_kg, cement_cem1_kg, cement_cem2_kg, additive_kg
    INTO v_old_black_sand_kg, v_old_brown_sand_kg, v_old_gravel_bin3_kg, v_old_gravel_bin4_kg, v_old_cement_kg, v_old_cement_cem2_kg, v_old_additive_kg
    FROM concrete_formulas 
    WHERE id = OLD.formula_id;

    SET v_old_black_sand_kg = v_old_black_sand_kg * v_old_total_volume;
    SET v_old_brown_sand_kg = v_old_brown_sand_kg * v_old_total_volume;
    SET v_old_gravel_bin3_kg = v_old_gravel_bin3_kg * v_old_total_volume;
    SET v_old_gravel_bin4_kg = v_old_gravel_bin4_kg * v_old_total_volume;
    SET v_old_cement_kg = v_old_cement_kg * v_old_total_volume;
    SET v_old_cement_cem2_kg = v_old_cement_cem2_kg * v_old_total_volume;
    SET v_old_additive_kg = v_old_additive_kg * v_old_total_volume;

    -- هەژمارکردنی بڕی نوێ
    SET v_new_total_volume = NEW.quantity;
    SELECT black_sand_kg, brown_sand_kg, gravel_bin3_kg, gravel_bin4_kg, cement_cem1_kg, cement_cem2_kg, additive_kg
    INTO v_new_black_sand_kg, v_new_brown_sand_kg, v_new_gravel_bin3_kg, v_new_gravel_bin4_kg, v_new_cement_kg, v_new_cement_cem2_kg, v_new_additive_kg
    FROM concrete_formulas 
    WHERE id = NEW.formula_id;

    SET v_new_black_sand_kg = v_new_black_sand_kg * v_new_total_volume;
    SET v_new_brown_sand_kg = v_new_brown_sand_kg * v_new_total_volume;
    SET v_new_gravel_bin3_kg = v_new_gravel_bin3_kg * v_new_total_volume;
    SET v_new_gravel_bin4_kg = v_new_gravel_bin4_kg * v_new_total_volume;
    SET v_new_cement_kg = v_new_cement_kg * v_new_total_volume;
    SET v_new_cement_cem2_kg = v_new_cement_cem2_kg * v_new_total_volume;
    SET v_new_additive_kg = v_new_additive_kg * v_new_total_volume;

    -- گەڕاندنەوەی بڕی کۆن بۆ stock
    IF v_old_black_sand_kg > 0 THEN
        UPDATE bins_silos SET amount = amount + v_old_black_sand_kg, total_value = (amount * average_price) WHERE id = 2;
    END IF;
    IF v_old_brown_sand_kg > 0 THEN
        UPDATE bins_silos SET amount = amount + v_old_brown_sand_kg, total_value = (amount * average_price) WHERE id = 1;
    END IF;
    IF v_old_gravel_bin3_kg > 0 THEN
        UPDATE bins_silos SET amount = amount + v_old_gravel_bin3_kg, total_value = (amount * average_price) WHERE id = 3;
    END IF;
    IF v_old_gravel_bin4_kg > 0 THEN
        UPDATE bins_silos SET amount = amount + v_old_gravel_bin4_kg, total_value = (amount * average_price) WHERE id = 4;
    END IF;
    IF v_old_cement_kg > 0 THEN
        UPDATE bins_silos SET amount = amount + v_old_cement_kg, total_value = (amount * average_price) WHERE id = 5;
    END IF;
    IF v_old_cement_cem2_kg > 0 THEN
        UPDATE bins_silos SET amount = amount + v_old_cement_cem2_kg, total_value = (amount * average_price) WHERE id = 6;
    END IF;
    IF v_old_additive_kg > 0 THEN
        UPDATE bins_silos SET amount = amount + v_old_additive_kg, total_value = (amount * average_price) WHERE id = 7;
    END IF;

    -- کەمکردنەوەی بڕی نوێ لە stock
    IF v_new_black_sand_kg > 0 THEN
        UPDATE bins_silos SET amount = amount - v_new_black_sand_kg, total_value = (amount * average_price) WHERE id = 2;
    END IF;
    IF v_new_brown_sand_kg > 0 THEN
        UPDATE bins_silos SET amount = amount - v_new_brown_sand_kg, total_value = (amount * average_price) WHERE id = 1;
    END IF;
    IF v_new_gravel_bin3_kg > 0 THEN
        UPDATE bins_silos SET amount = amount - v_new_gravel_bin3_kg, total_value = (amount * average_price) WHERE id = 3;
    END IF;
    IF v_new_gravel_bin4_kg > 0 THEN
        UPDATE bins_silos SET amount = amount - v_new_gravel_bin4_kg, total_value = (amount * average_price) WHERE id = 4;
    END IF;
    IF v_new_cement_kg > 0 THEN
        UPDATE bins_silos SET amount = amount - v_new_cement_kg, total_value = (amount * average_price) WHERE id = 5;
    END IF;
    IF v_new_cement_cem2_kg > 0 THEN
        UPDATE bins_silos SET amount = amount - v_new_cement_cem2_kg, total_value = (amount * average_price) WHERE id = 6;
    END IF;
    IF v_new_additive_kg > 0 THEN
        UPDATE bins_silos SET amount = amount - v_new_additive_kg, total_value = (amount * average_price) WHERE id = 7;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_before_delete_sale_cash_box` BEFORE DELETE ON `sales` FOR EACH ROW BEGIN
    IF OLD.payment_type = 'نەقد' THEN
        IF OLD.amount_paid_usd > 0 THEN
            DELETE FROM cash_box
            WHERE `date` = OLD.order_date AND `type` = 'deposit' AND amount_usd = OLD.amount_paid_usd AND currency = 'دۆلار' AND note = CONCAT('فرۆشتن: invoice ', OLD.invoice_number);
        END IF;
        IF OLD.amount_paid_iq > 0 THEN
            DELETE FROM cash_box
            WHERE `date` = OLD.order_date AND `type` = 'deposit' AND amount_iqd = OLD.amount_paid_iq AND currency = 'دینار' AND note = CONCAT('فرۆشتن: invoice ', OLD.invoice_number);
        END IF;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_after_delete_sale` AFTER DELETE ON `sales` FOR EACH ROW BEGIN
    DECLARE v_black_sand_kg DECIMAL(10,2);
    DECLARE v_brown_sand_kg DECIMAL(10,2);
    DECLARE v_gravel_bin3_kg DECIMAL(10,2);
    DECLARE v_gravel_bin4_kg DECIMAL(10,2);
    DECLARE v_cement_kg DECIMAL(10,2);
    DECLARE v_cement_cem2_kg DECIMAL(10,2);
    DECLARE v_additive_kg DECIMAL(10,2);
    DECLARE v_total_volume DECIMAL(10,2);

    -- هەژمارکردنی مەتری سێجا
    SET v_total_volume = OLD.quantity;

    -- وەرگرتنی ڕێژەکانی فۆرمۆلاکە
    SELECT black_sand_kg, brown_sand_kg, gravel_bin3_kg, gravel_bin4_kg, cement_cem1_kg, cement_cem2_kg, additive_kg
    INTO v_black_sand_kg, v_brown_sand_kg, v_gravel_bin3_kg, v_gravel_bin4_kg, v_cement_kg, v_cement_cem2_kg, v_additive_kg
    FROM concrete_formulas 
    WHERE id = OLD.formula_id;

    -- لێکدانی ڕێژەکان بۆ قەبارەی فرۆشراو
    SET v_black_sand_kg = v_black_sand_kg * v_total_volume;
    SET v_brown_sand_kg = v_brown_sand_kg * v_total_volume;
    SET v_gravel_bin3_kg = v_gravel_bin3_kg * v_total_volume;
    SET v_gravel_bin4_kg = v_gravel_bin4_kg * v_total_volume;
    SET v_cement_kg = v_cement_kg * v_total_volume;
    SET v_cement_cem2_kg = v_cement_cem2_kg * v_total_volume;
    SET v_additive_kg = v_additive_kg * v_total_volume;

    -- گەڕاندنەوەی ماتریاڵەکان (stock) بۆ bins_silos
    IF v_black_sand_kg > 0 THEN
        UPDATE bins_silos 
        SET amount = amount + v_black_sand_kg, 
            total_value = (amount * average_price)
        WHERE id = 1;
    END IF;

    IF v_brown_sand_kg > 0 THEN
        UPDATE bins_silos 
        SET amount = amount + v_brown_sand_kg, 
            total_value = (amount * average_price)
        WHERE id = 2;
    END IF;

    IF v_gravel_bin3_kg > 0 THEN
        UPDATE bins_silos 
        SET amount = amount + v_gravel_bin3_kg, 
            total_value = (amount * average_price)
        WHERE id = 3;
    END IF;

    IF v_gravel_bin4_kg > 0 THEN
        UPDATE bins_silos 
        SET amount = amount + v_gravel_bin4_kg, 
            total_value = (amount * average_price)
        WHERE id = 4;
    END IF;

    IF v_cement_kg > 0 THEN
        UPDATE bins_silos 
        SET amount = amount + v_cement_kg, 
            total_value = (amount * average_price)
        WHERE id = 5;
    END IF;
    
    IF v_cement_cem2_kg > 0 THEN
        UPDATE bins_silos 
        SET amount = amount + v_cement_cem2_kg, 
            total_value = (amount * average_price)
        WHERE id = 6;
    END IF;
    
    IF v_additive_kg > 0 THEN
        UPDATE bins_silos 
        SET amount = amount + v_additive_kg, 
            total_value = (amount * average_price)
        WHERE id = 7;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `value` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'usd_iqd_rate','139250');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_adjustments`
--

DROP TABLE IF EXISTS `stock_adjustments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_adjustments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `bin_id` int NOT NULL,
  `adjustment` decimal(10,2) NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `user_id` int DEFAULT NULL,
  `price` decimal(12,2) DEFAULT '0.00',
  `price_usd` decimal(12,2) DEFAULT '0.00',
  `price_iqd` decimal(12,2) DEFAULT '0.00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_adjustments`
--

LOCK TABLES `stock_adjustments` WRITE;
/*!40000 ALTER TABLE `stock_adjustments` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_adjustments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `unit_conversion_rules`
--

DROP TABLE IF EXISTS `unit_conversion_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `unit_conversion_rules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `unit_type_id` int NOT NULL,
  `rule_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rule_description` text COLLATE utf8mb4_unicode_ci,
  `conversion_logic` json NOT NULL COMMENT 'JSON structure defining conversion logic',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `unit_type_id` (`unit_type_id`),
  CONSTRAINT `fk_unit_conversion_rules_unit_type` FOREIGN KEY (`unit_type_id`) REFERENCES `unit_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `unit_conversion_rules`
--

LOCK TABLES `unit_conversion_rules` WRITE;
/*!40000 ALTER TABLE `unit_conversion_rules` DISABLE KEYS */;
INSERT INTO `unit_conversion_rules` VALUES (1,1,'carton_to_pieces','Convert carton to pieces','{\"formula\": \"carton_quantity * pieces_per_carton\", \"input_fields\": [\"pieces_per_carton\"], \"output_fields\": [\"total_pieces\"]}',1,'2025-08-02 20:14:23'),(2,2,'piece_direct','Direct piece conversion','{\"formula\": \"piece_quantity\", \"input_fields\": [], \"output_fields\": [\"total_pieces\"]}',1,'2025-08-02 20:14:23'),(3,3,'barrel_to_buckets_to_liters','Convert barrel to buckets to liters','{\"formula\": \"barrel_quantity * buckets_per_barrel * liters_per_bucket\", \"input_fields\": [\"buckets_per_barrel\", \"liters_per_bucket\"], \"output_fields\": [\"total_buckets\", \"total_liters\"]}',1,'2025-08-02 20:14:23'),(4,4,'bucket_to_liters','Convert bucket to liters','{\"formula\": \"bucket_quantity * liters_per_bucket\", \"input_fields\": [\"liters_per_bucket\"], \"output_fields\": [\"total_liters\"]}',1,'2025-08-02 20:14:23'),(5,5,'liter_direct','Direct liter conversion','{\"formula\": \"liter_quantity\", \"input_fields\": [], \"output_fields\": [\"total_liters\"]}',1,'2025-08-02 20:14:23');
/*!40000 ALTER TABLE `unit_conversion_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `unit_types`
--

DROP TABLE IF EXISTS `unit_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `unit_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_ku` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `unit_types`
--

LOCK TABLES `unit_types` WRITE;
/*!40000 ALTER TABLE `unit_types` DISABLE KEYS */;
INSERT INTO `unit_types` VALUES (1,'carton','کارتۆن','کارتۆن کە چەند دانەیەکی تێدایە',1,'2025-08-02 20:14:23'),(2,'piece','دانە','دانە بەتەنیا',1,'2025-08-02 20:14:23'),(3,'barrel','بەرمیل','بەرمیل کە هەر بەرمیلێک چەند دەبەیە و هەر دەبەیەک چەن لیتر',1,'2025-08-02 20:14:23'),(4,'bucket','دەبە','دەبە بەتەنیا و هەر دەبەیەک چەن لیتر',1,'2025-08-02 20:14:23'),(5,'liter','لیتر','لیتر بە تەنیا',1,'2025-08-02 20:14:23');
/*!40000 ALTER TABLE `unit_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('admin','user','accountant','manager') COLLATE utf8mb4_general_ci DEFAULT 'user',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Dana','$2y$10$zu.c2smtRIzusVGN2tUpwuc/C50zJx.pWJuMz0VyER84Sw0j9XbRe','admin'),(2,'rawezh','$2y$10$.okWrqkFKbIoEN7LdwwApusaQV.SOJRYJx5Zfrn.OOk2ULmUFRor6','admin'),(7,'mustafa','$2y$12$MeImTJ1uu6kkWG1bc.vA1epaf8.X.JPUmY8GHDq6RImukkYm3wIfq','user'),(8,'bazyan','$2y$12$1ibiS7zdOOZh4xwQNjforuriFMiDtxHYuMlTvah869Mt7sYyg/teO','manager'),(9,'twana','$2y$12$Xt/rbvcfjcsMB2tKzsMw8.w91akDuCAP6QUGE5cekeJUQQyuV/Pu6','accountant');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warehouse_inventory`
--

DROP TABLE IF EXISTS `warehouse_inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `warehouse_inventory` (
  `id` int NOT NULL AUTO_INCREMENT,
  `material_id` int NOT NULL,
  `quantity` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'Quantity in base units',
  `available_quantity` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'Available quantity in base units',
  `total_value_usd` decimal(15,4) DEFAULT '0.0000',
  `total_value_iqd` decimal(15,4) DEFAULT '0.0000',
  `average_price_usd` decimal(15,4) DEFAULT '0.0000' COMMENT 'Average price per base unit in USD',
  `average_price_iqd` decimal(15,4) DEFAULT '0.0000' COMMENT 'Average price per base unit in IQD',
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `material_id` (`material_id`),
  CONSTRAINT `fk_warehouse_inventory_material` FOREIGN KEY (`material_id`) REFERENCES `warehouse_materials` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warehouse_inventory`
--

LOCK TABLES `warehouse_inventory` WRITE;
/*!40000 ALTER TABLE `warehouse_inventory` DISABLE KEYS */;
/*!40000 ALTER TABLE `warehouse_inventory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warehouse_materials`
--

DROP TABLE IF EXISTS `warehouse_materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `warehouse_materials` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_ku` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('black_sand','brown_sand','gravel','cement','medicine','gas','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `unit_type_id` int NOT NULL,
  `base_unit` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Smallest unit (piece, liter, kg)',
  `conversion_factor` decimal(15,4) DEFAULT '1.0000' COMMENT 'Conversion to base unit',
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `unit_type_id` (`unit_type_id`),
  CONSTRAINT `fk_warehouse_materials_unit_type` FOREIGN KEY (`unit_type_id`) REFERENCES `unit_types` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warehouse_materials`
--

LOCK TABLES `warehouse_materials` WRITE;
/*!40000 ALTER TABLE `warehouse_materials` DISABLE KEYS */;
/*!40000 ALTER TABLE `warehouse_materials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warehouse_purchase_items`
--

DROP TABLE IF EXISTS `warehouse_purchase_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `warehouse_purchase_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `purchase_id` int NOT NULL,
  `material_id` int NOT NULL,
  `purchase_quantity` decimal(15,4) NOT NULL COMMENT 'Quantity in purchase unit',
  `purchase_unit` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Unit used for purchase (carton, piece, barrel, etc.)',
  `purchase_price_usd` decimal(15,4) DEFAULT '0.0000' COMMENT 'Price per purchase unit in USD',
  `purchase_price_iqd` decimal(15,4) DEFAULT '0.0000' COMMENT 'Price per purchase unit in IQD',
  `total_price_usd` decimal(15,4) DEFAULT '0.0000',
  `total_price_iqd` decimal(15,4) DEFAULT '0.0000',
  `converted_quantity` decimal(15,4) NOT NULL COMMENT 'Quantity converted to base units',
  `unit_conversion_details` json DEFAULT NULL COMMENT 'JSON with conversion details',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `purchase_id` (`purchase_id`),
  KEY `material_id` (`material_id`),
  CONSTRAINT `fk_warehouse_purchase_items_material` FOREIGN KEY (`material_id`) REFERENCES `warehouse_materials` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_warehouse_purchase_items_purchase` FOREIGN KEY (`purchase_id`) REFERENCES `warehouse_purchases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warehouse_purchase_items`
--

LOCK TABLES `warehouse_purchase_items` WRITE;
/*!40000 ALTER TABLE `warehouse_purchase_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `warehouse_purchase_items` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `after_warehouse_purchase_items_insert` AFTER INSERT ON `warehouse_purchase_items` FOR EACH ROW BEGIN
    DECLARE current_quantity DECIMAL(15,4) DEFAULT 0;
    DECLARE current_value_usd DECIMAL(15,4) DEFAULT 0;
    DECLARE current_value_iqd DECIMAL(15,4) DEFAULT 0;
    DECLARE new_quantity DECIMAL(15,4) DEFAULT 0;
    DECLARE new_value_usd DECIMAL(15,4) DEFAULT 0;
    DECLARE new_value_iqd DECIMAL(15,4) DEFAULT 0;
    DECLARE new_avg_price_usd DECIMAL(15,4) DEFAULT 0;
    DECLARE new_avg_price_iqd DECIMAL(15,4) DEFAULT 0;
    
    -- Get current inventory values
    SELECT quantity, total_value_usd, total_value_iqd 
    INTO current_quantity, current_value_usd, current_value_iqd
    FROM warehouse_inventory 
    WHERE material_id = NEW.material_id;
    
    -- Calculate new values
    SET new_quantity = IFNULL(current_quantity, 0) + NEW.converted_quantity;
    SET new_value_usd = IFNULL(current_value_usd, 0) + NEW.total_price_usd;
    SET new_value_iqd = IFNULL(current_value_iqd, 0) + NEW.total_price_iqd;
    
    -- Calculate new average prices
    IF new_quantity > 0 THEN
        SET new_avg_price_usd = new_value_usd / new_quantity;
        SET new_avg_price_iqd = new_value_iqd / new_quantity;
    END IF;
    
    -- Insert or update inventory
    INSERT INTO warehouse_inventory (material_id, quantity, available_quantity, total_value_usd, total_value_iqd, average_price_usd, average_price_iqd)
    VALUES (NEW.material_id, new_quantity, new_quantity, new_value_usd, new_value_iqd, new_avg_price_usd, new_avg_price_iqd)
    ON DUPLICATE KEY UPDATE
        quantity = new_quantity,
        available_quantity = new_quantity,
        total_value_usd = new_value_usd,
        total_value_iqd = new_value_iqd,
        average_price_usd = new_avg_price_usd,
        average_price_iqd = new_avg_price_iqd;
    
    -- Log transaction
    INSERT INTO warehouse_transactions (material_id, transaction_type, quantity, unit_price_usd, unit_price_iqd, total_value_usd, total_value_iqd, reference_id, reference_table, notes, created_by)
    VALUES (NEW.material_id, 'purchase', NEW.converted_quantity, 
            CASE WHEN NEW.converted_quantity > 0 THEN NEW.total_price_usd / NEW.converted_quantity ELSE 0 END,
            CASE WHEN NEW.converted_quantity > 0 THEN NEW.total_price_iqd / NEW.converted_quantity ELSE 0 END,
            NEW.total_price_usd, NEW.total_price_iqd, NEW.purchase_id, 'warehouse_purchases', 
            CONCAT('Purchase: ', NEW.purchase_quantity, ' ', NEW.purchase_unit), 
            (SELECT created_by FROM warehouse_purchases WHERE id = NEW.purchase_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `after_warehouse_purchase_items_delete` AFTER DELETE ON `warehouse_purchase_items` FOR EACH ROW BEGIN
    DECLARE current_quantity DECIMAL(15,4) DEFAULT 0;
    DECLARE current_value_usd DECIMAL(15,4) DEFAULT 0;
    DECLARE current_value_iqd DECIMAL(15,4) DEFAULT 0;
    DECLARE new_quantity DECIMAL(15,4) DEFAULT 0;
    DECLARE new_value_usd DECIMAL(15,4) DEFAULT 0;
    DECLARE new_value_iqd DECIMAL(15,4) DEFAULT 0;
    DECLARE new_avg_price_usd DECIMAL(15,4) DEFAULT 0;
    DECLARE new_avg_price_iqd DECIMAL(15,4) DEFAULT 0;
    
    -- Get current inventory values
    SELECT quantity, total_value_usd, total_value_iqd 
    INTO current_quantity, current_value_usd, current_value_iqd
    FROM warehouse_inventory 
    WHERE material_id = OLD.material_id;
    
    -- Calculate new values (subtract deleted values)
    SET new_quantity = IFNULL(current_quantity, 0) - OLD.converted_quantity;
    SET new_value_usd = IFNULL(current_value_usd, 0) - OLD.total_price_usd;
    SET new_value_iqd = IFNULL(current_value_iqd, 0) - OLD.total_price_iqd;
    
    -- Ensure quantities don't go negative
    IF new_quantity < 0 THEN
        SET new_quantity = 0;
    END IF;
    IF new_value_usd < 0 THEN
        SET new_value_usd = 0;
    END IF;
    IF new_value_iqd < 0 THEN
        SET new_value_iqd = 0;
    END IF;
    
    -- Calculate new average prices
    IF new_quantity > 0 THEN
        SET new_avg_price_usd = new_value_usd / new_quantity;
        SET new_avg_price_iqd = new_value_iqd / new_quantity;
    END IF;
    
    -- Update inventory
    UPDATE warehouse_inventory 
    SET quantity = new_quantity,
        available_quantity = new_quantity,
        total_value_usd = new_value_usd,
        total_value_iqd = new_value_iqd,
        average_price_usd = new_avg_price_usd,
        average_price_iqd = new_avg_price_iqd
    WHERE material_id = OLD.material_id;
    
    -- Log transaction
    INSERT INTO warehouse_transactions (material_id, transaction_type, quantity, unit_price_usd, unit_price_iqd, total_value_usd, total_value_iqd, reference_id, reference_table, notes, created_by)
    VALUES (OLD.material_id, 'adjustment', -OLD.converted_quantity, 
            CASE WHEN OLD.converted_quantity > 0 THEN OLD.total_price_usd / OLD.converted_quantity ELSE 0 END,
            CASE WHEN OLD.converted_quantity > 0 THEN OLD.total_price_iqd / OLD.converted_quantity ELSE 0 END,
            -OLD.total_price_usd, -OLD.total_price_iqd, OLD.purchase_id, 'warehouse_purchases', 
            CONCAT('Purchase deletion: ', OLD.purchase_quantity, ' ', OLD.purchase_unit), 
            (SELECT created_by FROM warehouse_purchases WHERE id = OLD.purchase_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `warehouse_purchases`
--

DROP TABLE IF EXISTS `warehouse_purchases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `warehouse_purchases` (
  `id` int NOT NULL AUTO_INCREMENT,
  `receipt_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `supplier_id` int NOT NULL,
  `purchase_date` date NOT NULL,
  `currency_type` enum('دینار','دۆلار') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'دینار',
  `exchange_rate` decimal(15,4) DEFAULT '139250.0000',
  `transfer_cost` decimal(15,4) DEFAULT '0.0000',
  `other_costs` decimal(15,4) DEFAULT '0.0000',
  `total_amount_usd` decimal(15,4) DEFAULT '0.0000',
  `total_amount_iqd` decimal(15,4) DEFAULT '0.0000',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `receipt_number` (`receipt_number`),
  KEY `supplier_id` (`supplier_id`),
  CONSTRAINT `fk_warehouse_purchases_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `other_expense_persons` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warehouse_purchases`
--

LOCK TABLES `warehouse_purchases` WRITE;
/*!40000 ALTER TABLE `warehouse_purchases` DISABLE KEYS */;
/*!40000 ALTER TABLE `warehouse_purchases` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warehouse_transactions`
--

DROP TABLE IF EXISTS `warehouse_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `warehouse_transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `material_id` int NOT NULL,
  `transaction_type` enum('purchase','sale','adjustment','transfer') COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(15,4) NOT NULL COMMENT 'Quantity in base units',
  `unit_price_usd` decimal(15,4) DEFAULT '0.0000',
  `unit_price_iqd` decimal(15,4) DEFAULT '0.0000',
  `total_value_usd` decimal(15,4) DEFAULT '0.0000',
  `total_value_iqd` decimal(15,4) DEFAULT '0.0000',
  `reference_id` int DEFAULT NULL COMMENT 'ID of related record (purchase, sale, etc.)',
  `reference_table` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Table name of related record',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `material_id` (`material_id`),
  KEY `transaction_type` (`transaction_type`),
  KEY `reference_id` (`reference_id`),
  CONSTRAINT `fk_warehouse_transactions_material` FOREIGN KEY (`material_id`) REFERENCES `warehouse_materials` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warehouse_transactions`
--

LOCK TABLES `warehouse_transactions` WRITE;
/*!40000 ALTER TABLE `warehouse_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `warehouse_transactions` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-08-05 11:11:58
