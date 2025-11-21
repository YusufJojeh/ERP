<?php
/**
 * Script to ensure all projects have project managers
 * This script:
 * 1. Checks for projects without managers
 * 2. Assigns default managers to projects without managers
 * 3. Updates the database schema to enforce NOT NULL on manager_id
 */

require_once 'config/config.php';

echo "=== Fixing Project Managers ===\n\n";

try {
    // Step 1: Check for projects without managers
    $sql = "SELECT id, name, manager_id FROM projects WHERE manager_id IS NULL";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $projectsWithoutManagers = $stmt->fetchAll();
    
    if (!empty($projectsWithoutManagers)) {
        echo "Found " . count($projectsWithoutManagers) . " project(s) without managers:\n";
        foreach ($projectsWithoutManagers as $project) {
            echo "  - Project ID: {$project['id']}, Name: {$project['name']}\n";
        }
        echo "\n";
        
        // Get the first available project manager
        $sql = "SELECT id, first_name, last_name FROM users WHERE role = 'project_manager' LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $defaultManager = $stmt->fetch();
        
        if ($defaultManager) {
            echo "Assigning default manager: {$defaultManager['first_name']} {$defaultManager['last_name']} (ID: {$defaultManager['id']})\n\n";
            
            // Update projects with NULL managers
            $sql = "UPDATE projects SET manager_id = ? WHERE manager_id IS NULL";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$defaultManager['id']]);
            
            if ($result) {
                $affected = $stmt->rowCount();
                echo "✓ Successfully assigned managers to {$affected} project(s)\n\n";
            }
        } else {
            echo "✗ ERROR: No project managers found in the database. Please create at least one project manager first.\n";
            exit(1);
        }
    } else {
        echo "✓ All projects already have managers assigned.\n\n";
    }
    
    // Step 2: Check current schema
    echo "Checking database schema...\n";
    $sql = "SELECT COLUMN_NAME, IS_NULLABLE, COLUMN_TYPE 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'projects' 
            AND COLUMN_NAME = 'manager_id'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $columnInfo = $stmt->fetch();
    
    if ($columnInfo) {
        echo "Current manager_id column: IS_NULLABLE = {$columnInfo['IS_NULLABLE']}\n";
        
        if ($columnInfo['IS_NULLABLE'] === 'YES') {
            echo "\nUpdating schema to make manager_id NOT NULL...\n";
            
            // Drop existing foreign key constraint first
            try {
                $sql = "SELECT CONSTRAINT_NAME 
                        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                        WHERE TABLE_SCHEMA = DATABASE() 
                        AND TABLE_NAME = 'projects' 
                        AND COLUMN_NAME = 'manager_id' 
                        AND REFERENCED_TABLE_NAME IS NOT NULL";
                $stmt = $pdo->prepare($sql);
                $stmt->execute();
                $fkInfo = $stmt->fetch();
                
                if ($fkInfo) {
                    $fkName = $fkInfo['CONSTRAINT_NAME'];
                    echo "Dropping foreign key constraint: {$fkName}\n";
                    $pdo->exec("ALTER TABLE projects DROP FOREIGN KEY {$fkName}");
                }
            } catch (Exception $e) {
                echo "Note: " . $e->getMessage() . "\n";
            }
            
            // Modify column to NOT NULL
            $sql = "ALTER TABLE projects MODIFY COLUMN manager_id INT NOT NULL";
            $pdo->exec($sql);
            echo "✓ Column updated to NOT NULL\n";
            
            // Add foreign key constraint back with RESTRICT
            try {
                $sql = "ALTER TABLE projects 
                        ADD CONSTRAINT projects_manager_fk 
                        FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE RESTRICT";
                $pdo->exec($sql);
                echo "✓ Foreign key constraint added with RESTRICT\n";
            } catch (Exception $e) {
                echo "Note: " . $e->getMessage() . "\n";
            }
        } else {
            echo "✓ Schema already enforces NOT NULL\n";
        }
    }
    
    // Step 3: Final verification
    echo "\n=== Final Verification ===\n";
    $sql = "SELECT id, name, manager_id FROM projects WHERE manager_id IS NULL";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $remaining = $stmt->fetchAll();
    
    if (empty($remaining)) {
        echo "✓ SUCCESS: All projects have managers assigned!\n";
    } else {
        echo "✗ WARNING: Still found " . count($remaining) . " project(s) without managers.\n";
    }
    
    echo "\n=== Done ===\n";
    
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

