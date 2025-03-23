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
    ('Haarlem', 'Grote Houtstraat 100', '2011SN', 'NL', '+31-23-5678901'),
    ('Zwolle', 'Melkmarkt 12', '8011MC', 'NL', '+31-38-1234567'),
    ('Arnhem', 'Korenmarkt 8', '6811GV', 'NL', '+31-26-7654321'),
    ('Breda', 'Grote Markt 20', '4811XR', 'NL', '+31-76-9876543'),
    ('Tilburg', 'Heuvelstraat 15', '5038AA', 'NL', '+31-13-4567890'),
    ('Almere', 'Stadhuisplein 1', '1315HR', 'NL', '+31-36-1239876'),
    ('Nijmegen', 'Grote Markt 10', '6511KH', 'NL', '+31-24-6543210'),
    ('Apeldoorn', 'Hoofdstraat 5', '7311KA', 'NL', '+31-55-7890123'),
    ('Amersfoort', 'Lieve Vrouweplein 3', '3811BR', 'NL', '+31-33-2345678'),
    ('Den Bosch', 'Markt 25', '5211JW', 'NL', '+31-73-3456789'),
    ('Haarlemmermeer', 'Raadhuisplein 1', '2132TZ', 'NL', '+31-23-5678901'),
    ('Zoetermeer', 'Stadhuisplein 10', '2711EC', 'NL', '+31-79-1234567'),
    ('Leeuwarden', 'Zaailand 15', '8911BL', 'NL', '+31-58-7654321'),
    ('Deventer', 'Brink 8', '7411BR', 'NL', '+31-57-9876543'),
    ('Enschede', 'Oude Markt 20', '7511GA', 'NL', '+31-53-4567890'),
    ('Hengelo', 'Burgemeester Jansenplein 5', '7551ED', 'NL', '+31-74-1239876');

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
    ('Haarlem Exclusive Catering', 10),
    ('Zwolle Business Events', 11),
    ('Arnhem Outdoor Catering', 12),
    ('Breda Conference Hall', 13),
    ('Tilburg Wedding Venue', 14),
    ('Almere Private Events', 15),
    ('Nijmegen Banquet Hall', 16),
    ('Apeldoorn Cultural Center', 17),
    ('Amersfoort Exclusive Dining', 18),
    ('Den Bosch Party Hall', 19),
    ('Haarlemmermeer Event Venue', 20),
    ('Zoetermeer Grand Hall', 21),
    ('Leeuwarden Outdoor Events', 22),
    ('Deventer Conference Center', 23),
    ('Enschede Private Dining', 24),
    ('Hengelo Exclusive Catering', 25);

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
    (10, 5), -- Haarlem Exclusive Catering -> Indoor
    (11, 2), -- Zwolle Business Events -> Corporate Event
    (12, 4), -- Arnhem Outdoor Catering -> Outdoor
    (13, 6), -- Breda Conference Hall -> Conference
    (14, 1), -- Tilburg Wedding Venue -> Wedding
    (15, 7), -- Almere Private Events -> Private Party
    (16, 6), -- Nijmegen Banquet Hall -> Conference
    (17, 4), -- Apeldoorn Cultural Center -> Outdoor
    (18, 7), -- Amersfoort Exclusive Dining -> Private Party
    (19, 3), -- Den Bosch Party Hall -> Birthday Party
    (20, 2), -- Haarlemmermeer Event Venue -> Corporate Event
    (21, 4), -- Zoetermeer Grand Hall -> Outdoor
    (22, 6), -- Leeuwarden Outdoor Events -> Conference
    (23, 5), -- Deventer Conference Center -> Indoor
    (24, 7), -- Enschede Private Dining -> Private Party
    (25, 1); -- Hengelo Exclusive Catering -> Wedding