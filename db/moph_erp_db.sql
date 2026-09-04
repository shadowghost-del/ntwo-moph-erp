-- ========================================================
-- MOPH ERP Narathiwat - Database Backup (Data Mirroring)
-- Host: 127.0.0.1 | Database: moph_erp_db
-- Backup Date: 2026-09-03 10:16:19
-- Created by: Nexus Health Super Admin
-- ========================================================

SET FOREIGN_KEY_CHECKS=0;

-- --------------------------------------------------------
-- Table structure for `downtime_logs`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `downtime_logs`;
CREATE TABLE `downtime_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hospital_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `down_date` date NOT NULL,
  `last_sent_at` datetime NOT NULL,
  `days_offline` int NOT NULL DEFAULT '1',
  `notes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_hosp_down_date` (`hospital_code`,`down_date`),
  CONSTRAINT `downtime_logs_ibfk_1` FOREIGN KEY (`hospital_code`) REFERENCES `hospitals` (`code`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Records of `downtime_logs`
INSERT INTO `downtime_logs` VALUES ('1', 'EA0010751', '2026-07-24', '2026-07-24 08:59:00', '1', 'บันทึกอัตโนมัติโดยระบบ Admin', '2026-08-01 12:40:16');
INSERT INTO `downtime_logs` VALUES ('2', 'EA0011438', '2026-07-24', '2026-07-24 08:59:00', '1', 'บันทึกอัตโนมัติโดยระบบ Admin', '2026-08-01 12:41:14');
INSERT INTO `downtime_logs` VALUES ('3', 'EA0011441', '2026-07-24', '2026-07-24 06:59:00', '1', 'บันทึกอัตโนมัติโดยระบบ Admin', '2026-08-01 12:42:47');
INSERT INTO `downtime_logs` VALUES ('4', 'EA0011442', '2026-07-24', '2026-07-24 08:59:00', '1', 'บันทึกอัตโนมัติโดยระบบ Admin', '2026-08-01 12:43:33');
INSERT INTO `downtime_logs` VALUES ('5', 'EA0011439', '2026-08-01', '2026-08-01 08:59:00', '1', 'บันทึกอัตโนมัติโดยระบบ Admin', '2026-08-02 09:26:24');
INSERT INTO `downtime_logs` VALUES ('6', 'EA0011439', '2026-08-02', '2026-08-02 08:59:00', '1', 'บันทึกอัตโนมัติโดยระบบ Admin', '2026-08-02 09:26:42');
INSERT INTO `downtime_logs` VALUES ('7', 'EA0011439', '2026-08-03', '2026-08-03 08:59:00', '1', 'บันทึกอัตโนมัติโดยระบบ Admin', '2026-08-03 11:43:47');
INSERT INTO `downtime_logs` VALUES ('8', 'EA0015010', '2026-08-07', '2026-08-07 06:59:00', '1', 'บันทึกอัตโนมัติโดยระบบ Admin', '2026-08-07 09:34:05');
INSERT INTO `downtime_logs` VALUES ('10', 'EA0013818', '2026-08-08', '2026-08-08 07:59:00', '1', 'บันทึกอัตโนมัติโดยระบบ Admin', '2026-08-08 10:05:15');
INSERT INTO `downtime_logs` VALUES ('11', 'EA0013818', '2026-08-09', '2026-08-09 07:59:00', '1', 'บันทึกอัตโนมัติโดยระบบ Admin', '2026-08-09 10:14:52');
INSERT INTO `downtime_logs` VALUES ('12', 'EA0013818', '2026-08-14', '2026-08-14 09:59:00', '1', 'บันทึกอัตโนมัติโดยระบบ Admin', '2026-08-17 09:24:34');
INSERT INTO `downtime_logs` VALUES ('13', 'EA0013818', '2026-08-15', '2026-08-15 09:59:00', '1', 'บันทึกอัตโนมัติโดยระบบ Admin', '2026-08-17 09:24:49');
INSERT INTO `downtime_logs` VALUES ('14', 'EA0013818', '2026-08-16', '2026-08-16 09:59:00', '1', 'บันทึกอัตโนมัติโดยระบบ Admin', '2026-08-17 09:25:22');
INSERT INTO `downtime_logs` VALUES ('15', 'EA0013818', '2026-08-17', '2026-08-17 09:59:00', '1', 'บันทึกอัตโนมัติโดยระบบ Admin', '2026-08-17 09:25:47');
INSERT INTO `downtime_logs` VALUES ('17', 'EA0010750', '2026-08-17', '2026-08-17 19:59:00', '1', 'บันทึกอัตโนมัติโดยระบบ Admin', '2026-08-19 10:13:20');
INSERT INTO `downtime_logs` VALUES ('18', 'EA0010750', '2026-08-18', '2026-08-18 19:59:00', '1', 'บันทึกอัตโนมัติโดยระบบ Admin', '2026-08-20 10:54:09');
INSERT INTO `downtime_logs` VALUES ('19', 'EA0010750', '2026-08-19', '2026-08-19 19:59:00', '1', 'บันทึกอัตโนมัติโดยระบบ Admin', '2026-08-20 10:54:40');
INSERT INTO `downtime_logs` VALUES ('20', 'EA0010750', '2026-08-20', '2026-08-20 19:59:00', '1', 'บันทึกอัตโนมัติโดยระบบ Admin', '2026-08-20 10:54:48');
INSERT INTO `downtime_logs` VALUES ('21', 'EA0010750', '2026-08-15', '2026-08-15 14:59:00', '1', 'บันทึกอัตโนมัติโดยระบบ Admin', '2026-08-24 09:40:30');
INSERT INTO `downtime_logs` VALUES ('22', 'EA0010750', '2026-08-23', '2026-08-23 14:59:00', '1', 'บันทึกอัตโนมัติโดยระบบ Admin', '2026-08-24 09:40:47');
INSERT INTO `downtime_logs` VALUES ('23', 'EA0010750', '2026-08-24', '2026-08-24 14:59:00', '1', 'บันทึกอัตโนมัติโดยระบบ Admin', '2026-08-24 09:40:57');
INSERT INTO `downtime_logs` VALUES ('24', 'EA0010750', '2026-08-25', '2026-08-25 08:59:00', '1', 'บันทึกอัตโนมัติโดยระบบ Admin', '2026-08-25 09:47:37');
INSERT INTO `downtime_logs` VALUES ('25', 'EA0010750', '2026-08-26', '2026-08-26 08:59:00', '1', 'บันทึกอัตโนมัติโดยระบบ Admin', '2026-08-26 09:59:25');
INSERT INTO `downtime_logs` VALUES ('26', 'EA0010750', '2026-08-27', '2026-08-27 08:59:00', '1', 'บันทึกอัตโนมัติโดยระบบ Admin', '2026-08-27 09:26:15');

-- --------------------------------------------------------
-- Table structure for `hospital_beds`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `hospital_beds`;
CREATE TABLE `hospital_beds` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hospital_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hcode_old` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `beds` int NOT NULL DEFAULT '0',
  `icu` int NOT NULL DEFAULT '0',
  `or_rooms` int NOT NULL DEFAULT '0',
  `doctors` int NOT NULL DEFAULT '0',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_hosp_bed` (`hospital_code`),
  CONSTRAINT `fk_beds_hospitals` FOREIGN KEY (`hospital_code`) REFERENCES `hospitals` (`code`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Records of `hospital_beds`
INSERT INTO `hospital_beds` VALUES ('1', 'EA0013818', '13818', '60', '0', '0', '9', '2026-08-02 10:02:01');
INSERT INTO `hospital_beds` VALUES ('2', 'EA0015010', '15010', '56', '0', '0', '9', '2026-08-02 10:02:01');
INSERT INTO `hospital_beds` VALUES ('3', 'EA0011435', '11435', '100', '0', '2', '25', '2026-08-02 10:02:01');
INSERT INTO `hospital_beds` VALUES ('4', 'EA0010750', '10750', '413', '48', '9', '108', '2026-08-02 16:08:41');
INSERT INTO `hospital_beds` VALUES ('5', 'EA0011436', '11436', '68', '0', '0', '7', '2026-08-02 10:02:01');
INSERT INTO `hospital_beds` VALUES ('6', 'EA0023771', '23771', '58', '0', '0', '13', '2026-08-02 10:02:01');
INSERT INTO `hospital_beds` VALUES ('7', 'EA0011437', '11437', '135', '0', '2', '24', '2026-08-02 10:02:01');
INSERT INTO `hospital_beds` VALUES ('8', 'EA0011438', '11438', '82', '0', '0', '26', '2026-08-02 10:02:01');
INSERT INTO `hospital_beds` VALUES ('9', 'EA0011440', '11440', '60', '0', '0', '14', '2026-08-02 10:02:01');
INSERT INTO `hospital_beds` VALUES ('10', 'EA0011439', '11439', '59', '0', '0', '8', '2026-08-02 10:02:01');
INSERT INTO `hospital_beds` VALUES ('11', 'EA0011441', '11441', '38', '0', '1', '8', '2026-08-02 10:02:01');
INSERT INTO `hospital_beds` VALUES ('12', 'EA0010751', '10751', '212', '13', '4', '46', '2026-08-02 10:02:01');
INSERT INTO `hospital_beds` VALUES ('13', 'EA0011442', '11442', '39', '0', '1', '15', '2026-08-02 10:02:01');

-- --------------------------------------------------------
-- Table structure for `hospitals`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `hospitals`;
CREATE TABLE `hospitals` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `level` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `his_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `api_version` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `api_status` enum('NORMAL','NORMAL-LATE','DOWN') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'NORMAL',
  `last_sent_at` datetime NOT NULL,
  `days_count` int NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Records of `hospitals`
