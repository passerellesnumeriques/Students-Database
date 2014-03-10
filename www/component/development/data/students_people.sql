-- phpMyAdmin SQL Dump
-- version 3.5.2.2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Nov 23, 2013 at 09:04 AM
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
-- Dumping data for table `people`
--

INSERT INTO `people` (`id`, `first_name`, `middle_name`, `last_name`, `khmer_first_name`, `khmer_last_name`, `sex`, `birth`, `picture`, `picture_version`, `types`) VALUES
(17, 'Christianly', 'Pacilan', 'ABELLO', NULL, NULL, 'M', '1996-04-06', NULL, NULL, 'student'),
(18, 'Judy Ann', 'Nellas', 'ADALIA', NULL, NULL, 'F', '1996-04-26', NULL, NULL, 'student'),
(19, 'Vincent', 'Alcazar', 'AMARO', NULL, NULL, 'M', '1994-04-18', NULL, NULL, 'student'),
(20, 'Glainess', 'Brion', 'ARRIESGADO', NULL, NULL, 'F', '1996-12-10', NULL, NULL, 'student'),
(21, 'Julieto', 'Plazo', 'ASENJO', NULL, NULL, 'M', '1995-07-11', NULL, NULL, 'student'),
(22, 'Jerlyn', 'Ceritta', 'ATES', NULL, NULL, 'F', '1995-10-07', NULL, NULL, 'student'),
(23, 'Merlan', 'Rabago', 'BACARISAS', NULL, NULL, 'M', '1996-04-22', NULL, NULL, 'student'),
(24, 'Mildred', 'Enero', 'BACARO', NULL, NULL, 'F', '1996-02-27', NULL, NULL, 'student'),
(25, 'Josephine', 'Balatero', 'BALEN', NULL, NULL, 'F', '1996-12-13', NULL, NULL, 'student'),
(26, 'Jesie Boy', 'Catalan', 'BALONGCAS', NULL, NULL, 'M', '1995-12-16', NULL, NULL, 'student'),
(27, 'Joven', 'Apa', 'BAN-AS', NULL, NULL, 'M', '1996-11-18', NULL, NULL, 'student'),
(28, 'Gerabeth', 'Calixto', 'BANDINO', NULL, NULL, 'F', '1996-12-23', NULL, NULL, 'student'),
(29, 'Ronelio', 'Mondido', 'BUNAOS', NULL, NULL, 'M', '1996-02-13', NULL, NULL, 'student'),
(30, 'Amador', 'Buntod', 'BUSALANAN', NULL, NULL, 'M', '1993-10-01', NULL, NULL, 'student'),
(31, 'Mark Joseph', 'Odal', 'CABUQUIT', NULL, NULL, 'M', '1996-10-17', NULL, NULL, 'student'),
(32, 'Ronilo', 'Tocmo', 'CADENAS', NULL, NULL, 'M', '1996-05-12', NULL, NULL, 'student'),
(33, 'Kenneth', 'Tabuzo', 'CAGAT-CAGAT', NULL, NULL, 'M', '1996-10-02', NULL, NULL, 'student'),
(34, 'John carlo', 'Novio', 'CAHIMAT', NULL, NULL, 'M', '1996-03-23', NULL, NULL, 'student'),
(35, 'Christopher', 'Tudtud', 'CALUMBA', NULL, NULL, 'M', '1993-08-15', NULL, NULL, 'student'),
(36, 'Junrey', 'Oyangorin', 'CAMPOSO', NULL, NULL, 'M', '1996-11-15', NULL, NULL, 'student'),
(37, 'Marjorie', 'N.A.', 'CASTRO', NULL, NULL, 'F', '1995-11-09', NULL, NULL, 'student'),
(38, 'Gibbrett', 'N.A.', 'CATAÑAR', NULL, NULL, 'M', '1994-07-13', NULL, NULL, 'student'),
(39, 'Adrian', 'Sepe', 'CAWAS', NULL, NULL, 'M', '1996-06-28', NULL, NULL, 'student'),
(40, 'Lyndon', 'Betuin', 'CENTINALES', NULL, NULL, 'M', '1996-07-31', NULL, NULL, 'student'),
(41, 'Leonie Guil', 'N.A.', 'CORONEL', NULL, NULL, 'F', '1997-02-26', NULL, NULL, 'student'),
(42, 'Michelle', 'Torres', 'DENZO', NULL, NULL, 'F', '1996-10-26', NULL, NULL, 'student'),
(43, 'Joseph', 'Kilong-kilong', 'DICDICAN', NULL, NULL, 'M', '1996-07-30', NULL, NULL, 'student'),
(44, 'Rica', 'Sesio', 'ESTELLA', NULL, NULL, 'F', '1997-02-09', NULL, NULL, 'student'),
(45, 'Rycel', 'Asada', 'EZAR', NULL, NULL, 'F', '1996-07-21', NULL, NULL, 'student'),
(46, 'Dionel', 'Torron', 'FERRERAS', NULL, NULL, 'M', '1995-09-26', NULL, NULL, 'student'),
(47, 'Agnes', 'Romares', 'FILOSOFO', NULL, NULL, 'F', '1996-07-22', NULL, NULL, 'student'),
(48, 'Christian Rey', 'Saquilabon', 'FLORES', NULL, NULL, 'M', '1996-05-26', NULL, NULL, 'student'),
(49, 'Roque', 'Tica', 'FOLMINAR', NULL, NULL, 'M', '1995-08-16', NULL, NULL, 'student'),
(50, 'Jayne Grace', 'Presilda', 'FUENTESPINA', NULL, NULL, 'F', '1996-02-11', NULL, NULL, 'student'),
(51, 'Joselito Jr.', 'Abay', 'GAMAO', NULL, NULL, 'M', '1997-10-08', NULL, NULL, 'student'),
(52, 'Mike Gil', 'Baquiano', 'GEALON', NULL, NULL, 'M', '1995-11-14', NULL, NULL, 'student'),
(53, 'Niño', 'Lucabon', 'GELIGAN', NULL, NULL, 'M', '1995-02-05', NULL, NULL, 'student'),
(54, 'Jonalyn', 'Rafayla', 'GENERALAO', NULL, NULL, 'F', '1995-12-10', NULL, NULL, 'student'),
(55, 'Alera', 'Abarquez', 'GERA', NULL, NULL, 'F', '1996-11-26', NULL, NULL, 'student'),
(56, 'Jocelyn', 'Alad-ad', 'JAVIER', NULL, NULL, 'F', '1996-09-03', NULL, NULL, 'student'),
(57, 'Carl Harvey', 'Dealagdon', 'JAYME', NULL, NULL, 'M', '1996-11-12', NULL, NULL, 'student'),
(58, 'Chesil', 'Juarez', 'JUANICH', NULL, NULL, 'F', '1996-10-05', NULL, NULL, 'student'),
(59, 'Angeline Marie', 'Jerezon', 'JUMAMIL', NULL, NULL, 'F', '1997-04-14', NULL, NULL, 'student'),
(60, 'Filjumar', 'Melecio', 'JUMAMOY', NULL, NULL, 'M', '1994-04-29', NULL, NULL, 'student'),
(61, 'Eugene', 'Batolanon', 'LAMOSTE', NULL, NULL, 'M', '1996-05-20', NULL, NULL, 'student'),
(62, 'Mark Soliver', 'Serrano', 'LAPERA', NULL, NULL, 'M', '1996-11-03', NULL, NULL, 'student'),
(63, 'Edna', 'Escaso', 'LENTERIA', NULL, NULL, 'F', '1996-07-14', NULL, NULL, 'student'),
(64, 'Mark Anthony', 'Amazon', 'LIBRADILLA', NULL, NULL, 'M', '1996-10-22', NULL, NULL, 'student'),
(65, 'Juvelyn', 'Cariliman', 'LOBINGCO', NULL, NULL, 'F', '1996-07-12', NULL, NULL, 'student'),
(66, 'Sarah', 'N.A.', 'LORICO', NULL, NULL, 'F', '1995-12-21', NULL, NULL, 'student'),
(67, 'Johna', 'Guiritan', 'MACASERO', NULL, NULL, 'F', '1996-06-14', NULL, NULL, 'student'),
(68, 'Rose Mar', 'Lazaga', 'MAGALAY', NULL, NULL, 'F', '1996-11-19', NULL, NULL, 'student'),
(69, 'Hyrel', 'Bravo', 'MALINAO', NULL, NULL, 'F', '1996-08-08', NULL, NULL, 'student'),
(70, 'Aimelo', 'N.A.', 'MALISA', NULL, NULL, 'M', '1996-03-11', NULL, NULL, 'student'),
(71, 'Harlene', 'Bautista', 'MANLAPAZ', NULL, NULL, 'F', '1997-03-25', NULL, NULL, 'student'),
(72, 'Helfe', 'Padayao', 'MARQUEZ', NULL, NULL, 'F', '1996-09-26', NULL, NULL, 'student'),
(73, 'Anne Merlyn', 'Gitongo', 'MARTINEZ', NULL, NULL, 'F', '1996-10-21', NULL, NULL, 'student'),
(74, 'Ivy', 'Pardillo', 'MIÑOZA', NULL, NULL, 'F', '1996-10-04', NULL, NULL, 'student'),
(75, 'Jelly Mae', 'Misoles', 'MOMPAR', NULL, NULL, 'F', '1996-12-13', NULL, NULL, 'student'),
(76, 'Charry Mae', 'Lumanog', 'MOYA', NULL, NULL, 'F', '1995-10-12', NULL, NULL, 'student'),
(77, 'Vanessa Rose', 'Manajero', 'NUÑEZ', NULL, NULL, 'F', '1996-09-27', NULL, NULL, 'student'),
(78, 'Rhea Mae', 'Danlasan', 'OGATES', NULL, NULL, 'F', '1996-12-14', NULL, NULL, 'student'),
(79, 'Ivy Grace', 'Ocier', 'OLASIMAN', NULL, NULL, 'F', '1996-12-06', NULL, NULL, 'student'),
(80, 'Elbert James', 'Bacamante', 'OLIVAR', NULL, NULL, 'M', '1997-10-10', NULL, NULL, 'student'),
(81, 'Gerald', 'Endruela', 'ORTEGA', NULL, NULL, 'M', '1993-10-03', NULL, NULL, 'student'),
(82, 'Stiffanny', 'N.A.', 'ORTEGA', NULL, NULL, 'F', '1995-11-08', NULL, NULL, 'student'),
(83, 'Stephen', 'Vicente', 'PADILLA', NULL, NULL, 'M', '1996-08-05', NULL, NULL, 'student'),
(84, 'Gabriel', 'Garay', 'PAITUAR', NULL, NULL, 'M', '1996-04-27', NULL, NULL, 'student'),
(85, 'Jenalyn', 'Galinggaling', 'PARAGADOS', NULL, NULL, 'F', '1997-02-14', NULL, NULL, 'student'),
(86, 'Daniel', 'Biras', 'PESARE', NULL, NULL, 'M', '1996-11-24', NULL, NULL, 'student'),
(87, 'Juvilyn', 'Mumar', 'POROL', NULL, NULL, 'F', '1996-08-24', NULL, NULL, 'student'),
(88, 'Tressa', 'Padoga', 'PRADILLA', NULL, NULL, 'F', '1996-04-03', NULL, NULL, 'student'),
(89, 'Eunice Faith', 'Batuhinay', 'PUDE', NULL, NULL, 'F', '1996-02-18', NULL, NULL, 'student'),
(90, 'Norman', 'Gipega', 'PULOD', NULL, NULL, 'M', '1996-07-22', NULL, NULL, 'student'),
(91, 'Marisa', 'Pongase', 'QUIAOT', NULL, NULL, 'F', '1997-06-10', NULL, NULL, 'student'),
(92, 'Cristy Joy', 'Rabago', 'RAZO', NULL, NULL, 'F', '1996-05-07', NULL, NULL, 'student'),
(93, 'Nholyn Marie', 'Arrogante', 'REYES', NULL, NULL, 'F', '1997-03-01', NULL, NULL, 'student'),
(94, 'Elizabeth', 'Romano', 'ROSALES', NULL, NULL, 'F', '1997-06-01', NULL, NULL, 'student'),
(95, 'Gleen', 'Correche', 'SABULAAN', NULL, NULL, 'M', '1997-09-24', NULL, NULL, 'student'),
(96, 'Rowena', 'Gamayot', 'SALVALEON', NULL, NULL, 'F', '1996-10-05', NULL, NULL, 'student'),
(97, 'Russel Jhon', 'Abasolo', 'SEARES', NULL, NULL, 'M', '1997-09-09', NULL, NULL, 'student'),
(98, 'Mylene', 'Tagaro', 'SILVA', NULL, NULL, 'F', '1995-08-27', NULL, NULL, 'student'),
(99, 'Gingen', 'Mahilum', 'SIMEON', NULL, NULL, 'F', '1996-09-09', NULL, NULL, 'student'),
(100, 'Lineth', 'Quillan', 'SUERTE', NULL, NULL, 'F', '1996-08-28', NULL, NULL, 'student'),
(101, 'Joann', 'Getutua', 'TACSAN', NULL, NULL, 'F', '1997-05-15', NULL, NULL, 'student'),
(102, 'Mely', 'Domugho', 'TIMTIM', NULL, NULL, 'F', '1994-08-24', NULL, NULL, 'student'),
(103, 'Ailyn', 'Paspie', 'TIONGZON', NULL, NULL, 'F', '1997-03-20', NULL, NULL, 'student'),
(104, 'Marvin', 'Horca', 'TORDILLOS', NULL, NULL, 'M', '1996-04-07', NULL, NULL, 'student'),
(105, 'Gladys', 'Daverao', 'VAILOCES', NULL, NULL, 'F', '1996-09-11', NULL, NULL, 'student'),
(106, 'Clark Garlou', 'Magbanua', 'YAP', NULL, NULL, 'M', '1996-01-20', NULL, NULL, 'student');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
