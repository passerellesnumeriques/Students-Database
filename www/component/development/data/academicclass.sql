-- phpMyAdmin SQL Dump
-- version 3.5.2.2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Nov 23, 2013 at 09:02 AM
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
-- Dumping data for table `academicclass`
--

INSERT INTO `academicclass` (`id`, `period`, `specialization`, `name`) VALUES
(1, 1, NULL, 'A'),
(2, 1, NULL, 'B'),
(3, 1, NULL, 'C'),
(4, 1, NULL, 'D'),
(5, 2, 1, 'SD A'),
(6, 3, 1, 'SD A'),
(7, 4, 1, 'SD A'),
(8, 5, 1, 'SD A'),
(9, 6, 1, 'SD A'),
(10, 2, 1, 'SD B'),
(11, 3, 1, 'SD B'),
(12, 4, 1, 'SD B'),
(13, 5, 1, 'SD B'),
(14, 6, 1, 'SD B'),
(15, 2, 2, 'SNA A'),
(16, 3, 2, 'SNA A'),
(17, 4, 2, 'SNA A'),
(18, 5, 2, 'SNA A'),
(19, 6, 2, 'SNA A'),
(20, 2, 2, 'SNA B'),
(21, 3, 2, 'SNA B'),
(22, 4, 2, 'SNA B'),
(23, 5, 2, 'SNA B'),
(24, 6, 2, 'SNA B');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
