-- Add photo_path column to student_details table
ALTER TABLE student_details
ADD COLUMN photo_path VARCHAR(255) DEFAULT NULL;

-- Add comment to explain the column
COMMENT ON COLUMN student_details.photo_path IS 'Path to student profile photo'; 