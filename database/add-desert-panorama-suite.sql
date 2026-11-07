USE staysmart;
SET NAMES utf8mb4;

-- Preserve existing rooms and add description storage only when missing.
SET @description_sql = IF(
  EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'rooms' AND column_name = 'description'
  ),
  'SELECT 1',
  'ALTER TABLE rooms ADD COLUMN description TEXT NULL'
);
PREPARE description_statement FROM @description_sql;
EXECUTE description_statement;
DEALLOCATE PREPARE description_statement;

-- Rerunning this script does not create a duplicate.
INSERT INTO rooms (
  room_name, price, size, capacity, adult, kids, facilities,
  beds, rating, quantity, image, description, availability, is_visible, is_open
)
SELECT
  'Desert Panorama Suite', 220.00, '65 m²', 3, 2, 1,
  'Free Wi-Fi, Air Conditioning, Private Bathroom, Mountain View, Seating Area, Smart TV, Mini Fridge',
  '1 King Bed + 1 Sofa Bed', 4.80, 3, 'our-room-3.jpg',
  'A spacious desert retreat with floor-to-ceiling windows, natural wood furnishings, and panoramic views of the surrounding rocky landscape.',
  'available', 1, 1
WHERE NOT EXISTS (
  SELECT 1 FROM rooms WHERE room_name = 'Desert Panorama Suite'
);

UPDATE rooms SET image = 'our-room-3.jpg'
WHERE room_name = 'Desert Panorama Suite';
