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

-- Insert sample data into Employees table
REPLACE INTO Employees (id, name, address, phone, email) VALUES
(1, 'John Smith', '123 Main St, Amsterdam', '+31 6 1234 5678', 'john.smith@example.com'),
(2, 'Emma Johnson', '456 Oak Ave, Rotterdam', '+31 6 2345 6789', 'emma.johnson@example.com'),
(3, 'Michael Brown', '789 Pine Rd, Utrecht', '+31 6 3456 7890', 'michael.brown@example.com'),
(4, 'Sophia Davis', '321 Elm St, The Hague', '+31 6 4567 8901', 'sophia.davis@example.com'),
(5, 'Oliver Wilson', '654 Maple Dr, Eindhoven', '+31 6 5678 9012', 'oliver.wilson@example.com'),
(6, 'Liam Anderson', '111 Beach Rd, Groningen', '+31 6 6789 0123', 'liam.anderson@example.com'),
(7, 'Ava Martinez', '222 Hill St, Maastricht', '+31 6 7890 1234', 'ava.martinez@example.com'),
(8, 'Noah Garcia', '333 Lake Ave, Leiden', '+31 6 8901 2345', 'noah.garcia@example.com'),
(9, 'Isabella Rodriguez', '444 River Rd, Delft', '+31 6 9012 3456', 'isabella.rodriguez@example.com'),
(10, 'James Wilson', '555 Forest Dr, Haarlem', '+31 6 0123 4567', 'james.wilson@example.com'),
(11, 'Mia Lopez', '666 Park St, Zwolle', '+31 6 1234 6789', 'mia.lopez@example.com'),
(12, 'Benjamin Lee', '777 Garden Ave, Arnhem', '+31 6 2345 7890', 'benjamin.lee@example.com'),
(13, 'Charlotte Walker', '888 Valley Rd, Breda', '+31 6 3456 8901', 'charlotte.walker@example.com'),
(14, 'Lucas Hall', '999 Mountain Dr, Tilburg', '+31 6 4567 9012', 'lucas.hall@example.com'),
(15, 'Amelia Young', '101 Ocean Blvd, Almere', '+31 6 5678 0123', 'amelia.young@example.com'),
(16, 'Henry King', '202 Desert Rd, Nijmegen', '+31 6 6789 1234', 'henry.king@example.com'),
(17, 'Harper Wright', '303 Plains Ave, Apeldoorn', '+31 6 7890 2345', 'harper.wright@example.com'),
(18, 'Alexander Scott', '404 Canyon St, Amersfoort', '+31 6 8901 3456', 'alexander.scott@example.com'),
(19, 'Evelyn Green', '505 Meadow Dr, Den Bosch', '+31 6 9012 4567', 'evelyn.green@example.com'),
(20, 'Sebastian Adams', '606 Creek Rd, Haarlemmermeer', '+31 6 0123 5678', 'sebastian.adams@example.com'),
(21, 'Abigail Baker', '707 Bridge Ave, Zoetermeer', '+31 6 1234 7890', 'abigail.baker@example.com'),
(22, 'Jack Nelson', '808 Harbor St, Leeuwarden', '+31 6 2345 8901', 'jack.nelson@example.com'),
(23, 'Emily Carter', '909 Marina Dr, Deventer', '+31 6 3456 9012', 'emily.carter@example.com'),
(24, 'Daniel Mitchell', '110 Port Rd, Enschede', '+31 6 4567 0123', 'daniel.mitchell@example.com'),
(25, 'Ella Perez', '120 Bay Ave, Hengelo', '+31 6 5678 1234', 'ella.perez@example.com'),
(26, 'Matthew Roberts', '130 Shore St, Amsterdam', '+31 6 6789 2345', 'matthew.roberts@example.com'),
(27, 'Scarlett Turner', '140 Coast Dr, Rotterdam', '+31 6 7890 3456', 'scarlett.turner@example.com'),
(28, 'David Phillips', '150 Cliff Rd, Utrecht', '+31 6 8901 4567', 'david.phillips@example.com'),
(29, 'Victoria Campbell', '160 Summit Ave, Eindhoven', '+31 6 9012 5678', 'victoria.campbell@example.com'),
(30, 'Joseph Parker', '170 Peak St, Groningen', '+31 6 0123 6789', 'joseph.parker@example.com'),
(31, 'Grace Evans', '180 Ridge Dr, Maastricht', '+31 6 1234 8901', 'grace.evans@example.com'),
(32, 'Samuel Edwards', '190 Trail Rd, Leiden', '+31 6 2345 9012', 'samuel.edwards@example.com'),
(33, 'Chloe Collins', '200 Path Ave, Delft', '+31 6 3456 0123', 'chloe.collins@example.com'),
(34, 'Andrew Stewart', '210 Road St, Haarlem', '+31 6 4568 1234', 'andrew.stewart@example.com'),
(35, 'Zoey Morris', '220 Lane Dr, Zwolle', '+31 6 5679 2345', 'zoey.morris@example.com'),
(36, 'Joshua Rogers', '230 Court Rd, Arnhem', '+31 6 6780 3456', 'joshua.rogers@example.com'),
(37, 'Lily Reed', '240 Square Ave, Breda', '+31 6 7891 4567', 'lily.reed@example.com'),
(38, 'Christopher Cook', '250 Circle St, Tilburg', '+31 6 8902 5678', 'christopher.cook@example.com'),
(39, 'Hannah Morgan', '260 Loop Dr, Almere', '+31 6 9013 6789', 'hannah.morgan@example.com'),
(40, 'Ryan Bell', '270 Bend Rd, Nijmegen', '+31 6 0124 7890', 'ryan.bell@example.com'),
(41, 'Aria Murphy', '280 Curve Ave, Apeldoorn', '+31 6 1235 8901', 'aria.murphy@example.com'),
(42, 'Nathan Bailey', '290 Turn St, Amersfoort', '+31 6 2346 9012', 'nathan.bailey@example.com'),
(43, 'Lillian Rivera', '300 Twist Dr, Den Bosch', '+31 6 3457 0123', 'lillian.rivera@example.com'),
(44, 'Isaac Cooper', '310 Wind Rd, Haarlemmermeer', '+31 6 4568 1235', 'isaac.cooper@example.com'),
(45, 'Addison Richardson', '320 Storm Ave, Zoetermeer', '+31 6 5679 2346', 'addison.richardson@example.com'),
(46, 'Gabriel Cox', '330 Rain St, Leeuwarden', '+31 6 6780 3457', 'gabriel.cox@example.com'),
(47, 'Ellie Howard', '340 Snow Dr, Deventer', '+31 6 7891 4568', 'ellie.howard@example.com'),
(48, 'Owen Ward', '350 Cloud Rd, Enschede', '+31 6 8902 5679', 'owen.ward@example.com'),
(49, 'Avery Torres', '360 Sky Ave, Hengelo', '+31 6 9013 6780', 'avery.torres@example.com'),
(50, 'Carter Peterson', '370 Star St, Amsterdam', '+31 6 0124 7891', 'carter.peterson@example.com'),
(51, 'Sofia Gray', '380 Moon Dr, Rotterdam', '+31 6 1235 8902', 'sofia.gray@example.com'),
(52, 'Wyatt Ramirez', '390 Sun Rd, Utrecht', '+31 6 2346 9013', 'wyatt.ramirez@example.com'),
(53, 'Madison James', '400 Light Ave, Eindhoven', '+31 6 3457 0124', 'madison.james@example.com'),
(54, 'Luke Watson', '410 Bright St, Groningen', '+31 6 4568 1236', 'luke.watson@example.com'),
(55, 'Layla Brooks', '420 Dawn Dr, Maastricht', '+31 6 5679 2347', 'layla.brooks@example.com'),
(56, 'Dylan Kelly', '430 Dusk Rd, Leiden', '+31 6 6780 3458', 'dylan.kelly@example.com'),
(57, 'Penelope Sanders', '440 Sunset Ave, Delft', '+31 6 7891 4569', 'penelope.sanders@example.com'),
(58, 'Grayson Price', '450 Sunrise St, Haarlem', '+31 6 8902 5680', 'grayson.price@example.com'),
(59, 'Nora Bennett', '460 Twilight Dr, Zwolle', '+31 6 9013 6781', 'nora.bennett@example.com'),
(60, 'Zachary Wood', '470 Evening Rd, Arnhem', '+31 6 0124 7892', 'zachary.wood@example.com'),
(61, 'Riley Barnes', '480 Morning Ave, Breda', '+31 6 1235 8903', 'riley.barnes@example.com'),
(62, 'Eli Ross', '490 Noon St, Tilburg', '+31 6 2346 9014', 'eli.ross@example.com'),
(63, 'Stella Henderson', '500 Night Dr, Almere', '+31 6 3457 0125', 'stella.henderson@example.com'),
(64, 'Christian Coleman', '510 Day Rd, Nijmegen', '+31 6 4568 1237', 'christian.coleman@example.com'),
(65, 'Hazel Jenkins', '520 Time Ave, Apeldoorn', '+31 6 5679 2348', 'hazel.jenkins@example.com'),
(66, 'Hunter Perry', '530 Hour St, Amersfoort', '+31 6 6780 3459', 'hunter.perry@example.com'),
(67, 'Violet Powell', '540 Minute Dr, Den Bosch', '+31 6 7891 4570', 'violet.powell@example.com'),
(68, 'Aaron Long', '550 Second Rd, Haarlemmermeer', '+31 6 8902 5681', 'aaron.long@example.com'),
(69, 'Savannah Patterson', '560 Moment Ave, Zoetermeer', '+31 6 9013 6782', 'savannah.patterson@example.com'),
(70, 'Thomas Hughes', '570 Instant St, Leeuwarden', '+31 6 0124 7893', 'thomas.hughes@example.com'),
(71, 'Brooklyn Flores', '580 Flash Dr, Deventer', '+31 6 1235 8904', 'brooklyn.flores@example.com'),
(72, 'Charles Washington', '590 Spark Rd, Enschede', '+31 6 2346 9015', 'charles.washington@example.com'),
(73, 'Claire Butler', '600 Flame Ave, Hengelo', '+31 6 3457 0126', 'claire.butler@example.com'),
(74, 'Jaxon Simmons', '610 Fire St, Amsterdam', '+31 6 4568 1238', 'jaxon.simmons@example.com'),
(75, 'Paisley Foster', '620 Heat Dr, Rotterdam', '+31 6 5679 2349', 'paisley.foster@example.com'),
(76, 'Jonathan Gonzales', '630 Warm Rd, Utrecht', '+31 6 6780 3460', 'jonathan.gonzales@example.com'),
(77, 'Audrey Bryant', '640 Cool Ave, Eindhoven', '+31 6 7891 4571', 'audrey.bryant@example.com'),
(78, 'Cameron Alexander', '650 Cold St, Groningen', '+31 6 8902 5682', 'cameron.alexander@example.com'),
(79, 'Skylar Russell', '660 Ice Dr, Maastricht', '+31 6 9013 6783', 'skylar.russell@example.com'),
(80, 'Landon Griffin', '670 Frost Rd, Leiden', '+31 6 0124 7894', 'landon.griffin@example.com'),
(81, 'Anna Diaz', '680 Chill Ave, Delft', '+31 6 1235 8905', 'anna.diaz@example.com'),
(82, 'Colton Hayes', '690 Breeze St, Haarlem', '+31 6 2346 9016', 'colton.hayes@example.com'),
(83, 'Caroline Myers', '700 Gust Dr, Zwolle', '+31 6 3457 0127', 'caroline.myers@example.com'),
(84, 'Easton Ford', '710 Gale Rd, Arnhem', '+31 6 4568 1239', 'easton.ford@example.com'),
(85, 'Genesis Hamilton', '720 Blast Ave, Breda', '+31 6 5679 2350', 'genesis.hamilton@example.com'),
(86, 'Hudson Graham', '730 Rush St, Tilburg', '+31 6 6780 3461', 'hudson.graham@example.com'),
(87, 'Aaliyah Sullivan', '740 Flow Dr, Almere', '+31 6 7891 4572', 'aaliyah.sullivan@example.com'),
(88, 'Tristan Wallace', '750 Stream Rd, Nijmegen', '+31 6 8902 5683', 'tristan.wallace@example.com'),
(89, 'Kinsley Woods', '760 Tide Ave, Apeldoorn', '+31 6 9013 6784', 'kinsley.woods@example.com'),
(90, 'Austin West', '770 Wave St, Amersfoort', '+31 6 0124 7895', 'austin.west@example.com'),
(91, 'Madelyn Cole', '780 Surge Dr, Den Bosch', '+31 6 1235 8906', 'madelyn.cole@example.com'),
(92, 'Adrian Jordan', '790 Ripple Rd, Haarlemmermeer', '+31 6 2346 9017', 'adrian.jordan@example.com'),
(93, 'Ruby Owens', '800 Splash Ave, Zoetermeer', '+31 6 3457 0128', 'ruby.owens@example.com'),
(94, 'Jeremiah Reynolds', '810 Drop St, Leeuwarden', '+31 6 4568 1240', 'jeremiah.reynolds@example.com'),
(95, 'Autumn Fisher', '820 Pour Dr, Deventer', '+31 6 5679 2351', 'autumn.fisher@example.com'),
(96, 'Jason Ellis', '830 Drip Rd, Enschede', '+31 6 6780 3462', 'jason.ellis@example.com'),
(97, 'Piper Marshall', '840 Trickle Ave, Hengelo', '+31 6 7891 4573', 'piper.marshall@example.com'),
(98, 'Xavier Romero', '850 Spring St, Amsterdam', '+31 6 8902 5684', 'xavier.romero@example.com'),
(99, 'Extra Employee One', '900 Alpha Rd, ExampleCity', '+31 6 9010 0001', 'extra.one@example.com'),
(100, 'Extra Employee Two', '901 Beta St, ExampleTown', '+31 6 9010 0002', 'extra.two@example.com');

