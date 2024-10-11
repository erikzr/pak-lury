-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 11, 2024 at 05:11 PM
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
-- Database: `ebanking`
--

-- --------------------------------------------------------

--
-- Table structure for table `m_customer`
--

CREATE TABLE `m_customer` (
  `id` bigint(20) NOT NULL,
  `customer_name` varchar(30) NOT NULL,
  `customer_username` varchar(50) NOT NULL,
  `customer_pin` varchar(200) NOT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `customer_email` varchar(50) DEFAULT NULL,
  `cif_number` varchar(30) DEFAULT NULL,
  `failed_login_attempts` int(11) DEFAULT 0,
  `failed_ib_token_attempts` int(11) DEFAULT 0,
  `failed_mb_token_attempts` int(11) DEFAULT 0,
  `ib_status` varchar(1) DEFAULT NULL,
  `mb_status` varchar(1) DEFAULT NULL,
  `previous_ib_status` varchar(1) DEFAULT NULL,
  `previous_mb_status` varchar(1) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `last_token_id` varchar(50) DEFAULT NULL,
  `registration_card_number` varchar(20) DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `createdby` int(11) NOT NULL DEFAULT 1,
  `updated` timestamp NOT NULL DEFAULT current_timestamp(),
  `updatedby` int(11) NOT NULL DEFAULT 1,
  `m_customer_group_id` int(11) NOT NULL DEFAULT 1,
  `auto_close_date` timestamp NULL DEFAULT NULL,
  `last_link_token` timestamp NULL DEFAULT NULL,
  `user_link_token` varchar(20) DEFAULT NULL,
  `spv_link_token` varchar(20) DEFAULT NULL,
  `token_type` varchar(1) DEFAULT NULL,
  `registration_account_number` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `m_customer`
--

INSERT INTO `m_customer` (`id`, `customer_name`, `customer_username`, `customer_pin`, `customer_phone`, `customer_email`, `cif_number`, `failed_login_attempts`, `failed_ib_token_attempts`, `failed_mb_token_attempts`, `ib_status`, `mb_status`, `previous_ib_status`, `previous_mb_status`, `last_login`, `last_token_id`, `registration_card_number`, `created`, `createdby`, `updated`, `updatedby`, `m_customer_group_id`, `auto_close_date`, `last_link_token`, `user_link_token`, `spv_link_token`, `token_type`, `registration_account_number`) VALUES
(1, 'MUHAMMAD ERIK ZUBAIR ROHMAN', 'erik', '$2y$10$BqDO8F1o.5P0ZlC56d/6aeqGBE88/Aern//nTWuh1We4KbOvNIYp6', '081330445365', 'muhammaderikzubairrohman@gmail.com', NULL, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 04:38:35', 1, '2024-10-11 04:38:35', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'laksono riho cahyo', 'ridho', '$2y$10$MQJTiMZgZKrwhs4XcKjJXuJh3qyevSZByp8qpnWdHs5M.uO/MnZmG', '081330445365', 'laksono@gmail.com', NULL, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 04:38:57', 1, '2024-10-11 04:38:57', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 'alfian fathur rahman', 'alfian', '$2y$10$hK8r8Eg1ZehxGJsmkUaS4O/SkXnydFgMx.8NlxlooiPg6gHs2jYY2', '081330445365', 'muhammaderiman@gmail.com', NULL, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 06:54:42', 1, '2024-10-11 06:54:42', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `m_parameter`
--

CREATE TABLE `m_parameter` (
  `id` bigint(20) NOT NULL,
  `parameter_name` varchar(30) DEFAULT NULL,
  `parameter_value` varchar(200) DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  `created` timestamp(6) NOT NULL DEFAULT current_timestamp(6),
  `createdby` int(11) NOT NULL DEFAULT 1,
  `updated` timestamp(6) NOT NULL DEFAULT current_timestamp(6),
  `updatedby` int(11) NOT NULL DEFAULT 1,
  `access_type` int(11) DEFAULT NULL,
  `parameter_value_binary` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `m_portfolio_account`
--

CREATE TABLE `m_portfolio_account` (
  `id` bigint(20) NOT NULL,
  `m_customer_id` int(11) DEFAULT NULL,
  `account_number` varchar(20) DEFAULT NULL,
  `account_status` varchar(1) DEFAULT NULL,
  `account_name` varchar(50) DEFAULT NULL,
  `account_type` varchar(10) DEFAULT NULL,
  `product_code` varchar(10) DEFAULT NULL,
  `product_name` varchar(50) DEFAULT NULL,
  `currency_code` varchar(3) DEFAULT NULL,
  `branch_code` varchar(10) DEFAULT NULL,
  `plafond` decimal(30,5) DEFAULT NULL,
  `clear_balance` decimal(30,5) DEFAULT NULL,
  `available_balance` decimal(30,5) DEFAULT NULL,
  `confidential` varchar(1) DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `createdby` int(11) NOT NULL DEFAULT 1,
  `updated` timestamp NOT NULL DEFAULT current_timestamp(),
  `updatedby` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `m_portfolio_account`
--

INSERT INTO `m_portfolio_account` (`id`, `m_customer_id`, `account_number`, `account_status`, `account_name`, `account_type`, `product_code`, `product_name`, `currency_code`, `branch_code`, `plafond`, `clear_balance`, `available_balance`, `confidential`, `created`, `createdby`, `updated`, `updatedby`) VALUES
(1, 1, 'ACC00001', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 100000.00000, 0.00000, NULL, '2024-10-11 04:38:35', 1, '2024-10-11 04:38:35', 1),
(2, 2, 'ACC00002', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 100000.00000, 500000.00000, NULL, '2024-10-11 04:38:57', 1, '2024-10-11 04:38:57', 1),
(3, 3, 'ACC00003', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 100000.00000, 500000.00000, NULL, '2024-10-11 06:54:42', 1, '2024-10-11 06:54:42', 1);

-- --------------------------------------------------------

--
-- Table structure for table `t_transaction`
--

CREATE TABLE `t_transaction` (
  `id` bigint(20) NOT NULL,
  `m_customer_id` int(11) NOT NULL,
  `mti` char(4) DEFAULT NULL,
  `transaction_type` varchar(2) NOT NULL DEFAULT '',
  `card_number` varchar(20) DEFAULT NULL,
  `transaction_amount` decimal(30,5) DEFAULT NULL,
  `fee_indicator` varchar(1) DEFAULT NULL,
  `fee` decimal(30,5) DEFAULT NULL,
  `transmission_date` timestamp NULL DEFAULT NULL,
  `transaction_date` timestamp NULL DEFAULT NULL,
  `value_date` timestamp NULL DEFAULT NULL,
  `conversion_rate` decimal(30,5) DEFAULT NULL,
  `stan` decimal(6,0) DEFAULT NULL,
  `merchant_type` varchar(4) DEFAULT NULL,
  `terminal_id` varchar(8) DEFAULT NULL,
  `reference_number` varchar(12) DEFAULT NULL,
  `approval_number` varchar(12) DEFAULT NULL,
  `response_code` char(2) DEFAULT NULL,
  `currency_code` char(3) DEFAULT NULL,
  `customer_reference` varchar(50) DEFAULT NULL,
  `biller_name` varchar(50) DEFAULT NULL,
  `from_account_number` varchar(20) DEFAULT NULL,
  `to_account_number` varchar(20) DEFAULT NULL,
  `from_account_type` varchar(2) DEFAULT '00',
  `to_account_type` varchar(2) DEFAULT '00',
  `balance` varchar(100) DEFAULT NULL,
  `description` varchar(250) DEFAULT NULL,
  `to_bank_code` varchar(3) DEFAULT NULL,
  `execution_type` varchar(10) NOT NULL DEFAULT 'N',
  `status` varchar(10) NOT NULL,
  `translation_code` varchar(1000) DEFAULT NULL,
  `free_data1` mediumtext DEFAULT NULL,
  `free_data2` mediumtext DEFAULT NULL,
  `free_data3` varchar(1000) DEFAULT NULL,
  `free_data4` varchar(1000) DEFAULT NULL,
  `free_data5` varchar(1000) DEFAULT NULL,
  `delivery_channel` varchar(10) DEFAULT NULL,
  `delivery_channel_id` varchar(50) DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `createdby` int(11) NOT NULL DEFAULT 1,
  `updated` timestamp NOT NULL DEFAULT current_timestamp(),
  `updatedby` int(11) NOT NULL DEFAULT 1,
  `archive` tinyint(1) DEFAULT 0,
  `t_transaction_queue_id` int(11) DEFAULT NULL,
  `biller_id` varchar(20) DEFAULT NULL,
  `product_id` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `t_transaction`
--

INSERT INTO `t_transaction` (`id`, `m_customer_id`, `mti`, `transaction_type`, `card_number`, `transaction_amount`, `fee_indicator`, `fee`, `transmission_date`, `transaction_date`, `value_date`, `conversion_rate`, `stan`, `merchant_type`, `terminal_id`, `reference_number`, `approval_number`, `response_code`, `currency_code`, `customer_reference`, `biller_name`, `from_account_number`, `to_account_number`, `from_account_type`, `to_account_type`, `balance`, `description`, `to_bank_code`, `execution_type`, `status`, `translation_code`, `free_data1`, `free_data2`, `free_data3`, `free_data4`, `free_data5`, `delivery_channel`, `delivery_channel_id`, `created`, `createdby`, `updated`, `updatedby`, `archive`, `t_transaction_queue_id`, `biller_id`, `product_id`) VALUES
(1, 1, NULL, '', NULL, 100.00000, NULL, NULL, NULL, '2024-10-11 04:39:45', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ACC00001', 'ACC00002', '00', '00', NULL, NULL, NULL, 'N', '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 04:39:45', 1, '2024-10-11 04:39:45', 1, 0, NULL, NULL, NULL),
(2, 1, NULL, '', NULL, 20.00000, NULL, NULL, NULL, '2024-10-11 06:38:17', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ACC00001', 'ACC00002', '00', '00', NULL, NULL, NULL, 'N', '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 06:38:17', 1, '2024-10-11 06:38:17', 1, 0, NULL, NULL, NULL),
(3, 2, NULL, '', NULL, 1.00000, NULL, NULL, NULL, '2024-10-11 06:50:17', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ACC00002', 'ACC00001', '00', '00', NULL, NULL, NULL, 'N', '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 06:50:17', 1, '2024-10-11 06:50:17', 1, 0, NULL, NULL, NULL),
(4, 2, NULL, '', NULL, 500.00000, NULL, NULL, NULL, '2024-10-11 06:50:42', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ACC00002', 'ACC00001', '00', '00', NULL, NULL, NULL, 'N', '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 06:50:42', 1, '2024-10-11 06:50:42', 1, 0, NULL, NULL, NULL),
(5, 3, NULL, '', NULL, 500.00000, NULL, NULL, NULL, '2024-10-11 06:55:39', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ACC00003', 'ACC00002', '00', '00', NULL, NULL, NULL, 'N', '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 06:55:39', 1, '2024-10-11 06:55:39', 1, 0, NULL, NULL, NULL),
(6, 1, NULL, 'TO', NULL, 20.00000, NULL, NULL, NULL, '2024-10-11 07:38:03', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'SYSTEM', 'ACC00001', '00', '00', NULL, NULL, NULL, 'N', 'success', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 07:38:03', 1, '2024-10-11 07:38:03', 1, 0, NULL, NULL, NULL),
(7, 1, NULL, 'TO', NULL, 400.00000, NULL, NULL, NULL, '2024-10-11 07:38:28', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'SYSTEM', 'ACC00001', '00', '00', NULL, NULL, NULL, 'N', 'success', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 07:38:28', 1, '2024-10-11 07:38:28', 1, 0, NULL, NULL, NULL),
(8, 1, NULL, 'TO', NULL, 200.00000, NULL, NULL, NULL, '2024-10-11 07:40:50', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'SYSTEM', 'ACC00001', '00', '00', NULL, NULL, NULL, 'N', 'success', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 07:40:50', 1, '2024-10-11 07:40:50', 1, 0, NULL, NULL, NULL),
(9, 1, NULL, 'TO', NULL, 500.00000, NULL, NULL, NULL, '2024-10-11 07:43:44', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'SYSTEM', 'ACC00001', '00', '00', NULL, NULL, NULL, 'N', 'success', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 07:43:44', 1, '2024-10-11 07:43:44', 1, 0, NULL, NULL, NULL),
(10, 1, NULL, 'TR', NULL, 500.00000, NULL, NULL, NULL, '2024-10-11 07:46:49', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ACC00001', 'ACC00002', '00', '00', NULL, NULL, NULL, 'N', 'success', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 07:46:49', 1, '2024-10-11 07:46:49', 1, 0, NULL, NULL, NULL),
(11, 1, NULL, 'TR', NULL, 500.00000, NULL, NULL, NULL, '2024-10-11 07:47:02', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ACC00001', 'ACC00002', '00', '00', NULL, NULL, NULL, 'N', 'success', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 07:47:02', 1, '2024-10-11 07:47:02', 1, 0, NULL, NULL, NULL),
(12, 1, NULL, 'TO', NULL, 500.00000, NULL, NULL, NULL, '2024-10-11 07:50:58', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'SYSTEM', 'ACC00001', '00', '00', NULL, NULL, NULL, 'N', 'success', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 07:50:58', 1, '2024-10-11 07:50:58', 1, 0, NULL, NULL, NULL),
(13, 1, NULL, 'TR', NULL, 1.00000, NULL, NULL, NULL, '2024-10-11 07:51:26', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ACC00001', 'ACC00002', '00', '00', NULL, NULL, NULL, 'N', 'success', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 07:51:26', 1, '2024-10-11 07:51:26', 1, 0, NULL, NULL, NULL),
(14, 1, NULL, 'TR', NULL, 1000.00000, NULL, NULL, NULL, '2024-10-11 07:52:09', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ACC00001', 'ACC00003', '00', '00', NULL, NULL, NULL, 'N', 'success', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 07:52:09', 1, '2024-10-11 07:52:09', 1, 0, NULL, NULL, NULL),
(15, 1, NULL, 'TR', NULL, 500.00000, NULL, NULL, NULL, '2024-10-11 08:01:26', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ACC00001', 'ACC00003', '00', '00', NULL, NULL, NULL, 'N', 'success', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 08:01:26', 1, '2024-10-11 08:01:26', 1, 0, NULL, NULL, NULL),
(16, 3, NULL, 'TR', NULL, 500.00000, NULL, NULL, NULL, '2024-10-11 09:29:58', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ACC00003', 'ACC00002', '00', '00', NULL, NULL, NULL, 'N', 'success', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 09:29:58', 1, '2024-10-11 09:29:58', 1, 0, NULL, NULL, NULL),
(17, 3, NULL, 'TR', NULL, 1.00000, NULL, NULL, NULL, '2024-10-11 11:45:34', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ACC00003', 'ACC00002', '00', '00', NULL, NULL, NULL, 'N', 'success', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 11:45:34', 1, '2024-10-11 11:45:34', 1, 0, NULL, NULL, NULL),
(18, 3, NULL, 'TR', NULL, 4.00000, NULL, NULL, NULL, '2024-10-11 11:45:49', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ACC00003', 'ACC00002', '00', '00', NULL, NULL, NULL, 'N', 'success', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 11:45:49', 1, '2024-10-11 11:45:49', 1, 0, NULL, NULL, NULL),
(19, 3, NULL, 'TR', NULL, 5.00000, NULL, NULL, NULL, '2024-10-11 11:47:34', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ACC00003', 'ACC00001', '00', '00', NULL, NULL, NULL, 'N', 'success', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 11:47:34', 1, '2024-10-11 11:47:34', 1, 0, NULL, NULL, NULL),
(20, 3, NULL, 'TR', NULL, 100.00000, NULL, NULL, NULL, '2024-10-11 11:52:12', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ACC00003', 'ACC00001', '00', '00', NULL, NULL, NULL, 'N', 'success', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 11:52:12', 1, '2024-10-11 11:52:12', 1, 0, NULL, NULL, NULL),
(21, 3, NULL, 'TR', NULL, 100.00000, NULL, NULL, NULL, '2024-10-11 11:56:58', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ACC00003', 'ACC00001', '00', '00', NULL, NULL, NULL, 'N', 'success', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 11:56:58', 1, '2024-10-11 11:56:58', 1, 0, NULL, NULL, NULL),
(22, 3, NULL, 'TR', NULL, 2.90000, NULL, NULL, NULL, '2024-10-11 11:57:20', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ACC00003', 'ACC00002', '00', '00', NULL, NULL, NULL, 'N', 'success', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 11:57:20', 1, '2024-10-11 11:57:20', 1, 0, NULL, NULL, NULL),
(23, 3, NULL, 'TR', NULL, 100.00000, NULL, NULL, NULL, '2024-10-11 11:57:38', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ACC00003', 'ACC00001', '00', '00', NULL, NULL, NULL, 'N', 'success', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 11:57:38', 1, '2024-10-11 11:57:38', 1, 0, NULL, NULL, NULL),
(24, 3, NULL, 'TR', NULL, 1.00000, NULL, NULL, NULL, '2024-10-11 11:57:50', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ACC00003', 'ACC00001', '00', '00', NULL, NULL, NULL, 'N', 'success', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 11:57:50', 1, '2024-10-11 11:57:50', 1, 0, NULL, NULL, NULL),
(25, 3, NULL, 'TR', NULL, 100.00000, NULL, NULL, NULL, '2024-10-11 11:57:59', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ACC00003', 'ACC00002', '00', '00', NULL, NULL, NULL, 'N', 'success', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 11:57:59', 1, '2024-10-11 11:57:59', 1, 0, NULL, NULL, NULL),
(26, 3, NULL, 'TR', NULL, 86.00000, NULL, NULL, NULL, '2024-10-11 11:58:16', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ACC00003', 'ACC00002', '00', '00', NULL, NULL, NULL, 'N', 'success', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 11:58:16', 1, '2024-10-11 11:58:16', 1, 0, NULL, NULL, NULL),
(27, 3, NULL, 'TR', NULL, 100.00000, NULL, NULL, NULL, '2024-10-11 11:58:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ACC00003', 'ACC00001', '00', '00', NULL, NULL, NULL, 'N', 'success', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 11:58:31', 1, '2024-10-11 11:58:31', 1, 0, NULL, NULL, NULL),
(28, 3, NULL, 'TR', NULL, 100.00000, NULL, NULL, NULL, '2024-10-11 11:58:44', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ACC00003', 'ACC00002', '00', '00', NULL, NULL, NULL, 'N', 'success', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 11:58:44', 1, '2024-10-11 11:58:44', 1, 0, NULL, NULL, NULL),
(29, 3, NULL, 'TR', NULL, 100.00000, NULL, NULL, NULL, '2024-10-11 12:02:15', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ACC00003', 'ACC00002', '00', '00', NULL, NULL, NULL, 'N', 'success', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 12:02:15', 1, '2024-10-11 12:02:15', 1, 0, NULL, NULL, NULL),
(30, 3, NULL, 'TR', NULL, 50.00000, NULL, NULL, NULL, '2024-10-11 12:02:25', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ACC00003', 'ACC00002', '00', '00', NULL, NULL, NULL, 'N', 'success', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 12:02:25', 1, '2024-10-11 12:02:25', 1, 0, NULL, NULL, NULL),
(31, 3, NULL, 'TR', NULL, 500.00000, NULL, NULL, NULL, '2024-10-11 12:02:37', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ACC00003', 'ACC00002', '00', '00', NULL, NULL, NULL, 'N', 'success', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 12:02:37', 1, '2024-10-11 12:02:37', 1, 0, NULL, NULL, NULL),
(32, 3, NULL, 'TR', NULL, 100.00000, NULL, NULL, NULL, '2024-10-11 12:02:50', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ACC00003', 'ACC00001', '00', '00', NULL, NULL, NULL, 'N', 'success', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 12:02:50', 1, '2024-10-11 12:02:50', 1, 0, NULL, NULL, NULL),
(33, 3, NULL, 'TR', NULL, 900.00000, NULL, NULL, NULL, '2024-10-11 12:03:15', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ACC00003', 'ACC00002', '00', '00', NULL, NULL, NULL, 'N', 'success', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 12:03:15', 1, '2024-10-11 12:03:15', 1, 0, NULL, NULL, NULL),
(34, 3, NULL, 'TR', NULL, 90.00000, NULL, NULL, NULL, '2024-10-11 12:03:27', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ACC00003', 'ACC00002', '00', '00', NULL, NULL, NULL, 'N', 'success', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 12:03:27', 1, '2024-10-11 12:03:27', 1, 0, NULL, NULL, NULL),
(35, 3, NULL, 'TR', NULL, 900.00000, NULL, NULL, NULL, '2024-10-11 12:03:37', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ACC00003', 'ACC00002', '00', '00', NULL, NULL, NULL, 'N', 'success', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 12:03:37', 1, '2024-10-11 12:03:37', 1, 0, NULL, NULL, NULL),
(36, 1, NULL, 'WI', NULL, 100006.00000, NULL, NULL, NULL, '2024-10-11 12:08:40', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ACC00001', 'SYSTEM', '00', '00', NULL, NULL, NULL, 'N', 'success', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 12:08:40', 1, '2024-10-11 12:08:40', 1, 0, NULL, NULL, NULL),
(37, 1, NULL, 'TO', NULL, 100.00000, NULL, NULL, NULL, '2024-10-11 12:08:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'SYSTEM', 'ACC00001', '00', '00', NULL, NULL, NULL, 'N', 'success', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 12:08:52', 1, '2024-10-11 12:08:52', 1, 0, NULL, NULL, NULL),
(38, 1, NULL, 'TO', NULL, 100.00000, NULL, NULL, NULL, '2024-10-11 14:07:32', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'SYSTEM', 'ACC00001', '00', '00', NULL, NULL, NULL, 'N', 'success', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 14:07:32', 1, '2024-10-11 14:07:32', 1, 0, NULL, NULL, NULL),
(39, 1, NULL, 'TO', NULL, 100000000.00000, NULL, NULL, NULL, '2024-10-11 14:18:07', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'SYSTEM', 'ACC00001', '00', '00', NULL, NULL, NULL, 'N', 'success', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 14:18:07', 1, '2024-10-11 14:18:07', 1, 0, NULL, NULL, NULL),
(40, 1, NULL, 'WI', NULL, 100000200.00000, NULL, NULL, NULL, '2024-10-11 14:18:22', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ACC00001', 'SYSTEM', '00', '00', NULL, NULL, NULL, 'N', 'success', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 14:18:22', 1, '2024-10-11 14:18:22', 1, 0, NULL, NULL, NULL),
(41, 1, NULL, 'TO', NULL, 100000.00000, NULL, NULL, NULL, '2024-10-11 14:18:33', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'SYSTEM', 'ACC00001', '00', '00', NULL, NULL, NULL, 'N', 'success', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 14:18:33', 1, '2024-10-11 14:18:33', 1, 0, NULL, NULL, NULL),
(42, 1, NULL, 'TR', NULL, 75000.00000, NULL, NULL, NULL, '2024-10-11 14:18:55', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ACC00001', 'ACC00002', '00', '00', NULL, NULL, NULL, 'N', 'success', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 14:18:55', 1, '2024-10-11 14:18:55', 1, 0, NULL, NULL, NULL),
(43, 1, NULL, 'TO', NULL, 100000.00000, NULL, NULL, NULL, '2024-10-11 14:19:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'SYSTEM', 'ACC00001', '00', '00', NULL, NULL, NULL, 'N', 'success', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 14:19:47', 1, '2024-10-11 14:19:47', 1, 0, NULL, NULL, NULL),
(44, 1, NULL, 'TO', NULL, 100000.00000, NULL, NULL, NULL, '2024-10-11 14:19:55', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'SYSTEM', 'ACC00001', '00', '00', NULL, NULL, NULL, 'N', 'success', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 14:19:55', 1, '2024-10-11 14:19:55', 1, 0, NULL, NULL, NULL),
(45, 1, NULL, 'TR', NULL, 100000.00000, NULL, NULL, NULL, '2024-10-11 14:20:05', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ACC00001', 'ACC00002', '00', '00', NULL, NULL, NULL, 'N', 'success', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 14:20:05', 1, '2024-10-11 14:20:05', 1, 0, NULL, NULL, NULL),
(46, 1, NULL, 'TR', NULL, 100000.00000, NULL, NULL, NULL, '2024-10-11 14:20:20', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ACC00001', 'ACC00002', '00', '00', NULL, NULL, NULL, 'N', 'success', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 14:20:20', 1, '2024-10-11 14:20:20', 1, 0, NULL, NULL, NULL),
(47, 1, NULL, 'WI', NULL, 25000.00000, NULL, NULL, NULL, '2024-10-11 15:02:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ACC00001', 'SYSTEM', '00', '00', NULL, NULL, NULL, 'N', 'success', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 15:02:46', 1, '2024-10-11 15:02:46', 1, 0, NULL, NULL, NULL),
(48, 2, NULL, 'WI', NULL, 379453.90000, NULL, NULL, NULL, '2024-10-11 15:03:12', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ACC00002', 'SYSTEM', '00', '00', NULL, NULL, NULL, 'N', 'success', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 15:03:12', 1, '2024-10-11 15:03:12', 1, 0, NULL, NULL, NULL),
(49, 3, NULL, 'WI', NULL, 97160.10000, NULL, NULL, NULL, '2024-10-11 15:03:51', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ACC00003', 'SYSTEM', '00', '00', NULL, NULL, NULL, 'N', 'success', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 15:03:51', 1, '2024-10-11 15:03:51', 1, 0, NULL, NULL, NULL),
(50, 3, NULL, 'TO', NULL, 1000000.00000, NULL, NULL, NULL, '2024-10-11 15:03:57', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'SYSTEM', 'ACC00003', '00', '00', NULL, NULL, NULL, 'N', 'success', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 15:03:57', 1, '2024-10-11 15:03:57', 1, 0, NULL, NULL, NULL),
(51, 3, NULL, 'TR', NULL, 500000.00000, NULL, NULL, NULL, '2024-10-11 15:04:16', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ACC00003', 'ACC00002', '00', '00', NULL, NULL, NULL, 'N', 'success', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-10-11 15:04:16', 1, '2024-10-11 15:04:16', 1, 0, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `t_transaction_data`
--

CREATE TABLE `t_transaction_data` (
  `id` bigint(20) NOT NULL,
  `t_transaction_id` bigint(20) NOT NULL,
  `class_name` varchar(100) DEFAULT NULL,
  `transaction_data` varchar(5000) DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `createdby` int(11) NOT NULL DEFAULT 1,
  `updated` timestamp NOT NULL DEFAULT current_timestamp(),
  `updatedby` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `m_customer`
--
ALTER TABLE `m_customer`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `m_parameter`
--
ALTER TABLE `m_parameter`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `m_portfolio_account`
--
ALTER TABLE `m_portfolio_account`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `t_transaction`
--
ALTER TABLE `t_transaction`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `t_transaction_data`
--
ALTER TABLE `t_transaction_data`
  ADD PRIMARY KEY (`id`),
  ADD KEY `t_transaction_fk` (`t_transaction_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `m_customer`
--
ALTER TABLE `m_customer`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `m_portfolio_account`
--
ALTER TABLE `m_portfolio_account`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `t_transaction`
--
ALTER TABLE `t_transaction`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `t_transaction_data`
--
ALTER TABLE `t_transaction_data`
  ADD CONSTRAINT `t_transaction_fk` FOREIGN KEY (`t_transaction_id`) REFERENCES `t_transaction` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
