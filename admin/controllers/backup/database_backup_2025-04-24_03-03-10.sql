-- Database Backup
-- Database: capstone-new
-- Timestamp: 2025-04-24_03-03-10


-- Table structure for table `created_payments`
DROP TABLE IF EXISTS `created_payments`;
CREATE TABLE `created_payments` (
  `payment_id` int(100) NOT NULL AUTO_INCREMENT,
  `course` varchar(100) NOT NULL,
  `year_level` varchar(100) NOT NULL,
  `student_type` varchar(100) NOT NULL,
  `tuition_type` varchar(100) NOT NULL,
  `fee_for` varchar(255) NOT NULL,
  `event_date_start` date DEFAULT NULL,
  `event_date_end` date DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `due_date` date NOT NULL,
  `status` enum('unpaid','pending','paid') DEFAULT 'unpaid',
  `reference` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deducted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`payment_id`)
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table structure for table `semester_fees`
DROP TABLE IF EXISTS `semester_fees`;
CREATE TABLE `semester_fees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_number` int(11) NOT NULL,
  `firstname` varchar(255) NOT NULL,
  `lastname` varchar(255) NOT NULL,
  `fee_for` varchar(255) NOT NULL,
  `event_date_start` date DEFAULT NULL,
  `event_date_end` date DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `due_date` date NOT NULL,
  `status` enum('Unpaid','Pending','Paid') DEFAULT 'Unpaid',
  `reference` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deducted` tinyint(1) DEFAULT 0,
  `payment_date` datetime DEFAULT NULL,
  `payment_method` varchar(255) DEFAULT NULL,
  `verified_date` datetime DEFAULT NULL,
  `pending_timestamp` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1872 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `semester_fees`
INSERT INTO `semester_fees` (`id`, `student_number`, `firstname`, `lastname`, `fee_for`, `event_date_start`, `event_date_end`, `amount`, `due_date`, `status`, `reference`, `created_at`, `updated_at`, `deducted`, `payment_date`, `payment_method`, `verified_date`, `pending_timestamp`) VALUES ('1868', '0', '', '', 'Prelim', '2025-05-01', '2025-05-15', '1500.00', '2025-04-24', 'Unpaid', '', '2025-04-24 09:03:10', '2025-04-24 09:03:10', '0', '', '', '', '');
INSERT INTO `semester_fees` (`id`, `student_number`, `firstname`, `lastname`, `fee_for`, `event_date_start`, `event_date_end`, `amount`, `due_date`, `status`, `reference`, `created_at`, `updated_at`, `deducted`, `payment_date`, `payment_method`, `verified_date`, `pending_timestamp`) VALUES ('1869', '0', '', '', 'Midterm', '2025-05-29', '2025-06-12', '1500.00', '2025-04-24', 'Unpaid', '', '2025-04-24 09:03:10', '2025-04-24 09:03:10', '0', '', '', '', '');
INSERT INTO `semester_fees` (`id`, `student_number`, `firstname`, `lastname`, `fee_for`, `event_date_start`, `event_date_end`, `amount`, `due_date`, `status`, `reference`, `created_at`, `updated_at`, `deducted`, `payment_date`, `payment_method`, `verified_date`, `pending_timestamp`) VALUES ('1870', '0', '', '', 'Prefinal', '2025-06-26', '2025-07-10', '1500.00', '2025-04-24', 'Unpaid', '', '2025-04-24 09:03:10', '2025-04-24 09:03:10', '0', '', '', '', '');
INSERT INTO `semester_fees` (`id`, `student_number`, `firstname`, `lastname`, `fee_for`, `event_date_start`, `event_date_end`, `amount`, `due_date`, `status`, `reference`, `created_at`, `updated_at`, `deducted`, `payment_date`, `payment_method`, `verified_date`, `pending_timestamp`) VALUES ('1871', '0', '', '', 'Final', '2025-07-24', '2025-08-07', '1500.00', '2025-04-24', 'Unpaid', '', '2025-04-24 09:03:10', '2025-04-24 09:03:10', '0', '', '', '', '');


-- Table structure for table `student_accounts`
DROP TABLE IF EXISTS `student_accounts`;
CREATE TABLE `student_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_number` int(100) NOT NULL,
  `student_type` varchar(100) NOT NULL,
  `tuition_type` varchar(100) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `middlename` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `course` varchar(100) NOT NULL,
  `year_level` int(100) NOT NULL,
  `section` varchar(100) NOT NULL,
  `semester` int(100) NOT NULL,
  `total_tuition_fee` int(100) NOT NULL,
  `tuition_fee_discount` int(100) NOT NULL,
  `balance_to_be_paid` int(100) NOT NULL,
  `down_payment` int(100) NOT NULL,
  `total_balance` int(100) NOT NULL,
  `remaining_balance_to_pay` int(100) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_number` (`student_number`)
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table structure for table `student_payments`
DROP TABLE IF EXISTS `student_payments`;
CREATE TABLE `student_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_number` int(11) NOT NULL,
  `fee_for` varchar(255) NOT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `event_date_start` date DEFAULT NULL,
  `event_date_end` date DEFAULT NULL,
  `due_date` date NOT NULL,
  `status` enum('Unpaid','Pending','Paid','Partial Payment') DEFAULT 'Unpaid',
  `reference` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `payment_date` datetime DEFAULT NULL,
  `payment_method` varchar(255) DEFAULT NULL,
  `verified_date` datetime DEFAULT NULL,
  `pending_timestamp` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=714 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

