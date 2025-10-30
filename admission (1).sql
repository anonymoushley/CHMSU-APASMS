-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Oct 21, 2025 at 08:22 AM
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
-- Database: `admission`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_background`
--

CREATE TABLE `academic_background` (
  `id` int(11) NOT NULL,
  `personal_info_id` int(11) NOT NULL,
  `last_school_attended` varchar(255) NOT NULL,
  `strand_id` int(11) NOT NULL,
  `year_graduated` year(4) NOT NULL,
  `g11_1st_avg` decimal(4,2) NOT NULL,
  `g11_2nd_avg` decimal(4,2) NOT NULL,
  `g12_1st_avg` decimal(4,2) NOT NULL,
  `academic_award` enum('None','Honors','High Honors','Highest Honors') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `academic_background`
--

INSERT INTO `academic_background` (`id`, `personal_info_id`, `last_school_attended`, `strand_id`, `year_graduated`, `g11_1st_avg`, `g11_2nd_avg`, `g12_1st_avg`, `academic_award`, `created_at`) VALUES
(28, 25, 'JYFJFYURYU', 1, '2015', 76.00, 65.00, 65.00, 'Honors', '2025-10-06 09:04:35'),
(29, 26, 'LA CONSOLACION COLLEGE', 2, '2025', 95.00, 96.00, 95.00, 'High Honors', '2025-10-10 13:31:17'),
(30, 27, 'DONA HORTENCIA MEMORIAL HIGH SCHOOL', 2, '2020', 90.00, 91.00, 94.00, 'High Honors', '2025-10-19 10:53:40');

-- --------------------------------------------------------

--
-- Table structure for table `application_status`
--

CREATE TABLE `application_status` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `profile_completed` tinyint(1) DEFAULT 0,
  `exam_completed` tinyint(1) DEFAULT 0,
  `interview_completed` tinyint(1) DEFAULT 0,
  `application_completed` tinyint(1) DEFAULT 0,
  `exam_score` int(11) DEFAULT NULL,
  `exam_total_points` int(11) DEFAULT NULL,
  `interview_score` int(11) DEFAULT NULL,
  `interview_total_points` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buildings`
--

CREATE TABLE `buildings` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `buildings`
--

