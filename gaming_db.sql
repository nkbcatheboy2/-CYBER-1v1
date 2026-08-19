-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 18, 2026 at 11:37 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gaming_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `matches`
--

CREATE TABLE `matches` (
  `id` int(11) NOT NULL,
  `room_code` varchar(4) NOT NULL,
  `game_type` enum('ludo','chess','qa_challenge') NOT NULL,
  `player1_id` int(11) NOT NULL,
  `player2_id` int(11) DEFAULT NULL,
  `bet_amount` decimal(10,2) NOT NULL,
  `win_amount` decimal(10,2) NOT NULL,
  `platform_fee` decimal(10,2) NOT NULL,
  `winner_id` int(11) DEFAULT NULL,
  `status` enum('waiting','in_progress','completed','cancelled') DEFAULT 'waiting',
  `custom_question` text DEFAULT NULL,
  `custom_answer` varchar(255) DEFAULT NULL,
  `game_state` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`game_state`)),
  `current_turn` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `matches`
--

INSERT INTO `matches` (`id`, `room_code`, `game_type`, `player1_id`, `player2_id`, `bet_amount`, `win_amount`, `platform_fee`, `winner_id`, `status`, `custom_question`, `custom_answer`, `game_state`, `current_turn`, `created_at`) VALUES
(1, '5068', 'ludo', 1, NULL, 10.00, 15.00, 5.00, NULL, 'waiting', '', '', '{\"p1_pos\":0,\"p2_pos\":0,\"dice\":1,\"chess_board\":\"rnbqkbnr\\/pppppppp\\/8\\/8\\/8\\/8\\/PPPPPPPP\\/RNBQKBNR\"}', 1, '2026-08-17 12:16:43'),
(2, '7184', 'chess', 1, 2, 10.00, 15.00, 5.00, NULL, 'in_progress', '', '', '{\"p1_pos\":0,\"p2_pos\":0,\"dice\":1,\"chess_board\":\"rnbqkbnr\\/pppppppp\\/8\\/8\\/8\\/8\\/PPPPPPPP\\/RNBQKBNR\"}', 1, '2026-08-17 12:17:14'),
(3, '9850', 'ludo', 2, 1, 10.00, 15.00, 5.00, 1, 'completed', '', '', '{\"p1_pos\":23,\"p2_pos\":25,\"dice\":5,\"chess_board\":\"rnbqkbnr\\/pppppppp\\/8\\/8\\/8\\/8\\/PPPPPPPP\\/RNBQKBNR\"}', 1, '2026-08-17 12:18:46'),
(4, '1462', 'qa_challenge', 1, NULL, 10.00, 15.00, 5.00, NULL, 'waiting', 'raja ke beta ke raja kon hi ?', 'Pita', '{\"p1_pos\":0,\"p2_pos\":0,\"dice\":1,\"chess_board\":\"rnbqkbnr\\/pppppppp\\/8\\/8\\/8\\/8\\/PPPPPPPP\\/RNBQKBNR\"}', 1, '2026-08-18 06:36:23');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_questions`
--

CREATE TABLE `quiz_questions` (
  `id` int(11) NOT NULL,
  `question` text NOT NULL,
  `option_a` varchar(255) NOT NULL,
  `option_b` varchar(255) NOT NULL,
  `option_c` varchar(255) NOT NULL,
  `option_d` varchar(255) NOT NULL,
  `correct_option` char(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quiz_questions`
--

INSERT INTO `quiz_questions` (`id`, `question`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_option`) VALUES
(1, 'Bharat ki rajdhani kya hai?', 'Mumbai', 'New Delhi', 'Kolkata', 'Chennai', 'B'),
(2, 'Ludo game me kitne rang hote hain?', '2', '3', '4', '6', 'C'),
(3, 'Chess board me kul kitne squares hote hain?', '48', '64', '72', '81', 'B'),
(4, 'Internet ka aavishkar kisne kiya tha?', 'Tim Berners-Lee', 'Vint Cerf', 'Bill Gates', 'Alan Turing', 'B'),
(5, 'Cricket me kitne khiladi hote hain ek team me?', '10', '11', '12', '9', 'B');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `type` enum('signup_bonus','deposit','withdrawal','match_fee','match_win','platform_commission') NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `user_id`, `amount`, `type`, `description`, `created_at`) VALUES
(1, 1, 50.00, 'signup_bonus', 'Signup Bonus', '2026-08-17 12:07:11'),
(2, 1, -10.00, 'match_fee', 'Match Entry Fee', '2026-08-17 12:07:23'),
(3, 1, 15.00, 'match_win', 'Match Win Prize (Pool ₹20 - ₹5 Commission)', '2026-08-17 12:07:54'),
(4, 1, -10.00, 'match_fee', 'Match Entry Fee', '2026-08-17 12:08:08'),
(5, 1, 15.00, 'match_win', 'Match Win Prize (Pool ₹20 - ₹5 Commission)', '2026-08-17 12:08:24'),
(6, 1, -10.00, 'match_fee', 'Created Room #5068', '2026-08-17 12:16:43'),
(7, 1, -10.00, 'match_fee', 'Created Room #7184', '2026-08-17 12:17:14'),
(8, 2, -10.00, 'match_fee', 'Joined Room #7184', '2026-08-17 12:18:14'),
(9, 2, -10.00, 'match_fee', 'Created Room #9850', '2026-08-17 12:18:46'),
(10, 1, -10.00, 'match_fee', 'Joined Room #9850', '2026-08-17 12:19:02'),
(11, 1, 15.00, 'match_win', 'Won Match #9850', '2026-08-17 12:19:59'),
(12, 1, -10.00, 'match_fee', 'Created Room #1462', '2026-08-18 06:36:23');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `password` varchar(255) NOT NULL,
  `wallet_balance` decimal(10,2) DEFAULT 50.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `phone`, `password`, `wallet_balance`, `created_at`) VALUES
(1, 'Nitesh Bhardwaj', '7897130368', '$2y$10$KdJVj8.Y4OKiyNp0tSWBMuGKbm4As662vR5tF6qfunw/GzSTd1XsG', 35.00, '2026-08-17 12:07:11'),
(2, 'Nitesh Bhardwaj', '9005800999', '$2y$10$szuz4r7ZeVSqrrwabQV9n.bE2P7I1N2RMcc6Ix4n9anBPuYc3rUk2', 80.00, '2026-08-17 12:17:57');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `matches`
--
ALTER TABLE `matches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `room_code` (`room_code`);

--
-- Indexes for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `matches`
--
ALTER TABLE `matches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
