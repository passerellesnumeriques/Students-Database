-- phpMyAdmin SQL Dump
-- version 3.5.2.2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Sep 30, 2013 at 05:39 AM
-- Server version: 5.5.23
-- PHP Version: 5.3.9

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `students_dev`
--

--
-- Dumping data for table `country`
--

INSERT INTO `country` (`id`, `code`, `name`) VALUES
(1, 'PH', 'Philippines');

--
-- Dumping data for table `country_division`
--

INSERT INTO `country_division` (`id`, `country`, `parent`, `name`) VALUES
(1, 1, NULL, 'Province'),
(2, 1, 1, 'Municipality'),
(3, 1, 2, 'Baranguay');

--
-- Dumping data for table `geographic_area`
--

INSERT INTO `geographic_area` (`id`, `country_division`, `name`, `parent`) VALUES
(1, 1, 'Cebu', NULL),
(2, 1, 'Negros', NULL),
(3, 2, 'Cebu City', 1),
(4, 3, 'Talamban', 3),
(5, 3, 'Mandaue', 3),
(6, 2, 'Oslob', 1),
(7, 2, 'Moalboal', 1),
(8, 2, 'Dumaguete', 2),
(9, 2, 'San Carlos', 2);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
