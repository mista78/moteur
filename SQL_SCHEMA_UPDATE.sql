-- SQL Schema Update for ij_arret table
-- Add decompte_days field to track non-paid days before payment starts

-- Option 1: Add column if it doesn't exist (MySQL 5.7+)
ALTER TABLE `ij_arret`
ADD COLUMN IF NOT EXISTS `decompte_days` int(11) DEFAULT 0
COMMENT 'Number of non-paid days before payment starts (décompte period)'
AFTER `first_day`;

-- Option 2: Add column (for older MySQL versions)
-- Check if column exists first, then run:
-- ALTER TABLE `ij_arret`
-- ADD COLUMN `decompte_days` int(11) DEFAULT 0
-- COMMENT 'Number of non-paid days before payment starts (décompte period)'
-- AFTER `first_day`;

-- Updated table structure with decompte_days field
-- DROP TABLE IF EXISTS `ij_arret`;
-- CREATE TABLE `ij_arret` (
--   `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
--   `adherent_number` varchar(7) NOT NULL,
--   `code_pathologie` varchar(2) NOT NULL,
--   `num_sinistre` int(11) NOT NULL,
--   `date_start` date DEFAULT NULL,
--   `date_end` date DEFAULT NULL,
--   `date_prolongation` date DEFAULT NULL,
--   `first_day` tinyint(4) DEFAULT NULL COMMENT '1 = first day paid, 0 = first day excused',
--   `decompte_days` int(11) DEFAULT 0 COMMENT 'Number of non-paid days before payment starts',
--   `date_declaration` date DEFAULT NULL,
--   `DT_excused` tinyint(4) DEFAULT NULL,
--   `valid_med_controleur` tinyint(4) DEFAULT NULL,
--   `cco_a_jour` tinyint(4) DEFAULT NULL,
--   `date_dern_attestation` date DEFAULT NULL,
--   `date_deb_droit` date DEFAULT NULL,
--   `date_deb_dr_force` date DEFAULT NULL,
--   `taux` float DEFAULT NULL,
--   `NOARRET` int(4) DEFAULT NULL COMMENT 'Numero Arret Mainframe',
--   `version` tinyint(4) NOT NULL DEFAULT 1,
--   `actif` tinyint(1) NOT NULL DEFAULT 1,
--   `created_at` timestamp NULL DEFAULT current_timestamp(),
--   `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
--   PRIMARY KEY (`id`),
--   KEY `adherent_number` (`adherent_number`),
--   KEY `ij_arret_fk_1` (`code_pathologie`),
--   KEY `num_sinistre` (`num_sinistre`),
--   CONSTRAINT `ij_arret_fk_pathologie` FOREIGN KEY (`code_pathologie`) REFERENCES `pathologie` (`code_pathologie`),
--   CONSTRAINT `ij_arret_ibfk_1` FOREIGN KEY (`num_sinistre`) REFERENCES `ij_sinistre` (`id`),
--   CONSTRAINT `ij_arret_ibfk_3` FOREIGN KEY (`adherent_number`) REFERENCES `adherent_infos` (`adherent_number`)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
