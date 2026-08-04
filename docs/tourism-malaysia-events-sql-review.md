# Tourism Malaysia events SQL review

This document reviews the 53 records prepared for `database/seeders/TourismMalaysiaFestivalSeeder.php`. The Laravel seeder is the chosen import method. The reference SQL in `database/sql/tourism-malaysia-events-from-2026-08.sql` was not executed and is not intended to be executed now.

Only one designated team member should run the seeder after the team has reviewed the dataset and compared it with existing Supabase rows. Existing Experience records are preserved: the seeder never updates or deletes them, and it skips matches using normalized title plus exact start and end dates. Location is intentionally excluded because venue wording may differ or be missing for the same official event.

That designated teammate must run both seeders against the shared Supabase database, in this order and only after review:

```powershell
php artisan db:seed --class="Database\Seeders\FestivalCategorySeeder"
php artisan db:seed --class="Database\Seeders\TourismMalaysiaFestivalSeeder"
```

Do not have multiple teammates run these imports concurrently.

## Schema and mapping assumptions

- Every row resolves `experience_type.type_id` using `type_name = 'Festival'`.
- Every category resolves by `category.category_name` and is additionally required to belong to the Festival type.
- The repository does not version the category rows themselves. Before execution, the preflight query must return all six names used by this script: Cultural Festival, Music Festival, Food Festival, Sports Festival, Nature Festival, and National Celebration.
- The current `experiences` schema has no source URL, organizer, external ID, website, address, city, state, average-rating, or review-count columns. Source and review metadata therefore remain in this document and SQL comments rather than being inserted.
- Latitude and longitude remain `NULL`; no coordinate was asserted without a verified match.
- Images remain `NULL` because no stable official image file URL was captured.
- The shared `experiences.description` column rejected `NULL` during the first seeder attempt. The main seeder now contains supplied summaries for 36 of its existing event titles. The remaining 17 events transparently use `Description not provided by Tourism Malaysia.` The failed attempt rolled back completely before the fallback was added.
- Five additional supplied title/description pairs are not part of the current 53-record dataset and were not added without complete record data: The Batik Linut Heritage Art Workshop; Labuan F&B Tour: Semenanjung Vendor Trip to Labuan F.T.; Labuan Bird Fest 2026; International Tourism, Arts & Culture Exhibition & Conference 2026 (INTAC 2026); and The Heritage Ride Taiping 2026.

## Event review table

