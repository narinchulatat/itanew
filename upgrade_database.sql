-- 
-- Database upgrade script to add new fields to home_display_config
-- for improved quarter display management
-- 

-- Add is_default field to identify default configuration
ALTER TABLE `home_display_config` 
ADD COLUMN `is_default` BOOLEAN NOT NULL DEFAULT FALSE 
COMMENT 'Whether this configuration is the default setting';

-- Add active_quarter field to specify which quarter should be active
ALTER TABLE `home_display_config` 
ADD COLUMN `active_quarter` INT(11) NOT NULL DEFAULT 1 
COMMENT 'Which quarter should be active/selected by default (1-4)';

-- Create index for better performance when querying default config
ALTER TABLE `home_display_config` 
ADD INDEX `idx_is_default` (`is_default`);

-- Update existing records to have a default configuration
-- Set the first record as default if no default exists
UPDATE `home_display_config` 
SET `is_default` = TRUE, `active_quarter` = 3
WHERE `id` = (
    SELECT `id` 
    FROM (
        SELECT `id` 
        FROM `home_display_config` 
        ORDER BY `id` ASC 
        LIMIT 1
    ) AS temp
)
AND NOT EXISTS (
    SELECT 1 
    FROM `home_display_config` 
    WHERE `is_default` = TRUE
);

-- Ensure only one default configuration exists
-- If multiple defaults exist, keep only the first one
UPDATE `home_display_config` 
SET `is_default` = FALSE 
WHERE `is_default` = TRUE 
AND `id` NOT IN (
    SELECT `id` 
    FROM (
        SELECT `id` 
        FROM `home_display_config` 
        WHERE `is_default` = TRUE 
        ORDER BY `id` ASC 
        LIMIT 1
    ) AS temp
);