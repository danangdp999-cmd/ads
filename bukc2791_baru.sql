-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Waktu pembuatan: 13 Nov 2025 pada 04.56
-- Versi server: 11.4.8-MariaDB-cll-lve
-- Versi PHP: 8.4.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bukc2791_baru`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `ogo_users`
--

CREATE TABLE `ogo_users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `name` varchar(190) DEFAULT NULL,
  `role` enum('guest','host','admin','super_admin') NOT NULL DEFAULT 'guest',
  `status` enum('active','inactive','suspended','pending') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `ogo_users`
--

INSERT INTO `ogo_users` (`id`, `email`, `password_hash`, `name`, `role`, `status`, `created_at`, `updated_at`, `last_login_at`) VALUES
(1, 'dprayogo21@gmail.com', '$2y$12$7oXZwNTGXMKUf765KzKEme90Juenh1wpYqopGcU5Rti1eOtAcjqny', 'Danang Prayogo', 'guest', 'active', '2025-11-12 01:03:11', NULL, '2025-11-12 20:44:28'),
(2, 'dprayogo212@gmail.com', '$2y$12$bsY5N.haXP.KBrjwAkudPeehtOV/p/Yeuxh7qEW/SKKicHZTDCQQ.', 'OGORooms', 'admin', 'active', '2025-11-12 03:12:35', NULL, NULL),
(3, 'admin@ogorooms.test', '$2y$10$qHG7n0c5P7FgbtqeE8lHdeTgydYLVy4xq5PuiB6C7GBrfXo9WW4wa', 'Super Admin', 'super_admin', 'active', '2025-11-12 20:43:20', NULL, NULL),
(4, 'dprayogo123@gmail.com', '$2y$12$3AKjK1FbKklLQ1PbxGxKCulrnrrZt.W8S.GGYaAflQbRHIihnm3RS', 'OGORooms Super', 'super_admin', 'active', '2025-11-12 21:40:01', NULL, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `simple_listings`
--

CREATE TABLE `simple_listings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `host_id` bigint(20) UNSIGNED DEFAULT NULL,
  `host_user_id` bigint(20) UNSIGNED NOT NULL,
  `host_type` enum('home','experience','service') NOT NULL,
  `title` varchar(190) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `property_type` varchar(100) DEFAULT NULL,
  `room_type` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `address_line1` varchar(190) DEFAULT NULL,
  `bedrooms` int(10) UNSIGNED DEFAULT NULL,
  `bathrooms` int(10) UNSIGNED DEFAULT NULL,
  `nightly_price` decimal(10,2) DEFAULT NULL,
  `nightly_price_strike` decimal(10,2) DEFAULT NULL,
  `weekend_price` decimal(10,2) DEFAULT NULL,
  `weekend_price_strike` decimal(10,2) DEFAULT NULL,
  `has_discount` tinyint(1) NOT NULL DEFAULT 0,
  `discount_label` varchar(50) DEFAULT NULL,
  `status` enum('draft','in_review','published','rejected') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  `rejected_reason` text DEFAULT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `location_city` varchar(120) DEFAULT NULL,
  `location_country` varchar(120) DEFAULT NULL,
  `address_line` varchar(255) DEFAULT NULL,
  `lat` decimal(10,7) DEFAULT NULL,
  `lng` decimal(10,7) DEFAULT NULL,
  `guests` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `currency_code` char(3) NOT NULL DEFAULT 'IDR',
  `strike_through_price` decimal(12,2) DEFAULT NULL,
  `weekend_strike_through_price` decimal(12,2) DEFAULT NULL,
  `cover_photo_url` varchar(500) DEFAULT NULL,
  `headline` varchar(190) DEFAULT NULL,
  `story` text DEFAULT NULL,
  `highlights_json` json DEFAULT NULL,
  `amenities_json` json DEFAULT NULL,
  `house_rules_json` json DEFAULT NULL,
  `custom_rules` text DEFAULT NULL,
  `checkin_window` varchar(60) DEFAULT NULL,
  `checkout_time` varchar(60) DEFAULT NULL,
  `welcome_message` text DEFAULT NULL,
  `cancellation_policy` varchar(60) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `simple_listings`
--

INSERT INTO `simple_listings` (`id`, `host_id`, `host_user_id`, `host_type`, `title`, `description`, `property_type`, `room_type`, `country`, `city`, `address_line1`, `bedrooms`, `bathrooms`, `nightly_price`, `nightly_price_strike`, `weekend_price`, `weekend_price_strike`, `has_discount`, `discount_label`, `status`, `created_at`, `updated_at`, `rejected_reason`, `approved_by`, `approved_at`, `location_city`, `location_country`, `address_line`, `lat`, `lng`, `guests`, `currency_code`, `strike_through_price`, `weekend_strike_through_price`, `cover_photo_url`, `headline`, `story`, `highlights_json`, `amenities_json`, `house_rules_json`, `custom_rules`, `checkin_window`, `checkout_time`, `welcome_message`, `cancellation_policy`) VALUES
(1, NULL, 1, 'home', 'OGORooms Near Jakarta International Stadium (JIS)', NULL, NULL, NULL, 'Indonesia', 'Jakarta', NULL, NULL, NULL, 249999.00, 649999.00, 299999.00, 749999.00, 1, 'Last room deals', 'in_review', '2025-11-12 01:46:46', '2025-11-12 03:07:07', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'IDR', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `amenity_groups`
--

CREATE TABLE `amenity_groups` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(120) NOT NULL,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `amenity_groups` (`id`, `code`, `name`, `sort_order`) VALUES
(1, 'bathroom', 'Bathroom', 10),
(2, 'bedroom_laundry', 'Bedroom & Laundry', 20),
(3, 'entertainment', 'Entertainment', 30),
(4, 'heating_cooling', 'Heating & Cooling', 40),
(5, 'internet_office', 'Internet & Office', 50),
(6, 'kitchen_dining', 'Kitchen & Dining', 60),
(7, 'location_features', 'Location Features', 70),
(8, 'parking_facilities', 'Parking & Facilities', 80),
(9, 'services', 'Services', 90),
(10, 'compliance', 'Compliance & Safety', 100);

-- --------------------------------------------------------

--
-- Struktur dari tabel `amenities`
--

CREATE TABLE `amenities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `group_id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(80) NOT NULL,
  `name` varchar(150) NOT NULL,
  `requires_detail` tinyint(1) NOT NULL DEFAULT 0,
  `detail_schema` json DEFAULT NULL,
  `affects_search` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `amenities` (`id`, `group_id`, `code`, `name`, `requires_detail`, `detail_schema`, `affects_search`, `sort_order`) VALUES
(1, 1, 'hair_dryer', 'Hair dryer', 0, NULL, 1, 10),
(2, 1, 'shampoo', 'Shampoo', 0, NULL, 1, 20),
(3, 1, 'conditioner', 'Conditioner', 0, NULL, 1, 30),
(4, 1, 'body_soap', 'Body soap', 0, NULL, 1, 40),
(5, 1, 'bidet', 'Bidet', 0, NULL, 1, 50),
(6, 1, 'hot_water', 'Hot water', 0, NULL, 1, 60),
(7, 1, 'shower_gel', 'Shower gel', 0, NULL, 1, 70),
(8, 2, 'essentials', 'Essentials (towels, soap, toilet paper)', 0, NULL, 1, 10),
(9, 2, 'towels', 'Towels', 0, NULL, 1, 20),
(10, 2, 'bed_sheets', 'Bed sheets', 0, NULL, 1, 30),
(11, 2, 'hangers', 'Hangers', 0, NULL, 1, 40),
(12, 2, 'bed_linens', 'Extra bed linens', 0, NULL, 1, 50),
(13, 2, 'room_darkening_shades', 'Room-darkening shades', 0, NULL, 1, 60),
(14, 2, 'iron', 'Iron', 0, NULL, 1, 70),
(15, 2, 'clothing_storage', 'Clothing storage', 0, NULL, 1, 80),
(16, 3, 'tv', 'Television', 0, NULL, 1, 10),
(17, 4, 'air_conditioning', 'Air conditioning', 1, '{"type":"object","required":["type"],"properties":{"type":{"type":"string","enum":["window","split","central"]},"availability_hours":{"type":"string","maxLength":120}}}', 1, 10),
(18, 5, 'wifi', 'Wi-Fi', 1, '{"type":"object","required":["ssid","down_mbps","up_mbps"],"properties":{"ssid":{"type":"string","maxLength":120},"down_mbps":{"type":"number","minimum":1},"up_mbps":{"type":"number","minimum":1}}}', 1, 10),
(19, 5, 'dedicated_workspace', 'Dedicated workspace', 0, NULL, 1, 20),
(20, 6, 'kitchen', 'Kitchen', 0, NULL, 1, 10),
(21, 6, 'cooking_basics', 'Cooking basics', 0, NULL, 1, 20),
(22, 6, 'dishes_silverware', 'Dishes & silverware', 0, NULL, 1, 30),
(23, 6, 'mini_fridge', 'Mini fridge', 0, NULL, 1, 40),
(24, 6, 'freezer', 'Freezer', 0, NULL, 1, 50),
(25, 6, 'stove', 'Stove', 0, NULL, 1, 60),
(26, 6, 'coffee_maker_nespresso', 'Nespresso coffee maker', 0, '{"type":"object","properties":{"model":{"type":"string","maxLength":80}}}', 1, 70),
(27, 6, 'rice_maker', 'Rice maker', 0, NULL, 1, 80),
(28, 6, 'dining_table', 'Dining table', 0, NULL, 1, 90),
(29, 6, 'coffee', 'Complimentary coffee', 0, NULL, 1, 100),
(30, 7, 'laundromat_nearby', 'Laundromat nearby', 0, NULL, 1, 10),
(31, 8, 'free_parking_on_premises', 'Free parking on premises', 1, '{"type":"object","required":["slot_count"],"properties":{"slot_count":{"type":"integer","minimum":1}}}', 1, 10),
(32, 8, 'paid_parking_on_premises', 'Paid parking on premises', 1, '{"type":"object","required":["daily_rate"],"properties":{"daily_rate":{"type":"number","minimum":0}}}', 1, 20),
(33, 8, 'hot_tub', 'Hot tub', 1, '{"type":"object","required":["type"],"properties":{"type":{"type":"string","enum":["private","shared"]}}}', 1, 30),
(34, 8, 'pool', 'Pool', 1, '{"type":"object","required":["type","heated"],"properties":{"type":{"type":"string","enum":["private","shared"]},"heated":{"type":"boolean"}}}', 1, 40),
(35, 9, 'luggage_dropoff_allowed', 'Luggage drop-off allowed', 0, NULL, 1, 10),
(36, 9, 'breakfast_provided', 'Breakfast provided', 0, NULL, 1, 20),
(37, 9, 'long_term_stays_allowed', 'Long-term stays allowed', 0, NULL, 1, 30),
(38, 9, 'self_check_in', 'Self check-in', 0, NULL, 1, 40),
(39, 9, 'building_staff_24h', '24-hour building staff', 0, NULL, 1, 50),
(40, 9, 'cleaning_available_during_stay', 'Cleaning during stay', 0, NULL, 1, 60),
(41, 10, 'exterior_security_cameras', 'Exterior security cameras', 0, NULL, 0, 10),
(42, 10, 'washer', 'Washer', 0, NULL, 1, 20),
(43, 10, 'dryer', 'Dryer', 0, NULL, 1, 30),
(44, 10, 'smoke_alarm', 'Smoke alarm', 0, NULL, 1, 40),
(45, 10, 'carbon_monoxide_alarm', 'Carbon monoxide alarm', 0, NULL, 1, 50),
(46, 10, 'heating', 'Heating', 0, NULL, 1, 60),
(47, 7, 'balcony', 'Balcony or patio', 0, NULL, 1, 110);

-- --------------------------------------------------------

--
-- Struktur dari tabel `listing_amenities`
--

CREATE TABLE `listing_amenities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `listing_id` bigint(20) UNSIGNED NOT NULL,
  `amenity_id` bigint(20) UNSIGNED NOT NULL,
  `present_state` enum('yes','no','unknown') NOT NULL DEFAULT 'unknown',
  `detail_json` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `listing_media`
--

CREATE TABLE `listing_media` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `listing_id` bigint(20) UNSIGNED NOT NULL,
  `role` enum('cover','gallery') NOT NULL DEFAULT 'gallery',
  `storage_key` varchar(255) NOT NULL,
  `original_url` varchar(500) NOT NULL,
  `width` int(10) UNSIGNED DEFAULT NULL,
  `height` int(10) UNSIGNED DEFAULT NULL,
  `bytes` bigint(20) UNSIGNED DEFAULT NULL,
  `mime` varchar(50) DEFAULT NULL,
  `sha1` char(40) NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `order_index` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `is_valid` tinyint(1) NOT NULL DEFAULT 1,
  `variants_json` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `listing_media_edits`
--

CREATE TABLE `listing_media_edits` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `media_id` bigint(20) UNSIGNED NOT NULL,
  `version` int(10) UNSIGNED NOT NULL,
  `edits_json` json NOT NULL,
  `output_url` varchar(500) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `listing_highlights`
--

CREATE TABLE `listing_highlights` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `listing_id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(80) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `listing_wizard_progress`
--

CREATE TABLE `listing_wizard_progress` (
  `listing_id` bigint(20) UNSIGNED NOT NULL,
  `current_step` enum('1','2','3') NOT NULL DEFAULT '1',
  `completed_sections` json DEFAULT NULL,
  `last_saved_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `ogo_users`
--
ALTER TABLE `ogo_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indeks untuk tabel `simple_listings`
--
ALTER TABLE `simple_listings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_host_user` (`host_user_id`),
  ADD KEY `idx_simple_listings_status` (`status`),
  ADD KEY `idx_simple_listings_host` (`host_id`),
  ADD KEY `idx_simple_listings_approved` (`approved_at`);

--
-- Indeks untuk tabel `amenity_groups`
--
ALTER TABLE `amenity_groups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_amenity_groups_code` (`code`);

--
-- Indeks untuk tabel `amenities`
--
ALTER TABLE `amenities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_amenities_code` (`code`),
  ADD KEY `idx_amenities_group` (`group_id`);

--
-- Indeks untuk tabel `listing_amenities`
--
ALTER TABLE `listing_amenities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_listing_amenity` (`listing_id`,`amenity_id`),
  ADD KEY `idx_listing_amenities_listing` (`listing_id`),
  ADD KEY `idx_listing_amenities_amenity` (`amenity_id`);

--
-- Indeks untuk tabel `listing_media`
--
ALTER TABLE `listing_media`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_listing_media_sha1` (`sha1`),
  ADD KEY `idx_listing_media_listing` (`listing_id`),
  ADD KEY `idx_listing_media_role` (`role`);

--
-- Indeks untuk tabel `listing_media_edits`
--
ALTER TABLE `listing_media_edits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_media_version` (`media_id`,`version`),
  ADD KEY `idx_listing_media_edits_media` (`media_id`);

--
-- Indeks untuk tabel `listing_highlights`
--
ALTER TABLE `listing_highlights`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_listing_highlight` (`listing_id`,`code`);

--
-- Indeks untuk tabel `listing_wizard_progress`
--
ALTER TABLE `listing_wizard_progress`
  ADD PRIMARY KEY (`listing_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `ogo_users`
--
ALTER TABLE `ogo_users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `simple_listings`
--
ALTER TABLE `simple_listings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `amenity_groups`
--
ALTER TABLE `amenity_groups`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `amenities`
--
ALTER TABLE `amenities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT untuk tabel `listing_amenities`
--
ALTER TABLE `listing_amenities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `listing_media`
--
ALTER TABLE `listing_media`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `listing_media_edits`
--
ALTER TABLE `listing_media_edits`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `listing_highlights`
--
ALTER TABLE `listing_highlights`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `simple_listings`
--
ALTER TABLE `simple_listings`
  ADD CONSTRAINT `fk_simple_listings_host` FOREIGN KEY (`host_id`) REFERENCES `ogo_users` (`id`) ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `amenities`
--
ALTER TABLE `amenities`
  ADD CONSTRAINT `fk_amenities_group` FOREIGN KEY (`group_id`) REFERENCES `amenity_groups` (`id`) ON UPDATE CASCADE ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `listing_amenities`
--
ALTER TABLE `listing_amenities`
  ADD CONSTRAINT `fk_listing_amenities_listing` FOREIGN KEY (`listing_id`) REFERENCES `simple_listings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_listing_amenities_amenity` FOREIGN KEY (`amenity_id`) REFERENCES `amenities` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `listing_media`
--
ALTER TABLE `listing_media`
  ADD CONSTRAINT `fk_listing_media_listing` FOREIGN KEY (`listing_id`) REFERENCES `simple_listings` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `listing_media_edits`
--
ALTER TABLE `listing_media_edits`
  ADD CONSTRAINT `fk_listing_media_edits_media` FOREIGN KEY (`media_id`) REFERENCES `listing_media` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `listing_highlights`
--
ALTER TABLE `listing_highlights`
  ADD CONSTRAINT `fk_listing_highlights_listing` FOREIGN KEY (`listing_id`) REFERENCES `simple_listings` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `listing_wizard_progress`
--
ALTER TABLE `listing_wizard_progress`
  ADD CONSTRAINT `fk_listing_wizard_progress_listing` FOREIGN KEY (`listing_id`) REFERENCES `simple_listings` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
