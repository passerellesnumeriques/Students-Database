-- phpMyAdmin SQL Dump
-- version 4.1.6
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jul 14, 2014 at 05:42 AM
-- Server version: 5.5.23
-- PHP Version: 5.4.24

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
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

INSERT INTO `academicperiod` (`id`, `year`, `name`, `start`, `end`, `weeks`, `weeks_break`) VALUES
(1, 1, 'Semester 1', '2009-06-01', '2009-10-04', 18, 0),
(2, 1, 'Semester 2', '2009-11-02', '2010-03-21', 18, 2),
(3, 1, 'Summer', '2010-04-05', '2010-05-02', 4, 0),
(4, 2, 'Semester 1', '2010-06-07', '2010-10-10', 18, 0),
(5, 2, 'Semester 2', '2010-11-01', '2011-03-20', 18, 2),
(6, 2, 'Summer', '2011-04-04', '2011-05-01', 4, 0),
(7, 3, 'Semester 1', '2011-06-06', '2011-10-09', 18, 0),
(8, 3, 'Semester 2', '2011-11-07', '2012-03-25', 18, 2),
(9, 3, 'Summer', '2012-04-02', '2012-04-29', 4, 0),
(10, 4, 'Semester 1', '2012-06-04', '2012-10-07', 18, 0),
(11, 4, 'Semester 2', '2012-11-05', '2013-03-24', 18, 2),
(12, 4, 'Summer', '2013-04-01', '2013-04-28', 4, 0),
(13, 5, 'Semester 1', '2013-06-03', '2013-10-06', 18, 0),
(14, 5, 'Semester 2', '2013-11-04', '2014-03-23', 18, 2),
(15, 5, 'Summer', '2014-04-07', '2014-05-04', 4, 0),
(16, 6, 'Semester 1', '2014-06-02', '2014-10-05', 18, 0),
(17, 6, 'Semester 2', '2014-11-03', '2015-03-22', 18, 2),
(18, 6, 'Summer', '2015-04-06', '2015-05-03', 4, 0),
(19, 7, 'Semester 1', '2015-06-01', '2015-10-04', 18, 0),
(20, 7, 'Semester 2', '2015-11-02', '2016-03-20', 18, 2),
(21, 7, 'Summer', '2016-04-04', '2016-05-01', 4, 0),
(22, 8, 'Semester 1', '2016-06-06', '2016-10-09', 18, 0),
(23, 8, 'Semester 2', '2016-11-07', '2017-03-26', 18, 2),
(24, 8, 'Summer', '2017-04-03', '2017-04-30', 4, 0);

--
-- Dumping data for table `academicyear`
--

INSERT INTO `academicyear` (`id`, `year`, `name`) VALUES
(1, 2009, '2009-2010'),
(2, 2010, '2010-2011'),
(3, 2011, '2011-2012'),
(4, 2012, '2012-2013'),
(5, 2013, '2013-2014'),
(6, 2014, '2014-2015'),
(7, 2015, '2015-2016'),
(8, 2016, '2016-2017');

--
-- Dumping data for table `batchperiod`
--

INSERT INTO `batchperiod` (`id`, `batch`, `academic_period`, `name`) VALUES
(1, 1, 1, 'Semester 1'),
(2, 1, 2, 'Semester 2'),
(3, 1, 4, 'Semester 3'),
(4, 1, 5, 'Semester 4'),
(5, 1, 7, 'Semester 5'),
(6, 1, 8, 'Semester 6'),
(7, 2, 4, 'Semester 1'),
(8, 2, 5, 'Semester 2'),
(10, 2, 7, 'Semester 3'),
(11, 2, 8, 'Semester 4'),
(13, 2, 10, 'Semester 5'),
(14, 2, 11, 'Semester 6'),
(16, 3, 7, 'Semester 1'),
(17, 3, 8, 'Semester 2'),
(19, 3, 10, 'Semester 3'),
(20, 3, 11, 'Semester 4'),
(22, 3, 13, 'Semester 5'),
(23, 3, 14, 'Semester 6'),
(25, 4, 10, 'Semester 1'),
(26, 4, 11, 'Semester 2'),
(27, 4, 12, 'Summer'),
(28, 4, 13, 'Semester 3'),
(29, 4, 14, 'Semester 4'),
(30, 4, 16, 'Semester 5'),
(31, 4, 17, 'Semester 6'),
(32, 5, 13, 'Semester 1'),
(33, 5, 14, 'Semester 2'),
(34, 5, 15, 'Summer'),
(35, 5, 16, 'Semester 3'),
(36, 5, 17, 'Semester 4'),
(37, 5, 19, 'Semester 5'),
(38, 5, 20, 'Semester 6'),
(39, 6, 16, 'Semester 1'),
(40, 6, 17, 'Semester 2'),
(41, 6, 18, 'Summer 1'),
(42, 6, 19, 'Semester 3'),
(43, 6, 20, 'Semester 4'),
(44, 6, 22, 'Semester 5'),
(45, 6, 23, 'Semester 6');

--
-- Dumping data for table `batchperiodspecialization`
--

INSERT INTO `batchperiodspecialization` (`period`, `specialization`) VALUES
(26, 1),
(26, 2),
(27, 1),
(27, 2),
(28, 1),
(28, 2),
(29, 1),
(29, 2),
(30, 1),
(30, 2),
(31, 1),
(31, 2),
(33, 1),
(33, 2),
(34, 1),
(34, 2),
(35, 1),
(35, 2),
(36, 1),
(36, 2),
(37, 1),
(37, 2),
(38, 1),
(38, 2),
(40, 1),
(40, 2),
(41, 1),
(41, 2),
(42, 1),
(42, 2),
(43, 1),
(43, 2),
(44, 1),
(44, 2),
(45, 1),
(45, 2);