INSERT INTO `buildings` (`id`, `name`, `status`, `created_at`, `updated_at`) VALUES
(1, 'LSAB', 'active', '2025-10-19 08:56:40', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `chairperson_accounts`
--

CREATE TABLE `chairperson_accounts` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `designation` varchar(100) NOT NULL,
  `program` varchar(50) NOT NULL,
  `campus` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chairperson_accounts`
--

INSERT INTO `chairperson_accounts` (`id`, `name`, `username`, `password`, `designation`, `program`, `campus`, `created_at`) VALUES
(3, 'DANES TUMABING', 'mchair', '$2y$10$VQ7.OXeC8EFFnAjnY/0hLuMxVohoVfLISDUFDgmhlhb4t9wXVssaq', 'Program Chair', 'BSIS', 'Talisay', '2025-08-27 06:42:14'),
(4, 'Maria Chair', 'mchair', '$2y$10$oa7W30ASm3Gk0SmSwFUH5e5NwBhZyDr.SqNwzUWKrA4Z90KGzMIwK', 'Program Chair', 'BSIS', 'Talisay', '2025-08-27 06:42:40'),
(5, 'Maria Chair', 'mchair', '$2y$10$vjl3rbops8/mwH/Lll/eT.uKwiaRMMKfNT/qyCIWaDzDn7.FXMYv2', 'Program Chair', 'BSIS', 'Talisay', '2025-08-27 06:42:48'),
(6, 'Maria Chair', 'mchair', '$2y$10$aagJNlhrfJE24Yh5neTH7OKf6b65svKbMKUdwrXPG5.1oOIzXcQIa', 'Program Chair', 'BSIS', 'Talisay', '2025-08-27 06:42:56');

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `personal_info_id` int(11) NOT NULL,
  `g11_1st` varchar(255) NOT NULL,
  `g11_2nd` varchar(255) NOT NULL,
  `g12_1st` varchar(255) NOT NULL,
  `ncii` varchar(255) DEFAULT NULL,
  `guidance_cert` varchar(255) DEFAULT NULL,
  `additional_file` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `g11_1st_status` enum('Pending','Accepted','Rejected') DEFAULT 'Pending',
  `g11_2nd_status` enum('Pending','Accepted','Rejected') DEFAULT 'Pending',
  `g12_1st_status` enum('Pending','Accepted','Rejected') DEFAULT 'Pending',
  `ncii_status` enum('Pending','Accepted','Rejected') DEFAULT 'Pending',
  `guidance_cert_status` enum('Pending','Accepted','Rejected') DEFAULT 'Pending',
  `additional_file_status` enum('Pending','Accepted','Rejected') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`id`, `personal_info_id`, `g11_1st`, `g11_2nd`, `g12_1st`, `ncii`, `guidance_cert`, `additional_file`, `created_at`, `g11_1st_status`, `g11_2nd_status`, `g12_1st_status`, `ncii_status`, `guidance_cert_status`, `additional_file_status`) VALUES
(22, 25, '68e3866746c73_Screenshot 2024-11-04 203232.png', '68e38667488e9_Screenshot 2024-11-05 225403.png', '68e3866749010_Screenshot 2024-12-04 044614.png', '68e38667495e7_Screenshot 2025-01-09 224447.png', '68e3866749e44_Screenshot 2024-11-16 193325.png', '68e386674a405_Screenshot 2024-11-16 193734.png', '2025-10-06 09:05:43', 'Pending', 'Pending', 'Pending', 'Pending', 'Pending', 'Pending'),
(23, 26, '68e90b3c0ee9f_Screenshot 2024-11-15 214048.png', '68e90b3c0f6c5_Screenshot 2024-11-16 193325.png', '68e90b3c0fb88_Screenshot 2024-11-16 193734.png', '68e90b3c100d0_Screenshot 2024-11-25 141404.png', '68e90b3c10645_Screenshot 2024-11-24 230029.png', '68e90b3c10e09_Screenshot 2024-12-03 190922.png', '2025-10-10 13:33:48', 'Rejected', 'Accepted', 'Pending', 'Pending', 'Pending', 'Pending'),
(24, 27, '68f4c35bb0584_Screenshot 2025-08-09 142707.png', '68f4c35bb420a_Screenshot 2025-08-09 151450.png', '68f4c35bb751a_Screenshot 2025-08-09 152425.png', '68f4c35bb7f6d_Screenshot 2025-08-09 152445.png', '68f4c35bb8a71_Screenshot 2025-08-09 152734.png', '68f4c35bba161_Screenshot 2025-08-09 152748.png', '2025-10-19 10:54:19', 'Accepted', 'Accepted', 'Accepted', 'Accepted', 'Accepted', 'Accepted');

-- --------------------------------------------------------

--
-- Table structure for table `exam_answers`
--

CREATE TABLE `exam_answers` (
  `id` int(11) NOT NULL,
  `applicant_id` int(11) NOT NULL,
  `version_id` int(11) NOT NULL,
  `total_questions` int(11) DEFAULT NULL,
  `points_earned` decimal(6,2) DEFAULT 0.00,
  `points_possible` decimal(6,2) DEFAULT 0.00,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_answers`
--

INSERT INTO `exam_answers` (`id`, `applicant_id`, `version_id`, `total_questions`, `points_earned`, `points_possible`, `submitted_at`) VALUES
(42, 20260034, 30, 3, 2.00, 3.00, '2025-10-20 14:32:17');

-- --------------------------------------------------------

--
-- Table structure for table `exam_versions`
--

CREATE TABLE `exam_versions` (
  `id` int(11) NOT NULL,
  `version_name` varchar(255) NOT NULL,
  `is_published` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Published','Unpublished') DEFAULT 'Unpublished',
  `is_archived` tinyint(1) DEFAULT 0,
  `time_limit` int(11) DEFAULT 60,
  `instructions` text DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `chair_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_versions`
--

INSERT INTO `exam_versions` (`id`, `version_name`, `is_published`, `created_at`, `status`, `is_archived`, `time_limit`, `instructions`, `published_at`, `chair_id`) VALUES
(16, 'sample', 0, '2025-05-08 15:17:52', 'Unpublished', 1, 60, NULL, NULL, NULL),
(17, 'sample2', 0, '2025-05-08 15:37:06', 'Unpublished', 1, 60, NULL, NULL, NULL),
(18, 'sample 3', 0, '2025-05-08 15:41:46', 'Unpublished', 1, 60, NULL, NULL, NULL),
(20, 'A.Y. 2024-2025', 0, '2025-05-09 09:37:01', 'Unpublished', 0, 60, NULL, NULL, NULL),
(21, 'A.Y. 2025-2026', 0, '2025-05-10 02:50:51', 'Unpublished', 0, 60, NULL, NULL, NULL),
(22, 'A.Y. 2026-2027', 0, '2025-05-10 07:40:21', 'Unpublished', 0, 60, NULL, NULL, NULL),
(23, 'sample1', 0, '2025-05-12 15:46:46', 'Unpublished', 1, 60, NULL, NULL, NULL),
(24, 'sample4', 0, '2025-05-13 07:41:34', 'Unpublished', 0, 60, NULL, NULL, NULL),
(29, 'A.Y. 2025-2026 Exam Day 1', 0, '2025-08-27 06:57:00', 'Unpublished', 0, 60, NULL, NULL, 3),
(30, 'Admission', 1, '2025-09-24 06:18:02', 'Published', 0, 60, 'answer all questions', '2025-10-19 02:39:33', 3),
(31, 'yyyty', 0, '2025-09-24 06:26:50', 'Unpublished', 0, 60, NULL, NULL, 3),
(34, 'A.Y. 2025-2026 Exam Day 5', 0, '2025-10-10 13:38:09', 'Unpublished', 1, 60, NULL, NULL, 3);

-- --------------------------------------------------------

--
-- Table structure for table `interviewers`
--

CREATE TABLE `interviewers` (
  `id` int(11) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `chairperson_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `interviewers`
--

INSERT INTO `interviewers` (`id`, `last_name`, `first_name`, `email`, `password`, `created_at`, `chairperson_id`) VALUES
(11, 'GUIAGOGO', 'MARK LLOYD', 'matyasaqoe@gmail.com', '$2y$10$KkdQmAAWj0m9knRNf4fLtuELE9yI23DJlybNihSH37pZx5qWcHbm6', '2025-10-19 02:41:43', 3);

-- --------------------------------------------------------

--
-- Table structure for table `interview_schedules`
--

CREATE TABLE `interview_schedules` (
  `id` int(11) NOT NULL,
  `event_date` date NOT NULL,
  `event_time` time NOT NULL,
  `end_time` time DEFAULT NULL,
  `venue` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `interview_schedules`
--

INSERT INTO `interview_schedules` (`id`, `event_date`, `event_time`, `end_time`, `venue`, `created_at`) VALUES
(2, '2025-10-20', '08:36:00', '08:36:00', 'LSAB - Room 311', '2025-10-20 14:36:22');

-- --------------------------------------------------------

--
-- Table structure for table `interview_schedule_applicants`
--

CREATE TABLE `interview_schedule_applicants` (
  `id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `applicant_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `interview_schedule_applicants`
--

INSERT INTO `interview_schedule_applicants` (`id`, `schedule_id`, `applicant_id`, `created_at`) VALUES
(2, 2, 20260034, '2025-10-20 14:36:22');

-- --------------------------------------------------------

--
-- Table structure for table `interview_scores`
--

CREATE TABLE `interview_scores` (
  `id` int(11) NOT NULL,
  `personal_info_id` int(11) NOT NULL,
  `interviewer_id` int(11) NOT NULL,
  `preparedness_math_science` tinyint(4) DEFAULT NULL,
  `preparedness_study_habits` tinyint(4) DEFAULT NULL,
  `preparedness_interest_program` tinyint(4) DEFAULT NULL,
  `preparedness_achievements` tinyint(4) DEFAULT NULL,
  `oral_content_responses` tinyint(4) DEFAULT NULL,
  `oral_delivery_responses` tinyint(4) DEFAULT NULL,
  `oral_mechanics_grammar` tinyint(4) DEFAULT NULL,
  `oral_gestures_facial` tinyint(4) DEFAULT NULL,
  `personal_traits` tinyint(4) DEFAULT NULL,
  `social_traits` tinyint(4) DEFAULT NULL,
  `physical_appearance` tinyint(4) DEFAULT NULL,
  `body_language` tinyint(4) DEFAULT NULL,
  `writing_skills_score` tinyint(4) DEFAULT NULL,
  `reading_comprehension_score` tinyint(4) DEFAULT NULL,
  `total_raw_score` int(11) DEFAULT NULL,
  `interview_score` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal_info`
--

CREATE TABLE `personal_info` (
  `id` int(11) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) NOT NULL,
  `date_of_birth` date NOT NULL,
  `age` int(11) NOT NULL,
  `sex` enum('Male','Female') NOT NULL,
  `contact_number` varchar(50) NOT NULL,
  `region` varchar(50) NOT NULL,
  `province` varchar(50) NOT NULL,
  `city` varchar(50) NOT NULL,
  `barangay` varchar(50) NOT NULL,
  `street_purok` varchar(50) NOT NULL,
  `id_picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `personal_info`
--

INSERT INTO `personal_info` (`id`, `last_name`, `first_name`, `middle_name`, `date_of_birth`, `age`, `sex`, `contact_number`, `region`, `province`, `city`, `barangay`, `street_purok`, `id_picture`, `created_at`) VALUES
(25, 'regalado', 'ashly', 'JARANILLA', '2003-12-29', 21, 'Female', '09312312434', 'Western Visayas', 'Negros Occidental', 'City of Bacolod', 'Punta Taytay', 'Mangga', '68e385f787645.png', '2025-10-06 09:03:51'),
(26, 'REGALADO', 'ASHLEY CARYLL', '', '2003-12-10', 21, 'Female', '09704643801', 'Western Visayas', 'Negros Occidental', 'City of Bacolod', 'Punta Taytay', 'Mangga', '68e90a3ed83d9.jpg', '2025-10-10 13:29:34'),
(27, 'guiagogo', 'mark lloyd', 'marfil', '2001-12-22', 23, 'Male', '09614376716', 'Western Visayas', 'Negros Occidental', 'Pontevedra', 'Antipolo', 'Purok Lerio', '68f4c2ffa823f.png', '2025-10-19 10:52:47');

-- --------------------------------------------------------

--
-- Table structure for table `program_application`
--

CREATE TABLE `program_application` (
  `id` int(11) NOT NULL,
  `personal_info_id` int(11) NOT NULL,
  `campus` enum('Talisay','Alijis','Fortune','Binalbagan') NOT NULL,
  `college` enum('CCS') DEFAULT NULL,
  `program` enum('BSIS','BSIT') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `registration_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `program_application`
--

INSERT INTO `program_application` (`id`, `personal_info_id`, `campus`, `college`, `program`, `created_at`, `registration_id`) VALUES
(26, 25, 'Talisay', '', 'BSIS', '2025-10-06 09:04:45', NULL),
(27, 26, 'Talisay', 'CCS', 'BSIS', '2025-10-10 13:32:42', NULL),
(28, 27, 'Talisay', 'CCS', 'BSIS', '2025-10-19 10:53:49', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `id` int(11) NOT NULL,
  `version_id` int(11) NOT NULL,
  `question_number` int(11) DEFAULT NULL,
  `question_type` varchar(50) NOT NULL,
  `question_text` text NOT NULL,
  `option_a` varchar(255) DEFAULT NULL,
  `option_b` varchar(255) DEFAULT NULL,
  `option_c` varchar(255) DEFAULT NULL,
  `option_d` varchar(255) DEFAULT NULL,
  `answer` varchar(255) NOT NULL,
  `points` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `exam_version` varchar(255) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`id`, `version_id`, `question_number`, `question_type`, `question_text`, `option_a`, `option_b`, `option_c`, `option_d`, `answer`, `points`, `created_at`, `exam_version`, `image_url`) VALUES
(101, 16, 1, 'multiple', '<strong>CP st</strong>ands for?', 'Canton Pancit', 'Cell Phone', 'Call Papi', 'Coke Pepsi', 'B', 1, '2025-05-08 15:18:52', '', NULL),
(102, 16, 2, 'short', 'sample', '', '', '', '', 'sample', 1, '2025-05-08 15:18:52', '', NULL),
(103, 16, 3, 'truefalse', 'sample', 'True', 'False', '', '', 'False', 1, '2025-05-09 01:52:04', '', NULL),
(104, 16, 4, 'truefalse', 'sample', 'True', 'False', '', '', 'True', 1, '2025-05-09 01:53:07', '', NULL),
(106, 20, 1, 'multiple', 'What does the acronym USB stands for?', 'University State Building', 'Universal Serial Bus', 'Unity Sara Bongbong', 'Unli Sabaw Baboy', 'B', 1, '2025-05-09 09:38:28', '', NULL),
(107, 20, 2, 'multiple', '1+1?', '2', '3', '4', '6', 'A', 1, '2025-05-09 09:38:28', '', NULL),
(108, 20, 3, 'short', 'what is the chemical symbol for gold?', '', '', '', '', 'Au', 1, '2025-05-09 13:47:31', '', NULL),
(113, 29, 1, 'multiple', 'What does the USB acronym stands for?', 'Universal Serial Bus', 'Una Bwass Skwela', 'Unli Sabaw Baboy', 'Unity Bongbong Sara', 'A', 1, '2025-09-21 07:27:35', '', NULL),
(119, 30, 1, 'multiple', 'What is our country', 'PH', 'KOR', 'USA', 'JPN', 'PH', 1, '2025-09-24 06:38:06', '', NULL),
(120, 30, 2, 'truefalse', 'Am i Handsome', 'True', 'False', '', '', 'True', 1, '2025-10-19 09:29:50', NULL, NULL),
(121, 30, 3, 'short', 'What is my name', '', '', '', '', 'Mark Lloyd', 1, '2025-10-19 09:29:50', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `registration`
--

CREATE TABLE `registration` (
  `id` int(11) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `email_address` varchar(100) NOT NULL,
  `applicant_status` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `personal_info_id` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `application_submitted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `registration`
--

INSERT INTO `registration` (`id`, `last_name`, `first_name`, `email_address`, `applicant_status`, `password`, `personal_info_id`, `updated_at`, `created_at`, `application_submitted`) VALUES
(20260032, 'regalado', 'ashly', 'matyasaqoe@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$nS4T55hIgEBSa97ESJO5FudYe3TjKC7NLL2C60ELLw7SeSQcF8jeO', 25, '2025-10-06 17:05:52', '2025-10-06 17:00:14', 1),
(20260033, 'REGALADO', 'ASHLEY CARYLL', 'acregalado.chmsu@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$Iss2zcV/GoRJZbozB4mKdOU7BqbW38gsjejw.isYtbJLSVbBAJVUO', 26, '2025-10-10 21:34:20', '2025-10-10 21:25:49', 1),
(20260034, 'guiagogo', 'mark lloyd', 'marklloyd.guiagogo@chmsc.edu.ph', 'New Applicant - Same Academic Year', '$2y$10$N5o9Jb8V7DCb4VPMDdMPR.O2DQITqglTlDATFdsRIsWiOZZ33/68y', 27, '2025-10-20 06:50:19', '2025-10-19 03:50:12', 1);

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `building_id` int(11) NOT NULL,
  `room_number` varchar(50) NOT NULL,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `building_id`, `room_number`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, '311', 'active', '2025-10-19 08:56:53', NULL),
(2, 1, '312', 'active', '2025-10-19 08:57:03', NULL),
(3, 1, '313', 'active', '2025-10-19 08:57:14', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `id` int(11) NOT NULL,
  `event_date` date NOT NULL,
  `event_time` time NOT NULL,
  `end_time` time NOT NULL,
  `venue` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`id`, `event_date`, `event_time`, `end_time`, `venue`, `created_at`) VALUES
(5, '2025-10-20', '08:35:00', '08:35:00', 'LSAB - Room 311', '2025-10-20 14:35:42');

-- --------------------------------------------------------

--
-- Table structure for table `schedule_applicants`
--

CREATE TABLE `schedule_applicants` (
  `id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `applicant_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedule_applicants`
--

INSERT INTO `schedule_applicants` (`id`, `schedule_id`, `applicant_id`, `created_at`) VALUES
(5, 5, 20260034, '2025-10-20 14:35:42');

-- --------------------------------------------------------

--
-- Table structure for table `screening_results`
--

CREATE TABLE `screening_results` (
  `id` int(11) NOT NULL,
  `personal_info_id` int(11) NOT NULL,
  `gwa_score` decimal(5,2) DEFAULT NULL,
  `stanine_result` int(11) DEFAULT NULL,
  `stanine_score` decimal(5,2) DEFAULT NULL,
  `initial_total` decimal(5,2) DEFAULT NULL,
  `qualifying_exam_score` decimal(5,2) DEFAULT NULL,
  `exam_total_score` decimal(5,2) DEFAULT NULL,
  `exam_percentage` decimal(5,2) DEFAULT NULL,
  `interview_total_score` decimal(5,2) DEFAULT NULL,
  `interview_percentage` decimal(5,2) DEFAULT NULL,
  `plus_factor` decimal(5,2) DEFAULT NULL,
  `final_rating` decimal(5,2) DEFAULT NULL,
  `rank` int(11) DEFAULT NULL,
  `note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `screening_results`
--

INSERT INTO `screening_results` (`id`, `personal_info_id`, `gwa_score`, `stanine_result`, `stanine_score`, `initial_total`, `qualifying_exam_score`, `exam_total_score`, `exam_percentage`, `interview_total_score`, `interview_percentage`, `plus_factor`, `final_rating`, `rank`, `note`) VALUES
(80, 25, 68.67, 8, 8.00, 8.07, NULL, NULL, 0.00, NULL, 0.00, NULL, 8.07, 3, NULL),
(100, 26, 95.33, 8, 8.00, 10.73, NULL, NULL, 0.00, NULL, 0.00, NULL, 10.73, 1, NULL),
(102, 27, 91.67, 9, 9.00, 10.52, NULL, 66.67, 0.00, NULL, 0.00, NULL, 10.52, 2, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `socio_demographic`
--

CREATE TABLE `socio_demographic` (
  `id` int(11) NOT NULL,
  `personal_info_id` int(11) NOT NULL,
  `marital_status` enum('Single','Married','Divorced','Domestic Partnership','Others') NOT NULL,
  `religion` enum('None','Christianity','Islam','Hinduism','Others') NOT NULL,
  `orientation` enum('Heterosexual','Homosexual','Bisexual','Others') NOT NULL,
  `father_status` enum('Alive; Away','Alive; at Home','Deceased','Unknown') NOT NULL,
  `father_education` enum('No High School Diploma','High School Diploma','Bachelor''s Degree','Graduate Degree') NOT NULL,
  `father_employment` enum('Employed Full-Time','Employed Part-Time','Unemployed') NOT NULL,
  `mother_status` enum('Alive; Away','Alive; at Home','Deceased','Unknown') NOT NULL,
  `mother_education` enum('No High School Diploma','High School Diploma','Bachelor''s Degree','Graduate Degree') NOT NULL,
  `mother_employment` enum('Employed Full-Time','Employed Part-Time','Unemployed') NOT NULL,
  `siblings` enum('None','One','Two or more') NOT NULL,
  `living_with` enum('Both parents','One parent only','Relatives','Alone') NOT NULL,
  `access_computer` varchar(3) DEFAULT NULL,
  `access_internet` varchar(3) DEFAULT NULL,
  `access_mobile` varchar(3) DEFAULT NULL,
  `indigenous_group` varchar(3) NOT NULL,
  `first_gen_college` varchar(3) DEFAULT NULL,
  `was_scholar` varchar(3) DEFAULT NULL,
  `received_honors` varchar(3) DEFAULT NULL,
  `has_disability` varchar(3) DEFAULT NULL,
  `disability_detail` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `socio_demographic`
--

INSERT INTO `socio_demographic` (`id`, `personal_info_id`, `marital_status`, `religion`, `orientation`, `father_status`, `father_education`, `father_employment`, `mother_status`, `mother_education`, `mother_employment`, `siblings`, `living_with`, `access_computer`, `access_internet`, `access_mobile`, `indigenous_group`, `first_gen_college`, `was_scholar`, `received_honors`, `has_disability`, `disability_detail`, `created_at`) VALUES
(25, 25, 'Single', 'None', 'Heterosexual', 'Unknown', 'No High School Diploma', 'Unemployed', 'Alive; Away', 'No High School Diploma', 'Employed Full-Time', 'None', 'One parent only', 'Yes', 'Yes', 'Yes', 'No', 'No', 'No', 'No', 'No', '', '2025-10-06 09:03:51'),
(26, 26, 'Single', 'Christianity', 'Heterosexual', 'Unknown', 'No High School Diploma', 'Employed Full-Time', 'Alive; Away', 'Bachelor\'s Degree', 'Employed Full-Time', 'None', 'One parent only', 'Yes', 'Yes', 'Yes', 'No', 'Yes', 'No', 'Yes', 'No', '', '2025-10-10 13:29:34'),
(27, 27, 'Single', 'Christianity', 'Heterosexual', 'Alive; Away', 'High School Diploma', 'Employed Full-Time', 'Alive; Away', 'High School Diploma', 'Unemployed', 'One', 'Relatives', 'No', 'No', 'Yes', 'No', 'Yes', 'No', 'Yes', 'No', '', '2025-10-19 10:52:47');

-- --------------------------------------------------------

--
-- Table structure for table `strands`
--

CREATE TABLE `strands` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `strands`
--

INSERT INTO `strands` (`id`, `name`, `status`, `created_at`, `updated_at`) VALUES
(1, 'HUMSS', 'active', '2025-10-06 09:00:00', NULL),
(2, 'TVL', 'active', '2025-10-06 09:00:00', NULL),
(3, 'STEM', 'active', '2025-10-06 09:00:00', NULL),
(4, 'ABM', 'active', '2025-10-06 09:00:00', NULL),
(5, 'GAS', 'active', '2025-10-06 09:00:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `uploads`
--

CREATE TABLE `uploads` (
  `id` int(11) NOT NULL,
  `applicant_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_background`
--
ALTER TABLE `academic_background`
  ADD PRIMARY KEY (`id`),
  ADD KEY `personal_info_id` (`personal_info_id`),
  ADD KEY `academic_background_ibfk_2` (`strand_id`);

--
-- Indexes for table `application_status`
--
ALTER TABLE `application_status`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `buildings`
--
ALTER TABLE `buildings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `chairperson_accounts`
--
ALTER TABLE `chairperson_accounts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `personal_info_id` (`personal_info_id`);

--
-- Indexes for table `exam_answers`
--
ALTER TABLE `exam_answers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_applicant_version` (`applicant_id`,`version_id`),
  ADD KEY `applicant_id` (`applicant_id`),
  ADD KEY `version_id` (`version_id`);

--
-- Indexes for table `exam_versions`
--
ALTER TABLE `exam_versions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `version_name` (`version_name`),
  ADD KEY `fk_exam_chair` (`chair_id`);

--
-- Indexes for table `interviewers`
--
ALTER TABLE `interviewers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`email`),
  ADD KEY `fk_chairperson` (`chairperson_id`);

--
-- Indexes for table `interview_schedules`
--
ALTER TABLE `interview_schedules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `interview_schedule_applicants`
--
ALTER TABLE `interview_schedule_applicants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_interview_schedule_applicant` (`schedule_id`,`applicant_id`),
  ADD KEY `idx_interview_schedule_id` (`schedule_id`),
  ADD KEY `idx_interview_applicant_id` (`applicant_id`);

--
-- Indexes for table `interview_scores`
--
ALTER TABLE `interview_scores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `interviewer_id` (`interviewer_id`),
  ADD KEY `personal_info_id` (`personal_info_id`);

--
-- Indexes for table `personal_info`
--
ALTER TABLE `personal_info`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `program_application`
--
ALTER TABLE `program_application`
  ADD PRIMARY KEY (`id`),
  ADD KEY `personal_info_id` (`personal_info_id`),
  ADD KEY `fk_pa_registrationid` (`registration_id`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `version_id` (`version_id`);

--
-- Indexes for table `registration`
--
ALTER TABLE `registration`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email_address` (`email_address`),
  ADD UNIQUE KEY `Index 2` (`email_address`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `building_id` (`building_id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `schedule_applicants`
--
ALTER TABLE `schedule_applicants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_schedule_applicant` (`schedule_id`,`applicant_id`),
  ADD KEY `schedule_id` (`schedule_id`),
  ADD KEY `applicant_id` (`applicant_id`);

--
-- Indexes for table `screening_results`
--
ALTER TABLE `screening_results`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_personal_info` (`personal_info_id`);

--
-- Indexes for table `socio_demographic`
--
ALTER TABLE `socio_demographic`
  ADD PRIMARY KEY (`id`),
  ADD KEY `personal_info_id` (`personal_info_id`);

--
-- Indexes for table `strands`
--
ALTER TABLE `strands`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `uploads`
--
ALTER TABLE `uploads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `applicant_id` (`applicant_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_background`
--
ALTER TABLE `academic_background`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `application_status`
--
ALTER TABLE `application_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `buildings`
--
ALTER TABLE `buildings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `chairperson_accounts`
--
ALTER TABLE `chairperson_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `exam_answers`
--
ALTER TABLE `exam_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `exam_versions`
--
ALTER TABLE `exam_versions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `interviewers`
--
ALTER TABLE `interviewers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `interview_schedules`
--
ALTER TABLE `interview_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `interview_schedule_applicants`
--
ALTER TABLE `interview_schedule_applicants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `interview_scores`
--
ALTER TABLE `interview_scores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `personal_info`
--
ALTER TABLE `personal_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `program_application`
--
ALTER TABLE `program_application`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=122;

--
-- AUTO_INCREMENT for table `registration`
--
ALTER TABLE `registration`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20260035;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `schedule_applicants`
--
ALTER TABLE `schedule_applicants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `screening_results`
--
ALTER TABLE `screening_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=122;

--
-- AUTO_INCREMENT for table `socio_demographic`
--
ALTER TABLE `socio_demographic`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `strands`
--
ALTER TABLE `strands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `uploads`
--
ALTER TABLE `uploads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `academic_background`
--
ALTER TABLE `academic_background`
  ADD CONSTRAINT `academic_background_ibfk_1` FOREIGN KEY (`personal_info_id`) REFERENCES `personal_info` (`id`),
  ADD CONSTRAINT `academic_background_ibfk_2` FOREIGN KEY (`strand_id`) REFERENCES `strands` (`id`);

--
-- Constraints for table `application_status`
--
ALTER TABLE `application_status`
  ADD CONSTRAINT `fk_application_status_user` FOREIGN KEY (`user_id`) REFERENCES `registration` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`personal_info_id`) REFERENCES `personal_info` (`id`);

--
-- Constraints for table `exam_answers`
--
ALTER TABLE `exam_answers`
  ADD CONSTRAINT `exam_answers_ibfk_1` FOREIGN KEY (`applicant_id`) REFERENCES `registration` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_answers_ibfk_3` FOREIGN KEY (`version_id`) REFERENCES `exam_versions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_versions`
--
ALTER TABLE `exam_versions`
  ADD CONSTRAINT `fk_exam_chair` FOREIGN KEY (`chair_id`) REFERENCES `chairperson_accounts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `interviewers`
--
ALTER TABLE `interviewers`
  ADD CONSTRAINT `fk_chairperson` FOREIGN KEY (`chairperson_id`) REFERENCES `chairperson_accounts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `interview_scores`
--
ALTER TABLE `interview_scores`
  ADD CONSTRAINT `interview_scores_ibfk_1` FOREIGN KEY (`interviewer_id`) REFERENCES `interviewers` (`id`),
  ADD CONSTRAINT `interview_scores_ibfk_2` FOREIGN KEY (`personal_info_id`) REFERENCES `personal_info` (`id`);

--
-- Constraints for table `program_application`
--
ALTER TABLE `program_application`
  ADD CONSTRAINT `fk_pa_registrationid` FOREIGN KEY (`registration_id`) REFERENCES `registration` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_registration` FOREIGN KEY (`registration_id`) REFERENCES `registration` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `program_application_ibfk_1` FOREIGN KEY (`personal_info_id`) REFERENCES `personal_info` (`id`);

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`version_id`) REFERENCES `exam_versions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `schedule_applicants`
--
ALTER TABLE `schedule_applicants`
  ADD CONSTRAINT `schedule_applicants_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `schedule_applicants_ibfk_2` FOREIGN KEY (`applicant_id`) REFERENCES `registration` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `screening_results`
--
ALTER TABLE `screening_results`
  ADD CONSTRAINT `screening_results_ibfk_1` FOREIGN KEY (`personal_info_id`) REFERENCES `personal_info` (`id`);

--
-- Constraints for table `socio_demographic`
--
ALTER TABLE `socio_demographic`
  ADD CONSTRAINT `socio_demographic_ibfk_1` FOREIGN KEY (`personal_info_id`) REFERENCES `personal_info` (`id`);

--
-- Constraints for table `uploads`
--
ALTER TABLE `uploads`
  ADD CONSTRAINT `uploads_ibfk_1` FOREIGN KEY (`applicant_id`) REFERENCES `registration` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
