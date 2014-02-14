-- phpMyAdmin SQL Dump
-- version 3.5.2.2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Dec 04, 2013 at 09:22 AM
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
-- Dumping data for table `ContactPoint`
--

INSERT INTO `ContactPoint` (`organization`, `people`, `designation`) VALUES
(1, 91, 'chef'),
(1, 92, 'boss'),
(3, 93, 'CEO');

--
-- Dumping data for table `organization`
--

INSERT INTO `organization` (`id`, `name`, `creator`) VALUES
(1, 'PN', 'Selection'),
(2, 'titi', 'Selection'),
(3, 'toto', 'Selection'),
(4, 'tata', 'Selection');

--
-- Dumping data for table `OrganizationAddress`
--

INSERT INTO `OrganizationAddress` (`organization`, `address`) VALUES
(1, 1),
(1, 2),
(2, 3);


--
-- Dumping data for table `PostalAddress`
--

INSERT INTO `PostalAddress` (`id`, `country`, `geographic_area`, `street`, `street_number`, `building`, `unit`, `additional`, `address_type`) VALUES
(1, 1, 5, 'toto', '2', 'toto', 'toto', 'toto', 'Work'),
(2, 1, 6, 'titi', '2', 'titi', 'titi', 'titi', 'Work'),
(3, 1, 7, 'tata', '2', 'tata', 'tata', 'tata', 'Work');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
