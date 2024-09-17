-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 15, 2024 at 07:01 AM
-- Server version: 8.3.0
-- PHP Version: 8.2.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `smartattendtest`
--

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

DROP TABLE IF EXISTS `assignments`;
CREATE TABLE IF NOT EXISTS `assignments` (
  `assignment_id` int NOT NULL AUTO_INCREMENT,
  `class_id` int NOT NULL,
  `student_id` int NOT NULL,
  `submission_date` datetime DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `grade` float DEFAULT NULL,
  PRIMARY KEY (`assignment_id`),
  KEY `class_id` (`class_id`),
  KEY `student_id` (`student_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `assignment_analysis`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `assignment_analysis`;
CREATE TABLE IF NOT EXISTS `assignment_analysis` (
`class_name` varchar(100)
,`course` varchar(100)
,`grade` float
,`student_id` int
);

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
CREATE TABLE IF NOT EXISTS `attendance` (
  `attendance_id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `class_id` int NOT NULL,
  `attendance_time` datetime DEFAULT NULL,
  `student_location` varchar(255) DEFAULT NULL,
  `is_present` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`attendance_id`),
  KEY `student_id` (`student_id`),
  KEY `class_id` (`class_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `attendance_analysis`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `attendance_analysis`;
CREATE TABLE IF NOT EXISTS `attendance_analysis` (
`attendance_percentage` decimal(30,4)
,`attended_classes` decimal(23,0)
,`class_name` varchar(100)
,`course` varchar(100)
,`student_id` int
,`total_classes` bigint
);

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

DROP TABLE IF EXISTS `classes`;
CREATE TABLE IF NOT EXISTS `classes` (
  `class_id` int NOT NULL AUTO_INCREMENT,
  `class_name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`class_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`class_id`, `class_name`) VALUES
(1, '112 SE'),
(2, '114 SE');

-- --------------------------------------------------------

--
-- Table structure for table `lecture_materials`
--

DROP TABLE IF EXISTS `lecture_materials`;
CREATE TABLE IF NOT EXISTS `lecture_materials` (
  `material_id` int NOT NULL AUTO_INCREMENT,
  `class_id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `upload_date` datetime DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`material_id`),
  KEY `class_id` (`class_id`),
  KEY `teacher_id` (`teacher_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `management`
--

DROP TABLE IF EXISTS `management`;
CREATE TABLE IF NOT EXISTS `management` (
  `management_id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `position` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone_number` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`management_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `qr_codes`
--

DROP TABLE IF EXISTS `qr_codes`;
CREATE TABLE IF NOT EXISTS `qr_codes` (
  `qr_code_id` int NOT NULL AUTO_INCREMENT,
  `class_id` int NOT NULL,
  `generated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `geo_location` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL,
  PRIMARY KEY (`qr_code_id`),
  KEY `class_id` (`class_id`)
) ENGINE=MyISAM AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `qr_codes`
--

INSERT INTO `qr_codes` (`qr_code_id`, `class_id`, `generated_at`, `geo_location`, `expires_at`) VALUES
(28, 1, '2024-09-10 14:07:29', '6.9533696,79.8621696', '2024-09-10 08:38:29'),
(27, 1, '2024-09-10 13:40:24', '6.9533696,79.8621696', '2024-09-10 08:11:24'),
(26, 1, '2024-09-10 13:13:28', '6.9533696,79.8621696', '2024-09-10 07:44:28'),
(25, 1, '2024-09-10 13:11:29', '6.9533696,79.8621696', '2024-09-10 07:42:29'),
(24, 1, '2024-09-10 12:56:04', '6.9533696,79.8621696', '2024-09-10 07:27:04'),
(23, 1, '2024-09-09 08:26:33', '6.9533696,79.8621696', '2024-09-09 02:57:33'),
(22, 1, '2024-09-09 08:20:42', '6.9533696,79.8621696', '2024-09-09 02:51:42'),
(21, 1, '2024-09-09 08:18:34', '6.9533696,79.8621696', '2024-09-09 02:49:34'),
(20, 1, '2024-09-09 08:16:56', '6.9533696,79.8621696', '2024-09-09 02:47:56'),
(19, 1, '2024-09-09 08:12:12', '', '2024-09-09 02:43:12'),
(18, 1, '2024-09-09 07:18:11', '6.9533696,79.8621696', '2024-09-09 01:49:11'),
(16, 1, '2024-09-09 06:32:09', '', '2024-09-09 01:03:09'),
(17, 1, '2024-09-09 06:59:00', '6.9270786,79.861243', '2024-09-09 01:30:00'),
(29, 1, '2024-09-10 15:43:04', '6.9533696,79.8621696', '2024-09-10 10:14:04'),
(30, 1, '2024-09-10 15:49:54', '6.9533696,79.8621696', '2024-09-10 10:20:54'),
(31, 1, '2024-09-10 16:57:40', '6.9964158,79.8997702', '2024-09-10 11:28:40'),
(32, 1, '2024-09-10 17:02:18', '6.9964165,79.8997707', '2024-09-10 11:33:18'),
(33, 1, '2024-09-10 17:10:51', '6.9964144,79.8997724', '2024-09-10 11:41:51');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
CREATE TABLE IF NOT EXISTS `students` (
  `student_id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone_number` varchar(15) DEFAULT NULL,
  `course` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`student_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `username`, `password`, `name`, `email`, `phone_number`, `course`) VALUES
(2, 'Milusha', '$2y$10$eLsiFvTTXn6Pp.9YSzIjXOPck3/bB8Yy/F9k67Y8F0KNSnwHrU.n2', 'MilushaSilva', 'milushasilva003@gmail.com', '0775455867', '112 SE'),
(3, 'Vihanga', '$2y$10$nOXoh2Gb1JJzvu8TxvL9JuzzeucY6dJMhC5rcIQSy2YKmxRDx0CrC', 'VihangaKaveesh', 'vihanga@gmail.com', '0775455855', '112 SE'),
(4, 'Hashen', '$2y$10$6NgJrj/xIoGbeUZaJOEWC.mU0uN5JptEf5FIMdFqbuATgrTaZh.cS', 'MilushaSilva', 'milushasilva03@gmail.com', '0775455867', '112 SE'),
(5, 'avishka123', '$2y$10$A/ZolKjibtX4nRc9H267mO2bUCI0cscdXfRJ9wK5WXxNvsMXCnOoW', 'AvishkaShashindu', 'avishka@gmail.com', '0775455866', '114 SE'),
(6, 'Hiraj', '$2y$10$kMKrHQSIOmEu6eAU.NXhMeYDscFIN82j2k1tewzW/xb6UzxJU/FXu', 'HirajSilva', 'hiraj@gmail.com', '0775455866', '112 SE');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

DROP TABLE IF EXISTS `teachers`;
CREATE TABLE IF NOT EXISTS `teachers` (
  `teacher_id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone_number` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`teacher_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`teacher_id`, `username`, `password`, `name`, `department`, `email`, `phone_number`) VALUES
(3, 'Milusha', '$2y$10$/gfzruQQTRCLCv4VASwIveN3ZlsUtsGq9A0Ndp8K7CB0ePFB.IFqq', 'MilushaSilva', '', 'milushasilva@gmail.com', '0775455867'),
(4, 'Vihanga', '$2y$10$YPObm2HSZwc/c5CXGYUineLv8Ts5wPeInqhrJUPHqQkWZjQLC00wu', 'VihangaKaveesh', NULL, 'vihanga@gmail.com', '0775455855'),
(5, 'Hashen', '$2y$10$A03AunhuRNy3kaG/a1MxRushEO4ObtTIXy/f68R9qLi4bxtDH5D.2', 'HashenSilva', NULL, 'hashen@gmail.com', '0775455867'),
(7, 'avishka123', '$2y$10$HNyZUAax08Og4L9CwfX9/ueG.cCC3QB7S0mzE6eFaq9pf7xcFYyzq', 'AvishkaShashindu', NULL, 'avishkaa@gmail.com', '0775455855');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_courses`
--

DROP TABLE IF EXISTS `teacher_courses`;
CREATE TABLE IF NOT EXISTS `teacher_courses` (
  `teacher_id` int NOT NULL,
  `class_id` int NOT NULL,
  PRIMARY KEY (`teacher_id`,`class_id`),
  KEY `class_id` (`class_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `teacher_courses`
--

INSERT INTO `teacher_courses` (`teacher_id`, `class_id`) VALUES
(3, 1),
(4, 1),
(5, 1),
(7, 1),
(7, 2);

-- --------------------------------------------------------

--
-- Structure for view `assignment_analysis`
--
DROP TABLE IF EXISTS `assignment_analysis`;

DROP VIEW IF EXISTS `assignment_analysis`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `assignment_analysis`  AS SELECT `s`.`student_id` AS `student_id`, `s`.`course` AS `course`, `c`.`class_name` AS `class_name`, `a`.`grade` AS `grade` FROM ((`students` `s` join `assignments` `a` on((`s`.`student_id` = `a`.`student_id`))) join `classes` `c` on((`a`.`class_id` = `c`.`class_id`))) ;

-- --------------------------------------------------------

--
-- Structure for view `attendance_analysis`
--
DROP TABLE IF EXISTS `attendance_analysis`;

DROP VIEW IF EXISTS `attendance_analysis`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `attendance_analysis`  AS SELECT `s`.`student_id` AS `student_id`, `s`.`course` AS `course`, `c`.`class_name` AS `class_name`, count(`a`.`attendance_id`) AS `total_classes`, sum((case when (`a`.`is_present` = 1) then 1 else 0 end)) AS `attended_classes`, ((sum((case when (`a`.`is_present` = 1) then 1 else 0 end)) / count(`a`.`attendance_id`)) * 100) AS `attendance_percentage` FROM ((`students` `s` join `attendance` `a` on((`s`.`student_id` = `a`.`student_id`))) join `classes` `c` on((`a`.`class_id` = `c`.`class_id`))) GROUP BY `s`.`student_id`, `c`.`class_name` ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
