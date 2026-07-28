-- Run this against your existing `graduate_tracer` database before using the
-- updated Laravel controllers/models. It only ADDS columns/tables - nothing
-- you've already inserted will be lost.

-- 1. general_information was missing telephone (Q4) and province (Q10)
ALTER TABLE `general_information`
  ADD COLUMN `telephone` VARCHAR(50) NULL AFTER `permanent_address`,
  ADD COLUMN `province` VARCHAR(100) NULL AFTER `region_of_origin`;

-- Also fix the trailing-period typo flagged earlier, if not already done:
-- ALTER TABLE `general_information` CHANGE `email.` `email` VARCHAR(255) NOT NULL;

-- 2. employment_data was missing self_employed_skills (Q18 follow-up),
--    and still has the old leftover reasons_not_employedd column
ALTER TABLE `employment_data`
  ADD COLUMN `self_employed_skills` TEXT NULL AFTER `present_employment_status`,
  DROP COLUMN `reasons_not_employedd`;

-- 3. Q15b (advance study reason) has no home yet - one answer per survey,
--    so it belongs on the parent survey row, not a child table
ALTER TABLE `graduate_tracer_survey`
  ADD COLUMN `advance_study_reason` ENUM('promotion','professional_development','others') NULL,
  ADD COLUMN `advance_study_reason_other` VARCHAR(255) NULL;

-- 4. Every "Others, please specify ___" checkbox needs somewhere to store
--    the free-text answer when reason_key = 'other'
ALTER TABLE `course_reasons` ADD COLUMN `other_text` VARCHAR(255) NULL;
ALTER TABLE `not_employed_reasons_17` ADD COLUMN `other_text` VARCHAR(255) NULL;
ALTER TABLE `job_reasons_23to25` ADD COLUMN `other_text` VARCHAR(255) NULL;
ALTER TABLE `competencies` ADD COLUMN `other_text` VARCHAR(255) NULL;
