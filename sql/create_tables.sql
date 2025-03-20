-- Create Locations Table
CREATE TABLE IF NOT EXISTS Locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    city VARCHAR(255) NOT NULL,
    address TEXT NOT NULL,
    zip_code VARCHAR(20) NOT NULL,
    country_code VARCHAR(10) NOT NULL,
    phone_number VARCHAR(20) NOT NULL
);

-- Create Facilities Table
CREATE TABLE IF NOT EXISTS Facilities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    creation_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    location_id INT NOT NULL,
    FOREIGN KEY (location_id) REFERENCES Locations(id) ON DELETE CASCADE
);

-- Create Tags Table
CREATE TABLE IF NOT EXISTS Tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE
);

-- Create Facility_Tags Junction Table
CREATE TABLE IF NOT EXISTS Facility_Tags (
    facility_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (facility_id, tag_id),
    FOREIGN KEY (facility_id) REFERENCES Facilities(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES Tags(id) ON DELETE CASCADE
);