-- REFERENCE ONLY: do not execute this SQL now.
-- TourismMalaysiaFestivalSeeder is the selected import method.
-- Tourism Malaysia events from 1 August 2026 onward.
-- Retained for dataset review and comparison with the Laravel seeder.
-- DO NOT run until the category preflight query returns all six categories used below.
-- Sources checked: https://www.malaysia.travel/events/2026/8 through /2026/12,
-- plus 2027 listings for the continuing Tatreez exhibition.

BEGIN;

-- Preflight: these names must already exist and belong to Festival.
-- If this returns fewer than seven rows, ROLLBACK and reconcile category names first.
SELECT c.category_name, et.type_name
FROM category AS c
JOIN experience_type AS et ON et.type_id = c.type_id
WHERE et.type_name = 'Festival'
  AND c.category_name IN (
    'Cultural Festival', 'Music Festival', 'Food Festival',
    'Sports Festival', 'Nature Festival',
    'National Celebration'
  )
ORDER BY c.category_name;

DO $preflight$
DECLARE
  matching_category_count integer;
BEGIN
  SELECT count(*)
  INTO matching_category_count
  FROM category AS c
  JOIN experience_type AS et ON et.type_id = c.type_id
  WHERE et.type_name = 'Festival'
    AND c.category_name IN (
      'Cultural Festival', 'Music Festival', 'Food Festival',
      'Sports Festival', 'Nature Festival',
      'National Celebration'
    );

  IF matching_category_count <> 6 THEN
    RAISE EXCEPTION
      'Preflight failed: expected 6 Festival categories used by this script, found %',
      matching_category_count;
  END IF;
END
$preflight$;