-- Insert sample data into Employee_Facility table (distribute employees across facilities)
REPLACE INTO Employee_Facility (employee_id, facility_id) VALUES
(1, 1), (1, 2), (2, 1), (2, 3), (3, 2), (3, 4), (4, 3), (4, 5),
(5, 4), (5, 6), (6, 5), (6, 7), (7, 6), (7, 8), (8, 7), (8, 9),
(9, 8), (9, 10), (10, 9), (10, 11), (11, 10), (11, 12), (12, 11), (12, 13),
(13, 12), (13, 14), (14, 13), (14, 15), (15, 14), (15, 16), (16, 15), (16, 17),
(17, 16), (17, 18), (18, 17), (18, 19), (19, 18), (19, 20), (20, 19), (20, 21),
(21, 20), (21, 22), (22, 21), (22, 23), (23, 22), (23, 24), (24, 23), (24, 25),
(25, 24), (25, 1), (26, 25), (26, 2), (27, 1), (27, 3), (28, 2), (28, 4),
(29, 3), (29, 5), (30, 4), (30, 6), (31, 5), (31, 7), (32, 6), (32, 8),
(33, 7), (33, 9), (34, 8), (34, 10), (35, 9), (35, 11), (36, 10), (36, 12),
(37, 11), (37, 13), (38, 12), (38, 14), (39, 13), (39, 15), (40, 14), (40, 16),
(41, 15), (41, 17), (42, 16), (42, 18), (43, 17), (43, 19), (44, 18), (44, 20),
(45, 19), (45, 21), (46, 20), (46, 22), (47, 21), (47, 23), (48, 22), (48, 24),
(49, 23), (49, 25), (50, 24), (50, 1);