| Event | Start | End | Venue/location | Local category | Official source | Missing information |
|---|---|---|---|---|---|---|
| Tatreez : Reclaiming Palestine Through Embroidery | 2026-06-19 | 2027-04-25 | Special Gallery 1 & 2, Islamic Arts Museum Malaysia | Cultural Festival | https://www.malaysia.travel/events/tatreez-reclaiming-palestine-through-embroidery | price, hours, image, coordinates |
| Festival Kraf Utara 2026 | 2026-07-23 | 2026-08-09 | Kraftangan Malaysia Perlis Branch | Cultural Festival | https://www.malaysia.travel/events/festival-kraf-utara-2026 | image, coordinates |
| Temasya Orang Kedah | 2026-07-30 | 2026-08-02 | Perkarangan Stadium Darul Aman, Alor Setar, Kedah | Cultural Festival | https://www.malaysia.travel/events/2026/8 | description, organizer, contact, price, hours, image, coordinates |
| Pesta Kuantan 188 | 2026-07-31 | 2026-08-02 | Kuantan | Cultural Festival | https://www.malaysia.travel/events/2026/8 | description, organizer, contact, price, hours, image, coordinates |
| Art Of Speed | 2026-08-01 | 2026-08-02 | MAEPS, Serdang, Selangor | Cultural Festival | https://www.malaysia.travel/events/art-of-speed | organizer, price, hours, image, coordinates |
| Youth Performance Speed Fest | 2026-08-07 | 2026-08-09 | Puteri Harbour, Iskandar Puteri, Johor | Sports Festival | https://www.malaysia.travel/events/2026/8 | description, organizer, contact, price, hours, image, coordinates |
| Taiping Half Marathon | 2026-08-09 | 2026-08-09 | Dataran Warisan Taiping | Sports Festival | https://www.malaysia.travel/events/2026/8 | description, organizer, contact, price, hours, image, coordinates |
| Penang Hill Heritage Forest Challenge 2026 (PHHFC2026) | 2026-08-16 | 2026-08-16 | Penang Hill | Nature Festival | https://www.malaysia.travel/events/2026/8 | description, organizer, contact, price, hours, image, coordinates |
| Pahang Eco 2026 | 2026-08-21 | 2026-08-23 | Teluk Cempedak, Kuantan | Sports Festival | https://www.malaysia.travel/events/2026/8 | description, organizer, contact, price, hours, image, coordinates |
| A Heart Unveiled: The Music of Tchaikovsky | 2026-08-22 | 2026-08-22 | Dewan Filharmonik PETRONAS | Music Festival | https://www.malaysia.travel/events/2026/8 | description, organizer, contact, price, hours, image, coordinates |
| The Sky Race | 2026-08-22 | 2026-08-23 | Merdeka 118 | Sports Festival | https://www.malaysia.travel/events/2026/8 | description, organizer, contact, price, hours, image, coordinates |
| Kodaline – Farewell Tour | 2026-08-26 | 2026-08-26 | Idea Live Arena, W.P Kuala Lumpur | Music Festival | https://www.malaysia.travel/events/2026/8 | description, organizer, contact, price, hours, image, coordinates |
| A Knight's Tale: Valor and Romance in Music | 2026-08-29 | 2026-08-29 | Dewan Filharmonik PETRONAS | Music Festival | https://www.malaysia.travel/events/2026/8 | description, organizer, contact, price, hours, image, coordinates |
| Bukit Maras Trail Run Challenge 2.0 | 2026-08-29 | 2026-08-29 | Bukit Maras, Kuala Nerus, Terengganu | Sports Festival | https://www.malaysia.travel/events/2026/8 | description, organizer, contact, price, hours, image, coordinates |
| Malaysia Marathon 2026 | 2026-08-30 | 2026-08-30 | Pavilion KL to KLCC | Sports Festival | https://www.malaysia.travel/events/2026/8 | description, organizer, contact, price, hours, image, coordinates |
| Merdeka Day | 2026-08-31 | 2026-08-31 | All over Malaysia | National Celebration | https://www.malaysia.travel/events/2026/8 | description, organizer, contact, price, hours, image, coordinates |
| A Tribute to Alfonso Soliano | 2026-09-02 | 2026-09-02 | Dewan Filharmonik PETRONAS | Music Festival | https://www.malaysia.travel/events/2026/9 | description, organizer, contact, price, hours, image, coordinates |
| Sepilok Jazz Festival | 2026-09-04 | 2026-09-05 | Rainforest Discovery Centre, Sepilok, Sandakan, Sabah | Music Festival | https://www.malaysia.travel/events/2026/9 | description, organizer, contact, price, hours, image, coordinates |
| Yin and Yang: A Dance Kaleidoscope | 2026-09-05 | 2026-09-05 | Dewan Filharmonik PETRONAS | Music Festival | https://www.malaysia.travel/events/2026/9 | description, organizer, contact, price, hours, image, coordinates |
| PJ Half Marathon | 2026-09-06 | 2026-09-06 | Laman MBPJ, Petaling Jaya | Sports Festival | https://www.malaysia.travel/events/2026/9 | description, organizer, contact, price, hours, image, coordinates |
| Malaysia Ultra-Trail By Utmb | 2026-09-10 | 2026-09-13 | Dataran Warisan Taiping, Perak | Sports Festival | https://www.malaysia.travel/events/2026/9 | description, organizer, contact, price, hours, image, coordinates |
| Maurice Steger's Nature Concerti | 2026-09-12 | 2026-09-12 | Dewan Filharmonik PETRONAS | Music Festival | https://www.malaysia.travel/events/2026/9 | description, organizer, contact, price, hours, image, coordinates |
| RHB Lekas Highway Ride 2026 | 2026-09-12 | 2026-09-12 | Kajang Selatan Toll Plaza/Lekas Highway | Sports Festival | https://www.malaysia.travel/events/2026/9 | description, organizer, contact, price, hours, image, coordinates |
| Keretapi Sarong 2026 | 2026-09-16 | 2026-09-16 | Klang Valley, Johor Bahru, Ipoh, Pasir Mas, Kuantan, Sungai Petani | Cultural Festival | https://www.malaysia.travel/events/keretapi-sarong-2026 | contact, price, hours, image, coordinates |
| Powerman Malaysia 2026 | 2026-09-18 | 2026-09-20 | Dataran Putrajaya | Sports Festival | https://www.malaysia.travel/events/2026/9 | description, organizer, contact, price, hours, image, coordinates |
| Malaysia Sarong Music Run 2026 | 2026-09-19 | 2026-09-19 | KLCC | Sports Festival | https://www.malaysia.travel/events/2026/9 | description, organizer, contact, price, hours, image, coordinates |
| Malaysia International Craft Fair | 2026-09-24 | 2026-10-05 | Kuala Lumpur Craft Complex | Cultural Festival | https://www.malaysia.travel/events/2026/9 | description, organizer, contact, price, hours, image, coordinates |
| VM2026 Food Festival @ MATIC | 2026-09-25 | 2026-09-27 | Malaysia Tourism Centre (MaTiC) | Food Festival | https://www.malaysia.travel/events/2026/9 | description, organizer, contact, price, hours, image, coordinates |
| Three By Three | 2026-10-03 | 2026-10-03 | Dewan Filharmonik PETRONAS | Music Festival | https://www.malaysia.travel/events/2026/10 | description, organizer, contact, price, hours, image, coordinates |
| The Music of Queen…Lives On! | 2026-10-10 | 2026-10-10 | Dewan Filharmonik PETRONAS | Music Festival | https://www.malaysia.travel/events/2026/10 | description, organizer, contact, price, hours, image, coordinates |
| Jaclyn Victor Gemilang Bersama MPO | 2026-10-17 | 2026-10-17 | Dewan Filharmonik PETRONAS | Music Festival | https://www.malaysia.travel/events/2026/10 | description, organizer, contact, price, hours, image, coordinates |
| Challenge Malaysia | 2026-10-17 | 2026-10-18 | Forest City, Johor | Sports Festival | https://www.malaysia.travel/events/2026/10 | description, organizer, contact, price, hours, image, coordinates |
| KOKOL Ultra 2026 | 2026-10-23 | 2026-10-25 | Expertise Event Management | Sports Festival | https://www.malaysia.travel/events/2026/10 | venue requires review; description, organizer, contact, price, hours, image, coordinates |
| Sarawak International Dragon Boat Regatta 2026 | 2026-10-24 | 2026-10-26 | Tebingan Sungai Kuching | Sports Festival | https://www.malaysia.travel/events/2026/10 | description, organizer, contact, price, hours, image, coordinates |
| A Regal Evening with Stephen Hough | 2026-10-24 | 2026-10-24 | Dewan Filharmonik PETRONAS | Music Festival | https://www.malaysia.travel/events/a-regal-evening-with-stephen-hough | image, coordinates; official date range is malformed, corrected to the stated one-day 24 Oct performance |
| Petronas Grand Prix of Malaysia (MotoGP) | 2026-10-30 | 2026-11-01 | Sepang International Circuit, Sepang | Sports Festival | https://www.malaysia.travel/events/2026/10 | description, organizer, contact, price, hours, image, coordinates |
| Beats of Borneo: Alena Murang with the MPO | 2026-10-31 | 2026-10-31 | Dewan Filharmonik PETRONAS | Music Festival | https://www.malaysia.travel/events/2026/10 | description, organizer, contact, price, hours, image, coordinates |
| Selangor Marathon 2026 | 2026-11-01 | 2026-11-01 | Persiaran Flora, Cyberjaya | Sports Festival | https://www.malaysia.travel/events/2026/11 | description, organizer, contact, price, hours, image, coordinates |
| LANY : Soft World Tour | 2026-11-01 | 2026-11-01 | Unifi Arena, Bukit Jalil, W.P Kuala Lumpur | Music Festival | https://www.malaysia.travel/events/2026/11 | description, organizer, contact, price, hours, image, coordinates |
| As If She Were Here: Chen Jia Sings Teresa Teng | 2026-11-07 | 2026-11-29 | Dewan Filharmonik PETRONAS | Music Festival | https://www.malaysia.travel/events/2026/11 | schedule detail requires review; description, organizer, contact, price, hours, image, coordinates |
| Deepavali | 2026-11-08 | 2026-11-08 | All over Malaysia | National Celebration | https://www.malaysia.travel/events/2026/11 | description, organizer, contact, price, hours, image, coordinates |
| The 36th Raja Muda Selangor International Regatta 2026 | 2026-11-13 | 2026-11-21 | Port Klang, Pangkor Island, Penang & Langkawi | Sports Festival | https://www.malaysia.travel/events/2026/11 | description, organizer, contact, price, hours, image, coordinates |
| Simfoni Mantra: Kunto Aji bersama MPO | 2026-11-14 | 2026-11-14 | Dewan Filharmonik PETRONAS | Music Festival | https://www.malaysia.travel/events/2026/11 | description, organizer, contact, price, hours, image, coordinates |
| Penang International Dragon Boat Regatta 2026 | 2026-11-20 | 2026-11-22 | Straits Quay Marina Waterfront, Tanjung Tokong, Penang | Sports Festival | https://www.malaysia.travel/events/2026/11 | description, organizer, contact, price, hours, image, coordinates |
| Ironman 70.3 Langkawi | 2026-11-21 | 2026-11-21 | Langkawi, Kedah | Sports Festival | https://www.malaysia.travel/events/2026/11 | description, organizer, contact, price, hours, image, coordinates |
| Wave to Earth | 2026-11-22 | 2026-11-22 | Idea Live Arena, W.P Kuala Lumpur | Music Festival | https://www.malaysia.travel/events/2026/11 | description, organizer, contact, price, hours, image, coordinates |
| Malaysia World Masters Golf Championship 2026 | 2026-11-22 | 2026-11-28 | Klang Valley | Sports Festival | https://www.malaysia.travel/events/2026/11 | description, organizer, contact, price, hours, image, coordinates |
| A Chorale Spectacular | 2026-12-05 | 2026-12-05 | Dewan Filharmonik PETRONAS | Music Festival | https://www.malaysia.travel/events/2026/12 | description, organizer, contact, price, hours, image, coordinates |
| Sibu Bike Week | 2026-12-05 | 2026-12-06 | Dataran Tun Tuanku Bujang Phase 2 | Sports Festival | https://www.malaysia.travel/events/2026/12 | description, organizer, contact, price, hours, image, coordinates |
| Nakawan Ultra 3.0 | 2026-12-11 | 2026-12-12 | Bukit Air Resort, Kompleks Eko Pelancongan, Sungai Batu Pahat, Perlis | Nature Festival | https://www.malaysia.travel/events/2026/12 | description, organizer, contact, price, hours, image, coordinates |
| Penang Bridge International Marathon | 2026-12-13 | 2026-12-13 | Batu Kawan Stadium | Sports Festival | https://www.malaysia.travel/events/2026/12 | description, organizer, contact, price, hours, image, coordinates |
| BTS World Tour ‘Arirang’ in Kuala Lumpur | 2026-12-13 | 2026-12-13 | Stadium Nasional, Bukit Jalil | Music Festival | https://www.malaysia.travel/events/2026/12 | description, organizer, contact, price, hours, image, coordinates |
| Christmas Celebration | 2026-12-25 | 2026-12-25 | All over Malaysia | National Celebration | https://www.malaysia.travel/events/2026/12 | description, organizer, contact, price, hours, image, coordinates |

