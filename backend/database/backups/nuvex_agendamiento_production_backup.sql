-- ========================================================================
-- NUVEX TECNOLOGÍA — RESPALDO COMPLETO DE BASE DE DATOS PARA PRODUCCIÓN
-- Generado: 2026-11-23T21:58:56-05:00
-- Base de datos: nuvex_agendamiento_paola
-- ========================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- --------------------------------------------------------
-- Estructura de tabla para `appointments`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `appointments`;
CREATE TABLE `appointments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned DEFAULT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `appointment_number` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `service_id` bigint(20) unsigned NOT NULL,
  `client_name` varchar(255) NOT NULL,
  `client_phone` varchar(255) NOT NULL,
  `client_email` varchar(255) DEFAULT NULL,
  `appointment_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'PENDING_DEPOSIT',
  `payment_method` varchar(255) DEFAULT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `deposit_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `deposit_paid` decimal(12,2) NOT NULL DEFAULT 0.00,
  `balance_due` decimal(12,2) NOT NULL DEFAULT 0.00,
  `deposit_proof_image` varchar(255) DEFAULT NULL,
  `verification_notes` text DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `reminder_24h_sent_at` timestamp NULL DEFAULT NULL,
  `attendance_confirmed_at` timestamp NULL DEFAULT NULL,
  `payment_gateway_reference` varchar(255) DEFAULT NULL,
  `payment_gateway_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payment_gateway_payload`)),
  `cancellation_reason` varchar(255) DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `client_notes` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `appointments_appointment_number_unique` (`appointment_number`),
  KEY `appointments_user_id_foreign` (`user_id`),
  KEY `appointments_service_id_foreign` (`service_id`),
  KEY `appointments_appointment_date_start_time_end_time_index` (`appointment_date`,`start_time`,`end_time`),
  KEY `appointments_status_index` (`status`),
  KEY `appointments_client_phone_index` (`client_phone`),
  KEY `appointments_tenant_id_foreign` (`tenant_id`),
  KEY `idx_appointments_reminder_check` (`status`,`appointment_date`,`reminder_24h_sent_at`),
  KEY `appointments_customer_id_foreign` (`customer_id`),
  CONSTRAINT `appointments_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `appointments_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos para la tabla `appointments` (4 registros)
INSERT INTO `appointments` VALUES (1, 1, 67, 'PA-261013-MSO7', NULL, 2, 'Vivian Mohnblatt', '3106080402', NULL, '2026-10-14', '08:00:00', '08:50:00', 'COMPLETED', 'CASH_AT_LOCATION', '55000.00', '0.00', '0.00', '55000.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-13 11:47:25', '2026-10-17 21:47:16'),
(2, 1, 68, 'PA-261123-39B6', NULL, 9, 'Vivian Mohnblatt', '3106080402', NULL, '2026-11-24', '09:00:00', '09:05:00', 'CONFIRMED', 'CASH_AT_LOCATION', '20000.00', '0.00', '0.00', '20000.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-23 16:31:22', '2026-11-23 16:31:22'),
(3, 1, 68, 'PA-261123-2ABC', NULL, 8, 'Vivian Mohnblatt', '3106080402', NULL, '2026-11-24', '09:05:00', '09:10:00', 'CONFIRMED', 'CASH_AT_LOCATION', '5000.00', '0.00', '0.00', '5000.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-23 16:32:14', '2026-11-23 16:32:14'),
(4, 1, 68, 'PA-261123-4770', NULL, 1, 'Vivian Mohnblatt', '3106080402', NULL, '2026-11-24', '08:00:00', '08:50:00', 'CONFIRMED', 'CASH_AT_LOCATION', '60000.00', '50000.00', '0.00', '60000.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-23 16:46:56', '2026-11-23 16:46:56');

-- --------------------------------------------------------
-- Estructura de tabla para `blocked_slots`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `blocked_slots`;
CREATE TABLE `blocked_slots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL DEFAULT 'Almuerzo / Descanso',
  `blocked_date` date DEFAULT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_recurring` tinyint(1) NOT NULL DEFAULT 0,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `blocked_slots_blocked_date_start_time_end_time_index` (`blocked_date`,`start_time`,`end_time`),
  KEY `blocked_slots_tenant_id_foreign` (`tenant_id`),
  CONSTRAINT `blocked_slots_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos para la tabla `blocked_slots` (1 registros)
