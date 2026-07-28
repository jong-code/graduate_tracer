-- The controller now correctly sets academic_program_id from the wizard's
-- new Program dropdown, and auto-derives school_year_id from whichever
-- school_years row has is_current = 1. But if no admin has marked a school
-- year "current" yet, that lookup returns null - so this column must allow
-- NULL or every submission will fail until an admin visits School Years
-- and clicks "Set as current".

ALTER TABLE `graduate_tracer_survey` MODIFY `school_year_id` INT(11) NULL;
