-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: mariadb
-- Generation Time: Feb 15, 2025 at 07:27 AM
-- Server version: 11.0.2-MariaDB-1:11.0.2+maria~ubu2204
-- PHP Version: 8.2.8

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `postrocknation`
--

-- --------------------------------------------------------

--
-- Table structure for table `artists`
--

CREATE TABLE `artists` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`images`)),
  `spotify_id` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `links` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`links`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `spotify_popularity` int(11) DEFAULT NULL,
  `published` tinyint(1) NOT NULL DEFAULT 1,
  `last_sync` timestamp NULL DEFAULT NULL,
  `popularity` int(11) NOT NULL DEFAULT 0,
  `auto_announce` tinyint(1) NOT NULL DEFAULT 0,
  `country` varchar(255) DEFAULT NULL,
  `participates_in_radio` tinyint(1) NOT NULL DEFAULT 0,
  `subdomain` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `artists`
--

INSERT INTO `artists` (`name`, `spotify_id`) VALUES
('God Is An Astronaut', '079svMEXkbT5nGU2kfoqO2'),
('1099', '12zVnfDLMYNY4LlE3rBYrM'),
('Brave Arrows', '19S0ZeuRL6isJvOKa1PUyE'),
('42DE', '1bXWVbvAldx4OrVrUAg6cr'),
('1 Mile North', '2e62yyjw0GPB4heVGRwczC'),
('Inspired By Illusions', '3adbMFpxkFWrZtLOGyiSN7'),
('3nd', '3mZrT1scKNjGo7Flenc4nf'),
('Pray for Sound', '3pmb6EnakP15oTPwkUndJx'),
('18 Seconds', '3sO3j1otSkmkGHMB3qbkdD'),
('417.3', '3UXayJMsdFGlK7PWbLAr7A'),
('4dots', '3zgUaA73DKW8Ou8s3kOQm6'),
('7nightsatsea', '4KgiqtI9DUi7qgSD7qFEAZ'),
('52 Commercial Road', '4MP4u1Cb27E9Xi40tC1tCH'),
('Erida\'s Garden', '4NaSxHexScSCMfqYBAueo4'),
('1inamillion', '4TNcOPus5yB62kDOFuBhei'),
('10 Waves of You', '50qEChUBHr0sSHFBowJD1q'),
('MONO', '53LVoipNTQ4lvUSJ61XKU3'),
('bravery in battle', '5VYlljUx2PZc4PNsxgD5nW'),
('Volkan', '5XmD2gTsVX158gxx62YK9e'),
('Erupter', '6fkwEfCEyioh1gXpd6tCvG'),
('MNMM', '6mKUPTfLCkTZkWZOAP2MbR'),
('5 O Clock In The Morning', '6zHD4h9ctb6RrI4NkOX1Em'),
('Misto', '70A5NZYkIbkH74COM4qbap'),
('48V', '7c17TmMobttiYTW4JO7kx3'),
('30 Fathom Grave', '7sowmtRt3fz5OXI5NOI61Y');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `artists`
--
ALTER TABLE `artists`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `artists_slug_unique` (`slug`),
  ADD UNIQUE KEY `artists_spotify_id_unique` (`spotify_id`),
  ADD UNIQUE KEY `artists_subdomain_unique` (`subdomain`),
  ADD KEY `artists_last_sync_index` (`last_sync`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `artists`
--
ALTER TABLE `artists`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3123;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
