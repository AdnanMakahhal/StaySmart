USE staysmart;
SET NAMES utf8mb4;

-- Existing room names are left unchanged when this file is imported again.
START TRANSACTION;

INSERT INTO rooms (room_name, price, size, capacity, adult, kids, facilities, beds, rating, quantity, image, description, availability, is_visible, is_open)
SELECT
  'Tropical Garden Deluxe Room', 145.00, '36 m²', 3, 2, 1,
  'Free Wi-Fi, Air Conditioning, Garden View, Private Bathroom, Smart TV, Mini Fridge, Coffee Maker',
  '1 King Bed + 1 Extra Bed', 4.70, 8, 'our-room-5.jpg',
  'A warm, peaceful room with natural wood finishes, soft lighting, and a beautiful private garden outlook.',
  'available', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM rooms WHERE room_name = 'Tropical Garden Deluxe Room');

INSERT INTO rooms (room_name, price, size, capacity, adult, kids, facilities, beds, rating, quantity, image, description, availability, is_visible, is_open)
SELECT
  'City Skyline King Room', 165.00, '32 m²', 2, 2, 0,
  'Free Wi-Fi, Air Conditioning, City View, Smart TV, Private Bathroom, Blackout Curtains, Mini Bar',
  '1 King Bed', 4.60, 10, 'our-room-6.jpg',
  'A contemporary city room featuring oversized windows, sunset views, and comfortable bedding.',
  'available', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM rooms WHERE room_name = 'City Skyline King Room');

INSERT INTO rooms (room_name, price, size, capacity, adult, kids, facilities, beds, rating, quantity, image, description, availability, is_visible, is_open)
SELECT
  'Desert Glass Dome Suite', 310.00, '42 m²', 2, 2, 0,
  'Free Wi-Fi, Air Conditioning, Panoramic Desert View, Private Bathroom, Outdoor Terrace, Seating Area',
  '1 King Bed', 4.90, 2, 'our-room-7.jpg',
  'A unique glamping experience inside a panoramic dome, surrounded by breathtaking desert mountains.',
  'available', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM rooms WHERE room_name = 'Desert Glass Dome Suite');

INSERT INTO rooms (room_name, price, size, capacity, adult, kids, facilities, beds, rating, quantity, image, description, availability, is_visible, is_open)
SELECT
  'Modern Executive Suite', 200.00, '55 m²', 3, 2, 1,
  'Free Wi-Fi, Air Conditioning, Smart TV, Walk-in Closet, Private Bathroom, Work Desk, Coffee Maker',
  '1 King Bed + 1 Extra Bed', 4.80, 5, 'our-room-9.jpg',
  'An elegant executive suite with sophisticated neutral colors, spacious interiors, and a luxurious contemporary design.',
  'available', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM rooms WHERE room_name = 'Modern Executive Suite');

COMMIT;
