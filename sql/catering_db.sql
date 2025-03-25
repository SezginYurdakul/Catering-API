-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Mar 25, 2025 at 05:46 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `catering_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `Facilities`
--

CREATE TABLE `Facilities` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `creation_date` datetime NOT NULL DEFAULT current_timestamp(),
  `location_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `Facilities`
--

INSERT INTO `Facilities` (`id`, `name`, `creation_date`, `location_id`) VALUES
(1, 'Amsterdam Grand Catering', '2025-03-25 17:46:11', 1),
(2, 'Rotterdam Event Center', '2025-03-25 17:46:11', 2),
(3, 'The Hague Banquet Hall', '2025-03-25 17:46:11', 3),
(4, 'Utrecht Party Venue', '2025-03-25 17:46:11', 4),
(5, 'Eindhoven Outdoor Events', '2025-03-25 17:46:11', 5),
(6, 'Groningen Conference Center', '2025-03-25 17:46:11', 6),
(7, 'Maastricht Wedding Hall', '2025-03-25 17:46:11', 7),
(8, 'Leiden Private Dining', '2025-03-25 17:46:11', 8),
(9, 'Delft Cultural Events', '2025-03-25 17:46:11', 9),
(10, 'Haarlem Exclusive Catering', '2025-03-25 17:46:11', 10),
(11, 'Zwolle Business Events', '2025-03-25 17:46:11', 11),
(12, 'Arnhem Outdoor Catering', '2025-03-25 17:46:11', 12),
(13, 'Breda Conference Hall', '2025-03-25 17:46:11', 13),
(14, 'Tilburg Wedding Venue', '2025-03-25 17:46:11', 14),
(15, 'Almere Private Events', '2025-03-25 17:46:11', 15),
(16, 'Nijmegen Banquet Hall', '2025-03-25 17:46:11', 16),
(17, 'Apeldoorn Cultural Center', '2025-03-25 17:46:11', 17),
(18, 'Amersfoort Exclusive Dining', '2025-03-25 17:46:11', 18),
(19, 'Den Bosch Party Hall', '2025-03-25 17:46:11', 19),
(20, 'Haarlemmermeer Event Venue', '2025-03-25 17:46:11', 20),
(21, 'Zoetermeer Grand Hall', '2025-03-25 17:46:11', 21),
(22, 'Leeuwarden Outdoor Events', '2025-03-25 17:46:11', 22),
(23, 'Deventer Conference Center', '2025-03-25 17:46:11', 23),
(24, 'Enschede Private Dining', '2025-03-25 17:46:11', 24),
(25, 'Hengelo Exclusive Catering', '2025-03-25 17:46:11', 25);

-- --------------------------------------------------------

--
-- Table structure for table `Facility_Tags`
--

