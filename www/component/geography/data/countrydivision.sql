-- phpMyAdmin SQL Dump
-- version 4.1.6
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Aug 01, 2014 at 08:18 AM
-- Server version: 5.5.23
-- PHP Version: 5.4.24

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `students_pnp`
--

--
-- Dumping data for table `countrydivision`
--

INSERT INTO `CountryDivision` (`id`, `country`, `parent`, `name`) VALUES
(1, 1, NULL, 'Province'),
(2, 1, 1, 'Municipality'),
(3, 1, 2, 'Barangay'),
(4, 92, NULL, 'Province'),
(5, 92, 4, 'District'),
(6, 92, 5, 'Commune'),
(7, 92, 6, 'Village');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
