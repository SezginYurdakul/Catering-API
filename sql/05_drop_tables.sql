-- Drop Tables Script
-- Drops all tables from the database (keeps database structure)
-- Use with EXTREME caution: This will delete ALL tables and data!

SET FOREIGN_KEY_CHECKS = 0;

-- Drop tables in reverse dependency order
DROP TABLE IF EXISTS Facility_Tags;
DROP TABLE IF EXISTS Facilities;
DROP TABLE IF EXISTS Locations;
DROP TABLE IF EXISTS Tags;

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'All tables dropped successfully! Database is now empty.' AS Status;