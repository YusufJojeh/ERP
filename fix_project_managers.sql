-- SQL Script to ensure all projects have project managers
-- Run this script to update existing projects and enforce manager_id requirement

-- Step 1: Find any projects without managers and assign a default project manager
-- Get the first available project manager
SET @default_manager_id = (SELECT id FROM users WHERE role = 'project_manager' LIMIT 1);

-- Update projects with NULL managers to use the default manager
UPDATE projects 
SET manager_id = @default_manager_id 
WHERE manager_id IS NULL AND @default_manager_id IS NOT NULL;

-- Step 2: Drop the old foreign key constraint
ALTER TABLE projects 
DROP FOREIGN KEY projects_ibfk_1;

-- Step 3: Update the manager_id column to be NOT NULL
ALTER TABLE projects 
MODIFY COLUMN manager_id INT NOT NULL;

-- Step 4: Add the foreign key constraint back with RESTRICT instead of SET NULL
ALTER TABLE projects 
ADD CONSTRAINT projects_manager_fk 
FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE RESTRICT;

-- Verification: Show projects without managers (should return no rows)
SELECT id, name, manager_id 
FROM projects 
WHERE manager_id IS NULL;

