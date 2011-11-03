-- phpMyAdmin SQL Dump
-- version 3.4.5deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Nov 03, 2011 at 09:18 AM
-- Server version: 5.1.58
-- PHP Version: 5.3.6-13ubuntu3.2

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `telwebfinanceblank`
--

-- --------------------------------------------------------

--
-- Table structure for table `acc`
--

CREATE TABLE IF NOT EXISTS `acc` (
  `accid` int(11) NOT NULL AUTO_INCREMENT,
  `accname` varchar(255) NOT NULL,
  `sbal` decimal(19,4) NOT NULL COMMENT 'Starting Balance of the Account',
  `sdate` date NOT NULL COMMENT 'Starting Date for the Accounting',
  PRIMARY KEY (`accid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=13 ;

-- --------------------------------------------------------

--
-- Table structure for table `budget`
--

CREATE TABLE IF NOT EXISTS `budget` (
  `budgetid` int(11) NOT NULL AUTO_INCREMENT,
  `b_month` int(11) DEFAULT NULL,
  `catid` int(11) NOT NULL,
  `b_amount` decimal(19,4) NOT NULL,
  PRIMARY KEY (`budgetid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=95 ;

-- --------------------------------------------------------

--
-- Table structure for table `cat`
--

CREATE TABLE IF NOT EXISTS `cat` (
  `catid` int(11) NOT NULL AUTO_INCREMENT,
  `catname` varchar(255) NOT NULL,
  PRIMARY KEY (`catid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=86 ;

-- --------------------------------------------------------

--
-- Table structure for table `payee`
--

CREATE TABLE IF NOT EXISTS `payee` (
  `payeeid` int(11) NOT NULL AUTO_INCREMENT,
  `payeename` varchar(255) NOT NULL,
  PRIMARY KEY (`payeeid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=229 ;

-- --------------------------------------------------------

--
-- Table structure for table `recon`
--

CREATE TABLE IF NOT EXISTS `recon` (
  `reconid` int(11) NOT NULL AUTO_INCREMENT,
  `accid` int(11) NOT NULL,
  `ebalance` decimal(19,4) DEFAULT NULL,
  `completed` datetime DEFAULT NULL,
  `recdate` date NOT NULL,
  PRIMARY KEY (`reconid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=15 ;

-- --------------------------------------------------------

--
-- Table structure for table `trans`
--

CREATE TABLE IF NOT EXISTS `trans` (
  `transid` int(11) NOT NULL AUTO_INCREMENT,
  `transdate` date NOT NULL,
  `transnumber` varchar(16) NOT NULL,
  `transpayee` int(11) NOT NULL,
  `transtypeid` int(11) NOT NULL,
  `accid` int(11) NOT NULL,
  `reconid` int(11) DEFAULT NULL,
  `balance` decimal(14,2) NOT NULL,
  PRIMARY KEY (`transid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2123 ;

-- --------------------------------------------------------

--
-- Table structure for table `transparts`
--

CREATE TABLE IF NOT EXISTS `transparts` (
  `transpartid` int(11) NOT NULL AUTO_INCREMENT,
  `transid` int(11) NOT NULL,
  `catid` int(11) NOT NULL,
  `memo` varchar(255) NOT NULL,
  `amount` decimal(19,4) NOT NULL,
  PRIMARY KEY (`transpartid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2156 ;

-- --------------------------------------------------------

--
-- Table structure for table `transtypes`
--

CREATE TABLE IF NOT EXISTS `transtypes` (
  `transtypeid` int(11) NOT NULL AUTO_INCREMENT,
  `transtype` varchar(255) NOT NULL,
  `transtypedesc` text NOT NULL,
  PRIMARY KEY (`transtypeid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=8 ;

--
-- Dumping data for table `transtypes`
--

INSERT INTO `transtypes` (`transtypeid`, `transtype`, `transtypedesc`) VALUES
(1, 'check', ''),
(2, 'debit', ''),
(3, 'withdrawal', ''),
(4, 'transfer', ''),
(5, 'deposit', ''),
(6, 'charge', ''),
(7, 'interest', '');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `userid` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` varchar(40) NOT NULL,
  `uniqueid` varchar(40) NOT NULL,
  PRIMARY KEY (`userid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
