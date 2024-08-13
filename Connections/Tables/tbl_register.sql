-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Aug 13, 2024 at 04:14 PM
-- Server version: 5.7.40
-- PHP Version: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `openmodalwtid`
--

-- --------------------------------------------------------

--
-- Table structure for table `tbl_register`
--

DROP TABLE IF EXISTS `tbl_register`;
CREATE TABLE IF NOT EXISTS `tbl_register` (
  `reg_id` int(11) NOT NULL AUTO_INCREMENT,
  `regNumber` varchar(255) DEFAULT NULL,
  `surname` varchar(100) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `status` varchar(45) DEFAULT '0',
  `verification_id` varchar(255) NOT NULL,
  `verification_status` varchar(10) NOT NULL,
  `dateadded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dateedited` date DEFAULT NULL,
  `editedby` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`reg_id`),
  UNIQUE KEY `regNumber` (`regNumber`)
) ENGINE=InnoDB AUTO_INCREMENT=651 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `tbl_register`
--

INSERT INTO `tbl_register` (`reg_id`, `regNumber`, `surname`, `firstname`, `status`, `verification_id`, `verification_status`, `dateadded`, `dateedited`, `editedby`) VALUES
(7, NULL, 'Fawole', 'Bolanle', '0', '255998', '0', '2023-07-17 08:50:04', NULL, NULL),
(8, NULL, 'Fawole', 'Bolanle', '0', '208500', '0', '2023-07-17 08:50:04', NULL, NULL),
(9, NULL, 'Fawole', 'Bolanle', '0', '415595', '0', '2023-07-17 08:50:15', NULL, NULL),
(10, NULL, 'Esang', 'Aniefiok', '0', '755800', '0', '2023-07-17 14:05:08', NULL, NULL),
(11, NULL, 'Eyong', 'Victor', '0', '676031', '0', '2023-07-17 15:11:45', NULL, NULL),
(12, NULL, 'Archibong', 'Edward', '0', '848107', '0', '2023-07-17 17:31:53', NULL, NULL),
(13, NULL, 'Oko', 'John', '0', '121761', '0', '2023-07-17 19:44:41', NULL, NULL),
(514, NULL, 'EKPIKEN-EKANEM', 'ROIBITO', '0', '141362', '0', '2023-08-27 00:01:00', NULL, NULL),
(515, NULL, 'EKPIKEN-EKANEM', 'ROIBITO', '0', '896867', '0', '2023-08-27 00:01:59', NULL, NULL),
(516, NULL, 'EKPIKEN-EKANEM', 'ROIBITO', '0', '160161', '0', '2023-08-27 00:14:14', NULL, NULL);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
