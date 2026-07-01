-- Clearance Migration Script
-- This script migrates the chapter_approvals table from using integer chapter numbers
-- to using string-based clearance levels (proposal, internal, external).

-- 1. Add the new column
ALTER TABLE chapter_approvals ADD COLUMN clearance_level VARCHAR(50) NULL AFTER chapter_number;

-- 2. Migrate existing data
-- Chapter 1 becomes 'proposal'
-- Chapter 2 becomes 'internal'
-- Chapter 3 becomes 'external'
UPDATE chapter_approvals SET clearance_level = 'proposal' WHERE chapter_number = 1;
UPDATE chapter_approvals SET clearance_level = 'internal' WHERE chapter_number = 2;
UPDATE chapter_approvals SET clearance_level = 'external' WHERE chapter_number = 3;

-- 3. Delete any approvals for chapters beyond 3
DELETE FROM chapter_approvals WHERE chapter_number NOT IN (1, 2, 3);

-- 4. Drop the old unique key and chapter_number column
ALTER TABLE chapter_approvals DROP INDEX student_chapter;
ALTER TABLE chapter_approvals DROP COLUMN chapter_number;

-- 5. Make the new column NOT NULL and add the new unique key
ALTER TABLE chapter_approvals MODIFY clearance_level VARCHAR(50) NOT NULL;
ALTER TABLE chapter_approvals ADD UNIQUE KEY student_clearance (student_id, clearance_level);
