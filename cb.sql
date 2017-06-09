-- phpMyAdmin SQL Dump
-- version 4.5.1
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Jun 10, 2017 at 01:18 AM
-- Server version: 10.1.16-MariaDB
-- PHP Version: 7.0.9

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cb`
--

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `branch_id` int(11) NOT NULL,
  `programme_id` int(11) DEFAULT NULL,
  `name` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `branches`:
--

-- --------------------------------------------------------

--
-- Table structure for table `class_group`
--

CREATE TABLE `class_group` (
  `class_group_id` int(10) NOT NULL,
  `branch_id` int(10) NOT NULL,
  `name` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `class_group`:
--

-- --------------------------------------------------------

--
-- Table structure for table `col`
--

CREATE TABLE `col` (
  `college_id` int(4) NOT NULL,
  `name` varchar(500) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- RELATIONS FOR TABLE `col`:
--

-- --------------------------------------------------------

--
-- Table structure for table `college`
--

CREATE TABLE `college` (
  `college_id` int(10) NOT NULL,
  `name` text NOT NULL,
  `grade` varchar(100) DEFAULT NULL,
  `EC_approved_dt` varchar(100) DEFAULT NULL,
  `Cycle1` varchar(100) DEFAULT NULL,
  `lat` float DEFAULT NULL,
  `longitude` float DEFAULT NULL,
  `address` text,
  `city` text,
  `logo` text,
  `cover_pic` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `college`:
--

-- --------------------------------------------------------

--
-- Table structure for table `collegeadmins`
--

CREATE TABLE `collegeadmins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `college_id` int(11) NOT NULL,
  `rollnumber` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `collegeadmins`:
--   `username`
--       `students` -> `username`
--

-- --------------------------------------------------------

--
-- Table structure for table `college_updates`
--

