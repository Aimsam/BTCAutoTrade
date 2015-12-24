-- phpMyAdmin SQL Dump
-- version 4.2.2
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: 2014-10-14 18:04:44
-- 服务器版本： 5.5.37-0ubuntu0.12.04.1
-- PHP Version: 5.5.13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `autotrade`
--
CREATE DATABASE IF NOT EXISTS `autotrade` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `autotrade`;

-- --------------------------------------------------------

--
-- 表的结构 `account`
--

CREATE TABLE IF NOT EXISTS `account` (
`id` int(10) unsigned NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `description` text,
  `market_id` int(10) unsigned NOT NULL,
  `account_group_id` int(10) unsigned NOT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `account_config`
--

CREATE TABLE IF NOT EXISTS `account_config` (
`id` int(10) unsigned NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `value` text,
  `account_id` int(10) unsigned NOT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `account_group`
--

CREATE TABLE IF NOT EXISTS `account_group` (
`id` int(10) unsigned NOT NULL,
  `name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `account_info`
--

CREATE TABLE IF NOT EXISTS `account_info` (
`id` int(10) unsigned NOT NULL,
  `available_btc` decimal(10,4) DEFAULT NULL,
  `available_cny` decimal(10,4) DEFAULT NULL,
  `frozen_btc` decimal(10,4) DEFAULT NULL,
  `frozen_cny` decimal(10,4) DEFAULT NULL,
  `created` datetime NOT NULL,
  `account_id` int(10) unsigned NOT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `config`
--

CREATE TABLE IF NOT EXISTS `config` (
`id` int(11) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `value` text,
  `account_group_id` int(10) unsigned NOT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `market`
--

CREATE TABLE IF NOT EXISTS `market` (
`id` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `orders`
--

CREATE TABLE IF NOT EXISTS `orders` (
`id` int(11) unsigned NOT NULL,
  `price` decimal(10,4) NOT NULL,
  `amount` decimal(10,4) NOT NULL,
  `created` datetime NOT NULL,
  `order_type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'sell buy',
  `account_id` int(10) unsigned NOT NULL,
  `trade_id` int(10) unsigned NOT NULL,
  `order_id` int(11) DEFAULT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `price`
--

CREATE TABLE IF NOT EXISTS `price` (
`id` int(10) unsigned NOT NULL,
  `price` decimal(10,4) DEFAULT NULL,
  `created` datetime NOT NULL,
  `market_id` int(10) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `trade`
--

CREATE TABLE IF NOT EXISTS `trade` (
`id` int(10) unsigned NOT NULL,
  `created` datetime DEFAULT NULL,
  `trade_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'auto force balance earn'
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `user`
--

CREATE TABLE IF NOT EXISTS `user` (
`id` int(10) unsigned NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT '0',
  `account_group_id` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `account`
--
ALTER TABLE `account`
 ADD PRIMARY KEY (`id`), ADD KEY `account_market_idx` (`market_id`), ADD KEY `account_group_idx` (`account_group_id`);

--
-- Indexes for table `account_config`
--
ALTER TABLE `account_config`
 ADD PRIMARY KEY (`id`), ADD KEY `account_config_market_idx` (`account_id`);

--
-- Indexes for table `account_group`
--
ALTER TABLE `account_group`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `account_info`
--
ALTER TABLE `account_info`
 ADD PRIMARY KEY (`id`), ADD KEY `account_info_account_idx` (`account_id`);

--
-- Indexes for table `config`
--
ALTER TABLE `config`
 ADD PRIMARY KEY (`id`), ADD KEY `config_account_group_idx` (`account_group_id`), ADD KEY `name_idx` (`name`);

--
-- Indexes for table `market`
--
ALTER TABLE `market`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
 ADD PRIMARY KEY (`id`), ADD KEY `trade_account_sell_idx` (`account_id`), ADD KEY `order_trade_idx` (`trade_id`);

--
-- Indexes for table `price`
--
ALTER TABLE `price`
 ADD PRIMARY KEY (`id`), ADD KEY `price_history_market_idx` (`market_id`);

--
-- Indexes for table `trade`
--
ALTER TABLE `trade`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `username_UNIQUE` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `account`
--
ALTER TABLE `account`
MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=11;
--
-- AUTO_INCREMENT for table `account_config`
--
ALTER TABLE `account_config`
MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=91;
--
-- AUTO_INCREMENT for table `account_group`
--
ALTER TABLE `account_group`
MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `account_info`
--
ALTER TABLE `account_info`
MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1074443;
--
-- AUTO_INCREMENT for table `config`
--
ALTER TABLE `config`
MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=17;
--
-- AUTO_INCREMENT for table `market`
--
ALTER TABLE `market`
MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=8;
--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=91823;
--
-- AUTO_INCREMENT for table `price`
--
ALTER TABLE `price`
MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `trade`
--
ALTER TABLE `trade`
MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=45912;
--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- 限制导出的表
--

--
-- 限制表 `account`
--
ALTER TABLE `account`
ADD CONSTRAINT `account_group` FOREIGN KEY (`account_group_id`) REFERENCES `account_group` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `account_market` FOREIGN KEY (`market_id`) REFERENCES `market` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- 限制表 `account_config`
--
ALTER TABLE `account_config`
ADD CONSTRAINT `account_config_market` FOREIGN KEY (`account_id`) REFERENCES `account` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- 限制表 `account_info`
--
ALTER TABLE `account_info`
ADD CONSTRAINT `account_info_account` FOREIGN KEY (`account_id`) REFERENCES `account` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- 限制表 `config`
--
ALTER TABLE `config`
ADD CONSTRAINT `config_account_group` FOREIGN KEY (`account_group_id`) REFERENCES `account_group` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- 限制表 `price`
--
ALTER TABLE `price`
ADD CONSTRAINT `price_history_market` FOREIGN KEY (`market_id`) REFERENCES `market` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

INSERT INTO `account_group` (`id`, `name`) VALUES
(1, '测试组'),
(2, '回收站');

INSERT INTO `config` (`id`, `name`, `value`, `account_group_id`) VALUES
(1, 'trans_unit', '0.2', 1),
(2, 'btc_input', '34.31684085', 1),
(3, 'cny_input', '255194', 1),
(4, 'max_frozen_cny', '6800', 1),
(5, 'max_frozen_btc', '3.8', 1),
(6, 'force_times', '30', 1),
(7, 'cny_assurance', '8000', 1),
(8, 'btc_assurance', '0.8', 1),
(9, 'trans_unit', '1', 2),
(10, 'btc_input', '1', 2),
(11, 'cny_input', '1', 2),
(12, 'max_frozen_cny', '1', 2),
(13, 'max_frozen_btc', '1', 2),
(14, 'force_times', '1', 2),
(15, 'cny_assurance', '1', 2),
(16, 'btc_assurance', '1', 2);

INSERT INTO `user` (`id`, `username`, `password`, `is_admin`, `account_group_id`) VALUES
(1, 'admin', 'test', 1, 0);

INSERT INTO `market` (`id`, `name`, `description`, `url`) VALUES
(1, 'Ok', 'OkCoin', ''),
(2, 'China', 'btcc', ''),
(3, 'Chbtc', 'chbtc', ''),
(4, 'Huo', 'huo', ''),
(5, 'Bit', 'bit', '');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
