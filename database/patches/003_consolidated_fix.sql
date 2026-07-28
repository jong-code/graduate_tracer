-- Consolidated fix - supersedes the earlier 002_fill_missing_form_fields.sql.
-- Run this single file against your current `graduate_tracer` database.
-- Safe to run even if some of these were already applied - just skip any
-- individual statement that errors with "duplicate column" or "duplicate key".

-- ============================================================
-- 1. CRITICAL: remove incorrect UNIQUE keys (these currently block
--    more than one survey from sharing a program/school year, and
--    block more than one audit log entry per user)
-- ============================================================
ALTER TABLE `graduate_tracer_survey` DROP INDEX `academic_program_id`;
ALTER TABLE `graduate_tracer_survey` ADD INDEX `academic_program_id` (`academic_program_id`);

ALTER TABLE `graduate_tracer_survey` DROP INDEX `school_year_id`;
ALTER TABLE `graduate_tracer_survey` ADD INDEX `school_year_id` (`school_year_id`);

ALTER TABLE `audit_logs` DROP INDEX `user_id`;
ALTER TABLE `audit_logs` ADD INDEX `user_id` (`user_id`);
-- Also make user_id nullable, so system-generated log entries
-- (e.g. an automated backup job with no logged-in actor) don't fail:
ALTER TABLE `audit_logs` MODIFY `user_id` INT(11) NULL;

-- ============================================================
-- 2. Fix the space-in-column-name typo + wrong types on the
--    lookup tables' boolean flags
-- ============================================================
ALTER TABLE `school_years` CHANGE `is current` `is_current` TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE `academic_programs` MODIFY `is_active` TINYINT(1) NOT NULL DEFAULT 1;

-- ============================================================
-- 3. Make the optional Q15b fields nullable (they're NOT NULL
--    right now, which will break saves for anyone who didn't
--    pursue advance studies)
-- ============================================================
ALTER TABLE `graduate_tracer_survey`
  MODIFY `advance_study_reason` VARCHAR(255) NULL,
  MODIFY `advance_study_reason_other` VARCHAR(255) NULL;

-- ============================================================
-- 4. Still-outstanding fixes from the previous patch
-- ============================================================
ALTER TABLE `general_information` CHANGE `email.` `email` VARCHAR(255) NOT NULL;
ALTER TABLE `general_information`
  ADD COLUMN `telephone` VARCHAR(50) NULL AFTER `permanent_address`,
  ADD COLUMN `province` VARCHAR(100) NULL AFTER `region_of_origin`;

ALTER TABLE `employment_data`
  ADD COLUMN `self_employed_skills` TEXT NULL AFTER `present_employment_status`;

ALTER TABLE `course_reasons` ADD COLUMN `other_text` VARCHAR(255) NULL;
ALTER TABLE `not_employed_reasons_17` ADD COLUMN `other_text` VARCHAR(255) NULL;
ALTER TABLE `job_reasons_23to25` ADD COLUMN `other_text` VARCHAR(255) NULL;
ALTER TABLE `competencies` ADD COLUMN `other_text` VARCHAR(255) NULL;