INSERT INTO `blocked_slots` VALUES (1, 1, 'Hora de Almuerzo & Descanso', NULL, '13:00:00', '14:00:00', 1, 'Horario habitual de almuerzo', '2026-09-17 20:44:59', '2026-09-17 20:44:59');

-- --------------------------------------------------------
-- Estructura de tabla para `cache`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos para la tabla `cache` (8 registros)
INSERT INTO `cache` VALUES ('07782b16c0ca289a009903817fcb0fe1', 'i:6;', 1795489058),
('07782b16c0ca289a009903817fcb0fe1:timer', 'i:1795489058;', 1795489058),
('filament-edit-profile:1', 'i:1;', 1795454765),
('filament-edit-profile:1:timer', 'i:1795454765;', 1795454765),
('livewire-rate-limiter:16d36dff9abd246c67dfac3e63b993a169af77e6', 'i:1;', 1795467511),
('livewire-rate-limiter:16d36dff9abd246c67dfac3e63b993a169af77e6:timer', 'i:1795467511;', 1795467511),
('livewire-rate-limiter:48cfdd9a9499a4d6be7cfa3ad6063ac471ca1cc7', 'i:1;', 1795454765),
('livewire-rate-limiter:48cfdd9a9499a4d6be7cfa3ad6063ac471ca1cc7:timer', 'i:1795454765;', 1795454765);