-- The repository has no unique constraint suitable for ON CONFLICT.
-- Duplicate protection therefore compares normalized title, dates, and location.
WITH event_rows (
  source_month, source_url, official_category, category_name,
  experiences_name, description, location_name, price, duration,
  start_date, end_date, operating_hours, contact_number
) AS (
  VALUES
  -- AUGUST 2026: https://www.malaysia.travel/events/2026/8
  ('2026-08', 'https://www.malaysia.travel/events/tatreez-reclaiming-palestine-through-embroidery', 'Arts & Culture', 'Cultural Festival', 'Tatreez : Reclaiming Palestine Through Embroidery', 'Tatreez is the Arabic word for embroidery, a visual language used by Palestinian women across generations. The exhibition presents traditional regional styles and contemporary work while preserving Palestinian textile heritage.', 'Special Gallery 1 & 2, Islamic Arts Museum Malaysia', NULL, NULL, DATE '2026-06-19', DATE '2027-04-25', NULL, '0320927114'),
  ('2026-08', 'https://www.malaysia.travel/events/festival-kraf-utara-2026', 'Arts & Culture; Festival', 'Cultural Festival', 'Festival Kraf Utara 2026', 'A northern craft festival featuring Malaysian heritage products, live craft demonstrations, interactive activities and local craft entrepreneurs.', 'Kraftangan Malaysia Perlis Branch', 0.00, NULL, DATE '2026-07-23', DATE '2026-08-09', '10:00-22:00 daily', '04-985 5278'),
  ('2026-08', 'https://www.malaysia.travel/events/2026/8', 'VM2026 Signature Events; Arts & Culture; Entertainment; Foods & Drinks; Music', 'Cultural Festival', 'Temasya Orang Kedah', NULL, 'Perkarangan Stadium Darul Aman, Alor Setar, Kedah', NULL, NULL, DATE '2026-07-30', DATE '2026-08-02', NULL, NULL),
  ('2026-08', 'https://www.malaysia.travel/events/2026/8', 'Arts & Culture; Entertainment; Music', 'Cultural Festival', 'Pesta Kuantan 188', NULL, 'Kuantan', NULL, NULL, DATE '2026-07-31', DATE '2026-08-02', NULL, NULL),
  ('2026-08', 'https://www.malaysia.travel/events/art-of-speed', 'VM2026 Signature Events; Arts & Culture', 'Cultural Festival', 'Art Of Speed', 'An annual event featuring artwork in canvas, poster, moving visual, sculpture and rolling-art formats.', 'MAEPS, Serdang, Selangor', NULL, NULL, DATE '2026-08-01', DATE '2026-08-02', NULL, '+6012 262 0405'),
  ('2026-08', 'https://www.malaysia.travel/events/2026/8', 'Sports', 'Sports Festival', 'Youth Performance Speed Fest', NULL, 'Puteri Harbour, Iskandar Puteri, Johor', NULL, NULL, DATE '2026-08-07', DATE '2026-08-09', NULL, NULL),
  ('2026-08', 'https://www.malaysia.travel/events/2026/8', 'Sports', 'Sports Festival', 'Taiping Half Marathon', NULL, 'Dataran Warisan Taiping', NULL, NULL, DATE '2026-08-09', DATE '2026-08-09', NULL, NULL),
  ('2026-08', 'https://www.malaysia.travel/events/2026/8', 'Nature; Sports', 'Nature Festival', 'Penang Hill Heritage Forest Challenge 2026 (PHHFC2026)', NULL, 'Penang Hill', NULL, NULL, DATE '2026-08-16', DATE '2026-08-16', NULL, NULL),
  ('2026-08', 'https://www.malaysia.travel/events/2026/8', 'Sports', 'Sports Festival', 'Pahang Eco 2026', NULL, 'Teluk Cempedak, Kuantan', NULL, NULL, DATE '2026-08-21', DATE '2026-08-23', NULL, NULL),
  ('2026-08', 'https://www.malaysia.travel/events/2026/8', 'Entertainment; Music', 'Music Festival', 'A Heart Unveiled: The Music of Tchaikovsky', NULL, 'Dewan Filharmonik PETRONAS', NULL, NULL, DATE '2026-08-22', DATE '2026-08-22', NULL, NULL),
  ('2026-08', 'https://www.malaysia.travel/events/2026/8', 'Sports', 'Sports Festival', 'The Sky Race', NULL, 'Merdeka 118', NULL, NULL, DATE '2026-08-22', DATE '2026-08-23', NULL, NULL),
  ('2026-08', 'https://www.malaysia.travel/events/2026/8', 'VM2026 Signature Events; Entertainment; Music', 'Music Festival', 'Kodaline – Farewell Tour', NULL, 'Idea Live Arena, W.P Kuala Lumpur', NULL, NULL, DATE '2026-08-26', DATE '2026-08-26', NULL, NULL),
  ('2026-08', 'https://www.malaysia.travel/events/2026/8', 'Entertainment; Music', 'Music Festival', 'A Knight''s Tale: Valor and Romance in Music', NULL, 'Dewan Filharmonik PETRONAS', NULL, NULL, DATE '2026-08-29', DATE '2026-08-29', NULL, NULL),
  ('2026-08', 'https://www.malaysia.travel/events/2026/8', 'Sports', 'Sports Festival', 'Bukit Maras Trail Run Challenge 2.0', NULL, 'Bukit Maras, Kuala Nerus, Terengganu', NULL, NULL, DATE '2026-08-29', DATE '2026-08-29', NULL, NULL),
  ('2026-08', 'https://www.malaysia.travel/events/2026/8', 'VM2026 Signature Events; Sports', 'Sports Festival', 'Malaysia Marathon 2026', NULL, 'Pavilion KL to KLCC', NULL, NULL, DATE '2026-08-30', DATE '2026-08-30', NULL, NULL),
  ('2026-08', 'https://www.malaysia.travel/events/2026/8', 'Festival; Music', 'National Celebration', 'Merdeka Day', NULL, 'All over Malaysia', NULL, NULL, DATE '2026-08-31', DATE '2026-08-31', NULL, NULL),

  -- SEPTEMBER 2026: https://www.malaysia.travel/events/2026/9
  ('2026-09', 'https://www.malaysia.travel/events/2026/9', 'Entertainment; Music', 'Music Festival', 'A Tribute to Alfonso Soliano', NULL, 'Dewan Filharmonik PETRONAS', NULL, NULL, DATE '2026-09-02', DATE '2026-09-02', NULL, NULL),
  ('2026-09', 'https://www.malaysia.travel/events/2026/9', 'Entertainment; Festival; Music', 'Music Festival', 'Sepilok Jazz Festival', NULL, 'Rainforest Discovery Centre (RDC), Sepilok, Sandakan, Sabah', NULL, NULL, DATE '2026-09-04', DATE '2026-09-05', NULL, NULL),
  ('2026-09', 'https://www.malaysia.travel/events/2026/9', 'Entertainment; Music', 'Music Festival', 'Yin and Yang: A Dance Kaleidoscope', NULL, 'Dewan Filharmonik PETRONAS', NULL, NULL, DATE '2026-09-05', DATE '2026-09-05', NULL, NULL),
  ('2026-09', 'https://www.malaysia.travel/events/2026/9', 'Sports', 'Sports Festival', 'PJ Half Marathon', NULL, 'Laman MBPJ, Petaling Jaya', NULL, NULL, DATE '2026-09-06', DATE '2026-09-06', NULL, NULL),
  ('2026-09', 'https://www.malaysia.travel/events/2026/9', 'Sports', 'Sports Festival', 'Malaysia Ultra-Trail By Utmb', NULL, 'Dataran Warisan Taiping, Perak', NULL, NULL, DATE '2026-09-10', DATE '2026-09-13', NULL, NULL),
  ('2026-09', 'https://www.malaysia.travel/events/2026/9', 'Entertainment; Music', 'Music Festival', 'Maurice Steger''s Nature Concerti', NULL, 'Dewan Filharmonik PETRONAS', NULL, NULL, DATE '2026-09-12', DATE '2026-09-12', NULL, NULL),
  ('2026-09', 'https://www.malaysia.travel/events/2026/9', 'Sports', 'Sports Festival', 'RHB Lekas Highway Ride 2026', NULL, 'Kajang Selatan Toll Plaza/Lekas Highway', NULL, NULL, DATE '2026-09-12', DATE '2026-09-12', NULL, NULL),
  ('2026-09', 'https://www.malaysia.travel/events/keretapi-sarong-2026', 'VM2026 Signature Events; Arts & Culture', 'Cultural Festival', 'Keretapi Sarong 2026', 'A nationwide cultural gathering themed Ethnicity, featuring traditional dance, music and storytelling that celebrates Malaysia''s multicultural identity.', 'Klang Valley, Johor Bahru, Ipoh, Pasir Mas, Kuantan, Sungai Petani', NULL, NULL, DATE '2026-09-16', DATE '2026-09-16', NULL, NULL),
  ('2026-09', 'https://www.malaysia.travel/events/2026/9', 'VM2026 Signature Events; Sports', 'Sports Festival', 'Powerman Malaysia 2026', NULL, 'Dataran Putrajaya', NULL, NULL, DATE '2026-09-18', DATE '2026-09-20', NULL, NULL),
  ('2026-09', 'https://www.malaysia.travel/events/2026/9', 'VM2026 Signature Events; Sports', 'Sports Festival', 'Malaysia Sarong Music Run 2026', NULL, 'KLCC', NULL, NULL, DATE '2026-09-19', DATE '2026-09-19', NULL, NULL),
  ('2026-09', 'https://www.malaysia.travel/events/2026/9', 'VM2026 Signature Events; Arts & Culture', 'Cultural Festival', 'Malaysia International Craft Fair', NULL, 'Kuala Lumpur Craft Complex', NULL, NULL, DATE '2026-09-24', DATE '2026-10-05', NULL, NULL),
  ('2026-09', 'https://www.malaysia.travel/events/2026/9', 'VM2026 Signature Events; Foods & Drinks', 'Food Festival', 'VM2026 Food Festival @ MATIC', NULL, 'Malaysia Tourism Centre (MaTiC)', NULL, NULL, DATE '2026-09-25', DATE '2026-09-27', NULL, NULL),

  -- OCTOBER 2026: https://www.malaysia.travel/events/2026/10
  ('2026-10', 'https://www.malaysia.travel/events/2026/10', 'Entertainment; Music', 'Music Festival', 'Three By Three', NULL, 'Dewan Filharmonik PETRONAS', NULL, NULL, DATE '2026-10-03', DATE '2026-10-03', NULL, NULL),
  ('2026-10', 'https://www.malaysia.travel/events/2026/10', 'Entertainment; Music', 'Music Festival', 'The Music of Queen…Lives On!', NULL, 'Dewan Filharmonik PETRONAS', NULL, NULL, DATE '2026-10-10', DATE '2026-10-10', NULL, NULL),
  ('2026-10', 'https://www.malaysia.travel/events/2026/10', 'Entertainment; Music', 'Music Festival', 'Jaclyn Victor Gemilang Bersama MPO', NULL, 'Dewan Filharmonik PETRONAS', NULL, NULL, DATE '2026-10-17', DATE '2026-10-17', NULL, NULL),
  ('2026-10', 'https://www.malaysia.travel/events/2026/10', 'Sports', 'Sports Festival', 'Challenge Malaysia', NULL, 'Forest City, Johor', NULL, NULL, DATE '2026-10-17', DATE '2026-10-18', NULL, NULL),
  ('2026-10', 'https://www.malaysia.travel/events/2026/10', 'Sports', 'Sports Festival', 'KOKOL Ultra 2026', NULL, 'Expertise Event Management', NULL, NULL, DATE '2026-10-23', DATE '2026-10-25', NULL, NULL),
  ('2026-10', 'https://www.malaysia.travel/events/2026/10', 'Arts & Culture; Sports', 'Sports Festival', 'Sarawak International Dragon Boat Regatta 2026', NULL, 'Tebingan Sungai Kuching', NULL, NULL, DATE '2026-10-24', DATE '2026-10-26', NULL, NULL),
  ('2026-10', 'https://www.malaysia.travel/events/a-regal-evening-with-stephen-hough', 'Entertainment; Music', 'Music Festival', 'A Regal Evening with Stephen Hough', 'An all-Beethoven concert with the Malaysian Philharmonic Orchestra, conductor Junichi Hirokami and pianist Stephen Hough.', 'Dewan Filharmonik PETRONAS', NULL, NULL, DATE '2026-10-24', DATE '2026-10-24', '20:00', '03 23317007'),
  ('2026-10', 'https://www.malaysia.travel/events/2026/10', 'VM2026 Signature Events; Sports', 'Sports Festival', 'Petronas Grand Prix of Malaysia (MotoGP)', NULL, 'Sepang International Circuit, Sepang', NULL, NULL, DATE '2026-10-30', DATE '2026-11-01', NULL, NULL),
  ('2026-10', 'https://www.malaysia.travel/events/2026/10', 'Entertainment; Music', 'Music Festival', 'Beats of Borneo: Alena Murang with the MPO', NULL, 'Dewan Filharmonik PETRONAS', NULL, NULL, DATE '2026-10-31', DATE '2026-10-31', NULL, NULL),

  -- NOVEMBER 2026: https://www.malaysia.travel/events/2026/11
  ('2026-11', 'https://www.malaysia.travel/events/2026/11', 'Sports', 'Sports Festival', 'Selangor Marathon 2026', NULL, 'Persiaran Flora, Cyberjaya', NULL, NULL, DATE '2026-11-01', DATE '2026-11-01', NULL, NULL),
  ('2026-11', 'https://www.malaysia.travel/events/2026/11', 'VM2026 Signature Events; Entertainment; Music', 'Music Festival', 'LANY : Soft World Tour', NULL, 'Unifi Arena, Bukit Jalil, W.P Kuala Lumpur', NULL, NULL, DATE '2026-11-01', DATE '2026-11-01', NULL, NULL),
  ('2026-11', 'https://www.malaysia.travel/events/2026/11', 'Entertainment; Music', 'Music Festival', 'As If She Were Here: Chen Jia Sings Teresa Teng', NULL, 'Dewan Filharmonik PETRONAS', NULL, NULL, DATE '2026-11-07', DATE '2026-11-29', NULL, NULL),
  ('2026-11', 'https://www.malaysia.travel/events/2026/11', 'Festival', 'National Celebration', 'Deepavali', NULL, 'All over Malaysia', NULL, NULL, DATE '2026-11-08', DATE '2026-11-08', NULL, NULL),
  ('2026-11', 'https://www.malaysia.travel/events/2026/11', 'Sports', 'Sports Festival', 'The 36th Raja Muda Selangor International Regatta 2026', NULL, 'Port Klang, Pangkor Island, Penang & Langkawi', NULL, NULL, DATE '2026-11-13', DATE '2026-11-21', NULL, NULL),
  ('2026-11', 'https://www.malaysia.travel/events/2026/11', 'Entertainment; Music', 'Music Festival', 'Simfoni Mantra: Kunto Aji bersama MPO', NULL, 'Dewan Filharmonik PETRONAS', NULL, NULL, DATE '2026-11-14', DATE '2026-11-14', NULL, NULL),
  ('2026-11', 'https://www.malaysia.travel/events/2026/11', 'Arts & Culture; Sports', 'Sports Festival', 'Penang International Dragon Boat Regatta 2026', NULL, 'Straits Quay Marina Waterfront, Tanjung Tokong, Penang', NULL, NULL, DATE '2026-11-20', DATE '2026-11-22', NULL, NULL),
  ('2026-11', 'https://www.malaysia.travel/events/2026/11', 'Sports', 'Sports Festival', 'Ironman 70.3 Langkawi', NULL, 'Langkawi, Kedah', NULL, NULL, DATE '2026-11-21', DATE '2026-11-21', NULL, NULL),
  ('2026-11', 'https://www.malaysia.travel/events/2026/11', 'VM2026 Signature Events; Entertainment; Music', 'Music Festival', 'Wave to Earth', NULL, 'Idea Live Arena, W.P Kuala Lumpur', NULL, NULL, DATE '2026-11-22', DATE '2026-11-22', NULL, NULL),
  ('2026-11', 'https://www.malaysia.travel/events/2026/11', 'Sports', 'Sports Festival', 'Malaysia World Masters Golf Championship 2026', NULL, 'Klang Valley', NULL, NULL, DATE '2026-11-22', DATE '2026-11-28', NULL, NULL),

  -- DECEMBER 2026: https://www.malaysia.travel/events/2026/12
  ('2026-12', 'https://www.malaysia.travel/events/2026/12', 'Entertainment; Music', 'Music Festival', 'A Chorale Spectacular', NULL, 'Dewan Filharmonik PETRONAS', NULL, NULL, DATE '2026-12-05', DATE '2026-12-05', NULL, NULL),
  ('2026-12', 'https://www.malaysia.travel/events/2026/12', 'VM2026 Signature Events; Entertainment; Sports', 'Sports Festival', 'Sibu Bike Week', NULL, 'Dataran Tun Tuanku Bujang Phase 2', NULL, NULL, DATE '2026-12-05', DATE '2026-12-06', NULL, NULL),
  ('2026-12', 'https://www.malaysia.travel/events/2026/12', 'Sports', 'Nature Festival', 'Nakawan Ultra 3.0', NULL, 'Bukit Air Resort, Kompleks Eko Pelancongan, Sungai Batu Pahat, Perlis', NULL, NULL, DATE '2026-12-11', DATE '2026-12-12', NULL, NULL),
  ('2026-12', 'https://www.malaysia.travel/events/2026/12', 'VM2026 Signature Events; Sports', 'Sports Festival', 'Penang Bridge International Marathon', NULL, 'Batu Kawan Stadium', NULL, NULL, DATE '2026-12-13', DATE '2026-12-13', NULL, NULL),
  ('2026-12', 'https://www.malaysia.travel/events/2026/12', 'VM2026 Signature Events; Entertainment; Music', 'Music Festival', 'BTS World Tour ‘Arirang’ in Kuala Lumpur', NULL, 'Stadium Nasional, Bukit Jalil', NULL, NULL, DATE '2026-12-13', DATE '2026-12-13', NULL, NULL),
  ('2026-12', 'https://www.malaysia.travel/events/2026/12', 'Festival', 'National Celebration', 'Christmas Celebration', NULL, 'All over Malaysia', NULL, NULL, DATE '2026-12-25', DATE '2026-12-25', NULL, NULL)
),
resolved_rows AS (
  SELECT e.*, et.type_id, c.category_id
  FROM event_rows AS e
  JOIN experience_type AS et ON et.type_name = 'Festival'
  JOIN category AS c
    ON c.category_name = e.category_name
   AND c.type_id = et.type_id
)
INSERT INTO experiences (
  experiences_name, description, location_name, image_url, price, duration,
  created_at, start_date, end_date, operating_hours, type_id, category_id,
  latitude, longitude, contact_number, status, updated_at
)
SELECT
  r.experiences_name, r.description, r.location_name, NULL, r.price, r.duration,
  CURRENT_TIMESTAMP, r.start_date, r.end_date, r.operating_hours,
  r.type_id, r.category_id, NULL, NULL, r.contact_number, 'Available', CURRENT_TIMESTAMP