--
-- Dumping data for table `studentbatch`
--

INSERT INTO `studentbatch` (`id`, `name`, `start_date`, `end_date`) VALUES
(1, '2012', '2009-05-14', '2012-04-29'),
(2, '2013', '2010-05-20', '2013-04-28'),
(3, '2014', '2011-05-14', '2014-05-04'),
(4, '2015', '2012-05-24', '2015-05-03'),
(5, '2016', '2013-05-23', '2016-05-01'),
(6, '2017', '2014-05-22', '2017-04-30');

--
-- Dumping data for table `academicclass`
--

INSERT INTO `academicclass` (`id`, `period`, `specialization`, `name`) VALUES
(1, 1, NULL, 'Pioneers'),
(2, 2, NULL, 'Pioneers'),
(3, 3, NULL, 'Pioneers'),
(4, 4, NULL, 'Pioneers'),
(5, 5, NULL, 'Pioneers'),
(6, 6, NULL, 'Pioneers'),
(7, 7, NULL, 'A'),
(8, 8, NULL, 'A'),
(9, 10, NULL, 'A'),
(10, 11, NULL, 'A'),
(11, 13, NULL, 'A'),
(12, 14, NULL, 'A'),
(13, 7, NULL, 'B'),
(14, 8, NULL, 'B'),
(15, 10, NULL, 'B'),
(16, 11, NULL, 'B'),
(17, 13, NULL, 'B'),
(18, 14, NULL, 'B'),
(19, 16, NULL, 'A'),
(20, 17, NULL, 'A'),
(21, 19, NULL, 'A'),
(22, 20, NULL, 'A'),
(23, 22, NULL, 'A'),
(24, 23, NULL, 'A'),
(25, 16, NULL, 'B'),
(26, 17, NULL, 'B'),
(27, 19, NULL, 'B'),
(28, 20, NULL, 'B'),
(29, 22, NULL, 'B'),
(30, 23, NULL, 'B'),
(31, 25, NULL, 'A'),
(32, 25, NULL, 'B'),
(33, 25, NULL, 'C'),
(34, 25, NULL, 'D'),
(35, 26, 1, 'SD A'),
(36, 27, 1, 'SD A'),
(37, 28, 1, 'SD A'),
(38, 29, 1, 'SD A'),
(39, 30, 1, 'SD A'),
(40, 31, 1, 'SD A'),
(41, 26, 1, 'SD B'),
(42, 27, 1, 'SD B'),
(43, 28, 1, 'SD B'),
(44, 29, 1, 'SD B'),
(45, 30, 1, 'SD B'),
(46, 31, 1, 'SD B'),
(47, 26, 2, 'SNA A'),
(48, 27, 2, 'SNA A'),
(49, 28, 2, 'SNA A'),
(50, 29, 2, 'SNA A'),
(51, 30, 2, 'SNA A'),
(52, 31, 2, 'SNA A'),
(53, 26, 2, 'SNA B'),
(54, 27, 2, 'SNA B'),
(55, 28, 2, 'SNA B'),
(56, 29, 2, 'SNA B'),
(57, 30, 2, 'SNA B'),
(58, 31, 2, 'SNA B'),
(59, 32, NULL, 'A'),
(60, 32, NULL, 'B'),
(61, 32, NULL, 'C'),
(62, 32, NULL, 'D'),
(63, 33, 1, 'SD A'),
(64, 34, 1, 'SD A'),
(65, 35, 1, 'SD A'),
(66, 36, 1, 'SD A'),
(67, 37, 1, 'SD A'),
(68, 38, 1, 'SD A'),
(69, 33, 1, 'SD B'),
(70, 34, 1, 'SD B'),
(71, 35, 1, 'SD B'),
(72, 36, 1, 'SD B'),
(73, 37, 1, 'SD B'),
(74, 38, 1, 'SD B'),
(75, 33, 2, 'SNA A'),
(76, 34, 2, 'SNA A'),
(77, 35, 2, 'SNA A'),
(78, 36, 2, 'SNA A'),
(79, 37, 2, 'SNA A'),
(80, 38, 2, 'SNA A'),
(81, 33, 2, 'SNA B'),
(82, 34, 2, 'SNA B'),
(83, 35, 2, 'SNA B'),
(84, 36, 2, 'SNA B'),
(85, 37, 2, 'SNA B'),
(86, 38, 2, 'SNA B'),
(87, 39, NULL, 'A'),
(88, 39, NULL, 'B'),
(89, 39, NULL, 'C'),
(90, 39, NULL, 'D'),
(91, 40, 1, 'SD A'),
(92, 41, 1, 'SD A'),
(93, 42, 1, 'SD A'),
(94, 43, 1, 'SD A'),
(95, 44, 1, 'SD A'),
(96, 45, 1, 'SD A'),
(97, 40, 1, 'SD B'),
(98, 41, 1, 'SD B'),
(99, 42, 1, 'SD B'),
(100, 43, 1, 'SD B'),
(101, 44, 1, 'SD B'),
(102, 45, 1, 'SD B'),
(103, 40, 2, 'SNA A'),
(104, 41, 2, 'SNA A'),
(105, 42, 2, 'SNA A'),
(106, 43, 2, 'SNA A'),
(107, 44, 2, 'SNA A'),
(108, 45, 2, 'SNA A'),
(109, 40, 2, 'SNA B'),
(110, 41, 2, 'SNA B'),
(111, 42, 2, 'SNA B'),
(112, 43, 2, 'SNA B'),
(113, 44, 2, 'SNA B'),
(114, 45, 2, 'SNA B');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
