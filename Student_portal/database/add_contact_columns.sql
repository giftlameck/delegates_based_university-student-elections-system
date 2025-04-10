-- Add contact information columns to student_details table
ALTER TABLE student_details
ADD COLUMN email VARCHAR(255) DEFAULT NULL COMMENT 'Student email address',
ADD COLUMN phone VARCHAR(20) DEFAULT NULL COMMENT 'Student phone number',
ADD COLUMN address TEXT DEFAULT NULL COMMENT 'Student residential address'; 