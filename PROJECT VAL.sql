-- 1. Add Chapter Configuration to Departments
-- This allows each department (DPC) to set their own required chapter count
ALTER TABLE departments 
ADD COLUMN num_chapters INT DEFAULT 5 AFTER department_name;

-- 2. Create Chapter Approvals Table
-- This tracks individual chapter completions per student, supervisor, and session
CREATE TABLE IF NOT EXISTS chapter_approvals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    supervisor_id INT NOT NULL,
    chapter_number INT NOT NULL,
    status ENUM('pending', 'approved') DEFAULT 'pending',
    approval_date DATETIME DEFAULT NULL,
    academic_session VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Ensures a student doesn't have duplicate approvals for the same chapter
    UNIQUE KEY student_chapter_unique (student_id, chapter_number),
    
    -- Foreign Key constraints (assuming standard users/students table structure)
    CONSTRAINT fk_chapter_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_chapter_supervisor FOREIGN KEY (supervisor_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Optional: Add an index for faster report generation
CREATE INDEX idx_chapter_session ON chapter_approvals(academic_session);