-- Adminer 5.3.0 MariaDB 5.5.5-10.11.14-MariaDB-0+deb12u2 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `ij_arret`;
CREATE TABLE `ij_arret` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `adherent_number` varchar(7) NOT NULL,
  `code_pathologie` varchar(2) NOT NULL,
  `num_sinistre` int(11) NOT NULL,
  `date_start` date DEFAULT NULL,
  `date_end` date DEFAULT NULL,
  `date_prolongation` date DEFAULT NULL,
  `first_day` tinyint(4) DEFAULT NULL,
  `date_declaration` date DEFAULT NULL,
  `DT_excused` tinyint(4) DEFAULT NULL,
  `valid_med_controleur` tinyint(4) DEFAULT NULL,
  `cco_a_jour` tinyint(4) DEFAULT NULL,
  `date_dern_attestation` date DEFAULT NULL,
  `date_deb_droit` date DEFAULT NULL,
  `date_deb_dr_force` date DEFAULT NULL,
  `taux` float DEFAULT NULL,
  `NOARRET` int(4) DEFAULT NULL COMMENT 'Numero Arret Mainframe',
  `version` tinyint(4) NOT NULL DEFAULT 1,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `adherent_number` (`adherent_number`),
  KEY `ij_arret_fk_1` (`code_pathologie`),
  KEY `num_sinistre` (`num_sinistre`),
  CONSTRAINT `ij_arret_fk_pathologie` FOREIGN KEY (`code_pathologie`) REFERENCES `pathologie` (`code_pathologie`),
  CONSTRAINT `ij_arret_ibfk_1` FOREIGN KEY (`num_sinistre`) REFERENCES `ij_sinistre` (`id`),
  CONSTRAINT `ij_arret_ibfk_3` FOREIGN KEY (`adherent_number`) REFERENCES `adherent_infos` (`adherent_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- 2025-11-18 06:59:48 UTC