-- --------------------------------------------------------
-- Estructura de tabla para `cache_locks`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Estructura de tabla para `customers`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `is_legacy` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `registered_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customers_tenant_id_phone_index` (`tenant_id`,`phone`),
  KEY `customers_phone_index` (`phone`),
  KEY `customers_is_legacy_index` (`is_legacy`),
  CONSTRAINT `customers_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=69 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos para la tabla `customers` (68 registros)
INSERT INTO `customers` VALUES (1, 1, 'Alexandra Bueno', '573112512847', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(2, 1, 'Alexandra Toro', '573154330358', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(3, 1, 'Ana Beliza Mercado', '573174041848', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(4, 1, 'Ana Maria Alvarado', '573182804631', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(5, 1, 'Ana Maria Guzman', '573165384243', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(6, 1, 'Ana Maria Montoya', '573178951604', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(7, 1, 'Andrea Buitrago', '573112539037', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(8, 1, 'Andrea Cortes', '573244235274', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(9, 1, 'Andrea Marmolejo', '573183365300', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(10, 1, 'Andrea Morales', '525580617221', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(11, 1, 'Angie Moran', '573125167410', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(12, 1, 'Blanca Castro', '573225278098', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(13, 1, 'Carolina Arroyo', '573008743308', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(14, 1, 'Catalina Cardona', '573106570070', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(15, 1, 'Catalina Quiroz', '573213432171', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(16, 1, 'Catalina Sanabria', '573134969288', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(17, 1, 'Catherien Siachoque', '17863559136', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(18, 1, 'Catherine Espinosa', '573123796059', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(19, 1, 'Catherine Moggio', '573134338239', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(20, 1, 'Daina Garcia', '573138707744', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(21, 1, 'Daniela Otalora', '573112782451', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(22, 1, 'Daniela Vidal', '573113530750', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(23, 1, 'Danna Adame', '573104834194', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(24, 1, 'Diana Zambrano', '573174241667', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(25, 1, 'Elisa Beltran', '573176366446', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(26, 1, 'Elisa Santamaria', '573176366465', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(27, 1, 'Elizabeth Proaños', '573212103501', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(28, 1, 'Ellen Mendivelso', '573175105664', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(29, 1, 'Estefania Corredor', '573102529704', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(30, 1, 'Helena Fadul', '573103956228', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(31, 1, 'Isabella Yepes', '573144136052', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(32, 1, 'Jennifer Valdes', '573137982400', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(33, 1, 'Jill San Juan', '573118495159', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(34, 1, 'Juan Pablo Socarras', '573108749904', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(35, 1, 'Juliana Castro', '573125170365', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(36, 1, 'Juliana Velasco', '573166938847', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(37, 1, 'Julieht Valdes', '573102088945', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(38, 1, 'Karol Perez', '573003751387', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(39, 1, 'Kelly Porras', '573184872101', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(40, 1, 'Laura Rodriguez', '573103354319', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(41, 1, 'Laurie Haimes', '13057217550', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(42, 1, 'Leonardo Angulo', '573152203258', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(43, 1, 'Lilian Rozo', '573123789644', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(44, 1, 'Liliana Herrera', '573123540399', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(45, 1, 'Lina Pulido', '573112673446', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(46, 1, 'Luisa Morales', '573143342855', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(47, 1, 'Manuela Botero', '573108682511', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(48, 1, 'Maria Claudia Ricalde', '573232249395', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(49, 1, 'Maria Paula Gomez', '573114816518', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(50, 1, 'Maritza Arias', '573057870706', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(51, 1, 'Marlly Moreno', '573229492636', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(52, 1, 'Martha Chavez', '573114737072', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(53, 1, 'Nathalie Grossman', '573156353291', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(54, 1, 'Nathalie Magno', '573188211537', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:23', '2026-10-13 08:39:58'),
(55, 1, 'Nina Rodriguez', '573103216342', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:24', '2026-10-13 08:39:58'),
(56, 1, 'Orian Himelfarb', '573107601021', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:24', '2026-10-13 08:39:58'),
(57, 1, 'Paula Zabal', '573142762255', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:24', '2026-10-13 08:39:58'),
(58, 1, 'Renee Cohen', '573175171971', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:24', '2026-10-13 08:39:58'),
(59, 1, 'Sofia Reyes', '573103214183', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:24', '2026-10-13 08:39:58'),
(60, 1, 'Stephania Bradford', '17868326477', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:24', '2026-10-13 08:39:58'),
(61, 1, 'Stephania Mattos', '573158602270', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:24', '2026-10-13 08:39:58'),
(62, 1, 'Tatiana De Los Rios', '573137918685', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:24', '2026-10-13 08:39:58'),
(63, 1, 'Thalia Birmaher', '573128631842', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:24', '2026-10-13 08:39:58'),
(64, 1, 'Valentina Castañeda', '573124814272', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:24', '2026-10-13 08:39:58'),
(65, 1, 'Victoria Btesh', '16174802175', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:24', '2026-10-13 08:39:58'),
(66, 1, 'Victoria Holguin', '573164131913', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:24', '2026-10-13 08:39:58'),
(67, 1, 'Vivian Mohnblatt', '573138537263', NULL, 1, 'Clienta habitual histórica de La Belle Nails Bogotá', '2026-01-01 09:00:00', '2026-10-13 08:10:24', '2026-10-13 08:39:58'),
(68, 1, 'Vivian Mohnblatt', '3106080402', NULL, 0, NULL, '2026-10-14 00:00:00', '2026-11-20 19:46:38', '2026-11-20 19:46:38');

-- --------------------------------------------------------
-- Estructura de tabla para `failed_jobs`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Estructura de tabla para `job_batches`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Estructura de tabla para `jobs`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Estructura de tabla para `migrations`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos para la tabla `migrations` (17 registros)
INSERT INTO `migrations` VALUES (1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_09_08_192819_create_personal_access_tokens_table', 1),
(5, '2026_09_13_000001_create_services_table', 1),
(6, '2026_09_13_000002_create_working_schedules_table', 1),
(7, '2026_09_13_000003_create_blocked_slots_table', 1),
(8, '2026_09_13_000004_create_appointments_table', 1),
(9, '2026_09_13_000005_create_waitlists_table', 1),
(10, '2026_09_17_000001_create_tenants_table', 1),
(11, '2026_09_17_000002_add_tenant_id_to_all_tables', 1),
(12, '2026_10_07_174144_add_branding_fields_to_tenants_table', 2),
(13, '2026_10_08_005635_add_reminder_24h_sent_at_to_appointments_table', 3),
(14, '2026_10_08_015712_add_attendance_confirmed_at_to_appointments_table', 4),
(15, '2026_10_12_102626_create_customers_table_and_add_customer_id_to_appointments', 5),
(16, '2026_10_13_083716_add_is_legacy_to_customers_table', 6),
(17, '2026_10_16_010000_add_meta_whatsapp_fields_to_tenants_table', 7);

-- --------------------------------------------------------
-- Estructura de tabla para `password_reset_tokens`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Estructura de tabla para `personal_access_tokens`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `personal_access_tokens`;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos para la tabla `personal_access_tokens` (3 registros)
INSERT INTO `personal_access_tokens` VALUES (2, 'App\\Models\\User', 2, 'admin-spa', '693483b768ecb90c81c0ffe84f6568380efef3594b351fbf429e46b0445f2f13', '[\"ADMIN\"]', NULL, NULL, '2026-11-23 21:52:48', '2026-11-23 21:52:48'),
(3, 'App\\Models\\User', 5, 'admin-spa', '5100e862f4d3338ab52aa69d2bcdc616ecd8a5898cffa1bf15f1943a3a0dc5e0', '[\"SUPER_ADMIN\"]', NULL, NULL, '2026-11-23 21:53:01', '2026-11-23 21:53:01'),
(4, 'App\\Models\\User', 2, 'admin-spa', 'ac8d86d20065e35517a78f7f918b17268f5657b38633ae9ea91802878d4cdfb9', '[\"ADMIN\"]', '2026-11-23 21:54:42', NULL, '2026-11-23 21:54:24', '2026-11-23 21:54:42');

-- --------------------------------------------------------
-- Estructura de tabla para `services`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `services`;
CREATE TABLE `services` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL DEFAULT 'FACIAL',
  `description` text DEFAULT NULL,
  `duration_minutes` int(11) NOT NULL DEFAULT 60,
  `base_price` decimal(12,2) NOT NULL,
  `deposit_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `image_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `services_tenant_slug_unique` (`tenant_id`,`slug`),
  KEY `services_category_index` (`category`),
  KEY `services_is_active_index` (`is_active`),
  CONSTRAINT `services_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos para la tabla `services` (11 registros)
