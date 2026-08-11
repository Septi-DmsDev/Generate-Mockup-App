-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 31, 2025 at 02:45 AM
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
-- Database: `overlay_app`
--

-- --------------------------------------------------------

--
-- Table structure for table `backgrounds`
--

CREATE TABLE `backgrounds` (
  `id` int(11) NOT NULL,
  `preset_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` int(11) NOT NULL,
  `preset_id` int(11) NOT NULL,
  `user_image` varchar(255) NOT NULL,
  `zip_file` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `logos`
--

CREATE TABLE `logos` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `logos`
--

INSERT INTO `logos` (`id`, `name`, `file_path`) VALUES
(109, 'TEKNOS INDONESIA', 'uploads/logos/logo_6940d307efdc3.png'),
(110, 'PACKABAG', 'uploads/logos/logo_6940d30e87c9e.png'),
(111, 'MAHARATU', 'uploads/logos/logo_6940d31aaa445.png'),
(112, 'CLASSY SOUVENIR', 'uploads/logos/logo_6940d3223473c.png');

-- --------------------------------------------------------

--
-- Table structure for table `presets`
--

CREATE TABLE `presets` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `created_at`) VALUES
(1, 'TEKNOS', '$2y$10$.7TwK/smr9LlZ2OI9fLQ/.aEzuarsQ88zrtrIv7cFVPjUymlOrAFe', '2025-12-08 07:07:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `backgrounds`
--
ALTER TABLE `backgrounds`
  ADD PRIMARY KEY (`id`),
  ADD KEY `preset_id` (`preset_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `logos`
--
ALTER TABLE `logos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `presets`
--
ALTER TABLE `presets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `backgrounds`
--
ALTER TABLE `backgrounds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=829;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `logos`
--
ALTER TABLE `logos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT for table `presets`
--
ALTER TABLE `presets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `backgrounds`
--
ALTER TABLE `backgrounds`
  ADD CONSTRAINT `backgrounds_ibfk_1` FOREIGN KEY (`preset_id`) REFERENCES `presets` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
  