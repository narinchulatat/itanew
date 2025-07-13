-- Migration script to add default configuration fields to home_display_config table
-- This script adds the required fields for managing default year/quarter settings

-- Add new columns to home_display_config table
ALTER TABLE home_display_config 
ADD COLUMN is_default BOOLEAN DEFAULT FALSE,
ADD COLUMN default_year INT NULL,
ADD COLUMN default_quarter INT NULL;

-- Update existing records: find the first configuration for year 2568 quarter 3 and set it as default
UPDATE home_display_config 
SET is_default = TRUE, 
    default_year = 2568, 
    default_quarter = 3 
WHERE year = (SELECT id FROM years WHERE year = 2568 LIMIT 1) 
  AND quarter = 3 
  AND id = (
    SELECT id FROM (
      SELECT id FROM home_display_config 
      WHERE year = (SELECT id FROM years WHERE year = 2568 LIMIT 1) 
        AND quarter = 3 
      ORDER BY id ASC 
      LIMIT 1
    ) AS tmp
  );

-- If no configuration exists for 2568 Q3, create a default one
INSERT INTO home_display_config (year, quarter, source_year, source_quarter, is_default, default_year, default_quarter)
SELECT 
    (SELECT id FROM years WHERE year = 2568 LIMIT 1) as year,
    3 as quarter,
    (SELECT id FROM years WHERE year = 2568 LIMIT 1) as source_year,
    3 as source_quarter,
    TRUE as is_default,
    2568 as default_year,
    3 as default_quarter
WHERE NOT EXISTS (
    SELECT 1 FROM home_display_config 
    WHERE year = (SELECT id FROM years WHERE year = 2568 LIMIT 1) 
      AND quarter = 3
);

-- Ensure only one default configuration exists
UPDATE home_display_config 
SET is_default = FALSE 
WHERE is_default = TRUE 
  AND id NOT IN (
    SELECT id FROM (
      SELECT id FROM home_display_config 
      WHERE is_default = TRUE 
      ORDER BY id ASC 
      LIMIT 1
    ) AS tmp
  );