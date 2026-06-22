CREATE TABLE `ete_applicant_ratings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ete_id` BIGINT UNSIGNED NOT NULL,
  `application_id` BIGINT UNSIGNED NOT NULL,
  `jid` BIGINT UNSIGNED NOT NULL,
  `evaluation_date` DATE NULL,
  `present_position` VARCHAR(255) NULL,
  `college_department` VARCHAR(255) NULL,
  `education_met` TINYINT(1) NULL,
  `experience_met` TINYINT(1) NULL,
  `eligibility_met` TINYINT(1) NULL,
  `training_met` TINYINT(1) NULL,
  `minimum_requirement_score` DECIMAL(6,2) NOT NULL DEFAULT 0,
  `education_score` DECIMAL(6,2) NOT NULL DEFAULT 0,
  `education_ratings` JSON NULL,
  `training_score` DECIMAL(6,2) NOT NULL DEFAULT 0,
  `training_ratings` JSON NULL,
  `experience_score` DECIMAL(6,2) NOT NULL DEFAULT 0,
  `experience_year_ratings` JSON NULL,
  `total_score` DECIMAL(6,2) NOT NULL DEFAULT 0,
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ete_applicant_ratings_unique` (`ete_id`, `application_id`),
  KEY `ete_applicant_ratings_application_fk` (`application_id`),
  KEY `ete_applicant_ratings_job_fk` (`jid`),
  CONSTRAINT `ete_applicant_ratings_ete_fk` FOREIGN KEY (`ete_id`) REFERENCES `ete_evaluations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ete_applicant_ratings_application_fk` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ete_applicant_ratings_job_fk` FOREIGN KEY (`jid`) REFERENCES `job_hirings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional one-time copy: keeps one existing evaluator score per applicant as the admin score.
-- Run only if old ETE scores must be retained.
INSERT INTO `ete_applicant_ratings` (
  `ete_id`, `application_id`, `jid`, `evaluation_date`, `present_position`,
  `college_department`, `education_met`, `experience_met`, `eligibility_met`,
  `training_met`, `minimum_requirement_score`, `education_score`, `education_ratings`,
  `training_score`, `training_ratings`, `experience_score`, `experience_year_ratings`,
  `total_score`, `remarks`, `created_at`, `updated_at`
)
SELECT ee.`ete_id`, ee.`application_id`, ee.`jid`, ee.`evaluation_date`, ee.`present_position`,
       ee.`college_department`, ee.`education_met`, ee.`experience_met`, ee.`eligibility_met`,
       ee.`training_met`, ee.`minimum_requirement_score`, ee.`education_score`, ee.`education_ratings`,
       ee.`training_score`, ee.`training_ratings`, ee.`experience_score`, ee.`experience_year_ratings`,
       ee.`total_score`, ee.`remarks`, ee.`created_at`, ee.`updated_at`
FROM `employee_evaluates` ee
INNER JOIN (
  SELECT `ete_id`, `application_id`, MIN(`id`) AS `id`
  FROM `employee_evaluates`
  GROUP BY `ete_id`, `application_id`
) first_score ON first_score.`id` = ee.`id`
ON DUPLICATE KEY UPDATE `updated_at` = VALUES(`updated_at`);
