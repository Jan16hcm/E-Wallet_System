-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: May 08, 2026 at 12:21 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `fakebank`
--
CREATE DATABASE IF NOT EXISTS `fakebank` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `fakebank`;

-- --------------------------------------------------------

--
-- Table structure for table `history`
--

CREATE TABLE `history` (
  `id` varchar(32) NOT NULL COMMENT '14 char from max time length + 6-16 phonenum length + padding 0 = 32 length',
  `user_phone` varchar(15) NOT NULL COMMENT 'SĐT người thực hiện giao dịch',
  `receiver_phone` varchar(15) DEFAULT NULL COMMENT 'SĐT người nhận (nếu là chuyển khoản)',
  `transfer_type` varchar(50) DEFAULT 'Transfer' COMMENT 'Transfer/Deposit/Withdraw/Buycard',
  `card_num` varchar(6) DEFAULT NULL,
  `expire` date DEFAULT NULL,
  `CVV` varchar(3) DEFAULT NULL,
  `date_transfer` datetime NOT NULL,
  `date_confirm` datetime DEFAULT NULL COMMENT 'date admin approve/cancelled',
  `money` decimal(19,4) NOT NULL DEFAULT 0.0000,
  `fee` decimal(19,4) NOT NULL DEFAULT 0.0000,
  `note` mediumtext DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 2 COMMENT '0:Cancelled\n1:Approved\n2:Pending',
  `selfFeeBear` tinyint(1) DEFAULT 1 COMMENT '1: User pays fee, 0: Recipient pays fee'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `phonecard`
--

CREATE TABLE `phonecard` (
  `id` varchar(32) NOT NULL COMMENT 'same as id in history',
  `code` int(11) NOT NULL COMMENT '10 digits',
  `carrier` varchar(50) NOT NULL,
  `denomination` float NOT NULL COMMENT '10000/20000/50000/100000'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `phonenum` varchar(15) NOT NULL,
  `email` varchar(255) NOT NULL,
  `name` varchar(50) DEFAULT NULL,
  `birth` date DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `front` mediumblob DEFAULT NULL,
  `back` mediumblob DEFAULT NULL,
  `pass` varchar(255) NOT NULL,
  `verified` tinyint(1) NOT NULL DEFAULT -1 COMMENT '-1:firstRegist\r\n0:False\r\n1:True\r\n2:Request additional information\r\n3:IsAdmin True\r\n4:DisableAccount',
  `abnormal_login` tinyint(4) NOT NULL DEFAULT 0 COMMENT '>6 lock account\n>3 lock 1 minute',
  `card_num` varchar(6) DEFAULT NULL,
  `money` decimal(19,4) NOT NULL DEFAULT 0.0000,
  `CVV` varchar(3) DEFAULT NULL,
  `expire` date DEFAULT NULL,
  `locked_time` datetime DEFAULT NULL COMMENT 'Disable account overtime',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Account registration timestamp',
  `card_updated_at` datetime DEFAULT NULL COMMENT 'Last time ID card was uploaded/updated'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` VALUES
('0000000000', 'admin@meomeo.com', 'Admin', '2005-01-16', 'TDTU', NULL, NULL, '$2y$10$B6hq495fsonInn5zlBf7mOn.CbplMG7/Y87wCD1go.chQeGWdNk8y', 3, 0, NULL, 0.0000, NULL, NULL, NULL, current_timestamp(), NULL),
('0909090909', 'user3@gmail.com', 'Le Van C', '1998-02-28', 'Đà Nẵng', NULL, NULL, '$2y$10$BBstCMneuLr2RJoEgEtOUualLHCJtTrC3HVvIPwOg/vYTXwxgATsO', 4, 0, NULL, 150000.0000, NULL, NULL, NULL, current_timestamp(), NULL),
('0912345678', 'user1@gmail.com', 'Tran Hoang Khai', '1995-05-15', 'Hồ Chí Minh', NULL, NULL, '$2y$10$Wyq/jXyw6jwyxjjvxy1.ceYiYODYEOS34GfitOqthWL1llDPmZlBe', 1, 0, NULL, 5000000.0000, NULL, NULL, NULL, current_timestamp(), NULL),
('0987654321', 'user2@gmail.com', 'Tran Thi B', '2000-10-20', 'Hà Nội', NULL, NULL, '$2y$10$WdH8txReFwer4lGWxptvpuW6D0L7MdFDTqwhGoWgNmY8m9daATH.K', 0, 0, NULL, 0.0000, NULL, NULL, NULL, current_timestamp(), NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `history`
--
ALTER TABLE `history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_history_user` (`user_phone`);

--
-- Indexes for table `phonecard`
--
ALTER TABLE `phonecard`
  ADD PRIMARY KEY (`id`,`code`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`phonenum`);
ALTER TABLE `user` 
  ADD UNIQUE (`email`);
ALTER TABLE `user` 
  ADD `subverified` tinyint(1) NOT NULL DEFAULT -1 COMMENT '-1:firstRegist\r\n0:False\r\n1:True\r\n2:Request additional information\r\n4:DisableAccount';
--
-- Constraints for dumped tables
--

--
-- Constraints for table `history`
--
ALTER TABLE `history`
  ADD CONSTRAINT `fk_history_user` FOREIGN KEY (`user_phone`) REFERENCES `user` (`phonenum`) ON DELETE CASCADE;

--
-- Constraints for table `phonecard`
--
ALTER TABLE `phonecard`
  ADD CONSTRAINT `fk_phonecard_history` FOREIGN KEY (`id`) REFERENCES `history` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
