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
-- Table structure for table `tbl_settings`
--

DROP TABLE IF EXISTS `tbl_settings`;
CREATE TABLE IF NOT EXISTS `tbl_settings` (
  `set_id` int(11) NOT NULL,
  `logo` varchar(255) NOT NULL DEFAULT 'logo.png',
  `favicon` varchar(45) NOT NULL DEFAULT 'favicon.ico',
  `sidebarlogo` varchar(45) NOT NULL,
  `appname` varchar(100) NOT NULL,
  `abbrvtn` varchar(15) NOT NULL,
  `version` varchar(10) DEFAULT '1.0',
  `addedby` varchar(100) NOT NULL DEFAULT ' admin@churchapp.com',
  `dateadded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `editedby` varchar(100) DEFAULT NULL,
  `dateedited` date DEFAULT NULL,
  PRIMARY KEY (`set_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `tbl_settings`
--

INSERT INTO `tbl_settings` (`set_id`, `logo`, `favicon`, `sidebarlogo`, `appname`, `abbrvtn`, `version`, `addedby`, `dateadded`, `editedby`, `dateedited`) VALUES
(1, 'logo.png', 'favicon.ico', 'sidebarlogo.png', 'Open Modal With ID', 'OMWID', '1.0', 'admin@openmodalwtid.com', '2022-09-18 00:23:21', 'admin@openmodalwtid.com', '2022-10-19');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
