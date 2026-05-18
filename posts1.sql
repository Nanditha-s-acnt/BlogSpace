-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 18, 2026 at 07:25 PM
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
-- Database: `posts1`
--

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `post_id`, `user_id`, `comment_text`, `created_at`) VALUES
(1, 1, 2, 'hello there\r\n', '2026-05-10 20:32:59'),
(2, 2, 2, 'we\'ll never be able to....', '2026-05-10 20:37:53'),
(3, 4, 3, 'we\'ll never be able to ig...\r\n', '2026-05-10 20:42:35'),
(4, 10, 4, 'just genz things wt say', '2026-05-11 19:19:39'),
(5, 10, 4, 'yeah just genz thing they say', '2026-05-11 19:19:59');

-- --------------------------------------------------------

--
-- Table structure for table `likes`
--

CREATE TABLE `likes` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `likes`
--

INSERT INTO `likes` (`id`, `post_id`, `user_id`) VALUES
(1, 1, 1),
(2, 2, 2),
(3, 4, 3),
(4, 12, 4);

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `topic` varchar(100) DEFAULT 'General',
  `status` varchar(20) DEFAULT 'draft',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `post_type` varchar(20) DEFAULT 'text',
  `media_url` text DEFAULT NULL,
  `content_extra` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `title`, `content`, `user_id`, `created_at`, `topic`, `status`, `updated_at`, `post_type`, `media_url`, `content_extra`) VALUES
(1, 'Welcome to BlogSpace', 'This is your first post! Start writing amazing content.', 1, '2026-05-10 17:49:54', 'General', 'draft', '2026-05-11 15:07:14', 'text', NULL, NULL),
(7, 'what is the latest internet trend', '', 3, '2026-05-11 15:22:46', 'Lifestyle', 'draft', '2026-05-11 15:22:46', 'text', NULL, NULL),
(8, 'The latest food trend on Internet', 'p', 3, '2026-05-11 15:30:44', 'Lifestyle', 'draft', '2026-05-11 15:30:44', 'text', NULL, NULL),
(9, 'The latest food trend on Internet', 'people are rushing to the places that were non existent ', 3, '2026-05-11 15:31:14', 'Lifestyle', 'draft', '2026-05-11 15:31:14', 'text', NULL, NULL),
(10, 'The latest trend on Internet', 'people are rushing to the places that were non existent for over the years just because of the popularity of the place in internet', 3, '2026-05-11 15:31:42', 'Lifestyle', 'published', '2026-05-11 15:42:59', 'text', NULL, NULL),
(12, 'The Engineering Sem', 'every sem feels like i\'m cooked in this one', 4, '2026-05-12 00:40:34', 'Lifestyle', 'published', '2026-05-12 00:40:34', 'text', '', ''),
(13, 'sammy..', 'this gotta be frustrating', 3, '2026-05-12 01:49:50', 'Tech', 'draft', '2026-05-12 01:49:50', 'text', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `saved_posts`
--

CREATE TABLE `saved_posts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `saved_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `saved_posts`
--

INSERT INTO `saved_posts` (`id`, `user_id`, `post_id`, `saved_at`) VALUES
(1, 3, 10, '2026-05-11 17:49:28'),
(2, 3, 10, '2026-05-11 17:54:29'),
(3, 3, 10, '2026-05-11 18:04:43');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `display_name` varchar(100) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `theme` varchar(30) DEFAULT 'default',
  `profile_pic` varchar(255) DEFAULT 'default.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `display_name`, `bio`, `created_at`, `theme`, `profile_pic`) VALUES
(1, 'admin', 'admin@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', NULL, '2026-05-10 17:49:41', 'default', 'default.png'),
(3, 'Siri', 'nandhunanditha198@gmail.com', '$2y$10$eIXAnnpb5SPRiVlniKWilOC.f7VFvr/Ds0hQdTTo7tUTykAZXTi2y', NULL, 'SIRI_SPEAKS', '2026-05-10 20:40:57', 'ocean', 'default.png'),
(4, 'Preethi', 'preeethi98@gmail.com', '$2y$10$x937rOmypvbUJkCk7HegPOENsaOXJU.myiRrXJ8zus9ID84UkKDyK', NULL, 'PREETHI_SPEAKS', '2026-05-11 19:08:19', 'default', 'default.png');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `likes`
--
ALTER TABLE `likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_like` (`post_id`,`user_id`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `saved_posts`
--
ALTER TABLE `saved_posts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `likes`
--
ALTER TABLE `likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `saved_posts`
--
ALTER TABLE `saved_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