FROM resolved_rows AS r
WHERE NOT EXISTS (
  SELECT 1
  FROM experiences AS existing
  WHERE lower(regexp_replace(trim(existing.experiences_name), '[[:punct:]\s]+', ' ', 'g'))
      = lower(regexp_replace(trim(r.experiences_name), '[[:punct:]\s]+', ' ', 'g'))
    AND existing.start_date IS NOT DISTINCT FROM r.start_date
    AND existing.end_date IS NOT DISTINCT FROM r.end_date
    AND lower(regexp_replace(trim(COALESCE(existing.location_name, '')), '[[:punct:]\s]+', ' ', 'g'))
      = lower(regexp_replace(trim(COALESCE(r.location_name, '')), '[[:punct:]\s]+', ' ', 'g'))
);

COMMIT;

-- Verification only: review matching Tourism Malaysia rows after execution.
SELECT
  e.experiences_id,
  e.experiences_name,
  e.start_date,
  e.end_date,
  e.location_name,
  et.type_name,
  c.category_name,
  e.status
FROM experiences AS e
JOIN experience_type AS et ON et.type_id = e.type_id
JOIN category AS c ON c.category_id = e.category_id
WHERE et.type_name = 'Festival'
  AND e.start_date >= DATE '2026-06-19'
  AND e.experiences_name IN (
    'Tatreez : Reclaiming Palestine Through Embroidery',
    'Festival Kraf Utara 2026', 'Temasya Orang Kedah', 'Pesta Kuantan 188',
    'Art Of Speed', 'Youth Performance Speed Fest', 'Taiping Half Marathon',
    'Penang Hill Heritage Forest Challenge 2026 (PHHFC2026)', 'Pahang Eco 2026',
    'A Heart Unveiled: The Music of Tchaikovsky', 'The Sky Race',
    'Kodaline – Farewell Tour', 'A Knight''s Tale: Valor and Romance in Music',
    'Bukit Maras Trail Run Challenge 2.0', 'Malaysia Marathon 2026', 'Merdeka Day',
    'A Tribute to Alfonso Soliano', 'Sepilok Jazz Festival',
    'Yin and Yang: A Dance Kaleidoscope', 'PJ Half Marathon',
    'Malaysia Ultra-Trail By Utmb', 'Maurice Steger''s Nature Concerti',
    'RHB Lekas Highway Ride 2026', 'Keretapi Sarong 2026', 'Powerman Malaysia 2026',
    'Malaysia Sarong Music Run 2026', 'Malaysia International Craft Fair',
    'VM2026 Food Festival @ MATIC', 'Three By Three', 'The Music of Queen…Lives On!',
    'Jaclyn Victor Gemilang Bersama MPO', 'Challenge Malaysia', 'KOKOL Ultra 2026',
    'Sarawak International Dragon Boat Regatta 2026',
    'A Regal Evening with Stephen Hough', 'Petronas Grand Prix of Malaysia (MotoGP)',
    'Beats of Borneo: Alena Murang with the MPO', 'Selangor Marathon 2026',
    'LANY : Soft World Tour', 'As If She Were Here: Chen Jia Sings Teresa Teng',
    'Deepavali', 'The 36th Raja Muda Selangor International Regatta 2026',
    'Simfoni Mantra: Kunto Aji bersama MPO',
    'Penang International Dragon Boat Regatta 2026', 'Ironman 70.3 Langkawi',
    'Wave to Earth', 'Malaysia World Masters Golf Championship 2026',
    'A Chorale Spectacular', 'Sibu Bike Week', 'Nakawan Ultra 3.0',
    'Penang Bridge International Marathon', 'BTS World Tour ‘Arirang’ in Kuala Lumpur',
    'Christmas Celebration'
  )
ORDER BY e.start_date, e.experiences_name;