INSERT INTO `hospitals` VALUES ('1', 'EA0010750', 'โรงพยาบาลนราธิวาสราชนครินทร์', 'A+, S', 'HOSxP', '3.10.3', 'NORMAL', '2026-09-03 08:59:00', '7', '2026-07-21 14:01:25', '2026-09-03 09:35:56');
INSERT INTO `hospitals` VALUES ('2', 'EA0010751', 'โรงพยาบาลสุไหงโก-ลก', 'A+, M1', 'HOSxP 3', '3.10.3', 'NORMAL', '2026-09-03 08:59:00', '7', '2026-07-21 14:01:25', '2026-09-03 09:36:05');
INSERT INTO `hospitals` VALUES ('3', 'EA0011435', 'โรงพยาบาลตากใบ', 'S+, F1', 'HOSxP bms.6400', '3.10.3', 'NORMAL', '2026-09-03 08:59:00', '7', '2026-07-21 14:01:25', '2026-09-03 09:36:17');
INSERT INTO `hospitals` VALUES ('4', 'EA0011436', 'โรงพยาบาลบาเจาะ', 'S, F2', 'HOSxP 3', '3.10.3', 'NORMAL', '2026-09-03 08:59:00', '7', '2026-07-21 14:01:25', '2026-09-03 09:36:25');
INSERT INTO `hospitals` VALUES ('5', 'EA0011437', 'โรงพยาบาลระแงะ', 'S+, F1', 'HOSxP 4.6404', '3.10.3', 'NORMAL', '2026-09-03 08:59:00', '7', '2026-07-21 14:01:25', '2026-09-03 09:36:34');
INSERT INTO `hospitals` VALUES ('6', 'EA0011438', 'โรงพยาบาลรือเสาะ', 'S, F1', 'HOSxP 3', '3.10.3', 'NORMAL', '2026-09-03 08:59:00', '7', '2026-07-21 14:01:25', '2026-09-03 09:36:54');
INSERT INTO `hospitals` VALUES ('7', 'EA0011439', 'โรงพยาบาลศรีสาคร', 'S, F2', 'HOSxP 4', '3.10.3', 'NORMAL', '2026-09-03 08:59:00', '7', '2026-07-21 14:01:25', '2026-09-03 09:37:17');
INSERT INTO `hospitals` VALUES ('8', 'EA0011440', 'โรงพยาบาลแว้ง', 'S, F2', 'HOSxP 3.68.10.16', '3.10.3', 'NORMAL', '2026-09-03 08:59:00', '7', '2026-07-21 14:01:25', '2026-09-03 09:37:26');
INSERT INTO `hospitals` VALUES ('9', 'EA0011441', 'โรงพยาบาลสุคิริน', 'S, F2', 'HOSxP 3', '3.10.3', 'NORMAL', '2026-09-03 08:59:00', '7', '2026-07-21 14:01:25', '2026-09-03 09:37:34');
INSERT INTO `hospitals` VALUES ('10', 'EA0011442', 'โรงพยาบาลสุไหงปาดี', 'S, F2', 'HOSxP 3', '3.10.3', 'NORMAL', '2026-09-03 08:59:00', '7', '2026-07-21 14:01:25', '2026-09-03 09:38:55');
INSERT INTO `hospitals` VALUES ('11', 'EA0013818', 'โรงพยาบาลจะแนะ', 'S, F2', 'HOSxP 3', '3.10.3', 'NORMAL', '2026-09-03 08:59:00', '7', '2026-07-21 14:01:25', '2026-09-03 09:39:04');
INSERT INTO `hospitals` VALUES ('12', 'EA0015010', 'โรงพยาบาลเจาะไอร้อง', 'S, F2', 'HOSxP 3', '3.10.3', 'NORMAL', '2026-09-03 08:59:00', '7', '2026-07-21 14:01:25', '2026-09-03 09:40:20');
INSERT INTO `hospitals` VALUES ('13', 'EA0023771', 'โรงพยาบาลยี่งอเฉลิมพระเกียรติ 80 พรรษา', 'S, F2', 'HOSxP 4', '3.10.3', 'NORMAL', '2026-09-02 07:59:00', '7', '2026-07-21 14:01:25', '2026-09-03 09:40:30');

