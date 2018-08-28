-- phpMyAdmin SQL Dump
-- version 4.7.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Aug 28, 2018 at 08:27 AM
-- Server version: 5.6.35
-- PHP Version: 7.1.8

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `exchange-api`
--

-- --------------------------------------------------------

--
-- Table structure for table `account`
--

CREATE TABLE `account` (
  `id` int(10) NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `status` int(1) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `aglo` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `validate_f2a_code` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `validate_email` int(1) NOT NULL,
  `hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `account`
--

INSERT INTO `account` (`id`, `email`, `password`, `token`, `status`, `created_at`, `updated_at`, `aglo`, `validate_f2a_code`, `validate_email`, `hash`) VALUES
(1, 'thietkewebvip@gmail.com', 'a131b831377a7ecb892750b1c2d118aaeca47647', 'tokenID', 1, '2018-07-07 15:58:55', '0000-00-00 00:00:00', 'sha256', '', 0, '$2y$10$o/KuLZwe1pmQj9QowWCLAu/pq9OJ7Ti82P4vrh5OuH7CN1g6VAlCm');

-- --------------------------------------------------------

--
-- Table structure for table `account_history`
--

CREATE TABLE `account_history` (
  `id` bigint(20) NOT NULL,
  `account_id` int(10) NOT NULL,
  `action` text COLLATE utf8_unicode_ci NOT NULL,
  `lastlogin` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `lastlogin_ip` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `lastlogin_brown` text COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `account_history`
--

INSERT INTO `account_history` (`id`, `account_id`, `action`, `lastlogin`, `lastlogin_ip`, `lastlogin_brown`) VALUES
(1, 1, '', '2018-07-06 20:59:32', '127.0.0.1', 'Firefox 61.0 Mac OS X'),
(2, 1, '', '2018-07-06 20:59:36', '127.0.0.1', 'Firefox 61.0 Mac OS X'),
(3, 1, '', '2018-07-06 21:34:34', '127.0.0.1', 'Firefox 61.0 Mac OS X'),
(4, 1, '', '2018-07-06 21:36:02', '127.0.0.1', 'Firefox 61.0 Mac OS X'),
(5, 1, '', '2018-07-06 21:36:19', '127.0.0.1', 'Firefox 61.0 Mac OS X'),
(6, 1, '', '2018-07-06 21:38:55', '127.0.0.1', 'Firefox 61.0 Mac OS X'),
(7, 1, '', '2018-07-06 21:39:30', '127.0.0.1', 'Firefox 61.0 Mac OS X'),
(8, 1, '', '2018-07-07 21:35:21', '127.0.0.1', 'Firefox 61.0 Mac OS X'),
(9, 1, '', '2018-07-08 00:39:50', '127.0.0.1', 'Firefox 61.0 Mac OS X'),
(10, 1, '', '2018-08-03 20:03:45', '127.0.0.1', 'Firefox 61.0 Mac OS X'),
(11, 1, '', '2018-08-03 18:36:24', '127.0.0.1', 'Firefox 61.0 Mac OS X'),
(12, 1, '', '2018-08-04 00:11:20', '127.0.0.1', 'Firefox 61.0 Mac OS X'),
(13, 1, '', '2018-08-04 18:52:33', '127.0.0.1', 'Firefox 61.0 Mac OS X'),
(14, 1, '', '2018-08-04 23:58:44', '127.0.0.1', 'Firefox 61.0 Mac OS X'),
(15, 1, '', '2018-08-05 21:11:25', '127.0.0.1', 'Firefox 61.0 Mac OS X'),
(16, 1, '', '2018-08-06 04:17:33', '127.0.0.1', 'Firefox 61.0 Mac OS X'),
(17, 1, '', '2018-08-05 20:47:29', '127.0.0.1', 'Firefox 61.0 Mac OS X'),
(18, 1, '', '2018-08-05 20:48:15', '127.0.0.1', 'Firefox 61.0 Mac OS X'),
(19, 1, '', '2018-08-07 02:50:43', '127.0.0.1', 'Firefox 61.0 Mac OS X'),
(20, 1, '', '2018-08-08 00:44:04', '127.0.0.1', 'Firefox 61.0 Mac OS X'),
(21, 1, '', '2018-08-08 00:49:46', '127.0.0.1', 'Firefox 61.0 Mac OS X');

-- --------------------------------------------------------

--
-- Table structure for table `account_login`
--

CREATE TABLE `account_login` (
  `cache_id` bigint(20) NOT NULL,
  `users_id` int(10) NOT NULL,
  `session_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `account_login`
--

INSERT INTO `account_login` (`cache_id`, `users_id`, `session_id`, `updated`) VALUES
(62, 1, '1b036a12a22203f2c29b695bbd7c5a5303924287', '2018-08-28 04:27:12');

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(10) NOT NULL,
  `admin_login` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `admin_password` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `hash` varchar(40) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `api_access`
--

CREATE TABLE `api_access` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `apikey` varchar(40) NOT NULL,
  `level` int(2) NOT NULL,
  `ignore_limits` tinyint(1) NOT NULL DEFAULT '0',
  `is_private_key` tinyint(1) NOT NULL DEFAULT '0',
  `ip_addresses` text,
  `date_created` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `markets`
--

CREATE TABLE `markets` (
  `trade_id` bigint(20) NOT NULL,
  `trade_side` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `trade_type` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'limit',
  `base` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `symbol` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `amount` float(10,2) NOT NULL,
  `prices` float NOT NULL,
  `users_id` int(10) NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `markets`
--

INSERT INTO `markets` (`trade_id`, `trade_side`, `trade_type`, `base`, `symbol`, `amount`, `prices`, `users_id`, `created`, `hash`) VALUES
(8, 'buy', 'limit', 'BTC', 'ROL', 9764.00, 0.000026, 1, '2018-08-23 11:37:30', '52071444a72209feb2d50a51592dc722097ac2df'),
(9, 'buy', 'limit', 'BTC', 'ROL', 9764.00, 0.000026, 1, '2018-08-23 11:38:56', '52071444a72209feb2d50a51592dc722097ac2df'),
(10, 'sell', 'limit', 'BTC', 'ROL', 9764.00, 0.0000252, 1, '2018-08-23 11:38:56', '52071444a72209feb2d50a51592dc722097ac2df');

-- --------------------------------------------------------

--
-- Table structure for table `symbol_service`
--

CREATE TABLE `symbol_service` (
  `symbol_id` int(10) NOT NULL,
  `symbol_name` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `symbol_fullname` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `symbol_icon` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `symbol_class` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `rpc_server` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `rpc_port` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `rpc_username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `rpc_password` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `basewallet` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `baseaccount` varchar(255) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `symbol_service`
--

INSERT INTO `symbol_service` (`symbol_id`, `symbol_name`, `symbol_fullname`, `symbol_icon`, `symbol_class`, `rpc_server`, `rpc_port`, `rpc_username`, `rpc_password`, `basewallet`, `baseaccount`) VALUES
(1, 'ROL', 'Roller', '', 'Web3', '125.212.245.163', '8889', '', '', '', ''),
(2, 'BTC', 'Bitcoin', '', 'JsonRPC', '125.212.245.163', '8888', 'btcusernameconnect', 'motcongacongnghiepsaibtc', '3NqCHgYAXGaxNL2tHQ8rLcKvLaxrWQF21i', '3NqCHgYAXGaxNL2tHQ8rLcKvLaxrWQF21i'),
(3, 'ETH', 'Ethereum', '', 'Web3', '127.0.0.2', '8545', 'admin', 'admin1', '', ''),
(4, 'ETC', 'Ethereum Classic', '', 'Web3', '127.0.0.3', '8545', 'admin', 'admin1', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `symbol_trade`
--

CREATE TABLE `symbol_trade` (
  `symbol_id` int(10) NOT NULL,
  `symbol_name` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `symbol_fullname` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `symbol_icon` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `trade_with` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `change_24h` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `high_24h` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `low_24h` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `volume_24h` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `last_prices` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `is_parent` int(1) NOT NULL,
  `shorts` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `symbol_trade`
--

INSERT INTO `symbol_trade` (`symbol_id`, `symbol_name`, `symbol_fullname`, `symbol_icon`, `trade_with`, `change_24h`, `high_24h`, `low_24h`, `volume_24h`, `last_prices`, `is_parent`, `shorts`) VALUES
(1, 'ROL', 'Roller', '', 'BTC', '', '', '', '', '', 0, 0),
(2, 'BTC', 'Bitcoin', '', '', '', '', '', '', '', 1, 1),
(3, 'ETH', 'Ethereum', '', 'BTC', '', '', '', '', '', 1, 3),
(4, 'ETC', 'Ethereum Classic', '', 'BTC', '', '', '', '', '', 0, 0),
(6, 'ROL', 'Roller', '', 'ETH', '', '', '', '', '', 0, 0),
(7, 'USDT', 'Telel', '', '', '', '', '', '', '', 1, 2),
(8, 'ROL', 'Roller', '', 'USDT', '', '', '', '', '', 0, 0),
(9, 'BTC', 'Bitcoin', '', 'USDT', '', '', '', '', '', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `trade_history`
--

CREATE TABLE `trade_history` (
  `trade_id` bigint(20) NOT NULL,
  `trade_type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `base` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `symbol` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `amount` float(10,2) NOT NULL,
  `prices` float NOT NULL,
  `users_id` int(10) NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `total` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `trade_history`
--

INSERT INTO `trade_history` (`trade_id`, `trade_type`, `base`, `symbol`, `amount`, `prices`, `users_id`, `created`, `hash`, `total`) VALUES
(1, '', 'BTC', 'ROL', 100.00, 0.000025, 1, '2018-08-16 22:06:58', '', 0),
(2, '', 'BTC', 'ROL', 102.00, 0.000026, 4, '2018-08-16 22:07:15', '', 0),
(3, '', 'BTC', 'ROL', 30.00, 0.000025, 1, '2018-08-19 22:06:58', '', 0);

-- --------------------------------------------------------

--
-- Table structure for table `trade_invoice`
--

CREATE TABLE `trade_invoice` (
  `invoice_id` bigint(20) NOT NULL,
  `users_id` int(10) NOT NULL,
  `trade_id` bigint(20) NOT NULL,
  `trade_type` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `base` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `symbol` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `action_prices` float NOT NULL,
  `target_prices` float NOT NULL,
  `amount` float NOT NULL,
  `total` float NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` int(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wallet_alt`
--

CREATE TABLE `wallet_alt` (
  `alt_id` bigint(20) NOT NULL,
  `alt_symbol` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `alt_address` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `alt_amount` float NOT NULL,
  `alt_deposit` float NOT NULL,
  `alt_trade_avalible` float NOT NULL,
  `alt_block_balancer` float NOT NULL,
  `users_id` int(10) NOT NULL,
  `hash` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` int(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wallet_btc`
--

CREATE TABLE `wallet_btc` (
  `btc_id` bigint(20) NOT NULL,
  `btc_address` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `btc_amount` float NOT NULL,
  `btc_deposit` float NOT NULL,
  `btc_trade_avalible` float NOT NULL,
  `btc_block_balancer` float NOT NULL,
  `users_id` int(10) NOT NULL,
  `hash` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `status` int(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `wallet_btc`
--

INSERT INTO `wallet_btc` (`btc_id`, `btc_address`, `btc_amount`, `btc_deposit`, `btc_trade_avalible`, `btc_block_balancer`, `users_id`, `hash`, `created`, `updated`, `status`) VALUES
(1, '37qjvfvAXpX8YmdoAUxbUoX26Jix6hVJAG', 1, 0, 1, 0, 1, '', '2018-08-16 18:10:15', '0000-00-00 00:00:00', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `account`
--
ALTER TABLE `account`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `account_history`
--
ALTER TABLE `account_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `account_login`
--
ALTER TABLE `account_login`
  ADD PRIMARY KEY (`cache_id`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`);

--
-- Indexes for table `api_access`
--
ALTER TABLE `api_access`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `apikey` (`apikey`);

--
-- Indexes for table `markets`
--
ALTER TABLE `markets`
  ADD PRIMARY KEY (`trade_id`),
  ADD KEY `users_id` (`users_id`),
  ADD KEY `base` (`base`),
  ADD KEY `symbol` (`symbol`),
  ADD KEY `trade_side` (`trade_side`);

--
-- Indexes for table `symbol_service`
--
ALTER TABLE `symbol_service`
  ADD PRIMARY KEY (`symbol_id`),
  ADD UNIQUE KEY `symbol_name` (`symbol_name`);

--
-- Indexes for table `symbol_trade`
--
ALTER TABLE `symbol_trade`
  ADD PRIMARY KEY (`symbol_id`);

--
-- Indexes for table `trade_history`
--
ALTER TABLE `trade_history`
  ADD PRIMARY KEY (`trade_id`);

--
-- Indexes for table `trade_invoice`
--
ALTER TABLE `trade_invoice`
  ADD PRIMARY KEY (`invoice_id`);

--
-- Indexes for table `wallet_alt`
--
ALTER TABLE `wallet_alt`
  ADD PRIMARY KEY (`alt_id`),
  ADD UNIQUE KEY `alt_symbol` (`alt_symbol`),
  ADD UNIQUE KEY `alt_address` (`alt_address`);

--
-- Indexes for table `wallet_btc`
--
ALTER TABLE `wallet_btc`
  ADD PRIMARY KEY (`btc_id`),
  ADD UNIQUE KEY `btc_address` (`btc_address`),
  ADD UNIQUE KEY `users_id` (`users_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `account`
--
ALTER TABLE `account`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `account_history`
--
ALTER TABLE `account_history`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;
--
-- AUTO_INCREMENT for table `account_login`
--
ALTER TABLE `account_login`
  MODIFY `cache_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;
--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `api_access`
--
ALTER TABLE `api_access`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `markets`
--
ALTER TABLE `markets`
  MODIFY `trade_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
--
-- AUTO_INCREMENT for table `symbol_service`
--
ALTER TABLE `symbol_service`
  MODIFY `symbol_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `symbol_trade`
--
ALTER TABLE `symbol_trade`
  MODIFY `symbol_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
--
-- AUTO_INCREMENT for table `trade_history`
--
ALTER TABLE `trade_history`
  MODIFY `trade_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT for table `trade_invoice`
--
ALTER TABLE `trade_invoice`
  MODIFY `invoice_id` bigint(20) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `wallet_btc`
--
ALTER TABLE `wallet_btc`
  MODIFY `btc_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;