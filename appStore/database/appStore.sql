-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jan 07, 2026 at 05:43 PM
-- Server version: 5.7.30-log
-- PHP Version: 7.2.28
DROP DATABASE IF EXISTS appstore;
CREATE DATABASE appstore;
USE appstore;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `appstore`
--

-- --------------------------------------------------------

--
-- Table structure for table `apps_en-arab`
--

CREATE TABLE `apps_en-arab` (
  `id` int(11) NOT NULL,
  `shortName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'used in page URL for example',
  `medName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'used in the menu bar for example',
  `longName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'used as the page title for example',
  `shortDescription` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'used, for example, on the ''card'' on the home page linking to the section',
  `longDescription` varchar(1023) COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `iconColour` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `sortOrder` int(11) NOT NULL,
  `orphaningTaskID` int(11) DEFAULT NULL,
  `publishStatus` tinyint(4) NOT NULL DEFAULT '0',
  `organization` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `protocol` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `devDomain` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `testDomain` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `prodDomain` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `appRoot` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `homePage` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `legalAndPolicyLink` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `secureAnAccountLink` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `allowNewRFC` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `allowNewRFI` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `allowNewIncident` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `newRFCLink` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `newRFILink` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `newIncidentLink` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `adminEmail` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `otherDigitalSMEs` tinytext COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `apps_en-gb`
--

CREATE TABLE `apps_en-gb` (
  `id` int(11) NOT NULL,
  `type` enum('AD','COTS','R') COLLATE utf8mb4_unicode_ci NOT NULL,
  `shortName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'used in page URL for example',
  `medName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'used in the menu bar for example',
  `longName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'used as the page title for example',
  `shortDescription` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'used, for example, on the ''card'' on the home page linking to the section',
  `longDescription` varchar(1023) COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `iconColour` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `sortOrder` int(11) NOT NULL,
  `publishStatus` tinyint(4) NOT NULL DEFAULT '0',
  `primaryClient` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `primaryClientUIN` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `protocol` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `devDomain` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `testDomain` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `prodDomain` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `appRoot` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `homePage` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `legalAndPolicyLink` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `secureAnAccountLink` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `allowNewRFCs` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `allowNewRFIs` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `allowNewIncidents` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `newRFCLink` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `newRFILink` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `newIncidentLink` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `adminEmail` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `otherSMEs` tinytext COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `apps_en-us`
--

CREATE TABLE `apps_en-us` (
  `id` int(11) NOT NULL,
  `type` enum('AD','COTS','R') COLLATE utf8mb4_unicode_ci NOT NULL,
  `shortName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'used in page URL for example',
  `medName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'used in the menu bar for example',
  `longName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'used as the page title for example',
  `shortDescription` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'used, for example, on the ''card'' on the home page linking to the section',
  `longDescription` varchar(1023) COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `iconColour` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `sortOrder` int(11) NOT NULL,
  `publishStatus` tinyint(4) NOT NULL DEFAULT '0',
  `primaryClient` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `primaryClientUIN` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `protocol` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `devDomain` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `testDomain` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `prodDomain` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `appRoot` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `homePage` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `legalAndPolicyLink` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `secureAnAccountLink` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `allowNewRFCs` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `allowNewRFIs` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `allowNewIncidents` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `newRFCLink` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `newRFILink` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `newIncidentLink` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `adminEmail` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `otherSMEs` tinytext COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `processes_en-gb`
--

CREATE TABLE `processes_en-gb` (
  `ID` int(11) NOT NULL,
  `admin` tinytext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `POC` tinytext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Client-Facing Service` tinytext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Process` tinytext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `apps_en-arab`
--
ALTER TABLE `apps_en-arab`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `apps_en-gb`
--
ALTER TABLE `apps_en-gb`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `apps_en-us`
--
ALTER TABLE `apps_en-us`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `processes_en-gb`
--
ALTER TABLE `processes_en-gb`
  ADD PRIMARY KEY (`ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `apps_en-arab`
--
ALTER TABLE `apps_en-arab`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `apps_en-gb`
--
ALTER TABLE `apps_en-gb`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `apps_en-us`
--
ALTER TABLE `apps_en-us`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `processes_en-gb`
--
ALTER TABLE `processes_en-gb`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
