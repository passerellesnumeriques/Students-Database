-- phpMyAdmin SQL Dump
-- version 3.5.2.2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Nov 23, 2013 at 09:03 AM
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
-- Dumping data for table `curriculumsubject`
--

INSERT INTO `curriculumsubject` (`id`, `category`, `period`, `specialization`, `code`, `name`) VALUES
(1, 1, 1, NULL, 'SNA 111A', 'Basic Programming (Lectures)'),
(2, 1, 1, NULL, 'SNA 111AL', 'Basic Programming (Laboratory)'),
(3, 2, 1, NULL, 'LM 111', 'Life Mathematics 1');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
