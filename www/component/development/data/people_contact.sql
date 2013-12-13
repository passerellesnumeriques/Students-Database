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
-- Dumping data for table `people_contact`
--

INSERT INTO `people_contact` (`contact`, `people`) VALUES
(91, 1),
(94, 1),
(97, 1),
(92, 4),
(95, 4),
(98, 4),
(93, 3),
(96, 3),
(99, 3);

--
-- Dumping data for table `contact`
--

INSERT INTO `contact` (`id`, `type`, `sub_type`, `contact`) VALUES
(91, 'email', 'work', 'toto@gmail.com'),
(92, 'email', 'work', 'titi@gmail.com'),
(93, 'email', 'work', 'tata@gmail.com'),
(94, 'IM', 'work', 'toto'),
(95, 'IM', 'work', 'tata'),
(96, 'IM', 'work', 'titi'),
(97, 'phone', 'work', '123'),
(98, 'phone', 'work', '456'),
(99, 'phone', 'work', '789');
