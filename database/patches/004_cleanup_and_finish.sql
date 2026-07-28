-- Run these ONE AT A TIME in phpMyAdmin's SQL tab. If any single line
-- errors, stop and tell me the exact error + which line it was.

-- ============================================================
-- STEP 1: clean up graduate_tracer_survey (currently has both a
-- blocking UNIQUE key AND a redundant duplicate non-unique key)
-- ============================================================
ALTER TABLE `graduate_tracer_survey` DROP INDEX `academic_program_id`;
ALTER TABLE `graduate_tracer_survey` DROP INDEX `academic_program_id_2`;
ALTER TABLE `graduate_tracer_survey` ADD INDEX `academic_program_id` (`academic_program_id`);

ALTER TABLE `graduate_tracer_survey` DROP INDEX `school_year_id`;
ALTER TABLE `graduate_tracer_survey` DROP INDEX `school_year_id_2`;
ALTER TABLE `graduate_tracer_survey` ADD INDEX `school_year_id` (`school_year_id`);

-- ============================================================
-- STEP 2: same cleanup for audit_logs
-- ============================================================
ALTER TABLE `audit_logs` DROP INDEX `user_id`;
ALTER TABLE `audit_logs` DROP INDEX `user_id_2`;
ALTER TABLE `audit_logs` ADD INDEX `user_id` (`user_id`);
ALTER TABLE `audit_logs` MODIFY `user_id` INT(11) NULL;

-- ============================================================
-- STEP 3: make the optional Q15b fields nullable (still NOT NULL)
-- ============================================================
ALTER TABLE `graduate_tracer_survey` MODIFY `advance_study_reason` VARCHAR(255) NULL;
ALTER TABLE `graduate_tracer_survey` MODIFY `advance_study_reason_other` VARCHAR(255) NULL;

-- ============================================================
-- STEP 4: the rest of the previous patch - still not applied
-- ============================================================
ALTER TABLE `general_information` CHANGE `email.` `email` VARCHAR(255) NOT NULL;
ALTER TABLE `general_information` ADD COLUMN `telephone` VARCHAR(50) NULL AFTER `permanent_address`;
ALTER TABLE `general_information` ADD COLUMN `province` VARCHAR(100) NULL AFTER `region_of_origin`;

ALTER TABLE `employment_data` ADD COLUMN `self_employed_skills` TEXT NULL AFTER `present_employment_status`;

ALTER TABLE `course_reasons` ADD COLUMN `other_text` VARCHAR(255) NULL;
ALTER TABLE `not_employed_reasons_17` ADD COLUMN `other_text` VARCHAR(255) NULL;
ALTER TABLE `job_reasons_23to25` ADD COLUMN `other_text` VARCHAR(255) NULL;
ALTER TABLE `competencies` ADD COLUMN `other_text` VARCHAR(255) NULL;

-- ============================================================
-- STEP 5 (optional cleanup): school_years.is_current is correctly
-- named now but still varchar - tighten the type
-- ============================================================
ALTER TABLE `school_years` MODIFY `is_current` TINYINT(1) NOT NULL DEFAULT 0;
