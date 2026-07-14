-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Aug 27, 2025 at 08:08 AM
-- Server version: 9.1.0
-- PHP Version: 8.1.31

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `school_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `addons`
--

DROP TABLE IF EXISTS `addons`;
CREATE TABLE IF NOT EXISTS `addons` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `image` text NOT NULL,
  `name` varchar(500) DEFAULT NULL,
  `config_name` varchar(200) NOT NULL DEFAULT '',
  `short_name` varchar(100) NOT NULL,
  `directory` varchar(500) NOT NULL,
  `description` text,
  `price` float(10,2) NOT NULL DEFAULT '0.00',
  `current_version` varchar(50) DEFAULT NULL,
  `article_link` text NOT NULL,
  `installation_by` int DEFAULT NULL,
  `uninstall_version` varchar(50) DEFAULT NULL,
  `unistall_by` int DEFAULT NULL,
  `addon_prod` text,
  `addon_ver` text,
  `last_update` datetime DEFAULT NULL,
  `current_stage` int NOT NULL DEFAULT '0' COMMENT '0 for buy addon,1 for folder available ready to install,2 for folder addon installed',
  `product_order` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `addons`
--

INSERT INTO `addons` (`id`, `product_id`, `image`, `name`, `config_name`, `short_name`, `directory`, `description`, `price`, `current_version`, `article_link`, `installation_by`, `uninstall_version`, `unistall_by`, `addon_prod`, `addon_ver`, `last_update`, `current_stage`, `product_order`, `created_at`, `updated_at`) VALUES
(1, 47443722, 'uploads/addon_images/sscbse_images.jpg', 'Smart School CBSE Examination', 'cbse-config', 'sscbse', 'cbse_examination', 'CBSE Examination addon adds CBSE Examination module in Smart School. Using this module teacher/staff can create and print marksheets with advance features.', 0.00, NULL, 'https://go.smart-school.in/cbse-exam', NULL, NULL, NULL, NULL, NULL, '2024-09-03 16:04:58', 0, 4, '2024-09-03 16:04:58', '2025-03-03 05:32:37'),
(2, 44278049, 'uploads/addon_images/sstfa_images.jpg', 'Smart School Two Factor Authentication', 'google-authenticate-config', 'sstfa', 'two_factor_authentication', 'Two Factor Authentication addon adds Two Factor Authentication module in Smart School. Using this module you can enhance login security of your Smart School users.', 0.00, NULL, 'https://go.smart-school.in/2fa', NULL, NULL, NULL, NULL, NULL, '2025-01-29 11:16:14', 0, 5, '2024-09-07 10:45:18', '2025-03-03 05:32:28'),
(3, 44277916, 'uploads/addon_images/ssmb_images.jpg', 'Smart School Multi Branch', 'multibranch-config', 'ssmb', 'multi_branch', 'Multi Branch addon adds Multi Branch module in Smart School. Using this module Superadmin user can add other any number of schools/branches.', 0.00, NULL, 'https://go.smart-school.in/multi-branch', NULL, NULL, NULL, NULL, NULL, '2025-01-29 11:16:15', 0, 6, '2024-09-07 10:45:18', '2025-03-03 05:32:20'),
(4, 44247532, 'uploads/addon_images/ssbr_images.jpg', 'Smart School Behaviour Records', 'behaviour-report-config', 'ssbr', 'behavior_records', 'Behaviour Records addon adds Behaviour Records module in Smart School. Using this module teacher/staff can create different incidents with positive/negative marks and then assign these incidents on students.', 0.00, NULL, 'https://go.smart-school.in/behaviour-records', NULL, NULL, NULL, NULL, NULL, '2025-01-29 11:16:19', 0, 7, '2024-09-07 10:45:42', '2025-03-03 05:32:11'),
(5, 33101540, 'uploads/addon_images/ssoclc_images.jpg', 'Smart School Online Course', 'onlinecourse-config', 'ssoclc', 'online_course', 'Online Course addon adds Online Course module in Smart School. Using this module teacher/staff can create free or paid online course with their study material based on video, audio or in document content format.', 0.00, NULL, 'https://go.smart-school.in/online-course', NULL, NULL, NULL, NULL, NULL, '2025-01-29 11:16:19', 0, 8, '2024-09-07 10:45:42', '2025-03-03 05:32:02'),
(6, 27492043, 'uploads/addon_images/sszlc_images.jpg', 'Smart School Zoom Live Classes', 'zoom-config', 'sszlc', 'zoom_live_class', 'Zoom Live Class addon adds Zoom Live Class module in Smart School. Using this module teacher/staff can create live online classes using Zoom.us service. Further students can join these classes.', 0.00, NULL, 'https://go.smart-school.in/zoom', NULL, NULL, NULL, NULL, NULL, '2025-01-29 11:16:17', 0, 10, '2024-09-07 10:46:10', '2025-03-03 05:31:49'),
(7, 28941973, 'uploads/addon_images/ssglc_images.jpg', 'Smart School Gmeet Live Class', 'gmeet-config', 'ssglc', 'gmeet_live_class', 'Gmeet Live Class addon adds Gmeet Live Class module in Smart School. Using this module teacher/staff can create live online classes using http://meet.google.com service. Further students can join these classes.', 0.00, NULL, 'https://go.smart-school.in/gmeet', NULL, NULL, NULL, NULL, NULL, '2025-01-29 11:16:24', 0, 9, '2024-09-07 10:46:10', '2025-03-03 05:31:33'),
(8, 50336584, 'uploads/addon_images/ssqra_images.jpg', 'Smart School QR Code Attendance', 'qrattendance-config', 'ssqra', 'qr_code_attendance', 'QR Code Attendance addon adds automated Student/Staff attendance using QR/Barcode module in Smart School. Using this module Student/Staff can submit their attendance by just scanning their ID Card.', 0.00, NULL, 'https://go.smart-school.in/qr-attendance', NULL, NULL, NULL, NULL, NULL, '2025-01-28 22:28:58', 0, 3, '2025-01-13 13:10:06', '2025-03-03 05:32:47'),
(9, 57220011, 'uploads/addon_images/ssqfc_images.jpg', 'Smart School Quick Fees Create', 'quickfees-config', 'ssqfc', 'quick_fees_create', 'Quick Fees Create addon adds one click fees create feature in Smart School Fees Collection module. Using this you can create and assign fees on students in just few seconds and all Fees Category, Fees Groups, Fees Masters will be create automatically in your Smart School.', 0.00, NULL, 'https://go.smart-school.in/quick-fees', NULL, NULL, NULL, NULL, NULL, '2025-01-28 22:28:58', 0, 2, '2025-01-13 13:10:06', '2025-03-03 05:33:00'),
(10, 57219905, 'uploads/addon_images/sstpa_images.jpg', 'Smart School Thermal Print', 'thermalprint-config', 'sstpa', 'thermal_print', 'Thermal Print addon adds Thermal Printer compatible small size fees receipt print capability in Smart School. Using this module you can utilize modern cost effective fees receipt printing in Smart School.', 0.00, NULL, 'https://go.smart-school.in/thermal-print', NULL, NULL, NULL, NULL, NULL, '2025-01-30 10:28:42', 0, 1, '2025-01-13 13:10:06', '2025-03-03 05:33:06');

-- --------------------------------------------------------

--
-- Table structure for table `addon_versions`
--

DROP TABLE IF EXISTS `addon_versions`;
CREATE TABLE IF NOT EXISTS `addon_versions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `addon_id` int DEFAULT NULL,
  `version` varchar(10) DEFAULT NULL,
  `version_order` int DEFAULT NULL,
  `folder_path` text,
  `sort_description` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `addon_id` (`addon_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `alumni_events`
--

DROP TABLE IF EXISTS `alumni_events`;
CREATE TABLE IF NOT EXISTS `alumni_events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` text NOT NULL,
  `event_for` varchar(100) NOT NULL,
  `session_id` int DEFAULT NULL,
  `class_id` int DEFAULT NULL,
  `section` varchar(255) NOT NULL,
  `from_date` datetime NOT NULL,
  `to_date` datetime NOT NULL,
  `note` text NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `is_active` int NOT NULL,
  `event_notification_message` text NOT NULL,
  `show_onwebsite` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`),
  KEY `class_id` (`class_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `alumni_students`
--

DROP TABLE IF EXISTS `alumni_students`;
CREATE TABLE IF NOT EXISTS `alumni_students` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `current_email` varchar(255) NOT NULL,
  `current_phone` varchar(255) NOT NULL,
  `occupation` text NOT NULL,
  `address` text NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `annual_calendar`
--

DROP TABLE IF EXISTS `annual_calendar`;
CREATE TABLE IF NOT EXISTS `annual_calendar` (
  `id` int NOT NULL AUTO_INCREMENT,
  `session_id` int DEFAULT NULL,
  `holiday_type` int NOT NULL,
  `from_date` datetime DEFAULT NULL,
  `to_date` datetime DEFAULT NULL,
  `description` text NOT NULL,
  `is_active` int NOT NULL DEFAULT '1',
  `holiday_color` varchar(200) NOT NULL,
  `front_site` int NOT NULL DEFAULT '0',
  `created_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `attendence_type`
--

DROP TABLE IF EXISTS `attendence_type`;
CREATE TABLE IF NOT EXISTS `attendence_type` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type` varchar(50) DEFAULT NULL,
  `key_value` varchar(50) NOT NULL,
  `long_lang_name` varchar(250) DEFAULT NULL,
  `long_name_style` varchar(250) DEFAULT NULL,
  `is_active` varchar(255) DEFAULT 'no',
  `for_qr_attendance` int NOT NULL DEFAULT '1',
  `for_schedule` int NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `attendence_type`
--

INSERT INTO `attendence_type` (`id`, `type`, `key_value`, `long_lang_name`, `long_name_style`, `is_active`, `for_qr_attendance`, `for_schedule`, `created_at`, `updated_at`) VALUES
(1, 'Present', '<b class=\"text text-success\">P</b>', 'present', 'label label-success', 'yes', 1, 1, '2023-12-13 07:53:10', '0000-00-00 00:00:00'),
(2, 'Late With Excuse', '<b class=\"text text-warning\">E</b>', 'late_with_excuse', 'label label-warning text-dark', 'no', 0, 0, '2023-12-13 07:51:03', '0000-00-00 00:00:00'),
(3, 'Late', '<b class=\"text text-warning\">L</b>', 'late', 'label label-warning text-dark', 'yes', 1, 1, '2023-12-13 07:51:09', '0000-00-00 00:00:00'),
(4, 'Absent', '<b class=\"text text-danger\">A</b>', 'absent', 'label label-danger', 'yes', 0, 0, '2023-12-15 06:18:05', '0000-00-00 00:00:00'),
(5, 'Holiday', 'H', 'holiday', 'label label-info', 'yes', 0, 0, '2023-12-14 12:57:13', '0000-00-00 00:00:00'),
(6, 'Half Day', '<b class=\"text text-warning\">F</b>', 'half_day', 'label label-warning text-dark', 'yes', 1, 1, '2023-12-15 06:18:37', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

DROP TABLE IF EXISTS `books`;
CREATE TABLE IF NOT EXISTS `books` (
  `id` int NOT NULL AUTO_INCREMENT,
  `book_title` varchar(100) NOT NULL,
  `book_no` varchar(50) NOT NULL,
  `isbn_no` varchar(100) NOT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `rack_no` varchar(100) NOT NULL,
  `publish` varchar(100) DEFAULT NULL,
  `author` varchar(100) DEFAULT NULL,
  `qty` int DEFAULT NULL,
  `perunitcost` float(10,2) DEFAULT NULL,
  `postdate` date DEFAULT NULL,
  `description` text,
  `available` varchar(10) DEFAULT 'yes',
  `is_active` varchar(255) DEFAULT 'no',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `book_issues`
--

DROP TABLE IF EXISTS `book_issues`;
CREATE TABLE IF NOT EXISTS `book_issues` (
  `id` int NOT NULL AUTO_INCREMENT,
  `book_id` int NOT NULL,
  `member_id` int DEFAULT NULL,
  `duereturn_date` date DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `is_returned` int DEFAULT '0',
  `is_active` varchar(10) NOT NULL DEFAULT 'no',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `book_id` (`book_id`),
  KEY `member_id` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `captcha`
--

DROP TABLE IF EXISTS `captcha`;
CREATE TABLE IF NOT EXISTS `captcha` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(250) NOT NULL,
  `status` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `captcha`
--

INSERT INTO `captcha` (`id`, `name`, `status`, `created_at`, `updated_at`) VALUES
(1, 'userlogin', 0, '2021-01-19 08:10:29', '2025-08-25 08:58:03'),
(2, 'login', 0, '2021-01-19 08:10:31', '2025-08-25 08:58:03'),
(3, 'admission', 0, '2021-01-19 04:48:11', '2025-08-25 08:58:03'),
(4, 'complain', 0, '2021-01-19 04:48:13', '2025-08-25 08:58:03'),
(5, 'contact_us', 0, '2021-01-19 04:48:15', '2025-08-25 08:58:03');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category` varchar(100) DEFAULT NULL,
  `is_active` varchar(255) DEFAULT 'no',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `certificates`
--

DROP TABLE IF EXISTS `certificates`;
CREATE TABLE IF NOT EXISTS `certificates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `certificate_name` varchar(100) NOT NULL,
  `certificate_text` text NOT NULL,
  `left_header` varchar(100) NOT NULL,
  `center_header` varchar(100) NOT NULL,
  `right_header` varchar(100) NOT NULL,
  `left_footer` varchar(100) NOT NULL,
  `right_footer` varchar(100) NOT NULL,
  `center_footer` varchar(100) NOT NULL,
  `background_image` varchar(100) DEFAULT NULL,
  `created_for` tinyint(1) NOT NULL COMMENT '1 = staff, 2 = students',
  `status` tinyint(1) NOT NULL,
  `header_height` int NOT NULL,
  `content_height` int NOT NULL,
  `footer_height` int NOT NULL,
  `content_width` int NOT NULL,
  `enable_student_image` tinyint(1) NOT NULL COMMENT '0=no,1=yes',
  `enable_image_height` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `certificates`
--

INSERT INTO `certificates` (`id`, `certificate_name`, `certificate_text`, `left_header`, `center_header`, `right_header`, `left_footer`, `right_footer`, `center_footer`, `background_image`, `created_for`, `status`, `header_height`, `content_height`, `footer_height`, `content_width`, `enable_student_image`, `enable_image_height`, `created_at`, `updated_at`) VALUES
(1, 'Sample Transfer Certificate', 'This is certify that <b>[name]</b> has born on [dob]  <br> and have following details [present_address] [guardian] [created_at] [admission_no] [roll_no] [class] [section] [gender] [admission_date] [category] [cast] [father_name] [mother_name] [religion] [email] [phone] .<br>We wish best of luck for future endeavors.', 'Reff. No.....1111111.........', 'To Whomever It May Concern', 'Date: _10__/_10__/__2019__', '.................................<br>admin', '.................................<br>principal', '.................................<br>admin', 'sampletc121.png', 2, 1, 360, 400, 480, 810, 1, 230, '2019-12-21 15:14:34', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `chat_connections`
--

DROP TABLE IF EXISTS `chat_connections`;
CREATE TABLE IF NOT EXISTS `chat_connections` (
  `id` int NOT NULL AUTO_INCREMENT,
  `chat_user_one` int NOT NULL,
  `chat_user_two` int NOT NULL,
  `ip` varchar(30) DEFAULT NULL,
  `time` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `chat_user_one` (`chat_user_one`),
  KEY `chat_user_two` (`chat_user_two`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

DROP TABLE IF EXISTS `chat_messages`;
CREATE TABLE IF NOT EXISTS `chat_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `message` text,
  `chat_user_id` int NOT NULL,
  `ip` varchar(30) NOT NULL,
  `time` int NOT NULL,
  `is_first` int DEFAULT '0',
  `is_read` int NOT NULL DEFAULT '0',
  `chat_connection_id` int NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `chat_user_id` (`chat_user_id`),
  KEY `chat_connection_id` (`chat_connection_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `chat_users`
--

DROP TABLE IF EXISTS `chat_users`;
CREATE TABLE IF NOT EXISTS `chat_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_type` varchar(20) DEFAULT NULL,
  `staff_id` int DEFAULT NULL,
  `student_id` int DEFAULT NULL,
  `create_staff_id` int DEFAULT NULL,
  `create_student_id` int DEFAULT NULL,
  `is_active` int DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `staff_id` (`staff_id`),
  KEY `student_id` (`student_id`),
  KEY `create_staff_id` (`create_staff_id`),
  KEY `create_student_id` (`create_student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

DROP TABLE IF EXISTS `classes`;
CREATE TABLE IF NOT EXISTS `classes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `class` varchar(60) DEFAULT NULL,
  `is_active` varchar(255) DEFAULT 'no',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `class_sections`
--

DROP TABLE IF EXISTS `class_sections`;
CREATE TABLE IF NOT EXISTS `class_sections` (
  `id` int NOT NULL AUTO_INCREMENT,
  `class_id` int DEFAULT NULL,
  `section_id` int DEFAULT NULL,
  `is_active` varchar(255) DEFAULT 'no',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `class_id` (`class_id`),
  KEY `section_id` (`section_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `class_section_times`
--

DROP TABLE IF EXISTS `class_section_times`;
CREATE TABLE IF NOT EXISTS `class_section_times` (
  `id` int NOT NULL AUTO_INCREMENT,
  `class_section_id` int DEFAULT NULL,
  `time` time DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `class_section_id` (`class_section_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `class_teacher`
--

DROP TABLE IF EXISTS `class_teacher`;
CREATE TABLE IF NOT EXISTS `class_teacher` (
  `id` int NOT NULL AUTO_INCREMENT,
  `session_id` int NOT NULL,
  `class_id` int NOT NULL,
  `section_id` int NOT NULL,
  `staff_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `class_id` (`class_id`),
  KEY `section_id` (`section_id`),
  KEY `session_id` (`session_id`),
  KEY `staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `complaint`
--

DROP TABLE IF EXISTS `complaint`;
CREATE TABLE IF NOT EXISTS `complaint` (
  `id` int NOT NULL AUTO_INCREMENT,
  `complaint_type` varchar(255) NOT NULL,
  `source` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `contact` varchar(15) NOT NULL,
  `email` varchar(200) NOT NULL,
  `date` date NOT NULL,
  `description` text NOT NULL,
  `action_taken` varchar(200) NOT NULL,
  `assigned` varchar(50) NOT NULL,
  `note` text NOT NULL,
  `image` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `complaint_type`
--

DROP TABLE IF EXISTS `complaint_type`;
CREATE TABLE IF NOT EXISTS `complaint_type` (
  `id` int NOT NULL AUTO_INCREMENT,
  `complaint_type` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `contents`
--

DROP TABLE IF EXISTS `contents`;
CREATE TABLE IF NOT EXISTS `contents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(100) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `is_public` varchar(10) DEFAULT 'No',
  `class_id` int DEFAULT NULL,
  `cls_sec_id` int DEFAULT NULL,
  `file` varchar(250) DEFAULT NULL,
  `date` date NOT NULL,
  `note` text,
  `is_active` varchar(255) DEFAULT 'no',
  `created_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `class_id` (`class_id`),
  KEY `cls_sec_id` (`cls_sec_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `content_for`
--

DROP TABLE IF EXISTS `content_for`;
CREATE TABLE IF NOT EXISTS `content_for` (
  `id` int NOT NULL AUTO_INCREMENT,
  `role` varchar(50) DEFAULT NULL,
  `content_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `content_id` (`content_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `content_types`
--

DROP TABLE IF EXISTS `content_types`;
CREATE TABLE IF NOT EXISTS `content_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(200) DEFAULT NULL,
  `description` text,
  `is_active` int DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `cumulative_fine`
--

DROP TABLE IF EXISTS `cumulative_fine`;
CREATE TABLE IF NOT EXISTS `cumulative_fine` (
  `id` int NOT NULL AUTO_INCREMENT,
  `overdue_day` int NOT NULL,
  `fine_amount` float(10,2) NOT NULL,
  `fee_groups_feetype_id` int NOT NULL,
  `fee_session_group_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `currencies`
--

DROP TABLE IF EXISTS `currencies`;
CREATE TABLE IF NOT EXISTS `currencies` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `short_name` varchar(100) DEFAULT NULL,
  `symbol` varchar(10) DEFAULT NULL,
  `base_price` varchar(10) NOT NULL DEFAULT '1',
  `is_active` int DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=180 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `currencies`
--

INSERT INTO `currencies` (`id`, `name`, `short_name`, `symbol`, `base_price`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'AED', 'AED', 'AEDf', '1', 0, '2022-12-30 06:19:15', '2025-08-25 08:58:03'),
(2, 'AFN', 'AFN', '؋', '1', 0, '2022-12-30 06:19:19', '2025-08-25 08:58:03'),
(3, 'ALL', 'ALL', 'ALL', '1', 0, '2022-12-30 06:19:22', '2025-08-25 08:58:03'),
(4, 'AMD', 'AMD', 'AMD', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(5, 'ANG', 'ANG', 'ANG', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(6, 'AOA', 'AOA', 'AOA', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(7, 'ARS', 'ARS', 'ARS', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(8, 'AUD', 'AUD', 'AUD', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(9, 'AWG', 'AWG', 'AWG', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(10, 'AZN', 'AZN', 'AZN', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(11, 'BAM', 'BAM', 'BAM', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(12, 'BAM', 'BAM', 'BAM', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(13, 'BDT', 'BDT', 'BDT', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(14, 'BGN', 'BGN', 'BGN', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(15, 'BHD', 'BHD', 'BHD', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(16, 'BIF', 'BIF', 'BIF', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(17, 'BMD', 'BMD', 'BMD', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(18, 'BND', 'BND', 'BND', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(19, 'BOB', 'BOB', 'BOB', '1', 0, '2022-12-30 06:19:29', '2025-08-25 08:58:03'),
(20, 'BOV', 'BOV', 'BOV', '1', 0, '2022-12-30 06:19:38', '2025-08-25 08:58:03'),
(21, 'BRL', 'BRL', 'BRL', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(22, 'BSD', 'BSD', 'BSD', '1', 0, '2022-12-30 06:19:40', '2025-08-25 08:58:03'),
(23, 'BTN', 'BTN', 'BTN', '1', 0, '2022-12-30 06:19:42', '2025-08-25 08:58:03'),
(24, 'BWP', 'BWP', 'BWP', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(25, 'BYN', 'BYN', 'BYN', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(26, 'BYR', 'BYR', 'BYR', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(27, 'BZD', 'BZD', 'BZD', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(28, 'CAD', 'CAD', 'CAD', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(29, 'CDF', 'CDF', 'CDF', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(30, 'CHE', 'CHE', 'CHE', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(31, 'CHF', 'CHF', 'CHF', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(32, 'CHW', 'CHW', 'CHW', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(33, 'CLF', 'CLF', 'CLF', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(34, 'CLP', 'CLP', 'CLP', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(35, 'CNY', 'CNY', 'CNY', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(36, 'COP', 'COP', 'COP', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(37, 'COU', 'COU', 'COU', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(38, 'CRC', 'CRC', 'CRC', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(39, 'CUC', 'CUC', 'CUC', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(40, 'CUP', 'CUP', 'CUP', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(41, 'CVE', 'CVE', 'CVE', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(42, 'CZK', 'CZK', 'CZK', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(43, 'DJF', 'DJF', 'DJF', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(44, 'DKK', 'DKK', 'DKK', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(45, 'DOP', 'DOP', 'DOP', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(46, 'DZD', 'DZD', 'DZD', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(47, 'EGP', 'EGP', 'EGP', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(48, 'ERN', 'ERN', 'ERN', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(49, 'ETB', 'ETB', 'ETB', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(50, 'EUR', 'EUR', '€', '1', 0, '2022-12-30 06:20:25', '2025-08-25 08:58:03'),
(51, 'FJD', 'FJD', 'FJD', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(52, 'FKP', 'FKP', 'FKP', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(53, 'GBP', 'GBP', '£', '1', 0, '2022-12-30 06:20:29', '2025-08-25 08:58:03'),
(54, 'GEL', 'GEL', 'GEL', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(55, 'GHS', 'GHS', 'GHS', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(56, 'GIP', 'GIP', 'GIP', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(57, 'GMD', 'GMD', 'GMD', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(58, 'GNF', 'GNF', 'GNF', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(59, 'GTQ', 'GTQ', 'GTQ', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(60, 'GYD', 'GYD', 'GYD', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(61, 'HKD', 'HKD', 'HKD', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(62, 'HNL', 'HNL', 'HNL', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(63, 'HRK', 'HRK', 'HRK', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(64, 'HTG', 'HTG', 'HTG', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(65, 'HUF', 'HUF', 'HUF', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(66, 'IDR', 'IDR', 'IDR', '1', 0, '2022-12-30 06:20:34', '2025-08-25 08:58:03'),
(67, 'ILS', 'ILS', 'ILS', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(68, 'INR', 'INR', '₹', '1', 0, '2022-12-30 06:20:37', '2025-08-25 08:58:03'),
(69, 'IQD', 'IQD', 'IQD', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(70, 'IRR', 'IRR', 'IRR', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(71, 'ISK', 'ISK', 'ISK', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(72, 'JMD', 'JMD', 'JMD', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(73, 'JOD', 'JOD', 'JOD', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(74, 'JPY', 'JPY', 'JPY', '1', 0, '2022-12-30 06:19:56', '2025-08-25 08:58:03'),
(75, 'KES', 'KES', 'KES', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(76, 'KGS', 'KGS', 'KGS', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(77, 'KHR', 'KHR', 'KHR', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(78, 'KMF', 'KMF', 'KMF', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(79, 'KPW', 'KPW', 'KPW', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(80, 'KRW', 'KRW', 'KRW', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(81, 'KWD', 'KWD', 'KWD', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(82, 'KYD', 'KYD', 'KYD', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(83, 'KZT', 'KZT', 'KZT', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(84, 'LAK', 'LAK', 'LAK', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(85, 'LBP', 'LBP', 'LBP', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(86, 'LKR', 'LKR', 'LKR', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(87, 'LRD', 'LRD', 'LRD', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(88, 'LSL', 'LSL', 'LSL', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(89, 'LYD', 'LYD', 'LYD', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(90, 'MAD', 'MAD', 'MAD', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(91, 'MDL', 'MDL', 'MDL', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(92, 'MGA', 'MGA', 'MGA', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(93, 'MKD', 'MKD', 'MKD', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(94, 'MMK', 'MMK', 'MMK', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(95, 'MNT', 'MNT', 'MNT', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(96, 'MOP', 'MOP', 'MOP', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(97, 'MRO', 'MRO', 'MRO', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(98, 'MUR', 'MUR', 'MUR', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(99, 'MVR', 'MVR', 'MVR', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(100, 'MWK', 'MWK', 'MWK', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(101, 'MXN', 'MXN', 'MXN', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(102, 'MXV', 'MXV', 'MXV', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(103, 'MYR', 'MYR', 'MYR', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(104, 'MZN', 'MZN', 'MZN', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(105, 'NAD', 'NAD', 'NAD', '1', 0, '2022-07-30 09:32:37', '2025-08-25 08:58:03'),
(106, 'NGN', 'NGN', 'NGN', '1', 0, '2022-12-30 06:20:42', '2025-08-25 08:58:03'),
(107, 'NIO', 'NIO', 'NIO', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(108, 'NOK', 'NOK', 'NOK', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(109, 'NPR', 'NPR', 'NPR', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(110, 'NZD', 'NZD', 'NZD', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(111, 'OMR', 'OMR', 'OMR', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(112, 'PAB', 'PAB', 'PAB', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(113, 'PEN', 'PEN', 'PEN', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(114, 'PGK', 'PGK', 'PGK', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(115, 'PHP', 'PHP', 'PHP', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(116, 'PKR', 'PKR', 'PKR', '1', 0, '2022-12-30 06:20:19', '2025-08-25 08:58:03'),
(117, 'PLN', 'PLN', 'PLN', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(118, 'PYG', 'PYG', 'PYG', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(119, 'QAR', 'QAR', 'QAR', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(120, 'RON', 'RON', 'RON', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(121, 'RSD', 'RSD', 'RSD', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(122, 'RUB', 'RUB', 'RUB', '1', 0, '2022-12-30 06:20:16', '2025-08-25 08:58:03'),
(123, 'RWF', 'RWF', 'RWF', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(124, 'SAR', 'SAR', 'SAR', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(125, 'SBD', 'SBD', 'SBD', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(126, 'SCR', 'SCR', 'SCR', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(127, 'SDG', 'SDG', 'SDG', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(128, 'SEK', 'SEK', 'SEK', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(129, 'SGD', 'SGD', 'SGD', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(130, 'SHP', 'SHP', 'SHP', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(131, 'SLL', 'SLL', 'SLL', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(132, 'SOS', 'SOS', 'SOS', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(133, 'SRD', 'SRD', 'SRD', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(134, 'SSP', 'SSP', 'SSP', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(135, 'STD', 'STD', 'STD', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(136, 'SVC', 'SVC', 'SVC', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(137, 'SYP', 'SYP', 'SYP', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(138, 'SZL', 'SZL', 'SZL', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(139, 'THB', 'THB', 'THB', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(140, 'TJS', 'TJS', 'TJS', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(141, 'TMT', 'TMT', 'TMT', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(142, 'TND', 'TND', 'TND', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(143, 'TOP', 'TOP', 'TOP', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(144, 'TRY', 'TRY', 'TRY', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(145, 'TTD', 'TTD', 'TTD', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(146, 'TWD', 'TWD', 'TWD', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(147, 'TZS', 'TZS', 'TZS', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(148, 'UAH', 'UAH', 'UAH', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(149, 'UGX', 'UGX', 'UGX', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(150, 'USD', 'USD', '$', '1', 1, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(151, 'USN', 'USN', 'USN', '1', 0, '2022-12-30 06:20:03', '2025-08-25 08:58:03'),
(152, 'UYI', 'UYI', 'UYI', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(153, 'UYU', 'UYU', 'UYU', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(154, 'UZS', 'UZS', 'UZS', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(155, 'VEF', 'VEF', 'VEF', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(156, 'VND', 'VND', 'VND', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(157, 'VUV', 'VUV', 'VUV', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(158, 'WST', 'WST', 'WST', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(159, 'XAF', 'XAF', 'XAF', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(160, 'XAG', 'XAG', 'XAG', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(161, 'XAU', 'XAU', 'XAU', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(162, 'XBA', 'XBA', 'XBA', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(163, 'XBB', 'XBB', 'XBB', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(164, 'XBC', 'XBC', 'XBC', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(165, 'XBD', 'XBD', 'XBD', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(166, 'XCD', 'XCD', 'XCD', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(167, 'XDR', 'XDR', 'XDR', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(168, 'XOF', 'XOF', 'XOF', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(169, 'XPD', 'XPD', 'XPD', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(170, 'XPF', 'XPF', 'XPF', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(171, 'XPT', 'XPT', 'XPT', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(172, 'XSU', 'XSU', 'XSU', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(173, 'XTS', 'XTS', 'XTS', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(174, 'XUA', 'XUA', 'XUA', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(175, 'XXX', 'XXX', 'XXX', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(176, 'YER', 'YER', 'YER', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03'),
(177, 'ZAR', 'ZAR', 'ZAR', '1', 0, '2022-12-30 06:20:07', '2025-08-25 08:58:03'),
(178, 'ZMW', 'ZMW', 'ZMW', '1', 0, '2022-07-30 07:34:00', '2025-08-25 08:58:03'),
(179, 'ZWL', 'ZWL', 'ZWL', '1', 0, '2022-07-22 10:55:15', '2025-08-25 08:58:03');

-- --------------------------------------------------------

--
-- Table structure for table `custom_fields`
--

DROP TABLE IF EXISTS `custom_fields`;
CREATE TABLE IF NOT EXISTS `custom_fields` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `belong_to` varchar(100) DEFAULT NULL,
  `type` varchar(100) DEFAULT NULL,
  `bs_column` int DEFAULT NULL,
  `validation` int DEFAULT '0',
  `field_values` text,
  `show_table` varchar(100) DEFAULT NULL,
  `visible_on_table` int NOT NULL,
  `weight` int DEFAULT NULL,
  `is_active` int DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_name` (`name`),
  KEY `idx_belong_to` (`belong_to`),
  KEY `idx_type` (`type`),
  KEY `idx_visible_on_table` (`visible_on_table`),
  KEY `idx_weight` (`weight`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `custom_field_values`
--

DROP TABLE IF EXISTS `custom_field_values`;
CREATE TABLE IF NOT EXISTS `custom_field_values` (
  `id` int NOT NULL AUTO_INCREMENT,
  `belong_table_id` int DEFAULT NULL,
  `custom_field_id` int DEFAULT NULL,
  `field_value` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `custom_field_id` (`custom_field_id`),
  KEY `idx_belong_table_id` (`belong_table_id`),
  KEY `idx_field_value` (`field_value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `daily_assignment`
--

DROP TABLE IF EXISTS `daily_assignment`;
CREATE TABLE IF NOT EXISTS `daily_assignment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_session_id` int NOT NULL,
  `subject_group_subject_id` int NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text,
  `attachment` varchar(255) DEFAULT NULL,
  `evaluated_by` int DEFAULT NULL,
  `date` date DEFAULT NULL,
  `evaluation_date` date DEFAULT NULL,
  `remark` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_session_id` (`student_session_id`),
  KEY `evaluated_by` (`evaluated_by`),
  KEY `subject_group_subject_id` (`subject_group_subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `department`
--

DROP TABLE IF EXISTS `department`;
CREATE TABLE IF NOT EXISTS `department` (
  `id` int NOT NULL AUTO_INCREMENT,
  `department_name` varchar(200) NOT NULL,
  `is_active` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `disable_reason`
--

DROP TABLE IF EXISTS `disable_reason`;
CREATE TABLE IF NOT EXISTS `disable_reason` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reason` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `dispatch_receive`
--

DROP TABLE IF EXISTS `dispatch_receive`;
CREATE TABLE IF NOT EXISTS `dispatch_receive` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reference_no` varchar(50) NOT NULL,
  `to_title` varchar(100) NOT NULL,
  `type` varchar(10) NOT NULL,
  `address` varchar(500) NOT NULL,
  `note` varchar(500) NOT NULL,
  `from_title` varchar(200) NOT NULL,
  `date` date DEFAULT NULL,
  `image` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `email_attachments`
--

DROP TABLE IF EXISTS `email_attachments`;
CREATE TABLE IF NOT EXISTS `email_attachments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `message_id` int NOT NULL,
  `directory` varchar(255) NOT NULL,
  `attachment` varchar(255) NOT NULL,
  `attachment_name` varchar(200) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `message_id` (`message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `email_config`
--

DROP TABLE IF EXISTS `email_config`;
CREATE TABLE IF NOT EXISTS `email_config` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `email_type` varchar(100) DEFAULT NULL,
  `smtp_server` varchar(100) DEFAULT NULL,
  `smtp_port` varchar(100) DEFAULT NULL,
  `smtp_email` varchar(255) DEFAULT NULL,
  `smtp_username` varchar(100) DEFAULT NULL,
  `smtp_password` varchar(100) DEFAULT NULL,
  `ssl_tls` varchar(100) DEFAULT NULL,
  `smtp_auth` varchar(10) NOT NULL,
  `api_key` varchar(255) DEFAULT NULL,
  `api_secret` varchar(255) DEFAULT NULL,
  `region` varchar(255) DEFAULT NULL,
  `is_active` varchar(10) NOT NULL DEFAULT 'no',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `email_config`
--

INSERT INTO `email_config` (`id`, `email_type`, `smtp_server`, `smtp_port`, `smtp_email`, `smtp_username`, `smtp_password`, `ssl_tls`, `smtp_auth`, `api_key`, `api_secret`, `region`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'sendmail', NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, NULL, NULL, 'yes', '2020-02-28 13:46:03', '2025-08-25 08:58:03');

-- --------------------------------------------------------

--
-- Table structure for table `email_template`
--

DROP TABLE IF EXISTS `email_template`;
CREATE TABLE IF NOT EXISTS `email_template` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `created_at` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `email_template_attachment`
--

DROP TABLE IF EXISTS `email_template_attachment`;
CREATE TABLE IF NOT EXISTS `email_template_attachment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email_template_id` int NOT NULL,
  `attachment` varchar(100) NOT NULL,
  `attachment_name` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `enquiry`
--

DROP TABLE IF EXISTS `enquiry`;
CREATE TABLE IF NOT EXISTS `enquiry` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `contact` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `reference` varchar(20) NOT NULL,
  `date` date NOT NULL,
  `description` varchar(500) NOT NULL,
  `follow_up_date` date NOT NULL,
  `note` text NOT NULL,
  `source` varchar(50) NOT NULL,
  `email` varchar(50) DEFAULT NULL,
  `assigned` int DEFAULT NULL,
  `class_id` int DEFAULT NULL,
  `no_of_child` varchar(11) DEFAULT NULL,
  `status` varchar(100) NOT NULL,
  `created_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `assigned` (`assigned`),
  KEY `enquiry_ibfk_4` (`class_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `enquiry_type`
--

DROP TABLE IF EXISTS `enquiry_type`;
CREATE TABLE IF NOT EXISTS `enquiry_type` (
  `id` int NOT NULL AUTO_INCREMENT,
  `enquiry_type` varchar(100) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
CREATE TABLE IF NOT EXISTS `events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_title` varchar(200) NOT NULL,
  `event_description` text NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `event_type` varchar(100) NOT NULL,
  `event_color` varchar(200) NOT NULL,
  `event_for` varchar(100) NOT NULL,
  `role_id` int DEFAULT NULL,
  `is_active` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--

DROP TABLE IF EXISTS `exams`;
CREATE TABLE IF NOT EXISTS `exams` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(200) DEFAULT NULL,
  `sesion_id` int NOT NULL,
  `note` text,
  `is_active` varchar(255) DEFAULT 'no',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `sesion_id` (`sesion_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `exam_groups`
--

DROP TABLE IF EXISTS `exam_groups`;
CREATE TABLE IF NOT EXISTS `exam_groups` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(250) DEFAULT NULL,
  `exam_type` varchar(250) DEFAULT NULL,
  `description` text,
  `is_active` int DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `exam_group_class_batch_exams`
--

DROP TABLE IF EXISTS `exam_group_class_batch_exams`;
CREATE TABLE IF NOT EXISTS `exam_group_class_batch_exams` (
  `id` int NOT NULL AUTO_INCREMENT,
  `exam` varchar(250) DEFAULT NULL,
  `passing_percentage` float(10,2) DEFAULT NULL,
  `session_id` int NOT NULL,
  `date_from` date DEFAULT NULL,
  `date_to` date DEFAULT NULL,
  `exam_group_id` int DEFAULT NULL,
  `use_exam_roll_no` int NOT NULL DEFAULT '1',
  `is_publish` int DEFAULT '0',
  `is_rank_generated` int NOT NULL DEFAULT '0',
  `description` text,
  `is_active` int DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `exam_group_id` (`exam_group_id`),
  KEY `exam_group_class_batch_exams_ibfk_2` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `exam_group_class_batch_exam_students`
--

DROP TABLE IF EXISTS `exam_group_class_batch_exam_students`;
CREATE TABLE IF NOT EXISTS `exam_group_class_batch_exam_students` (
  `id` int NOT NULL AUTO_INCREMENT,
  `exam_group_class_batch_exam_id` int NOT NULL,
  `student_id` int NOT NULL,
  `student_session_id` int NOT NULL,
  `roll_no` int DEFAULT NULL,
  `teacher_remark` text,
  `rank` int NOT NULL DEFAULT '0',
  `is_active` int DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `exam_group_class_batch_exam_id` (`exam_group_class_batch_exam_id`),
  KEY `student_id` (`student_id`),
  KEY `student_session_id` (`student_session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `exam_group_class_batch_exam_subjects`
--

DROP TABLE IF EXISTS `exam_group_class_batch_exam_subjects`;
CREATE TABLE IF NOT EXISTS `exam_group_class_batch_exam_subjects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `exam_group_class_batch_exams_id` int DEFAULT NULL,
  `subject_id` int NOT NULL,
  `date_from` date NOT NULL,
  `time_from` time NOT NULL,
  `duration` varchar(50) NOT NULL,
  `room_no` varchar(100) DEFAULT NULL,
  `max_marks` float(10,2) DEFAULT NULL,
  `min_marks` float(10,2) DEFAULT NULL,
  `credit_hours` float(10,2) DEFAULT '0.00',
  `date_to` datetime DEFAULT NULL,
  `is_active` int DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `exam_group_class_batch_exams_id` (`exam_group_class_batch_exams_id`),
  KEY `subject_id` (`subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `exam_group_exam_connections`
--

DROP TABLE IF EXISTS `exam_group_exam_connections`;
CREATE TABLE IF NOT EXISTS `exam_group_exam_connections` (
  `id` int NOT NULL AUTO_INCREMENT,
  `exam_group_id` int DEFAULT NULL,
  `exam_group_class_batch_exams_id` int DEFAULT NULL,
  `exam_weightage` float(10,2) DEFAULT '0.00',
  `is_active` int DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `exam_group_id` (`exam_group_id`),
  KEY `exam_group_class_batch_exams_id` (`exam_group_class_batch_exams_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `exam_group_exam_results`
--

DROP TABLE IF EXISTS `exam_group_exam_results`;
CREATE TABLE IF NOT EXISTS `exam_group_exam_results` (
  `id` int NOT NULL AUTO_INCREMENT,
  `exam_group_class_batch_exam_student_id` int NOT NULL,
  `exam_group_class_batch_exam_subject_id` int DEFAULT NULL,
  `exam_group_student_id` int DEFAULT NULL,
  `attendence` varchar(10) DEFAULT NULL,
  `get_marks` float(10,2) DEFAULT '0.00',
  `note` text,
  `is_active` int DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `exam_group_class_batch_exam_subject_id` (`exam_group_class_batch_exam_subject_id`),
  KEY `exam_group_student_id` (`exam_group_student_id`),
  KEY `exam_group_class_batch_exam_student_id` (`exam_group_class_batch_exam_student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `exam_group_students`
--

DROP TABLE IF EXISTS `exam_group_students`;
CREATE TABLE IF NOT EXISTS `exam_group_students` (
  `id` int NOT NULL AUTO_INCREMENT,
  `exam_group_id` int DEFAULT NULL,
  `student_id` int DEFAULT NULL,
  `student_session_id` int DEFAULT NULL,
  `is_active` int DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `exam_group_id` (`exam_group_id`),
  KEY `student_id` (`student_id`),
  KEY `student_session_id` (`student_session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `exam_schedules`
--

DROP TABLE IF EXISTS `exam_schedules`;
CREATE TABLE IF NOT EXISTS `exam_schedules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `session_id` int NOT NULL,
  `exam_id` int DEFAULT NULL,
  `teacher_subject_id` int DEFAULT NULL,
  `date_of_exam` date DEFAULT NULL,
  `start_to` varchar(50) DEFAULT NULL,
  `end_from` varchar(50) DEFAULT NULL,
  `room_no` varchar(50) DEFAULT NULL,
  `full_marks` int DEFAULT NULL,
  `passing_marks` int DEFAULT NULL,
  `note` text,
  `is_active` varchar(255) DEFAULT 'no',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `teacher_subject_id` (`teacher_subject_id`),
  KEY `session_id` (`session_id`),
  KEY `exam_id` (`exam_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

DROP TABLE IF EXISTS `expenses`;
CREATE TABLE IF NOT EXISTS `expenses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `exp_head_id` int DEFAULT NULL,
  `name` varchar(50) DEFAULT NULL,
  `invoice_no` varchar(200) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `amount` float(10,2) DEFAULT NULL,
  `documents` varchar(255) DEFAULT NULL,
  `note` text,
  `is_active` varchar(255) DEFAULT 'yes',
  `is_deleted` varchar(255) DEFAULT 'no',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `exp_head_id` (`exp_head_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `expense_head`
--

DROP TABLE IF EXISTS `expense_head`;
CREATE TABLE IF NOT EXISTS `expense_head` (
  `id` int NOT NULL AUTO_INCREMENT,
  `exp_category` varchar(50) DEFAULT NULL,
  `description` text,
  `is_active` varchar(255) DEFAULT 'yes',
  `is_deleted` varchar(255) DEFAULT 'no',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `feemasters`
--

DROP TABLE IF EXISTS `feemasters`;
CREATE TABLE IF NOT EXISTS `feemasters` (
  `id` int NOT NULL AUTO_INCREMENT,
  `session_id` int DEFAULT NULL,
  `feetype_id` int NOT NULL,
  `class_id` int DEFAULT NULL,
  `amount` float(10,2) DEFAULT NULL,
  `description` text,
  `is_active` varchar(255) DEFAULT 'no',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`),
  KEY `feetype_id` (`feetype_id`),
  KEY `class_id` (`class_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `fees_discounts`
--

DROP TABLE IF EXISTS `fees_discounts`;
CREATE TABLE IF NOT EXISTS `fees_discounts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `session_id` int DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `code` varchar(100) DEFAULT NULL,
  `type` varchar(20) DEFAULT NULL,
  `percentage` float(10,2) DEFAULT NULL,
  `amount` float(10,2) DEFAULT NULL,
  `discount_limit` int DEFAULT NULL,
  `expire_date` date DEFAULT NULL,
  `description` text,
  `is_active` varchar(10) NOT NULL DEFAULT 'no',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `fees_reminder`
--

DROP TABLE IF EXISTS `fees_reminder`;
CREATE TABLE IF NOT EXISTS `fees_reminder` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reminder_type` varchar(10) DEFAULT NULL,
  `day` int DEFAULT NULL,
  `is_active` int DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `fees_reminder`
--

INSERT INTO `fees_reminder` (`id`, `reminder_type`, `day`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'before', 2, 0, '2020-02-28 13:38:32', NULL),
(2, 'before', 5, 0, '2020-02-28 13:38:36', NULL),
(3, 'after', 2, 0, '2020-02-28 13:38:40', NULL),
(4, 'after', 5, 0, '2020-02-28 13:38:44', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `feetype`
--

DROP TABLE IF EXISTS `feetype`;
CREATE TABLE IF NOT EXISTS `feetype` (
  `id` int NOT NULL AUTO_INCREMENT,
  `is_system` int NOT NULL DEFAULT '0',
  `feecategory_id` int DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `code` varchar(100) NOT NULL,
  `is_active` varchar(255) DEFAULT 'no',
  `description` text,
  `session_id` int DEFAULT NULL,
  `student_session_id` int DEFAULT NULL,
  `nature` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `fee_groups`
--

DROP TABLE IF EXISTS `fee_groups`;
CREATE TABLE IF NOT EXISTS `fee_groups` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(200) DEFAULT NULL,
  `is_system` int NOT NULL DEFAULT '0',
  `description` text,
  `nature` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `is_active` varchar(10) NOT NULL DEFAULT 'no',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `fee_groups_feetype`
--

DROP TABLE IF EXISTS `fee_groups_feetype`;
CREATE TABLE IF NOT EXISTS `fee_groups_feetype` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fee_session_group_id` int DEFAULT NULL,
  `fee_groups_id` int DEFAULT NULL,
  `feetype_id` int DEFAULT NULL,
  `session_id` int DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `fine_type` varchar(50) NOT NULL DEFAULT 'none',
  `due_date` date DEFAULT NULL,
  `fine_percentage` float(10,2) NOT NULL DEFAULT '0.00',
  `fine_amount` float(10,2) NOT NULL DEFAULT '0.00',
  `fine_per_day` int NOT NULL DEFAULT '0',
  `is_active` varchar(10) NOT NULL DEFAULT 'no',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fee_session_group_id` (`fee_session_group_id`),
  KEY `fee_groups_id` (`fee_groups_id`),
  KEY `feetype_id` (`feetype_id`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `fee_receipt_no`
--

DROP TABLE IF EXISTS `fee_receipt_no`;
CREATE TABLE IF NOT EXISTS `fee_receipt_no` (
  `id` int NOT NULL AUTO_INCREMENT,
  `payment` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `fee_session_groups`
--

DROP TABLE IF EXISTS `fee_session_groups`;
CREATE TABLE IF NOT EXISTS `fee_session_groups` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fee_groups_id` int DEFAULT NULL,
  `session_id` int DEFAULT NULL,
  `is_active` varchar(10) NOT NULL DEFAULT 'no',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fee_groups_id` (`fee_groups_id`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `filetypes`
--

DROP TABLE IF EXISTS `filetypes`;
CREATE TABLE IF NOT EXISTS `filetypes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `file_extension` text,
  `file_mime` text,
  `file_size` int NOT NULL,
  `image_extension` text,
  `image_mime` text,
  `image_size` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `filetypes`
--

INSERT INTO `filetypes` (`id`, `file_extension`, `file_mime`, `file_size`, `image_extension`, `image_mime`, `image_size`, `created_at`, `updated_at`) VALUES
(1, 'pdf, zip, jpg, jpeg, png, txt, 7z, gif, csv, docx, mp3, mp4, accdb, odt, ods, ppt, pptx, xlsx, wmv, jfif, apk, ppt, bmp, jpe, mdb, rar, xls, svg', 'application/pdf, image/zip, image/jpg, image/png, image/jpeg, text/plain, application/x-zip-compressed, application/zip, image/gif, text/csv, application/vnd.openxmlformats-officedocument.wordprocessingml.document, audio/mpeg, application/msaccess, application/vnd.oasis.opendocument.text, application/vnd.oasis.opendocument.spreadsheet, application/vnd.ms-powerpoint, application/vnd.openxmlformats-officedocument.presentationml.presentation, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, video/x-ms-wmv, video/mp4, image/jpeg, application/vnd.android.package-archive, application/x-msdownload, application/vnd.ms-powerpoint, image/bmp, image/jpeg, application/msaccess, application/vnd.ms-excel, image/svg+xml', 100048576, 'jfif, png, jpe, jpeg, jpg, bmp, gif, svg', 'image/jpeg, image/png, image/jpeg, image/jpeg, image/bmp, image/gif, image/x-ms-bmp, image/svg+xml', 10048576, '2021-01-30 13:03:03', '2025-08-25 08:58:04');

-- --------------------------------------------------------

--
-- Table structure for table `follow_up`
--

DROP TABLE IF EXISTS `follow_up`;
CREATE TABLE IF NOT EXISTS `follow_up` (
  `id` int NOT NULL AUTO_INCREMENT,
  `enquiry_id` int NOT NULL,
  `date` date NOT NULL,
  `next_date` date NOT NULL,
  `response` text NOT NULL,
  `note` text NOT NULL,
  `followup_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `enquiry_id` (`enquiry_id`),
  KEY `followup_by` (`followup_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `front_cms_media_gallery`
--

DROP TABLE IF EXISTS `front_cms_media_gallery`;
CREATE TABLE IF NOT EXISTS `front_cms_media_gallery` (
  `id` int NOT NULL AUTO_INCREMENT,
  `image` varchar(300) DEFAULT NULL,
  `thumb_path` varchar(300) DEFAULT NULL,
  `dir_path` varchar(300) DEFAULT NULL,
  `img_name` varchar(300) DEFAULT NULL,
  `thumb_name` varchar(300) DEFAULT NULL,
  `file_type` varchar(100) NOT NULL,
  `file_size` varchar(100) NOT NULL,
  `vid_url` text NOT NULL,
  `vid_title` varchar(250) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `front_cms_menus`
--

DROP TABLE IF EXISTS `front_cms_menus`;
CREATE TABLE IF NOT EXISTS `front_cms_menus` (
  `id` int NOT NULL AUTO_INCREMENT,
  `menu` varchar(100) DEFAULT NULL,
  `slug` varchar(200) DEFAULT NULL,
  `description` text,
  `open_new_tab` int NOT NULL DEFAULT '0',
  `ext_url` text NOT NULL,
  `ext_url_link` text NOT NULL,
  `publish` int NOT NULL DEFAULT '0',
  `content_type` varchar(10) NOT NULL DEFAULT 'manual',
  `is_active` varchar(10) DEFAULT 'no',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `front_cms_menus`
--

INSERT INTO `front_cms_menus` (`id`, `menu`, `slug`, `description`, `open_new_tab`, `ext_url`, `ext_url_link`, `publish`, `content_type`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Main Menu', 'main-menu', 'Main menu', 0, '', '', 0, 'default', 'no', '2018-04-20 14:54:49', '2025-08-25 08:58:04'),
(2, 'Bottom Menu', 'bottom-menu', 'Bottom Menu', 0, '', '', 0, 'default', 'no', '2018-04-20 14:54:55', '2025-08-25 08:58:04');

-- --------------------------------------------------------

--
-- Table structure for table `front_cms_menu_items`
--

DROP TABLE IF EXISTS `front_cms_menu_items`;
CREATE TABLE IF NOT EXISTS `front_cms_menu_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `menu_id` int NOT NULL,
  `menu` varchar(100) DEFAULT NULL,
  `page_id` int NOT NULL,
  `parent_id` int NOT NULL,
  `ext_url` text,
  `open_new_tab` int DEFAULT '0',
  `ext_url_link` text,
  `slug` varchar(200) DEFAULT NULL,
  `weight` int DEFAULT NULL,
  `publish` int NOT NULL DEFAULT '0',
  `description` text,
  `is_active` varchar(10) DEFAULT 'no',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `menu_id` (`menu_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `front_cms_menu_items`
--

INSERT INTO `front_cms_menu_items` (`id`, `menu_id`, `menu`, `page_id`, `parent_id`, `ext_url`, `open_new_tab`, `ext_url_link`, `slug`, `weight`, `publish`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'Home', 1, 0, NULL, NULL, NULL, 'home', 1, 0, NULL, 'no', '2019-12-02 22:11:50', '2025-08-25 08:58:04'),
(2, 1, 'Contact Us', 76, 0, NULL, NULL, NULL, 'contact-us', 4, 0, NULL, 'no', '2019-12-02 22:11:52', '2025-08-25 08:58:04'),
(3, 1, 'Complain', 2, 0, NULL, NULL, NULL, 'complain', 3, 0, NULL, 'no', '2019-12-02 22:11:52', '2025-08-25 08:58:04'),
(4, 1, 'Online Admission', 0, 0, '1', NULL, 'http://yourschoolurl.com/online_admission', 'admssion', 2, 0, NULL, 'no', '2019-12-21 15:33:00', '2025-08-25 08:58:04');

-- --------------------------------------------------------

--
-- Table structure for table `front_cms_pages`
--

DROP TABLE IF EXISTS `front_cms_pages`;
CREATE TABLE IF NOT EXISTS `front_cms_pages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `page_type` varchar(10) NOT NULL DEFAULT 'manual',
  `is_homepage` int DEFAULT '0',
  `title` varchar(250) DEFAULT NULL,
  `url` varchar(250) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `slug` varchar(200) DEFAULT NULL,
  `meta_title` text,
  `meta_description` text,
  `meta_keyword` text,
  `feature_image` varchar(200) NOT NULL,
  `description` longtext,
  `publish_date` date DEFAULT NULL,
  `publish` int DEFAULT '0',
  `sidebar` int DEFAULT '0',
  `is_active` varchar(10) DEFAULT 'no',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `front_cms_pages`
--

INSERT INTO `front_cms_pages` (`id`, `page_type`, `is_homepage`, `title`, `url`, `type`, `slug`, `meta_title`, `meta_description`, `meta_keyword`, `feature_image`, `description`, `publish_date`, `publish`, `sidebar`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'default', 1, 'Home', 'page/home', 'page', 'home', '', '', '', '', '<p>home page</p>', '0000-00-00', 1, NULL, 'no', '2019-12-02 15:23:47', '2025-08-25 08:58:04'),
(2, 'default', 0, 'Complain', 'page/complain', 'page', 'complain', 'Complain form', '                                                                                                                                                                                    complain form                                                                                                                                                                                                                                ', 'complain form', '', '<p>[form-builder:complain]</p>', '0000-00-00', 1, NULL, 'no', '2019-11-13 10:16:36', '2025-08-25 08:58:04'),
(3, 'default', 0, '404 page', 'page/404-page', 'page', '404-page', '', '                                ', '', '', '<html>\r\n<head>\r\n <title></title>\r\n</head>\r\n<body>\r\n<p>404 page found</p>\r\n</body>\r\n</html>', '0000-00-00', 0, NULL, 'no', '2018-05-18 14:46:04', '2025-08-25 08:58:04'),
(4, 'default', 0, 'Contact us', 'page/contact-us', 'page', 'contact-us', '', '', '', '', '<section class=\"contact\">\r\n<div class=\"container\">\r\n<div class=\"row\">\r\n<h2 class=\"col-md-12 col-sm-12\">Send In Your Query</h2>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<div class=\"col-md-12 col-sm-12\">[form-builder:contact_us]<!--./row--></div>\r\n<!--./col-md-12--></div>\r\n<!--./row--></div>\r\n<!--./container--></section>\r\n\r\n<div class=\"col-md-4 col-sm-4\">\r\n<div class=\"contact-item\"><img src=\"http://192.168.1.81/repos/smartschool/uploads/gallery/media/pin.svg\" />\r\n<h3>Our Location</h3>\r\n\r\n<p>350 Fifth Avenue, 34th floor New York NY 10118-3299 USA</p>\r\n</div>\r\n<!--./contact-item--></div>\r\n<!--./col-md-4-->\r\n\r\n<div class=\"col-md-4 col-sm-4\">\r\n<div class=\"contact-item\"><img src=\"http://192.168.1.81/repos/smartschool/uploads/gallery/media/phone.svg\" />\r\n<h3>CALL US</h3>\r\n\r\n<p>E-mail : info@abcschool.com</p>\r\n\r\n<p>Mobile : +91-9009987654</p>\r\n</div>\r\n<!--./contact-item--></div>\r\n<!--./col-md-4-->\r\n\r\n<div class=\"col-md-4 col-sm-4\">\r\n<div class=\"contact-item\"><img src=\"http://192.168.1.81/repos/smartschool/uploads/gallery/media/clock.svg\" />\r\n<h3>Working Hours</h3>\r\n\r\n<p>Mon-Fri : 9 am to 5 pm</p>\r\n\r\n<p>Sat : 9 am to 3 pm</p>\r\n</div>\r\n<!--./contact-item--></div>\r\n<!--./col-md-4-->\r\n\r\n<div class=\"col-md-12 col-sm-12\">\r\n<div class=\"mapWrapper fullwidth\"><iframe frameborder=\"0\" height=\"500\" marginheight=\"0\" marginwidth=\"0\" scrolling=\"no\" src=\"http://maps.google.com/maps?f=q&source=s_q&hl=EN&q=time+square&aq=&sll=40.716558,-73.931122&sspn=0.40438,1.056747&ie=UTF8&rq=1&ev=p&split=1&radius=33.22&hq=time+square&hnear=&ll=37.061753,-95.677185&spn=0.438347,0.769043&z=9&output=embed\" width=\"100%\"></iframe></div>\r\n</div>', '0000-00-00', 0, NULL, 'no', '2019-05-04 15:46:41', '2025-08-25 08:58:04');

-- --------------------------------------------------------

--
-- Table structure for table `front_cms_page_contents`
--

DROP TABLE IF EXISTS `front_cms_page_contents`;
CREATE TABLE IF NOT EXISTS `front_cms_page_contents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `page_id` int DEFAULT NULL,
  `content_type` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `page_id` (`page_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `front_cms_programs`
--

DROP TABLE IF EXISTS `front_cms_programs`;
CREATE TABLE IF NOT EXISTS `front_cms_programs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type` varchar(50) DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `url` text,
  `title` varchar(200) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `event_start` date DEFAULT NULL,
  `event_end` date DEFAULT NULL,
  `event_venue` text,
  `description` text,
  `is_active` varchar(10) DEFAULT 'no',
  `meta_title` text NOT NULL,
  `meta_description` text NOT NULL,
  `meta_keyword` text NOT NULL,
  `feature_image` text NOT NULL,
  `publish_date` date DEFAULT NULL,
  `publish` varchar(10) DEFAULT '0',
  `sidebar` int DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `front_cms_program_photos`
--

DROP TABLE IF EXISTS `front_cms_program_photos`;
CREATE TABLE IF NOT EXISTS `front_cms_program_photos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `program_id` int DEFAULT NULL,
  `media_gallery_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `program_id` (`program_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `front_cms_settings`
--

DROP TABLE IF EXISTS `front_cms_settings`;
CREATE TABLE IF NOT EXISTS `front_cms_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `theme` varchar(50) DEFAULT NULL,
  `is_active_rtl` int DEFAULT '0',
  `is_active_front_cms` int DEFAULT '0',
  `is_active_sidebar` int DEFAULT '0',
  `logo` varchar(200) DEFAULT NULL,
  `contact_us_email` varchar(100) DEFAULT NULL,
  `complain_form_email` varchar(100) DEFAULT NULL,
  `sidebar_options` text NOT NULL,
  `whatsapp_url` varchar(255) NOT NULL,
  `fb_url` varchar(200) NOT NULL,
  `twitter_url` varchar(200) NOT NULL,
  `youtube_url` varchar(200) NOT NULL,
  `google_plus` varchar(200) NOT NULL,
  `instagram_url` varchar(200) NOT NULL,
  `pinterest_url` varchar(200) NOT NULL,
  `linkedin_url` varchar(200) NOT NULL,
  `google_analytics` text,
  `footer_text` varchar(500) DEFAULT NULL,
  `cookie_consent` varchar(255) NOT NULL,
  `fav_icon` varchar(250) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `front_cms_settings`
--

INSERT INTO `front_cms_settings` (`id`, `theme`, `is_active_rtl`, `is_active_front_cms`, `is_active_sidebar`, `logo`, `contact_us_email`, `complain_form_email`, `sidebar_options`, `whatsapp_url`, `fb_url`, `twitter_url`, `youtube_url`, `google_plus`, `instagram_url`, `pinterest_url`, `linkedin_url`, `google_analytics`, `footer_text`, `cookie_consent`, `fav_icon`, `created_at`, `updated_at`) VALUES
(1, 'material_pink', NULL, NULL, NULL, './uploads/school_content/logo/1672900975-128524590063b6716fc26ed!front_logo-608ff44a5f8f07.35255544.png', '', '', '[]', '', '', '', '', '', '', '', '', '', '', '', './uploads/school_content/logo/1672900975-63781378463b6716fc2886!front_fav_icon-608ff44a5fdb33.94953981.png', '2023-01-05 06:42:55', '2025-08-25 08:58:04');

-- --------------------------------------------------------

--
-- Table structure for table `gateway_ins`
--

DROP TABLE IF EXISTS `gateway_ins`;
CREATE TABLE IF NOT EXISTS `gateway_ins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `online_admission_id` int DEFAULT NULL,
  `gateway_name` varchar(50) NOT NULL,
  `module_type` varchar(255) NOT NULL,
  `unique_id` varchar(255) NOT NULL,
  `parameter_details` mediumtext NOT NULL,
  `payment_status` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `online_admission_id` (`online_admission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `gateway_ins_response`
--

DROP TABLE IF EXISTS `gateway_ins_response`;
CREATE TABLE IF NOT EXISTS `gateway_ins_response` (
  `id` int NOT NULL AUTO_INCREMENT,
  `gateway_ins_id` int DEFAULT NULL,
  `posted_data` text,
  `response` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `gateway_ins_id` (`gateway_ins_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `general_calls`
--

DROP TABLE IF EXISTS `general_calls`;
CREATE TABLE IF NOT EXISTS `general_calls` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `contact` varchar(12) NOT NULL,
  `date` date NOT NULL,
  `description` varchar(500) NOT NULL,
  `follow_up_date` date NOT NULL,
  `call_duration` varchar(50) NOT NULL,
  `note` text NOT NULL,
  `call_type` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

DROP TABLE IF EXISTS `grades`;
CREATE TABLE IF NOT EXISTS `grades` (
  `id` int NOT NULL AUTO_INCREMENT,
  `exam_type` varchar(250) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `point` float(10,1) DEFAULT NULL,
  `mark_from` float(10,2) DEFAULT NULL,
  `mark_upto` float(10,2) DEFAULT NULL,
  `description` text,
  `is_active` varchar(255) DEFAULT 'no',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `holiday_type`
--

DROP TABLE IF EXISTS `holiday_type`;
CREATE TABLE IF NOT EXISTS `holiday_type` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `is_default` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `holiday_type`
--

INSERT INTO `holiday_type` (`id`, `type`, `is_default`) VALUES
(1, 'Holiday', 1),
(2, 'Vacation', 1),
(3, 'Activity', 1);

-- --------------------------------------------------------

--
-- Table structure for table `homework`
--

DROP TABLE IF EXISTS `homework`;
CREATE TABLE IF NOT EXISTS `homework` (
  `id` int NOT NULL AUTO_INCREMENT,
  `class_id` int NOT NULL,
  `section_id` int NOT NULL,
  `session_id` int NOT NULL,
  `staff_id` int NOT NULL,
  `subject_group_subject_id` int DEFAULT NULL,
  `subject_id` int DEFAULT NULL,
  `homework_date` date NOT NULL,
  `submit_date` date NOT NULL,
  `marks` float(10,2) DEFAULT NULL,
  `description` text,
  `create_date` date NOT NULL,
  `evaluation_date` date DEFAULT NULL,
  `document` varchar(200) DEFAULT NULL,
  `created_by` int NOT NULL,
  `evaluated_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `subject_group_subject_id` (`subject_group_subject_id`),
  KEY `class_id` (`class_id`),
  KEY `section_id` (`section_id`),
  KEY `session_id` (`session_id`),
  KEY `staff_id` (`staff_id`),
  KEY `subject_id` (`subject_id`),
  KEY `evaluated_by` (`evaluated_by`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `homework_evaluation`
--

DROP TABLE IF EXISTS `homework_evaluation`;
CREATE TABLE IF NOT EXISTS `homework_evaluation` (
  `id` int NOT NULL AUTO_INCREMENT,
  `homework_id` int NOT NULL,
  `student_id` int NOT NULL,
  `student_session_id` int DEFAULT NULL,
  `marks` float(10,2) DEFAULT NULL,
  `note` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `status` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `homework_id` (`homework_id`),
  KEY `student_id` (`student_id`),
  KEY `student_session_id` (`student_session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `hostel`
--

DROP TABLE IF EXISTS `hostel`;
CREATE TABLE IF NOT EXISTS `hostel` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hostel_name` varchar(100) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `address` text,
  `intake` int DEFAULT NULL,
  `description` text,
  `is_active` varchar(255) DEFAULT 'no',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `hostel_rooms`
--

DROP TABLE IF EXISTS `hostel_rooms`;
CREATE TABLE IF NOT EXISTS `hostel_rooms` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hostel_id` int DEFAULT NULL,
  `room_type_id` int DEFAULT NULL,
  `room_no` varchar(200) DEFAULT NULL,
  `no_of_bed` int DEFAULT NULL,
  `cost_per_bed` float(10,2) DEFAULT '0.00',
  `title` varchar(200) DEFAULT NULL,
  `description` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `hostel_id` (`hostel_id`),
  KEY `room_type_id` (`room_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `id_card`
--

DROP TABLE IF EXISTS `id_card`;
CREATE TABLE IF NOT EXISTS `id_card` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `school_name` varchar(100) NOT NULL,
  `school_address` varchar(500) NOT NULL,
  `background` varchar(100) NOT NULL,
  `logo` varchar(100) NOT NULL,
  `sign_image` varchar(100) NOT NULL,
  `enable_vertical_card` int NOT NULL DEFAULT '0',
  `header_color` varchar(100) NOT NULL,
  `enable_admission_no` tinyint(1) NOT NULL COMMENT '0=disable,1=enable',
  `enable_student_name` tinyint(1) NOT NULL COMMENT '0=disable,1=enable',
  `enable_class` tinyint(1) NOT NULL COMMENT '0=disable,1=enable',
  `enable_fathers_name` tinyint(1) NOT NULL COMMENT '0=disable,1=enable',
  `enable_mothers_name` tinyint(1) NOT NULL COMMENT '0=disable,1=enable',
  `enable_address` tinyint(1) NOT NULL COMMENT '0=disable,1=enable',
  `enable_phone` tinyint(1) NOT NULL COMMENT '0=disable,1=enable',
  `enable_dob` tinyint(1) NOT NULL COMMENT '0=disable,1=enable',
  `enable_blood_group` tinyint(1) NOT NULL COMMENT '0=disable,1=enable',
  `enable_student_barcode` tinyint NOT NULL DEFAULT '1' COMMENT '0=disable,1=enable',
  `enable_student_rollno` tinyint(1) NOT NULL COMMENT '0=disable,1=enable	',
  `enable_student_house_name` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0=disable,1=enable	',
  `status` tinyint(1) NOT NULL COMMENT '0=disable,1=enable',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `id_card`
--

INSERT INTO `id_card` (`id`, `title`, `school_name`, `school_address`, `background`, `logo`, `sign_image`, `enable_vertical_card`, `header_color`, `enable_admission_no`, `enable_student_name`, `enable_class`, `enable_fathers_name`, `enable_mothers_name`, `enable_address`, `enable_phone`, `enable_dob`, `enable_blood_group`, `enable_student_barcode`, `enable_student_rollno`, `enable_student_house_name`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Sample Student Identity Card Horizontal', 'Mount Carmel School', '110 Kings Street, CA  Phone: 456542 Email: mount@gmail.com', 'samplebackground12.png', 'samplelogo12.png', 'samplesign12.png', 0, '#595959', 1, 1, 1, 1, 0, 1, 1, 1, 1, 1, 0, 0, 1, '2025-08-25 08:58:05', '2025-08-25 08:58:05'),
(2, 'Sample Student Identity Card Vertical', 'Mount Carmel School', '110 Kings Street, CA  Phone: 456542 Email: mount@gmail.com', 'samplebackground12.png', 'samplelogo12.png', 'samplesign12.png', 1, '#595959', 1, 1, 1, 1, 0, 1, 1, 1, 1, 1, 0, 0, 1, '2025-08-25 08:58:05', '2025-08-25 08:58:05');

-- --------------------------------------------------------

--
-- Table structure for table `income`
--

DROP TABLE IF EXISTS `income`;
CREATE TABLE IF NOT EXISTS `income` (
  `id` int NOT NULL AUTO_INCREMENT,
  `income_head_id` int DEFAULT NULL,
  `name` varchar(50) DEFAULT NULL,
  `invoice_no` varchar(200) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `amount` float(10,2) DEFAULT '0.00',
  `note` text,
  `is_active` varchar(255) DEFAULT 'yes',
  `documents` varchar(255) DEFAULT NULL,
  `is_deleted` varchar(255) DEFAULT 'no',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `income_head_id` (`income_head_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `income_head`
--

DROP TABLE IF EXISTS `income_head`;
CREATE TABLE IF NOT EXISTS `income_head` (
  `id` int NOT NULL AUTO_INCREMENT,
  `income_category` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` varchar(255) NOT NULL DEFAULT 'yes',
  `is_deleted` varchar(255) NOT NULL DEFAULT 'no',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `item`
--

DROP TABLE IF EXISTS `item`;
CREATE TABLE IF NOT EXISTS `item` (
  `id` int NOT NULL AUTO_INCREMENT,
  `item_category_id` int DEFAULT NULL,
  `item_store_id` int DEFAULT NULL,
  `item_supplier_id` int DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `unit` varchar(100) NOT NULL,
  `item_photo` varchar(225) DEFAULT NULL,
  `description` text NOT NULL,
  `quantity` int NOT NULL,
  `date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `item_category_id` (`item_category_id`),
  KEY `item_store_id` (`item_store_id`),
  KEY `item_supplier_id` (`item_supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `item_category`
--

DROP TABLE IF EXISTS `item_category`;
CREATE TABLE IF NOT EXISTS `item_category` (
  `id` int NOT NULL AUTO_INCREMENT,
  `item_category` varchar(255) NOT NULL,
  `is_active` varchar(255) NOT NULL DEFAULT 'yes',
  `description` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `item_issue`
--

DROP TABLE IF EXISTS `item_issue`;
CREATE TABLE IF NOT EXISTS `item_issue` (
  `id` int NOT NULL AUTO_INCREMENT,
  `issue_type` varchar(15) DEFAULT NULL,
  `issue_to` int NOT NULL,
  `issue_by` int DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `item_category_id` int DEFAULT NULL,
  `item_id` int DEFAULT NULL,
  `quantity` int NOT NULL,
  `note` text NOT NULL,
  `is_returned` int NOT NULL DEFAULT '1',
  `is_active` varchar(10) DEFAULT 'no',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `item_id` (`item_id`),
  KEY `item_category_id` (`item_category_id`),
  KEY `issue_to` (`issue_to`),
  KEY `issue_by` (`issue_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `item_stock`
--

DROP TABLE IF EXISTS `item_stock`;
CREATE TABLE IF NOT EXISTS `item_stock` (
  `id` int NOT NULL AUTO_INCREMENT,
  `item_id` int DEFAULT NULL,
  `supplier_id` int DEFAULT NULL,
  `store_id` int DEFAULT NULL,
  `symbol` varchar(10) NOT NULL DEFAULT '+',
  `quantity` int DEFAULT NULL,
  `purchase_price` float(10,2) NOT NULL,
  `date` date NOT NULL,
  `attachment` varchar(250) DEFAULT NULL,
  `description` text NOT NULL,
  `is_active` varchar(10) DEFAULT 'yes',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `item_id` (`item_id`),
  KEY `supplier_id` (`supplier_id`),
  KEY `store_id` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `item_store`
--

DROP TABLE IF EXISTS `item_store`;
CREATE TABLE IF NOT EXISTS `item_store` (
  `id` int NOT NULL AUTO_INCREMENT,
  `item_store` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `item_supplier`
--

DROP TABLE IF EXISTS `item_supplier`;
CREATE TABLE IF NOT EXISTS `item_supplier` (
  `id` int NOT NULL AUTO_INCREMENT,
  `item_supplier` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `contact_person_name` varchar(255) NOT NULL,
  `contact_person_phone` varchar(255) NOT NULL,
  `contact_person_email` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `languages`
--

DROP TABLE IF EXISTS `languages`;
CREATE TABLE IF NOT EXISTS `languages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `language` varchar(50) DEFAULT NULL,
  `short_code` varchar(255) NOT NULL,
  `country_code` varchar(255) NOT NULL,
  `is_rtl` int NOT NULL,
  `is_deleted` varchar(10) NOT NULL DEFAULT 'yes',
  `is_active` varchar(255) DEFAULT 'no',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=95 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `languages`
--

INSERT INTO `languages` (`id`, `language`, `short_code`, `country_code`, `is_rtl`, `is_deleted`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Azerbaijan', 'az', 'az', 0, 'no', 'no', '2019-11-20 11:23:12', '0000-00-00 00:00:00'),
(2, 'Albanian', 'sq', 'al', 0, 'no', 'no', '2019-11-20 11:42:42', '0000-00-00 00:00:00'),
(3, 'Amharic', 'am', 'am', 0, 'no', 'no', '2019-11-20 11:24:23', '0000-00-00 00:00:00'),
(4, 'English', 'en', 'us', 0, 'no', 'no', '2019-11-20 11:38:50', '0000-00-00 00:00:00'),
(5, 'Arabic', 'ar', 'sa', 0, 'no', 'no', '2019-11-20 11:47:28', '0000-00-00 00:00:00'),
(7, 'Afrikaans', 'af', 'af', 0, 'no', 'no', '2019-11-20 11:24:23', '0000-00-00 00:00:00'),
(8, 'Basque', 'eu', 'es', 0, 'no', 'no', '2019-11-20 11:54:10', '0000-00-00 00:00:00'),
(11, 'Bengali', 'bn', 'in', 0, 'no', 'no', '2019-11-20 11:41:53', '0000-00-00 00:00:00'),
(13, 'Bosnian', 'bs', 'bs', 0, 'no', 'no', '2019-11-20 11:24:23', '0000-00-00 00:00:00'),
(14, 'Welsh', 'cy', 'cy', 0, 'no', 'no', '2019-11-20 11:24:23', '0000-00-00 00:00:00'),
(15, 'Hungarian', 'hu', 'hu', 0, 'no', 'no', '2019-11-20 11:24:23', '0000-00-00 00:00:00'),
(16, 'Vietnamese', 'vi', 'vi', 0, 'no', 'no', '2019-11-20 11:24:23', '0000-00-00 00:00:00'),
(17, 'Haitian', 'ht', 'ht', 0, 'no', 'no', '2021-01-23 07:09:32', '0000-00-00 00:00:00'),
(18, 'Galician', 'gl', 'gl', 0, 'no', 'no', '2019-11-20 11:24:23', '0000-00-00 00:00:00'),
(19, 'Dutch', 'nl', 'nl', 0, 'no', 'no', '2019-11-20 11:24:23', '0000-00-00 00:00:00'),
(21, 'Greek', 'el', 'gr', 0, 'no', 'no', '2019-11-20 12:12:08', '0000-00-00 00:00:00'),
(22, 'Georgian', 'ka', 'ge', 0, 'no', 'no', '2019-11-20 12:11:40', '0000-00-00 00:00:00'),
(23, 'Gujarati', 'gu', 'in', 0, 'no', 'no', '2019-11-20 11:39:16', '0000-00-00 00:00:00'),
(24, 'Danish', 'da', 'dk', 0, 'no', 'no', '2019-11-20 12:03:25', '0000-00-00 00:00:00'),
(25, 'Hebrew', 'he', 'il', 0, 'no', 'no', '2019-11-20 12:13:50', '0000-00-00 00:00:00'),
(26, 'Yiddish', 'yi', 'il', 0, 'no', 'no', '2019-11-20 12:25:33', '0000-00-00 00:00:00'),
(27, 'Indonesian', 'id', 'id', 0, 'no', 'no', '2019-11-20 11:24:23', '0000-00-00 00:00:00'),
(28, 'Irish', 'ga', 'ga', 0, 'no', 'no', '2019-11-20 11:24:23', '0000-00-00 00:00:00'),
(29, 'Italian', 'it', 'it', 0, 'no', 'no', '2019-11-20 11:24:23', '0000-00-00 00:00:00'),
(30, 'Icelandic', 'is', 'is', 0, 'no', 'no', '2019-11-20 11:24:23', '0000-00-00 00:00:00'),
(31, 'Spanish', 'es', 'es', 0, 'no', 'no', '2019-11-20 11:24:23', '0000-00-00 00:00:00'),
(33, 'Kannada', 'kn', 'kn', 0, 'no', 'no', '2019-11-20 11:24:23', '0000-00-00 00:00:00'),
(34, 'Catalan', 'ca', 'ca', 0, 'no', 'no', '2019-11-20 11:24:23', '0000-00-00 00:00:00'),
(36, 'Chinese', 'zh', 'cn', 0, 'no', 'no', '2019-11-20 12:01:48', '0000-00-00 00:00:00'),
(37, 'Korean', 'ko', 'kr', 0, 'no', 'no', '2019-11-20 12:19:09', '0000-00-00 00:00:00'),
(38, 'Xhosa', 'xh', 'ls', 0, 'no', 'no', '2019-11-20 12:24:39', '0000-00-00 00:00:00'),
(39, 'Latin', 'la', 'it', 0, 'no', 'no', '2021-01-23 07:09:32', '0000-00-00 00:00:00'),
(40, 'Latvian', 'lv', 'lv', 0, 'no', 'no', '2019-11-20 11:24:23', '0000-00-00 00:00:00'),
(41, 'Lithuanian', 'lt', 'lt', 0, 'no', 'no', '2019-11-20 11:24:23', '0000-00-00 00:00:00'),
(43, 'Malagasy', 'mg', 'mg', 0, 'no', 'no', '2019-11-20 11:24:23', '0000-00-00 00:00:00'),
(44, 'Malay', 'ms', 'ms', 0, 'no', 'no', '2019-11-20 11:24:23', '0000-00-00 00:00:00'),
(45, 'Malayalam', 'ml', 'ml', 0, 'no', 'no', '2019-11-20 11:24:23', '0000-00-00 00:00:00'),
(46, 'Maltese', 'mt', 'mt', 0, 'no', 'no', '2019-11-20 11:24:23', '0000-00-00 00:00:00'),
(47, 'Macedonian', 'mk', 'mk', 0, 'no', 'no', '2019-11-20 11:24:23', '0000-00-00 00:00:00'),
(48, 'Maori', 'mi', 'nz', 0, 'no', 'no', '2019-11-20 12:20:27', '0000-00-00 00:00:00'),
(49, 'Marathi', 'mr', 'in', 0, 'no', 'no', '2019-11-20 11:39:51', '0000-00-00 00:00:00'),
(51, 'Mongolian', 'mn', 'mn', 0, 'no', 'no', '2019-11-20 11:24:23', '0000-00-00 00:00:00'),
(52, 'German', 'de', 'de', 0, 'no', 'no', '2019-11-20 11:24:23', '0000-00-00 00:00:00'),
(53, 'Nepali', 'ne', 'ne', 0, 'no', 'no', '2019-11-20 11:24:23', '0000-00-00 00:00:00'),
(54, 'Norwegian', 'no', 'no', 0, 'no', 'no', '2019-11-20 11:24:23', '0000-00-00 00:00:00'),
(55, 'Punjabi', 'pa', 'in', 0, 'no', 'no', '2019-11-20 11:40:16', '0000-00-00 00:00:00'),
(57, 'Persian', 'fa', 'ir', 0, 'no', 'no', '2019-11-20 12:21:17', '0000-00-00 00:00:00'),
(59, 'Portuguese', 'pt', 'pt', 0, 'no', 'no', '2019-11-20 11:24:23', '0000-00-00 00:00:00'),
(60, 'Romanian', 'ro', 'ro', 0, 'no', 'no', '2019-11-20 11:24:23', '0000-00-00 00:00:00'),
(61, 'Russian', 'ru', 'ru', 0, 'no', 'no', '2019-11-20 11:24:23', '0000-00-00 00:00:00'),
(62, 'Cebuano', 'ceb', 'ph', 0, 'no', 'no', '2019-11-20 11:59:12', '0000-00-00 00:00:00'),
(64, 'Sinhala', 'si', 'lk ', 0, 'no', 'no', '2021-01-23 07:09:32', '0000-00-00 00:00:00'),
(65, 'Slovakian', 'sk', 'sk', 0, 'no', 'no', '2019-11-20 11:24:23', '0000-00-00 00:00:00'),
(66, 'Slovenian', 'sl', 'sl', 0, 'no', 'no', '2019-11-20 11:24:23', '0000-00-00 00:00:00'),
(67, 'Swahili', 'sw', 'ke', 0, 'no', 'no', '2019-11-20 12:21:57', '0000-00-00 00:00:00'),
(68, 'Sundanese', 'su', 'sd', 0, 'no', 'no', '2019-12-03 11:06:57', '0000-00-00 00:00:00'),
(70, 'Thai', 'th', 'th', 0, 'no', 'no', '2019-11-20 11:24:23', '0000-00-00 00:00:00'),
(71, 'Tagalog', 'tl', 'tl', 0, 'no', 'no', '2019-11-20 11:24:23', '0000-00-00 00:00:00'),
(72, 'Tamil', 'ta', 'in', 0, 'no', 'no', '2019-11-20 11:40:53', '0000-00-00 00:00:00'),
(74, 'Telugu', 'te', 'in', 0, 'no', 'no', '2019-11-20 11:41:15', '0000-00-00 00:00:00'),
(75, 'Turkish', 'tr', 'tr', 0, 'no', 'no', '2019-11-20 11:24:23', '0000-00-00 00:00:00'),
(77, 'Uzbek', 'uz', 'uz', 0, 'no', 'no', '2019-11-20 11:24:23', '0000-00-00 00:00:00'),
(79, 'Urdu', 'ur', 'pk', 0, 'no', 'no', '2019-11-20 12:23:57', '0000-00-00 00:00:00'),
(80, 'Finnish', 'fi', 'fi', 0, 'no', 'no', '2019-11-20 11:24:23', '0000-00-00 00:00:00'),
(81, 'French', 'fr', 'fr', 0, 'no', 'no', '2019-11-20 11:24:23', '0000-00-00 00:00:00'),
(82, 'Hindi', 'hi', 'in', 0, 'no', 'no', '2019-11-20 11:36:34', '0000-00-00 00:00:00'),
(84, 'Czech', 'cs', 'cz', 0, 'no', 'no', '2019-11-20 12:02:36', '0000-00-00 00:00:00'),
(85, 'Swedish', 'sv', 'sv', 0, 'no', 'no', '2019-11-20 11:24:23', '0000-00-00 00:00:00'),
(86, 'Scottish', 'gd', 'gd', 0, 'no', 'no', '2019-11-20 11:24:23', '0000-00-00 00:00:00'),
(87, 'Estonian', 'et', 'et', 0, 'no', 'no', '2019-11-20 11:24:23', '0000-00-00 00:00:00'),
(88, 'Esperanto', 'eo', 'br', 0, 'no', 'no', '2019-11-21 04:49:18', '0000-00-00 00:00:00'),
(89, 'Javanese', 'jv', 'id', 0, 'no', 'no', '2019-11-20 12:18:29', '0000-00-00 00:00:00'),
(90, 'Japanese', 'ja', 'jp', 0, 'no', 'no', '2019-11-20 12:14:39', '0000-00-00 00:00:00'),
(91, 'Polish', 'pl', 'pl', 0, 'no', 'no', '2020-06-15 03:25:27', '0000-00-00 00:00:00'),
(92, 'Kurdish', 'ku', 'iq', 0, 'no', 'no', '2020-12-21 00:15:31', '0000-00-00 00:00:00'),
(93, 'Lao', 'lo', 'la', 0, 'no', 'no', '2020-12-21 00:15:36', '0000-00-00 00:00:00'),
(94, 'Croatia', 'hr', 'hr', 0, 'no', 'no', '2022-06-07 11:48:21', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `leave_types`
--

DROP TABLE IF EXISTS `leave_types`;
CREATE TABLE IF NOT EXISTS `leave_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type` varchar(200) NOT NULL,
  `is_active` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `lesson`
--

DROP TABLE IF EXISTS `lesson`;
CREATE TABLE IF NOT EXISTS `lesson` (
  `id` int NOT NULL AUTO_INCREMENT,
  `session_id` int NOT NULL,
  `subject_group_subject_id` int NOT NULL,
  `subject_group_class_sections_id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`),
  KEY `subject_group_subject_id` (`subject_group_subject_id`),
  KEY `subject_group_class_sections_id` (`subject_group_class_sections_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `lesson_plan_forum`
--

DROP TABLE IF EXISTS `lesson_plan_forum`;
CREATE TABLE IF NOT EXISTS `lesson_plan_forum` (
  `id` int NOT NULL AUTO_INCREMENT,
  `subject_syllabus_id` int NOT NULL,
  `type` varchar(20) NOT NULL COMMENT 'staff,student',
  `staff_id` int DEFAULT NULL,
  `student_id` int DEFAULT NULL,
  `message` text NOT NULL,
  `created_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subject_syllabus_id` (`subject_syllabus_id`),
  KEY `student_id` (`student_id`),
  KEY `staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `libarary_members`
--

DROP TABLE IF EXISTS `libarary_members`;
CREATE TABLE IF NOT EXISTS `libarary_members` (
  `id` int NOT NULL AUTO_INCREMENT,
  `library_card_no` varchar(50) DEFAULT NULL,
  `member_type` varchar(50) DEFAULT NULL,
  `member_id` int DEFAULT NULL,
  `is_active` varchar(10) NOT NULL DEFAULT 'no',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

DROP TABLE IF EXISTS `logs`;
CREATE TABLE IF NOT EXISTS `logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `message` text,
  `record_id` text,
  `user_id` int DEFAULT NULL,
  `action` varchar(50) DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `platform` varchar(50) DEFAULT NULL,
  `agent` varchar(50) DEFAULT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `logs`
--

INSERT INTO `logs` (`id`, `message`, `record_id`, `user_id`, `action`, `ip_address`, `platform`, `agent`, `time`, `created_at`) VALUES
(1, 'Record deleted On Multi Branch id 1', '1', 1, 'Delete', '::1', 'Windows 10', 'Chrome 139.0.0.0', '2025-08-26 05:09:09', NULL),
(2, 'Record deleted On Multi Branch id 2', '2', 1, 'Delete', '::1', 'Windows 10', 'Chrome 139.0.0.0', '2025-08-26 05:09:20', NULL),
(3, 'Record deleted On Multi Branch id 3', '3', 1, 'Delete', '::1', 'Windows 10', 'Chrome 139.0.0.0', '2025-08-26 05:09:28', NULL),
(4, 'Record deleted On Multi Branch id 4', '4', 1, 'Delete', '::1', 'Windows 10', 'Chrome 139.0.0.0', '2025-08-26 05:21:26', NULL),
(5, 'Record deleted On Multi Branch id 6', '6', 1, 'Delete', '::1', 'Windows 10', 'Chrome 139.0.0.0', '2025-08-26 05:31:23', NULL),
(6, 'Record deleted On Multi Branch id 5', '5', 1, 'Delete', '::1', 'Windows 10', 'Chrome 139.0.0.0', '2025-08-26 05:36:14', NULL),
(7, 'Record deleted On  Multi Branch   id 10', '10', 1, 'Delete', '::1', 'Windows 10', 'Chrome 139.0.0.0', '2025-08-26 07:24:24', NULL),
(8, 'Record deleted On  Multi Branch   id 9', '9', 1, 'Delete', '::1', 'Windows 10', 'Chrome 139.0.0.0', '2025-08-26 07:24:28', NULL),
(9, 'Record deleted On  Multi Branch   id 8', '8', 1, 'Delete', '::1', 'Windows 10', 'Chrome 139.0.0.0', '2025-08-26 07:24:32', NULL),
(10, 'Record deleted On  Multi Branch   id 11', '11', 1, 'Delete', '::1', 'Windows 10', 'Chrome 139.0.0.0', '2025-08-26 07:35:28', NULL),
(11, 'Record deleted On  Multi Branch   id 7', '7', 1, 'Delete', '::1', 'Windows 10', 'Chrome 139.0.0.0', '2025-08-27 00:52:58', NULL),
(12, 'Record deleted On  Multi Branch   id 12', '12', 1, 'Delete', '::1', 'Windows 10', 'Chrome 139.0.0.0', '2025-08-27 00:57:40', NULL),
(13, 'Record deleted On  Multi Branch   id 14', '14', 1, 'Delete', '::1', 'Windows 10', 'Chrome 139.0.0.0', '2025-08-27 01:33:21', NULL),
(14, 'Record deleted On  Multi Branch   id 13', '13', 1, 'Delete', '::1', 'Windows 10', 'Chrome 139.0.0.0', '2025-08-27 01:33:25', NULL),
(15, 'Record deleted On  Multi Branch   id 15', '15', 1, 'Delete', '::1', 'Windows 10', 'Chrome 139.0.0.0', '2025-08-27 01:37:38', NULL),
(16, 'Record deleted On  Multi Branch   id 16', '16', 1, 'Delete', '::1', 'Windows 10', 'Chrome 139.0.0.0', '2025-08-27 02:22:46', NULL),
(17, 'Record deleted On  Multi Branch   id 17', '17', 1, 'Delete', '::1', 'Windows 10', 'Chrome 139.0.0.0', '2025-08-27 02:23:49', NULL),
(18, 'Record deleted On  Multi Branch   id 18', '18', 1, 'Delete', '::1', 'Windows 10', 'Chrome 139.0.0.0', '2025-08-27 03:02:11', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `mark_divisions`
--

DROP TABLE IF EXISTS `mark_divisions`;
CREATE TABLE IF NOT EXISTS `mark_divisions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(200) DEFAULT NULL,
  `percentage_from` float(10,2) DEFAULT NULL,
  `percentage_to` float(10,2) DEFAULT NULL,
  `is_active` int DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
CREATE TABLE IF NOT EXISTS `messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(200) DEFAULT NULL,
  `template_id` varchar(100) DEFAULT NULL,
  `email_template_id` int DEFAULT NULL,
  `sms_template_id` int DEFAULT NULL,
  `send_through` varchar(20) DEFAULT NULL,
  `message` text,
  `send_mail` varchar(10) DEFAULT '0',
  `send_sms` varchar(10) DEFAULT '0',
  `is_group` varchar(10) DEFAULT '0',
  `is_individual` varchar(10) DEFAULT '0',
  `is_class` int NOT NULL DEFAULT '0',
  `is_schedule` int NOT NULL,
  `sent` int DEFAULT NULL,
  `schedule_date_time` datetime DEFAULT NULL,
  `group_list` text,
  `user_list` text,
  `send_to` varchar(255) DEFAULT NULL,
  `schedule_class` int DEFAULT NULL,
  `schedule_section` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE IF NOT EXISTS `migrations` (
  `version` bigint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `multi_branch`
--

DROP TABLE IF EXISTS `multi_branch`;
CREATE TABLE IF NOT EXISTS `multi_branch` (
  `id` int NOT NULL AUTO_INCREMENT,
  `branch_name` varchar(200) DEFAULT NULL,
  `branch_url` varchar(500) NOT NULL,
  `hostname` varchar(200) DEFAULT NULL,
  `username` varchar(200) DEFAULT NULL,
  `password` varchar(200) DEFAULT NULL,
  `database_name` varchar(200) DEFAULT NULL,
  `directory_path` varchar(500) DEFAULT NULL,
  `is_verified` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `multi_branch`
--

INSERT INTO `multi_branch` (`id`, `branch_name`, `branch_url`, `hostname`, `username`, `password`, `database_name`, `directory_path`, `is_verified`, `created_at`) VALUES
(19, 'A Branch', '', 'localhost', 'root', '', 'new_db', NULL, 1, '2025-08-27 08:02:40');

-- --------------------------------------------------------

--
-- Table structure for table `notification_roles`
--

DROP TABLE IF EXISTS `notification_roles`;
CREATE TABLE IF NOT EXISTS `notification_roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `send_notification_id` int DEFAULT NULL,
  `role_id` int DEFAULT NULL,
  `is_active` int DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `send_notification_id` (`send_notification_id`),
  KEY `role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `notification_setting`
--

DROP TABLE IF EXISTS `notification_setting`;
CREATE TABLE IF NOT EXISTS `notification_setting` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type` varchar(100) DEFAULT NULL,
  `is_mail` varchar(10) DEFAULT '0',
  `is_sms` varchar(10) DEFAULT '0',
  `is_notification` int NOT NULL DEFAULT '0',
  `display_notification` int NOT NULL DEFAULT '0',
  `display_sms` int NOT NULL DEFAULT '1',
  `is_student_recipient` int DEFAULT NULL,
  `is_guardian_recipient` int DEFAULT NULL,
  `is_staff_recipient` int DEFAULT NULL,
  `display_student_recipient` int DEFAULT NULL,
  `display_guardian_recipient` int DEFAULT NULL,
  `display_staff_recipient` int DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `template_id` varchar(100) NOT NULL,
  `template` longtext NOT NULL,
  `variables` text NOT NULL,
  `notification_order` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `notification_setting`
--

INSERT INTO `notification_setting` (`id`, `type`, `is_mail`, `is_sms`, `is_notification`, `display_notification`, `display_sms`, `is_student_recipient`, `is_guardian_recipient`, `is_staff_recipient`, `display_student_recipient`, `display_guardian_recipient`, `display_staff_recipient`, `subject`, `template_id`, `template`, `variables`, `notification_order`, `created_at`, `updated_at`) VALUES
(1, 'student_admission', '1', '0', 0, 0, 1, 1, 1, 0, 1, 1, NULL, 'Student Admission', '', 'Dear {{student_name}} your admission is confirm in Class: {{class}} Section:  {{section}} for Session: {{current_session_name}} for more \r\ndetail\r\n contact\r\n System\r\n Admin\r\n {{class}} {{section}} {{admission_no}} {{roll_no}} {{admission_date}} {{mobileno}} {{email}} {{dob}} {{guardian_name}} {{guardian_relation}} {{guardian_phone}} {{father_name}} {{father_phone}} {{blood_group}} {{mother_name}} {{gender}} {{guardian_email}}', '{{student_name}} {{class}}  {{section}}  {{admission_no}}  {{roll_no}}  {{admission_date}}   {{mobileno}}  {{email}}  {{dob}}  {{guardian_name}}  {{guardian_relation}}  {{guardian_phone}}  {{father_name}}  {{father_phone}}  {{blood_group}}  {{mother_name}}  {{gender}} {{guardian_email}} {{current_session_name}} ', NULL, '2022-12-28 09:52:24', '2025-02-13 10:21:49'),
(2, 'exam_result', '1', '0', 0, 1, 1, 1, 1, 0, 1, 1, NULL, 'Exam Result', '', 'Dear {{student_name}} - {{exam_roll_no}}, your {{exam}} result has been published.', '{{student_name}} {{exam_roll_no}} {{exam}}', NULL, '2022-12-28 09:52:24', '2025-02-26 06:31:30'),
(3, 'fee_submission', '1', '0', 0, 1, 1, 1, 1, 0, 1, 1, NULL, 'Fee Submission', '', 'Dear parents, we have received Fees Amount {{fee_amount}} for  {{student_name}}  by Your School Name \r\n{{class}} {{section}} {{fine_type}} {{fine_percentage}} {{fine_amount}} {{fee_group_name}} {{type}} {{code}} {{email}} {{contact_no}} {{invoice_id}} {{sub_invoice_id}} {{due_date}} {{amount}} {{fee_amount}}', '{{student_name}} {{class}} {{section}} {{fine_type}} {{fine_percentage}} {{fine_amount}} {{fee_group_name}} {{type}} {{code}} {{email}} {{contact_no}} {{invoice_id}} {{sub_invoice_id}} {{due_date}} {{amount}} {{fee_amount}}', NULL, '2022-12-28 09:52:24', '2025-02-13 10:21:49'),
(4, 'student_absent_attendence', '1', '0', 0, 1, 1, 1, 1, 0, 1, 1, NULL, 'Absent Attendence', '', 'Absent Notice :{{student_name}}  was absent on date {{date}} in period {{subject_name}} {{subject_code}} {{subject_type}} from Your School Name', '{{student_name}} {{mobileno}} {{email}} {{father_name}} {{father_phone}} {{father_occupation}} {{mother_name}} {{mother_phone}} {{guardian_name}} {{guardian_phone}} {{guardian_occupation}} {{guardian_email}} {{date}} {{current_session_name}} {{time_from}} {{time_to}} {{subject_name}} {{subject_code}} {{subject_type}}  ', NULL, '2022-12-28 09:52:24', '2025-02-13 10:38:04'),
(6, 'homework', '1', '0', 0, 1, 1, 1, 1, 0, 1, 1, NULL, 'Homework', '', 'New Homework has been created for \r\n{{student_name}} at\r\n\r\n\r\n\r\n{{homework_date}} for the class {{class}} {{section}} {{subject}}. kindly submit your\r\n\r\n\r\n homework before {{submit_date}} .Thank you', '{{homework_date}} {{submit_date}} {{class}} {{section}} {{subject}} {{student_name}} {{admission_no}} ', NULL, '2022-12-28 09:52:24', '2025-02-26 06:31:30'),
(7, 'fees_reminder', '1', '0', 0, 1, 1, 1, 1, 0, 1, 1, NULL, 'Fees Reminder', '', 'Dear parents, please pay fee amount Rs.{{due_amount}} of {{fee_type}} before {{due_date}} for {{student_name}}  from smart school (ignore if you already paid)', '{{fee_type}}{{fee_code}}{{due_date}}{{student_name}}{{school_name}}{{fee_amount}}{{due_amount}}{{deposit_amount}} ', NULL, '2022-12-28 09:52:24', '2025-02-13 10:21:49'),
(8, 'forgot_password', '1', '0', 0, 0, 0, 1, 1, 1, 1, 1, 1, 'Forgot Password', '', 'Dear  {{name}} , \r\n    Recently a request was submitted to reset password for your account. If you didn\'t make the request, just ignore this email. Otherwise you can reset your password using this link <a href=\'{{resetPassLink}}\'>Click here to reset your password</a>,\r\nif you\'re having trouble clicking the password reset button, copy and paste the URL below into your web browser. your username {{username}}\r\n{{resetPassLink}}\r\n Regards,\r\n {{school_name}}', '{{school_name}}{{name}}{{username}}{{resetPassLink}} ', NULL, '2022-12-28 09:52:24', '2025-02-13 10:08:56'),
(9, 'online_examination_publish_exam', '1', '0', 0, 1, 1, 1, 1, 0, 1, 1, NULL, 'Online Examination Publish Exam', '', 'A new exam {{exam_title}} has been created for  duration: {{time_duration}} min, which will be available from:  {{exam_from}} to  {{exam_to}}.', '{{exam_title}} {{exam_from}} {{exam_to}} {{time_duration}} {{attempt}} {{passing_percentage}}', NULL, '2022-12-28 09:52:24', '2025-02-26 06:31:30'),
(10, 'online_examination_publish_result', '1', '0', 0, 1, 1, 1, 1, 0, 1, 1, NULL, 'Online Examination Publish Result', '', 'Exam {{exam_title}} result has been declared which was conducted between  {{exam_from}} to   {{exam_to}}, for more details, please check your student portal.', '{{exam_title}} {{exam_from}} {{exam_to}} {{time_duration}} {{attempt}} {{passing_percentage}}', NULL, '2022-12-28 09:52:24', '2025-02-26 06:31:30'),
(11, 'online_admission_form_submission', '1', '0', 0, 1, 1, 1, 1, 0, 1, 1, NULL, 'Online Admission Form Submission', '', 'Dear {{firstname}}  {{lastname}} your online admission form is Submitted successfully  on date {{date}}. Your Reference number is {{reference_no}}. Please remember your reference number for further process.', ' {{firstname}} {{lastname}} {{date}} {{reference_no}}', NULL, '2022-12-28 09:52:24', '2025-02-13 10:21:49'),
(12, 'online_admission_fees_submission', '1', '0', 0, 1, 1, 1, 1, 0, 1, 1, NULL, 'Online Admission Fees Submission', '', 'Dear {{firstname}}  {{lastname}} your online admission form is Submitted successfully and the payment of {{paid_amount}} has recieved successfully on date {{date}}. Your Reference number is {{reference_no}}. Please remember your reference number for further process.', ' {{firstname}} {{lastname}} {{date}} {{paid_amount}} {{reference_no}}', NULL, '2022-12-28 09:52:24', '2025-02-26 06:30:59'),
(13, 'student_login_credential', '1', '0', 0, 0, 1, 1, 1, 0, 1, 1, NULL, 'Student Login Credential', '', 'Hello {{display_name}} your login details for Url: {{url}} Username: {{username}}  Password: {{password}} admission No: {{admission_no}}', '{{url}} {{display_name}} {{username}} {{password}} {{admission_no}}', NULL, '2022-08-06 05:34:41', '2025-02-26 06:30:11'),
(14, 'staff_login_credential', '1', '0', 0, 0, 1, 0, 0, 1, NULL, NULL, 1, 'Staff Login Credential', '', 'Hello {{first_name}} {{last_name}} your login details for Url: {{url}} Username: {{username}}  Password: {{password}} Employee ID: {{employee_id}}', '{{url}} {{first_name}} {{last_name}} {{username}} {{password}} {{employee_id}}', NULL, '2021-12-23 11:59:13', '2025-02-26 06:30:14'),
(15, 'fee_processing', '1', '0', 0, 1, 1, 1, 1, 0, 1, 1, NULL, 'Fee processing', '', 'Dear parents, we have received Fees Amount {{fee_amount}} for  {{student_name}}  by Your School Name \r\n{{class}} {{section}} {{email}} {{contact_no}}\r\n\r\n{{student_name}} {{class}} {{section}} {{email}} {{contact_no}} transaction_id :{{transaction_id}} {{fee_amount}}', '{{student_name}} {{class}} {{section}} {{email}} {{contact_no}} {{transaction_id}} {{fee_amount}}', NULL, '2021-12-22 10:15:42', '2025-02-26 06:30:42'),
(16, 'online_admission_fees_processing', '1', '0', 0, 1, 1, 1, 1, 0, 1, 1, NULL, 'Online Admission Fees Processing', '', 'Dear {{firstname}}  {{lastname}} your online admission form is Submitted successfully and the payment of {{paid_amount}} has processing on date {{date}}. Your Reference number is {{reference_no}} and your transaction id {{transaction_id}}. Please remember your reference number for further process.', ' {{firstname}} {{lastname}} {{date}} {{paid_amount}} {{reference_no}} {{transaction_id}}', NULL, '2022-08-06 11:09:47', '2025-02-26 06:30:40'),
(17, 'student_apply_leave', '1', '0', 0, 0, 1, 0, 1, 1, NULL, 1, 1, 'Student Apply Leave ( {{student_name}} - {{admission_no}} )', '', 'My Name is {{student_name}} Class {{class}} section {{section}}. I have to apply leave on {{apply_date}}and from {{from_date}} to {{to_date}}. {{message}} please provide.', '{{message}} {{apply_date}} {{from_date}} {{to_date}} {{student_name}} {{class}} {{section}}', NULL, '2022-03-12 11:58:37', '2025-02-26 06:30:24'),
(18, 'email_pdf_exam_marksheet', '1', '0', 0, 0, 0, 1, 1, 0, 1, 1, NULL, 'Email PDF Exam Marksheet ( {{student_name}} - {{admission_no}} )', '', 'Dear {{student_name}} ({{admission_no}}) {{class}} Section {{section}}. We have mailed you the marksheet of Exam {{exam}} Roll no.{{roll_no}}', '{{student_name}} {{class}}  {{section}}  {{admission_no}}  {{roll_no}} {{exam}} {{admit_card_roll_no}} ', NULL, '2022-03-12 12:24:42', '2025-02-13 10:08:56'),
(19, 'homework_evaluation', '1', '0', 0, 1, 1, 1, 1, 0, 1, 1, NULL, 'Homework Evaluation', '', 'Homework Evaluation\r\nHomework Assign Date:  {{homework_date}}  \r\nLast Submit Date: {{submit_date}}\r\nStudent Name: {{student_name}} .\r\nAdmission No {{admission_no}}\r\n{{class}}\r\nsection: {{section}}\r\nsubject : {{subject}} \r\nMarks: {{marks}}/{{max_marks}}\r\nDate:{{evaluation_date}}\r\nThank you', '{{homework_date}} {{submit_date}} {{class}} {{section}} {{subject}} {{student_name}} {{admission_no}} {{max_marks}} {{marks}} {{evaluation_date}}', 120, '2025-01-15 08:00:34', '2025-02-26 06:30:36'),
(20, 'student_present_attendence', '1', '0', 0, 1, 1, 1, 1, 0, 1, 1, NULL, 'Present Attendence', '', 'Present Notice :{{student_name}} {{admission_no}} was present on date {{date}} in in_time {{in_time}} period subject-{{subject_name}}, subject_code - {{subject_code}}, subject_type-{{subject_type}}, period_time_from-  {{period_time_from}}, period_time_to-  {{period_time_to}}, mobile no - {{mobileno}}, email -  {{email}}, father name -  {{father_name}}, father phone - {{father_phone}}, father occupation -  {{father_occupation}}, mother name -  {{mother_name}}, mother phone - {{mother_phone}}, guardian name -  {{guardian_name}}, guardian phone -  {{guardian_phone}}, guardian occupation - {{guardian_occupation}}, guardian email - {{guardian_email}}', '{{student_name}} {{mobileno}} {{email}} {{father_name}} {{father_phone}} {{father_occupation}} {{mother_name}} {{mother_phone}} {{guardian_name}} {{guardian_phone}} {{guardian_occupation}} {{guardian_email}} {{date}} {{in_time}}  ({{admission_no}}) {{period_time_from}} {{period_time_to}} {{subject_name}} {{subject_code}} {{subject_type}}', 15, '2025-01-13 05:55:46', '2025-02-26 06:33:37'),
(21, 'staff_present_attendence', '1', '0', 0, 1, 1, 0, 0, 1, NULL, NULL, 1, 'staff Present Attendence', '', 'Present Notice: Staff Name {{staff_name}} ({{employee_id}}) is Present on Date : {{date}} at Time : {{in_time}}\r\n<br>\r\nstaff contact no:{{contact_no}}\r\n<br>\r\nstaff mail id : {{email}}', '{{date}} {{in_time}} {{staff_name}} {{employee_id}} {{contact_no}} {{email}}\n', 190, '2025-02-07 11:43:28', '2025-02-13 10:40:00'),
(22, 'staff_absent_attendence', '1', '0', 0, 1, 1, 0, 0, 1, NULL, NULL, 1, 'staff Absent Attendence', '', 'Absent Notice: Staff Name {{staff_name}} ({{employee_id}}) is Absent on Date : {{date}} \r\n<br>\r\nstaff contact no:{{contact_no}}\r\n<br>\r\nstaff mail id : {{email}}', '{{date}} {{staff_name}} {{employee_id}} {{contact_no}} {{email}}\n', 200, '2025-02-07 11:43:28', '2025-02-13 10:40:00');

-- --------------------------------------------------------

--
-- Table structure for table `offline_fees_payments`
--

DROP TABLE IF EXISTS `offline_fees_payments`;
CREATE TABLE IF NOT EXISTS `offline_fees_payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `invoice_id` varchar(50) DEFAULT NULL,
  `student_session_id` int DEFAULT NULL,
  `student_fees_master_id` int DEFAULT NULL,
  `fee_groups_feetype_id` int DEFAULT NULL,
  `student_transport_fee_id` int DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `bank_from` varchar(200) DEFAULT NULL,
  `bank_account_transferred` varchar(200) DEFAULT NULL,
  `reference` varchar(200) DEFAULT NULL,
  `amount` float(10,2) DEFAULT NULL,
  `submit_date` datetime DEFAULT NULL,
  `approve_date` datetime DEFAULT NULL,
  `attachment` text,
  `reply` text,
  `approved_by` int DEFAULT NULL,
  `is_active` varchar(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_fees_master_id` (`student_fees_master_id`),
  KEY `fee_groups_feetype_id` (`fee_groups_feetype_id`),
  KEY `student_transport_fee_id` (`student_transport_fee_id`),
  KEY `offline_fees_payments_ibfk_4` (`approved_by`),
  KEY `student_session_id` (`student_session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `onlineexam`
--

DROP TABLE IF EXISTS `onlineexam`;
CREATE TABLE IF NOT EXISTS `onlineexam` (
  `id` int NOT NULL AUTO_INCREMENT,
  `session_id` int DEFAULT NULL,
  `exam` text,
  `attempt` int NOT NULL,
  `exam_from` datetime DEFAULT NULL,
  `exam_to` datetime DEFAULT NULL,
  `is_quiz` int NOT NULL DEFAULT '0',
  `auto_publish_date` datetime DEFAULT NULL,
  `time_from` time DEFAULT NULL,
  `time_to` time DEFAULT NULL,
  `duration` time NOT NULL,
  `passing_percentage` float NOT NULL DEFAULT '0',
  `description` text,
  `publish_result` int NOT NULL DEFAULT '0',
  `answer_word_count` int NOT NULL DEFAULT '-1',
  `is_active` varchar(1) DEFAULT '0',
  `is_marks_display` int NOT NULL DEFAULT '0',
  `is_neg_marking` int NOT NULL DEFAULT '0',
  `is_random_question` int NOT NULL DEFAULT '0',
  `is_rank_generated` int NOT NULL DEFAULT '0',
  `publish_exam_notification` int NOT NULL,
  `publish_result_notification` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `onlineexam_attempts`
--

DROP TABLE IF EXISTS `onlineexam_attempts`;
CREATE TABLE IF NOT EXISTS `onlineexam_attempts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `onlineexam_student_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `onlineexam_student_id` (`onlineexam_student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `onlineexam_questions`
--

DROP TABLE IF EXISTS `onlineexam_questions`;
CREATE TABLE IF NOT EXISTS `onlineexam_questions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `question_id` int DEFAULT NULL,
  `onlineexam_id` int DEFAULT NULL,
  `session_id` int DEFAULT NULL,
  `marks` float(10,2) NOT NULL DEFAULT '0.00',
  `neg_marks` float(10,2) DEFAULT '0.00',
  `is_active` varchar(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `onlineexam_id` (`onlineexam_id`),
  KEY `question_id` (`question_id`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `onlineexam_students`
--

DROP TABLE IF EXISTS `onlineexam_students`;
CREATE TABLE IF NOT EXISTS `onlineexam_students` (
  `id` int NOT NULL AUTO_INCREMENT,
  `onlineexam_id` int DEFAULT NULL,
  `student_session_id` int DEFAULT NULL,
  `is_attempted` int NOT NULL DEFAULT '0',
  `rank` int DEFAULT '0',
  `quiz_attempted` int NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `onlineexam_id` (`onlineexam_id`),
  KEY `student_session_id` (`student_session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `onlineexam_student_results`
--

DROP TABLE IF EXISTS `onlineexam_student_results`;
CREATE TABLE IF NOT EXISTS `onlineexam_student_results` (
  `id` int NOT NULL AUTO_INCREMENT,
  `onlineexam_student_id` int NOT NULL,
  `onlineexam_question_id` int NOT NULL,
  `select_option` longtext,
  `marks` float(10,2) NOT NULL DEFAULT '0.00',
  `remark` text,
  `attachment_name` text,
  `attachment_upload_name` varchar(250) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `onlineexam_student_id` (`onlineexam_student_id`),
  KEY `onlineexam_question_id` (`onlineexam_question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `online_admissions`
--

DROP TABLE IF EXISTS `online_admissions`;
CREATE TABLE IF NOT EXISTS `online_admissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `admission_no` varchar(100) DEFAULT NULL,
  `roll_no` varchar(100) DEFAULT NULL,
  `reference_no` varchar(50) NOT NULL,
  `admission_date` date DEFAULT NULL,
  `firstname` varchar(100) DEFAULT NULL,
  `middlename` varchar(255) NOT NULL,
  `lastname` varchar(100) DEFAULT NULL,
  `rte` varchar(20) NOT NULL DEFAULT 'No',
  `image` varchar(255) DEFAULT NULL,
  `mobileno` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `pincode` varchar(100) DEFAULT NULL,
  `religion` varchar(100) DEFAULT NULL,
  `cast` varchar(50) NOT NULL,
  `dob` date DEFAULT NULL,
  `gender` varchar(100) DEFAULT NULL,
  `current_address` text,
  `permanent_address` text,
  `category_id` int DEFAULT NULL,
  `class_section_id` int DEFAULT NULL,
  `route_id` int NOT NULL,
  `school_house_id` int DEFAULT NULL,
  `blood_group` varchar(200) NOT NULL,
  `vehroute_id` int NOT NULL,
  `hostel_room_id` int DEFAULT NULL,
  `adhar_no` varchar(100) DEFAULT NULL,
  `samagra_id` varchar(100) DEFAULT NULL,
  `bank_account_no` varchar(100) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `ifsc_code` varchar(100) DEFAULT NULL,
  `guardian_is` varchar(100) NOT NULL,
  `father_name` varchar(100) DEFAULT NULL,
  `father_phone` varchar(100) DEFAULT NULL,
  `father_occupation` varchar(100) DEFAULT NULL,
  `mother_name` varchar(100) DEFAULT NULL,
  `mother_phone` varchar(100) DEFAULT NULL,
  `mother_occupation` varchar(100) DEFAULT NULL,
  `guardian_name` varchar(100) DEFAULT NULL,
  `guardian_relation` varchar(100) DEFAULT NULL,
  `guardian_phone` varchar(100) DEFAULT NULL,
  `guardian_occupation` varchar(150) NOT NULL,
  `guardian_address` text,
  `guardian_email` varchar(100) NOT NULL,
  `father_pic` varchar(255) NOT NULL,
  `mother_pic` varchar(255) NOT NULL,
  `guardian_pic` varchar(255) NOT NULL,
  `is_enroll` int DEFAULT '0',
  `previous_school` text,
  `height` varchar(100) NOT NULL,
  `weight` varchar(100) NOT NULL,
  `note` text NOT NULL,
  `form_status` int NOT NULL,
  `paid_status` int NOT NULL,
  `measurement_date` date DEFAULT NULL,
  `app_key` text,
  `document` text,
  `submit_date` date DEFAULT NULL,
  `disable_at` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `class_section_id` (`class_section_id`),
  KEY `category_id` (`category_id`),
  KEY `hostel_room_id` (`hostel_room_id`),
  KEY `school_house_id` (`school_house_id`),
  KEY `idx_reference_no` (`reference_no`),
  KEY `idx_mobileno` (`mobileno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `online_admission_custom_field_value`
--

DROP TABLE IF EXISTS `online_admission_custom_field_value`;
CREATE TABLE IF NOT EXISTS `online_admission_custom_field_value` (
  `id` int NOT NULL AUTO_INCREMENT,
  `belong_table_id` int DEFAULT NULL,
  `custom_field_id` int DEFAULT NULL,
  `field_value` longtext NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `custom_field_id` (`custom_field_id`),
  KEY `idx_belong_table_id` (`belong_table_id`),
  KEY `idx_field_value` (`field_value`(200))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `online_admission_fields`
--

DROP TABLE IF EXISTS `online_admission_fields`;
CREATE TABLE IF NOT EXISTS `online_admission_fields` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(250) DEFAULT NULL,
  `status` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `online_admission_fields`
--

INSERT INTO `online_admission_fields` (`id`, `name`, `status`, `created_at`, `updated_at`) VALUES
(1, 'middlename', 0, '2021-05-28 10:29:23', '2025-08-25 08:58:05'),
(2, 'lastname', 1, '2021-06-02 04:49:19', '2025-08-25 08:58:05'),
(3, 'category', 0, '2021-06-02 04:48:35', '2025-08-25 08:58:05'),
(4, 'religion', 0, '2021-06-02 04:48:35', '2025-08-25 08:58:05'),
(5, 'cast', 0, '2021-06-02 04:48:35', '2025-08-25 08:58:05'),
(6, 'mobile_no', 1, '2021-06-02 04:50:24', '2025-08-25 08:58:05'),
(7, 'admission_date', 0, '2021-06-02 04:48:35', '2025-08-25 08:58:05'),
(8, 'student_photo', 0, '2021-06-02 04:48:35', '2025-08-25 08:58:05'),
(9, 'is_student_house', 0, '2021-05-29 13:22:53', '2025-08-25 08:58:05'),
(10, 'is_blood_group', 0, '2021-06-02 04:48:35', '2025-08-25 08:58:05'),
(11, 'student_height', 0, '2021-06-02 04:48:35', '2025-08-25 08:58:05'),
(12, 'student_weight', 0, '2021-06-02 04:48:35', '2025-08-25 08:58:05'),
(13, 'father_name', 0, '2021-06-02 04:48:35', '2025-08-25 08:58:05'),
(14, 'father_phone', 0, '2021-06-02 04:48:35', '2025-08-25 08:58:05'),
(15, 'father_occupation', 0, '2021-06-02 04:48:35', '2025-08-25 08:58:05'),
(16, 'father_pic', 0, '2021-06-02 04:48:35', '2025-08-25 08:58:05'),
(17, 'mother_name', 0, '2021-06-02 04:48:35', '2025-08-25 08:58:05'),
(18, 'mother_phone', 0, '2021-06-02 04:48:35', '2025-08-25 08:58:05'),
(19, 'mother_occupation', 0, '2021-06-02 04:48:35', '2025-08-25 08:58:05'),
(20, 'mother_pic', 0, '2021-06-02 04:48:35', '2025-08-25 08:58:05'),
(21, 'guardian_name', 1, '2021-06-02 04:50:54', '2025-08-25 08:58:05'),
(22, 'guardian_phone', 1, '2021-06-02 04:50:54', '2025-08-25 08:58:05'),
(23, 'if_guardian_is', 1, '2021-06-02 04:50:54', '2025-08-25 08:58:05'),
(24, 'guardian_relation', 1, '2021-06-02 04:50:54', '2025-08-25 08:58:05'),
(25, 'guardian_email', 1, '2021-06-02 04:51:35', '2025-08-25 08:58:05'),
(26, 'guardian_occupation', 1, '2021-06-02 04:51:26', '2025-08-25 08:58:05'),
(27, 'guardian_address', 1, '2021-06-02 04:51:31', '2025-08-25 08:58:05'),
(28, 'bank_account_no', 0, '2021-06-02 04:48:35', '2025-08-25 08:58:05'),
(29, 'bank_name', 0, '2021-06-02 04:48:35', '2025-08-25 08:58:05'),
(30, 'ifsc_code', 0, '2021-06-02 04:48:35', '2025-08-25 08:58:05'),
(31, 'national_identification_no', 0, '2021-06-02 04:48:35', '2025-08-25 08:58:05'),
(32, 'local_identification_no', 0, '2021-06-02 04:48:35', '2025-08-25 08:58:05'),
(33, 'rte', 0, '2021-06-02 04:48:35', '2025-08-25 08:58:05'),
(34, 'previous_school_details', 0, '2021-06-02 04:48:35', '2025-08-25 08:58:05'),
(35, 'guardian_photo', 1, '2021-06-02 04:51:29', '2025-08-25 08:58:05'),
(36, 'student_note', 0, '2021-06-02 04:55:08', '2025-08-25 08:58:05'),
(37, 'measurement_date', 0, '2021-06-02 04:48:35', '2025-08-25 08:58:05'),
(38, 'student_email', 1, '2021-06-02 04:49:38', '2025-08-25 08:58:05'),
(39, 'current_address', 0, '2021-06-02 04:48:35', '2025-08-25 08:58:05'),
(40, 'permanent_address', 0, '2021-06-02 04:48:35', '2025-08-25 08:58:05'),
(41, 'upload_documents', 1, '2022-09-20 08:00:32', '2025-08-25 08:58:05');

-- --------------------------------------------------------

--
-- Table structure for table `online_admission_payment`
--

DROP TABLE IF EXISTS `online_admission_payment`;
CREATE TABLE IF NOT EXISTS `online_admission_payment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `online_admission_id` int NOT NULL,
  `paid_amount` float(10,2) NOT NULL,
  `payment_mode` varchar(50) NOT NULL,
  `payment_type` varchar(100) NOT NULL,
  `transaction_id` varchar(100) NOT NULL,
  `note` varchar(100) NOT NULL,
  `date` datetime NOT NULL,
  `processing_charge_type` varchar(255) DEFAULT NULL,
  `processing_charge_value` float(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `online_admission_id` (`online_admission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `payment_settings`
--

DROP TABLE IF EXISTS `payment_settings`;
CREATE TABLE IF NOT EXISTS `payment_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `payment_type` varchar(200) NOT NULL,
  `api_username` varchar(200) DEFAULT NULL,
  `api_secret_key` varchar(200) NOT NULL,
  `salt` varchar(200) NOT NULL,
  `api_publishable_key` varchar(200) NOT NULL,
  `api_password` varchar(200) DEFAULT NULL,
  `api_signature` varchar(200) DEFAULT NULL,
  `api_email` varchar(200) DEFAULT NULL,
  `paypal_demo` varchar(100) NOT NULL,
  `account_no` varchar(200) NOT NULL,
  `is_active` varchar(255) DEFAULT 'no',
  `gateway_mode` int NOT NULL COMMENT '0 Testing, 1 live',
  `paytm_website` varchar(255) NOT NULL,
  `paytm_industrytype` varchar(255) NOT NULL,
  `charge_type` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `charge_value` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `payslip_allowance`
--

DROP TABLE IF EXISTS `payslip_allowance`;
CREATE TABLE IF NOT EXISTS `payslip_allowance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `payslip_id` int NOT NULL,
  `allowance_type` varchar(200) NOT NULL,
  `amount` float NOT NULL,
  `staff_id` int NOT NULL,
  `cal_type` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `staff_id` (`staff_id`),
  KEY `payslip_id` (`payslip_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `permission_category`
--

DROP TABLE IF EXISTS `permission_category`;
CREATE TABLE IF NOT EXISTS `permission_category` (
  `id` int NOT NULL AUTO_INCREMENT,
  `perm_group_id` int DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `short_code` varchar(100) DEFAULT NULL,
  `enable_view` int DEFAULT '0',
  `enable_add` int DEFAULT '0',
  `enable_edit` int DEFAULT '0',
  `enable_delete` int DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_short_code` (`short_code`),
  KEY `perm_group_id` (`perm_group_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10008 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `permission_category`
--

INSERT INTO `permission_category` (`id`, `perm_group_id`, `name`, `short_code`, `enable_view`, `enable_add`, `enable_edit`, `enable_delete`, `created_at`, `updated_at`) VALUES
(1, 1, 'Student', 'student', 1, 1, 1, 1, '2019-10-24 05:42:03', '2025-08-25 08:58:06'),
(2, 1, 'Import Student', 'import_student', 1, 0, 0, 0, '2018-06-22 10:17:19', '2025-08-25 08:58:06'),
(3, 1, 'Student Categories', 'student_categories', 1, 1, 1, 1, '2018-06-22 10:17:36', '2025-08-25 08:58:06'),
(4, 1, 'Student Houses', 'student_houses', 1, 1, 1, 1, '2018-06-22 10:17:53', '2025-08-25 08:58:06'),
(5, 2, 'Collect Fees', 'collect_fees', 1, 1, 0, 1, '2018-06-22 10:21:03', '2025-08-25 08:58:06'),
(6, 2, 'Fees Carry Forward', 'fees_carry_forward', 1, 0, 0, 0, '2018-06-27 00:18:15', '2025-08-25 08:58:06'),
(7, 2, 'Fees Master', 'fees_master', 1, 1, 1, 1, '2018-06-27 00:18:57', '2025-08-25 08:58:06'),
(8, 2, 'Fees Group', 'fees_group', 1, 1, 1, 1, '2018-06-22 10:21:46', '2025-08-25 08:58:06'),
(9, 3, 'Income', 'income', 1, 1, 1, 1, '2018-06-22 10:23:21', '2025-08-25 08:58:06'),
(10, 3, 'Income Head', 'income_head', 1, 1, 1, 1, '2018-06-22 10:22:44', '2025-08-25 08:58:06'),
(11, 3, 'Search Income', 'search_income', 1, 0, 0, 0, '2018-06-22 10:23:00', '2025-08-25 08:58:06'),
(12, 4, 'Expense', 'expense', 1, 1, 1, 1, '2018-06-22 10:24:06', '2025-08-25 08:58:06'),
(13, 4, 'Expense Head', 'expense_head', 1, 1, 1, 1, '2018-06-22 10:23:47', '2025-08-25 08:58:06'),
(14, 4, 'Search Expense', 'search_expense', 1, 0, 0, 0, '2018-06-22 10:24:13', '2025-08-25 08:58:06'),
(15, 5, 'Student / Period Attendance', 'student_attendance', 1, 1, 1, 0, '2019-11-29 01:19:05', '2025-08-25 08:58:06'),
(20, 6, 'Marks Grade', 'marks_grade', 1, 1, 1, 1, '2018-06-22 10:25:25', '2025-08-25 08:58:06'),
(21, 7, 'Class Timetable', 'class_timetable', 1, 0, 1, 0, '2019-11-24 03:05:17', '2025-08-25 08:58:06'),
(23, 7, 'Subject', 'subject', 1, 1, 1, 1, '2018-06-22 10:32:17', '2025-08-25 08:58:06'),
(24, 7, 'Class', 'class', 1, 1, 1, 1, '2018-06-22 10:32:35', '2025-08-25 08:58:06'),
(25, 7, 'Section', 'section', 1, 1, 1, 1, '2018-06-22 10:31:10', '2025-08-25 08:58:06'),
(26, 7, 'Promote Student', 'promote_student', 1, 0, 0, 0, '2018-06-22 10:32:47', '2025-08-25 08:58:06'),
(27, 8, 'Upload Content', 'upload_content', 1, 1, 0, 1, '2018-06-22 10:33:19', '2025-08-25 08:58:06'),
(28, 9, 'Books List', 'books', 1, 1, 1, 1, '2019-11-24 00:37:12', '2025-08-25 08:58:06'),
(29, 9, 'Issue Return', 'issue_return', 1, 0, 0, 0, '2019-11-24 00:37:18', '2025-08-25 08:58:06'),
(30, 9, 'Add Staff Member', 'add_staff_member', 1, 0, 0, 0, '2018-07-02 11:37:00', '2025-08-25 08:58:06'),
(31, 10, 'Issue Item', 'issue_item', 1, 1, 1, 1, '2019-11-29 06:39:27', '2025-08-25 08:58:06'),
(32, 10, 'Add Item Stock', 'item_stock', 1, 1, 1, 1, '2019-11-24 00:39:17', '2025-08-25 08:58:06'),
(33, 10, 'Add Item', 'item', 1, 1, 1, 1, '2019-11-24 00:39:39', '2025-08-25 08:58:06'),
(34, 10, 'Item Store', 'store', 1, 1, 1, 1, '2019-11-24 00:40:41', '2025-08-25 08:58:06'),
(35, 10, 'Item Supplier', 'supplier', 1, 1, 1, 1, '2019-11-24 00:40:49', '2025-08-25 08:58:06'),
(37, 11, 'Routes', 'routes', 1, 1, 1, 1, '2018-06-22 10:39:17', '2025-08-25 08:58:06'),
(38, 11, 'Vehicle', 'vehicle', 1, 1, 1, 1, '2018-06-22 10:39:36', '2025-08-25 08:58:06'),
(39, 11, 'Assign Vehicle', 'assign_vehicle', 1, 1, 1, 1, '2018-06-27 04:39:20', '2025-08-25 08:58:06'),
(40, 12, 'Hostel', 'hostel', 1, 1, 1, 1, '2018-06-22 10:40:49', '2025-08-25 08:58:06'),
(41, 12, 'Room Type', 'room_type', 1, 1, 1, 1, '2018-06-22 10:40:27', '2025-08-25 08:58:06'),
(42, 12, 'Hostel Rooms', 'hostel_rooms', 1, 1, 1, 1, '2018-06-25 06:23:03', '2025-08-25 08:58:06'),
(43, 13, 'Notice Board', 'notice_board', 1, 1, 1, 1, '2018-06-22 10:41:17', '2025-08-25 08:58:06'),
(44, 13, 'Email', 'email', 1, 0, 0, 0, '2019-11-26 05:20:37', '2025-08-25 08:58:06'),
(46, 13, 'Email / SMS Log', 'email_sms_log', 1, 0, 0, 0, '2018-06-22 10:41:23', '2025-08-25 08:58:06'),
(53, 15, 'Languages', 'languages', 0, 1, 0, 1, '2021-01-23 07:09:32', '2025-08-25 08:58:06'),
(54, 15, 'General Setting', 'general_setting', 1, 0, 1, 0, '2018-07-05 09:08:35', '2025-08-25 08:58:06'),
(55, 15, 'Session Setting', 'session_setting', 1, 1, 1, 1, '2018-06-22 10:44:15', '2025-08-25 08:58:06'),
(56, 15, 'Notification Setting', 'notification_setting', 1, 0, 1, 0, '2018-07-05 09:08:41', '2025-08-25 08:58:06'),
(57, 15, 'SMS Setting', 'sms_setting', 1, 0, 1, 0, '2018-07-05 09:08:47', '2025-08-25 08:58:06'),
(58, 15, 'Email Setting', 'email_setting', 1, 0, 1, 0, '2018-07-05 09:08:51', '2025-08-25 08:58:06'),
(59, 15, 'Front CMS Setting', 'front_cms_setting', 1, 0, 1, 0, '2018-07-05 09:08:55', '2025-08-25 08:58:06'),
(60, 15, 'Payment Methods', 'payment_methods', 1, 0, 1, 0, '2018-07-05 09:08:59', '2025-08-25 08:58:06'),
(61, 16, 'Menus', 'menus', 1, 1, 0, 1, '2018-07-09 03:50:06', '2025-08-25 08:58:06'),
(62, 16, 'Media Manager', 'media_manager', 1, 1, 0, 1, '2018-07-09 03:50:26', '2025-08-25 08:58:06'),
(63, 16, 'Banner Images', 'banner_images', 1, 1, 0, 1, '2018-06-22 10:46:02', '2025-08-25 08:58:06'),
(64, 16, 'Pages', 'pages', 1, 1, 1, 1, '2018-06-22 10:46:21', '2025-08-25 08:58:06'),
(65, 16, 'Gallery', 'gallery', 1, 1, 1, 1, '2018-06-22 10:47:02', '2025-08-25 08:58:06'),
(66, 16, 'Event', 'event', 1, 1, 1, 1, '2018-06-22 10:47:20', '2025-08-25 08:58:06'),
(67, 16, 'News', 'notice', 1, 1, 1, 1, '2018-07-03 08:39:34', '2025-08-25 08:58:06'),
(68, 2, 'Fees Group Assign', 'fees_group_assign', 1, 0, 0, 0, '2018-06-22 10:20:42', '2025-08-25 08:58:06'),
(69, 2, 'Fees Type', 'fees_type', 1, 1, 1, 1, '2018-06-22 10:19:34', '2025-08-25 08:58:06'),
(70, 2, 'Fees Discount', 'fees_discount', 1, 1, 1, 1, '2018-06-22 10:20:10', '2025-08-25 08:58:06'),
(71, 2, 'Fees Discount Assign', 'fees_discount_assign', 1, 0, 0, 0, '2018-06-22 10:20:17', '2025-08-25 08:58:06'),
(73, 2, 'Search Fees Payment', 'search_fees_payment', 1, 0, 0, 0, '2018-06-22 10:20:27', '2025-08-25 08:58:06'),
(74, 2, 'Search Due Fees', 'search_due_fees', 1, 0, 0, 0, '2018-06-22 10:20:35', '2025-08-25 08:58:06'),
(77, 7, 'Assign Class Teacher', 'assign_class_teacher', 1, 1, 1, 1, '2018-06-22 10:30:52', '2025-08-25 08:58:06'),
(78, 17, 'Admission Enquiry', 'admission_enquiry', 1, 1, 1, 1, '2018-06-22 10:51:24', '2025-08-25 08:58:06'),
(79, 17, 'Follow Up Admission Enquiry', 'follow_up_admission_enquiry', 1, 1, 0, 1, '2018-06-22 10:51:39', '2025-08-25 08:58:06'),
(80, 17, 'Visitor Book', 'visitor_book', 1, 1, 1, 1, '2018-06-22 10:48:58', '2025-08-25 08:58:06'),
(81, 17, 'Phone Call Log', 'phone_call_log', 1, 1, 1, 1, '2018-06-22 10:50:57', '2025-08-25 08:58:06'),
(82, 17, 'Postal Dispatch', 'postal_dispatch', 1, 1, 1, 1, '2018-06-22 10:50:21', '2025-08-25 08:58:06'),
(83, 17, 'Postal Receive', 'postal_receive', 1, 1, 1, 1, '2018-06-22 10:50:04', '2025-08-25 08:58:06'),
(84, 17, 'Complain', 'complaint', 1, 1, 1, 1, '2018-07-03 08:40:55', '2025-08-25 08:58:06'),
(85, 17, 'Setup Front Office', 'setup_font_office', 1, 1, 1, 1, '2025-02-13 09:03:14', '2025-08-25 08:58:06'),
(86, 18, 'Staff', 'staff', 1, 1, 1, 1, '2018-06-22 10:53:31', '2025-08-25 08:58:06'),
(87, 18, 'Disable Staff', 'disable_staff', 1, 0, 0, 0, '2018-06-22 10:53:12', '2025-08-25 08:58:06'),
(88, 18, 'Staff Attendance', 'staff_attendance', 1, 1, 1, 0, '2018-06-22 10:53:10', '2025-08-25 08:58:06'),
(90, 18, 'Staff Payroll', 'staff_payroll', 1, 1, 0, 1, '2018-06-22 10:52:51', '2025-08-25 08:58:06'),
(93, 19, 'Homework', 'homework', 1, 1, 1, 1, '2018-06-22 10:53:50', '2025-08-25 08:58:06'),
(94, 19, 'Homework Evaluation', 'homework_evaluation', 1, 1, 0, 0, '2018-06-27 03:07:21', '2025-08-25 08:58:06'),
(96, 20, 'Student Certificate', 'student_certificate', 1, 1, 1, 1, '2018-07-06 10:41:07', '2025-08-25 08:58:06'),
(97, 20, 'Generate Certificate', 'generate_certificate', 1, 0, 0, 0, '2018-07-06 10:37:16', '2025-08-25 08:58:06'),
(98, 20, 'Student ID Card', 'student_id_card', 1, 1, 1, 1, '2018-07-06 10:41:28', '2025-08-25 08:58:06'),
(99, 20, 'Generate ID Card', 'generate_id_card', 1, 0, 0, 0, '2018-07-06 10:41:49', '2025-08-25 08:58:06'),
(102, 21, 'Calendar To Do List', 'calendar_to_do_list', 1, 1, 1, 1, '2018-06-22 10:54:41', '2025-08-25 08:58:06'),
(104, 10, 'Item Category', 'item_category', 1, 1, 1, 1, '2018-06-22 10:34:33', '2025-08-25 08:58:06'),
(106, 22, 'Quick Session Change', 'quick_session_change', 1, 0, 0, 0, '2018-06-22 10:54:45', '2025-08-25 08:58:06'),
(107, 1, 'Disable Student', 'disable_student', 1, 0, 0, 0, '2018-06-25 06:21:34', '2025-08-25 08:58:06'),
(108, 18, ' Approve Leave Request', 'approve_leave_request', 1, 0, 1, 1, '2020-10-05 08:56:27', '2025-08-25 08:58:06'),
(109, 18, 'Apply Leave', 'apply_leave', 1, 1, 0, 0, '2019-11-28 23:47:46', '2025-08-25 08:58:06'),
(110, 18, 'Leave Types ', 'leave_types', 1, 1, 1, 1, '2018-07-02 10:17:56', '2025-08-25 08:58:06'),
(111, 18, 'Department', 'department', 1, 1, 1, 1, '2018-06-26 03:57:07', '2025-08-25 08:58:06'),
(112, 18, 'Designation', 'designation', 1, 1, 1, 1, '2018-06-26 03:57:07', '2025-08-25 08:58:06'),
(113, 22, 'Fees Collection And Expense Monthly Chart', 'fees_collection_and_expense_monthly_chart', 1, 0, 0, 0, '2018-07-03 07:08:15', '2025-08-25 08:58:06'),
(114, 22, 'Fees Collection And Expense Yearly Chart', 'fees_collection_and_expense_yearly_chart', 1, 0, 0, 0, '2018-07-03 07:08:15', '2025-08-25 08:58:06'),
(115, 22, 'Monthly Fees Collection Widget', 'Monthly fees_collection_widget', 1, 0, 0, 0, '2018-07-03 07:13:35', '2025-08-25 08:58:06'),
(116, 22, 'Monthly Expense Widget', 'monthly_expense_widget', 1, 0, 0, 0, '2018-07-03 07:13:35', '2025-08-25 08:58:06'),
(117, 22, 'Student Count Widget', 'student_count_widget', 1, 0, 0, 0, '2018-07-03 07:13:35', '2025-08-25 08:58:06'),
(118, 22, 'Staff Role Count Widget', 'staff_role_count_widget', 1, 0, 0, 0, '2018-07-03 07:13:35', '2025-08-25 08:58:06'),
(122, 5, 'Attendance By Date', 'attendance_by_date', 1, 0, 0, 0, '2018-07-03 08:42:29', '2025-08-25 08:58:06'),
(123, 9, 'Add Student', 'add_student', 1, 0, 0, 0, '2018-07-03 08:42:29', '2025-08-25 08:58:06'),
(126, 15, 'User Status', 'user_status', 1, 0, 0, 0, '2018-07-03 08:42:29', '2025-08-25 08:58:06'),
(127, 18, 'Can See Other Users Profile', 'can_see_other_users_profile', 1, 0, 0, 0, '2018-07-03 08:42:29', '2025-08-25 08:58:06'),
(128, 1, 'Student Timeline', 'student_timeline', 1, 1, 1, 1, '2022-12-28 09:52:24', '2025-08-25 08:58:06'),
(129, 18, 'Staff Timeline', 'staff_timeline', 1, 1, 1, 1, '2022-12-28 09:52:24', '2025-08-25 08:58:06'),
(130, 15, 'Backup', 'backup', 1, 1, 0, 1, '2018-07-09 04:17:17', '2025-08-25 08:58:06'),
(131, 15, 'Restore', 'restore', 1, 0, 0, 0, '2018-07-09 04:17:17', '2025-08-25 08:58:06'),
(134, 1, 'Disable Reason', 'disable_reason', 1, 1, 1, 1, '2019-11-27 06:39:21', '2025-08-25 08:58:06'),
(135, 2, 'Fees Reminder', 'fees_reminder', 1, 0, 1, 0, '2019-10-25 00:39:49', '2025-08-25 08:58:06'),
(136, 5, 'Approve Leave', 'approve_leave', 1, 1, 1, 1, '2022-12-28 09:52:24', '2025-08-25 08:58:06'),
(137, 6, 'Exam Group', 'exam_group', 1, 1, 1, 1, '2019-10-25 01:02:34', '2025-08-25 08:58:06'),
(141, 6, 'Design Admit Card', 'design_admit_card', 1, 1, 1, 1, '2019-10-25 01:06:59', '2025-08-25 08:58:06'),
(142, 6, 'Print Admit Card', 'print_admit_card', 1, 0, 0, 0, '2019-11-23 23:57:51', '2025-08-25 08:58:06'),
(143, 6, 'Design Marksheet', 'design_marksheet', 1, 1, 1, 1, '2019-10-25 01:10:25', '2025-08-25 08:58:06'),
(144, 6, 'Print Marksheet', 'print_marksheet', 1, 0, 0, 0, '2019-10-25 01:11:02', '2025-08-25 08:58:06'),
(145, 7, 'Teachers Timetable', 'teachers_time_table', 1, 0, 0, 0, '2019-11-30 02:52:21', '2025-08-25 08:58:06'),
(146, 14, 'Student Report', 'student_report', 1, 0, 0, 0, '2019-10-25 01:27:00', '2025-08-25 08:58:06'),
(147, 14, 'Guardian Report', 'guardian_report', 1, 0, 0, 0, '2019-10-25 01:30:27', '2025-08-25 08:58:06'),
(148, 14, 'Student History', 'student_history', 1, 0, 0, 0, '2019-10-25 01:39:07', '2025-08-25 08:58:06'),
(149, 14, 'Student Login Credential Report', 'student_login_credential_report', 1, 0, 0, 0, '2019-10-25 01:39:07', '2025-08-25 08:58:06'),
(150, 14, 'Class Subject Report', 'class_subject_report', 1, 0, 0, 0, '2019-10-25 01:39:07', '2025-08-25 08:58:06'),
(151, 14, 'Admission Report', 'admission_report', 1, 0, 0, 0, '2019-10-25 01:39:07', '2025-08-25 08:58:06'),
(152, 14, 'Sibling Report', 'sibling_report', 1, 0, 0, 0, '2019-10-25 01:39:07', '2025-08-25 08:58:06'),
(153, 14, 'Homework Evaluation Report', 'homehork_evaluation_report', 1, 0, 0, 0, '2019-11-24 01:04:24', '2025-08-25 08:58:06'),
(154, 14, 'Student Profile', 'student_profile', 1, 0, 0, 0, '2019-10-25 01:39:07', '2025-08-25 08:58:06'),
(155, 14, 'Fees Statement', 'fees_statement', 1, 0, 0, 0, '2019-10-25 01:55:52', '2025-08-25 08:58:06'),
(156, 14, 'Balance Fees Report', 'balance_fees_report', 1, 0, 0, 0, '2019-10-25 01:55:52', '2025-08-25 08:58:06'),
(157, 14, 'Fees Collection Report', 'fees_collection_report', 1, 0, 0, 0, '2019-10-25 01:55:52', '2025-08-25 08:58:06'),
(158, 14, 'Online Fees Collection Report', 'online_fees_collection_report', 1, 0, 0, 0, '2019-10-25 01:55:52', '2025-08-25 08:58:06'),
(159, 14, 'Income Report', 'income_report', 1, 0, 0, 0, '2019-10-25 01:55:52', '2025-08-25 08:58:06'),
(160, 14, 'Expense Report', 'expense_report', 1, 0, 0, 0, '2019-10-25 01:55:52', '2025-08-25 08:58:06'),
(161, 14, 'PayRoll Report', 'payroll_report', 1, 0, 0, 0, '2019-10-31 00:23:22', '2025-08-25 08:58:06'),
(162, 14, 'Income Group Report', 'income_group_report', 1, 0, 0, 0, '2019-10-25 01:55:52', '2025-08-25 08:58:06'),
(163, 14, 'Expense Group Report', 'expense_group_report', 1, 0, 0, 0, '2019-10-25 01:55:52', '2025-08-25 08:58:06'),
(164, 14, 'Attendance Report', 'attendance_report', 1, 0, 0, 0, '2019-10-25 02:08:06', '2025-08-25 08:58:06'),
(165, 14, 'Staff Attendance Report', 'staff_attendance_report', 1, 0, 0, 0, '2019-10-25 02:08:06', '2025-08-25 08:58:06'),
(174, 14, 'Transport Report', 'transport_report', 1, 0, 0, 0, '2019-10-25 02:13:56', '2025-08-25 08:58:06'),
(175, 14, 'Hostel Report', 'hostel_report', 1, 0, 0, 0, '2019-11-27 06:51:53', '2025-08-25 08:58:06'),
(176, 14, 'Audit Trail Report', 'audit_trail_report', 1, 0, 0, 0, '2019-10-25 02:16:39', '2025-08-25 08:58:06'),
(177, 14, 'User Log', 'user_log', 1, 0, 0, 0, '2019-10-25 02:19:27', '2025-08-25 08:58:06'),
(178, 14, 'Book Issue Report', 'book_issue_report', 1, 0, 0, 0, '2019-10-25 02:29:04', '2025-08-25 08:58:06'),
(179, 14, 'Book Due Report', 'book_due_report', 1, 0, 0, 0, '2019-10-25 02:29:04', '2025-08-25 08:58:06'),
(180, 14, 'Book Inventory Report', 'book_inventory_report', 1, 0, 0, 0, '2019-10-25 02:29:04', '2025-08-25 08:58:06'),
(181, 14, 'Stock Report', 'stock_report', 1, 0, 0, 0, '2019-10-25 02:31:28', '2025-08-25 08:58:06'),
(182, 14, 'Add Item Report', 'add_item_report', 1, 0, 0, 0, '2019-10-25 02:31:28', '2025-08-25 08:58:06'),
(183, 14, 'Issue Item Report', 'issue_item_report', 1, 0, 0, 0, '2019-11-29 03:48:06', '2025-08-25 08:58:06'),
(185, 23, 'Online Examination', 'online_examination', 1, 1, 1, 1, '2019-11-23 23:54:50', '2025-08-25 08:58:06'),
(186, 23, 'Question Bank', 'question_bank', 1, 1, 1, 1, '2019-11-23 23:55:18', '2025-08-25 08:58:06'),
(187, 6, 'Exam Result', 'exam_result', 1, 0, 0, 0, '2019-11-23 23:58:50', '2025-08-25 08:58:06'),
(188, 7, 'Subject Group', 'subject_group', 1, 1, 1, 1, '2019-11-24 00:34:32', '2025-08-25 08:58:06'),
(189, 18, 'Teachers Rating', 'teachers_rating', 1, 0, 1, 1, '2019-11-24 03:12:54', '2025-08-25 08:58:06'),
(190, 22, 'Fees Awaiting Payment Widegts', 'fees_awaiting_payment_widegts', 1, 0, 0, 0, '2019-11-24 00:52:51', '2025-08-25 08:58:06'),
(191, 22, 'Converted Leads Widegts', 'conveted_leads_widegts', 1, 0, 0, 0, '2025-02-13 09:03:14', '2025-08-25 08:58:06'),
(192, 22, 'Fees Overview Widegts', 'fees_overview_widegts', 1, 0, 0, 0, '2019-11-24 00:57:41', '2025-08-25 08:58:06'),
(193, 22, 'Enquiry Overview Widegts', 'enquiry_overview_widegts', 1, 0, 0, 0, '2019-12-02 05:06:09', '2025-08-25 08:58:06'),
(194, 22, 'Library Overview Widegts', 'book_overview_widegts', 1, 0, 0, 0, '2019-12-01 01:13:04', '2025-08-25 08:58:06'),
(195, 22, 'Student Today Attendance Widegts', 'today_attendance_widegts', 1, 0, 0, 0, '2019-12-03 04:57:45', '2025-08-25 08:58:06'),
(196, 6, 'Marks Import', 'marks_import', 1, 0, 0, 0, '2019-11-24 01:02:11', '2025-08-25 08:58:06'),
(197, 14, 'Student Attendance Type Report', 'student_attendance_type_report', 1, 0, 0, 0, '2019-11-24 01:06:32', '2025-08-25 08:58:06'),
(198, 14, 'Exam Marks Report', 'exam_marks_report', 1, 0, 0, 0, '2019-11-24 01:11:15', '2025-08-25 08:58:06'),
(200, 14, 'Online Exam Wise Report', 'online_exam_wise_report', 1, 0, 0, 0, '2019-11-24 01:18:14', '2025-08-25 08:58:06'),
(201, 14, 'Online Exams Report', 'online_exams_report', 1, 0, 0, 0, '2019-11-29 02:48:05', '2025-08-25 08:58:06'),
(202, 14, 'Online Exams Attempt Report', 'online_exams_attempt_report', 1, 0, 0, 0, '2019-11-29 02:46:24', '2025-08-25 08:58:06'),
(203, 14, 'Online Exams Rank Report', 'online_exams_rank_report', 1, 0, 0, 0, '2019-11-24 01:22:25', '2025-08-25 08:58:06'),
(204, 14, 'Staff Report', 'staff_report', 1, 0, 0, 0, '2019-11-24 01:25:27', '2025-08-25 08:58:06'),
(205, 6, 'Exam', 'exam', 1, 1, 1, 1, '2019-11-24 04:55:48', '2025-08-25 08:58:06'),
(207, 6, 'Exam Publish', 'exam_publish', 1, 0, 0, 0, '2019-11-24 05:15:04', '2025-08-25 08:58:06'),
(208, 6, 'Link Exam', 'link_exam', 1, 0, 1, 0, '2019-11-24 05:15:04', '2025-08-25 08:58:06'),
(210, 6, 'Assign / View student', 'exam_assign_view_student', 1, 0, 1, 0, '2019-11-24 05:15:04', '2025-08-25 08:58:06'),
(211, 6, 'Exam Subject', 'exam_subject', 1, 0, 1, 0, '2019-11-24 05:15:04', '2025-08-25 08:58:06'),
(212, 6, 'Exam Marks', 'exam_marks', 1, 0, 1, 0, '2019-11-24 05:15:04', '2025-08-25 08:58:06'),
(213, 15, 'Language Switcher', 'language_switcher', 1, 0, 0, 0, '2019-11-24 05:17:11', '2025-08-25 08:58:06'),
(214, 23, 'Add Questions in Exam ', 'add_questions_in_exam', 1, 0, 1, 0, '2019-11-28 01:38:57', '2025-08-25 08:58:06'),
(215, 15, 'Custom Fields', 'custom_fields', 1, 0, 0, 0, '2019-11-29 04:08:35', '2025-08-25 08:58:06'),
(216, 15, 'System Fields', 'system_fields', 1, 0, 0, 0, '2019-11-25 00:15:01', '2025-08-25 08:58:06'),
(217, 13, 'SMS', 'sms', 1, 0, 0, 0, '2018-06-22 10:40:54', '2025-08-25 08:58:06'),
(219, 14, 'Student / Period Attendance Report', 'student_period_attendance_report', 1, 0, 0, 0, '2019-11-29 02:19:31', '2025-08-25 08:58:06'),
(220, 14, 'Biometric Attendance Log', 'biometric_attendance_log', 1, 0, 0, 0, '2019-11-27 05:59:16', '2025-08-25 08:58:06'),
(221, 14, 'Book Issue Return Report', 'book_issue_return_report', 1, 0, 0, 0, '2019-11-27 06:30:23', '2025-08-25 08:58:06'),
(222, 23, 'Assign / View Student', 'online_assign_view_student', 1, 0, 1, 0, '2019-11-28 04:20:22', '2025-08-25 08:58:06'),
(223, 14, 'Rank Report', 'rank_report', 1, 0, 0, 0, '2019-11-29 02:30:21', '2025-08-25 08:58:06'),
(224, 25, 'Chat', 'chat', 1, 0, 0, 0, '2019-11-29 04:10:28', '2025-08-25 08:58:06'),
(226, 22, 'Income Donut Graph', 'income_donut_graph', 1, 0, 0, 0, '2019-11-29 05:00:33', '2025-08-25 08:58:06'),
(227, 22, 'Expense Donut Graph', 'expense_donut_graph', 1, 0, 0, 0, '2019-11-29 05:01:10', '2025-08-25 08:58:06'),
(228, 9, 'Import Book', 'import_book', 1, 0, 0, 0, '2019-11-29 06:21:01', '2025-08-25 08:58:06'),
(229, 22, 'Staff Present Today Widegts', 'staff_present_today_widegts', 1, 0, 0, 0, '2019-11-29 06:48:00', '2025-08-25 08:58:06'),
(230, 22, 'Student Present Today Widegts', 'student_present_today_widegts', 1, 0, 0, 0, '2019-11-29 06:47:42', '2025-08-25 08:58:06'),
(231, 26, 'Multi Class Student', 'multi_class_student', 1, 1, 1, 1, '2020-10-05 08:56:27', '2025-08-25 08:58:06'),
(232, 27, 'Online Admission', 'online_admission', 1, 0, 1, 1, '2019-12-02 06:11:10', '2025-08-25 08:58:06'),
(233, 15, 'Print Header Footer', 'print_header_footer', 1, 0, 0, 0, '2020-02-12 02:02:02', '2025-08-25 08:58:06'),
(234, 28, 'Manage Alumni', 'manage_alumni', 1, 1, 1, 1, '2020-06-02 03:15:46', '2025-08-25 08:58:06'),
(235, 28, 'Events', 'events', 1, 1, 1, 1, '2020-05-28 21:48:52', '2025-08-25 08:58:06'),
(236, 29, 'Manage Lesson Plan', 'manage_lesson_plan', 1, 1, 1, 0, '2020-05-28 22:17:37', '2025-08-25 08:58:06'),
(237, 29, 'Manage Syllabus Status', 'manage_syllabus_status', 1, 0, 1, 0, '2020-05-28 22:20:11', '2025-08-25 08:58:06'),
(238, 29, 'Lesson', 'lesson', 1, 1, 1, 1, '2020-05-28 22:20:11', '2025-08-25 08:58:06'),
(239, 29, 'Topic', 'topic', 1, 1, 1, 1, '2020-05-28 22:20:11', '2025-08-25 08:58:06'),
(240, 14, 'Syllabus Status Report', 'syllabus_status_report', 1, 0, 0, 0, '2020-05-28 23:17:54', '2025-08-25 08:58:06'),
(241, 14, 'Teacher Syllabus Status Report', 'teacher_syllabus_status_report', 1, 0, 0, 0, '2020-05-28 23:17:54', '2025-08-25 08:58:06'),
(242, 14, 'Alumni Report', 'alumni_report', 1, 0, 0, 0, '2020-06-07 23:59:54', '2025-08-25 08:58:06'),
(243, 15, 'Student Profile Update', 'student_profile_update', 1, 0, 0, 0, '2020-08-21 05:36:33', '2025-08-25 08:58:06'),
(244, 14, 'Student Gender Ratio Report', 'student_gender_ratio_report', 1, 0, 0, 0, '2020-08-22 12:37:51', '2025-08-25 08:58:06'),
(245, 14, 'Student Teacher Ratio Report', 'student_teacher_ratio_report', 1, 0, 0, 0, '2020-08-22 12:42:27', '2025-08-25 08:58:06'),
(246, 14, 'Daily Attendance Report', 'daily_attendance_report', 1, 0, 0, 0, '2020-08-22 12:43:16', '2025-08-25 08:58:06'),
(247, 23, 'Import Question', 'import_question', 1, 0, 0, 0, '2019-11-23 18:25:18', '2025-08-25 08:58:06'),
(248, 20, 'Staff ID Card', 'staff_id_card', 1, 1, 1, 1, '2018-07-06 10:41:28', '2025-08-25 08:58:06'),
(249, 20, 'Generate Staff ID Card', 'generate_staff_id_card', 1, 0, 0, 0, '2018-07-06 10:41:49', '2025-08-25 08:58:06'),
(250, 19, 'Daily Assignment', 'daily_assignment', 1, 0, 0, 0, '2022-03-02 07:28:23', '2025-08-25 08:58:06'),
(251, 6, 'Marks Division', 'marks_division', 1, 1, 1, 1, '2022-07-01 15:24:16', '2025-08-25 08:58:06'),
(252, 13, 'Schedule Email SMS Log', 'schedule_email_sms_log', 1, 0, 1, 0, '2022-07-09 11:25:16', '2025-08-25 08:58:06'),
(253, 13, 'Login Credentials Send', 'login_credentials_send', 1, 0, 0, 0, '2022-07-01 15:46:10', '2025-08-25 08:58:06'),
(254, 13, 'Email Template', 'email_template', 1, 1, 1, 1, '2022-07-01 15:46:10', '2025-08-25 08:58:06'),
(255, 13, 'SMS Template', 'sms_template', 1, 1, 1, 1, '2022-07-01 15:46:10', '2025-08-25 08:58:06'),
(256, 14, 'Balance Fees Report With Remark', 'balance_fees_report_with_remark', 1, 0, 0, 0, '2019-10-25 01:55:52', '2025-08-25 08:58:06'),
(257, 14, 'Balance Fees Statement', 'balance_fees_statement', 1, 0, 0, 0, '2019-10-25 01:55:52', '2025-08-25 08:58:06'),
(258, 14, 'Daily Collection Report', 'daily_collection_report', 1, 0, 0, 0, '2019-10-25 01:55:52', '2025-08-25 08:58:06'),
(259, 11, 'Fees Master', 'transport_fees_master', 1, 0, 1, 0, '2022-07-05 09:29:19', '2025-08-25 08:58:06'),
(260, 11, 'Pickup Point', 'pickup_point', 1, 1, 1, 1, '2022-07-04 09:50:08', '2025-08-25 08:58:06'),
(261, 11, 'Route Pickup Point', 'route_pickup_point', 1, 1, 1, 1, '2022-07-04 09:50:08', '2025-08-25 08:58:06'),
(262, 11, 'Student Transport Fees', 'student_transport_fees', 1, 1, 1, 0, '2022-07-05 10:15:55', '2025-08-25 08:58:06'),
(263, 29, 'Comments', 'lesson_plan_comments', 1, 1, 0, 1, '2020-05-28 22:20:11', '2025-08-25 08:58:06'),
(264, 15, 'Sidebar Menu', 'sidebar_menu', 1, 0, 0, 0, '2022-07-11 12:01:17', '2025-08-25 08:58:06'),
(265, 15, 'Currency', 'currency', 1, 0, 0, 0, '2020-08-21 05:36:33', '2025-08-25 08:58:06'),
(266, 6, 'Exam Schedule', 'exam_schedule', 1, 0, 0, 0, '2019-11-23 23:58:50', '2025-08-25 08:58:06'),
(267, 6, 'Generate Rank', 'generate_rank', 1, 0, 0, 0, '2019-11-24 05:15:04', '2025-08-25 08:58:06'),
(268, 8, 'Content Type', 'content_type', 1, 1, 1, 1, '2022-07-08 05:18:54', '2025-08-25 08:58:06'),
(269, 8, 'Content Share List', 'content_share_list', 1, 0, 0, 1, '2022-07-08 05:18:58', '2025-08-25 08:58:06'),
(270, 8, 'Video Tutorial', 'video_tutorial', 1, 1, 1, 1, '2022-07-08 05:19:01', '2025-08-25 08:58:06'),
(271, 15, 'Currency Switcher', 'currency_switcher', 1, 0, 0, 0, '2019-11-24 05:17:11', '2025-08-25 08:58:06'),
(272, 2, 'Offline Bank Payments', 'offline_bank_payments', 1, 0, 0, 0, '2018-06-27 00:18:15', '2025-08-25 08:58:06'),
(273, 29, 'Copy Old Lessons', 'copy_old_lesson', 1, 0, 0, 0, '2020-05-28 22:20:11', '2025-08-25 08:58:06'),
(274, 30, 'Annual Calendar', 'annual_calendar', 1, 1, 1, 1, '2020-05-28 22:20:11', '2025-08-25 08:58:06'),
(275, 30, 'Holiday Type', 'holiday_type', 1, 1, 1, 1, '2024-10-14 12:31:14', '2025-08-25 08:58:06'),
(276, 14, 'Online Admission Report', 'online_admission_report', 1, 0, 0, 0, '2020-08-22 12:42:27', '2025-08-25 08:58:06'),
(277, 31, 'Download CV', 'download_cv', 1, 0, 0, 0, '2024-12-10 11:06:30', '2025-08-25 08:58:06'),
(278, 31, 'Build CV', 'build_cv', 1, 1, 0, 1, '2024-12-13 07:05:10', '2025-08-25 08:58:06'),
(279, 31, 'Setting', 'download_cv_setting', 1, 0, 0, 0, '2024-12-10 11:06:30', '2025-08-25 08:58:06'),
(280, 22, 'Student Head Count Widget', 'student_head_count_widget', 1, 0, 0, 0, '2018-07-03 07:13:35', '2025-08-25 08:58:06'),
(281, 22, 'Staff Approved Leave Widegts', 'staff_approved_leave_widegts', 1, 0, 0, 0, '2018-07-03 07:13:35', '2025-08-25 08:58:06'),
(282, 22, 'Student Approved Leave Widegts', 'student_approved_leave_widegts', 1, 0, 0, 0, '2018-07-03 07:13:35', '2025-08-25 08:58:06'),
(10001, 1000, 'Overview', 'multi_branch_overview', 1, 0, 0, 0, '2025-08-15 00:07:36', '2025-08-25 09:08:28'),
(10002, 1000, 'Daily Collection Report', 'multi_branch_daily_collection_report', 1, 0, 0, 0, '2025-08-14 23:57:02', '2025-08-25 09:08:28'),
(10003, 1000, 'Payroll Report', 'multi_branch_payroll', 1, 0, 0, 0, '2025-08-16 06:19:48', '2025-08-25 09:08:28'),
(10004, 1000, 'Income Report', 'multi_branch_income_report', 1, 0, 0, 0, '2025-08-15 00:07:36', '2025-08-25 09:08:28'),
(10005, 1000, 'Expense Report', 'multi_branch_expense_report', 1, 0, 0, 0, '2025-08-15 00:02:27', '2025-08-25 09:08:28'),
(10006, 1000, 'User Log Report', 'multi_branch_user_log_report', 1, 0, 0, 0, '2025-08-15 00:02:27', '2025-08-25 09:08:28'),
(10007, 1000, 'Setting', 'multi_branch_setting', 1, 0, 0, 0, '2025-08-15 00:07:36', '2025-08-25 09:08:28');

-- --------------------------------------------------------

--
-- Table structure for table `permission_group`
--

DROP TABLE IF EXISTS `permission_group`;
CREATE TABLE IF NOT EXISTS `permission_group` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `short_code` varchar(100) NOT NULL,
  `is_active` int DEFAULT '0',
  `system` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1001 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `permission_group`
--

INSERT INTO `permission_group` (`id`, `name`, `short_code`, `is_active`, `system`, `created_at`, `updated_at`) VALUES
(1, 'Student Information', 'student_information', 1, 1, '2019-03-15 09:30:22', '2025-08-25 08:58:06'),
(2, 'Fees Collection', 'fees_collection', 1, 0, '2020-06-11 00:51:35', '2025-08-25 08:58:06'),
(3, 'Income', 'income', 1, 0, '2020-06-01 01:57:39', '2025-08-25 08:58:06'),
(4, 'Expense', 'expense', 1, 0, '2019-03-15 09:06:22', '2025-08-25 08:58:06'),
(5, 'Student Attendance', 'student_attendance', 1, 0, '2018-07-02 07:48:08', '2025-08-25 08:58:06'),
(6, 'Examination', 'examination', 1, 0, '2018-07-11 02:49:08', '2025-08-25 08:58:06'),
(7, 'Academics', 'academics', 1, 1, '2018-07-02 07:25:43', '2025-08-25 08:58:06'),
(8, 'Download Center', 'download_center', 1, 0, '2018-07-02 07:49:29', '2025-08-25 08:58:06'),
(9, 'Library', 'library', 1, 0, '2018-06-28 11:13:14', '2025-08-25 08:58:06'),
(10, 'Inventory', 'inventory', 1, 0, '2018-06-27 00:48:58', '2025-08-25 08:58:06'),
(11, 'Transport', 'transport', 1, 0, '2018-06-27 07:51:26', '2025-08-25 08:58:06'),
(12, 'Hostel', 'hostel', 1, 0, '2018-07-02 07:49:32', '2025-08-25 08:58:06'),
(13, 'Communicate', 'communicate', 1, 0, '2018-07-02 07:50:00', '2025-08-25 08:58:06'),
(14, 'Reports', 'reports', 1, 1, '2018-06-27 03:40:22', '2025-08-25 08:58:06'),
(15, 'System Settings', 'system_settings', 1, 1, '2018-06-27 03:40:28', '2025-08-25 08:58:06'),
(16, 'Front CMS', 'front_cms', 1, 0, '2018-07-10 05:16:54', '2025-08-25 08:58:06'),
(17, 'Front Office', 'front_office', 1, 0, '2018-06-27 03:45:30', '2025-08-25 08:58:06'),
(18, 'Human Resource', 'human_resource', 1, 1, '2018-06-27 03:41:02', '2025-08-25 08:58:06'),
(19, 'Homework', 'homework', 1, 0, '2018-06-27 00:49:38', '2025-08-25 08:58:06'),
(20, 'Certificate', 'certificate', 1, 0, '2018-06-27 07:51:29', '2025-08-25 08:58:06'),
(21, 'Calendar To Do List', 'calendar_to_do_list', 1, 0, '2019-03-15 09:06:25', '2025-08-25 08:58:06'),
(22, 'Dashboard and Widgets', 'dashboard_and_widgets', 1, 1, '2018-06-27 03:41:17', '2025-08-25 08:58:06'),
(23, 'Online Examination', 'online_examination', 1, 0, '2020-06-01 02:25:36', '2025-08-25 08:58:06'),
(25, 'Chat', 'chat', 1, 0, '2019-11-23 23:54:04', '2025-08-25 08:58:06'),
(26, 'Multi Class', 'multi_class', 1, 0, '2019-11-27 12:14:14', '2025-08-25 08:58:06'),
(27, 'Online Admission', 'online_admission', 1, 0, '2019-11-27 02:42:13', '2025-08-25 08:58:06'),
(28, 'Alumni', 'alumni', 1, 0, '2020-05-29 00:26:38', '2025-08-25 08:58:06'),
(29, 'Lesson Plan', 'lesson_plan', 1, 0, '2020-06-07 05:38:30', '2025-08-25 08:58:06'),
(30, 'Annual Calendar', 'annual_calendar', 1, 0, '2024-10-22 10:45:56', '2025-08-25 08:58:06'),
(31, 'Student CV', 'student_cv', 1, 0, '2024-12-13 11:54:57', '2025-08-25 08:58:06'),
(1000, 'Multi Branch', 'multi_branch', 1, 0, '2025-08-17 05:53:36', '2025-08-25 09:08:27');

-- --------------------------------------------------------

--
-- Table structure for table `permission_student`
--

DROP TABLE IF EXISTS `permission_student`;
CREATE TABLE IF NOT EXISTS `permission_student` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `short_code` varchar(100) NOT NULL,
  `system` int NOT NULL,
  `student` int NOT NULL,
  `parent` int NOT NULL,
  `group_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `permission_student`
--

INSERT INTO `permission_student` (`id`, `name`, `short_code`, `system`, `student`, `parent`, `group_id`, `created_at`, `updated_at`) VALUES
(1, 'Fees', 'fees', 0, 1, 1, 2, '2020-06-11 00:51:35', '2025-08-25 08:58:06'),
(2, 'Class Timetable', 'class_timetable', 1, 1, 1, 7, '2020-05-30 19:57:50', '2025-08-25 08:58:06'),
(3, 'Homework', 'homework', 0, 1, 1, 19, '2020-06-01 02:49:14', '2025-08-25 08:58:06'),
(4, 'Download Center', 'download_center', 0, 1, 1, 8, '2020-06-01 02:52:49', '2025-08-25 08:58:06'),
(5, 'Attendance', 'attendance', 0, 1, 1, 5, '2020-06-01 02:57:18', '2025-08-25 08:58:06'),
(7, 'Examinations', 'examinations', 0, 1, 1, 6, '2020-06-01 02:59:50', '2025-08-25 08:58:06'),
(8, 'Notice Board', 'notice_board', 0, 1, 1, 13, '2020-06-01 03:00:35', '2025-08-25 08:58:06'),
(11, 'Library', 'library', 0, 1, 1, 9, '2020-06-01 03:02:37', '2025-08-25 08:58:06'),
(12, 'Transport Routes', 'transport_routes', 0, 1, 1, 11, '2020-06-01 03:51:30', '2025-08-25 08:58:06'),
(13, 'Hostel Rooms', 'hostel_rooms', 0, 1, 1, 12, '2020-06-01 03:52:27', '2025-08-25 08:58:06'),
(14, 'Calendar To Do List', 'calendar_to_do_list', 0, 1, 1, 21, '2020-06-01 03:53:18', '2025-08-25 08:58:06'),
(15, 'Online Examination', 'online_examination', 0, 1, 1, 23, '2020-06-11 05:20:01', '2025-08-25 08:58:06'),
(16, 'Teachers Rating', 'teachers_rating', 0, 1, 1, NULL, '2022-12-28 09:52:28', '2025-08-25 08:58:06'),
(17, 'Chat', 'chat', 0, 1, 1, 25, '2020-06-01 04:53:06', '2025-08-25 08:58:06'),
(18, 'Multi Class', 'multi_class', 1, 1, 1, 26, '2020-05-30 19:56:52', '2025-08-25 08:58:06'),
(19, 'Lesson Plan', 'lesson_plan', 0, 1, 1, 29, '2020-06-07 05:38:30', '2025-08-25 08:58:06'),
(20, 'Syllabus Status', 'syllabus_status', 0, 1, 1, 29, '2020-06-07 05:38:30', '2025-08-25 08:58:06'),
(23, 'Apply Leave', 'apply_leave', 0, 1, 1, NULL, '2022-12-28 09:52:28', '2025-08-25 08:58:06'),
(24, 'Visitor Book', 'visitor_book', 0, 1, 1, NULL, '2022-10-10 11:45:18', '2025-08-25 08:58:06'),
(25, 'Student Timeline', 'student_timeline', 0, 1, 1, NULL, '2022-10-11 04:50:29', '2025-08-25 08:58:06');

-- --------------------------------------------------------

--
-- Table structure for table `pickup_point`
--

DROP TABLE IF EXISTS `pickup_point`;
CREATE TABLE IF NOT EXISTS `pickup_point` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `latitude` varchar(100) DEFAULT NULL,
  `longitude` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `print_headerfooter`
--

DROP TABLE IF EXISTS `print_headerfooter`;
CREATE TABLE IF NOT EXISTS `print_headerfooter` (
  `id` int NOT NULL AUTO_INCREMENT,
  `print_type` varchar(255) NOT NULL,
  `header_image` varchar(255) NOT NULL,
  `footer_content` text NOT NULL,
  `created_by` int NOT NULL,
  `entry_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `print_headerfooter`
--

INSERT INTO `print_headerfooter` (`id`, `print_type`, `header_image`, `footer_content`, `created_by`, `entry_date`, `created_at`, `updated_at`) VALUES
(1, 'staff_payslip', 'header_image.jpg', 'This payslip is computer generated hence no signature is required.', 1, '2020-02-28 15:41:08', '2022-12-28 09:52:24', '2025-08-25 08:58:06'),
(2, 'student_receipt', 'header_image.jpg', 'This receipt is computer generated hence no signature is required.', 1, '2020-02-28 15:40:58', '2022-12-28 09:52:24', '2025-08-25 08:58:06'),
(3, 'online_admission_receipt', 'header_image.jpg', 'This receipt is for online admission  computer ffffffff generated hence no signature is required.', 1, '2021-05-27 12:50:24', '2022-12-28 09:52:24', '2025-08-25 08:58:06'),
(4, 'online_exam', '1655913577-198504634062b33c698fde1!online-exam.jpg', 'This receipt is for online exam computer  generated hence no signature is required.', 1, '2022-08-30 12:58:46', '2022-09-08 17:28:34', '2025-08-25 08:58:06'),
(5, 'general_purpose', 'header_image.jpg', '<h1>\r\n\r\n</h1><p>footer text here ....</p>', 1, '2025-02-05 07:26:06', '2022-09-08 17:28:34', '2025-08-25 08:58:06');

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

DROP TABLE IF EXISTS `questions`;
CREATE TABLE IF NOT EXISTS `questions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `staff_id` int DEFAULT NULL,
  `subject_id` int DEFAULT NULL,
  `question_type` varchar(100) NOT NULL,
  `level` varchar(10) NOT NULL,
  `class_id` int NOT NULL,
  `section_id` int DEFAULT NULL,
  `class_section_id` int DEFAULT NULL,
  `question` text,
  `opt_a` text,
  `opt_b` text,
  `opt_c` text,
  `opt_d` text,
  `opt_e` text,
  `correct` text,
  `descriptive_word_limit` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `subject_id` (`subject_id`),
  KEY `staff_id` (`staff_id`),
  KEY `class_id` (`class_id`),
  KEY `section_id` (`section_id`),
  KEY `class_section_id` (`class_section_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `read_notification`
--

DROP TABLE IF EXISTS `read_notification`;
CREATE TABLE IF NOT EXISTS `read_notification` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int DEFAULT NULL,
  `parent_id` int DEFAULT NULL,
  `staff_id` int DEFAULT NULL,
  `notification_id` int DEFAULT NULL,
  `is_active` varchar(255) DEFAULT 'no',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `notification_id` (`notification_id`),
  KEY `staff_id` (`staff_id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `reference`
--

DROP TABLE IF EXISTS `reference`;
CREATE TABLE IF NOT EXISTS `reference` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reference` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `resume_additional_fields_settings`
--

DROP TABLE IF EXISTS `resume_additional_fields_settings`;
CREATE TABLE IF NOT EXISTS `resume_additional_fields_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `status` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `resume_additional_fields_settings`
--

INSERT INTO `resume_additional_fields_settings` (`id`, `name`, `status`, `created_at`, `updated_at`) VALUES
(1, 'work_experience', 1, '2024-12-06 06:17:04', '2025-08-25 08:58:06'),
(2, 'education_qalification', 1, '2024-12-06 06:17:04', '2025-08-25 08:58:06'),
(3, 'technical_skills', 1, '2024-12-06 06:17:04', '2025-08-25 08:58:06'),
(4, 'reference', 1, '2024-12-06 06:17:04', '2025-08-25 08:58:06'),
(5, 'other_details', 1, '2024-12-06 06:17:04', '2025-08-25 08:58:06');

-- --------------------------------------------------------

--
-- Table structure for table `resume_settings_fields`
--

DROP TABLE IF EXISTS `resume_settings_fields`;
CREATE TABLE IF NOT EXISTS `resume_settings_fields` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(250) DEFAULT NULL,
  `status` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `resume_settings_fields`
--

INSERT INTO `resume_settings_fields` (`id`, `name`, `status`, `created_at`, `updated_at`) VALUES
(1, 'middlename', 1, '2025-02-14 06:40:33', '2025-02-14 06:40:33'),
(2, 'lastname', 1, '2025-02-14 06:40:34', '2025-02-14 06:40:34'),
(3, 'gender', 1, '2025-02-14 06:40:35', '2025-02-14 06:40:35'),
(4, 'dob', 1, '2025-02-14 06:40:36', '2025-02-14 06:40:36'),
(5, 'category', 1, '2025-02-14 06:40:37', '2025-02-14 06:40:37'),
(6, 'religion', 1, '2025-02-14 06:40:38', '2025-02-14 06:40:38'),
(7, 'cast', 1, '2025-02-14 06:40:40', '2025-02-14 06:40:40'),
(8, 'mobile_no', 1, '2025-02-14 06:40:41', '2025-02-14 06:40:41'),
(9, 'student_email', 1, '2025-02-14 06:40:41', '2025-02-14 06:40:41'),
(10, 'student_photo', 1, '2025-02-14 06:40:42', '2025-02-14 06:40:42'),
(11, 'is_blood_group', 1, '2025-02-14 06:40:43', '2025-02-14 06:40:43'),
(12, 'height', 1, '2025-02-14 06:40:44', '2025-02-14 06:40:44'),
(13, 'weight', 1, '2025-02-14 06:40:46', '2025-02-14 06:40:46'),
(14, 'father_name', 1, '2025-02-14 06:40:47', '2025-02-14 06:40:47'),
(15, 'father_phone', 1, '2025-02-14 06:40:48', '2025-02-14 06:40:48'),
(16, 'father_occupation', 1, '2025-02-14 06:40:49', '2025-02-14 06:40:49'),
(17, 'father_pic', 1, '2025-02-14 06:40:49', '2025-02-14 06:40:49'),
(18, 'mother_name', 1, '2025-02-14 06:40:50', '2025-02-14 06:40:50'),
(19, 'mother_phone', 1, '2025-02-14 06:40:51', '2025-02-14 06:40:51'),
(20, 'mother_occupation', 1, '2025-02-14 06:40:52', '2025-02-14 06:40:52'),
(21, 'mother_pic', 1, '2025-02-14 06:40:52', '2025-02-14 06:40:52'),
(22, 'if_guardian_is', 1, '2025-02-14 06:40:53', '2025-02-14 06:42:17'),
(23, 'guardian_name', 1, '2025-02-14 06:41:31', '2025-02-14 06:42:17'),
(24, 'guardian_relation', 1, '2025-02-14 06:41:31', '2025-02-14 06:42:17'),
(25, 'guardian_email', 1, '2025-02-14 06:41:32', '2025-02-14 06:42:17'),
(26, 'guardian_photo', 1, '2025-02-14 06:41:33', '2025-02-14 06:42:17'),
(27, 'guardian_phone', 1, '2025-02-14 06:41:34', '2025-02-14 06:42:17'),
(28, 'guardian_occupation', 1, '2025-02-14 06:41:34', '2025-02-14 06:42:17'),
(29, 'guardian_address', 1, '2025-02-14 06:41:35', '2025-02-14 06:42:17'),
(30, 'current_address', 1, '2025-02-14 06:41:36', '2025-02-14 06:41:36'),
(31, 'permanent_address', 1, '2025-02-14 06:41:37', '2025-02-14 06:41:37'),
(32, 'national_identification_no', 1, '2025-02-14 06:41:37', '2025-02-14 06:41:37'),
(33, 'local_identification_no', 1, '2025-02-14 06:41:38', '2025-02-14 06:41:38'),
(34, 'personal_details', 1, '2025-02-14 06:41:39', '2025-02-14 06:41:39'),
(35, 'parent_guardian_detail', 1, '2025-02-14 06:41:41', '2025-02-14 06:41:41');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `slug` varchar(150) DEFAULT NULL,
  `is_active` int DEFAULT '0',
  `is_system` int NOT NULL DEFAULT '0',
  `is_superadmin` int NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `slug`, `is_active`, `is_system`, `is_superadmin`, `created_at`, `updated_at`) VALUES
(1, 'Admin', NULL, 0, 1, 0, '2018-06-30 15:39:11', '0000-00-00 00:00:00'),
(2, 'Teacher', NULL, 0, 1, 0, '2018-06-30 15:39:14', '0000-00-00 00:00:00'),
(3, 'Accountant', NULL, 0, 1, 0, '2018-06-30 15:39:17', '0000-00-00 00:00:00'),
(4, 'Librarian', NULL, 0, 1, 0, '2018-06-30 15:39:21', '0000-00-00 00:00:00'),
(6, 'Receptionist', NULL, 0, 1, 0, '2018-07-02 05:39:03', '0000-00-00 00:00:00'),
(7, 'Super Admin', NULL, 0, 1, 1, '2018-07-11 14:11:29', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `roles_permissions`
--

DROP TABLE IF EXISTS `roles_permissions`;
CREATE TABLE IF NOT EXISTS `roles_permissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `role_id` int DEFAULT NULL,
  `perm_cat_id` int DEFAULT NULL,
  `can_view` int DEFAULT NULL,
  `can_add` int DEFAULT NULL,
  `can_edit` int DEFAULT NULL,
  `can_delete` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `role_id` (`role_id`),
  KEY `perm_cat_id` (`perm_cat_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1493 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `roles_permissions`
--

INSERT INTO `roles_permissions` (`id`, `role_id`, `perm_cat_id`, `can_view`, `can_add`, `can_edit`, `can_delete`, `created_at`, `updated_at`) VALUES
(11, 1, 78, 1, 1, 1, 1, '2018-07-03 00:49:43', '2025-08-25 08:58:06'),
(23, 1, 12, 1, 1, 1, 1, '2018-07-06 09:45:38', '2025-08-25 08:58:06'),
(24, 1, 13, 1, 1, 1, 1, '2018-07-06 09:48:28', '2025-08-25 08:58:06'),
(26, 1, 15, 1, 1, 1, 0, '2019-11-27 23:47:28', '2025-08-25 08:58:06'),
(31, 1, 21, 1, 0, 1, 0, '2019-11-26 04:51:15', '2025-08-25 08:58:06'),
(34, 1, 24, 1, 1, 1, 1, '2019-11-28 06:35:20', '2025-08-25 08:58:06'),
(43, 1, 32, 1, 1, 1, 1, '2018-07-06 10:22:05', '2025-08-25 08:58:06'),
(44, 1, 33, 1, 1, 1, 1, '2018-07-06 10:22:29', '2025-08-25 08:58:06'),
(45, 1, 34, 1, 1, 1, 1, '2018-07-06 10:23:59', '2025-08-25 08:58:06'),
(46, 1, 35, 1, 1, 1, 1, '2018-07-06 10:24:34', '2025-08-25 08:58:06'),
(47, 1, 104, 1, 1, 1, 1, '2018-07-06 10:23:08', '2025-08-25 08:58:06'),
(48, 1, 37, 1, 1, 1, 1, '2018-07-06 10:25:30', '2025-08-25 08:58:06'),
(49, 1, 38, 1, 1, 1, 1, '2018-07-09 05:15:27', '2025-08-25 08:58:06'),
(61, 1, 55, 1, 1, 1, 1, '2018-07-02 09:24:16', '2025-08-25 08:58:06'),
(67, 1, 61, 1, 1, 0, 1, '2018-07-09 05:59:19', '2025-08-25 08:58:06'),
(68, 1, 62, 1, 1, 0, 1, '2018-07-09 05:59:19', '2025-08-25 08:58:06'),
(69, 1, 63, 1, 1, 0, 1, '2018-07-09 03:51:38', '2025-08-25 08:58:06'),
(70, 1, 64, 1, 1, 1, 1, '2018-07-09 03:02:19', '2025-08-25 08:58:06'),
(71, 1, 65, 1, 1, 1, 1, '2018-07-09 03:11:21', '2025-08-25 08:58:06'),
(72, 1, 66, 1, 1, 1, 1, '2018-07-09 03:13:09', '2025-08-25 08:58:06'),
(73, 1, 67, 1, 1, 1, 1, '2018-07-09 03:14:47', '2025-08-25 08:58:06'),
(74, 1, 79, 1, 1, 0, 1, '2019-11-30 01:32:51', '2025-08-25 08:58:06'),
(75, 1, 80, 1, 1, 1, 1, '2018-07-06 09:41:23', '2025-08-25 08:58:06'),
(76, 1, 81, 1, 1, 1, 1, '2018-07-06 09:41:23', '2025-08-25 08:58:06'),
(78, 1, 83, 1, 1, 1, 1, '2018-07-06 09:41:23', '2025-08-25 08:58:06'),
(79, 1, 84, 1, 1, 1, 1, '2018-07-06 09:41:23', '2025-08-25 08:58:06'),
(80, 1, 85, 1, 1, 1, 1, '2018-07-12 00:16:00', '2025-08-25 08:58:06'),
(94, 1, 82, 1, 1, 1, 1, '2018-07-06 09:41:23', '2025-08-25 08:58:06'),
(120, 1, 39, 1, 1, 1, 1, '2018-07-06 10:26:28', '2025-08-25 08:58:06'),
(156, 1, 9, 1, 1, 1, 1, '2019-11-27 23:45:46', '2025-08-25 08:58:06'),
(157, 1, 10, 1, 1, 1, 1, '2019-11-27 23:45:46', '2025-08-25 08:58:06'),
(159, 1, 40, 1, 1, 1, 1, '2019-11-30 00:49:39', '2025-08-25 08:58:06'),
(160, 1, 41, 1, 1, 1, 1, '2019-12-02 05:43:41', '2025-08-25 08:58:06'),
(161, 1, 42, 1, 1, 1, 1, '2019-11-30 00:49:39', '2025-08-25 08:58:06'),
(169, 1, 27, 1, 1, 0, 1, '2019-11-29 06:15:37', '2025-08-25 08:58:06'),
(178, 1, 54, 1, 0, 1, 0, '2018-07-05 09:09:22', '2025-08-25 08:58:06'),
(179, 1, 56, 1, 0, 1, 0, '2019-11-30 00:49:54', '2025-08-25 08:58:06'),
(180, 1, 57, 1, 0, 1, 0, '2019-11-30 01:32:51', '2025-08-25 08:58:06'),
(181, 1, 58, 1, 0, 1, 0, '2019-11-30 01:32:51', '2025-08-25 08:58:06'),
(182, 1, 59, 1, 0, 1, 0, '2019-11-30 01:32:51', '2025-08-25 08:58:06'),
(183, 1, 60, 1, 0, 1, 0, '2019-11-30 00:59:57', '2025-08-25 08:58:06'),
(201, 1, 14, 1, 0, 0, 0, '2018-07-02 11:22:03', '2025-08-25 08:58:06'),
(204, 1, 26, 1, 0, 0, 0, '2018-07-02 11:32:05', '2025-08-25 08:58:06'),
(206, 1, 29, 1, 0, 0, 0, '2018-07-02 11:43:54', '2025-08-25 08:58:06'),
(207, 1, 30, 1, 0, 0, 0, '2018-07-02 11:43:54', '2025-08-25 08:58:06'),
(208, 1, 31, 1, 1, 1, 1, '2019-11-30 01:32:51', '2025-08-25 08:58:06'),
(222, 1, 1, 1, 1, 1, 1, '2019-11-27 22:55:06', '2025-08-25 08:58:06'),
(307, 1, 126, 1, 0, 0, 0, '2018-07-03 09:26:13', '2025-08-25 08:58:06'),
(315, 1, 123, 1, 0, 0, 0, '2018-07-03 10:27:03', '2025-08-25 08:58:06'),
(369, 1, 102, 1, 1, 1, 1, '2019-12-02 05:02:15', '2025-08-25 08:58:06'),
(435, 1, 96, 1, 1, 1, 1, '2018-07-09 01:03:54', '2025-08-25 08:58:06'),
(461, 1, 97, 1, 0, 0, 0, '2018-07-09 01:00:16', '2025-08-25 08:58:06'),
(464, 1, 86, 1, 1, 1, 1, '2019-11-28 06:39:19', '2025-08-25 08:58:06'),
(474, 1, 130, 1, 1, 0, 1, '2018-07-09 10:56:36', '2025-08-25 08:58:06'),
(476, 1, 131, 1, 0, 0, 0, '2018-07-09 04:53:32', '2025-08-25 08:58:06'),
(557, 6, 82, 1, 1, 1, 1, '2019-12-01 01:48:28', '2025-08-25 08:58:06'),
(558, 6, 83, 1, 1, 1, 1, '2019-12-01 01:49:08', '2025-08-25 08:58:06'),
(559, 6, 84, 1, 1, 1, 1, '2019-12-01 01:49:59', '2025-08-25 08:58:06'),
(575, 6, 44, 1, 0, 0, 0, '2018-07-10 07:35:33', '2025-08-25 08:58:06'),
(576, 6, 46, 1, 0, 0, 0, '2018-07-10 07:35:33', '2025-08-25 08:58:06'),
(578, 6, 102, 1, 1, 1, 1, '2019-12-01 01:52:27', '2025-08-25 08:58:06'),
(625, 1, 28, 1, 1, 1, 1, '2019-11-29 06:19:18', '2025-08-25 08:58:06'),
(634, 4, 102, 1, 1, 1, 1, '2019-12-01 01:03:00', '2025-08-25 08:58:06'),
(669, 1, 145, 1, 0, 0, 0, '2019-11-26 04:51:15', '2025-08-25 08:58:06'),
(677, 1, 153, 1, 0, 0, 0, '2019-11-01 02:28:24', '2025-08-25 08:58:06'),
(720, 1, 216, 1, 0, 0, 0, '2019-11-26 05:24:12', '2025-08-25 08:58:06'),
(728, 1, 185, 1, 1, 1, 1, '2019-11-28 02:50:33', '2025-08-25 08:58:06'),
(729, 1, 186, 1, 1, 1, 1, '2019-11-28 02:49:07', '2025-08-25 08:58:06'),
(730, 1, 214, 1, 0, 1, 0, '2019-11-28 01:47:53', '2025-08-25 08:58:06'),
(732, 1, 198, 1, 0, 0, 0, '2019-11-26 05:24:30', '2025-08-25 08:58:06'),
(734, 1, 200, 1, 0, 0, 0, '2019-11-26 05:24:30', '2025-08-25 08:58:06'),
(735, 1, 201, 1, 0, 0, 0, '2019-11-26 05:24:30', '2025-08-25 08:58:06'),
(736, 1, 202, 1, 0, 0, 0, '2019-11-26 05:24:30', '2025-08-25 08:58:06'),
(737, 1, 203, 1, 0, 0, 0, '2019-11-26 05:24:30', '2025-08-25 08:58:06'),
(747, 1, 2, 1, 0, 0, 0, '2019-11-27 22:56:08', '2025-08-25 08:58:06'),
(748, 1, 3, 1, 1, 1, 1, '2019-11-27 22:56:32', '2025-08-25 08:58:06'),
(749, 1, 4, 1, 1, 1, 1, '2019-11-27 22:56:48', '2025-08-25 08:58:06'),
(751, 1, 128, 0, 1, 0, 1, '2019-11-27 22:57:01', '2025-08-25 08:58:06'),
(754, 1, 134, 1, 1, 1, 1, '2019-11-27 23:18:21', '2025-08-25 08:58:06'),
(755, 1, 5, 1, 1, 0, 1, '2019-11-27 23:35:07', '2025-08-25 08:58:06'),
(756, 1, 6, 1, 0, 0, 0, '2019-11-27 23:35:25', '2025-08-25 08:58:06'),
(757, 1, 7, 1, 1, 1, 1, '2019-11-27 23:36:35', '2025-08-25 08:58:06'),
(758, 1, 8, 1, 1, 1, 1, '2019-11-27 23:37:27', '2025-08-25 08:58:06'),
(760, 1, 68, 1, 0, 0, 0, '2019-11-27 23:38:06', '2025-08-25 08:58:06'),
(761, 1, 69, 1, 1, 1, 1, '2019-11-27 23:39:06', '2025-08-25 08:58:06'),
(762, 1, 70, 1, 1, 1, 1, '2019-11-27 23:39:41', '2025-08-25 08:58:06'),
(763, 1, 71, 1, 0, 0, 0, '2019-11-27 23:39:59', '2025-08-25 08:58:06'),
(765, 1, 73, 1, 0, 0, 0, '2019-11-27 23:43:15', '2025-08-25 08:58:06'),
(766, 1, 74, 1, 0, 0, 0, '2019-11-27 23:43:55', '2025-08-25 08:58:06'),
(768, 1, 11, 1, 0, 0, 0, '2019-11-27 23:45:46', '2025-08-25 08:58:06'),
(769, 1, 122, 1, 0, 0, 0, '2019-11-27 23:52:43', '2025-08-25 08:58:06'),
(771, 1, 136, 1, 0, 0, 0, '2019-11-27 23:55:36', '2025-08-25 08:58:06'),
(772, 1, 20, 1, 1, 1, 1, '2019-11-28 04:06:44', '2025-08-25 08:58:06'),
(773, 1, 137, 1, 1, 1, 1, '2019-11-28 00:46:14', '2025-08-25 08:58:06'),
(774, 1, 141, 1, 1, 1, 1, '2019-11-28 00:59:42', '2025-08-25 08:58:06'),
(775, 1, 142, 1, 0, 0, 0, '2019-11-27 23:56:12', '2025-08-25 08:58:06'),
(776, 1, 143, 1, 1, 1, 1, '2019-11-28 00:59:42', '2025-08-25 08:58:06'),
(777, 1, 144, 1, 0, 0, 0, '2019-11-27 23:56:12', '2025-08-25 08:58:06'),
(778, 1, 187, 1, 0, 0, 0, '2019-11-27 23:56:12', '2025-08-25 08:58:06'),
(779, 1, 196, 1, 0, 0, 0, '2019-11-27 23:56:12', '2025-08-25 08:58:06'),
(781, 1, 207, 1, 0, 0, 0, '2019-11-27 23:56:12', '2025-08-25 08:58:06'),
(782, 1, 208, 1, 0, 1, 0, '2019-11-28 00:10:22', '2025-08-25 08:58:06'),
(783, 1, 210, 1, 0, 1, 0, '2019-11-28 00:34:40', '2025-08-25 08:58:06'),
(784, 1, 211, 1, 0, 1, 0, '2019-11-28 00:38:23', '2025-08-25 08:58:06'),
(785, 1, 212, 1, 0, 1, 0, '2019-11-28 00:42:15', '2025-08-25 08:58:06'),
(786, 1, 205, 1, 1, 1, 1, '2019-11-28 00:42:15', '2025-08-25 08:58:06'),
(787, 1, 222, 1, 0, 1, 0, '2019-11-28 01:36:36', '2025-08-25 08:58:06'),
(788, 1, 77, 1, 1, 1, 1, '2019-11-28 06:22:10', '2025-08-25 08:58:06'),
(789, 1, 188, 1, 1, 1, 1, '2019-11-28 06:26:16', '2025-08-25 08:58:06'),
(790, 1, 23, 1, 1, 1, 1, '2019-11-28 06:34:20', '2025-08-25 08:58:06'),
(791, 1, 25, 1, 1, 1, 1, '2019-11-28 06:36:20', '2025-08-25 08:58:06'),
(792, 1, 127, 1, 0, 0, 0, '2019-11-28 06:41:25', '2025-08-25 08:58:06'),
(794, 1, 88, 1, 1, 1, 0, '2019-11-28 06:43:04', '2025-08-25 08:58:06'),
(795, 1, 90, 1, 1, 0, 1, '2019-11-28 06:46:22', '2025-08-25 08:58:06'),
(796, 1, 108, 1, 0, 1, 1, '2021-01-23 07:09:32', '2025-08-25 08:58:06'),
(797, 1, 109, 1, 1, 0, 0, '2019-11-28 23:38:11', '2025-08-25 08:58:06'),
(798, 1, 110, 1, 1, 1, 1, '2019-11-28 23:49:29', '2025-08-25 08:58:06'),
(799, 1, 111, 1, 1, 1, 1, '2019-11-28 23:49:57', '2025-08-25 08:58:06'),
(800, 1, 112, 1, 1, 1, 1, '2019-11-28 23:49:57', '2025-08-25 08:58:06'),
(801, 1, 129, 0, 1, 0, 1, '2019-11-28 23:49:57', '2025-08-25 08:58:06'),
(802, 1, 189, 1, 0, 1, 1, '2019-11-28 23:59:22', '2025-08-25 08:58:06'),
(810, 2, 1, 1, 1, 1, 1, '2019-11-30 02:54:16', '2025-08-25 08:58:06'),
(817, 1, 93, 1, 1, 1, 1, '2019-11-29 00:56:14', '2025-08-25 08:58:06'),
(825, 1, 87, 1, 0, 0, 0, '2019-11-29 00:56:14', '2025-08-25 08:58:06'),
(829, 1, 94, 1, 1, 0, 0, '2019-11-29 00:57:57', '2025-08-25 08:58:06'),
(836, 1, 146, 1, 0, 0, 0, '2019-11-29 01:13:28', '2025-08-25 08:58:06'),
(837, 1, 147, 1, 0, 0, 0, '2019-11-29 01:13:28', '2025-08-25 08:58:06'),
(838, 1, 148, 1, 0, 0, 0, '2019-11-29 01:13:28', '2025-08-25 08:58:06'),
(839, 1, 149, 1, 0, 0, 0, '2019-11-29 01:13:28', '2025-08-25 08:58:06'),
(840, 1, 150, 1, 0, 0, 0, '2019-11-29 01:13:28', '2025-08-25 08:58:06'),
(841, 1, 151, 1, 0, 0, 0, '2019-11-29 01:13:28', '2025-08-25 08:58:06'),
(842, 1, 152, 1, 0, 0, 0, '2019-11-29 01:13:28', '2025-08-25 08:58:06'),
(843, 1, 154, 1, 0, 0, 0, '2019-11-29 01:13:28', '2025-08-25 08:58:06'),
(862, 1, 155, 1, 0, 0, 0, '2019-11-29 02:07:30', '2025-08-25 08:58:06'),
(863, 1, 156, 1, 0, 0, 0, '2019-11-29 02:07:52', '2025-08-25 08:58:06'),
(864, 1, 157, 1, 0, 0, 0, '2019-11-29 02:08:05', '2025-08-25 08:58:06'),
(874, 1, 158, 1, 0, 0, 0, '2019-11-29 02:14:03', '2025-08-25 08:58:06'),
(875, 1, 159, 1, 0, 0, 0, '2019-11-29 02:14:31', '2025-08-25 08:58:06'),
(876, 1, 160, 1, 0, 0, 0, '2019-11-29 02:14:44', '2025-08-25 08:58:06'),
(878, 1, 162, 1, 0, 0, 0, '2019-11-29 02:15:58', '2025-08-25 08:58:06'),
(879, 1, 163, 1, 0, 0, 0, '2019-11-29 02:16:19', '2025-08-25 08:58:06'),
(882, 1, 164, 1, 0, 0, 0, '2019-11-29 02:25:17', '2025-08-25 08:58:06'),
(884, 1, 165, 1, 0, 0, 0, '2019-11-29 02:25:30', '2025-08-25 08:58:06'),
(886, 1, 197, 1, 0, 0, 0, '2019-11-29 02:25:48', '2025-08-25 08:58:06'),
(887, 1, 219, 1, 0, 0, 0, '2019-11-29 02:26:05', '2025-08-25 08:58:06'),
(889, 1, 220, 1, 0, 0, 0, '2019-11-29 02:26:22', '2025-08-25 08:58:06'),
(932, 1, 204, 1, 0, 0, 0, '2019-11-29 03:43:27', '2025-08-25 08:58:06'),
(933, 1, 221, 1, 0, 0, 0, '2019-11-29 03:45:04', '2025-08-25 08:58:06'),
(934, 1, 178, 1, 0, 0, 0, '2019-11-29 03:45:16', '2025-08-25 08:58:06'),
(935, 1, 179, 1, 0, 0, 0, '2019-11-29 03:45:33', '2025-08-25 08:58:06'),
(936, 1, 161, 1, 0, 0, 0, '2019-11-29 03:45:48', '2025-08-25 08:58:06'),
(937, 1, 180, 1, 0, 0, 0, '2019-11-29 03:45:48', '2025-08-25 08:58:06'),
(938, 1, 181, 1, 0, 0, 0, '2019-11-29 03:49:33', '2025-08-25 08:58:06'),
(939, 1, 182, 1, 0, 0, 0, '2019-11-29 03:49:45', '2025-08-25 08:58:06'),
(940, 1, 183, 1, 0, 0, 0, '2019-11-29 03:49:56', '2025-08-25 08:58:06'),
(941, 1, 174, 1, 0, 0, 0, '2019-11-29 03:50:53', '2025-08-25 08:58:06'),
(943, 1, 176, 1, 0, 0, 0, '2019-11-29 03:52:10', '2025-08-25 08:58:06'),
(944, 1, 177, 1, 0, 0, 0, '2019-11-29 03:52:22', '2025-08-25 08:58:06'),
(945, 1, 53, 0, 1, 0, 1, '2021-01-23 07:09:32', '2025-08-25 08:58:06'),
(946, 1, 215, 1, 0, 0, 0, '2019-11-29 04:01:37', '2025-08-25 08:58:06'),
(947, 1, 213, 1, 0, 0, 0, '2019-11-29 04:07:45', '2025-08-25 08:58:06'),
(974, 1, 224, 1, 0, 0, 0, '2019-11-29 04:32:52', '2025-08-25 08:58:06'),
(1026, 1, 135, 1, 0, 1, 0, '2019-11-29 06:02:12', '2025-08-25 08:58:06'),
(1031, 1, 228, 1, 0, 0, 0, '2019-11-29 06:21:16', '2025-08-25 08:58:06'),
(1083, 1, 175, 1, 0, 0, 0, '2019-11-30 00:37:24', '2025-08-25 08:58:06'),
(1086, 1, 43, 1, 1, 1, 1, '2019-11-30 00:49:39', '2025-08-25 08:58:06'),
(1087, 1, 44, 1, 0, 0, 0, '2019-11-30 00:49:39', '2025-08-25 08:58:06'),
(1088, 1, 46, 1, 0, 0, 0, '2019-11-30 00:49:39', '2025-08-25 08:58:06'),
(1089, 1, 217, 1, 0, 0, 0, '2019-11-30 00:49:39', '2025-08-25 08:58:06'),
(1090, 1, 98, 1, 1, 1, 1, '2019-11-30 01:32:51', '2025-08-25 08:58:06'),
(1091, 1, 99, 1, 0, 0, 0, '2019-11-30 01:30:18', '2025-08-25 08:58:06'),
(1092, 1, 223, 1, 0, 0, 0, '2019-11-30 01:32:51', '2025-08-25 08:58:06'),
(1103, 2, 205, 1, 1, 1, 1, '2019-11-30 01:56:04', '2025-08-25 08:58:06'),
(1105, 2, 23, 1, 0, 0, 0, '2019-11-30 01:56:04', '2025-08-25 08:58:06'),
(1106, 2, 24, 1, 0, 0, 0, '2019-11-30 01:56:04', '2025-08-25 08:58:06'),
(1107, 2, 25, 1, 0, 0, 0, '2019-11-30 01:56:04', '2025-08-25 08:58:06'),
(1108, 2, 77, 1, 0, 0, 0, '2019-11-30 01:56:04', '2025-08-25 08:58:06'),
(1119, 2, 117, 1, 0, 0, 0, '2019-11-30 01:56:04', '2025-08-25 08:58:06'),
(1123, 3, 8, 1, 1, 1, 1, '2019-11-30 06:46:18', '2025-08-25 08:58:06'),
(1125, 3, 69, 1, 1, 1, 1, '2019-11-30 07:00:49', '2025-08-25 08:58:06'),
(1126, 3, 70, 1, 1, 1, 1, '2019-11-30 07:04:46', '2025-08-25 08:58:06'),
(1130, 3, 9, 1, 1, 1, 1, '2019-11-30 07:14:54', '2025-08-25 08:58:06'),
(1131, 3, 10, 1, 1, 1, 1, '2019-11-30 07:16:02', '2025-08-25 08:58:06'),
(1134, 3, 35, 1, 1, 1, 1, '2019-11-30 07:25:04', '2025-08-25 08:58:06'),
(1135, 3, 104, 1, 1, 1, 1, '2019-11-30 07:25:53', '2025-08-25 08:58:06'),
(1140, 3, 41, 1, 1, 1, 1, '2019-11-30 07:37:13', '2025-08-25 08:58:06'),
(1141, 3, 42, 1, 1, 1, 1, '2019-11-30 07:37:46', '2025-08-25 08:58:06'),
(1142, 3, 43, 1, 1, 1, 1, '2019-11-30 07:42:06', '2025-08-25 08:58:06'),
(1151, 3, 87, 1, 0, 0, 0, '2019-11-30 02:23:13', '2025-08-25 08:58:06'),
(1152, 3, 88, 1, 1, 1, 0, '2019-11-30 02:23:13', '2025-08-25 08:58:06'),
(1153, 3, 90, 1, 1, 0, 1, '2019-11-30 02:23:13', '2025-08-25 08:58:06'),
(1154, 3, 108, 1, 0, 1, 0, '2019-11-30 02:23:13', '2025-08-25 08:58:06'),
(1155, 3, 109, 1, 1, 0, 0, '2019-11-30 02:23:13', '2025-08-25 08:58:06'),
(1156, 3, 110, 1, 1, 1, 1, '2019-11-30 02:23:13', '2025-08-25 08:58:06'),
(1157, 3, 111, 1, 1, 1, 1, '2019-11-30 02:23:13', '2025-08-25 08:58:06'),
(1158, 3, 112, 1, 1, 1, 1, '2019-11-30 02:23:13', '2025-08-25 08:58:06'),
(1159, 3, 127, 1, 0, 0, 0, '2019-11-30 02:23:13', '2025-08-25 08:58:06'),
(1160, 3, 129, 0, 1, 0, 1, '2019-11-30 02:23:13', '2025-08-25 08:58:06'),
(1161, 3, 102, 1, 1, 1, 1, '2019-11-30 02:23:13', '2025-08-25 08:58:06'),
(1162, 3, 106, 1, 0, 0, 0, '2019-11-30 02:23:13', '2025-08-25 08:58:06'),
(1163, 3, 113, 1, 0, 0, 0, '2019-11-30 02:23:13', '2025-08-25 08:58:06'),
(1164, 3, 114, 1, 0, 0, 0, '2019-11-30 02:23:13', '2025-08-25 08:58:06'),
(1165, 3, 115, 1, 0, 0, 0, '2019-11-30 02:23:13', '2025-08-25 08:58:06'),
(1166, 3, 116, 1, 0, 0, 0, '2019-11-30 02:23:13', '2025-08-25 08:58:06'),
(1167, 3, 117, 1, 0, 0, 0, '2019-11-30 02:23:13', '2025-08-25 08:58:06'),
(1168, 3, 118, 1, 0, 0, 0, '2019-11-30 02:23:13', '2025-08-25 08:58:06'),
(1171, 2, 142, 1, 0, 0, 0, '2019-11-30 02:36:17', '2025-08-25 08:58:06'),
(1172, 2, 144, 1, 0, 0, 0, '2019-11-30 02:36:17', '2025-08-25 08:58:06'),
(1179, 2, 212, 1, 0, 1, 0, '2019-11-30 02:36:17', '2025-08-25 08:58:06'),
(1183, 2, 148, 1, 0, 0, 0, '2019-11-30 02:36:17', '2025-08-25 08:58:06'),
(1184, 2, 149, 1, 0, 0, 0, '2019-11-30 02:36:17', '2025-08-25 08:58:06'),
(1185, 2, 150, 1, 0, 0, 0, '2019-11-30 02:36:17', '2025-08-25 08:58:06'),
(1186, 2, 151, 1, 0, 0, 0, '2019-11-30 02:36:17', '2025-08-25 08:58:06'),
(1187, 2, 152, 1, 0, 0, 0, '2019-11-30 02:36:17', '2025-08-25 08:58:06'),
(1188, 2, 153, 1, 0, 0, 0, '2019-11-30 02:36:17', '2025-08-25 08:58:06'),
(1189, 2, 154, 1, 0, 0, 0, '2019-11-30 02:36:17', '2025-08-25 08:58:06'),
(1190, 2, 197, 1, 0, 0, 0, '2019-11-30 02:36:17', '2025-08-25 08:58:06'),
(1191, 2, 198, 1, 0, 0, 0, '2019-11-30 02:36:17', '2025-08-25 08:58:06'),
(1193, 2, 200, 1, 0, 0, 0, '2019-11-30 02:36:17', '2025-08-25 08:58:06'),
(1194, 2, 201, 1, 0, 0, 0, '2019-11-30 02:36:17', '2025-08-25 08:58:06'),
(1195, 2, 202, 1, 0, 0, 0, '2019-11-30 02:36:17', '2025-08-25 08:58:06'),
(1196, 2, 203, 1, 0, 0, 0, '2019-11-30 02:36:17', '2025-08-25 08:58:06'),
(1197, 2, 219, 1, 0, 0, 0, '2019-11-30 02:36:17', '2025-08-25 08:58:06'),
(1198, 2, 223, 1, 0, 0, 0, '2019-11-30 02:36:17', '2025-08-25 08:58:06'),
(1199, 2, 213, 1, 0, 0, 0, '2019-11-30 02:36:17', '2025-08-25 08:58:06'),
(1201, 2, 230, 1, 0, 0, 0, '2019-11-30 02:36:17', '2025-08-25 08:58:06'),
(1204, 2, 214, 1, 0, 1, 0, '2019-11-30 02:36:17', '2025-08-25 08:58:06'),
(1206, 2, 224, 1, 0, 0, 0, '2019-11-30 02:36:17', '2025-08-25 08:58:06'),
(1208, 2, 2, 1, 0, 0, 0, '2019-11-30 02:55:45', '2025-08-25 08:58:06'),
(1210, 2, 143, 1, 1, 1, 1, '2019-11-30 02:57:28', '2025-08-25 08:58:06'),
(1211, 2, 145, 1, 0, 0, 0, '2019-11-30 02:57:28', '2025-08-25 08:58:06'),
(1214, 2, 3, 1, 1, 1, 1, '2019-11-30 03:03:18', '2025-08-25 08:58:06'),
(1216, 2, 4, 1, 1, 1, 1, '2019-11-30 03:32:56', '2025-08-25 08:58:06'),
(1218, 2, 128, 0, 1, 0, 1, '2019-11-30 03:37:44', '2025-08-25 08:58:06'),
(1220, 3, 135, 1, 0, 1, 0, '2019-11-30 07:08:56', '2025-08-25 08:58:06'),
(1231, 3, 190, 1, 0, 0, 0, '2019-11-30 03:44:02', '2025-08-25 08:58:06'),
(1232, 3, 192, 1, 0, 0, 0, '2019-11-30 03:44:02', '2025-08-25 08:58:06'),
(1233, 3, 226, 1, 0, 0, 0, '2019-11-30 03:44:02', '2025-08-25 08:58:06'),
(1234, 3, 227, 1, 0, 0, 0, '2019-11-30 03:44:02', '2025-08-25 08:58:06'),
(1235, 3, 224, 1, 0, 0, 0, '2019-11-30 03:44:02', '2025-08-25 08:58:06'),
(1236, 2, 15, 1, 1, 1, 0, '2019-11-30 03:54:25', '2025-08-25 08:58:06'),
(1239, 2, 122, 1, 0, 0, 0, '2019-11-30 03:57:48', '2025-08-25 08:58:06'),
(1240, 2, 136, 1, 0, 0, 0, '2019-11-30 03:57:48', '2025-08-25 08:58:06'),
(1242, 6, 217, 1, 0, 0, 0, '2019-11-30 04:00:13', '2025-08-25 08:58:06'),
(1243, 6, 224, 1, 0, 0, 0, '2019-11-30 04:00:13', '2025-08-25 08:58:06'),
(1245, 2, 20, 1, 1, 1, 1, '2019-11-30 04:01:28', '2025-08-25 08:58:06'),
(1246, 2, 137, 1, 1, 1, 1, '2019-11-30 04:02:40', '2025-08-25 08:58:06'),
(1248, 2, 141, 1, 1, 1, 1, '2019-11-30 04:04:04', '2025-08-25 08:58:06'),
(1250, 2, 187, 1, 0, 0, 0, '2019-11-30 04:11:19', '2025-08-25 08:58:06'),
(1252, 2, 207, 1, 0, 0, 0, '2019-11-30 04:21:21', '2025-08-25 08:58:06'),
(1253, 2, 208, 1, 0, 1, 0, '2019-11-30 04:22:00', '2025-08-25 08:58:06'),
(1255, 2, 210, 1, 0, 1, 0, '2019-11-30 04:22:58', '2025-08-25 08:58:06'),
(1256, 2, 211, 1, 0, 1, 0, '2019-11-30 04:24:03', '2025-08-25 08:58:06'),
(1257, 2, 21, 1, 0, 0, 0, '2019-11-30 04:32:59', '2025-08-25 08:58:06'),
(1259, 2, 188, 1, 0, 0, 0, '2019-11-30 04:34:35', '2025-08-25 08:58:06'),
(1260, 2, 27, 1, 0, 0, 0, '2019-11-30 04:36:13', '2025-08-25 08:58:06'),
(1262, 2, 43, 1, 1, 1, 1, '2019-11-30 04:39:42', '2025-08-25 08:58:06'),
(1263, 2, 44, 1, 0, 0, 0, '2019-11-30 04:41:43', '2025-08-25 08:58:06'),
(1264, 2, 46, 1, 0, 0, 0, '2019-11-30 04:41:43', '2025-08-25 08:58:06'),
(1265, 2, 217, 1, 0, 0, 0, '2019-11-30 04:41:43', '2025-08-25 08:58:06'),
(1266, 2, 146, 1, 0, 0, 0, '2019-11-30 04:46:35', '2025-08-25 08:58:06'),
(1267, 2, 147, 1, 0, 0, 0, '2019-11-30 04:47:37', '2025-08-25 08:58:06'),
(1269, 2, 164, 1, 0, 0, 0, '2019-11-30 04:51:04', '2025-08-25 08:58:06'),
(1271, 2, 109, 1, 1, 0, 0, '2019-11-30 05:03:37', '2025-08-25 08:58:06'),
(1272, 2, 93, 1, 1, 1, 1, '2019-11-30 05:07:25', '2025-08-25 08:58:06'),
(1273, 2, 94, 1, 1, 0, 0, '2019-11-30 05:07:42', '2025-08-25 08:58:06'),
(1275, 2, 102, 1, 1, 1, 1, '2019-11-30 05:11:22', '2025-08-25 08:58:06'),
(1277, 2, 196, 1, 0, 0, 0, '2019-11-30 05:15:01', '2025-08-25 08:58:06'),
(1278, 2, 195, 1, 0, 0, 0, '2019-11-30 05:19:08', '2025-08-25 08:58:06'),
(1279, 2, 185, 1, 1, 1, 1, '2019-11-30 05:21:44', '2025-08-25 08:58:06'),
(1280, 2, 186, 1, 1, 1, 1, '2019-11-30 05:22:43', '2025-08-25 08:58:06'),
(1281, 2, 222, 1, 0, 1, 0, '2019-11-30 05:24:30', '2025-08-25 08:58:06'),
(1283, 3, 5, 1, 1, 0, 1, '2019-11-30 06:43:04', '2025-08-25 08:58:06'),
(1284, 3, 6, 1, 0, 0, 0, '2019-11-30 06:43:29', '2025-08-25 08:58:06'),
(1285, 3, 7, 1, 1, 1, 1, '2019-11-30 06:44:39', '2025-08-25 08:58:06'),
(1286, 3, 68, 1, 0, 0, 0, '2019-11-30 06:46:58', '2025-08-25 08:58:06'),
(1287, 3, 71, 1, 0, 0, 0, '2019-11-30 07:05:41', '2025-08-25 08:58:06'),
(1288, 3, 73, 1, 0, 0, 0, '2019-11-30 07:05:59', '2025-08-25 08:58:06'),
(1289, 3, 74, 1, 0, 0, 0, '2019-11-30 07:06:08', '2025-08-25 08:58:06'),
(1290, 3, 11, 1, 0, 0, 0, '2019-11-30 07:16:37', '2025-08-25 08:58:06'),
(1291, 3, 12, 1, 1, 1, 1, '2019-11-30 07:19:29', '2025-08-25 08:58:06'),
(1292, 3, 13, 1, 1, 1, 1, '2019-11-30 07:22:27', '2025-08-25 08:58:06'),
(1294, 3, 14, 1, 0, 0, 0, '2019-11-30 07:22:55', '2025-08-25 08:58:06'),
(1295, 3, 31, 1, 1, 1, 1, '2019-12-02 06:30:37', '2025-08-25 08:58:06'),
(1297, 3, 37, 1, 1, 1, 1, '2019-11-30 07:28:09', '2025-08-25 08:58:06'),
(1298, 3, 38, 1, 1, 1, 1, '2019-11-30 07:29:02', '2025-08-25 08:58:06'),
(1299, 3, 39, 1, 1, 1, 1, '2019-11-30 07:30:07', '2025-08-25 08:58:06'),
(1300, 3, 40, 1, 1, 1, 1, '2019-11-30 07:32:43', '2025-08-25 08:58:06'),
(1301, 3, 44, 1, 0, 0, 0, '2019-11-30 07:44:09', '2025-08-25 08:58:06'),
(1302, 3, 46, 1, 0, 0, 0, '2019-11-30 07:44:09', '2025-08-25 08:58:06'),
(1303, 3, 217, 1, 0, 0, 0, '2019-11-30 07:44:09', '2025-08-25 08:58:06'),
(1304, 3, 155, 1, 0, 0, 0, '2019-11-30 07:44:32', '2025-08-25 08:58:06'),
(1305, 3, 156, 1, 0, 0, 0, '2019-11-30 07:45:18', '2025-08-25 08:58:06'),
(1306, 3, 157, 1, 0, 0, 0, '2019-11-30 07:45:42', '2025-08-25 08:58:06'),
(1307, 3, 158, 1, 0, 0, 0, '2019-11-30 07:46:07', '2025-08-25 08:58:06'),
(1308, 3, 159, 1, 0, 0, 0, '2019-11-30 07:46:21', '2025-08-25 08:58:06'),
(1309, 3, 160, 1, 0, 0, 0, '2019-11-30 07:46:33', '2025-08-25 08:58:06'),
(1313, 3, 161, 1, 0, 0, 0, '2019-11-30 07:48:26', '2025-08-25 08:58:06'),
(1314, 3, 162, 1, 0, 0, 0, '2019-11-30 07:48:48', '2025-08-25 08:58:06'),
(1315, 3, 163, 1, 0, 0, 0, '2019-11-30 07:48:48', '2025-08-25 08:58:06'),
(1316, 3, 164, 1, 0, 0, 0, '2019-11-30 07:49:47', '2025-08-25 08:58:06'),
(1317, 3, 165, 1, 0, 0, 0, '2019-11-30 07:49:47', '2025-08-25 08:58:06'),
(1318, 3, 174, 1, 0, 0, 0, '2019-11-30 07:49:47', '2025-08-25 08:58:06'),
(1319, 3, 175, 1, 0, 0, 0, '2019-11-30 07:49:59', '2025-08-25 08:58:06'),
(1320, 3, 181, 1, 0, 0, 0, '2019-11-30 07:50:08', '2025-08-25 08:58:06'),
(1321, 3, 86, 1, 1, 1, 1, '2019-11-30 07:54:08', '2025-08-25 08:58:06'),
(1322, 4, 28, 1, 1, 1, 1, '2019-12-01 00:52:39', '2025-08-25 08:58:06'),
(1324, 4, 29, 1, 0, 0, 0, '2019-12-01 00:53:46', '2025-08-25 08:58:06'),
(1325, 4, 30, 1, 0, 0, 0, '2019-12-01 00:53:59', '2025-08-25 08:58:06'),
(1326, 4, 123, 1, 0, 0, 0, '2019-12-01 00:54:26', '2025-08-25 08:58:06'),
(1327, 4, 228, 1, 0, 0, 0, '2019-12-01 00:54:39', '2025-08-25 08:58:06'),
(1328, 4, 43, 1, 1, 1, 1, '2019-12-01 00:58:05', '2025-08-25 08:58:06'),
(1332, 4, 44, 1, 0, 0, 0, '2019-12-01 00:59:16', '2025-08-25 08:58:06'),
(1333, 4, 46, 1, 0, 0, 0, '2019-12-01 00:59:16', '2025-08-25 08:58:06'),
(1334, 4, 217, 1, 0, 0, 0, '2019-12-01 00:59:16', '2025-08-25 08:58:06'),
(1335, 4, 178, 1, 0, 0, 0, '2019-12-01 00:59:59', '2025-08-25 08:58:06'),
(1336, 4, 179, 1, 0, 0, 0, '2019-12-01 01:00:11', '2025-08-25 08:58:06'),
(1337, 4, 180, 1, 0, 0, 0, '2019-12-01 01:00:29', '2025-08-25 08:58:06'),
(1338, 4, 221, 1, 0, 0, 0, '2019-12-01 01:00:46', '2025-08-25 08:58:06'),
(1339, 4, 86, 1, 0, 0, 0, '2019-12-01 01:01:02', '2025-08-25 08:58:06'),
(1341, 4, 106, 1, 0, 0, 0, '2019-12-01 01:05:21', '2025-08-25 08:58:06'),
(1342, 1, 107, 1, 0, 0, 0, '2019-12-01 01:06:44', '2025-08-25 08:58:06'),
(1343, 4, 117, 1, 0, 0, 0, '2019-12-01 01:10:20', '2025-08-25 08:58:06'),
(1344, 4, 194, 1, 0, 0, 0, '2019-12-01 01:11:35', '2025-08-25 08:58:06'),
(1348, 4, 230, 1, 0, 0, 0, '2019-12-01 01:19:15', '2025-08-25 08:58:06'),
(1350, 6, 1, 1, 0, 0, 0, '2019-12-01 01:35:32', '2025-08-25 08:58:06'),
(1351, 6, 21, 1, 0, 0, 0, '2019-12-01 01:36:29', '2025-08-25 08:58:06'),
(1352, 6, 23, 1, 0, 0, 0, '2019-12-01 01:36:45', '2025-08-25 08:58:06'),
(1353, 6, 24, 1, 0, 0, 0, '2019-12-01 01:37:05', '2025-08-25 08:58:06'),
(1354, 6, 25, 1, 0, 0, 0, '2019-12-01 01:37:34', '2025-08-25 08:58:06'),
(1355, 6, 77, 1, 0, 0, 0, '2019-12-01 01:38:08', '2025-08-25 08:58:06'),
(1356, 6, 188, 1, 0, 0, 0, '2019-12-01 01:38:45', '2025-08-25 08:58:06'),
(1357, 6, 43, 1, 1, 1, 1, '2019-12-01 01:40:44', '2025-08-25 08:58:06'),
(1358, 6, 78, 1, 1, 1, 1, '2019-12-01 01:43:04', '2025-08-25 08:58:06'),
(1360, 6, 79, 1, 1, 0, 1, '2019-12-01 01:44:39', '2025-08-25 08:58:06'),
(1361, 6, 80, 1, 1, 1, 1, '2019-12-01 01:45:08', '2025-08-25 08:58:06'),
(1362, 6, 81, 1, 1, 1, 1, '2019-12-01 01:47:50', '2025-08-25 08:58:06'),
(1363, 6, 85, 1, 1, 1, 1, '2019-12-01 01:50:43', '2025-08-25 08:58:06'),
(1364, 6, 86, 1, 0, 0, 0, '2019-12-01 01:51:10', '2025-08-25 08:58:06'),
(1365, 6, 106, 1, 0, 0, 0, '2019-12-01 01:52:55', '2025-08-25 08:58:06'),
(1366, 6, 117, 1, 0, 0, 0, '2019-12-01 01:53:08', '2025-08-25 08:58:06'),
(1394, 1, 106, 1, 0, 0, 0, '2019-12-02 05:20:33', '2025-08-25 08:58:06'),
(1395, 1, 113, 1, 0, 0, 0, '2019-12-02 05:20:59', '2025-08-25 08:58:06'),
(1396, 1, 114, 1, 0, 0, 0, '2019-12-02 05:21:34', '2025-08-25 08:58:06'),
(1397, 1, 115, 1, 0, 0, 0, '2019-12-02 05:21:34', '2025-08-25 08:58:06'),
(1398, 1, 116, 1, 0, 0, 0, '2019-12-02 05:21:54', '2025-08-25 08:58:06'),
(1399, 1, 117, 1, 0, 0, 0, '2019-12-02 05:22:04', '2025-08-25 08:58:06'),
(1400, 1, 118, 1, 0, 0, 0, '2019-12-02 05:22:20', '2025-08-25 08:58:06'),
(1402, 1, 191, 1, 0, 0, 0, '2019-12-02 05:23:34', '2025-08-25 08:58:06'),
(1403, 1, 192, 1, 0, 0, 0, '2019-12-02 05:23:47', '2025-08-25 08:58:06'),
(1404, 1, 193, 1, 0, 0, 0, '2019-12-02 05:23:58', '2025-08-25 08:58:06'),
(1405, 1, 194, 1, 0, 0, 0, '2019-12-02 05:24:11', '2025-08-25 08:58:06'),
(1406, 1, 195, 1, 0, 0, 0, '2019-12-02 05:24:20', '2025-08-25 08:58:06'),
(1408, 1, 227, 1, 0, 0, 0, '2019-12-02 05:25:47', '2025-08-25 08:58:06'),
(1410, 1, 226, 1, 0, 0, 0, '2019-12-02 05:31:41', '2025-08-25 08:58:06'),
(1411, 1, 229, 1, 0, 0, 0, '2019-12-02 05:32:57', '2025-08-25 08:58:06'),
(1412, 1, 230, 1, 0, 0, 0, '2019-12-02 05:32:57', '2025-08-25 08:58:06'),
(1413, 1, 190, 1, 0, 0, 0, '2019-12-02 05:43:41', '2025-08-25 08:58:06'),
(1414, 2, 174, 1, 0, 0, 0, '2019-12-02 05:54:37', '2025-08-25 08:58:06'),
(1415, 2, 175, 1, 0, 0, 0, '2019-12-02 05:54:37', '2025-08-25 08:58:06'),
(1418, 2, 232, 1, 0, 1, 1, '2019-12-02 06:11:27', '2025-08-25 08:58:06'),
(1419, 2, 231, 1, 0, 0, 0, '2019-12-02 06:12:28', '2025-08-25 08:58:06'),
(1420, 1, 231, 1, 1, 1, 1, '2021-01-23 07:09:32', '2025-08-25 08:58:06'),
(1421, 1, 232, 1, 0, 1, 1, '2019-12-02 06:19:32', '2025-08-25 08:58:06'),
(1422, 3, 32, 1, 1, 1, 1, '2019-12-02 06:30:37', '2025-08-25 08:58:06'),
(1423, 3, 33, 1, 1, 1, 1, '2019-12-02 06:30:37', '2025-08-25 08:58:06'),
(1424, 3, 34, 1, 1, 1, 1, '2019-12-02 06:30:37', '2025-08-25 08:58:06'),
(1425, 3, 182, 1, 0, 0, 0, '2019-12-02 06:30:37', '2025-08-25 08:58:06'),
(1426, 3, 183, 1, 0, 0, 0, '2019-12-02 06:30:37', '2025-08-25 08:58:06'),
(1427, 3, 189, 1, 0, 1, 1, '2019-12-02 06:30:37', '2025-08-25 08:58:06'),
(1428, 3, 229, 1, 0, 0, 0, '2019-12-02 06:30:37', '2025-08-25 08:58:06'),
(1429, 3, 230, 1, 0, 0, 0, '2019-12-02 06:30:37', '2025-08-25 08:58:06'),
(1430, 4, 213, 1, 0, 0, 0, '2019-12-02 06:32:14', '2025-08-25 08:58:06'),
(1432, 4, 224, 1, 0, 0, 0, '2019-12-02 06:32:14', '2025-08-25 08:58:06'),
(1433, 4, 195, 1, 0, 0, 0, '2019-12-03 04:57:53', '2025-08-25 08:58:06'),
(1434, 4, 229, 1, 0, 0, 0, '2019-12-03 04:58:19', '2025-08-25 08:58:06'),
(1436, 6, 213, 1, 0, 0, 0, '2019-12-03 05:10:11', '2025-08-25 08:58:06'),
(1437, 6, 191, 1, 0, 0, 0, '2019-12-03 05:10:11', '2025-08-25 08:58:06'),
(1438, 6, 193, 1, 0, 0, 0, '2019-12-03 05:10:11', '2025-08-25 08:58:06'),
(1439, 6, 230, 1, 0, 0, 0, '2019-12-03 05:10:11', '2025-08-25 08:58:06'),
(1440, 2, 106, 1, 0, 0, 0, '2020-01-25 04:21:36', '2025-08-25 08:58:06'),
(1441, 2, 107, 1, 0, 0, 0, '2020-02-12 02:10:13', '2025-08-25 08:58:06'),
(1442, 2, 134, 1, 1, 1, 1, '2020-02-12 02:12:36', '2025-08-25 08:58:06'),
(1443, 1, 233, 1, 0, 0, 0, '2020-02-12 02:21:57', '2025-08-25 08:58:06'),
(1444, 2, 86, 1, 0, 0, 0, '2020-02-12 02:22:33', '2025-08-25 08:58:06'),
(1445, 3, 233, 1, 0, 0, 0, '2020-02-12 03:51:17', '2025-08-25 08:58:06'),
(1446, 1, 234, 1, 1, 1, 1, '2020-06-01 21:51:09', '2025-08-25 08:58:06'),
(1447, 1, 235, 1, 1, 1, 1, '2020-05-29 23:17:01', '2025-08-25 08:58:06'),
(1448, 1, 236, 1, 1, 1, 0, '2020-05-29 23:17:52', '2025-08-25 08:58:06'),
(1449, 1, 237, 1, 0, 1, 0, '2020-05-29 23:18:18', '2025-08-25 08:58:06'),
(1450, 1, 238, 1, 1, 1, 1, '2020-05-29 23:19:52', '2025-08-25 08:58:06'),
(1451, 1, 239, 1, 1, 1, 1, '2020-05-29 23:22:10', '2025-08-25 08:58:06'),
(1452, 2, 236, 1, 1, 1, 0, '2020-05-29 23:40:33', '2025-08-25 08:58:06'),
(1453, 2, 237, 1, 0, 1, 0, '2020-05-29 23:40:33', '2025-08-25 08:58:06'),
(1454, 2, 238, 1, 1, 1, 1, '2020-05-29 23:40:33', '2025-08-25 08:58:06'),
(1455, 2, 239, 1, 1, 1, 1, '2020-05-29 23:40:33', '2025-08-25 08:58:06'),
(1456, 2, 240, 1, 0, 0, 0, '2020-05-28 20:51:18', '2025-08-25 08:58:06'),
(1457, 2, 241, 1, 0, 0, 0, '2020-05-28 20:51:18', '2025-08-25 08:58:06'),
(1458, 1, 240, 1, 0, 0, 0, '2020-06-07 18:30:42', '2025-08-25 08:58:06'),
(1459, 1, 241, 1, 0, 0, 0, '2020-06-07 18:30:42', '2025-08-25 08:58:06'),
(1460, 1, 242, 1, 0, 0, 0, '2020-06-07 18:30:42', '2025-08-25 08:58:06'),
(1461, 2, 242, 1, 0, 0, 0, '2020-06-11 22:45:24', '2025-08-25 08:58:06'),
(1462, 3, 242, 1, 0, 0, 0, '2020-06-14 22:46:54', '2025-08-25 08:58:06'),
(1463, 6, 242, 1, 0, 0, 0, '2020-06-14 22:48:14', '2025-08-25 08:58:06'),
(1464, 1, 243, 1, 0, 0, 0, '2020-09-12 06:05:45', '2025-08-25 08:58:06'),
(1465, 1, 109, 1, 1, 0, 0, '2020-09-21 06:33:50', '2025-08-25 08:58:06'),
(1466, 1, 108, 1, 0, 1, 1, '2023-11-04 12:52:08', '2025-08-25 08:58:06'),
(1467, 1, 244, 1, 0, 0, 0, '2020-09-21 06:59:54', '2025-08-25 08:58:06'),
(1468, 1, 245, 1, 0, 0, 0, '2020-09-21 06:59:54', '2025-08-25 08:58:06'),
(1469, 1, 246, 1, 0, 0, 0, '2020-09-21 06:59:54', '2025-08-25 08:58:06'),
(1470, 1, 247, 1, 0, 0, 0, '2021-01-07 06:12:14', '2025-08-25 08:58:06'),
(1472, 2, 247, 1, 0, 0, 0, '2021-01-21 12:46:40', '2025-08-25 08:58:06'),
(1473, 1, 248, 1, 1, 1, 1, '2021-05-19 12:52:49', '2025-08-25 08:58:06'),
(1474, 1, 249, 1, 0, 0, 0, '2021-05-19 12:52:49', '2025-08-25 08:58:06'),
(1475, 2, 248, 1, 1, 1, 1, '2021-05-28 13:11:52', '2025-08-25 08:58:06'),
(1476, 3, 248, 1, 1, 1, 1, '2021-05-28 09:36:16', '2025-08-25 08:58:06'),
(1477, 3, 249, 1, 0, 0, 0, '2021-05-28 09:36:16', '2025-08-25 08:58:06'),
(1478, 6, 248, 1, 0, 0, 0, '2021-05-28 09:56:14', '2025-08-25 08:58:06'),
(1479, 6, 249, 1, 0, 0, 0, '2021-05-28 09:56:14', '2025-08-25 08:58:06'),
(1480, 2, 249, 1, 0, 0, 0, '2021-05-28 13:11:52', '2025-08-25 08:58:06'),
(1481, 1, 269, 1, 0, 0, 1, '2023-11-04 12:52:08', '2025-08-25 08:58:06'),
(1482, 2, 269, 1, 0, 0, 1, '2023-11-04 12:52:28', '2025-08-25 08:58:06'),
(1483, 3, 269, 1, 0, 0, 1, '2023-11-04 12:53:22', '2025-08-25 08:58:06'),
(1484, 4, 269, 1, 0, 0, 1, '2023-11-04 12:53:34', '2025-08-25 08:58:06'),
(1485, 6, 269, 1, 0, 0, 1, '2023-11-04 12:53:52', '2025-08-25 08:58:06'),
(1486, 1, 10001, 1, 0, 0, 0, '2025-08-05 02:00:06', '2025-08-25 09:08:28'),
(1487, 1, 10002, 1, 0, 0, 0, '2025-08-05 01:50:12', '2025-08-25 09:08:28'),
(1488, 1, 10003, 1, 0, 0, 0, '2025-08-05 01:50:12', '2025-08-25 09:08:28'),
(1489, 1, 10004, 1, 0, 0, 0, '2025-08-05 01:50:12', '2025-08-25 09:08:28'),
(1490, 1, 10005, 1, 0, 0, 0, '2025-08-05 01:50:12', '2025-08-25 09:08:28'),
(1491, 1, 10006, 1, 0, 0, 0, '2025-08-05 01:50:12', '2025-08-25 09:08:28'),
(1492, 1, 10007, 1, 0, 0, 0, '2025-08-05 01:50:12', '2025-08-25 09:08:28');

-- --------------------------------------------------------

--
-- Table structure for table `room_types`
--

DROP TABLE IF EXISTS `room_types`;
CREATE TABLE IF NOT EXISTS `room_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `room_type` varchar(200) DEFAULT NULL,
  `description` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `route_pickup_point`
--

DROP TABLE IF EXISTS `route_pickup_point`;
CREATE TABLE IF NOT EXISTS `route_pickup_point` (
  `id` int NOT NULL AUTO_INCREMENT,
  `session_id` int DEFAULT NULL,
  `transport_route_id` int NOT NULL,
  `pickup_point_id` int NOT NULL,
  `fees` float(10,2) DEFAULT '0.00',
  `destination_distance` float(10,1) DEFAULT '0.0',
  `pickup_time` time DEFAULT NULL,
  `order_number` float NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `transport_route_id` (`transport_route_id`),
  KEY `pickup_point_id` (`pickup_point_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `school_houses`
--

DROP TABLE IF EXISTS `school_houses`;
CREATE TABLE IF NOT EXISTS `school_houses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `house_name` varchar(200) NOT NULL,
  `description` varchar(400) NOT NULL,
  `is_active` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `sch_settings`
--

DROP TABLE IF EXISTS `sch_settings`;
CREATE TABLE IF NOT EXISTS `sch_settings` (
  `id` int NOT NULL,
  `base_url` varchar(500) DEFAULT NULL,
  `folder_path` text,
  `name` varchar(100) DEFAULT NULL,
  `biometric` int DEFAULT '0',
  `biometric_device` text,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text,
  `lang_id` int DEFAULT NULL,
  `languages` varchar(500) NOT NULL,
  `dise_code` varchar(50) DEFAULT NULL,
  `date_format` varchar(50) NOT NULL,
  `time_format` varchar(255) NOT NULL,
  `currency` varchar(50) NOT NULL,
  `currency_symbol` varchar(50) NOT NULL,
  `is_rtl` varchar(10) DEFAULT 'disabled',
  `is_duplicate_fees_invoice` varchar(100) DEFAULT '0',
  `collect_back_date_fees` int NOT NULL,
  `single_page_print` int DEFAULT '0',
  `timezone` varchar(30) DEFAULT 'UTC',
  `session_id` int DEFAULT NULL,
  `cron_secret_key` varchar(100) NOT NULL,
  `currency_place` varchar(50) NOT NULL DEFAULT 'before_number',
  `currency_format` varchar(20) DEFAULT NULL,
  `class_teacher` varchar(100) NOT NULL,
  `start_month` varchar(40) NOT NULL,
  `attendence_type` int NOT NULL DEFAULT '0',
  `low_attendance_limit` float(10,2) NOT NULL,
  `image` varchar(100) DEFAULT NULL,
  `admin_logo` varchar(255) NOT NULL,
  `admin_small_logo` varchar(255) NOT NULL,
  `admin_login_page_background` varchar(255) NOT NULL,
  `user_login_page_background` varchar(255) NOT NULL,
  `theme` varchar(200) NOT NULL DEFAULT 'default.jpg',
  `fee_due_days` int DEFAULT '0',
  `adm_auto_insert` int NOT NULL DEFAULT '1',
  `adm_prefix` varchar(50) NOT NULL DEFAULT 'ssadm19/20',
  `adm_start_from` varchar(11) NOT NULL,
  `adm_no_digit` int NOT NULL DEFAULT '6',
  `adm_update_status` int NOT NULL DEFAULT '0',
  `staffid_auto_insert` int NOT NULL DEFAULT '1',
  `staffid_prefix` varchar(100) NOT NULL DEFAULT 'staffss/19/20',
  `staffid_start_from` varchar(50) NOT NULL,
  `staffid_no_digit` int NOT NULL DEFAULT '6',
  `staffid_update_status` int NOT NULL DEFAULT '0',
  `is_active` varchar(255) DEFAULT 'no',
  `online_admission` int DEFAULT '0',
  `online_admission_payment` varchar(50) NOT NULL,
  `online_admission_amount` float NOT NULL,
  `online_admission_instruction` text NOT NULL,
  `online_admission_conditions` text NOT NULL,
  `online_admission_application_form` varchar(255) DEFAULT NULL,
  `exam_result` int NOT NULL,
  `is_blood_group` int NOT NULL DEFAULT '1',
  `is_student_house` int NOT NULL DEFAULT '1',
  `roll_no` int NOT NULL DEFAULT '1',
  `category` int NOT NULL,
  `religion` int NOT NULL DEFAULT '1',
  `cast` int NOT NULL DEFAULT '1',
  `mobile_no` int NOT NULL DEFAULT '1',
  `student_email` int NOT NULL DEFAULT '1',
  `admission_date` int NOT NULL DEFAULT '1',
  `lastname` int NOT NULL,
  `middlename` int NOT NULL DEFAULT '1',
  `student_photo` int NOT NULL DEFAULT '1',
  `student_height` int NOT NULL DEFAULT '1',
  `student_weight` int NOT NULL DEFAULT '1',
  `measurement_date` int NOT NULL DEFAULT '1',
  `father_name` int NOT NULL DEFAULT '1',
  `father_phone` int NOT NULL DEFAULT '1',
  `father_occupation` int NOT NULL DEFAULT '1',
  `father_pic` int NOT NULL DEFAULT '1',
  `mother_name` int NOT NULL DEFAULT '1',
  `mother_phone` int NOT NULL DEFAULT '1',
  `mother_occupation` int NOT NULL DEFAULT '1',
  `mother_pic` int NOT NULL DEFAULT '1',
  `guardian_name` int NOT NULL,
  `guardian_relation` int NOT NULL DEFAULT '1',
  `guardian_phone` int NOT NULL,
  `guardian_email` int NOT NULL DEFAULT '1',
  `guardian_pic` int NOT NULL DEFAULT '1',
  `guardian_occupation` int NOT NULL,
  `guardian_address` int NOT NULL DEFAULT '1',
  `current_address` int NOT NULL DEFAULT '1',
  `permanent_address` int NOT NULL DEFAULT '1',
  `route_list` int NOT NULL DEFAULT '1',
  `hostel_id` int NOT NULL DEFAULT '1',
  `bank_account_no` int NOT NULL DEFAULT '1',
  `ifsc_code` int NOT NULL,
  `bank_name` int NOT NULL,
  `national_identification_no` int NOT NULL DEFAULT '1',
  `local_identification_no` int NOT NULL DEFAULT '1',
  `rte` int NOT NULL DEFAULT '1',
  `previous_school_details` int NOT NULL DEFAULT '1',
  `student_note` int NOT NULL DEFAULT '1',
  `upload_documents` int NOT NULL DEFAULT '1',
  `student_barcode` int NOT NULL DEFAULT '1',
  `staff_designation` int NOT NULL DEFAULT '1',
  `staff_department` int NOT NULL DEFAULT '1',
  `staff_last_name` int NOT NULL DEFAULT '1',
  `staff_father_name` int NOT NULL DEFAULT '1',
  `staff_mother_name` int NOT NULL DEFAULT '1',
  `staff_date_of_joining` int NOT NULL DEFAULT '1',
  `staff_phone` int NOT NULL DEFAULT '1',
  `staff_emergency_contact` int NOT NULL DEFAULT '1',
  `staff_marital_status` int NOT NULL DEFAULT '1',
  `staff_photo` int NOT NULL DEFAULT '1',
  `staff_current_address` int NOT NULL DEFAULT '1',
  `staff_permanent_address` int NOT NULL DEFAULT '1',
  `staff_qualification` int NOT NULL DEFAULT '1',
  `staff_work_experience` int NOT NULL DEFAULT '1',
  `staff_note` int NOT NULL DEFAULT '1',
  `staff_epf_no` int NOT NULL DEFAULT '1',
  `staff_basic_salary` int NOT NULL DEFAULT '1',
  `staff_contract_type` int NOT NULL DEFAULT '1',
  `staff_work_shift` int NOT NULL DEFAULT '1',
  `staff_work_location` int NOT NULL DEFAULT '1',
  `staff_leaves` int NOT NULL DEFAULT '1',
  `staff_account_details` int NOT NULL DEFAULT '1',
  `staff_social_media` int NOT NULL DEFAULT '1',
  `staff_upload_documents` int NOT NULL DEFAULT '1',
  `staff_barcode` int NOT NULL DEFAULT '1',
  `staff_notification_email` varchar(50) NOT NULL,
  `mobile_api_url` tinytext NOT NULL,
  `app_primary_color_code` varchar(20) DEFAULT NULL,
  `app_secondary_color_code` varchar(20) DEFAULT NULL,
  `admin_mobile_api_url` tinytext NOT NULL,
  `admin_app_primary_color_code` varchar(20) NOT NULL,
  `admin_app_secondary_color_code` varchar(20) NOT NULL,
  `app_logo` varchar(250) DEFAULT NULL,
  `student_profile_edit` int NOT NULL DEFAULT '0',
  `start_week` varchar(10) NOT NULL,
  `my_question` int NOT NULL,
  `superadmin_restriction` varchar(20) NOT NULL,
  `student_timeline` varchar(20) NOT NULL,
  `calendar_event_reminder` int DEFAULT NULL,
  `event_reminder` varchar(20) NOT NULL,
  `student_login` varchar(100) DEFAULT NULL,
  `parent_login` varchar(100) DEFAULT NULL,
  `student_panel_login` int NOT NULL DEFAULT '1',
  `parent_panel_login` int NOT NULL DEFAULT '1',
  `is_student_feature_lock` int NOT NULL DEFAULT '0',
  `maintenance_mode` int NOT NULL DEFAULT '0',
  `lock_grace_period` int NOT NULL DEFAULT '0',
  `is_offline_fee_payment` int NOT NULL DEFAULT '0',
  `offline_bank_payment_instruction` text NOT NULL,
  `scan_code_type` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT 'barcode',
  `student_resume_download` int NOT NULL DEFAULT '1',
  `download_admit_card` int NOT NULL DEFAULT '0',
  `fees_discount` int NOT NULL,
  `front_side_whatsapp` int NOT NULL DEFAULT '0',
  `front_side_whatsapp_mobile` varchar(50) DEFAULT NULL,
  `front_side_whatsapp_from` time DEFAULT NULL,
  `front_side_whatsapp_to` time DEFAULT NULL,
  `admin_panel_whatsapp` int NOT NULL DEFAULT '0',
  `admin_panel_whatsapp_mobile` varchar(50) DEFAULT NULL,
  `admin_panel_whatsapp_from` time DEFAULT NULL,
  `admin_panel_whatsapp_to` time DEFAULT NULL,
  `student_panel_whatsapp` int NOT NULL DEFAULT '0',
  `student_panel_whatsapp_mobile` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `student_panel_whatsapp_from` time DEFAULT NULL,
  `student_panel_whatsapp_to` time DEFAULT NULL,
  `saas_key` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `lang_id` (`lang_id`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `sch_settings`
--

INSERT INTO `sch_settings` (`id`, `base_url`, `folder_path`, `name`, `biometric`, `biometric_device`, `email`, `phone`, `address`, `lang_id`, `languages`, `dise_code`, `date_format`, `time_format`, `currency`, `currency_symbol`, `is_rtl`, `is_duplicate_fees_invoice`, `collect_back_date_fees`, `single_page_print`, `timezone`, `session_id`, `cron_secret_key`, `currency_place`, `currency_format`, `class_teacher`, `start_month`, `attendence_type`, `low_attendance_limit`, `image`, `admin_logo`, `admin_small_logo`, `admin_login_page_background`, `user_login_page_background`, `theme`, `fee_due_days`, `adm_auto_insert`, `adm_prefix`, `adm_start_from`, `adm_no_digit`, `adm_update_status`, `staffid_auto_insert`, `staffid_prefix`, `staffid_start_from`, `staffid_no_digit`, `staffid_update_status`, `is_active`, `online_admission`, `online_admission_payment`, `online_admission_amount`, `online_admission_instruction`, `online_admission_conditions`, `online_admission_application_form`, `exam_result`, `is_blood_group`, `is_student_house`, `roll_no`, `category`, `religion`, `cast`, `mobile_no`, `student_email`, `admission_date`, `lastname`, `middlename`, `student_photo`, `student_height`, `student_weight`, `measurement_date`, `father_name`, `father_phone`, `father_occupation`, `father_pic`, `mother_name`, `mother_phone`, `mother_occupation`, `mother_pic`, `guardian_name`, `guardian_relation`, `guardian_phone`, `guardian_email`, `guardian_pic`, `guardian_occupation`, `guardian_address`, `current_address`, `permanent_address`, `route_list`, `hostel_id`, `bank_account_no`, `ifsc_code`, `bank_name`, `national_identification_no`, `local_identification_no`, `rte`, `previous_school_details`, `student_note`, `upload_documents`, `student_barcode`, `staff_designation`, `staff_department`, `staff_last_name`, `staff_father_name`, `staff_mother_name`, `staff_date_of_joining`, `staff_phone`, `staff_emergency_contact`, `staff_marital_status`, `staff_photo`, `staff_current_address`, `staff_permanent_address`, `staff_qualification`, `staff_work_experience`, `staff_note`, `staff_epf_no`, `staff_basic_salary`, `staff_contract_type`, `staff_work_shift`, `staff_work_location`, `staff_leaves`, `staff_account_details`, `staff_social_media`, `staff_upload_documents`, `staff_barcode`, `staff_notification_email`, `mobile_api_url`, `app_primary_color_code`, `app_secondary_color_code`, `admin_mobile_api_url`, `admin_app_primary_color_code`, `admin_app_secondary_color_code`, `app_logo`, `student_profile_edit`, `start_week`, `my_question`, `superadmin_restriction`, `student_timeline`, `calendar_event_reminder`, `event_reminder`, `student_login`, `parent_login`, `student_panel_login`, `parent_panel_login`, `is_student_feature_lock`, `maintenance_mode`, `lock_grace_period`, `is_offline_fee_payment`, `offline_bank_payment_instruction`, `scan_code_type`, `student_resume_download`, `download_admit_card`, `fees_discount`, `front_side_whatsapp`, `front_side_whatsapp_mobile`, `front_side_whatsapp_from`, `front_side_whatsapp_to`, `admin_panel_whatsapp`, `admin_panel_whatsapp_mobile`, `admin_panel_whatsapp_from`, `admin_panel_whatsapp_to`, `student_panel_whatsapp`, `student_panel_whatsapp_mobile`, `student_panel_whatsapp_from`, `student_panel_whatsapp_to`, `saas_key`, `created_at`, `updated_at`) VALUES
(1, '', '', 'Your School Name', 0, '', 'yourschoolemail@domain.com', 'Your School Phone', 'Your School Address', 4, '[\"4\"]', 'Your School Code', 'm/d/Y', '12-hour', '150', '$', 'disabled', '0', 0, 0, 'UTC', 20, '', 'after_number', '####.##', 'no', '4', 0, 0.00, '1.png', '1.png', '1.png', '1663064530-1070210809632059d2b8b0b!1662796232-1721792380631c41c80d038!login_bg3.jpg', '1663065284-93117584263205cc49769c!1662964519-2099955753631ed327d0ffa!login_bg5.jpg', 'default.jpg', 60, 0, '', '', 0, 1, 0, '', '', 0, 1, 'no', 0, '', 0, '', '<p>&nbsp;Please enter your institution online admission terms & conditions here.</p>', NULL, 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, '', '', '#424242', '#eeeeee', '', '', '', '1.png', 0, 'Monday', 0, 'enabled', 'disabled', 0, 'disabled', 'null', 'null', 1, 1, 0, 0, 0, 0, '', 'qrcode', 1, 0, 0, 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, '2022-12-30 06:44:20', '2022-12-30 06:44:20');

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

DROP TABLE IF EXISTS `sections`;
CREATE TABLE IF NOT EXISTS `sections` (
  `id` int NOT NULL AUTO_INCREMENT,
  `section` varchar(60) DEFAULT NULL,
  `is_active` varchar(255) DEFAULT 'no',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `send_notification`
--

DROP TABLE IF EXISTS `send_notification`;
CREATE TABLE IF NOT EXISTS `send_notification` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(50) DEFAULT NULL,
  `publish_date` date DEFAULT NULL,
  `date` date DEFAULT NULL,
  `attachment` varchar(500) DEFAULT NULL,
  `message` text,
  `visible_student` varchar(10) NOT NULL DEFAULT 'no',
  `visible_staff` varchar(10) NOT NULL DEFAULT 'no',
  `visible_parent` varchar(10) NOT NULL DEFAULT 'no',
  `created_by` varchar(60) DEFAULT NULL,
  `created_id` int DEFAULT NULL,
  `is_active` varchar(255) DEFAULT 'no',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_id` (`created_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `session` varchar(60) DEFAULT NULL,
  `is_active` varchar(255) DEFAULT 'no',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `session`, `is_active`, `created_at`, `updated_at`) VALUES
(7, '2016-17', 'no', '2017-04-20 06:42:19', '0000-00-00 00:00:00'),
(11, '2017-18', 'no', '2017-04-20 06:41:37', '0000-00-00 00:00:00'),
(13, '2018-19', 'no', '2016-08-24 19:26:44', '0000-00-00 00:00:00'),
(14, '2019-20', 'no', '2016-08-24 19:26:55', '0000-00-00 00:00:00'),
(15, '2020-21', 'no', '2016-10-01 05:28:08', '0000-00-00 00:00:00'),
(16, '2021-22', 'no', '2016-10-01 05:28:20', '0000-00-00 00:00:00'),
(18, '2022-23', 'no', '2016-10-01 05:29:02', '0000-00-00 00:00:00'),
(19, '2023-24', 'no', '2016-10-01 05:29:10', '0000-00-00 00:00:00'),
(20, '2024-25', 'no', '2016-10-01 05:29:18', '0000-00-00 00:00:00'),
(21, '2025-26', 'no', '2016-10-01 05:30:10', '0000-00-00 00:00:00'),
(22, '2026-27', 'no', '2016-10-01 05:30:18', '0000-00-00 00:00:00'),
(23, '2027-28', 'no', '2016-10-01 05:30:24', '0000-00-00 00:00:00'),
(24, '2028-29', 'no', '2016-10-01 05:30:30', '0000-00-00 00:00:00'),
(25, '2029-30', 'no', '2016-10-01 05:30:37', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `share_contents`
--

DROP TABLE IF EXISTS `share_contents`;
CREATE TABLE IF NOT EXISTS `share_contents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `send_to` varchar(50) DEFAULT NULL,
  `title` text,
  `share_date` date DEFAULT NULL,
  `valid_upto` date DEFAULT NULL,
  `description` text,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `share_content_for`
--

DROP TABLE IF EXISTS `share_content_for`;
CREATE TABLE IF NOT EXISTS `share_content_for` (
  `id` int NOT NULL AUTO_INCREMENT,
  `group_id` varchar(20) DEFAULT NULL,
  `student_id` int DEFAULT NULL,
  `user_parent_id` int DEFAULT NULL,
  `staff_id` int DEFAULT NULL,
  `class_section_id` int DEFAULT NULL,
  `share_content_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `upload_content_id` (`share_content_id`),
  KEY `student_id` (`student_id`),
  KEY `staff_id` (`staff_id`),
  KEY `class_section_id` (`class_section_id`),
  KEY `user_parent_id` (`user_parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `share_upload_contents`
--

DROP TABLE IF EXISTS `share_upload_contents`;
CREATE TABLE IF NOT EXISTS `share_upload_contents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `upload_content_id` int DEFAULT NULL,
  `share_content_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `upload_content_id` (`upload_content_id`),
  KEY `share_content_id` (`share_content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `sidebar_menus`
--

DROP TABLE IF EXISTS `sidebar_menus`;
CREATE TABLE IF NOT EXISTS `sidebar_menus` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_name` varchar(50) NOT NULL,
  `permission_group_id` int DEFAULT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `menu` varchar(500) DEFAULT NULL,
  `activate_menu` varchar(100) DEFAULT NULL,
  `lang_key` varchar(250) NOT NULL,
  `system_level` int DEFAULT '0',
  `level` int DEFAULT NULL,
  `sidebar_display` int DEFAULT '0',
  `access_permissions` text,
  `is_active` int NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `permission_group_id` (`permission_group_id`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `sidebar_menus`
--

INSERT INTO `sidebar_menus` (`id`, `product_name`, `permission_group_id`, `icon`, `menu`, `activate_menu`, `lang_key`, `system_level`, `level`, `sidebar_display`, `access_permissions`, `is_active`, `created_at`, `updated_at`) VALUES
(1, '', 17, 'fa fa-ioxhost ftlayer', 'Front Office', 'front_office', 'front_office', 10, 1, 1, '(\'admission_enquiry\', \'can_view\') || (\'visitor_book\', \'can_view\') ||       (\'phon_call_log\', \'can_view\') ||  (\'postal_dispatch\', \'can_view\') ||       (\'postal_receive\', \'can_view\') || (\'complaint\', \'can_view\') ||(\'setup_font_office\', \'can_view\')', 1, '2023-01-10 12:49:51', '2025-08-25 08:58:06'),
(2, '', 1, 'fa fa-user-plus ftlayer', 'Student Information', 'student_information', 'student_information', 20, 2, 1, '(\'student\', \'can_view\') || (\'student\', \'can_add\') || (\'student_history\', \'can_view\') || (\'student_categories\', \'can_view\') || (\'student_houses\', \'can_view\') || (\'disable_student\', \'can_view\') || (\'disable_reason\', \'can_view\') || (\'online_admission\', \'can_view\') || (\'multiclass_student\', \'can_view\') || (\'disable_reason\', \'can_view\')', 1, '2023-01-10 12:49:51', '2025-08-25 08:58:06'),
(3, '', 2, 'fa fa-money ftlayer', 'Fees Collection', 'fees_collection', 'fees_collection', 30, 3, 1, '(\'collect_fees\', \'can_view\') || (\'search_fees_payment\', \'can_view\') || (\'search_due_fees\', \'can_view\') || (\'fees_statement\', \'can_view\') || (\'fees_carry_forward\', \'can_view\') || (\'fees_master\', \'can_view\') || (\'fees_group\', \'can_view\') || (\'fees_type\', \'can_view\') || (\'fees_discount\', \'can_view\') || (\'accountants\', \'can_view\')', 1, '2023-01-10 12:49:51', '2025-08-25 08:58:06'),
(4, '', 3, 'fa fa-usd ftlayer', 'Income', 'income', 'income', 40, 10, 1, '(\'income\', \'can_view\') || (\'search_income\', \'can_view\') || (\'income_head\', \'can_view\')', 1, '2023-01-10 12:49:37', '2025-08-25 08:58:06'),
(7, '', 4, 'fa fa-credit-card ftlayer', 'Expense', 'expense', 'expenses', 50, 11, 1, '(\'expense\', \'can_view\') || (\'search_expense\', \'can_view\') || (\'expense_head\', \'can_view\')', 1, '2023-01-10 12:49:37', '2025-08-25 08:58:06'),
(10, '', 5, 'fa fa-calendar-check-o ftlayer', 'Attendance', 'attendance', 'attendance', 60, 13, 1, '(\'student_attendance\', \'can_view\')', 1, '2023-01-10 12:49:37', '2025-08-25 08:58:06'),
(11, '', 6, 'fa fa-map-o ftlayer', 'Examinations', 'examinations', 'examinations', 70, 12, 1, '(\'exam_group\', \'can_view\') || (\'exam_result\', \'can_view\') || (\'design_admit_card\', \'can_view\') || (\'print_admit_card\', \'can_view\') || (\'design_marksheet\', \'can_view\') || (\'print_marksheet\', \'can_view\') || (\'marks_grade\', \'can_view\')', 1, '2023-01-10 12:49:37', '2025-08-25 08:58:06'),
(12, '', 23, 'fa fa-rss ftlayer', 'Online Examinations', 'online_examinations', 'online_examinations', 80, 14, 1, '(\'online_examination\', \'can_view\') ||  (\'question_bank\', \'can_view\'', 1, '2023-01-10 12:49:37', '2025-08-25 08:58:06'),
(13, '', 29, 'fa fa-list-alt ftlayer', 'Lesson Plan', 'lesson_plan', 'lesson_plan', 90, 16, 1, '(\'manage_lesson_plan\', \'can_view\') || (\'manage_syllabus_status\', \'can_view\') || (\'lesson\', \'can_view\') ||  (\'topic\', \'can_view\')||  (\'copy_old_lesson\', \'can_view\')', 1, '2023-01-10 12:49:37', '2025-08-25 08:58:06'),
(14, '', 7, 'fa fa-mortar-board ftlayer', 'Academics', 'academics', 'academics', 100, 15, 1, '(\'class_timetable\', \'can_view\') || (\'teachers_timetable\', \'can_view\') || (\'assign_class_teacher\', \'can_view\') || (\'promote_student\', \'can_view\') || (\'subject_group\', \'can_view\') || (\'section\', \'can_view\') || (\'subject\', \'can_view\') || (\'class\', \'can_view\') || (\'section\', \'can_view\')', 1, '2023-01-10 12:49:37', '2025-08-25 08:58:06'),
(15, '', 18, 'fa fa-sitemap ftlayer', 'Human Resource', 'human_resource', 'human_resource', 110, 17, 1, '(\'staff\', \'can_view\') || (\'approve_leave_request\', \'can_view\') || (\'apply_leave\', \'can_view\') || (\'leave_types\', \'can_view\') || (\'teachers_rating\', \'can_view\') || (\'department\', \'can_view\') || (\'designation\', \'can_view\') || (\'disable_staff\', \'can_view\')', 1, '2023-01-10 12:49:37', '2025-08-25 08:58:06'),
(16, '', 13, 'fa fa-bullhorn ftlayer', 'Communicate', 'communicate', 'communicate', 120, 18, 1, '(\'notice_board\', \'can_view\') || (\'email\', \'can_view\') || (\'sms\', \'can_view\') || (\'email_sms_log\', \'can_view\')', 1, '2023-01-10 12:49:37', '2025-08-25 08:58:06'),
(17, '', 8, 'fa fa-download ftlayer', 'Download Center', 'download_center', 'download_center', 130, 19, 1, '(\'upload_content\', \'can_view\') || (\'video_tutorial\', \'can_view\') || (\'content_type\', \'can_view\') || (\'content_share_list\', \'can_view\')', 1, '2023-01-10 12:49:37', '2025-08-25 08:58:06'),
(18, '', 19, 'fa fa-flask ftlayer', 'Homework', 'homework', 'homework', 140, 20, 1, '(\'homework\', \'can_view\') || (\'homework\', \'can_view\')', 1, '2023-01-10 12:49:37', '2025-08-25 08:58:06'),
(19, '', 9, 'fa fa-book ftlayer', 'Library', 'library', 'library', 150, 21, 1, '(\'books\', \'can_view\') || (\'issue_return\', \'can_view\') || (\'add_staff_member\', \'can_view\') || (\'add_student\', \'can_view\')', 1, '2023-01-10 12:49:37', '2025-08-25 08:58:06'),
(20, '', 10, 'fa fa-object-group ftlayer', 'Inventory', 'inventory', 'inventory', 160, 22, 1, '(\'issue_item\', \'can_view\') || (\'item_stock\', \'can_view\') || (\'item\', \'can_view\') || (\'item_category\', \'can_view\') || (\'item_category\', \'can_view\') || (\'store\', \'can_view\') || (\'supplier\', \'can_view\')', 1, '2023-01-10 12:49:37', '2025-08-25 08:58:06'),
(21, '', 11, 'fa fa-bus ftlayer', 'Transport', 'transport', 'transport', 170, 23, 1, '(\'routes\', \'can_view\') || (\'vehicle\', \'can_view\') || (\'assign_vehicle\', \'can_view\') || (\'transport_fees_master\', \'can_view\') || (\'pickup_point\', \'can_view\') || (\'route_pickup_point\', \'can_view\') || (\'student_transport_fees\', \'can_view\')      ', 1, '2023-01-10 12:49:37', '2025-08-25 08:58:06'),
(22, '', 12, 'fa fa-building-o ftlayer', 'Hostel', 'hostel', 'hostel', 180, 24, 1, '(\'hostel_rooms\', \'can_view\') || (\'room_type\', \'can_view\') || (\'hostel\', \'can_view\')', 1, '2023-01-10 12:49:37', '2025-08-25 08:58:06'),
(23, '', 20, 'fa fa-newspaper-o ftlayer', 'Certificate', 'certificate', 'certificate', 190, 25, 1, '(\'student_certificate\', \'can_view\') || (\'generate_certificate\', \'can_view\') || (\'student_id_card\', \'can_view\') || (\'generate_id_card\', \'can_view\') || (\'staff_id_card\', \'can_view\') || (\'generate_staff_id_card\', \'can_view\')', 1, '2023-01-10 12:49:37', '2025-08-25 08:58:06'),
(24, '', 16, 'fa fa-empire ftlayer', 'Front CMS', 'front_cms', 'front_cms', 200, 26, 1, '(\'event\', \'can_view\') || (\'gallery\', \'can_view\') || (\'notice\', \'can_view\') || (\'media_manager\', \'can_view\') || (\'pages\', \'can_view\') || (\'menus\', \'can_view\') || (\'banner_images\', \'can_view\')', 1, '2023-01-10 12:49:37', '2025-08-25 08:58:06'),
(25, '', 28, 'fa fa-universal-access ftlayer', 'Alumni', 'alumni', 'alumni', 210, 27, 1, '(\'manage_alumni\', \'can_view\') || (\'events\', \'can_view\')', 1, '2023-01-10 12:49:37', '2025-08-25 08:58:06'),
(26, '', 14, 'fa fa-line-chart ftlayer', 'Reports', 'reports', 'reports', 220, 28, 1, '(\'student_report\', \'can_view\') || (\'guardian_report\', \'can_view\') || (\'student_history\', \'can_view\') || (\'student_login_credential_report\', \'can_view\') || (\'class_subject_report\', \'can_view\') || (\'admission_report\', \'can_view\') || (\'sibling_report\', \'can_view\') || (\'evaluation_report\', \'can_view\') || (\'student_profile\', \'can_view\') || (\'fees_statement\', \'can_view\') || (\'balance_fees_report\', \'can_view\') || (\'fees_collection_report\', \'can_view\') || (\'online_fees_collection_report\', \'can_view\') || (\'income_report\', \'can_view\') || (\'expense_report\', \'can_view\') || (\'payroll_report\', \'can_view\') || (\'income_group_report\', \'can_view\') || (\'expense_group_report\', \'can_view\') || (\'attendance_report\', \'can_view\') || (\'staff_attendance_report\', \'can_view\') || (\'exam_marks_report\', \'can_view\') ||        (\'online_exam_wise_report\', \'can_view\') || (\'online_exams_report\', \'can_view\') || (\'online_exams_attempt_report\', \'can_view\') || (\'online_exams_rank_report\', \'can_view\') || (\'payroll_report\', \'can_view\') || (\'transport_report\', \'can_view\') || (\'hostel_report\', \'can_view\') || (\'audit_trail_report\', \'can_view\') || (\'user_log\', \'can_view\') || (\'book_issue_report\', \'can_view\') || (\'book_due_report\', \'can_view\') || (\'book_inventory_report\', \'can_view\') || (\'stock_report\', \'can_view\') ||      (\'add_item_report\', \'can_view\') || (\'issue_inventory_report\', \'can_view\') || (\'syllabus_status_report\', \'can_view\') ||    (\'teacher_syllabus_status_report\', \'can_view\') || (\'daily_collection_report\', \'can_view\') || (\'balance_fees_statement\', \'can_view\') || (\'balance_fees_report_with_remark\', \'can_view\')', 1, '2023-01-10 12:49:37', '2025-08-25 08:58:06'),
(27, '', 15, 'fa fa-gears ftlayer', 'System Settings', 'system_settings', 'system_setting', 230, 29, 1, '(\'general_setting\', \'can_view\') || (\'session_setting\', \'can_view\') || (\'notification_setting\', \'can_view\') || (\'sms_setting\', \'can_view\') || (\'email_setting\', \'can_view\') || (\'payment_methods\', \'can_view\') || (\'languages\', \'can_view\') || (\'user_status\', \'can_view\') || (\'backup_restore\', \'can_view\') || (\'print_header_footer\', \'can_view\') || (\'backup\', \'can_view\') || (\'front_cms_setting\', \'can_view\') || (\'custom_fields\', \'can_view\') || (\'system_fields\', \'can_view\') || (\'student_profile_update\', \'can_view\') || (\'currency\', \'can_view\') || (\'language_switcher\', \'can_view\') || (\'sidebar_menu\', \'can_view\') || (\'online_admission\', \'can_view\')\r\n', 1, '2023-01-10 12:49:37', '2025-08-25 08:58:06'),
(33, '', 1000, 'fa fa-sitemap ftlayer', 'Multi Branch', 'multi_branch', 'multi_branch', 0, 4, 1, '(\'multi_branch_overview\', \'can_view\') || (\'multi_branch_daily_collection_report\', \'can_view\') || (\'multi_branch_payroll\', \'can_view\') || (\'multi_branch_income_report\', \'can_view\') || (\'multi_branch_expense_report\', \'can_view\') || (\'multi_branch_user_log_report\', \'can_view\') || (\'multi_branch_setting\', \'can_view\')', 1, '2025-08-10 07:49:51', '2025-08-25 09:08:28'),
(36, '', 30, 'fa fa-calendar', 'Annual Calendar', 'holiday', 'annual_calendar', 240, 16, 1, '(\'annual_calendar\', \'can_view\')\r\n', 0, '2025-01-18 09:15:03', '2025-08-25 08:58:06'),
(37, '', 31, 'fa fa-ioxhost ftlayer', 'Student CV', 'student_cv', 'student_cv', 1, 23, 1, '(\'download_cv\', \'can_view\') || (\'build_cv\', \'can_view\') || (\'resume_setting\', \'can_view\') || (\'student_resume_details\', \'can_view\')', 0, '2025-01-18 09:15:07', '2025-08-25 08:58:06');

-- --------------------------------------------------------

--
-- Table structure for table `sidebar_sub_menus`
--

DROP TABLE IF EXISTS `sidebar_sub_menus`;
CREATE TABLE IF NOT EXISTS `sidebar_sub_menus` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sidebar_menu_id` int DEFAULT NULL,
  `menu` varchar(500) DEFAULT NULL,
  `key` varchar(500) DEFAULT NULL,
  `lang_key` varchar(250) DEFAULT NULL,
  `url` text,
  `level` int DEFAULT NULL,
  `access_permissions` varchar(500) DEFAULT NULL,
  `permission_group_id` int DEFAULT NULL,
  `activate_controller` varchar(100) DEFAULT NULL COMMENT 'income',
  `activate_methods` varchar(500) DEFAULT NULL COMMENT 'index,edit',
  `addon_permission` varchar(100) DEFAULT NULL,
  `is_active` int DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `sidebar_menu_id` (`sidebar_menu_id`),
  KEY `permission_group_id` (`permission_group_id`)
) ENGINE=InnoDB AUTO_INCREMENT=220 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `sidebar_sub_menus`
--

INSERT INTO `sidebar_sub_menus` (`id`, `sidebar_menu_id`, `menu`, `key`, `lang_key`, `url`, `level`, `access_permissions`, `permission_group_id`, `activate_controller`, `activate_methods`, `addon_permission`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'admission_enquiry', NULL, 'admission_enquiry', 'admin/enquiry', 1, '(\'admission_enquiry\', \'can_view\')', NULL, 'enquiry', 'index', NULL, 1, '2022-07-11 12:04:46', '2025-08-25 08:58:06'),
(2, 1, 'visitor_book', NULL, 'visitor_book', 'admin/visitors', 2, '(\'visitor_book\', \'can_view\')', NULL, 'visitors', 'index', NULL, 1, '2022-07-11 12:04:46', '2025-08-25 08:58:06'),
(3, 1, 'phone_call_log', NULL, 'phone_call_log', 'admin/generalcall', 3, '(\'phone_call_log\', \'can_view\')', NULL, 'generalcall', 'index,edit', NULL, 1, '2022-05-10 11:40:07', '2025-08-25 08:58:06'),
(4, 1, 'postal_dispatch', NULL, 'postal_dispatch', 'admin/dispatch', 4, '(\'postal_dispatch\', \'can_view\')', NULL, 'dispatch', 'index,editdispatch', NULL, 1, '2022-05-10 11:40:09', '2025-08-25 08:58:06'),
(5, 1, 'postal_receive', NULL, 'postal_receive', 'admin/receive', 5, '(\'postal_receive\', \'can_view\')', NULL, 'receive', 'index,editreceive', NULL, 1, '2022-05-10 11:40:09', '2025-08-25 08:58:06'),
(6, 1, 'complain', NULL, 'complain', 'admin/complaint', 6, '(\'complaint\', \'can_view\')', NULL, 'complaint', 'index,edit', NULL, 1, '2022-05-10 11:40:00', '2025-08-25 08:58:06'),
(7, 1, 'setup_front_office', NULL, 'setup_front_office', 'admin/visitorspurpose', 7, '(\'setup_font_office\', \'can_view\')', NULL, 'visitorspurpose', 'index,edit', NULL, 1, '2022-04-18 15:43:15', '2025-08-25 08:58:06'),
(9, 2, 'student_admission', NULL, 'student_admission', 'student/create', 2, '(\'student\', \'can_add\')', NULL, 'student', 'create,import', NULL, 1, '2022-08-30 07:51:02', '2025-08-25 08:58:06'),
(10, 2, 'online_admission', NULL, 'online_admission', 'admin/onlinestudent', 3, '(\'online_admission\', \'can_view\')', 27, 'onlinestudent', 'index,edit', NULL, 1, '2022-05-10 11:40:30', '2025-08-25 08:58:06'),
(11, 2, 'disable_student', NULL, 'disabled_students', 'student/disablestudentslist', 4, '(\'disable_student\', \'can_view\')', NULL, 'student', 'disablestudentslist', '', 1, '2022-07-23 06:49:00', '2025-08-25 08:58:06'),
(12, 3, 'collect_fees', NULL, 'collect_fees', 'studentfee', 1, '(\'collect_fees\', \'can_view\')', NULL, 'studentfee', 'index,addfee', NULL, 1, '2022-07-23 06:53:34', '2025-08-25 08:58:06'),
(13, 3, 'search_fees_payment', NULL, 'search_fees_payment', 'studentfee/searchpayment', 3, '(\'search_fees_payment\', \'can_view\')', NULL, 'studentfee', 'searchpayment', NULL, 1, '2022-08-08 06:03:40', '2025-08-25 08:58:06'),
(14, 3, 'search_due_fees', NULL, 'search_due_fees', 'studentfee/feesearch', 4, '(\'search_due_fees\', \'can_view\')', NULL, 'studentfee', 'feesearch', NULL, 1, '2022-08-08 06:03:38', '2025-08-25 08:58:06'),
(15, 3, 'fees_master', NULL, 'fees_master', 'admin/feemaster', 5, '(\'fees_master\', \'can_view\')', NULL, 'feemaster', 'index,assign,edit', NULL, 1, '2022-09-24 07:35:55', '2025-08-25 08:58:06'),
(16, 3, 'fees_group', NULL, 'fees_group', 'admin/feegroup', 6, '(\'fees_group\', \'can_view\')', NULL, 'feegroup', 'index,edit', NULL, 1, '2022-08-08 06:03:32', '2025-08-25 08:58:06'),
(17, 4, 'add_income', NULL, 'add_income', 'admin/income', 1, '(\'income\', \'can_view\')', NULL, 'income', 'index,edit', NULL, 1, '2022-07-23 07:03:40', '2025-08-25 08:58:06'),
(18, 4, 'search_income', NULL, 'search_income', 'admin/income/incomesearch', 2, '(\'search_income\', \'can_view\')', NULL, 'income', 'incomesearch', NULL, 1, '2022-07-23 07:10:13', '2025-08-25 08:58:06'),
(19, 4, 'income_head', NULL, 'income_head', 'admin/incomehead', 3, '(\'income_head\', \'can_view\')', NULL, 'incomehead', 'index,edit', NULL, 1, '2022-07-23 07:11:11', '2025-08-25 08:58:06'),
(20, 2, 'student_details', NULL, 'student_details', 'student/search', 1, '(\'student\', \'can_view\')', NULL, 'student', 'search,view,edit', NULL, 1, '2022-08-30 07:51:05', '2025-08-25 08:58:06'),
(21, 2, 'multi_class_student', NULL, 'multi_class_student', 'student/multiclass', 5, '(\'multi_class_student\', \'can_view\')', 26, 'student', 'multiclass', NULL, 1, '2022-07-23 06:48:37', '2025-08-25 08:58:06'),
(22, 2, 'bulk_delete', NULL, 'bulk_delete', 'student/bulkdelete', 6, '(\'student\', \'can_delete\')', NULL, 'student', 'bulkdelete', NULL, 1, '2022-07-23 06:48:11', '2025-08-25 08:58:06'),
(23, 2, 'student_categories', NULL, 'student_categories', 'category', 7, '(\'student_categories\', \'can_view\')', NULL, 'category', 'index,edit', NULL, 1, '2022-07-23 06:47:24', '2025-08-25 08:58:06'),
(24, 2, 'student_house', NULL, 'student_house', 'admin/schoolhouse', 8, '(\'student_houses\', \'can_view\')', NULL, 'schoolhouse', 'index,edit', NULL, 1, '2022-07-23 06:49:59', '2025-08-25 08:58:06'),
(25, 2, 'disable_reason', NULL, 'disable_reason', 'admin/disable_reason', 9, '(\'disable_reason\', \'can_view\')', NULL, 'disable_reason', 'index,edit', NULL, 1, '2022-07-23 06:50:41', '2025-08-25 08:58:06'),
(29, 7, 'add_expense', NULL, 'add_expense', 'admin/expense', 1, '(\'expense\', \'can_view\')', NULL, 'expense', 'index,edit', '', 1, '2022-07-23 07:12:25', '2025-08-25 08:58:06'),
(32, 3, 'fees_type', NULL, 'fees_type', 'admin/feetype', 7, '(\'fees_type\', \'can_view\')', NULL, 'feetype', 'index,edit', '', 1, '2022-08-08 06:03:29', '2025-08-25 08:58:06'),
(33, 10, 'attendance_by_date', 'attendance_by_date', 'attendance_by_date', 'admin/stuattendence/attendencereport', 3, '(\'attendance_by_date\', \'can_view\')', NULL, 'stuattendence', 'attendencereport', '', 1, '2022-10-20 05:50:25', '2025-08-25 08:58:06'),
(34, 10, 'student_attendance', 'student_attendance', 'student_attendance', 'admin/stuattendence', 1, '(\'student_attendance\', \'can_view\')', NULL, 'stuattendence', 'index', '', 1, '2022-10-20 05:50:25', '2025-08-25 08:58:06'),
(35, 10, 'approve_leave', 'approve_leave', 'approve_leave', 'admin/approve_leave', 2, '(\'approve_leave\', \'can_view\')', NULL, 'approve_leave', 'index', '', 1, '2022-10-14 16:16:44', '2025-08-25 08:58:06'),
(36, 11, 'exam_group', NULL, 'exam_group', 'admin/examgroup', 1, '(\'exam_group\', \'can_view\')', NULL, 'examgroup', 'index,addexam,edit', '', 1, '2022-07-23 07:23:01', '2025-08-25 08:58:06'),
(37, 11, 'exam_schedule', NULL, 'exam_schedule', 'admin/exam_schedule', 2, '(\'student_attendance\', \'can_view\')', NULL, 'exam_schedule', 'index', '', 1, '2022-05-16 07:01:34', '2025-08-25 08:58:06'),
(38, 11, 'exam_result', NULL, 'exam_result', 'admin/examresult', 3, '(\'exam_result\', \'can_view\')', NULL, 'examresult', 'index', '', 1, '2022-05-16 07:01:34', '2025-08-25 08:58:06'),
(39, 11, 'design_admit_card', NULL, 'design_admit_card', 'admin/admitcard', 4, '(\'design_admit_card\', \'can_view\')', NULL, 'admitcard', 'index,edit', '', 1, '2022-07-23 07:28:02', '2025-08-25 08:58:06'),
(40, 11, 'print_admit_card', NULL, 'print_admit_card', 'admin/examresult/admitcard', 5, '(\'print_admit_card\', \'can_view\')', NULL, 'examresult', 'admitcard', '', 1, '2022-05-16 07:01:34', '2025-08-25 08:58:06'),
(41, 11, 'design_marksheet', NULL, 'design_marksheet', 'admin/marksheet', 6, '(\'design_marksheet\', \'can_view\')', NULL, 'marksheet', 'index,edit', '', 1, '2022-07-23 07:35:35', '2025-08-25 08:58:06'),
(42, 11, 'print_marksheet', NULL, 'print_marksheet', 'admin/examresult/marksheet', 7, '(\'print_marksheet\', \'can_view\')', NULL, 'examresult', 'marksheet', '', 1, '2022-05-16 07:01:38', '2025-08-25 08:58:06'),
(43, 11, 'marks_grade', NULL, 'marks_grade', 'admin/grade', 8, '(\'marks_grade\', \'can_view\')', NULL, 'grade', 'index,edit', '', 1, '2022-07-23 07:37:15', '2025-08-25 08:58:06'),
(44, 11, 'marks_division', NULL, 'marks_division', 'admin/marksdivision', 9, '(\'marks_division\', \'can_view\')', NULL, 'marksdivision', 'index,edit', '', 1, '2022-08-25 06:04:26', '2025-08-25 08:58:06'),
(45, 12, 'online_exam', NULL, 'online_exam', 'admin/onlineexam', 1, '(\'online_examination\', \'can_view\')', NULL, 'onlineexam', 'index,evalution,assign', '', 1, '2022-08-30 13:03:45', '2025-08-25 08:58:06'),
(46, 12, 'question_bank', NULL, 'question_bank', 'admin/question', 1, '(\'question_bank\', \'can_view\')', NULL, 'question', 'index,read', '', 1, '2022-08-30 11:03:13', '2025-08-25 08:58:06'),
(47, 13, 'manage_lesson_plan', NULL, 'manage_lesson_plan', 'admin/syllabus', 2, '(\'manage_lesson_plan\', \'can_view\')', NULL, 'syllabus', 'index', '', 1, '2022-09-03 16:59:31', '2025-08-25 08:58:06'),
(48, 13, 'manage_syllabus_status', NULL, 'manage_syllabus_status', 'admin/syllabus/status', 3, '(\'manage_syllabus_status\', \'can_view\')', NULL, 'syllabus', 'status', '', 1, '2022-09-03 16:59:35', '2025-08-25 08:58:06'),
(49, 13, 'lesson', NULL, 'lesson', 'admin/lessonplan/lesson', 4, '(\'lesson\', \'can_view\')', NULL, 'lessonplan', 'lesson,editlesson', '', 1, '2022-09-15 11:30:55', '2025-08-25 08:58:06'),
(50, 13, 'topic', NULL, 'topic', 'admin/lessonplan/topic', 5, '(\'topic\', \'can_view\')', NULL, 'lessonplan', 'topic,edittopic', '', 1, '2022-09-15 11:30:24', '2025-08-25 08:58:06'),
(51, 14, 'class_timetable', NULL, 'class_timetable', 'admin/timetable/classreport', 1, '(\'class_timetable\', \'can_view\')', NULL, 'timetable', 'classreport,create', '', 1, '2022-07-23 09:01:22', '2025-08-25 08:58:06'),
(52, 14, 'teachers_timetable', NULL, 'teachers_timetable', 'admin/timetable/mytimetable', 2, '(\'teachers_time_table\', \'can_view\')', NULL, 'timetable', 'mytimetable', '', 1, '2022-07-20 12:22:59', '2025-08-25 08:58:06'),
(53, 14, 'assign_class_teacher', NULL, 'assign_class_teacher', 'admin/teacher/assign_class_teacher', 3, '(\'assign_class_teacher\', \'can_view\')', NULL, 'teacher', 'assign_class_teacher,update_class_teacher', '', 1, '2022-07-23 09:00:19', '2025-08-25 08:58:06'),
(54, 14, 'promote_students', NULL, 'promote_students', 'admin/stdtransfer', 4, '(\'promote_student\', \'can_view\')', NULL, 'stdtransfer', 'index', '', 1, '2022-07-20 12:22:54', '2025-08-25 08:58:06'),
(55, 14, 'subject_group', NULL, 'subject_group', 'admin/subjectgroup', 5, '(\'subject_group\', \'can_view\')', NULL, 'subjectgroup', 'index,edit', '', 1, '2022-07-23 08:59:42', '2025-08-25 08:58:06'),
(56, 14, 'subjects', NULL, 'subjects', 'admin/subject', 6, '(\'subject\', \'can_view\')', NULL, 'subject', 'index,edit', '', 1, '2022-07-23 08:59:20', '2025-08-25 08:58:06'),
(57, 14, 'class', NULL, 'class', 'classes', 7, '(\'class\', \'can_view\')', NULL, 'classes', 'index,edit', '', 1, '2022-07-23 08:58:49', '2025-08-25 08:58:06'),
(58, 14, 'sections', NULL, 'sections', 'sections', 8, '(\'section\', \'can_view\')', NULL, 'sections', 'index,edit', '', 1, '2022-07-23 08:58:21', '2025-08-25 08:58:06'),
(59, 15, 'staff_directory', NULL, 'staff_directory', 'admin/staff', 1, '(\'staff\', \'can_view\')', NULL, 'staff', 'index,edit,profile,create', '', 1, '2022-10-12 09:13:24', '2025-08-25 08:58:06'),
(60, 15, 'staff_attendance', NULL, 'staff_attendance', 'admin/staffattendance', 1, '(\'staff_attendance\', \'can_view\')', NULL, 'staffattendance', 'index', '', 1, '2022-09-07 12:04:15', '2025-08-25 08:58:06'),
(61, 15, 'payroll', NULL, 'payroll', 'admin/payroll', 1, '(\'staff_payroll\', \'can_view\')', NULL, 'payroll', 'index,edit,create', '', 1, '2022-08-16 11:58:44', '2025-08-25 08:58:06'),
(62, 15, 'approve_leave_request', NULL, 'approve_leave_request', 'admin/leaverequest/leaverequest', 1, '(\'approve_leave_request\', \'can_view\')', NULL, 'leaverequest', 'leaverequest', '', 1, '2022-05-16 09:04:33', '2025-08-25 08:58:06'),
(74, 15, 'apply_leave', NULL, 'apply_leave', 'admin/staff/leaverequest', 1, '(\'apply_leave\', \'can_view\')', NULL, 'staff', 'leaverequest', '', 1, '2022-05-16 09:11:41', '2025-08-25 08:58:06'),
(75, 15, 'leave_type', NULL, 'leave_type', 'admin/leavetypes', 1, '(\'leave_types\', \'can_view\')', NULL, 'leavetypes', 'index,leaveedit,createleavetype', '', 1, '2022-10-18 11:19:22', '2025-08-25 08:58:06'),
(76, 15, 'teachers_rating', NULL, 'teachers_rating', 'admin/staff/rating', 1, '(\'teachers_rating\', \'can_view\')', NULL, 'staff', 'rating', '', 1, '2022-05-16 09:15:31', '2025-08-25 08:58:06'),
(77, 15, 'department', NULL, 'department', 'admin/department/department', 1, '(\'department\', \'can_view\')', NULL, 'department', 'department,departmentedit', '', 1, '2022-07-23 09:14:20', '2025-08-25 08:58:06'),
(78, 15, 'designation', NULL, 'designation', 'admin/designation/designation', 1, '(\'designation\', \'can_view\')', NULL, 'designation', 'designation,designationedit', '', 1, '2022-07-23 09:15:04', '2025-08-25 08:58:06'),
(79, 15, 'disabled_staff', NULL, 'disabled_staff', 'admin/staff/disablestafflist', 1, '(\'disable_staff\', \'can_view\')', NULL, 'staff', 'disablestafflist', '', 1, '2022-09-13 07:46:56', '2025-08-25 08:58:06'),
(80, 16, 'notice_board', NULL, 'notice_board', 'admin/notification', 1, '(\'notice_board\', \'can_view\')', NULL, 'notification', 'index,edit,add', '', 1, '2022-07-23 09:17:24', '2025-08-25 08:58:06'),
(81, 16, 'send_email', NULL, 'send_email', 'admin/mailsms/compose', 2, '(\'email\', \'can_view\')', NULL, 'mailsms', 'compose', '', 1, '2022-09-02 16:52:46', '2025-08-25 08:58:06'),
(82, 16, 'send_sms', NULL, 'send_sms', 'admin/mailsms/compose_sms', 3, '(\'sms\', \'can_view\')', NULL, 'mailsms', 'compose_sms', '', 1, '2022-09-02 16:52:46', '2025-08-25 08:58:06'),
(83, 16, 'email_sms_log', NULL, 'email_sms_log', 'admin/mailsms/index', 4, '(\'email_sms_log\', \'can_view\')', NULL, 'mailsms', 'index', '', 1, '2022-09-02 16:52:50', '2025-08-25 08:58:06'),
(84, 16, 'schedule_email_sms_log', NULL, 'schedule_email_sms_log', 'admin/mailsms/schedule', 5, '(\'schedule_email_sms_log\', \'can_view\')', NULL, 'mailsms', 'schedule,edit_schedule', '', 1, '2022-09-13 07:07:38', '2025-08-25 08:58:06'),
(85, 16, 'login_credentials_send', NULL, 'login_credentials_send', 'student/bulkmail', 6, '(\'login_credentials_send\', \'can_view\')', NULL, 'student', 'bulkmail', '', 1, '2022-09-02 16:52:46', '2025-08-25 08:58:06'),
(86, 16, 'email_template', NULL, 'email_template', 'admin/mailsms/email_template', 7, '(\'email_template\', \'can_view\')', NULL, 'mailsms', 'email_template', '', 1, '2022-09-02 16:52:46', '2025-08-25 08:58:06'),
(87, 16, 'sms_template', NULL, 'sms_template', 'admin/mailsms/sms_template', 8, '(\'sms_template\', \'can_view\')', NULL, 'mailsms', 'sms_template', '', 1, '2022-09-02 16:52:46', '2025-08-25 08:58:06'),
(88, 17, 'content_type', NULL, 'content_type', 'admin/contenttype', 3, '(\'content_type\', \'can_view\')', NULL, 'contenttype', 'index,edit', '', 1, '2022-07-23 09:24:45', '2025-08-25 08:58:06'),
(89, 17, 'content_share_list', NULL, 'content_share_list', 'admin/content/list', 2, '(\'content_share_list\', \'can_view\')', NULL, 'content', 'list', '', 1, '2022-07-22 10:07:17', '2025-08-25 08:58:06'),
(90, 17, 'upload_content', NULL, 'upload_content', 'admin/content/upload', 1, '(\'upload_content\', \'can_view\')', NULL, 'content', 'upload', '', 1, '2022-07-22 10:07:17', '2025-08-25 08:58:06'),
(91, 17, 'video_tutorial', NULL, 'video_tutorial', 'admin/video_tutorial', 4, '(\'video_tutorial\', \'can_view\')', NULL, 'video_tutorial', 'index', '', 1, '2022-07-22 10:07:17', '2025-08-25 08:58:06'),
(92, 18, 'add_homework', NULL, 'add_homework', 'homework', 1, '(\'homework\', \'can_view\')', NULL, 'homework', 'index', '', 1, '2022-06-25 09:50:01', '2025-08-25 08:58:06'),
(93, 18, 'daily_assignment', NULL, 'daily_assignment', 'homework/dailyassignment', 2, '(\'daily_assignment\', \'can_view\')', NULL, 'homework', 'dailyassignment', '', 1, '2022-07-23 09:27:23', '2025-08-25 08:58:06'),
(94, 19, 'book_list', NULL, 'book_list', 'admin/book/getall', 1, '(\'books\', \'can_view\')', NULL, 'book', 'getall,index,edit,import,issue_returnreport', '', 1, '2022-09-07 11:45:50', '2025-08-25 08:58:06'),
(95, 19, 'issue_return', NULL, 'issue_return', 'admin/member', 1, '(\'issue_return\', \'can_view\')', NULL, 'member', 'index,issue', '', 1, '2022-07-23 09:32:48', '2025-08-25 08:58:06'),
(96, 19, 'add_student', NULL, 'add_student', 'admin/member/student', 1, '(\'add_student\', \'can_view\')', NULL, 'member', 'student', '', 1, '2022-05-16 11:22:54', '2025-08-25 08:58:06'),
(97, 19, 'add_staff_member', NULL, 'add_staff_member', 'admin/member/teacher', 1, '(\'add_staff_member\', \'can_view\')', NULL, 'member', 'teacher', '', 1, '2022-05-16 11:31:43', '2025-08-25 08:58:06'),
(98, 7, 'search_expense', NULL, 'search_expense', 'admin/expense/expensesearch', 1, '(\'search_expense\', \'can_view\')', NULL, 'expense', 'expensesearch', '', 1, '2022-05-16 11:36:09', '2025-08-25 08:58:06'),
(99, 7, 'expense_head', NULL, 'expense_head', 'admin/expensehead', 1, '(\'expense_head\', \'can_view\')', NULL, 'expensehead', 'index,edit', '', 1, '2022-07-23 07:16:17', '2025-08-25 08:58:06'),
(100, 20, 'issue_item', NULL, 'issue_item', 'admin/issueitem', 1, '(\'issue_item\', \'can_view\')', NULL, 'issueitem', 'index,create', '', 1, '2022-07-23 09:35:03', '2025-08-25 08:58:06'),
(101, 20, 'add_item_stock', NULL, 'add_item_stock', 'admin/itemstock', 1, '(\'item_stock\', \'can_view\')', NULL, 'itemstock', 'index,edit', '', 1, '2022-07-23 09:36:17', '2025-08-25 08:58:06'),
(102, 20, 'add_item', NULL, 'add_item', 'admin/item', 1, '(\'item\', \'can_view\')', NULL, 'item', 'index,edit', '', 1, '2022-07-23 09:36:56', '2025-08-25 08:58:06'),
(103, 20, 'item_category', NULL, 'item_category', 'admin/itemcategory', 1, '(\'item_category\', \'can_view\')', NULL, 'itemcategory', 'index,edit', '', 1, '2022-07-23 09:37:12', '2025-08-25 08:58:06'),
(104, 20, 'item_store', NULL, 'item_store', 'admin/itemstore', 1, '(\'store\', \'can_view\')', NULL, 'itemstore', 'index,edit,create', '', 1, '2022-09-16 11:49:03', '2025-08-25 08:58:06'),
(105, 20, 'item_supplier', NULL, 'item_supplier', 'admin/itemsupplier', 1, '(\'supplier\', \'can_view\')', NULL, 'itemsupplier', 'index,edit,create', '', 1, '2022-07-23 09:38:22', '2025-08-25 08:58:06'),
(106, 21, 'fees_master', NULL, 'fees_master', 'admin/transport/feemaster', 1, '(\'transport_fees_master\', \'can_view\')', NULL, 'transport', 'feemaster', '', 1, '2023-03-31 05:33:14', '2025-08-25 08:58:06'),
(107, 21, 'pickup_point', NULL, 'pickup_point', 'admin/pickuppoint', 1, '(\'pickup_point\', \'can_view\')', NULL, 'pickuppoint', 'index', '', 1, '2023-03-31 05:24:24', '2025-08-25 08:58:06'),
(108, 21, 'routes', NULL, 'routes', 'admin/route', 1, '(\'routes\', \'can_view\')', NULL, 'route', 'index,edit', '', 1, '2022-09-17 06:21:23', '2025-08-25 08:58:06'),
(109, 21, 'vehicles', NULL, 'vehicles', 'admin/vehicle', 1, '(\'vehicle\', \'can_view\')', NULL, 'vehicle', 'index', '', 1, '2022-05-16 12:29:35', '2025-08-25 08:58:06'),
(110, 21, 'assign_vehicle', NULL, 'assign_vehicle', 'admin/vehroute', 1, '(\'assign_vehicle\',\'can_view\')', NULL, 'vehroute', 'index,edit', '', 1, '2022-10-19 07:06:08', '2025-08-25 08:58:06'),
(111, 21, 'route_pickup_point', NULL, 'route_pickup_point', 'admin/pickuppoint/assign', 1, '(\'route_pickup_point\', \'can_view\')', NULL, 'pickuppoint', 'assign', '', 1, '2023-03-31 05:25:08', '2025-08-25 08:58:06'),
(112, 21, 'student_transport_fees', NULL, 'student_transport_fees', 'admin/pickuppoint/student_fees', 1, '(\'student_transport_fees\', \'can_view\')', NULL, 'pickuppoint', 'student_fees', '', 1, '2023-03-31 05:25:43', '2025-08-25 08:58:06'),
(113, 22, 'hostel_rooms', NULL, 'hostel_rooms', 'admin/hostelroom', 1, '(\'hostel_rooms\', \'can_view\')', NULL, 'hostelroom', 'index,edit', '', 1, '2022-07-23 10:27:48', '2025-08-25 08:58:06'),
(114, 22, 'room_type', NULL, 'room_type', 'admin/roomtype', 2, '(\'room_type\', \'can_view\')', NULL, 'roomtype', 'index,edit', '', 1, '2022-07-23 10:32:14', '2025-08-25 08:58:06'),
(115, 22, 'hostel', NULL, 'hostel', 'admin/hostel', 3, '(\'hostel\', \'can_view\')', NULL, 'hostel', 'index,edit', '', 1, '2022-07-23 10:32:39', '2025-08-25 08:58:06'),
(116, 23, 'student_certificate', NULL, 'student_certificate', 'admin/certificate', 1, '(\'student_certificate\', \'can_view\')', NULL, 'certificate', 'index,edit', '', 1, '2022-07-23 10:44:30', '2025-08-25 08:58:06'),
(117, 23, 'generate_certificate', NULL, 'generate_certificate', 'admin/generatecertificate', 1, '(\'generate_certificate\', \'can_view\')', NULL, 'generatecertificate', 'index,search', '', 1, '2022-07-23 10:46:16', '2025-08-25 08:58:06'),
(118, 23, 'student_id_card', NULL, 'student_id_card', 'admin/studentidcard', 1, '(\'student_id_card\', \'can_view\')', NULL, 'studentidcard', 'index,edit', '', 1, '2022-07-23 10:47:01', '2025-08-25 08:58:06'),
(119, 23, 'generate_id_card', NULL, 'generate_id_card', 'admin/generateidcard/search', 1, '(\'generate_id_card\', \'can_view\')', NULL, 'generateidcard', 'search', '', 1, '2022-05-18 05:35:13', '2025-08-25 08:58:06'),
(120, 23, 'staff_id_card', NULL, 'staff_id_card', 'admin/staffidcard', 1, '(\'staff_id_card\', \'can_view\')', NULL, 'staffidcard', 'index,edit', '', 1, '2022-07-23 10:48:13', '2025-08-25 08:58:06'),
(121, 23, 'generate_staff_id_card', NULL, 'generate_staff_id_card', 'admin/generatestaffidcard', 1, '(\'generate_staff_id_card\', \'can_view\')', NULL, 'generatestaffidcard', 'index,search', '', 1, '2022-07-23 10:49:06', '2025-08-25 08:58:06'),
(122, 24, 'event', NULL, 'event', 'admin/front/events', 1, '(\'event\', \'can_view\')', NULL, 'events', 'index,edit,create', '', 1, '2022-07-23 10:51:51', '2025-08-25 08:58:06'),
(123, 24, 'gallery', NULL, 'gallery', 'admin/front/gallery', 1, '(\'gallery\', \'can_view\')', NULL, 'gallery', 'index,edit,create', '', 1, '2022-07-23 10:52:22', '2025-08-25 08:58:06'),
(124, 24, 'news', NULL, 'news', 'admin/front/notice', 1, '(\'notice\', \'can_view\')', NULL, 'notice', 'index,edit,create', '', 1, '2022-07-23 10:54:23', '2025-08-25 08:58:06'),
(125, 24, 'media_manager', NULL, 'media_manager', 'admin/front/media', 1, '(\'media_manager\', \'can_view\')', NULL, 'media', 'index', '', 1, '2022-05-18 06:03:32', '2025-08-25 08:58:06'),
(126, 24, 'pages', NULL, 'pages', 'admin/front/page', 1, '(\'pages\', \'can_view\')', NULL, 'page', 'index,edit,create', '', 1, '2022-07-23 10:55:28', '2025-08-25 08:58:06'),
(127, 24, 'menus', NULL, 'menus', 'admin/front/menus', 1, '(\'menus\', \'can_view\')', NULL, 'menus', 'index,additem', '', 1, '2022-07-23 10:56:31', '2025-08-25 08:58:06'),
(128, 24, 'banner_images', NULL, 'banner_images', 'admin/front/banner', 1, '(\'banner_images\', \'can_view\')', NULL, 'banner', 'index', '', 1, '2022-05-18 06:10:53', '2025-08-25 08:58:06'),
(129, 25, 'manage_alumini', NULL, 'manage_alumini', 'admin/alumni/alumnilist', 1, '(\'manage_alumni\', \'can_view\')', NULL, 'alumni', 'alumnilist', '', 1, '2022-07-23 10:58:36', '2025-08-25 08:58:06'),
(130, 25, 'events', NULL, 'events', 'admin/alumni/events', 1, '(\'events\', \'can_view\')', NULL, 'alumni', 'events', '', 1, '2022-07-23 10:59:09', '2025-08-25 08:58:06'),
(131, 26, 'student_information', NULL, 'student_information', 'report/studentinformation', 1, '(\'student_report\', \'can_view\') || (\'guardian_report\', \'can_view\') || (\'student_history\', \'can_view\') || (\'student_login_credential_report\', \'can_view\') || (\'class_subject_report\', \'can_view\') || (\'admission_report\', \'can_view\') || (\'sibling_report\', \'can_view\') || (\'homehork_evaluation_report\', \'can_view\') || (\'student_profile\', \'can_view\') || (\'student_gender_ratio_report\', \'can_view\') || (\'student_teacher_ratio_report\', \'can_view\')', NULL, 'report', 'studentinformation,studentreport,online_admission_report,student_teacher_ratio,boys_girls_ratio,student_profile,sibling_report,admission_report,class_subject,classsectionreport,guardianreport,admissionreport,logindetailreport,parentlogindetailreport', '', 1, '2022-09-26 05:26:53', '2025-08-25 08:58:06'),
(132, 26, 'finance', NULL, 'finance', 'financereports/finance', 2, '(\'fees_statement\', \'can_view\') || (\'balance_fees_report\', \'can_view\') || (\'fees_collection_report\', \'can_view\') || (\'online_fees_collection_report\', \'can_view\') || (\'income_report\', \'can_view\') || (\'expense_report\', \'can_view\') || (\'payroll_report\', \'can_view\') || (\'income_group_report\', \'can_view\') || (\'expense_group_report\', \'can_view\') || (\'online_admission\', \'can_view\')', NULL, 'financereports', 'finance,reportduefees,reportdailycollection,reportbyname,studentacademicreport,collection_report,onlinefees_report,duefeesremark,income,expense,payroll,incomegroup,expensegroup,onlineadmission', '', 1, '2022-09-24 12:20:32', '2025-08-25 08:58:06'),
(133, 26, 'attendance', NULL, 'attendance', 'attendencereports/attendance', 3, '(\'attendance_report\', \'can_view\') || (\'student_attendance_type_report\', \'can_view\') || (\'daily_attendance_report\', \'can_view\') || (\'staff_attendance_report\', \'can_view\')', NULL, 'attendencereports', 'attendance,classattendencereport,attendancereport,daily_attendance_report,staffattendancereport,biometric_attlog,reportbymonthstudent,reportbymonth', '', 1, '2022-09-26 11:36:08', '2025-08-25 08:58:06'),
(134, 26, 'examinations', NULL, 'examinations', 'admin/examresult/examinations', 4, '(\'rank_report\', \'can_view\')', NULL, 'examresult', 'rankreport,examinations', '', 1, '2022-09-20 08:34:13', '2025-08-25 08:58:06'),
(135, 26, 'lesson_plan', NULL, 'lesson_plan', 'report/lesson_plan', 6, '(\'syllabus_status_report\', \'can_view\') || (\'teacher_syllabus_status_report\', \'can_view\')', NULL, 'report', 'lesson_plan,teachersyllabusstatus', '', 1, '2022-07-25 11:39:17', '2025-08-25 08:58:06'),
(136, 26, 'human_resource', NULL, 'human_resource', 'report/human_resource', 7, '(\'staff_report\', \'can_view\') || (\'payroll_report\', \'can_view\')', NULL, 'report', 'human_resource,staff_report,payrollreport', '', 1, '2022-07-25 11:38:20', '2025-08-25 08:58:06'),
(137, 26, 'library', NULL, 'library', 'report/library', 9, '(\'book_issue_report\', \'can_view\') || (\'book_due_report\', \'can_view\') || (\'book_issue_return_report\', \'can_view\') || (\'book_inventory_report\', \'can_view\')', NULL, 'report', 'library,studentbookissuereport,bookduereport,bookinventory', '', 1, '2022-09-07 11:53:15', '2025-08-25 08:58:06'),
(138, 26, 'inventory', NULL, 'inventory', 'report/inventory', 10, '(\'stock_report\', \'can_view\') || (\'add_item_report\', \'can_view\') || (\'issue_item_report\', \'can_view\')', NULL, 'report', 'inventory,inventorystock,additem,issueinventory', '', 1, '2022-07-25 11:30:57', '2025-08-25 08:58:06'),
(139, 26, 'hostel', NULL, 'hostel', 'admin/hostelroom/studenthosteldetails', 12, '(\'hostel_report\', \'can_view\')', NULL, 'hostelroom', 'studenthosteldetails', '', 1, '2022-07-20 12:30:07', '2025-08-25 08:58:06'),
(140, 26, 'alumni', NULL, 'alumni', 'report/alumnireport', 13, '(\'alumni_report\', \'can_view\')', NULL, 'report', 'alumnireport', '', 1, '2022-07-20 12:30:07', '2025-08-25 08:58:06'),
(141, 26, 'user_log', NULL, 'user_log', 'admin/userlog', 14, '(\'user_log\', \'can_view\')', NULL, 'userlog', 'index', '', 1, '2022-07-20 12:30:07', '2025-08-25 08:58:06'),
(142, 26, 'audit_trail_report', NULL, 'audit_trail_report', 'admin/audit', 15, '(\'audit_trail_report\', \'can_view\')', NULL, 'audit', 'index', '', 1, '2022-07-20 12:30:07', '2025-08-25 08:58:06'),
(143, 26, 'online_examinations', NULL, 'online_examinations', 'admin/onlineexam/report', 5, '(\'online_exam_wise_report\', \'can_view\') || (\'online_exams_report\', \'can_view\') || (\'online_exams_attempt_report\', \'can_view\') || (\'online_exams_rank_report\', \'can_view\')', NULL, 'onlineexam', 'report,onlineexams', '', 1, '2022-07-25 11:48:23', '2025-08-25 08:58:06'),
(144, 26, 'homework', NULL, 'homework', 'homework/homeworkordailyassignmentreport', 8, '(\'homework\', \'can_view\') || (\'daily_assignment\', \'can_view\')', NULL, 'homework', 'homeworkordailyassignmentreport,homeworkreport,evaluation_report,dailyassignmentreport', '', 1, '2022-09-21 09:28:47', '2025-08-25 08:58:06'),
(145, 26, 'transport', NULL, 'transport', 'admin/route/studenttransportdetails', 11, '(\'transport_report\', \'can_view\')', NULL, 'route', 'studenttransportdetails', '', 1, '2022-07-20 12:30:07', '2025-08-25 08:58:06'),
(146, 27, 'general_setting', NULL, 'general_setting', 'schsettings', 1, '(\'general_setting\', \'can_view\')', NULL, 'schsettings', 'index,logo,miscellaneous,backendtheme,mobileapp,studentguardianpanel,fees,idautogeneration,attendancetype,maintenance,whatsappsettings', '', 1, '2025-02-13 09:03:12', '2025-08-25 08:58:06'),
(147, 27, 'session_setting', NULL, 'session_setting', 'sessions', 2, '(\'session_setting\', \'can_view\')', NULL, 'sessions', 'index,edit', '', 1, '2022-07-23 11:57:16', '2025-08-25 08:58:06'),
(148, 27, 'notification_setting', NULL, 'notification_setting', 'admin/notification/setting', 3, '(\'notification_setting\', \'can_view\')', NULL, 'notification', 'setting', '', 1, '2022-07-08 08:12:28', '2025-08-25 08:58:06'),
(149, 27, 'sms_setting', NULL, 'sms_setting', 'smsconfig', 4, '(\'sms_setting\', \'can_view\')', NULL, 'smsconfig', 'index', '', 1, '2022-07-08 08:12:28', '2025-08-25 08:58:06'),
(150, 27, 'email_setting', NULL, 'email_setting', 'emailconfig', 5, '(\'email_setting\', \'can_view\')', NULL, 'emailconfig', 'index', '', 1, '2022-07-08 08:12:28', '2025-08-25 08:58:06'),
(151, 27, 'payment_methods', NULL, 'payment_methods', 'admin/paymentsettings', 6, '(\'payment_methods\', \'can_view\')', NULL, 'paymentsettings', 'index', '', 1, '2022-07-08 08:12:28', '2025-08-25 08:58:06'),
(152, 27, 'print_headerfooter', NULL, 'print_headerfooter', 'admin/print_headerfooter', 7, '(\'print_header_footer\', \'can_view\')', NULL, 'print_headerfooter', 'index', '', 1, '2022-07-08 08:12:28', '2025-08-25 08:58:06'),
(153, 27, 'front_cms_setting', NULL, 'front_cms_setting', 'admin/frontcms', 8, '(\'front_cms_setting\', \'can_view\')', NULL, 'frontcms', 'index', '', 1, '2022-07-08 08:12:28', '2025-08-25 08:58:06'),
(154, 27, 'roles_permissions', NULL, 'roles_permissions', 'admin/roles', 9, '(\'superadmin\', \'can_view\')', NULL, 'roles', 'index,permission', '', 1, '2022-09-09 11:03:34', '2025-08-25 08:58:06'),
(155, 27, 'backup_restore', NULL, 'backup_restore', 'admin/admin/backup', 10, '(\'backup\', \'can_view\')', NULL, 'admin', 'backup', '', 1, '2022-07-08 08:12:28', '2025-08-25 08:58:06'),
(156, 27, 'users', NULL, 'users', 'admin/users', 13, '(\'user_status\', \'can_view\')', NULL, 'users', 'index', '', 1, '2022-07-20 12:34:09', '2025-08-25 08:58:06'),
(157, 27, 'languages', NULL, 'languages', 'admin/language', 11, '(\'languages\', \'can_view\')', NULL, 'language', 'index,create', '', 1, '2022-09-10 09:14:52', '2025-08-25 08:58:06'),
(158, 27, 'modules', NULL, 'modules', 'admin/module', 14, '(\'superadmin\', \'can_view\')', NULL, 'module', 'index', '', 1, '2022-07-20 12:34:06', '2025-08-25 08:58:06'),
(159, 27, 'custom_fields', NULL, 'custom_fields', 'admin/customfield', 15, '(\'custom_fields\', \'can_view\')', NULL, 'customfield', 'index,edit', '', 1, '2022-07-23 12:02:14', '2025-08-25 08:58:06'),
(160, 27, 'captcha_setting', NULL, 'captcha_setting', 'admin/captcha', 16, '(\'superadmin\', \'can_view\')', NULL, 'captcha', 'index', '', 1, '2022-07-20 12:34:06', '2025-08-25 08:58:06'),
(161, 27, 'system_fields', NULL, 'system_fields', 'admin/systemfield', 17, '(\'system_fields\', \'can_view\')', NULL, 'systemfield', 'index', '', 1, '2022-07-22 06:07:38', '2025-08-25 08:58:06'),
(162, 27, 'student_profile_update', NULL, 'student_profile_update', 'student/profilesetting', 18, '(\'student_profile_update\', \'can_view\')', NULL, 'student', 'profilesetting', '', 1, '2022-07-20 12:34:06', '2025-08-25 08:58:06'),
(163, 27, 'online_admission', NULL, 'online_admission', 'admin/onlineadmission/admissionsetting', 19, '(\'online_admission\', \'can_view\')', NULL, 'onlineadmission', 'admissionsetting', '', 1, '2022-07-20 12:34:06', '2025-08-25 08:58:06'),
(164, 27, 'file_types', NULL, 'file_types', 'admin/admin/filetype', 20, '(\'superadmin\', \'can_view\')', NULL, 'admin', 'filetype', '', 1, '2022-07-20 12:34:30', '2025-08-25 08:58:06'),
(165, 27, 'system_update', NULL, 'system_update', 'admin/updater', 22, '(\'superadmin\', \'can_view\')', NULL, 'updater', 'index', '', 1, '2022-10-13 11:49:51', '2025-08-25 08:58:06'),
(166, 27, 'sidebar_menu', NULL, 'sidebar_menu', 'admin/sidemenu', 21, '(\'sidebar_menu\', \'can_view\')', NULL, 'sidemenu', 'index', '', 1, '2022-10-13 11:49:51', '2025-08-25 08:58:06'),
(181, 3, 'fees_discount', NULL, 'fees_discount', 'admin/feediscount', 8, '(\'fees_discount\', \'can_view\')', NULL, 'feediscount', 'index,edit,assign', '', 1, '2022-08-08 06:03:27', '2025-08-25 08:58:06'),
(182, 3, 'fees_carry_forward', NULL, 'fees_carry_forward', 'admin/feesforward', 9, '(\'fees_carry_forward\', \'can_view\')', NULL, 'feesforward', 'index', '', 1, '2022-08-08 06:03:24', '2025-08-25 08:58:06'),
(183, 3, 'fees_reminder', NULL, 'fees_reminder', 'admin/feereminder/setting', 10, '(\'fees_reminder\', \'can_view\')', NULL, 'feereminder', 'setting', '', 1, '2022-08-08 06:03:21', '2025-08-25 08:58:06'),
(184, 27, 'currency', NULL, 'currency', 'admin/currency', 12, '(\'currency\', \'can_view\')', NULL, 'currency', 'index', '', 1, '2022-07-20 12:34:09', '2025-08-25 08:58:06'),
(190, 3, 'offline_bank_payments', NULL, 'offline_bank_payments', 'admin/offlinepayment', 2, '(\'offline_bank_payments\', \'can_view\')', NULL, 'offlinepayment', 'index', '', 1, '2022-08-08 06:05:29', '2025-08-25 08:58:06'),
(191, 13, 'Copy Old Lessons', NULL, 'copy_old_lesson', 'admin/lessonplan/copylesson', 1, '(\'copy_old_lesson\', \'can_view\')', NULL, 'lessonplan', 'copylesson', NULL, 1, '2022-09-09 10:20:37', '2025-08-25 08:58:06'),
(192, 10, 'Period Attendance', 'period_attendance', 'period_attendance', 'admin/subjectattendence/index', 4, '(\'student_attendance\',\'can_view\')', NULL, 'subjectattendence', 'index', NULL, 0, '2022-10-20 05:50:25', '2025-08-25 08:58:06'),
(193, 10, 'Period Attendance By Date', 'period_attendance_by_date', 'period_attendance_by_date', 'admin/subjectattendence/reportbydate', 5, '(\'attendance_by_date\', \'can_view\')', NULL, 'subjectattendence', 'reportbydate', NULL, 0, '2022-10-20 05:50:25', '2025-08-25 08:58:06'),
(198, 33, 'overview', NULL, 'overview', 'admin/multibranch/branch/overview', 1, '(\'multi_branch_overview\', \'can_view\')', NULL, 'branch', 'overview', '', 1, '2025-08-15 01:05:27', '2025-08-25 09:08:28'),
(199, 33, 'report', NULL, 'report', 'admin/multibranch/finance/index', 1, '(\'multi_branch_daily_collection_report\', \'can_view\') || (\'multi_branch_payroll\', \'can_view\') || (\'multi_branch_income_report\', \'can_view\') || (\'multi_branch_expense_report\', \'can_view\') || (\'multi_branch_user_log_report\', \'can_view\')', NULL, 'finance', 'dailycollectionreport,payroll,incomelist,expenselist,incomereport,expensereport,userlogreport,index', '', 1, '2025-08-22 00:59:38', '2025-08-25 09:08:28'),
(200, 33, 'setting', NULL, 'setting', 'admin/multibranch/branch', 1, '(\'multi_branch_setting\', \'can_view\')', NULL, 'branch', 'index', '', 1, '2025-08-15 00:45:32', '2025-08-25 09:08:28'),
(215, 36, 'annual_calendar', NULL, 'annual_calendar', 'admin/holiday/index', 1, '(\'annual_calendar\', \'can_view\')', NULL, 'holiday', 'index', '', 1, '2024-10-14 12:07:58', '2025-08-25 08:58:06'),
(216, 36, 'holiday_type', NULL, 'holiday_type', 'admin/holiday/holidaytype', 1, '(\'holiday_type\', \'can_view\')', NULL, 'holiday', 'holidaytype,editholidaytype', '', 1, '2024-10-14 12:06:02', '2025-08-25 08:58:06'),
(217, 37, 'download_cv', NULL, 'download_cv', 'admin/resume/download', 2, '(\'download_cv\', \'can_view\')', NULL, 'resume', 'download', NULL, 1, '2025-01-09 08:05:11', '2025-08-25 08:58:06'),
(218, 37, 'build_cv', NULL, 'build_cv', 'admin/resume/index', 1, '(\'build_cv\', \'can_view\')', NULL, 'resume', 'index,resume_setting,student_resume_details', NULL, 1, '2024-12-06 11:42:02', '2025-08-25 08:58:06'),
(219, 27, 'addons', NULL, 'addons', 'admin/addons', 13, '(\'superadmin\', \'can_view\')', NULL, 'addons', 'index', '', 1, '2024-12-21 11:43:48', '2025-08-25 08:58:06');

-- --------------------------------------------------------

--
-- Table structure for table `sms_config`
--

DROP TABLE IF EXISTS `sms_config`;
CREATE TABLE IF NOT EXISTS `sms_config` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `api_id` varchar(100) NOT NULL,
  `authkey` varchar(100) NOT NULL,
  `senderid` varchar(100) NOT NULL,
  `contact` text,
  `username` varchar(150) DEFAULT NULL,
  `url` varchar(150) DEFAULT NULL,
  `password` varchar(150) DEFAULT NULL,
  `is_active` varchar(255) DEFAULT 'disabled',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `sms_template`
--

DROP TABLE IF EXISTS `sms_template`;
CREATE TABLE IF NOT EXISTS `sms_template` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `source`
--

DROP TABLE IF EXISTS `source`;
CREATE TABLE IF NOT EXISTS `source` (
  `id` int NOT NULL AUTO_INCREMENT,
  `source` varchar(100) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

DROP TABLE IF EXISTS `staff`;
CREATE TABLE IF NOT EXISTS `staff` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` varchar(200) NOT NULL,
  `lang_id` int NOT NULL,
  `currency_id` int DEFAULT '0',
  `department` int DEFAULT NULL,
  `designation` int DEFAULT NULL,
  `qualification` varchar(200) NOT NULL,
  `work_exp` varchar(200) NOT NULL,
  `name` varchar(200) NOT NULL,
  `surname` varchar(200) NOT NULL,
  `father_name` varchar(200) NOT NULL,
  `mother_name` varchar(200) NOT NULL,
  `contact_no` varchar(200) NOT NULL,
  `emergency_contact_no` varchar(200) NOT NULL,
  `email` varchar(200) NOT NULL,
  `dob` date NOT NULL,
  `marital_status` varchar(100) NOT NULL,
  `date_of_joining` date DEFAULT NULL,
  `date_of_leaving` date DEFAULT NULL,
  `local_address` varchar(300) NOT NULL,
  `permanent_address` varchar(200) NOT NULL,
  `note` varchar(200) NOT NULL,
  `image` varchar(200) NOT NULL,
  `password` varchar(250) NOT NULL,
  `gender` varchar(50) NOT NULL,
  `account_title` varchar(200) NOT NULL,
  `bank_account_no` varchar(200) NOT NULL,
  `bank_name` varchar(200) NOT NULL,
  `ifsc_code` varchar(200) NOT NULL,
  `bank_branch` varchar(100) NOT NULL,
  `payscale` varchar(200) NOT NULL,
  `basic_salary` int DEFAULT NULL,
  `epf_no` varchar(200) NOT NULL,
  `contract_type` varchar(100) NOT NULL,
  `shift` varchar(100) NOT NULL,
  `location` varchar(100) NOT NULL,
  `facebook` varchar(200) NOT NULL,
  `twitter` varchar(200) NOT NULL,
  `linkedin` varchar(200) NOT NULL,
  `instagram` varchar(200) NOT NULL,
  `resume` varchar(200) NOT NULL,
  `joining_letter` varchar(200) NOT NULL,
  `resignation_letter` varchar(200) NOT NULL,
  `other_document_name` varchar(200) NOT NULL,
  `other_document_file` varchar(200) NOT NULL,
  `user_id` int NOT NULL,
  `is_active` int NOT NULL,
  `verification_code` varchar(100) NOT NULL,
  `disable_at` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_id` (`employee_id`),
  KEY `designation` (`designation`),
  KEY `department` (`department`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`id`, `employee_id`, `lang_id`, `currency_id`, `department`, `designation`, `qualification`, `work_exp`, `name`, `surname`, `father_name`, `mother_name`, `contact_no`, `emergency_contact_no`, `email`, `dob`, `marital_status`, `date_of_joining`, `date_of_leaving`, `local_address`, `permanent_address`, `note`, `image`, `password`, `gender`, `account_title`, `bank_account_no`, `bank_name`, `ifsc_code`, `bank_branch`, `payscale`, `basic_salary`, `epf_no`, `contract_type`, `shift`, `location`, `facebook`, `twitter`, `linkedin`, `instagram`, `resume`, `joining_letter`, `resignation_letter`, `other_document_name`, `other_document_file`, `user_id`, `is_active`, `verification_code`, `disable_at`, `created_at`, `updated_at`) VALUES
(1, '9000', 0, 0, NULL, NULL, '', '', 'Super Admin', '', '', '', '', '', 'abdulrehman4845@gmail.com', '2020-01-01', '', NULL, NULL, '', '', '', '', '$2y$10$FPnXng/VGvVgVtPtxKzewOc8LRzJN0Yd3E8zJaQjw9XE8Ye6be48i', 'Male', '', '', '', '', '', '', NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', 0, 1, '', NULL, '2025-08-25 08:58:54', '2025-08-25 09:56:49');

-- --------------------------------------------------------

--
-- Table structure for table `staff_attendance`
--

DROP TABLE IF EXISTS `staff_attendance`;
CREATE TABLE IF NOT EXISTS `staff_attendance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `staff_id` int NOT NULL,
  `staff_attendance_type_id` int NOT NULL,
  `biometric_attendence` int DEFAULT '0',
  `qrcode_attendance` int NOT NULL DEFAULT '0',
  `biometric_device_data` text,
  `user_agent` varchar(250) DEFAULT NULL,
  `remark` varchar(200) NOT NULL,
  `is_active` int NOT NULL,
  `in_time` time DEFAULT NULL,
  `out_time` time DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `FK_staff_attendance_staff` (`staff_id`),
  KEY `FK_staff_attendance_staff_attendance_type` (`staff_attendance_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `staff_attendance_type`
--

DROP TABLE IF EXISTS `staff_attendance_type`;
CREATE TABLE IF NOT EXISTS `staff_attendance_type` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type` varchar(200) NOT NULL,
  `key_value` varchar(200) NOT NULL,
  `is_active` varchar(50) NOT NULL,
  `for_qr_attendance` int NOT NULL DEFAULT '1',
  `long_lang_name` varchar(250) DEFAULT NULL,
  `long_name_style` varchar(250) DEFAULT NULL,
  `for_schedule` int NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `staff_attendance_type`
--

INSERT INTO `staff_attendance_type` (`id`, `type`, `key_value`, `is_active`, `for_qr_attendance`, `long_lang_name`, `long_name_style`, `for_schedule`, `created_at`, `updated_at`) VALUES
(1, 'Present', '<b class=\"text text-success\">P</b>', 'yes', 1, 'present', 'label label-success', 1, '2023-12-13 10:15:57', '2023-11-10 00:00:00'),
(2, 'Late', '<b class=\"text text-warning\">L</b>', 'yes', 1, 'late', 'label label-warning', 1, '2023-12-13 10:16:01', '2023-11-10 00:00:00'),
(3, 'Absent', '<b class=\"text text-danger\">A</b>', 'yes', 0, 'absent', 'label label-danger', 0, '2023-12-13 10:16:06', '2023-11-10 00:00:00'),
(4, 'Half Day', '<b class=\"text text-warning\">F</b>', 'yes', 1, 'half_day', 'label label-info', 1, '2023-12-14 12:57:07', '2023-11-10 00:00:00'),
(5, 'Holiday', 'H', 'yes', 0, 'holiday', 'label label-warning text-dark', 0, '2023-12-13 10:16:17', '2023-11-10 00:00:00'),
(6, 'Half Day Second Shift', '<b class=\"text text-warning\">SH</b>', 'yes', 1, 'half_day_second_shift', 'label label-info', 1, '2024-09-24 12:28:42', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `staff_attendence_schedules`
--

DROP TABLE IF EXISTS `staff_attendence_schedules`;
CREATE TABLE IF NOT EXISTS `staff_attendence_schedules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `staff_attendence_type_id` int DEFAULT NULL,
  `role_id` int DEFAULT NULL,
  `entry_time_from` time DEFAULT NULL,
  `entry_time_to` time DEFAULT NULL,
  `total_institute_hour` time DEFAULT '00:00:00',
  `is_active` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `staff_designation`
--

DROP TABLE IF EXISTS `staff_designation`;
CREATE TABLE IF NOT EXISTS `staff_designation` (
  `id` int NOT NULL AUTO_INCREMENT,
  `designation` varchar(200) NOT NULL,
  `is_active` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `staff_id_card`
--

DROP TABLE IF EXISTS `staff_id_card`;
CREATE TABLE IF NOT EXISTS `staff_id_card` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `school_name` varchar(255) NOT NULL,
  `school_address` varchar(255) NOT NULL,
  `background` varchar(100) NOT NULL,
  `logo` varchar(100) NOT NULL,
  `sign_image` varchar(100) NOT NULL,
  `header_color` varchar(100) NOT NULL,
  `enable_vertical_card` int NOT NULL DEFAULT '0',
  `enable_staff_role` tinyint(1) NOT NULL COMMENT '0=disable,1=enable',
  `enable_staff_id` tinyint(1) NOT NULL COMMENT '0=disable,1=enable',
  `enable_staff_department` tinyint(1) NOT NULL COMMENT '0=disable,1=enable',
  `enable_designation` tinyint(1) NOT NULL COMMENT '0=disable,1=enable',
  `enable_name` tinyint(1) NOT NULL COMMENT '0=disable,1=enable',
  `enable_fathers_name` tinyint(1) NOT NULL COMMENT '0=disable,1=enable',
  `enable_mothers_name` tinyint(1) NOT NULL COMMENT '0=disable,1=enable',
  `enable_date_of_joining` tinyint(1) NOT NULL COMMENT '0=disable,1=enable',
  `enable_permanent_address` tinyint(1) NOT NULL COMMENT '0=disable,1=enable',
  `enable_staff_dob` tinyint(1) NOT NULL COMMENT '0=disable,1=enable',
  `enable_staff_phone` tinyint(1) NOT NULL COMMENT '0=disable,1=enable',
  `enable_staff_barcode` tinyint(1) NOT NULL COMMENT '0=disable,1=enable',
  `status` tinyint(1) NOT NULL COMMENT '0=disable,1=enable',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `staff_id_card`
--

INSERT INTO `staff_id_card` (`id`, `title`, `school_name`, `school_address`, `background`, `logo`, `sign_image`, `header_color`, `enable_vertical_card`, `enable_staff_role`, `enable_staff_id`, `enable_staff_department`, `enable_designation`, `enable_name`, `enable_fathers_name`, `enable_mothers_name`, `enable_date_of_joining`, `enable_permanent_address`, `enable_staff_dob`, `enable_staff_phone`, `enable_staff_barcode`, `status`) VALUES
(1, 'Sample Staff ID Card Horizontal', 'Mount Carmel School', '110 Kings Street, CA', 'background1.png', 'logo1.png', 'sign1.png', '#9b1818', 0, 0, 1, 0, 0, 1, 1, 1, 1, 1, 1, 1, 0, 1),
(2, 'Sample Staff ID Card Vertical', 'Mount Carmel School', '110 Kings Street, CA', 'background1.png', 'logo1.png', 'sign1.png', '#9b1818', 1, 0, 1, 0, 0, 1, 1, 1, 1, 1, 1, 1, 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `staff_leave_details`
--

DROP TABLE IF EXISTS `staff_leave_details`;
CREATE TABLE IF NOT EXISTS `staff_leave_details` (
  `id` int NOT NULL AUTO_INCREMENT,
  `staff_id` int NOT NULL,
  `leave_type_id` int NOT NULL,
  `alloted_leave` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `FK_staff_leave_details_staff` (`staff_id`),
  KEY `FK_staff_leave_details_leave_types` (`leave_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `staff_leave_request`
--

DROP TABLE IF EXISTS `staff_leave_request`;
CREATE TABLE IF NOT EXISTS `staff_leave_request` (
  `id` int NOT NULL AUTO_INCREMENT,
  `staff_id` int NOT NULL,
  `leave_type_id` int NOT NULL,
  `leave_from` date NOT NULL,
  `leave_to` date NOT NULL,
  `leave_days` int NOT NULL,
  `employee_remark` varchar(200) NOT NULL,
  `admin_remark` varchar(200) NOT NULL,
  `approve_date` date DEFAULT NULL,
  `status` varchar(50) NOT NULL,
  `applied_by` int DEFAULT NULL,
  `document_file` varchar(200) NOT NULL,
  `date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `FK_staff_leave_request_staff` (`staff_id`),
  KEY `FK_staff_leave_request_leave_types` (`leave_type_id`),
  KEY `applied_by` (`applied_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `staff_payroll`
--

DROP TABLE IF EXISTS `staff_payroll`;
CREATE TABLE IF NOT EXISTS `staff_payroll` (
  `id` int NOT NULL AUTO_INCREMENT,
  `basic_salary` int NOT NULL,
  `pay_scale` varchar(200) NOT NULL,
  `grade` varchar(50) NOT NULL,
  `is_active` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `staff_payslip`
--

DROP TABLE IF EXISTS `staff_payslip`;
CREATE TABLE IF NOT EXISTS `staff_payslip` (
  `id` int NOT NULL AUTO_INCREMENT,
  `staff_id` int NOT NULL,
  `basic` float(10,2) NOT NULL,
  `total_allowance` float(10,2) NOT NULL,
  `total_deduction` float(10,2) NOT NULL,
  `leave_deduction` int NOT NULL,
  `tax` varchar(200) NOT NULL,
  `net_salary` float(10,2) NOT NULL,
  `status` varchar(100) NOT NULL,
  `month` varchar(200) NOT NULL,
  `year` varchar(200) NOT NULL,
  `payment_mode` varchar(200) NOT NULL,
  `payment_date` date NOT NULL,
  `remark` varchar(200) NOT NULL,
  `generated_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `FK_staff_payslip_staff` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `staff_rating`
--

DROP TABLE IF EXISTS `staff_rating`;
CREATE TABLE IF NOT EXISTS `staff_rating` (
  `id` int NOT NULL AUTO_INCREMENT,
  `staff_id` int NOT NULL,
  `comment` text NOT NULL,
  `rate` int NOT NULL,
  `user_id` int NOT NULL,
  `role` varchar(255) NOT NULL,
  `status` int NOT NULL COMMENT '0 decline, 1 Approve',
  `entrydt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `FK_staff_rating_staff` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `staff_roles`
--

DROP TABLE IF EXISTS `staff_roles`;
CREATE TABLE IF NOT EXISTS `staff_roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `role_id` int DEFAULT NULL,
  `staff_id` int DEFAULT NULL,
  `is_active` int DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `role_id` (`role_id`),
  KEY `staff_id` (`staff_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `staff_roles`
--

INSERT INTO `staff_roles` (`id`, `role_id`, `staff_id`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 7, 1, 0, '2025-08-25 08:58:54', '2025-08-25 08:58:54');

-- --------------------------------------------------------

--
-- Table structure for table `staff_timeline`
--

DROP TABLE IF EXISTS `staff_timeline`;
CREATE TABLE IF NOT EXISTS `staff_timeline` (
  `id` int NOT NULL AUTO_INCREMENT,
  `staff_id` int NOT NULL,
  `title` varchar(200) NOT NULL,
  `timeline_date` date NOT NULL,
  `description` varchar(300) NOT NULL,
  `document` varchar(200) NOT NULL,
  `status` varchar(200) NOT NULL,
  `date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `FK_staff_timeline_staff` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
CREATE TABLE IF NOT EXISTS `students` (
  `id` int NOT NULL AUTO_INCREMENT,
  `parent_id` int NOT NULL,
  `admission_no` varchar(100) DEFAULT NULL,
  `roll_no` varchar(100) DEFAULT NULL,
  `admission_date` date DEFAULT NULL,
  `firstname` varchar(100) DEFAULT NULL,
  `middlename` varchar(255) DEFAULT NULL,
  `lastname` varchar(100) DEFAULT NULL,
  `rte` varchar(20) DEFAULT NULL,
  `image` varchar(100) DEFAULT NULL,
  `mobileno` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `pincode` varchar(100) DEFAULT NULL,
  `religion` varchar(100) DEFAULT NULL,
  `cast` varchar(50) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `gender` varchar(100) DEFAULT NULL,
  `current_address` text,
  `permanent_address` text,
  `category_id` varchar(100) DEFAULT NULL,
  `school_house_id` int DEFAULT NULL,
  `blood_group` varchar(200) NOT NULL,
  `hostel_room_id` int DEFAULT NULL,
  `adhar_no` varchar(100) DEFAULT NULL,
  `samagra_id` varchar(100) DEFAULT NULL,
  `bank_account_no` varchar(100) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `ifsc_code` varchar(100) DEFAULT NULL,
  `guardian_is` varchar(100) NOT NULL,
  `father_name` varchar(100) DEFAULT NULL,
  `father_phone` varchar(100) DEFAULT NULL,
  `father_occupation` varchar(100) DEFAULT NULL,
  `mother_name` varchar(100) DEFAULT NULL,
  `mother_phone` varchar(100) DEFAULT NULL,
  `mother_occupation` varchar(100) DEFAULT NULL,
  `guardian_name` varchar(100) DEFAULT NULL,
  `guardian_relation` varchar(100) DEFAULT NULL,
  `guardian_phone` varchar(100) DEFAULT NULL,
  `guardian_occupation` varchar(150) NOT NULL,
  `guardian_address` text,
  `guardian_email` varchar(100) DEFAULT NULL,
  `father_pic` varchar(200) NOT NULL,
  `mother_pic` varchar(200) NOT NULL,
  `guardian_pic` varchar(200) NOT NULL,
  `is_active` varchar(255) DEFAULT 'yes',
  `previous_school` text,
  `height` varchar(100) NOT NULL,
  `weight` varchar(100) NOT NULL,
  `measurement_date` date DEFAULT NULL,
  `dis_reason` int NOT NULL,
  `note` varchar(200) DEFAULT NULL,
  `dis_note` text NOT NULL,
  `about` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `designation` varchar(255) DEFAULT NULL,
  `app_key` text,
  `parent_app_key` text,
  `created_by` int DEFAULT NULL,
  `disable_at` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_admission_no` (`admission_no`),
  KEY `idx_roll_no` (`roll_no`),
  KEY `idx_mobileno` (`mobileno`),
  KEY `idx_email` (`email`),
  KEY `idx_firstname` (`firstname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `student_applied_discounts`
--

DROP TABLE IF EXISTS `student_applied_discounts`;
CREATE TABLE IF NOT EXISTS `student_applied_discounts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_fees_deposite_id` int DEFAULT NULL,
  `student_fees_discount_id` int DEFAULT NULL,
  `date` date DEFAULT NULL,
  `invoice_id` int DEFAULT NULL,
  `sub_invoice_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_fees_deposite_id` (`student_fees_deposite_id`),
  KEY `student_fees_discount_id` (`student_fees_discount_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `student_applyleave`
--

DROP TABLE IF EXISTS `student_applyleave`;
CREATE TABLE IF NOT EXISTS `student_applyleave` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_session_id` int NOT NULL,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `apply_date` date NOT NULL,
  `status` int NOT NULL,
  `docs` varchar(200) DEFAULT NULL,
  `reason` text NOT NULL,
  `approve_by` int DEFAULT NULL,
  `approve_date` date DEFAULT NULL,
  `request_type` int NOT NULL COMMENT '0 student,1 staff',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_session_id` (`student_session_id`),
  KEY `approve_by` (`approve_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `student_attendences`
--

DROP TABLE IF EXISTS `student_attendences`;
CREATE TABLE IF NOT EXISTS `student_attendences` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_session_id` int DEFAULT NULL,
  `biometric_attendence` int NOT NULL DEFAULT '0',
  `qrcode_attendance` int NOT NULL DEFAULT '0',
  `date` date DEFAULT NULL,
  `attendence_type_id` int DEFAULT NULL,
  `remark` varchar(200) NOT NULL,
  `biometric_device_data` text,
  `user_agent` varchar(250) DEFAULT NULL,
  `in_time` time DEFAULT NULL,
  `out_time` time DEFAULT NULL,
  `is_active` varchar(255) DEFAULT 'no',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_session_id` (`student_session_id`),
  KEY `attendence_type_id` (`attendence_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `student_attendence_schedules`
--

DROP TABLE IF EXISTS `student_attendence_schedules`;
CREATE TABLE IF NOT EXISTS `student_attendence_schedules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `attendence_type_id` int DEFAULT NULL,
  `class_section_id` int DEFAULT NULL,
  `entry_time_from` time DEFAULT NULL,
  `entry_time_to` time DEFAULT NULL,
  `total_institute_hour` time DEFAULT NULL,
  `is_active` int DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `student_dashboard_settings`
--

DROP TABLE IF EXISTS `student_dashboard_settings`;
CREATE TABLE IF NOT EXISTS `student_dashboard_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(250) DEFAULT NULL,
  `short_code` varchar(255) NOT NULL,
  `is_student` int DEFAULT NULL,
  `is_parent` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `student_dashboard_settings`
--

INSERT INTO `student_dashboard_settings` (`id`, `name`, `short_code`, `is_student`, `is_parent`, `created_at`, `updated_at`) VALUES
(1, 'welcome_student', '', 1, 1, '2024-10-15 12:14:22', '2025-08-25 08:58:07'),
(2, 'notice_board', 'communicate', 1, 1, '2024-10-15 12:14:25', '2025-08-25 08:58:07'),
(3, 'subject_progress', 'lesson_plan', 1, 1, '2024-10-15 12:14:27', '2025-08-25 08:58:07'),
(4, 'upcomming_class', 'academics', 1, 1, '2024-10-15 12:14:55', '2025-08-25 08:58:07'),
(5, 'homework', 'homework', 1, 1, '2024-10-15 12:14:56', '2025-08-25 08:58:07'),
(6, 'teacher_list', 'human_resource', 1, 1, '2024-10-15 12:14:57', '2025-08-25 08:58:07'),
(7, 'visitor_list', 'front_office', 1, 1, '2024-10-15 12:14:58', '2025-08-25 08:58:07'),
(8, 'library', 'library', 1, 1, '2024-10-15 12:14:59', '2025-08-25 08:58:07');

-- --------------------------------------------------------

--
-- Table structure for table `student_doc`
--

DROP TABLE IF EXISTS `student_doc`;
CREATE TABLE IF NOT EXISTS `student_doc` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int DEFAULT NULL,
  `title` varchar(200) DEFAULT NULL,
  `doc` varchar(200) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `student_edit_fields`
--

DROP TABLE IF EXISTS `student_edit_fields`;
CREATE TABLE IF NOT EXISTS `student_edit_fields` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(250) DEFAULT NULL,
  `status` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `student_educational_details`
--

DROP TABLE IF EXISTS `student_educational_details`;
CREATE TABLE IF NOT EXISTS `student_educational_details` (
  `id` int NOT NULL AUTO_INCREMENT,
  `course` varchar(255) NOT NULL,
  `university` varchar(255) NOT NULL,
  `education_year` varchar(255) NOT NULL,
  `education_detail` varchar(255) NOT NULL,
  `student_id` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `student_fees`
--

DROP TABLE IF EXISTS `student_fees`;
CREATE TABLE IF NOT EXISTS `student_fees` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_session_id` int DEFAULT NULL,
  `feemaster_id` int DEFAULT NULL,
  `amount` float(10,2) DEFAULT NULL,
  `amount_discount` float(10,2) NOT NULL,
  `amount_fine` float(10,2) NOT NULL DEFAULT '0.00',
  `description` text,
  `date` date DEFAULT NULL,
  `payment_mode` varchar(50) NOT NULL,
  `is_active` varchar(255) DEFAULT 'no',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `feemaster_id` (`feemaster_id`),
  KEY `student_session_id` (`student_session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `student_fees_deposite`
--

DROP TABLE IF EXISTS `student_fees_deposite`;
CREATE TABLE IF NOT EXISTS `student_fees_deposite` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_fees_master_id` int DEFAULT NULL,
  `fee_groups_feetype_id` int DEFAULT NULL,
  `student_transport_fee_id` int DEFAULT NULL,
  `amount_detail` text,
  `is_active` varchar(10) NOT NULL DEFAULT 'no',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_fees_master_id` (`student_fees_master_id`),
  KEY `fee_groups_feetype_id` (`fee_groups_feetype_id`),
  KEY `student_transport_fee_id` (`student_transport_fee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `student_fees_discounts`
--

DROP TABLE IF EXISTS `student_fees_discounts`;
CREATE TABLE IF NOT EXISTS `student_fees_discounts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_session_id` int DEFAULT NULL,
  `fees_discount_id` int DEFAULT NULL,
  `status` varchar(20) DEFAULT 'assigned',
  `payment_id` varchar(50) DEFAULT NULL,
  `description` text,
  `is_active` varchar(10) NOT NULL DEFAULT 'no',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_session_id` (`student_session_id`),
  KEY `fees_discount_id` (`fees_discount_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `student_fees_master`
--

DROP TABLE IF EXISTS `student_fees_master`;
CREATE TABLE IF NOT EXISTS `student_fees_master` (
  `id` int NOT NULL AUTO_INCREMENT,
  `is_system` int NOT NULL DEFAULT '0',
  `student_session_id` int DEFAULT NULL,
  `fee_session_group_id` int DEFAULT NULL,
  `amount` float(10,2) DEFAULT '0.00',
  `pre_discount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `is_active` varchar(10) NOT NULL DEFAULT 'no',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_session_id` (`student_session_id`),
  KEY `fee_session_group_id` (`fee_session_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `student_fees_processing`
--

DROP TABLE IF EXISTS `student_fees_processing`;
CREATE TABLE IF NOT EXISTS `student_fees_processing` (
  `id` int NOT NULL AUTO_INCREMENT,
  `gateway_ins_id` int NOT NULL,
  `fee_category` varchar(255) NOT NULL,
  `student_fees_master_id` int DEFAULT NULL,
  `fee_groups_feetype_id` int DEFAULT NULL,
  `student_transport_fee_id` int DEFAULT NULL,
  `amount_detail` text,
  `is_active` varchar(10) NOT NULL DEFAULT 'no',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_fees_master_id` (`student_fees_master_id`),
  KEY `fee_groups_feetype_id` (`fee_groups_feetype_id`),
  KEY `student_transport_fee_id` (`student_transport_fee_id`),
  KEY `gateway_ins_id` (`gateway_ins_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `student_refrence`
--

DROP TABLE IF EXISTS `student_refrence`;
CREATE TABLE IF NOT EXISTS `student_refrence` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `relation` varchar(255) NOT NULL,
  `age` varchar(255) NOT NULL,
  `profession` varchar(255) NOT NULL,
  `contact` varchar(255) NOT NULL,
  `student_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `student_session`
--

DROP TABLE IF EXISTS `student_session`;
CREATE TABLE IF NOT EXISTS `student_session` (
  `id` int NOT NULL AUTO_INCREMENT,
  `session_id` int DEFAULT NULL,
  `student_id` int DEFAULT NULL,
  `class_id` int DEFAULT NULL,
  `section_id` int DEFAULT NULL,
  `hostel_room_id` int DEFAULT NULL,
  `vehroute_id` int DEFAULT NULL,
  `route_pickup_point_id` int DEFAULT NULL,
  `transport_fees` float(10,2) NOT NULL DEFAULT '0.00',
  `fees_discount` float(10,2) NOT NULL DEFAULT '0.00',
  `is_leave` int NOT NULL DEFAULT '0',
  `is_active` varchar(255) DEFAULT 'no',
  `is_alumni` int NOT NULL,
  `default_login` int NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`),
  KEY `student_id` (`student_id`),
  KEY `class_id` (`class_id`),
  KEY `section_id` (`section_id`),
  KEY `student_session_ibfk_5` (`vehroute_id`),
  KEY `hostel_room_id` (`hostel_room_id`),
  KEY `student_session_ibfk_6` (`route_pickup_point_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `student_skills_detail`
--

DROP TABLE IF EXISTS `student_skills_detail`;
CREATE TABLE IF NOT EXISTS `student_skills_detail` (
  `id` int NOT NULL AUTO_INCREMENT,
  `skill_category` varchar(255) NOT NULL,
  `skill_detail` varchar(255) NOT NULL,
  `student_id` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `student_subject_attendances`
--

DROP TABLE IF EXISTS `student_subject_attendances`;
CREATE TABLE IF NOT EXISTS `student_subject_attendances` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_session_id` int DEFAULT NULL,
  `subject_timetable_id` int DEFAULT NULL,
  `attendence_type_id` int DEFAULT NULL,
  `date` date DEFAULT NULL,
  `remark` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `attendence_type_id` (`attendence_type_id`),
  KEY `student_session_id` (`student_session_id`),
  KEY `subject_timetable_id` (`subject_timetable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `student_timeline`
--

DROP TABLE IF EXISTS `student_timeline`;
CREATE TABLE IF NOT EXISTS `student_timeline` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `title` varchar(200) NOT NULL,
  `timeline_date` date NOT NULL,
  `description` text NOT NULL,
  `document` varchar(200) DEFAULT NULL,
  `status` varchar(200) NOT NULL,
  `created_student_id` int NOT NULL,
  `date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `student_transport_fees`
--

DROP TABLE IF EXISTS `student_transport_fees`;
CREATE TABLE IF NOT EXISTS `student_transport_fees` (
  `id` int NOT NULL AUTO_INCREMENT,
  `transport_feemaster_id` int NOT NULL,
  `student_session_id` int NOT NULL,
  `route_pickup_point_id` int NOT NULL,
  `generated_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_session_id` (`student_session_id`),
  KEY `route_pickup_point_id` (`route_pickup_point_id`),
  KEY `transport_feemaster_id` (`transport_feemaster_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `student_work_experience`
--

DROP TABLE IF EXISTS `student_work_experience`;
CREATE TABLE IF NOT EXISTS `student_work_experience` (
  `id` int NOT NULL AUTO_INCREMENT,
  `institute` text NOT NULL,
  `designation` text NOT NULL,
  `year` varchar(255) NOT NULL,
  `location` text NOT NULL,
  `detail` text NOT NULL,
  `student_id` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

DROP TABLE IF EXISTS `subjects`;
CREATE TABLE IF NOT EXISTS `subjects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `code` varchar(100) NOT NULL,
  `type` varchar(100) NOT NULL,
  `is_active` varchar(255) DEFAULT 'no',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_name` (`name`),
  KEY `idx_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `subject_groups`
--

DROP TABLE IF EXISTS `subject_groups`;
CREATE TABLE IF NOT EXISTS `subject_groups` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(250) DEFAULT NULL,
  `description` text,
  `session_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `subject_group_class_sections`
--

DROP TABLE IF EXISTS `subject_group_class_sections`;
CREATE TABLE IF NOT EXISTS `subject_group_class_sections` (
  `id` int NOT NULL AUTO_INCREMENT,
  `subject_group_id` int DEFAULT NULL,
  `class_section_id` int DEFAULT NULL,
  `session_id` int DEFAULT NULL,
  `description` text,
  `is_active` int DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `class_section_id` (`class_section_id`),
  KEY `subject_group_id` (`subject_group_id`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `subject_group_subjects`
--

DROP TABLE IF EXISTS `subject_group_subjects`;
CREATE TABLE IF NOT EXISTS `subject_group_subjects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `subject_group_id` int DEFAULT NULL,
  `session_id` int DEFAULT NULL,
  `subject_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `subject_group_id` (`subject_group_id`),
  KEY `session_id` (`session_id`),
  KEY `subject_id` (`subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `subject_syllabus`
--

DROP TABLE IF EXISTS `subject_syllabus`;
CREATE TABLE IF NOT EXISTS `subject_syllabus` (
  `id` int NOT NULL AUTO_INCREMENT,
  `topic_id` int NOT NULL,
  `session_id` int NOT NULL,
  `created_by` int NOT NULL,
  `created_for` int NOT NULL,
  `date` date NOT NULL,
  `time_from` varchar(255) NOT NULL,
  `time_to` varchar(255) NOT NULL,
  `presentation` text NOT NULL,
  `attachment` text NOT NULL,
  `lacture_youtube_url` varchar(255) NOT NULL,
  `lacture_video` varchar(255) NOT NULL,
  `sub_topic` text NOT NULL,
  `teaching_method` text NOT NULL,
  `general_objectives` text NOT NULL,
  `previous_knowledge` text NOT NULL,
  `comprehensive_questions` text NOT NULL,
  `status` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `topic_id` (`topic_id`),
  KEY `session_id` (`session_id`),
  KEY `created_by` (`created_by`),
  KEY `created_for` (`created_for`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `subject_timetable`
--

DROP TABLE IF EXISTS `subject_timetable`;
CREATE TABLE IF NOT EXISTS `subject_timetable` (
  `id` int NOT NULL AUTO_INCREMENT,
  `session_id` int DEFAULT NULL,
  `class_id` int DEFAULT NULL,
  `section_id` int DEFAULT NULL,
  `subject_group_id` int DEFAULT NULL,
  `subject_group_subject_id` int DEFAULT NULL,
  `staff_id` int DEFAULT NULL,
  `day` varchar(20) DEFAULT NULL,
  `time_from` varchar(20) DEFAULT NULL,
  `time_to` varchar(20) DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `room_no` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `class_id` (`class_id`),
  KEY `section_id` (`section_id`),
  KEY `subject_group_id` (`subject_group_id`),
  KEY `subject_group_subject_id` (`subject_group_subject_id`),
  KEY `staff_id` (`staff_id`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `submit_assignment`
--

DROP TABLE IF EXISTS `submit_assignment`;
CREATE TABLE IF NOT EXISTS `submit_assignment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `homework_id` int NOT NULL,
  `student_id` int NOT NULL,
  `message` text NOT NULL,
  `docs` varchar(225) NOT NULL,
  `file_name` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `homework_id` (`homework_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `template_admitcards`
--

DROP TABLE IF EXISTS `template_admitcards`;
CREATE TABLE IF NOT EXISTS `template_admitcards` (
  `id` int NOT NULL AUTO_INCREMENT,
  `template` varchar(250) DEFAULT NULL,
  `heading` text,
  `title` text,
  `left_logo` varchar(200) DEFAULT NULL,
  `right_logo` varchar(200) DEFAULT NULL,
  `exam_name` varchar(200) DEFAULT NULL,
  `school_name` varchar(200) DEFAULT NULL,
  `exam_center` varchar(200) DEFAULT NULL,
  `sign` varchar(200) DEFAULT NULL,
  `background_img` varchar(200) DEFAULT NULL,
  `is_name` int NOT NULL DEFAULT '1',
  `is_father_name` int NOT NULL DEFAULT '1',
  `is_mother_name` int NOT NULL DEFAULT '1',
  `is_dob` int NOT NULL DEFAULT '1',
  `is_admission_no` int NOT NULL DEFAULT '1',
  `is_roll_no` int NOT NULL DEFAULT '1',
  `is_address` int NOT NULL DEFAULT '1',
  `is_gender` int NOT NULL DEFAULT '1',
  `is_photo` int NOT NULL,
  `is_class` int NOT NULL DEFAULT '0',
  `is_section` int NOT NULL DEFAULT '0',
  `is_active` int DEFAULT '0',
  `content_footer` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `template_admitcards`
--

INSERT INTO `template_admitcards` (`id`, `template`, `heading`, `title`, `left_logo`, `right_logo`, `exam_name`, `school_name`, `exam_center`, `sign`, `background_img`, `is_name`, `is_father_name`, `is_mother_name`, `is_dob`, `is_admission_no`, `is_roll_no`, `is_address`, `is_gender`, `is_photo`, `is_class`, `is_section`, `is_active`, `content_footer`, `created_at`, `updated_at`) VALUES
(1, 'Sample Admit Card', '', '', '', '', 'Test', 'Mount Carmel School', 'test dmit card2', '', '', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, '2020-02-28 14:26:15', '2020-02-28 14:26:15');

-- --------------------------------------------------------

--
-- Table structure for table `template_marksheets`
--

DROP TABLE IF EXISTS `template_marksheets`;
CREATE TABLE IF NOT EXISTS `template_marksheets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `header_image` varchar(200) DEFAULT NULL,
  `template` varchar(200) DEFAULT NULL,
  `heading` text,
  `title` text,
  `left_logo` varchar(200) DEFAULT NULL,
  `right_logo` varchar(200) DEFAULT NULL,
  `exam_name` varchar(200) DEFAULT NULL,
  `school_name` varchar(200) DEFAULT NULL,
  `exam_center` varchar(200) DEFAULT NULL,
  `left_sign` varchar(200) DEFAULT NULL,
  `middle_sign` varchar(200) DEFAULT NULL,
  `right_sign` varchar(200) DEFAULT NULL,
  `exam_session` int DEFAULT '1',
  `is_name` int DEFAULT '1',
  `is_father_name` int DEFAULT '1',
  `is_mother_name` int DEFAULT '1',
  `is_dob` int DEFAULT '1',
  `is_admission_no` int DEFAULT '1',
  `is_roll_no` int DEFAULT '1',
  `is_photo` int DEFAULT '1',
  `is_division` int NOT NULL DEFAULT '1',
  `is_rank` int NOT NULL DEFAULT '0',
  `is_customfield` int NOT NULL,
  `background_img` varchar(200) DEFAULT NULL,
  `date` varchar(20) DEFAULT NULL,
  `is_class` int NOT NULL DEFAULT '0',
  `is_teacher_remark` int NOT NULL DEFAULT '1',
  `is_section` int NOT NULL DEFAULT '0',
  `content` text,
  `content_footer` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `template_marksheets`
--

INSERT INTO `template_marksheets` (`id`, `header_image`, `template`, `heading`, `title`, `left_logo`, `right_logo`, `exam_name`, `school_name`, `exam_center`, `left_sign`, `middle_sign`, `right_sign`, `exam_session`, `is_name`, `is_father_name`, `is_mother_name`, `is_dob`, `is_admission_no`, `is_roll_no`, `is_photo`, `is_division`, `is_rank`, `is_customfield`, `background_img`, `date`, `is_class`, `is_teacher_remark`, `is_section`, `content`, `content_footer`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Sample Marksheet', 'BOARD OF SECONDARY EDUCATION, MADHYA PRADESH, BHOPAL', 'BOARD OF SECONDARY EDUCATION, MADHYA PRADESH, BHOPAL', '', '', 'HIGHER SECONDARY SCHOOL CERTIFICATE EXAMINATION', 'Mount Carmel School', 'GOVT GIRLS H S SCHOOL', '', '', '', 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 1, '', NULL, 0, 1, 0, NULL, NULL, '2020-02-28 14:26:06', '2020-02-28 14:26:06');

-- --------------------------------------------------------

--
-- Table structure for table `topic`
--

DROP TABLE IF EXISTS `topic`;
CREATE TABLE IF NOT EXISTS `topic` (
  `id` int NOT NULL AUTO_INCREMENT,
  `session_id` int NOT NULL,
  `lesson_id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` int NOT NULL,
  `complete_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`),
  KEY `lesson_id` (`lesson_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `transport_feemaster`
--

DROP TABLE IF EXISTS `transport_feemaster`;
CREATE TABLE IF NOT EXISTS `transport_feemaster` (
  `id` int NOT NULL AUTO_INCREMENT,
  `session_id` int NOT NULL,
  `month` varchar(50) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `fine_amount` float(10,2) DEFAULT '0.00',
  `fine_type` varchar(50) DEFAULT NULL,
  `fine_percentage` float(10,2) DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `transport_route`
--

DROP TABLE IF EXISTS `transport_route`;
CREATE TABLE IF NOT EXISTS `transport_route` (
  `id` int NOT NULL AUTO_INCREMENT,
  `route_title` varchar(100) DEFAULT NULL,
  `no_of_vehicle` int DEFAULT NULL,
  `note` text,
  `is_active` varchar(255) DEFAULT 'no',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `upload_contents`
--

DROP TABLE IF EXISTS `upload_contents`;
CREATE TABLE IF NOT EXISTS `upload_contents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `content_type_id` int NOT NULL,
  `image` varchar(300) DEFAULT NULL,
  `thumb_path` varchar(300) DEFAULT NULL,
  `dir_path` varchar(300) DEFAULT NULL,
  `real_name` text NOT NULL,
  `img_name` varchar(300) DEFAULT NULL,
  `thumb_name` varchar(300) DEFAULT NULL,
  `file_type` varchar(100) NOT NULL,
  `mime_type` text NOT NULL,
  `file_size` varchar(100) NOT NULL,
  `vid_url` text NOT NULL,
  `vid_title` varchar(250) NOT NULL,
  `upload_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `upload_by` (`upload_by`),
  KEY `upload_contents_ibfk_2` (`content_type_id`),
  KEY `idx_file_type` (`file_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `userlog`
--

DROP TABLE IF EXISTS `userlog`;
CREATE TABLE IF NOT EXISTS `userlog` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user` varchar(100) DEFAULT NULL,
  `role` varchar(100) DEFAULT NULL,
  `class_section_id` int DEFAULT NULL,
  `ipaddress` varchar(100) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `login_datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `class_section_id` (`class_section_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `userlog`
--

INSERT INTO `userlog` (`id`, `user`, `role`, `class_section_id`, `ipaddress`, `user_agent`, `login_datetime`) VALUES
(1, 'abdulrehman4845@gmail.com', 'Super Admin', NULL, '::1', 'Chrome 139.0.0.0, Windows 10', '2025-08-26 00:32:54'),
(2, 'abdulrehman4845@gmail.com', 'Super Admin', NULL, '::1', 'Chrome 139.0.0.0, Windows 10', '2025-08-26 02:34:18'),
(3, 'abdulrehman4845@gmail.com', 'Super Admin', NULL, '::1', 'Chrome 139.0.0.0, Windows 10', '2025-08-26 03:11:02'),
(4, 'abdulrehman4845@gmail.com', 'Super Admin', NULL, '::1', 'Chrome 139.0.0.0, Windows 10', '2025-08-26 06:40:23'),
(5, 'abdulrehman4845@gmail.com', 'Super Admin', NULL, '::1', 'Chrome 139.0.0.0, Windows 10', '2025-08-27 00:39:19'),
(6, 'abdulrehman4845@gmail.com', 'Super Admin', NULL, '::1', 'Chrome 139.0.0.0, Windows 10', '2025-08-27 02:57:28');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(50) DEFAULT NULL,
  `childs` text NOT NULL,
  `role` varchar(30) NOT NULL,
  `lang_id` int NOT NULL,
  `currency_id` int DEFAULT '0',
  `verification_code` varchar(200) NOT NULL,
  `is_active` varchar(255) DEFAULT 'yes',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `users_authentication`
--

DROP TABLE IF EXISTS `users_authentication`;
CREATE TABLE IF NOT EXISTS `users_authentication` (
  `id` int NOT NULL AUTO_INCREMENT,
  `users_id` int NOT NULL,
  `token` varchar(255) NOT NULL,
  `staff_id` int DEFAULT NULL,
  `expired_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

DROP TABLE IF EXISTS `vehicles`;
CREATE TABLE IF NOT EXISTS `vehicles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `vehicle_no` varchar(20) DEFAULT NULL,
  `vehicle_model` varchar(100) NOT NULL DEFAULT 'None',
  `vehicle_photo` varchar(255) DEFAULT NULL,
  `manufacture_year` varchar(4) DEFAULT NULL,
  `registration_number` varchar(50) NOT NULL,
  `chasis_number` varchar(100) NOT NULL,
  `max_seating_capacity` varchar(255) NOT NULL,
  `driver_name` varchar(50) DEFAULT NULL,
  `driver_licence` varchar(50) NOT NULL DEFAULT 'None',
  `driver_contact` varchar(20) DEFAULT NULL,
  `note` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_vehicle_no` (`vehicle_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_routes`
--

DROP TABLE IF EXISTS `vehicle_routes`;
CREATE TABLE IF NOT EXISTS `vehicle_routes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `route_id` int DEFAULT NULL,
  `vehicle_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `route_id` (`route_id`),
  KEY `vehicle_id` (`vehicle_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `video_tutorial`
--

DROP TABLE IF EXISTS `video_tutorial`;
CREATE TABLE IF NOT EXISTS `video_tutorial` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `vid_title` text,
  `description` text NOT NULL,
  `thumb_path` varchar(500) DEFAULT NULL,
  `dir_path` varchar(500) DEFAULT NULL,
  `img_name` varchar(300) NOT NULL,
  `thumb_name` varchar(300) NOT NULL,
  `video_link` varchar(100) NOT NULL,
  `created_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `idx_title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `video_tutorial_class_sections`
--

DROP TABLE IF EXISTS `video_tutorial_class_sections`;
CREATE TABLE IF NOT EXISTS `video_tutorial_class_sections` (
  `id` int NOT NULL AUTO_INCREMENT,
  `video_tutorial_id` int NOT NULL,
  `class_section_id` int NOT NULL,
  `created_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `class_section_id` (`class_section_id`),
  KEY `video_tutorial_id` (`video_tutorial_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `visitors_book`
--

DROP TABLE IF EXISTS `visitors_book`;
CREATE TABLE IF NOT EXISTS `visitors_book` (
  `id` int NOT NULL AUTO_INCREMENT,
  `staff_id` int DEFAULT NULL,
  `student_session_id` int DEFAULT NULL,
  `source` varchar(100) DEFAULT NULL,
  `purpose` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `contact` varchar(12) NOT NULL,
  `id_proof` varchar(50) NOT NULL,
  `no_of_people` int NOT NULL,
  `date` date NOT NULL,
  `in_time` varchar(20) NOT NULL,
  `out_time` varchar(20) NOT NULL,
  `note` text NOT NULL,
  `image` varchar(100) DEFAULT NULL,
  `meeting_with` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `staff_id` (`staff_id`),
  KEY `student_session_id` (`student_session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `visitors_purpose`
--

DROP TABLE IF EXISTS `visitors_purpose`;
CREATE TABLE IF NOT EXISTS `visitors_purpose` (
  `id` int NOT NULL AUTO_INCREMENT,
  `visitors_purpose` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `custom_fields`
--
ALTER TABLE `custom_fields` ADD FULLTEXT KEY `idx_field_values` (`field_values`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `addon_versions`
--
ALTER TABLE `addon_versions`
  ADD CONSTRAINT `addon_versions_ibfk_1` FOREIGN KEY (`addon_id`) REFERENCES `addons` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `alumni_events`
--
ALTER TABLE `alumni_events`
  ADD CONSTRAINT `alumni_events_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `alumni_events_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `alumni_students`
--
ALTER TABLE `alumni_students`
  ADD CONSTRAINT `alumni_students_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `annual_calendar`
--
ALTER TABLE `annual_calendar`
  ADD CONSTRAINT `annual_calendar_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`);

--
-- Constraints for table `book_issues`
--
ALTER TABLE `book_issues`
  ADD CONSTRAINT `book_issues_ibfk_1` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `book_issues_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `libarary_members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_connections`
--
ALTER TABLE `chat_connections`
  ADD CONSTRAINT `chat_connections_ibfk_1` FOREIGN KEY (`chat_user_one`) REFERENCES `chat_users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_connections_ibfk_2` FOREIGN KEY (`chat_user_two`) REFERENCES `chat_users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`chat_user_id`) REFERENCES `chat_users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_messages_ibfk_2` FOREIGN KEY (`chat_connection_id`) REFERENCES `chat_connections` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_users`
--
ALTER TABLE `chat_users`
  ADD CONSTRAINT `chat_users_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_users_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_users_ibfk_3` FOREIGN KEY (`create_staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_users_ibfk_4` FOREIGN KEY (`create_student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `class_sections`
--
ALTER TABLE `class_sections`
  ADD CONSTRAINT `class_sections_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_sections_ibfk_2` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `class_section_times`
--
ALTER TABLE `class_section_times`
  ADD CONSTRAINT `class_section_times_ibfk_1` FOREIGN KEY (`class_section_id`) REFERENCES `class_sections` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `class_teacher`
--
ALTER TABLE `class_teacher`
  ADD CONSTRAINT `class_teacher_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_teacher_ibfk_2` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_teacher_ibfk_3` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_teacher_ibfk_4` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `contents`
--
ALTER TABLE `contents`
  ADD CONSTRAINT `contents_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contents_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contents_ibfk_3` FOREIGN KEY (`cls_sec_id`) REFERENCES `class_sections` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `content_for`
--
ALTER TABLE `content_for`
  ADD CONSTRAINT `content_for_ibfk_1` FOREIGN KEY (`content_id`) REFERENCES `contents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `content_for_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `custom_field_values`
--
ALTER TABLE `custom_field_values`
  ADD CONSTRAINT `custom_field_values_ibfk_1` FOREIGN KEY (`custom_field_id`) REFERENCES `custom_fields` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `daily_assignment`
--
ALTER TABLE `daily_assignment`
  ADD CONSTRAINT `daily_assignment_ibfk_1` FOREIGN KEY (`student_session_id`) REFERENCES `student_session` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `daily_assignment_ibfk_2` FOREIGN KEY (`evaluated_by`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `daily_assignment_ibfk_3` FOREIGN KEY (`subject_group_subject_id`) REFERENCES `subject_group_subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `email_attachments`
--
ALTER TABLE `email_attachments`
  ADD CONSTRAINT `email_attachments_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `enquiry`
--
ALTER TABLE `enquiry`
  ADD CONSTRAINT `enquiry_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enquiry_ibfk_3` FOREIGN KEY (`assigned`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enquiry_ibfk_4` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exams`
--
ALTER TABLE `exams`
  ADD CONSTRAINT `exams_ibfk_1` FOREIGN KEY (`sesion_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_group_class_batch_exams`
--
ALTER TABLE `exam_group_class_batch_exams`
  ADD CONSTRAINT `exam_group_class_batch_exams_ibfk_1` FOREIGN KEY (`exam_group_id`) REFERENCES `exam_groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_group_class_batch_exams_ibfk_2` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_group_class_batch_exam_students`
--
ALTER TABLE `exam_group_class_batch_exam_students`
  ADD CONSTRAINT `exam_group_class_batch_exam_students_ibfk_1` FOREIGN KEY (`exam_group_class_batch_exam_id`) REFERENCES `exam_group_class_batch_exams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_group_class_batch_exam_students_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_group_class_batch_exam_students_ibfk_3` FOREIGN KEY (`student_session_id`) REFERENCES `student_session` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_group_class_batch_exam_subjects`
--
ALTER TABLE `exam_group_class_batch_exam_subjects`
  ADD CONSTRAINT `exam_group_class_batch_exam_subjects_ibfk_1` FOREIGN KEY (`exam_group_class_batch_exams_id`) REFERENCES `exam_group_class_batch_exams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_group_class_batch_exam_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_group_exam_connections`
--
ALTER TABLE `exam_group_exam_connections`
  ADD CONSTRAINT `exam_group_exam_connections_ibfk_1` FOREIGN KEY (`exam_group_id`) REFERENCES `exam_groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_group_exam_connections_ibfk_2` FOREIGN KEY (`exam_group_class_batch_exams_id`) REFERENCES `exam_group_class_batch_exams` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_group_exam_results`
--
ALTER TABLE `exam_group_exam_results`
  ADD CONSTRAINT `exam_group_exam_results_ibfk_1` FOREIGN KEY (`exam_group_class_batch_exam_subject_id`) REFERENCES `exam_group_class_batch_exam_subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_group_exam_results_ibfk_2` FOREIGN KEY (`exam_group_student_id`) REFERENCES `exam_group_students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_group_exam_results_ibfk_3` FOREIGN KEY (`exam_group_class_batch_exam_student_id`) REFERENCES `exam_group_class_batch_exam_students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_group_students`
--
ALTER TABLE `exam_group_students`
  ADD CONSTRAINT `exam_group_students_ibfk_1` FOREIGN KEY (`exam_group_id`) REFERENCES `exam_groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_group_students_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_group_students_ibfk_3` FOREIGN KEY (`student_session_id`) REFERENCES `student_session` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_schedules`
--
ALTER TABLE `exam_schedules`
  ADD CONSTRAINT `exam_schedules_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_schedules_ibfk_2` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`exp_head_id`) REFERENCES `expense_head` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `feemasters`
--
ALTER TABLE `feemasters`
  ADD CONSTRAINT `feemasters_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feemasters_ibfk_2` FOREIGN KEY (`feetype_id`) REFERENCES `feetype` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feemasters_ibfk_3` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fees_discounts`
--
ALTER TABLE `fees_discounts`
  ADD CONSTRAINT `fees_discounts_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fee_groups_feetype`
--
ALTER TABLE `fee_groups_feetype`
  ADD CONSTRAINT `fee_groups_feetype_ibfk_1` FOREIGN KEY (`fee_session_group_id`) REFERENCES `fee_session_groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fee_groups_feetype_ibfk_2` FOREIGN KEY (`fee_groups_id`) REFERENCES `fee_groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fee_groups_feetype_ibfk_3` FOREIGN KEY (`feetype_id`) REFERENCES `feetype` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fee_groups_feetype_ibfk_4` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fee_session_groups`
--
ALTER TABLE `fee_session_groups`
  ADD CONSTRAINT `fee_session_groups_ibfk_1` FOREIGN KEY (`fee_groups_id`) REFERENCES `fee_groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fee_session_groups_ibfk_2` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `follow_up`
--
ALTER TABLE `follow_up`
  ADD CONSTRAINT `follow_up_ibfk_1` FOREIGN KEY (`enquiry_id`) REFERENCES `enquiry` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `follow_up_ibfk_2` FOREIGN KEY (`followup_by`) REFERENCES `staff` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `front_cms_menu_items`
--
ALTER TABLE `front_cms_menu_items`
  ADD CONSTRAINT `front_cms_menu_items_ibfk_1` FOREIGN KEY (`menu_id`) REFERENCES `front_cms_menus` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `front_cms_page_contents`
--
ALTER TABLE `front_cms_page_contents`
  ADD CONSTRAINT `front_cms_page_contents_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `front_cms_pages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `front_cms_program_photos`
--
ALTER TABLE `front_cms_program_photos`
  ADD CONSTRAINT `front_cms_program_photos_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `front_cms_programs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `gateway_ins`
--
ALTER TABLE `gateway_ins`
  ADD CONSTRAINT `gateway_ins_ibfk_1` FOREIGN KEY (`online_admission_id`) REFERENCES `online_admissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `gateway_ins_response`
--
ALTER TABLE `gateway_ins_response`
  ADD CONSTRAINT `gateway_ins_response_ibfk_1` FOREIGN KEY (`gateway_ins_id`) REFERENCES `gateway_ins` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `homework`
--
ALTER TABLE `homework`
  ADD CONSTRAINT `homework_ibfk_1` FOREIGN KEY (`subject_group_subject_id`) REFERENCES `subject_group_subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `homework_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `homework_ibfk_3` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `homework_ibfk_4` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `homework_ibfk_5` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `homework_ibfk_6` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `homework_ibfk_7` FOREIGN KEY (`evaluated_by`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `homework_ibfk_8` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `homework_evaluation`
--
ALTER TABLE `homework_evaluation`
  ADD CONSTRAINT `homework_evaluation_ibfk_1` FOREIGN KEY (`homework_id`) REFERENCES `homework` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `homework_evaluation_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `homework_evaluation_ibfk_3` FOREIGN KEY (`student_session_id`) REFERENCES `student_session` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `hostel_rooms`
--
ALTER TABLE `hostel_rooms`
  ADD CONSTRAINT `hostel_rooms_ibfk_1` FOREIGN KEY (`hostel_id`) REFERENCES `hostel` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `hostel_rooms_ibfk_2` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `income`
--
ALTER TABLE `income`
  ADD CONSTRAINT `income_ibfk_1` FOREIGN KEY (`income_head_id`) REFERENCES `income_head` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `item`
--
ALTER TABLE `item`
  ADD CONSTRAINT `item_ibfk_1` FOREIGN KEY (`item_category_id`) REFERENCES `item_category` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `item_ibfk_2` FOREIGN KEY (`item_store_id`) REFERENCES `item_store` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `item_ibfk_3` FOREIGN KEY (`item_supplier_id`) REFERENCES `item_supplier` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `item_issue`
--
ALTER TABLE `item_issue`
  ADD CONSTRAINT `item_issue_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `item` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `item_issue_ibfk_2` FOREIGN KEY (`item_category_id`) REFERENCES `item_category` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `item_issue_ibfk_3` FOREIGN KEY (`issue_to`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `item_issue_ibfk_4` FOREIGN KEY (`issue_by`) REFERENCES `staff` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `item_stock`
--
ALTER TABLE `item_stock`
  ADD CONSTRAINT `item_stock_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `item` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `item_stock_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `item_supplier` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `item_stock_ibfk_3` FOREIGN KEY (`store_id`) REFERENCES `item_store` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lesson`
--
ALTER TABLE `lesson`
  ADD CONSTRAINT `lesson_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lesson_ibfk_2` FOREIGN KEY (`subject_group_subject_id`) REFERENCES `subject_group_subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lesson_ibfk_3` FOREIGN KEY (`subject_group_class_sections_id`) REFERENCES `subject_group_class_sections` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lesson_plan_forum`
--
ALTER TABLE `lesson_plan_forum`
  ADD CONSTRAINT `lesson_plan_forum_ibfk_1` FOREIGN KEY (`subject_syllabus_id`) REFERENCES `subject_syllabus` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lesson_plan_forum_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lesson_plan_forum_ibfk_3` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notification_roles`
--
ALTER TABLE `notification_roles`
  ADD CONSTRAINT `notification_roles_ibfk_1` FOREIGN KEY (`send_notification_id`) REFERENCES `send_notification` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notification_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `offline_fees_payments`
--
ALTER TABLE `offline_fees_payments`
  ADD CONSTRAINT `offline_fees_payments_ibfk_1` FOREIGN KEY (`student_fees_master_id`) REFERENCES `student_fees_master` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `offline_fees_payments_ibfk_2` FOREIGN KEY (`fee_groups_feetype_id`) REFERENCES `fee_groups_feetype` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `offline_fees_payments_ibfk_3` FOREIGN KEY (`student_transport_fee_id`) REFERENCES `student_transport_fees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `offline_fees_payments_ibfk_4` FOREIGN KEY (`approved_by`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `offline_fees_payments_ibfk_5` FOREIGN KEY (`student_session_id`) REFERENCES `student_session` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `onlineexam`
--
ALTER TABLE `onlineexam`
  ADD CONSTRAINT `onlineexam_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `onlineexam_attempts`
--
ALTER TABLE `onlineexam_attempts`
  ADD CONSTRAINT `onlineexam_attempts_ibfk_1` FOREIGN KEY (`onlineexam_student_id`) REFERENCES `onlineexam_students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `onlineexam_questions`
--
ALTER TABLE `onlineexam_questions`
  ADD CONSTRAINT `onlineexam_questions_ibfk_1` FOREIGN KEY (`onlineexam_id`) REFERENCES `onlineexam` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `onlineexam_questions_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `onlineexam_questions_ibfk_3` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `onlineexam_students`
--
ALTER TABLE `onlineexam_students`
  ADD CONSTRAINT `onlineexam_students_ibfk_1` FOREIGN KEY (`onlineexam_id`) REFERENCES `onlineexam` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `onlineexam_students_ibfk_2` FOREIGN KEY (`student_session_id`) REFERENCES `student_session` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `onlineexam_student_results`
--
ALTER TABLE `onlineexam_student_results`
  ADD CONSTRAINT `onlineexam_student_results_ibfk_1` FOREIGN KEY (`onlineexam_student_id`) REFERENCES `onlineexam_students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `onlineexam_student_results_ibfk_2` FOREIGN KEY (`onlineexam_question_id`) REFERENCES `onlineexam_questions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `online_admissions`
--
ALTER TABLE `online_admissions`
  ADD CONSTRAINT `online_admissions_ibfk_1` FOREIGN KEY (`class_section_id`) REFERENCES `class_sections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `online_admissions_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `online_admissions_ibfk_3` FOREIGN KEY (`hostel_room_id`) REFERENCES `hostel_rooms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `online_admissions_ibfk_4` FOREIGN KEY (`school_house_id`) REFERENCES `school_houses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `online_admission_custom_field_value`
--
ALTER TABLE `online_admission_custom_field_value`
  ADD CONSTRAINT `online_admission_custom_field_value_ibfk_1` FOREIGN KEY (`custom_field_id`) REFERENCES `custom_fields` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `online_admission_payment`
--
ALTER TABLE `online_admission_payment`
  ADD CONSTRAINT `online_admission_payment_ibfk_1` FOREIGN KEY (`online_admission_id`) REFERENCES `online_admissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payslip_allowance`
--
ALTER TABLE `payslip_allowance`
  ADD CONSTRAINT `payslip_allowance_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payslip_allowance_ibfk_2` FOREIGN KEY (`payslip_id`) REFERENCES `staff_payslip` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `permission_category`
--
ALTER TABLE `permission_category`
  ADD CONSTRAINT `permission_category_ibfk_1` FOREIGN KEY (`perm_group_id`) REFERENCES `permission_group` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `permission_student`
--
ALTER TABLE `permission_student`
  ADD CONSTRAINT `permission_student_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `permission_group` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `questions_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `questions_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `questions_ibfk_4` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `questions_ibfk_5` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `questions_ibfk_6` FOREIGN KEY (`class_section_id`) REFERENCES `class_sections` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `read_notification`
--
ALTER TABLE `read_notification`
  ADD CONSTRAINT `read_notification_ibfk_1` FOREIGN KEY (`notification_id`) REFERENCES `send_notification` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `read_notification_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `read_notification_ibfk_3` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `roles_permissions`
--
ALTER TABLE `roles_permissions`
  ADD CONSTRAINT `roles_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `roles_permissions_ibfk_2` FOREIGN KEY (`perm_cat_id`) REFERENCES `permission_category` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `route_pickup_point`
--
ALTER TABLE `route_pickup_point`
  ADD CONSTRAINT `route_pickup_point_ibfk_1` FOREIGN KEY (`transport_route_id`) REFERENCES `transport_route` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `route_pickup_point_ibfk_2` FOREIGN KEY (`pickup_point_id`) REFERENCES `pickup_point` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `send_notification`
--
ALTER TABLE `send_notification`
  ADD CONSTRAINT `send_notification_ibfk_1` FOREIGN KEY (`created_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `share_contents`
--
ALTER TABLE `share_contents`
  ADD CONSTRAINT `share_contents_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `share_content_for`
--
ALTER TABLE `share_content_for`
  ADD CONSTRAINT `share_content_for_ibfk_1` FOREIGN KEY (`share_content_id`) REFERENCES `share_contents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `share_content_for_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  ADD CONSTRAINT `share_content_for_ibfk_3` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`),
  ADD CONSTRAINT `share_content_for_ibfk_4` FOREIGN KEY (`class_section_id`) REFERENCES `class_sections` (`id`),
  ADD CONSTRAINT `share_content_for_ibfk_5` FOREIGN KEY (`user_parent_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `share_upload_contents`
--
ALTER TABLE `share_upload_contents`
  ADD CONSTRAINT `share_upload_contents_ibfk_1` FOREIGN KEY (`upload_content_id`) REFERENCES `upload_contents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `share_upload_contents_ibfk_2` FOREIGN KEY (`share_content_id`) REFERENCES `share_contents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sidebar_menus`
--
ALTER TABLE `sidebar_menus`
  ADD CONSTRAINT `sidebar_menus_ibfk_1` FOREIGN KEY (`permission_group_id`) REFERENCES `permission_group` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sidebar_sub_menus`
--
ALTER TABLE `sidebar_sub_menus`
  ADD CONSTRAINT `sidebar_sub_menus_ibfk_1` FOREIGN KEY (`sidebar_menu_id`) REFERENCES `sidebar_menus` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sidebar_sub_menus_ibfk_2` FOREIGN KEY (`permission_group_id`) REFERENCES `permission_group` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `staff`
--
ALTER TABLE `staff`
  ADD CONSTRAINT `staff_ibfk_1` FOREIGN KEY (`designation`) REFERENCES `staff_designation` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `staff_ibfk_2` FOREIGN KEY (`department`) REFERENCES `department` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `staff_attendance`
--
ALTER TABLE `staff_attendance`
  ADD CONSTRAINT `FK_staff_attendance_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_staff_attendance_staff_attendance_type` FOREIGN KEY (`staff_attendance_type_id`) REFERENCES `staff_attendance_type` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `staff_leave_details`
--
ALTER TABLE `staff_leave_details`
  ADD CONSTRAINT `FK_staff_leave_details_leave_types` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_staff_leave_details_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `staff_leave_request`
--
ALTER TABLE `staff_leave_request`
  ADD CONSTRAINT `FK_staff_leave_request_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `staff_leave_request_ibfk_1` FOREIGN KEY (`applied_by`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `staff_leave_request_ibfk_2` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `staff_payslip`
--
ALTER TABLE `staff_payslip`
  ADD CONSTRAINT `FK_staff_payslip_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `staff_rating`
--
ALTER TABLE `staff_rating`
  ADD CONSTRAINT `FK_staff_rating_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `staff_roles`
--
ALTER TABLE `staff_roles`
  ADD CONSTRAINT `FK_staff_roles_roles` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_staff_roles_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `staff_timeline`
--
ALTER TABLE `staff_timeline`
  ADD CONSTRAINT `FK_staff_timeline_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_applied_discounts`
--
ALTER TABLE `student_applied_discounts`
  ADD CONSTRAINT `student_applied_discounts_ibfk_1` FOREIGN KEY (`student_fees_deposite_id`) REFERENCES `student_fees_deposite` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_applied_discounts_ibfk_2` FOREIGN KEY (`student_fees_discount_id`) REFERENCES `student_fees_discounts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_applyleave`
--
ALTER TABLE `student_applyleave`
  ADD CONSTRAINT `student_applyleave_ibfk_1` FOREIGN KEY (`student_session_id`) REFERENCES `student_session` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_applyleave_ibfk_2` FOREIGN KEY (`approve_by`) REFERENCES `staff` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_attendences`
--
ALTER TABLE `student_attendences`
  ADD CONSTRAINT `student_attendences_ibfk_1` FOREIGN KEY (`attendence_type_id`) REFERENCES `attendence_type` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_attendences_ibfk_2` FOREIGN KEY (`student_session_id`) REFERENCES `student_session` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_fees`
--
ALTER TABLE `student_fees`
  ADD CONSTRAINT `student_fees_ibfk_1` FOREIGN KEY (`feemaster_id`) REFERENCES `feemasters` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_fees_ibfk_2` FOREIGN KEY (`student_session_id`) REFERENCES `student_session` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_fees_deposite`
--
ALTER TABLE `student_fees_deposite`
  ADD CONSTRAINT `student_fees_deposite_ibfk_1` FOREIGN KEY (`student_transport_fee_id`) REFERENCES `student_transport_fees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_fees_deposite_ibfk_2` FOREIGN KEY (`student_fees_master_id`) REFERENCES `student_fees_master` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_fees_deposite_ibfk_3` FOREIGN KEY (`fee_groups_feetype_id`) REFERENCES `fee_groups_feetype` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_fees_discounts`
--
ALTER TABLE `student_fees_discounts`
  ADD CONSTRAINT `student_fees_discounts_ibfk_1` FOREIGN KEY (`fees_discount_id`) REFERENCES `fees_discounts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_fees_discounts_ibfk_2` FOREIGN KEY (`student_session_id`) REFERENCES `student_session` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_fees_master`
--
ALTER TABLE `student_fees_master`
  ADD CONSTRAINT `student_fees_master_ibfk_1` FOREIGN KEY (`fee_session_group_id`) REFERENCES `fee_session_groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_fees_master_ibfk_2` FOREIGN KEY (`student_session_id`) REFERENCES `student_session` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_fees_processing`
--
ALTER TABLE `student_fees_processing`
  ADD CONSTRAINT `student_fees_processing_ibfk_1` FOREIGN KEY (`student_fees_master_id`) REFERENCES `student_fees_master` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_fees_processing_ibfk_2` FOREIGN KEY (`student_transport_fee_id`) REFERENCES `student_transport_fees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_fees_processing_ibfk_3` FOREIGN KEY (`fee_groups_feetype_id`) REFERENCES `fee_groups_feetype` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_fees_processing_ibfk_4` FOREIGN KEY (`gateway_ins_id`) REFERENCES `gateway_ins` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_session`
--
ALTER TABLE `student_session`
  ADD CONSTRAINT `student_session_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_session_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_session_ibfk_3` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_session_ibfk_4` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_session_ibfk_5` FOREIGN KEY (`vehroute_id`) REFERENCES `vehicle_routes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `student_session_ibfk_6` FOREIGN KEY (`route_pickup_point_id`) REFERENCES `route_pickup_point` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `student_session_ibfk_7` FOREIGN KEY (`hostel_room_id`) REFERENCES `hostel_rooms` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `student_subject_attendances`
--
ALTER TABLE `student_subject_attendances`
  ADD CONSTRAINT `student_subject_attendances_ibfk_1` FOREIGN KEY (`attendence_type_id`) REFERENCES `attendence_type` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_subject_attendances_ibfk_2` FOREIGN KEY (`student_session_id`) REFERENCES `student_session` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_subject_attendances_ibfk_3` FOREIGN KEY (`subject_timetable_id`) REFERENCES `subject_timetable` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_timeline`
--
ALTER TABLE `student_timeline`
  ADD CONSTRAINT `student_timeline_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_transport_fees`
--
ALTER TABLE `student_transport_fees`
  ADD CONSTRAINT `student_transport_fees_ibfk_1` FOREIGN KEY (`student_session_id`) REFERENCES `student_session` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_transport_fees_ibfk_2` FOREIGN KEY (`route_pickup_point_id`) REFERENCES `route_pickup_point` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_transport_fees_ibfk_3` FOREIGN KEY (`transport_feemaster_id`) REFERENCES `transport_feemaster` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subject_groups`
--
ALTER TABLE `subject_groups`
  ADD CONSTRAINT `subject_groups_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subject_group_class_sections`
--
ALTER TABLE `subject_group_class_sections`
  ADD CONSTRAINT `subject_group_class_sections_ibfk_1` FOREIGN KEY (`class_section_id`) REFERENCES `class_sections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subject_group_class_sections_ibfk_2` FOREIGN KEY (`subject_group_id`) REFERENCES `subject_groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subject_group_class_sections_ibfk_3` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subject_group_subjects`
--
ALTER TABLE `subject_group_subjects`
  ADD CONSTRAINT `subject_group_subjects_ibfk_1` FOREIGN KEY (`subject_group_id`) REFERENCES `subject_groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subject_group_subjects_ibfk_2` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subject_group_subjects_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subject_syllabus`
--
ALTER TABLE `subject_syllabus`
  ADD CONSTRAINT `subject_syllabus_ibfk_1` FOREIGN KEY (`topic_id`) REFERENCES `topic` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subject_syllabus_ibfk_2` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subject_syllabus_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subject_syllabus_ibfk_4` FOREIGN KEY (`created_for`) REFERENCES `staff` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subject_timetable`
--
ALTER TABLE `subject_timetable`
  ADD CONSTRAINT `subject_timetable_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subject_timetable_ibfk_2` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subject_timetable_ibfk_3` FOREIGN KEY (`subject_group_id`) REFERENCES `subject_groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subject_timetable_ibfk_4` FOREIGN KEY (`subject_group_subject_id`) REFERENCES `subject_group_subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subject_timetable_ibfk_5` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subject_timetable_ibfk_6` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `submit_assignment`
--
ALTER TABLE `submit_assignment`
  ADD CONSTRAINT `submit_assignment_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `submit_assignment_ibfk_2` FOREIGN KEY (`homework_id`) REFERENCES `homework` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `topic`
--
ALTER TABLE `topic`
  ADD CONSTRAINT `topic_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `topic_ibfk_2` FOREIGN KEY (`lesson_id`) REFERENCES `lesson` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transport_feemaster`
--
ALTER TABLE `transport_feemaster`
  ADD CONSTRAINT `transport_feemaster_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `upload_contents`
--
ALTER TABLE `upload_contents`
  ADD CONSTRAINT `upload_contents_ibfk_1` FOREIGN KEY (`upload_by`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `upload_contents_ibfk_2` FOREIGN KEY (`content_type_id`) REFERENCES `content_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `userlog`
--
ALTER TABLE `userlog`
  ADD CONSTRAINT `userlog_ibfk_1` FOREIGN KEY (`class_section_id`) REFERENCES `class_sections` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vehicle_routes`
--
ALTER TABLE `vehicle_routes`
  ADD CONSTRAINT `vehicle_routes_ibfk_1` FOREIGN KEY (`route_id`) REFERENCES `transport_route` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `vehicle_routes_ibfk_2` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `video_tutorial`
--
ALTER TABLE `video_tutorial`
  ADD CONSTRAINT `video_tutorial_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `video_tutorial_class_sections`
--
ALTER TABLE `video_tutorial_class_sections`
  ADD CONSTRAINT `video_tutorial_class_sections_ibfk_1` FOREIGN KEY (`class_section_id`) REFERENCES `class_sections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `video_tutorial_class_sections_ibfk_2` FOREIGN KEY (`video_tutorial_id`) REFERENCES `video_tutorial` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `visitors_book`
--
ALTER TABLE `visitors_book`
  ADD CONSTRAINT `visitors_book_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `visitors_book_ibfk_2` FOREIGN KEY (`student_session_id`) REFERENCES `student_session` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