INSERT INTO `services` VALUES (1, 1, 'Limpieza Manicura Rusa', 'limpieza-manicura-rusa', 'MANICURA', 'Técnica en seco de alta precisión para cutículas limpias y acabado pulido impecable.', 50, '60000.00', '50000.00', 'https://images.unsplash.com/photo-1604654894610-df63bc536371?auto=format&fit=crop&w=800&q=80', 1, 1, '2026-10-12 17:57:01', '2026-10-12 18:42:57'),
(2, 1, 'Limpieza Pedicura', 'limpieza-pedicura', 'PEDICURA', 'Limpieza profunda, exfoliación suave y cuidado integral para pies descansados y uñas saludables.', 50, '55000.00', '50000.00', 'https://images.unsplash.com/photo-1519415510236-718bdfcd89c8?auto=format&fit=crop&w=800&q=80', 1, 2, '2026-10-12 17:57:01', '2026-10-12 18:25:33'),
(3, 1, 'Pedicura Semipermanente', 'pedicura-semipermanente', 'PEDICURA', 'Pedicura completa con esmaltado semipermanente de máxima duración y secado inmediato en lámpara.', 60, '85000.00', '50000.00', 'https://images.unsplash.com/photo-1664643411326-6c589531be3c?auto=format&fit=crop&w=800&q=80', 1, 3, '2026-10-12 17:57:01', '2026-10-12 18:42:57'),
(4, 1, 'Aplicación Semi', 'aplicacion-semi', 'MANICURA', 'Esmaltado semipermanente profesional en manos con brillo espejo de larga duración.', 30, '55000.00', '50000.00', 'https://images.unsplash.com/photo-1519014816548-bf5fe059798b?auto=format&fit=crop&w=800&q=80', 1, 4, '2026-10-12 17:57:01', '2026-10-12 18:25:33'),
(5, 1, 'Aplicación Rubber', 'aplicacion-rubber', 'MANICURA', 'Base Rubber niveladora y reforzadora para aportar resistencia, volumen y flexibilidad a la uña natural.', 30, '60000.00', '50000.00', 'https://images.unsplash.com/photo-1588850561407-ed78c282e89b?auto=format&fit=crop&w=800&q=80', 1, 5, '2026-10-12 17:57:01', '2026-10-12 18:42:57'),
(6, 1, 'Remoción Semi', 'remocion-semi', 'REPARACION_RETIRO', 'Retiro profesional y seguro de esmaltado semipermanente sin desgastar la lámina ungueal.', 15, '16000.00', '0.00', 'https://images.unsplash.com/photo-1632345031435-8727f6897d53?auto=format&fit=crop&w=800&q=80', 1, 6, '2026-10-12 17:57:01', '2026-10-12 18:42:57'),
(7, 1, 'Pedicura Spa', 'pedicura-spa', 'PEDICURA', 'Experiencia relajante con exfoliación profunda, mascarilla nutritiva, masaje podal y esmaltado.', 60, '65000.00', '50000.00', 'https://images.unsplash.com/photo-1540555700478-4be289fbecef?auto=format&fit=crop&w=800&q=80', 1, 7, '2026-10-12 17:57:01', '2026-10-12 18:25:33'),
(8, 1, 'Remiendo', 'remiendo', 'REPARACION_RETIRO', 'Reparación exprés de uña rota, fisurada o debilitada para restaurar su estructura.', 5, '5000.00', '0.00', 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=800&q=80', 1, 8, '2026-10-12 17:57:01', '2026-10-12 18:42:57'),
(9, 1, 'Uña Individual Polygel', 'una-individual-polygel', 'SISTEMAS_ARTIFICIALES', 'Reconstrucción o extensión de una uña individual mediante técnica esculpida en Polygel.', 5, '20000.00', '0.00', 'https://images.unsplash.com/photo-1596704017254-9b121068fb31?auto=format&fit=crop&w=800&q=80', 1, 9, '2026-10-12 17:57:01', '2026-10-12 18:25:33'),
(10, 1, 'Uñas Polygel', 'unas-polygel', 'SISTEMAS_ARTIFICIALES', 'Set completo esculpido en Polygel: combinación perfecta de ligereza, resistencia superior y elegancia.', 120, '210000.00', '50000.00', 'https://images.unsplash.com/photo-1601244668565-afba8cbd2b51?auto=format&fit=crop&w=800&q=80', 1, 10, '2026-10-12 17:57:01', '2026-10-12 18:25:33'),
(11, 1, 'Mantenimiento Polygel', 'mantenimiento-polygel', 'SISTEMAS_ARTIFICIALES', 'Retoque y nivelación del crecimiento en uñas de Polygel para prolongar su durabilidad y aspecto nuevo.', 90, '155000.00', '50000.00', 'https://images.unsplash.com/photo-1607779097040-26e80aa78e66?auto=format&fit=crop&w=800&q=80', 1, 11, '2026-10-12 17:57:01', '2026-10-12 18:42:57');

-- --------------------------------------------------------
-- Estructura de tabla para `sessions`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos para la tabla `sessions` (1 registros)
INSERT INTO `sessions` VALUES ('wglx2EfibMI7CTy4NyFjD8haCUSxgstl5UFQqUoE', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', 'YTo4OntzOjY6Il90b2tlbiI7czo0MDoiY0pIcHNseHVIemZ1b2FDZkYxTG1ZRTBOc1VjUlN6cU1pOTk1VmdhSCI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjU1OiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvYWRtaW4vcGFvbGEtYWd1aWxlcmEvYXBwb2ludG1lbnRzIjtzOjU6InJvdXRlIjtzOjQzOiJmaWxhbWVudC5hZG1pbi5yZXNvdXJjZXMuYXBwb2ludG1lbnRzLmluZGV4Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MjtzOjE3OiJwYXNzd29yZF9oYXNoX3dlYiI7czo2NDoiOTdiMGFhMWZmNzU5ZjIxNThlZjExZDViMGFkZmVhNzI1YjRmZTZmZTBkZGEwYTJmNmE2ODUwMzkxYTIxZmUyMCI7czo4OiJmaWxhbWVudCI7YTowOnt9czo2OiJ0YWJsZXMiO2E6MTp7czo0MDoiNzEwNjJjYzQ3YjVlMDY1MTM2NjVmZjdkZTQzNzE1ZDJfY29sdW1ucyI7YTo1OntpOjA7YTo3OntzOjQ6InR5cGUiO3M6NjoiY29sdW1uIjtzOjQ6Im5hbWUiO3M6MTE6ImNsaWVudF9uYW1lIjtzOjU6ImxhYmVsIjtzOjc6IkNsaWVudGEiO3M6ODoiaXNIaWRkZW4iO2I6MDtzOjk6ImlzVG9nZ2xlZCI7YjoxO3M6MTI6ImlzVG9nZ2xlYWJsZSI7YjowO3M6MjQ6ImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI7Tjt9aToxO2E6Nzp7czo0OiJ0eXBlIjtzOjY6ImNvbHVtbiI7czo0OiJuYW1lIjtzOjEyOiJzZXJ2aWNlLm5hbWUiO3M6NToibGFiZWwiO3M6ODoiU2VydmljaW8iO3M6ODoiaXNIaWRkZW4iO2I6MDtzOjk6ImlzVG9nZ2xlZCI7YjoxO3M6MTI6ImlzVG9nZ2xlYWJsZSI7YjowO3M6MjQ6ImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI7Tjt9aToyO2E6Nzp7czo0OiJ0eXBlIjtzOjY6ImNvbHVtbiI7czo0OiJuYW1lIjtzOjE2OiJhcHBvaW50bWVudF9kYXRlIjtzOjU6ImxhYmVsIjtzOjE2OiJGZWNoYSAmYW1wOyBIb3JhIjtzOjg6ImlzSGlkZGVuIjtiOjA7czo5OiJpc1RvZ2dsZWQiO2I6MTtzOjEyOiJpc1RvZ2dsZWFibGUiO2I6MDtzOjI0OiJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiO047fWk6MzthOjc6e3M6NDoidHlwZSI7czo2OiJjb2x1bW4iO3M6NDoibmFtZSI7czo2OiJzdGF0dXMiO3M6NToibGFiZWwiO3M6NjoiRXN0YWRvIjtzOjg6ImlzSGlkZGVuIjtiOjA7czo5OiJpc1RvZ2dsZWQiO2I6MTtzOjEyOiJpc1RvZ2dsZWFibGUiO2I6MDtzOjI0OiJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiO047fWk6NDthOjc6e3M6NDoidHlwZSI7czo2OiJjb2x1bW4iO3M6NDoibmFtZSI7czoxMjoiZGVwb3NpdF9wYWlkIjtzOjU6ImxhYmVsIjtzOjE0OiJBYm9ubyBSZWNpYmlkbyI7czo4OiJpc0hpZGRlbiI7YjowO3M6OToiaXNUb2dnbGVkIjtiOjE7czoxMjoiaXNUb2dnbGVhYmxlIjtiOjA7czoyNDoiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjtOO319fX0=', 1795472184);

-- --------------------------------------------------------
-- Estructura de tabla para `tenants`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `tenants`;
CREATE TABLE `tenants` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `short_name` varchar(255) DEFAULT NULL,
  `business_type` varchar(255) DEFAULT 'ESTUDIO DE BELLEZA',
  `slug` varchar(255) NOT NULL,
  `domain` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `whatsapp_number` varchar(255) DEFAULT NULL,
  `whatsapp_driver` varchar(30) DEFAULT NULL,
  `meta_phone_number_id` varchar(100) DEFAULT NULL,
  `meta_waba_id` varchar(100) DEFAULT NULL,
  `meta_access_token` text DEFAULT NULL,
  `meta_verify_token` varchar(150) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(255) NOT NULL DEFAULT 'Bogotá',
  `specialties` varchar(255) DEFAULT 'Pestañas · Cejas · Faciales · Micropigmentación',
  `schedule_summary` varchar(255) DEFAULT 'Lunes a Sábado 8:00 AM - 6:00 PM | Almuerzo 1:00 PM - 2:00 PM',
  `logo_path` varchar(255) DEFAULT NULL,
  `primary_color` varchar(255) NOT NULL DEFAULT '#0d9488',
  `nequi_phone` varchar(255) DEFAULT NULL,
  `nequi_account_holder` varchar(255) DEFAULT NULL,
  `nequi_account_type` varchar(255) NOT NULL DEFAULT 'Personal',
  `nequi_qr_image` varchar(255) DEFAULT NULL,
  `bold_api_key` varchar(255) DEFAULT NULL,
  `bold_secret_key` varchar(255) DEFAULT NULL,
  `subscription_status` varchar(255) NOT NULL DEFAULT 'active',
  `plan_name` varchar(255) NOT NULL DEFAULT 'Pro Salon',
  `max_appointments_per_month` int(11) NOT NULL DEFAULT 500,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenants_slug_unique` (`slug`),
  UNIQUE KEY `tenants_domain_unique` (`domain`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos para la tabla `tenants` (1 registros)
INSERT INTO `tenants` VALUES (1, 'La Belle Nails Bogotá', 'La Belle Nails Bogotá', 'Spa & Estética', 'paola-aguilera', 'paola.salonesgo.com', '3106080402', '3106080402', 'meta', '1095482376978675', '1417147503385316', 'EAAQT4HalqZA0BS4SICPmf4OZB0iGNiRu6AODhvn2xjmNixyoSolyYiSo0H6gN6Vq3NYjBTnf0BQspTm5ICZA1LjeyK7kuLPnz14WuZBcjcaBMWHKwfuCsYZA5eZChHEGkBehE5UAZAQ03eHpKUZAU9t8a3hu2z2siv1KU4xq467EDyIByoe7veg7fOcZCzZBwaFZAKCtAZDZD', 'nuvex_meta_verify_2026', 'wallpapersss@gmail.com', 'Carrera 15 # 93-75, Chico', 'Bogotá, Colombia', 'Pestañas · Cejas · Faciales · Micropigmentación', 'Lunes a Sábado 8:00 AM - 6:00 PM | Almuerzo 1:00 PM - 2:00 PM', NULL, '#0d9488', '3106080402', 'Paola Andrea Aguilera Camacho', 'Ahorros Nequi', NULL, NULL, NULL, 'active', 'SaaS Enterprise Nuvex', 1000, 1, '2026-09-17 20:44:58', '2026-11-03 20:26:22');

-- --------------------------------------------------------
-- Estructura de tabla para `users`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'CLIENT',
  `phone` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_tenant_id_foreign` (`tenant_id`),
  CONSTRAINT `users_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos para la tabla `users` (4 registros)
INSERT INTO `users` VALUES (1, NULL, 'Super Admin Nuvex', 'admin@nuvex.co', 'SUPER_ADMIN', '3106080402', 1, NULL, '$2y$12$2lETbzEranuKI2.hWAQw2et0nK7Ely6RGqrD7zbiTE.o8HxbtbKIe', NULL, '2026-09-17 20:44:58', '2026-11-23 12:25:05'),
(2, 1, 'Paola Andrea Aguilera Camacho', 'paola@nuvex-belleza.com', 'ADMIN', '3106080402', 1, NULL, '$2y$12$cIdiyFYqhqei14F5Ng2hDewBZU99c.glvzdR9HFgXcTmbJYvuFtDa', NULL, '2026-09-17 20:44:59', '2026-10-08 03:34:01'),
(4, NULL, 'Super Admin Nuvex', 'admin@nuvex-tecnologia.com', 'SUPER_ADMIN', NULL, 1, NULL, '$2y$12$lfwc0QTm6.1nw9rzKQfS7.mxrUoGOx0bMItGGc0XoGsJ3FEpL1WWq', NULL, '2026-10-08 03:35:13', '2026-10-08 03:35:14'),
(5, NULL, 'Super Admin Cita Clave', 'citaclave@gmail.com', 'SUPER_ADMIN', NULL, 1, NULL, '$2y$12$8sjV4D4mwRuNJfX2GEAICuDi5ejeAjZKCzApryss0DKcAOBi4nQAW', NULL, '2026-10-08 07:34:56', '2026-10-08 07:34:56');

-- --------------------------------------------------------
-- Estructura de tabla para `waitlists`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `waitlists`;
CREATE TABLE `waitlists` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned DEFAULT NULL,
  `service_id` bigint(20) unsigned NOT NULL,
  `client_name` varchar(255) NOT NULL,
  `client_phone` varchar(255) NOT NULL,
  `client_email` varchar(255) DEFAULT NULL,
  `requested_date` date NOT NULL,
  `preferred_time_range` varchar(255) NOT NULL DEFAULT 'ANY',
  `status` varchar(255) NOT NULL DEFAULT 'WAITING',
  `notes` text DEFAULT NULL,
  `notified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `waitlists_service_id_foreign` (`service_id`),
  KEY `waitlists_requested_date_status_index` (`requested_date`,`status`),
  KEY `waitlists_tenant_id_foreign` (`tenant_id`),
  CONSTRAINT `waitlists_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE,
  CONSTRAINT `waitlists_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Estructura de tabla para `working_schedules`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `working_schedules`;
CREATE TABLE `working_schedules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned DEFAULT NULL,
  `day_of_week` tinyint(3) unsigned NOT NULL,
  `day_name` varchar(255) NOT NULL,
  `is_working_day` tinyint(1) NOT NULL DEFAULT 1,
  `open_time` time NOT NULL DEFAULT '08:00:00',
  `close_time` time NOT NULL DEFAULT '18:00:00',
  `slot_interval_minutes` int(11) NOT NULL DEFAULT 15,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `working_schedules_tenant_day_unique` (`tenant_id`,`day_of_week`),
  CONSTRAINT `working_schedules_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos para la tabla `working_schedules` (7 registros)
INSERT INTO `working_schedules` VALUES (1, 1, 1, 'Lunes', 1, '08:00:00', '18:00:00', 15, '2026-09-17 20:44:59', '2026-09-17 20:44:59'),
(2, 1, 2, 'Martes', 1, '08:00:00', '18:00:00', 15, '2026-09-17 20:44:59', '2026-09-17 20:44:59'),
(3, 1, 3, 'Miercoles', 1, '08:00:00', '18:00:00', 15, '2026-09-17 20:44:59', '2026-09-17 20:44:59'),
(4, 1, 4, 'Jueves', 1, '08:00:00', '18:00:00', 15, '2026-09-17 20:44:59', '2026-09-17 20:44:59'),
(5, 1, 5, 'Viernes', 1, '08:00:00', '18:00:00', 15, '2026-09-17 20:44:59', '2026-09-17 20:44:59'),
(6, 1, 6, 'Sabado', 1, '08:00:00', '17:00:00', 15, '2026-09-17 20:44:59', '2026-09-17 20:44:59'),
(7, 1, 0, 'Domingo', 0, '09:00:00', '14:00:00', 15, '2026-09-17 20:44:59', '2026-09-17 20:44:59');

SET FOREIGN_KEY_CHECKS = 1;
-- ========================================================================
-- FIN DEL VOLCADO SQL — NUVEX AGENDAMIENTO
-- ========================================================================
