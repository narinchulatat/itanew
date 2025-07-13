-- Complete migration script for home_display_config table
-- This ensures all required fields exist and sets up proper defaults

-- First, add the basic fields if they don't exist
ALTER TABLE home_display_config 
ADD COLUMN IF NOT EXISTS is_default BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS default_year INT NULL,
ADD COLUMN IF NOT EXISTS default_quarter INT NULL,
ADD COLUMN IF NOT EXISTS active_quarter INT DEFAULT 1;

-- Create index for better performance
ALTER TABLE home_display_config 
ADD INDEX IF NOT EXISTS idx_is_default (is_default),
ADD INDEX IF NOT EXISTS idx_year_quarter (year, quarter);

-- Update existing records: find the first configuration for year 2568 quarter 3 and set it as default
UPDATE home_display_config 
SET is_default = TRUE, 
    default_year = 2568, 
    default_quarter = 3,
    active_quarter = 3
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
INSERT INTO home_display_config (year, quarter, source_year, source_quarter, is_default, default_year, default_quarter, active_quarter)
SELECT 
    (SELECT id FROM years WHERE year = 2568 LIMIT 1) as year,
    3 as quarter,
    (SELECT id FROM years WHERE year = 2568 LIMIT 1) as source_year,
    3 as source_quarter,
    TRUE as is_default,
    2568 as default_year,
    3 as default_quarter,
    3 as active_quarter
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