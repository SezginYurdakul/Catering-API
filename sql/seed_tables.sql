-- Insert sample data into Locations table
INSERT INTO Locations (city, address, zip_code, country_code, phone_number)
VALUES
    ('Amsterdam', 'Damrak 1', '1012AB', 'NL', '+31-20-1234567'),
    ('Rotterdam', 'Coolsingel 10', '3012AD', 'NL', '+31-10-7654321'),
    ('The Hague', 'Lange Voorhout 15', '2514EE', 'NL', '+31-70-9876543'),
    ('Utrecht', 'Domplein 4', '3512JC', 'NL', '+31-30-4567890'),
    ('Eindhoven', 'Strijp-S 20', '5617AB', 'NL', '+31-40-1239876'),
    ('Groningen', 'Grote Markt 5', '9712CP', 'NL', '+31-50-6543210'),
    ('Maastricht', 'Vrijthof 7', '6211LE', 'NL', '+31-43-7890123'),
    ('Leiden', 'Breestraat 50', '2311CS', 'NL', '+31-71-2345678'),
    ('Delft', 'Markt 80', '2611GW', 'NL', '+31-15-3456789'),
    ('Haarlem', 'Grote Houtstraat 100', '2011SN', 'NL', '+31-23-5678901');

-- Insert sample data into Facilities table
INSERT INTO Facilities (name, location_id)
VALUES
    ('Amsterdam Grand Catering', 1),
    ('Rotterdam Event Center', 2),
    ('The Hague Banquet Hall', 3),
    ('Utrecht Party Venue', 4),
    ('Eindhoven Outdoor Events', 5),
    ('Groningen Conference Center', 6),
    ('Maastricht Wedding Hall', 7),
    ('Leiden Private Dining', 8),
    ('Delft Cultural Events', 9),
    ('Haarlem Exclusive Catering', 10);

-- Insert sample data into Tags table
INSERT INTO Tags (name)
VALUES
    ('Wedding'),
    ('Corporate Event'),
    ('Birthday Party'),
    ('Outdoor'),
    ('Indoor'),
    ('Conference'),
    ('Private Party');

-- Insert sample data into Facility_Tags table
INSERT INTO Facility_Tags (facility_id, tag_id)
VALUES
    (1, 1), -- Amsterdam Grand Catering -> Wedding
    (1, 4), -- Amsterdam Grand Catering -> Outdoor
    (2, 2), -- Rotterdam Event Center -> Corporate Event
    (2, 5), -- Rotterdam Event Center -> Indoor
    (3, 6), -- The Hague Banquet Hall -> Conference
    (4, 3), -- Utrecht Party Venue -> Birthday Party
    (5, 4), -- Eindhoven Outdoor Events -> Outdoor
    (6, 6), -- Groningen Conference Center -> Conference
    (7, 1), -- Maastricht Wedding Hall -> Wedding
    (8, 7), -- Leiden Private Dining -> Private Party
    (9, 4), -- Delft Cultural Events -> Outdoor
    (10, 5); -- Haarlem Exclusive Catering -> Indoor