## Duplicate prevention

No suitable unique constraint exists in the migrations. The selected Laravel seeder narrows candidates by exact start and end dates, then compares normalized titles in PHP. Seeder normalization trims the title, lowercases it, and reduces repeated whitespace to one space. Location is intentionally excluded from duplicate identity because venue wording may differ or be `NULL` for the same official event. A match is skipped and is never updated. Re-running the seeder should therefore skip rows it previously inserted.

The retained reference SQL has a comparable `WHERE NOT EXISTS` guard and additionally normalizes punctuation, but the SQL is not the selected execution method.

This cannot detect every possible manually entered Supabase variation. Review the final verification query and compare likely variants before committing the transaction in a production workflow.

## Execution instructions

1. Back up or export the existing `experiences`, `experience_type`, and `category` rows.
2. Open the script in the Supabase SQL Editor but do not immediately run it.
3. Run the preflight category `SELECT` separately. It must return all six category names used by this script under `Festival`.
4. Review the 53 `VALUES` rows and the documented missing information.
5. For a safer dry run, change the final `COMMIT;` to `ROLLBACK;`, run the script, and inspect the reported affected-row count/output available in the editor.
6. Restore `COMMIT;` only after duplicate and category checks are satisfactory.
7. Run the verification `SELECT` and manually review the inserted/skipped rows.

## Rollback advice

Before `COMMIT`, execute `ROLLBACK;` in the same SQL transaction to undo all inserts. After `COMMIT`, do not run a broad title/date deletion without review: it could remove pre-existing manual records. Use the verification result to capture the newly assigned `experiences_id` values, review those IDs, and delete only those exact IDs inside a separate transaction if rollback is necessary.
