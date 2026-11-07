USE staysmart;
SET NAMES utf8mb4;

-- Existing rooms with these names are preserved when importing again.
START TRANSACTION;

INSERT INTO rooms (room_name, price, size, capacity, adult, kids, facilities, beds, rating, quantity, image, description, availability, is_visible, is_open)
SELECT
  'Contemporary Comfort Room', 135.00, '36 m²', 3, 2, 1,
  'Free Wi-Fi, Air Conditioning, Smart TV, Private Bathroom, Seating Area, Room Service, Mini Fridge',
  '1 Queen Bed + 1 Extra Bed', 4.50, 12, 'our-room-10.jpg',
  'A bright, comfortable room featuring natural wood accents, modern furniture, and soft ambient lighting.',
  'available', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM rooms WHERE room_name = 'Contemporary Comfort Room');

INSERT INTO rooms (room_name, price, size, capacity, adult, kids, facilities, beds, rating, quantity, image, description, availability, is_visible, is_open)
SELECT
  'Signature Wood Suite', 195.00, '46 m²', 2, 2, 0,
  'Free Wi-Fi, Air Conditioning, Smart TV, Private Bathroom, Wardrobe, Lounge Bench, Coffee Maker',
  '1 King Bed', 4.80, 4, 'our-room-11.jpg',
  'A premium suite combining beautiful wooden wall panels, elegant lighting, and contemporary luxury.',
  'available', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM rooms WHERE room_name = 'Signature Wood Suite');

INSERT INTO rooms (room_name, price, size, capacity, adult, kids, facilities, beds, rating, quantity, image, description, availability, is_visible, is_open)
SELECT
  'Bright Deluxe Double Room', 150.00, '40 m²', 3, 2, 1,
  'Free Wi-Fi, Air Conditioning, Smart TV, Private Bathroom, Large Windows, Seating Area, Wardrobe',
  '1 Queen Bed + 1 Extra Bed', 4.60, 7, 'our-room-12.jpg',
  'An airy deluxe room with beautiful natural light, soft fabrics, and relaxing neutral tones.',
  'available', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM rooms WHERE room_name = 'Bright Deluxe Double Room');

INSERT INTO rooms (room_name, price, size, capacity, adult, kids, facilities, beds, rating, quantity, image, description, availability, is_visible, is_open)
SELECT
  'Cozy Standard Queen Room', 99.00, '25 m²', 2, 2, 0,
  'Free Wi-Fi, Air Conditioning, Smart TV, Private Bathroom, Bedside Lamp, Wardrobe',
  '1 Queen Bed', 4.30, 14, 'our-room-13.jpg',
  'A simple, welcoming room with comfortable bedding and minimalist furniture, ideal for a relaxing stay.',
  'available', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM rooms WHERE room_name = 'Cozy Standard Queen Room');

COMMIT;
