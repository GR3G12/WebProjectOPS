-- Database Backup
-- Database: capstone-new
-- Timestamp: 2025-04-26


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
) ENGINE=InnoDB AUTO_INCREMENT=78 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


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
) ENGINE=InnoDB AUTO_INCREMENT=2604 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `semester_fees`
INSERT INTO `semester_fees` (`id`, `student_number`, `firstname`, `lastname`, `fee_for`, `event_date_start`, `event_date_end`, `amount`, `due_date`, `status`, `reference`, `created_at`, `updated_at`, `deducted`, `payment_date`, `payment_method`, `verified_date`, `pending_timestamp`) VALUES ('2588', '0', '', '', 'Prelim', '2025-05-03', '2025-05-17', '1500.00', '2025-04-26', 'Unpaid', '', '2025-04-26 20:57:43', '2025-04-26 20:57:43', '0', '', '', '', '');
INSERT INTO `semester_fees` (`id`, `student_number`, `firstname`, `lastname`, `fee_for`, `event_date_start`, `event_date_end`, `amount`, `due_date`, `status`, `reference`, `created_at`, `updated_at`, `deducted`, `payment_date`, `payment_method`, `verified_date`, `pending_timestamp`) VALUES ('2589', '0', '', '', 'Midterm', '2025-05-31', '2025-06-14', '1500.00', '2025-04-26', 'Unpaid', '', '2025-04-26 20:57:43', '2025-04-26 20:57:43', '0', '', '', '', '');
INSERT INTO `semester_fees` (`id`, `student_number`, `firstname`, `lastname`, `fee_for`, `event_date_start`, `event_date_end`, `amount`, `due_date`, `status`, `reference`, `created_at`, `updated_at`, `deducted`, `payment_date`, `payment_method`, `verified_date`, `pending_timestamp`) VALUES ('2590', '0', '', '', 'Prefinal', '2025-06-28', '2025-07-12', '1500.00', '2025-04-26', 'Unpaid', '', '2025-04-26 20:57:43', '2025-04-26 20:57:43', '0', '', '', '', '');
INSERT INTO `semester_fees` (`id`, `student_number`, `firstname`, `lastname`, `fee_for`, `event_date_start`, `event_date_end`, `amount`, `due_date`, `status`, `reference`, `created_at`, `updated_at`, `deducted`, `payment_date`, `payment_method`, `verified_date`, `pending_timestamp`) VALUES ('2591', '0', '', '', 'Final', '2025-07-26', '2025-08-09', '1500.00', '2025-04-26', 'Unpaid', '', '2025-04-26 20:57:43', '2025-04-26 20:57:43', '0', '', '', '', '');
INSERT INTO `semester_fees` (`id`, `student_number`, `firstname`, `lastname`, `fee_for`, `event_date_start`, `event_date_end`, `amount`, `due_date`, `status`, `reference`, `created_at`, `updated_at`, `deducted`, `payment_date`, `payment_method`, `verified_date`, `pending_timestamp`) VALUES ('2592', '16735', 'Alex', 'Ant', 'Prelim', '2025-05-03', '2025-05-17', '1500.00', '2025-04-26', 'Unpaid', '', '2025-04-26 20:59:27', '2025-04-26 20:59:27', '0', '', '', '', '');
INSERT INTO `semester_fees` (`id`, `student_number`, `firstname`, `lastname`, `fee_for`, `event_date_start`, `event_date_end`, `amount`, `due_date`, `status`, `reference`, `created_at`, `updated_at`, `deducted`, `payment_date`, `payment_method`, `verified_date`, `pending_timestamp`) VALUES ('2593', '16735', 'Alex', 'Ant', 'Midterm', '2025-05-31', '2025-06-14', '1500.00', '2025-04-26', 'Unpaid', '', '2025-04-26 20:59:27', '2025-04-26 20:59:27', '0', '', '', '', '');
INSERT INTO `semester_fees` (`id`, `student_number`, `firstname`, `lastname`, `fee_for`, `event_date_start`, `event_date_end`, `amount`, `due_date`, `status`, `reference`, `created_at`, `updated_at`, `deducted`, `payment_date`, `payment_method`, `verified_date`, `pending_timestamp`) VALUES ('2594', '16735', 'Alex', 'Ant', 'Prefinal', '2025-06-28', '2025-07-12', '1500.00', '2025-04-26', 'Unpaid', '', '2025-04-26 20:59:27', '2025-04-26 20:59:27', '0', '', '', '', '');
INSERT INTO `semester_fees` (`id`, `student_number`, `firstname`, `lastname`, `fee_for`, `event_date_start`, `event_date_end`, `amount`, `due_date`, `status`, `reference`, `created_at`, `updated_at`, `deducted`, `payment_date`, `payment_method`, `verified_date`, `pending_timestamp`) VALUES ('2595', '16735', 'Alex', 'Ant', 'Final', '2025-07-26', '2025-08-09', '1500.00', '2025-04-26', 'Unpaid', '', '2025-04-26 20:59:27', '2025-04-26 20:59:27', '0', '', '', '', '');
INSERT INTO `semester_fees` (`id`, `student_number`, `firstname`, `lastname`, `fee_for`, `event_date_start`, `event_date_end`, `amount`, `due_date`, `status`, `reference`, `created_at`, `updated_at`, `deducted`, `payment_date`, `payment_method`, `verified_date`, `pending_timestamp`) VALUES ('2596', '16736', 'Alexx', 'Ants', 'Prelim', '2025-05-03', '2025-05-17', '1500.00', '2025-04-26', 'Unpaid', '', '2025-04-26 20:59:33', '2025-04-26 20:59:33', '0', '', '', '', '');
INSERT INTO `semester_fees` (`id`, `student_number`, `firstname`, `lastname`, `fee_for`, `event_date_start`, `event_date_end`, `amount`, `due_date`, `status`, `reference`, `created_at`, `updated_at`, `deducted`, `payment_date`, `payment_method`, `verified_date`, `pending_timestamp`) VALUES ('2597', '16736', 'Alexx', 'Ants', 'Midterm', '2025-05-31', '2025-06-14', '1500.00', '2025-04-26', 'Unpaid', '', '2025-04-26 20:59:33', '2025-04-26 20:59:33', '0', '', '', '', '');
INSERT INTO `semester_fees` (`id`, `student_number`, `firstname`, `lastname`, `fee_for`, `event_date_start`, `event_date_end`, `amount`, `due_date`, `status`, `reference`, `created_at`, `updated_at`, `deducted`, `payment_date`, `payment_method`, `verified_date`, `pending_timestamp`) VALUES ('2598', '16736', 'Alexx', 'Ants', 'Prefinal', '2025-06-28', '2025-07-12', '1500.00', '2025-04-26', 'Unpaid', '', '2025-04-26 20:59:33', '2025-04-26 20:59:33', '0', '', '', '', '');
INSERT INTO `semester_fees` (`id`, `student_number`, `firstname`, `lastname`, `fee_for`, `event_date_start`, `event_date_end`, `amount`, `due_date`, `status`, `reference`, `created_at`, `updated_at`, `deducted`, `payment_date`, `payment_method`, `verified_date`, `pending_timestamp`) VALUES ('2599', '16736', 'Alexx', 'Ants', 'Final', '2025-07-26', '2025-08-09', '1500.00', '2025-04-26', 'Unpaid', '', '2025-04-26 20:59:33', '2025-04-26 20:59:33', '0', '', '', '', '');
INSERT INTO `semester_fees` (`id`, `student_number`, `firstname`, `lastname`, `fee_for`, `event_date_start`, `event_date_end`, `amount`, `due_date`, `status`, `reference`, `created_at`, `updated_at`, `deducted`, `payment_date`, `payment_method`, `verified_date`, `pending_timestamp`) VALUES ('2600', '16737', 'Jam', 'Agarao', 'Prelim', '2025-05-03', '2025-05-17', '2750.00', '2025-04-26', 'Unpaid', '', '2025-04-26 20:59:42', '2025-04-26 20:59:42', '0', '', '', '', '');
INSERT INTO `semester_fees` (`id`, `student_number`, `firstname`, `lastname`, `fee_for`, `event_date_start`, `event_date_end`, `amount`, `due_date`, `status`, `reference`, `created_at`, `updated_at`, `deducted`, `payment_date`, `payment_method`, `verified_date`, `pending_timestamp`) VALUES ('2601', '16737', 'Jam', 'Agarao', 'Midterm', '2025-05-31', '2025-06-14', '2750.00', '2025-04-26', 'Unpaid', '', '2025-04-26 20:59:42', '2025-04-26 20:59:42', '0', '', '', '', '');
INSERT INTO `semester_fees` (`id`, `student_number`, `firstname`, `lastname`, `fee_for`, `event_date_start`, `event_date_end`, `amount`, `due_date`, `status`, `reference`, `created_at`, `updated_at`, `deducted`, `payment_date`, `payment_method`, `verified_date`, `pending_timestamp`) VALUES ('2602', '16737', 'Jam', 'Agarao', 'Prefinal', '2025-06-28', '2025-07-12', '2750.00', '2025-04-26', 'Unpaid', '', '2025-04-26 20:59:42', '2025-04-26 20:59:42', '0', '', '', '', '');
INSERT INTO `semester_fees` (`id`, `student_number`, `firstname`, `lastname`, `fee_for`, `event_date_start`, `event_date_end`, `amount`, `due_date`, `status`, `reference`, `created_at`, `updated_at`, `deducted`, `payment_date`, `payment_method`, `verified_date`, `pending_timestamp`) VALUES ('2603', '16737', 'Jam', 'Agarao', 'Final', '2025-07-26', '2025-08-09', '2750.00', '2025-04-26', 'Unpaid', '', '2025-04-26 20:59:42', '2025-04-26 20:59:42', '0', '', '', '', '');


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
) ENGINE=InnoDB AUTO_INCREMENT=116 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `student_accounts`
INSERT INTO `student_accounts` (`id`, `student_number`, `student_type`, `tuition_type`, `firstname`, `middlename`, `lastname`, `email`, `course`, `year_level`, `section`, `semester`, `total_tuition_fee`, `tuition_fee_discount`, `balance_to_be_paid`, `down_payment`, `total_balance`, `remaining_balance_to_pay`, `profile_image`) VALUES ('113', '16735', 'regular', 'scholar', 'Alex', 'N', 'Ant', 'pogi@gmail.com', 'BSCE', '4', 'B', '1', '11000', '0', '11000', '5000', '6000', '6000', '');
INSERT INTO `student_accounts` (`id`, `student_number`, `student_type`, `tuition_type`, `firstname`, `middlename`, `lastname`, `email`, `course`, `year_level`, `section`, `semester`, `total_tuition_fee`, `tuition_fee_discount`, `balance_to_be_paid`, `down_payment`, `total_balance`, `remaining_balance_to_pay`, `profile_image`) VALUES ('114', '16736', 'regular', 'scholar', 'Alexx', 'N.', 'Ants', 'pogig@gmail.com', 'BSIT', '4', 'B', '1', '11000', '0', '11000', '5000', '6000', '6000', '');
INSERT INTO `student_accounts` (`id`, `student_number`, `student_type`, `tuition_type`, `firstname`, `middlename`, `lastname`, `email`, `course`, `year_level`, `section`, `semester`, `total_tuition_fee`, `tuition_fee_discount`, `balance_to_be_paid`, `down_payment`, `total_balance`, `remaining_balance_to_pay`, `profile_image`) VALUES ('115', '16737', 'Regular', 'scholar', 'Jam', 'N.', 'Agarao', 'aga@gmail.com', 'BSIT', '4', 'A', '1', '15000', '1000', '14000', '3000', '11000', '11000', '');


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
) ENGINE=InnoDB AUTO_INCREMENT=717 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

