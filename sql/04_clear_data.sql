-- Reset Database Script
-- This script clears all data from tables while preserving structure
-- Use with caution: This will delete ALL data!

SET FOREIGN_KEY_CHECKS = 0;

-- Clear junction tables first (due to foreign key constraints)
DELETE FROM Facility_Tags;
DELETE FROM Employee_Facility;

-- Clear main tables
DELETE FROM Facilities;
DELETE FROM Locations;
DELETE FROM Tags;
DELETE FROM Employees;

-- Reset AUTO_INCREMENT values to start fresh
ALTER TABLE Facilities AUTO_INCREMENT = 1;
ALTER TABLE Locations AUTO_INCREMENT = 1;
ALTER TABLE Tags AUTO_INCREMENT = 1;
ALTER TABLE Employees AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;