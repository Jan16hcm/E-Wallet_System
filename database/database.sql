-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Apr 14, 2026 at 03:31 PM
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
-- Database: fakebank
--
CREATE DATABASE IF NOT EXISTS fakebank DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fakebank;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `history`;
DROP TABLE IF EXISTS `user`;
--
-- Table structure for table user
--

CREATE TABLE `user` (
  `phonenum` varchar(15) NOT NULL,
  `email` varchar(255) NOT NULL,
  `name` varchar(50) NOT NULL,
  `birth` date DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `front` mediumblob DEFAULT NULL,
  `back` mediumblob DEFAULT NULL,
  `pass` varchar(255) NOT NULL,
  `verified` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0:False\n1:True\n2:Request additional information\n3:IsAdmin True\n4:DisableAccount',
  `abnormal_login` tinyint(4) NOT NULL DEFAULT 0 COMMENT '>3 lock account',
  `card_num` varchar(6) DEFAULT NULL,
  `money` decimal(19,4) NOT NULL DEFAULT 0.0000,
  `CVV` varchar(3) DEFAULT NULL,
  `expiration` date DEFAULT NULL,
  `locked_time` DATETIME DEFAULT NULL COMMENT 'Disable account overtime',
  PRIMARY KEY (`phonenum`) -- SET PRIMARYKEY
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


--
-- Table structure for table history
--
CREATE TABLE `history` (
  `id` int(11) NOT NULL AUTO_INCREMENT, -- Auto increase id
  `user_phone` varchar(15) NOT NULL COMMENT 'SĐT người thực hiện giao dịch',
  `receiver_phone` varchar(15) DEFAULT NULL COMMENT 'SĐT người nhận (nếu là chuyển khoản)',
  `transfer_type` varchar(50) DEFAULT 'Transferto' COMMENT 'Transferto/Transferby/Withdraw/Buycard',
  `card_num` varchar(6) NOT NULL,
  `expiration` date NOT NULL,
  `CVV` varchar(3) NOT NULL,
  `date_transfer` datetime NOT NULL,
  `date_confirm` datetime DEFAULT NULL COMMENT 'date admin approve/cancelled',
  `money` decimal(19,4) NOT NULL DEFAULT 0.0000,
  `note` mediumtext DEFAULT NULL,
  `phone_card` varchar(255) DEFAULT NULL COMMENT 'an array of phone card, max 5',
  `status` tinyint(4) NOT NULL DEFAULT 2 COMMENT '0:Cancelled\n1:Approved\n2:Pending',
  PRIMARY KEY (`id`), -- SET key
  CONSTRAINT `fk_history_user` FOREIGN KEY (`user_phone`) REFERENCES `user` (`phonenum`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- --------------------------------------------------------

--
-- Dumping data for table user
--

-- 1. Admin
INSERT INTO `user` (`phonenum`, `email`, `name`, `birth`, `address`, `pass`, `verified`, `money`) 
VALUES 
('0000000000', 'admin@meomeo.com', 'Admin', '2005-01-16', 'TDTU', '$2y$10$B6hq495fsonInn5zlBf7mOn.CbplMG7/Y87wCD1go.chQeGWdNk8y', 3, 0.0000);

-- 2. User already verified (verified = 1) - Have 5 million
INSERT INTO `user` (`phonenum`, `email`, `name`, `birth`, `address`, `pass`, `verified`, `money`) 
VALUES 
('0912345678', 'user1@gmail.com', 'Tran Hoang Khai', '1995-05-15', 'Hồ Chí Minh', '$2y$10$Wyq/jXyw6jwyxjjvxy1.ceYiYODYEOS34GfitOqthWL1llDPmZlBe', 1, 5000000.0000);

-- 3. Account User wait for verified (verified = 0) - Wait and no money
INSERT INTO `user` (`phonenum`, `email`, `name`, `birth`, `address`, `pass`, `verified`, `money`) 
VALUES 
('0987654321', 'user2@gmail.com', 'Tran Thi B', '2000-10-20', 'Hà Nội', '$2y$10$WdH8txReFwer4lGWxptvpuW6D0L7MdFDTqwhGoWgNmY8m9daATH.K', 0, 0.0000);

-- 4. Account User disable (verified = 4)
INSERT INTO `user` (`phonenum`, `email`, `name`, `birth`, `address`, `pass`, `verified`, `money`) 
VALUES 
('0909090909', 'user3@gmail.com', 'Le Van C', '1998-02-28', 'Đà Nẵng', '$2y$10$BBstCMneuLr2RJoEgEtOUualLHCJtTrC3HVvIPwOg/vYTXwxgATsO', 4, 150000.0000);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;