CREATE TABLE `college_updates` (
  `update_id` int(11) NOT NULL,
  `college_id` int(11) NOT NULL,
  `title` text NOT NULL,
  `message` text NOT NULL,
  `link` text NOT NULL,
  `priority` text,
  `timer` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `college_updates`:
--

-- --------------------------------------------------------

--
-- Table structure for table `contacts`
--

CREATE TABLE `contacts` (
  `id` int(11) NOT NULL,
  `phone` int(11) NOT NULL,
  `open` int(11) DEFAULT NULL,
  `type` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `contacts`:
--

-- --------------------------------------------------------

--
-- Table structure for table `contents`
--

CREATE TABLE `contents` (
  `content_id` int(10) NOT NULL,
  `created_by_username` varchar(50) NOT NULL,
  `college_id` int(10) NOT NULL,
  `title` text NOT NULL,
  `view_type` tinyint(4) NOT NULL DEFAULT '1',
  `content_type_id` int(11) NOT NULL,
  `timer` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` varchar(50) NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;

--
-- RELATIONS FOR TABLE `contents`:
--   `created_by_username`
--       `students` -> `username`
--   `college_id`
--       `college` -> `college_id`
--

-- --------------------------------------------------------

--
-- Table structure for table `content_appreciates`
--

CREATE TABLE `content_appreciates` (
  `content_appreciate_id` int(10) NOT NULL,
  `content_id` int(10) NOT NULL,
  `username` varchar(50) NOT NULL,
  `timer` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=FIXED;

--
-- RELATIONS FOR TABLE `content_appreciates`:
--   `content_id`
--       `contents` -> `content_id`
--   `username`
--       `students` -> `username`
--

-- --------------------------------------------------------

--
-- Table structure for table `content_bookmarks`
--

CREATE TABLE `content_bookmarks` (
  `content_bookmark_id` int(10) NOT NULL,
  `content_id` int(10) NOT NULL,
  `username` varchar(50) NOT NULL,
  `timer` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `content_bookmarks`:
--   `content_id`
--       `contents` -> `content_id`
--   `username`
--       `students` -> `username`
--

-- --------------------------------------------------------

--
-- Table structure for table `content_items`
--

CREATE TABLE `content_items` (
  `content_item_id` int(11) NOT NULL,
  `content_id` int(11) NOT NULL,
  `content_item_type` varchar(11) NOT NULL,
  `description` text,
  `image` longblob,
  `view` int(11) DEFAULT NULL,
  `embed` text,
  `embed_url` text,
  `priority` int(11) DEFAULT NULL,
  `timer` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;

--
-- RELATIONS FOR TABLE `content_items`:
--   `content_id`
--       `contents` -> `content_id`
--

-- --------------------------------------------------------

--
-- Table structure for table `content_reports`
--

CREATE TABLE `content_reports` (
  `id` int(10) NOT NULL,
  `username` varchar(50) NOT NULL,
  `content_id` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `type_id` int(10) NOT NULL,
  `reason` text NOT NULL,
  `reported` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `content_reports`:
--

-- --------------------------------------------------------

--
-- Table structure for table `content_tags`
--

CREATE TABLE `content_tags` (
  `content_tag_id` int(11) NOT NULL,
  `content_id` int(11) NOT NULL,
  `name` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `content_tags`:
--

-- --------------------------------------------------------

--
-- Table structure for table `content_types`
--

CREATE TABLE `content_types` (
  `content_type_id` int(10) NOT NULL,
  `name` text NOT NULL,
  `default_view_type` tinyint(4) NOT NULL DEFAULT '0',
  `has_multiple_view_types` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `content_types`:
--

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `event_id` int(10) NOT NULL,
  `college_id` int(10) DEFAULT NULL,
  `created_by_username` varchar(50) DEFAULT NULL,
  `image` longblob,
  `title` text NOT NULL,
  `subtitle` text,
  `loc_type` tinyint(1) DEFAULT '1' COMMENT 'true: offline false: online',
  `from_date` text NOT NULL,
  `from_time` text NOT NULL,
  `from_period` tinyint(4) NOT NULL,
  `to_date` text NOT NULL,
  `to_time` text NOT NULL,
  `to_period` tinyint(4) NOT NULL,
  `description` text NOT NULL,
  `contactperson1` int(11) DEFAULT NULL,
  `contactperson2` int(11) DEFAULT NULL,
  `venue` text NOT NULL,
  `city` text NOT NULL,
  `state` text,
  `audience` tinyint(1) NOT NULL DEFAULT '0',
  `event_type_id` int(11) NOT NULL,
  `event_category_id` int(11) DEFAULT '0',
  `link` text,
  `organiser_name` text NOT NULL,
  `organiser_phone` int(11) NOT NULL,
  `organiser_link` text,
  `price` int(10) NOT NULL,
  `time_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `events`:
--   `created_by_username`
--       `students` -> `username`
--   `college_id`
--       `college` -> `college_id`
--

-- --------------------------------------------------------

--
-- Table structure for table `event_bookmarks`
--

CREATE TABLE `event_bookmarks` (
  `event_bookmark_id` int(10) NOT NULL,
  `event_id` int(10) NOT NULL,
  `username` varchar(70) NOT NULL,
  `timer` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `event_bookmarks`:
--   `event_id`
--       `events` -> `event_id`
--   `username`
--       `students` -> `username`
--

-- --------------------------------------------------------

--
-- Table structure for table `event_images`
--

CREATE TABLE `event_images` (
  `event_image_id` int(10) NOT NULL,
  `event_id` int(11) NOT NULL,
  `url` text NOT NULL,
  `timer` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `event_images`:
--   `event_id`
--       `events` -> `event_id`
--

-- --------------------------------------------------------

--
-- Table structure for table `event_likes`
--

CREATE TABLE `event_likes` (
  `likes_id` int(10) NOT NULL,
  `event_id` int(10) NOT NULL,
  `username` varchar(50) NOT NULL,
  `timed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=FIXED;

--
-- RELATIONS FOR TABLE `event_likes`:
--   `event_id`
--       `events` -> `event_id`
--   `username`
--       `students` -> `username`
--

-- --------------------------------------------------------

--
-- Table structure for table `event_reports`
--

CREATE TABLE `event_reports` (
  `event_report_id` int(10) NOT NULL,
  `username` varchar(50) NOT NULL,
  `event_id` text NOT NULL,
  `reason` text NOT NULL,
  `timer` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `event_reports`:
--

-- --------------------------------------------------------

--
-- Table structure for table `event_rsvps`
--

CREATE TABLE `event_rsvps` (
  `event_rsvp_id` int(10) NOT NULL,
  `event_id` int(10) NOT NULL,
  `state` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'true: Going false: Interested',
  `username` varchar(50) NOT NULL,
  `timer` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=FIXED;

--
-- RELATIONS FOR TABLE `event_rsvps`:
--

-- --------------------------------------------------------

--
-- Table structure for table `event_tags`
--

CREATE TABLE `event_tags` (
  `event_tag_id` int(10) NOT NULL,
  `event_id` int(11) NOT NULL,
  `name` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `event_tags`:
--

-- --------------------------------------------------------

--
-- Table structure for table `event_timings`
--

CREATE TABLE `event_timings` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `month` text NOT NULL,
  `day` int(2) NOT NULL,
  `year` int(5) NOT NULL,
  `time` text NOT NULL,
  `period` tinyint(4) NOT NULL,
  `info` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `event_timings`:
--

-- --------------------------------------------------------

--
-- Table structure for table `event_types`
--

CREATE TABLE `event_types` (
  `event_type_id` int(10) NOT NULL,
  `name` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `event_types`:
--

-- --------------------------------------------------------

--
-- Table structure for table `event_updates`
--

CREATE TABLE `event_updates` (
  `event_update_id` int(11) NOT NULL,
  `event_id` int(11) DEFAULT NULL,
  `title` text,
  `message` text,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `color` text,
  `society_id` int(11) DEFAULT NULL,
  `student_id` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `event_updates`:
--

-- --------------------------------------------------------

--
-- Table structure for table `event_views`
--

CREATE TABLE `event_views` (
  `event_view_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `event_id` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `device_id` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `event_views`:
--

-- --------------------------------------------------------

--
-- Table structure for table `followers`
--

CREATE TABLE `followers` (
  `id` int(10) NOT NULL,
  `followed_username` varchar(50) NOT NULL,
  `follower_username` varchar(50) NOT NULL,
  `timer` timestamp(1) NOT NULL DEFAULT CURRENT_TIMESTAMP
) ;

--
-- RELATIONS FOR TABLE `followers`:
--

-- --------------------------------------------------------

--
-- Table structure for table `hostel`
--

CREATE TABLE `hostel` (
  `hostel_id` int(10) NOT NULL,
  `college_id` int(10) NOT NULL,
  `name` text NOT NULL,
  `gender` text NOT NULL,
  `mess` tinyint(1) NOT NULL DEFAULT '1',
  `lat` float NOT NULL,
  `long` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `hostel`:
--

-- --------------------------------------------------------

--
-- Table structure for table `interests`
--

CREATE TABLE `interests` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `synonyms` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `interests`:
--

-- --------------------------------------------------------

--
-- Table structure for table `lastviewed`
--

CREATE TABLE `lastviewed` (
  `user_id` int(11) NOT NULL,
  `events` timestamp NULL DEFAULT NULL,
  `projects` timestamp NULL DEFAULT NULL,
  `store` timestamp NULL DEFAULT NULL,
  `notes` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `noticeboard` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `lastviewed`:
--

-- --------------------------------------------------------

--
-- Table structure for table `logins`
--

CREATE TABLE `logins` (
  `id` int(11) NOT NULL,
  `username` int(11) NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `device` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `logins`:
--

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE `logs` (
  `id` int(11) NOT NULL,
  `username` text NOT NULL,
  `api` text,
  `requestType` text NOT NULL,
  `deviceType` text NOT NULL,
  `deviceOs` text NOT NULL,
  `ipAddress` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `logs`:
--

-- --------------------------------------------------------

--
-- Table structure for table `noticeboard`
--

CREATE TABLE `noticeboard` (
  `id` int(11) NOT NULL,
  `college` int(11) NOT NULL,
  `title` text NOT NULL,
  `message` text NOT NULL,
  `userId` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `destroyOn` datetime NOT NULL,
  `year` int(11) NOT NULL,
  `branchId` int(11) NOT NULL,
  `status` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `noticeboard`:
--

-- --------------------------------------------------------

--
-- Table structure for table `programmes`
--

CREATE TABLE `programmes` (
  `programme_id` int(11) NOT NULL,
  `college_id` int(11) DEFAULT NULL,
  `name` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `programmes`:
--

-- --------------------------------------------------------

--
-- Table structure for table `request_contact`
--

CREATE TABLE `request_contact` (
  `id` int(10) NOT NULL,
  `req_by_id` int(11) NOT NULL,
  `req_to_id` int(11) NOT NULL,
  `req_field` int(11) NOT NULL,
  `status` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `request_contact`:
--

-- --------------------------------------------------------

--
-- Table structure for table `skills`
--

CREATE TABLE `skills` (
  `skill_id` int(10) NOT NULL,
  `name` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `skills`:
--

-- --------------------------------------------------------

--
-- Table structure for table `social_accounts`
--

CREATE TABLE `social_accounts` (
  `social_account_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `college_id` int(11) DEFAULT '0',
  `roll_number` int(11) DEFAULT NULL,
  `social_id` varchar(50) NOT NULL,
  `type` varchar(40) NOT NULL,
  `token` text NOT NULL,
  `link` text NOT NULL,
  `name` text NOT NULL,
  `email` text,
  `gender` text,
  `about` text NOT NULL,
  `birthday` text,
  `picture` text,
  `cover` text,
  `stamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

--
-- RELATIONS FOR TABLE `social_accounts`:
--   `username`
--       `students` -> `username`
--

-- --------------------------------------------------------

--
-- Table structure for table `societies`
--

CREATE TABLE `societies` (
  `id` int(10) NOT NULL,
  `college_id` int(10) NOT NULL,
  `name` text NOT NULL,
  `description` text NOT NULL,
  `dp` text NOT NULL,
  `created_by` int(10) NOT NULL,
  `website` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `societies`:
--

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(10) NOT NULL,
  `class_group_id` int(10) NOT NULL,
  `name` text NOT NULL,
  `image` text NOT NULL,
  `username` varchar(70) NOT NULL,
  `password` text,
  `roll_number` int(50) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `phone` int(10) DEFAULT NULL,
  `about` text,
  `hostel_id` int(11) DEFAULT NULL,
  `room_number` text,
  `home_city` text,
  `grad_id` int(10) DEFAULT NULL,
  `branch_id` int(10) DEFAULT NULL,
  `year` text,
  `class_id` int(10) DEFAULT NULL,
  `passout_year` int(10) DEFAULT NULL,
  `birthday` datetime DEFAULT NULL,
  `gender` text,
  `college_id` int(11) DEFAULT '0',
  `stamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `students`:
--

-- --------------------------------------------------------

--
-- Table structure for table `student_interests`
--

CREATE TABLE `student_interests` (
  `id` int(10) NOT NULL,
  `username` varchar(50) NOT NULL,
  `interest_id` int(11) NOT NULL,
  `interest_name` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `student_interests`:
--

-- --------------------------------------------------------

--
-- Table structure for table `student_skills`
--

CREATE TABLE `student_skills` (
  `id` int(10) NOT NULL,
  `username` varchar(50) NOT NULL,
  `skill_name` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `student_skills`:
--   `username`
--       `students` -> `username`
--

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `collegeId` int(11) NOT NULL,
  `programId` int(11) NOT NULL,
  `code` text NOT NULL,
  `name` text NOT NULL,
  `credits` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `subjects`:
--

-- --------------------------------------------------------

--
-- Table structure for table `table 43`
--

CREATE TABLE `table 43` (
  `COL 3` varchar(264) DEFAULT NULL,
  `COL 4` varchar(22) DEFAULT NULL,
  `COL 5` varchar(52) DEFAULT NULL,
  `COL 6` varchar(36) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- RELATIONS FOR TABLE `table 43`:
--

-- --------------------------------------------------------

--
-- Table structure for table `team_members`
--

CREATE TABLE `team_members` (
  `id` int(10) NOT NULL,
  `college_id` int(10) NOT NULL,
  `society_id` int(10) NOT NULL,
  `societyUsername` text,
  `added_by_id` int(10) NOT NULL,
  `name` text NOT NULL,
  `email` text NOT NULL,
  `phone` int(10) NOT NULL,
  `position` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `team_members`:
--

-- --------------------------------------------------------

--
-- Table structure for table `todos`
--

CREATE TABLE `todos` (
  `id` int(11) NOT NULL,
  `order` int(11) NOT NULL,
  `uid` text NOT NULL,
  `title` text NOT NULL,
  `completed` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `todos`:
--

--
-- Indexes for dumped tables
--

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`branch_id`);

--
-- Indexes for table `class_group`
--
ALTER TABLE `class_group`
  ADD PRIMARY KEY (`class_group_id`);

--
-- Indexes for table `col`
--
ALTER TABLE `col`
  ADD PRIMARY KEY (`college_id`);

--
-- Indexes for table `college`
--
ALTER TABLE `college`
  ADD PRIMARY KEY (`college_id`);

--
-- Indexes for table `collegeadmins`
--
ALTER TABLE `collegeadmins`
  ADD KEY `username` (`username`);

--
-- Indexes for table `college_updates`
--
ALTER TABLE `college_updates`
  ADD PRIMARY KEY (`update_id`);

--
-- Indexes for table `contents`
--
ALTER TABLE `contents`
  ADD PRIMARY KEY (`content_id`),
  ADD KEY `created_by_username` (`created_by_username`),
  ADD KEY `college_id` (`college_id`);
ALTER TABLE `contents` ADD FULLTEXT KEY `title` (`title`);

--
-- Indexes for table `content_appreciates`
--
ALTER TABLE `content_appreciates`
  ADD PRIMARY KEY (`content_appreciate_id`),
  ADD KEY `content_id` (`content_id`),
  ADD KEY `username` (`username`);

--
-- Indexes for table `content_bookmarks`
--
ALTER TABLE `content_bookmarks`
  ADD PRIMARY KEY (`content_bookmark_id`),
  ADD KEY `content_id` (`content_id`),
  ADD KEY `username` (`username`);

--
-- Indexes for table `content_items`
--
ALTER TABLE `content_items`
  ADD PRIMARY KEY (`content_item_id`),
  ADD KEY `content_id` (`content_id`);

--
-- Indexes for table `content_reports`
--
ALTER TABLE `content_reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `content_tags`
--
ALTER TABLE `content_tags`
  ADD PRIMARY KEY (`content_tag_id`);

--
-- Indexes for table `content_types`
--
ALTER TABLE `content_types`
  ADD PRIMARY KEY (`content_type_id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `created_by_username` (`created_by_username`),
  ADD KEY `college_id` (`college_id`);
ALTER TABLE `events` ADD FULLTEXT KEY `title` (`title`);
ALTER TABLE `events` ADD FULLTEXT KEY `subtitle` (`subtitle`);
ALTER TABLE `events` ADD FULLTEXT KEY `description` (`description`);

--
-- Indexes for table `event_bookmarks`
--
ALTER TABLE `event_bookmarks`
  ADD PRIMARY KEY (`event_bookmark_id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `username` (`username`);

--
-- Indexes for table `event_images`
--
ALTER TABLE `event_images`
  ADD PRIMARY KEY (`event_image_id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `event_likes`
--
ALTER TABLE `event_likes`
  ADD PRIMARY KEY (`likes_id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `username` (`username`);

--
-- Indexes for table `event_reports`
--
ALTER TABLE `event_reports`
  ADD PRIMARY KEY (`event_report_id`);

--
-- Indexes for table `event_rsvps`
--
ALTER TABLE `event_rsvps`
  ADD PRIMARY KEY (`event_rsvp_id`);

--
-- Indexes for table `event_tags`
--
ALTER TABLE `event_tags`
  ADD PRIMARY KEY (`event_tag_id`);

--
-- Indexes for table `event_timings`
--
ALTER TABLE `event_timings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `event_types`
--
ALTER TABLE `event_types`
  ADD PRIMARY KEY (`event_type_id`);

--
-- Indexes for table `event_updates`
--
ALTER TABLE `event_updates`
  ADD PRIMARY KEY (`event_update_id`);

--
-- Indexes for table `event_views`
--
ALTER TABLE `event_views`
  ADD PRIMARY KEY (`event_view_id`);

--
-- Indexes for table `social_accounts`
--
ALTER TABLE `social_accounts`
  ADD PRIMARY KEY (`social_account_id`),
  ADD KEY `username` (`username`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`username`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `student_interests`
--
ALTER TABLE `student_interests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_skills`
--
ALTER TABLE `student_skills`
  ADD PRIMARY KEY (`id`),
  ADD KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `col`
--
ALTER TABLE `col`
  MODIFY `college_id` int(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7270;
--
-- AUTO_INCREMENT for table `college`
--
ALTER TABLE `college`
  MODIFY `college_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7274;
--
-- AUTO_INCREMENT for table `college_updates`
--
ALTER TABLE `college_updates`
  MODIFY `update_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `contents`
--
ALTER TABLE `contents`
  MODIFY `content_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `content_appreciates`
--
ALTER TABLE `content_appreciates`
  MODIFY `content_appreciate_id` int(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `content_bookmarks`
--
ALTER TABLE `content_bookmarks`
  MODIFY `content_bookmark_id` int(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `content_items`
--
ALTER TABLE `content_items`
  MODIFY `content_item_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `content_reports`
--
ALTER TABLE `content_reports`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `content_tags`
--
ALTER TABLE `content_tags`
  MODIFY `content_tag_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT for table `event_bookmarks`
--
ALTER TABLE `event_bookmarks`
  MODIFY `event_bookmark_id` int(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `event_images`
--
ALTER TABLE `event_images`
  MODIFY `event_image_id` int(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `event_likes`
--
ALTER TABLE `event_likes`
  MODIFY `likes_id` int(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `event_reports`
--
ALTER TABLE `event_reports`
  MODIFY `event_report_id` int(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `event_rsvps`
--
ALTER TABLE `event_rsvps`
  MODIFY `event_rsvp_id` int(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `event_tags`
--
ALTER TABLE `event_tags`
  MODIFY `event_tag_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
--
-- AUTO_INCREMENT for table `event_timings`
--
ALTER TABLE `event_timings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `event_updates`
--
ALTER TABLE `event_updates`
  MODIFY `event_update_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `event_views`
--
ALTER TABLE `event_views`
  MODIFY `event_view_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `followers`
--
ALTER TABLE `followers`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `social_accounts`
--
ALTER TABLE `social_accounts`
  MODIFY `social_account_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=268;
--
-- AUTO_INCREMENT for table `student_interests`
--
ALTER TABLE `student_interests`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `student_skills`
--
ALTER TABLE `student_skills`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;
--
-- Constraints for dumped tables
--

--
-- Constraints for table `collegeadmins`
--
ALTER TABLE `collegeadmins`
  ADD CONSTRAINT `collegeadmins_ibfk_1` FOREIGN KEY (`username`) REFERENCES `students` (`username`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `contents`
--
ALTER TABLE `contents`
  ADD CONSTRAINT `contents_ibfk_1` FOREIGN KEY (`created_by_username`) REFERENCES `students` (`username`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `contents_ibfk_2` FOREIGN KEY (`college_id`) REFERENCES `college` (`college_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `content_appreciates`
--
ALTER TABLE `content_appreciates`
  ADD CONSTRAINT `content_appreciates_ibfk_1` FOREIGN KEY (`content_id`) REFERENCES `contents` (`content_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `content_appreciates_ibfk_2` FOREIGN KEY (`username`) REFERENCES `students` (`username`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `content_bookmarks`
--
ALTER TABLE `content_bookmarks`
  ADD CONSTRAINT `content_bookmarks_ibfk_1` FOREIGN KEY (`content_id`) REFERENCES `contents` (`content_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `content_bookmarks_ibfk_2` FOREIGN KEY (`username`) REFERENCES `students` (`username`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `content_items`
--
ALTER TABLE `content_items`
  ADD CONSTRAINT `content_items_ibfk_1` FOREIGN KEY (`content_id`) REFERENCES `contents` (`content_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`created_by_username`) REFERENCES `students` (`username`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `events_ibfk_2` FOREIGN KEY (`college_id`) REFERENCES `college` (`college_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `event_bookmarks`
--
ALTER TABLE `event_bookmarks`
  ADD CONSTRAINT `event_bookmarks_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `event_bookmarks_ibfk_2` FOREIGN KEY (`username`) REFERENCES `students` (`username`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `event_images`
--
ALTER TABLE `event_images`
  ADD CONSTRAINT `event_images_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `event_likes`
--
ALTER TABLE `event_likes`
  ADD CONSTRAINT `event_likes_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `event_likes_ibfk_2` FOREIGN KEY (`username`) REFERENCES `students` (`username`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `social_accounts`
--
ALTER TABLE `social_accounts`
  ADD CONSTRAINT `social_accounts_ibfk_1` FOREIGN KEY (`username`) REFERENCES `students` (`username`);

--
-- Constraints for table `student_skills`
--
ALTER TABLE `student_skills`
  ADD CONSTRAINT `student_skills_ibfk_1` FOREIGN KEY (`username`) REFERENCES `students` (`username`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
