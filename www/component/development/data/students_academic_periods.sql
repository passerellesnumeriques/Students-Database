-- phpMyAdmin SQL Dump
-- version 3.5.2.2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Nov 20, 2013 at 10:04 AM
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
-- Dumping data for table `academicperiod`
--

INSERT INTO `academicperiod` (`id`, `batch`, `name`, `start_date`, `end_date`) VALUES
(1, 1, 'Sem 1', '2013-06-03', '2013-10-18'),
(2, 1, 'Sem 2', '2013-11-04', '2014-03-21'),
(3, 1, 'Sem 3', '2014-06-02', '2014-10-17'),
(4, 1, 'Sem 4', '2014-11-03', '2015-03-27'),
(5, 1, 'Sem 5', '2015-06-01', '2015-10-23'),
(6, 1, 'Sem 6', '2015-11-02', '2016-03-31');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