REPLACE INTO Employee_Facility (employee_id, facility_id) VALUES
(51, 25), (51, 2), (52, 1), (52, 3),
(53, 2), (53, 4), (54, 3), (54, 5), (55, 4), (55, 6), (56, 5), (56, 7),
(57, 6), (57, 8), (58, 7), (58, 9), (59, 8), (59, 10), (60, 9), (60, 11),
(61, 10), (61, 12), (62, 11), (62, 13), (63, 12), (63, 14), (64, 13), (64, 15),
(65, 14), (65, 16), (66, 15), (66, 17), (67, 16), (67, 18), (68, 17), (68, 19),
(69, 18), (69, 20), (70, 19), (70, 21), (71, 20), (71, 22), (72, 21), (72, 23),
(73, 22), (73, 24), (74, 23), (74, 25), (75, 24), (75, 1);

REPLACE INTO Employee_Facility (employee_id, facility_id) VALUES
(76, 25), (76, 2),
(77, 1), (77, 3), (78, 2), (78, 4), (79, 3), (79, 5), (80, 4), (80, 6),
(81, 5), (81, 7), (82, 6), (82, 8), (83, 7), (83, 9), (84, 8), (84, 10),
(85, 9), (85, 11), (86, 10), (86, 12), (87, 11), (87, 13), (88, 12), (88, 14),
(89, 13), (89, 15), (90, 14), (90, 16), (91, 15), (91, 17), (92, 16), (92, 18),
(93, 17), (93, 19), (94, 18), (94, 20), (95, 19), (95, 21), (96, 20), (96, 22),
(97, 21), (97, 23), (98, 22), (98, 24), (99, 23), (99, 25), (100, 24), (100, 1);