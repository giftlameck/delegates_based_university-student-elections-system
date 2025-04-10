-- Add remarks column to applications table if it doesn't exist
ALTER TABLE applications ADD COLUMN IF NOT EXISTS remarks TEXT;

-- Add photo_path column to delegate_winners table if it doesn't exist
ALTER TABLE delegate_winners ADD COLUMN IF NOT EXISTS photo_path VARCHAR(255) AFTER student_name;

-- Update delegate_winners with photo paths from applications
UPDATE delegate_winners dw
JOIN applications a ON dw.delegate_id = a.student_id
SET dw.photo_path = a.photo_path; 