-- --------------------------------------------------------
-- Table structure for `it_officers`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `it_officers`;
CREATE TABLE `it_officers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fullname` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `agency` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Records of `it_officers`
INSERT INTO `it_officers` VALUES ('1', 'abdulroyadaraoh@gmail.com', 'นาย อับดุลรอยะ ดาราโอ๊ะ', '0820311437', 'IT รพ.', 'โรงพยาบาลระแงะ', '2026-08-02 12:24:31', '2026-08-02 12:24:31');
INSERT INTO `it_officers` VALUES ('2', 'crangae11437@gmail.com', 'นาย อันวา ซีเซ็ง', '0808740137', 'IT รพ.', 'โรงพยาบาลระแงะ', '2026-08-02 12:24:31', '2026-08-02 12:24:31');
INSERT INTO `it_officers` VALUES ('3', 'leeya1323@hotmail.com', 'ซูกีฟลี แวมุหะมะ', '0894659795', 'IT รพ.', 'โรงพยาบาลสุไหงโก-ลก', '2026-08-02 12:24:31', '2026-08-02 12:24:31');
INSERT INTO `it_officers` VALUES ('4', 'maruden_uma@hotmail.com', 'นาย มารูเด็น อูมา', '0945893757', 'IT รพ.', 'โรงพยาบาลสุคิริน', '2026-08-02 12:24:31', '2026-08-02 12:24:31');
INSERT INTO `it_officers` VALUES ('5', 'itnara2020@gmail.com', 'นาย คฑายุทธ์ คงถาวร', '0863414948', 'IT รพ.', 'โรงพยาบาลนราธิวาสราชนครินทร์', '2026-08-02 12:24:31', '2026-08-02 12:24:31');
INSERT INTO `it_officers` VALUES ('6', 'arnee_mrs@hotmail.com', 'น.ส. ปราณีย์ ตาเย๊ะ', '0635897679', 'IT รพ.', 'โรงพยาบาลสุคิริน', '2026-08-02 12:24:31', '2026-08-02 12:24:31');
INSERT INTO `it_officers` VALUES ('7', 'watanyu@outlook.com', 'นาย วทัญญู เหล็บหนู', '0816784462', 'IT รพ.', 'โรงพยาบาลสุไหงปาดี', '2026-08-02 12:24:31', '2026-08-02 12:24:31');
INSERT INTO `it_officers` VALUES ('8', 'negaton.app@gmail.com', 'นาย ซอลาฮุดดีน เบนโน', '0801468982', 'IT รพ.', 'โรงพยาบาลยี่งอเฉลิมพระเกียรติ 80 พรรษา', '2026-08-02 12:24:31', '2026-08-02 12:24:31');
INSERT INTO `it_officers` VALUES ('9', 'lar_buscom@hotmail.com', 'ไซนะ อาแซ', '0918473272', 'IT รพ.', 'โรงพยาบาลรือเสาะ', '2026-08-02 12:24:31', '2026-08-02 12:24:31');
INSERT INTO `it_officers` VALUES ('10', 'areefan.moph@gmail.com', 'นาย อารีฟัน หมะหมูด', '0629375159', 'Admin สสจ/เขต.', 'สำนักงานสาธารณสุขจังหวัดนราธิวาส', '2026-08-02 12:24:31', '2026-08-02 12:24:31');
INSERT INTO `it_officers` VALUES ('11', 'baer_x_rayz@hotmail.com', 'นุรมาน นาแซ', '0937499008', 'IT รพ.', 'โรงพยาบาลบาเจาะ', '2026-08-02 12:24:31', '2026-08-02 12:24:31');
INSERT INTO `it_officers` VALUES ('12', 'gweeped@gmail.com', 'นาดีน บินนิมะ', '0855997475', 'IT รพ.', 'โรงพยาบาลตากใบ', '2026-08-02 12:24:31', '2026-08-02 12:24:31');
INSERT INTO `it_officers` VALUES ('13', 'dcirhos@gmail.com', 'นาย โอมรัม ยูโซ๊ะ', '0862925149', 'IT รพ.', 'โรงพยาบาลเจาะไอร้อง', '2026-08-02 12:24:31', '2026-08-02 12:24:31');
INSERT INTO `it_officers` VALUES ('14', 'abdull_com@hotmail.com', 'บุศรอ ซาเร๊าะ', '0870505105', 'IT รพ.', 'โรงพยาบาลศรีสาคร', '2026-08-02 12:24:31', '2026-08-02 12:24:31');
INSERT INTO `it_officers` VALUES ('15', 'acharan2256@gmail.com', 'อัจรัณย์ ซำเซ', '0873910477', 'IT รพ.', 'โรงพยาบาลแว้ง', '2026-08-02 12:24:31', '2026-08-02 12:24:31');
INSERT INTO `it_officers` VALUES ('16', 'hatinees9@gmail.com', 'น.ส. ฮาตีณี สะมะแอ', '0844075030', 'IT รพ.', 'โรงพยาบาลแว้ง', '2026-08-02 12:24:31', '2026-08-02 12:24:31');
INSERT INTO `it_officers` VALUES ('17', 'heywae.nara@gmail.com', 'นาย มะตอเฮ เจ๊ะกา', '0862933510', 'IT รพ.', 'โรงพยาบาลระแงะ', '2026-08-02 12:24:31', '2026-08-02 12:24:31');
INSERT INTO `it_officers` VALUES ('18', 'mhd.saifudin@gmail.com', 'นาย มาหะมะ กาลาแต', '0808633537', 'IT รพ.', 'โรงพยาบาลจะแนะ', '2026-08-02 12:24:31', '2026-08-02 12:24:31');
INSERT INTO `it_officers` VALUES ('19', 'tammarat.k@gmail.com', 'นาย ธรรมรัตน์ กาเด็น', '0867474716', 'Admin สสจ/เขต.', 'สำนักงานสาธารณสุขจังหวัดนราธิวาส', '2026-08-02 12:24:31', '2026-08-02 12:24:31');
INSERT INTO `it_officers` VALUES ('20', 'digitalhealthssk@moph.go.th', 'น.ส. สุวีนา ยูโซ๊ะ', '0862934806', 'IT รพ.', 'โรงพยาบาลศรีสาคร', '2026-08-02 12:24:31', '2026-08-02 12:24:31');

-- --------------------------------------------------------
-- Table structure for `users`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fullname` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('SUPER_ADMIN','ADMIN') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SUPER_ADMIN',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Records of `users`
INSERT INTO `users` VALUES ('1', 'user', '$2a$12$Slqht/F/O57xzUg6YSNvX.0Dg6qJFC77rqbyCKysvT8sXdUioGfh.', 'Nexus Health Super Admin', 'SUPER_ADMIN', '2026-07-21 14:01:25', '2026-07-23 11:48:29');

SET FOREIGN_KEY_CHECKS=1;