CREATE TABLE `Facility_Tags` (
  `facility_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `Facility_Tags`
--

INSERT INTO `Facility_Tags` (`facility_id`, `tag_id`) VALUES
(1, 1),
(1, 4),
(2, 2),
(2, 5),
(3, 6),
(4, 3),
(5, 4),
(6, 6),
(7, 1),
(8, 7),
(9, 4),
(10, 5),
(11, 2),
(12, 4),
(13, 6),
(14, 1),
(15, 7),
(16, 6),
(17, 4),
(18, 7),
(19, 3),
(20, 2),
(21, 4),
(22, 6),
(23, 5),
(24, 7),
(25, 1);

-- --------------------------------------------------------

--
-- Table structure for table `Locations`
--

CREATE TABLE `Locations` (
  `id` int(11) NOT NULL,
  `city` varchar(255) NOT NULL,
  `address` text NOT NULL,
  `zip_code` varchar(20) NOT NULL,
  `country_code` varchar(10) NOT NULL,
  `phone_number` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `Locations`
--

INSERT INTO `Locations` (`id`, `city`, `address`, `zip_code`, `country_code`, `phone_number`) VALUES
(1, 'Amsterdam', 'Damrak 1', '1012AB', 'NL', '+31-20-1234567'),
(2, 'Rotterdam', 'Coolsingel 10', '3012AD', 'NL', '+31-10-7654321'),
(3, 'The Hague', 'Lange Voorhout 15', '2514EE', 'NL', '+31-70-9876543'),
(4, 'Utrecht', 'Domplein 4', '3512JC', 'NL', '+31-30-4567890'),
(5, 'Eindhoven', 'Strijp-S 20', '5617AB', 'NL', '+31-40-1239876'),
(6, 'Groningen', 'Grote Markt 5', '9712CP', 'NL', '+31-50-6543210'),
(7, 'Maastricht', 'Vrijthof 7', '6211LE', 'NL', '+31-43-7890123'),
(8, 'Leiden', 'Breestraat 50', '2311CS', 'NL', '+31-71-2345678'),
(9, 'Delft', 'Markt 80', '2611GW', 'NL', '+31-15-3456789'),
(10, 'Haarlem', 'Grote Houtstraat 100', '2011SN', 'NL', '+31-23-5678901'),
(11, 'Zwolle', 'Melkmarkt 12', '8011MC', 'NL', '+31-38-1234567'),
(12, 'Arnhem', 'Korenmarkt 8', '6811GV', 'NL', '+31-26-7654321'),
(13, 'Breda', 'Grote Markt 20', '4811XR', 'NL', '+31-76-9876543'),
(14, 'Tilburg', 'Heuvelstraat 15', '5038AA', 'NL', '+31-13-4567890'),
(15, 'Almere', 'Stadhuisplein 1', '1315HR', 'NL', '+31-36-1239876'),
(16, 'Nijmegen', 'Grote Markt 10', '6511KH', 'NL', '+31-24-6543210'),
(17, 'Apeldoorn', 'Hoofdstraat 5', '7311KA', 'NL', '+31-55-7890123'),
(18, 'Amersfoort', 'Lieve Vrouweplein 3', '3811BR', 'NL', '+31-33-2345678'),
(19, 'Den Bosch', 'Markt 25', '5211JW', 'NL', '+31-73-3456789'),
(20, 'Haarlemmermeer', 'Raadhuisplein 1', '2132TZ', 'NL', '+31-23-5678901'),
(21, 'Zoetermeer', 'Stadhuisplein 10', '2711EC', 'NL', '+31-79-1234567'),
(22, 'Leeuwarden', 'Zaailand 15', '8911BL', 'NL', '+31-58-7654321'),
(23, 'Deventer', 'Brink 8', '7411BR', 'NL', '+31-57-9876543'),
(24, 'Enschede', 'Oude Markt 20', '7511GA', 'NL', '+31-53-4567890'),
(25, 'Hengelo', 'Burgemeester Jansenplein 5', '7551ED', 'NL', '+31-74-1239876');

-- --------------------------------------------------------

--
-- Table structure for table `Tags`
--

CREATE TABLE `Tags` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `Tags`
--

INSERT INTO `Tags` (`id`, `name`) VALUES
(3, 'Birthday Party'),
(6, 'Conference'),
(2, 'Corporate Event'),
(5, 'Indoor'),
(4, 'Outdoor'),
(7, 'Private Party'),
(1, 'Wedding');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `Facilities`
--
ALTER TABLE `Facilities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`,`location_id`),
  ADD KEY `location_id` (`location_id`);

--
-- Indexes for table `Facility_Tags`
--
ALTER TABLE `Facility_Tags`
  ADD PRIMARY KEY (`facility_id`,`tag_id`),
  ADD KEY `tag_id` (`tag_id`);

--
-- Indexes for table `Locations`
--
ALTER TABLE `Locations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `Tags`
--
ALTER TABLE `Tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `Facilities`
--
ALTER TABLE `Facilities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `Locations`
--
ALTER TABLE `Locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `Tags`
--
ALTER TABLE `Tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `Facilities`
--
ALTER TABLE `Facilities`
  ADD CONSTRAINT `Facilities_ibfk_1` FOREIGN KEY (`location_id`) REFERENCES `Locations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `Facility_Tags`
--
ALTER TABLE `Facility_Tags`
  ADD CONSTRAINT `Facility_Tags_ibfk_1` FOREIGN KEY (`facility_id`) REFERENCES `Facilities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `Facility_Tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `Tags` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
