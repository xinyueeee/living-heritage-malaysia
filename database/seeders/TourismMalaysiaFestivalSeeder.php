<?php

namespace Database\Seeders;

use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class TourismMalaysiaFestivalSeeder extends Seeder
{
    private const MISSING_DESCRIPTION = 'Description not provided by Tourism Malaysia.';

    private const DESCRIPTION_OVERRIDES = [
        'Tatreez : Reclaiming Palestine Through Embroidery' => 'An exhibition showcasing the rich heritage of Palestinian embroidery through traditional and contemporary textile works, highlighting cultural identity, resilience, and the preservation of regional traditions.',
        'Festival Kraf Utara 2026' => 'A national craft festival featuring Malaysian handicrafts, live craft demonstrations, exhibitions, cultural activities, and interactive programmes that support traditional artisans and the northern craft industry.',
        'Temasya Orang Kedah' => 'A cultural celebration showcasing Kedah’s heritage through traditional performances, art exhibitions, local cuisine, folk games, and community cultural activities.',
        'Pesta Kuantan 188' => 'A community festival held in conjunction with the Sultan of Pahang’s birthday, featuring powerboat racing, fun runs, cycling activities, concerts, and a fireworks display.',
        'Art Of Speed' => 'An annual automotive and lifestyle festival showcasing custom vehicles, visual art, sculptures, creative exhibitions, and Malaysia’s automotive culture.',
        'Youth Performance Speed Fest' => 'A high-energy automotive festival featuring car and motorcycle shows, drifting, test-and-tune sessions, competitions, vendor activities, and modified vehicles.',
        'Taiping Half Marathon' => 'A scenic running event through Taiping that encourages endurance, fitness, and community participation while highlighting the town’s natural and historical surroundings.',
        'Penang Hill Heritage Forest Challenge 2026 (PHHFC2026)' => 'An annual trail running challenge through Penang Hill’s forest routes that promotes eco-tourism, environmental conservation, endurance, and appreciation of the biosphere reserve.',
        'Pahang Eco 2026' => 'An outdoor eco-sports event offering endurance challenges and recreational activities while promoting Pahang’s natural environment and eco-tourism attractions.',
        'The Sky Race' => 'A vertical stair-climbing challenge at Merdeka 118 where participants compete to conquer 118 floors in an international endurance event.',
        'A Heart Unveiled: The Music of Tchaikovsky' => 'A Malaysian Philharmonic Orchestra concert featuring Tchaikovsky’s Violin Concerto and Symphony No. 6 in an evening of expressive and dramatic classical music.',
        'Kodaline – Farewell Tour' => 'The final concert tour by Irish band Kodaline, featuring their most popular songs and music from their fifth and final studio album.',
        'Bukit Maras Trail Run Challenge 2.0' => 'A trail running and hiking event promoting Bukit Maras as a sports tourism destination while providing outdoor challenges for recreational and competitive participants.',
        "A Knight's Tale: Valor and Romance in Music" => 'A Malaysian Philharmonic Orchestra concert featuring works by Dvořák, Walton, and Schumann in a programme celebrating resilience, romance, and orchestral expression.',
        'Malaysia Marathon 2026' => 'A national marathon in Kuala Lumpur offering several race distances while celebrating National Day, fitness, community participation, and the Malaysian spirit.',
        'Merdeka Day' => 'Malaysia’s annual Independence Day celebration commemorating the nation’s independence through national parades, patriotic ceremonies, cultural performances, and public celebrations.',
        'A Tribute to Alfonso Soliano' => 'A tribute concert celebrating the legacy of Malaysian music pioneer Alfonso Soliano, featuring the Malaysian Philharmonic Orchestra and The Solianos in a special orchestral performance.',
        'Sepilok Jazz Festival' => 'An annual jazz festival held in the rainforest of Sepilok, bringing together local and international musicians for a unique musical experience surrounded by nature.',
        'Yin and Yang: A Dance Kaleidoscope' => 'A contemporary dance performance celebrating Malaysia’s multicultural heritage through storytelling, traditional influences, and artistic movement.',
        'PJ Half Marathon' => 'One of Malaysia’s longest-running half marathons, bringing together local and international runners in a community-focused road running event.',
        'Malaysia Ultra-Trail By Utmb' => 'An international trail running event that takes participants through the scenic landscapes of Taiping, offering a challenging world-class ultra-trail experience.',
        "Maurice Steger's Nature Concerti" => 'A Malaysian Philharmonic Orchestra concert featuring imaginative Baroque works inspired by nature, led by recorder virtuoso Maurice Steger.',
        'RHB Lekas Highway Ride 2026' => 'A large-scale night cycling event offering 78-kilometre and 105-kilometre routes, including a challenging King of Mountain climb.',
        'Keretapi Sarong 2026' => 'A multicultural celebration bringing Malaysians together in traditional attire through music, dance, storytelling, and cultural showcases that represent the country’s diverse communities.',
        'Powerman Malaysia 2026' => 'An international duathlon competition combining running and cycling while promoting athletic excellence, community participation, and world-class endurance racing.',
        'Malaysia Sarong Music Run 2026' => 'A cultural fun run combining Malaysian music, local food, traditional performances, and participants dressed in sarongs to celebrate heritage and national unity.',
        'Malaysia International Craft Fair' => 'An international craft exhibition showcasing Malaysian heritage arts, traditional handicrafts, and artisanal products from local and global creators.',
        'VM2026 Food Festival @ MATIC' => 'A culinary festival at the Malaysia Tourism Centre showcasing Malaysia’s diverse food heritage, local delicacies, and gastronomic traditions in conjunction with Visit Malaysia 2026.',
        'Three By Three' => 'A Malaysian Philharmonic Orchestra concert featuring Beethoven’s Triple Concerto and Vaughan Williams’ Symphony No. 5, performed with three rising instrumental soloists.',
        'The Music of Queen…Lives On!' => 'A symphonic rock concert combining the Malaysian Philharmonic Orchestra, a live rock band, and a vocalist to perform Queen’s most iconic songs.',
        'Jaclyn Victor Gemilang Bersama MPO' => 'A special concert celebrating Jaclyn Victor’s two-decade musical career through new orchestral arrangements performed with the Malaysian Philharmonic Orchestra.',
        'Challenge Malaysia' => 'A multi-category triathlon featuring swimming, cycling, and running events for children and adults of different ages and fitness levels.',
        'KOKOL Ultra 2026' => 'An international-standard ultra-trail event offering race distances from 15 to 70 kilometres, including recognised qualifier and performance-point opportunities.',
        'A Regal Evening with Stephen Hough' => 'An all-Beethoven concert featuring pianist Stephen Hough performing the Emperor Concerto with the Malaysian Philharmonic Orchestra, followed by Symphony No. 4.',
        'Petronas Grand Prix of Malaysia (MotoGP)' => 'Malaysia’s premier MotoGP event featuring world-class motorcycle riders competing in high-speed international races at the Sepang International Circuit.',
        'Beats of Borneo: Alena Murang with the MPO' => 'A Malaysian Philharmonic Orchestra concert featuring Alena Murang and the premiere of a new concerto for the traditional sape’, bridging Bornean heritage with orchestral music.',
    ];

    private const REQUIRED_CATEGORIES = [
        'Cultural Festival',
        'Music Festival',
        'Food Festival',
        'Sports Festival',
        'Nature Festival',
        'National Celebration',
    ];

    public function run(): void
    {
        $counters = ['inserted' => 0, 'skipped' => 0, 'failed' => 0];

        try {
            DB::transaction(function () use (&$counters): void {
                $festivalType = DB::table('experience_type')
                    ->where('type_name', 'Festival')
                    ->first(['type_id']);

                if ($festivalType === null) {
                    throw new RuntimeException(
                        'Tourism Malaysia festival import aborted: ExperienceType "Festival" was not found.'
                    );
                }

                $categories = DB::table('category')
                    ->where('type_id', $festivalType->type_id)
                    ->whereIn('category_name', self::REQUIRED_CATEGORIES)
                    ->pluck('category_id', 'category_name');

                $missingCategories = array_values(array_diff(
                    self::REQUIRED_CATEGORIES,
                    $categories->keys()->all(),
                ));

                if ($missingCategories !== []) {
                    throw new RuntimeException(sprintf(
                        'Tourism Malaysia festival import aborted: missing Festival categories: %s.',
                        implode(', ', $missingCategories),
                    ));
                }

                foreach ($this->records() as $record) {
                    $existingCandidates = DB::table('experiences')
                        ->whereDate('start_date', $record['start_date'])
                        ->whereDate('end_date', $record['end_date'])
                        ->get(['experiences_name']);

                    $duplicate = $existingCandidates->contains(function (object $existing) use ($record): bool {
                        return $this->normalize($existing->experiences_name)
                            === $this->normalize($record['experiences_name']);
                    });

                    if ($duplicate) {
                        $counters['skipped']++;

                        continue;
                    }

                    $inserted = DB::table('experiences')->insert([
                        'experiences_name' => $record['experiences_name'],
                        'description' => $record['description'],
                        'location_name' => $record['location_name'],
                        'image_url' => null,
                        'price' => $record['price'],
                        'duration' => $record['duration'],
                        'created_at' => now(),
                        'start_date' => $record['start_date'],
                        'end_date' => $record['end_date'],
                        'operating_hours' => $record['operating_hours'],
                        'type_id' => $festivalType->type_id,
                        'category_id' => $categories[$record['category_name']],
                        'latitude' => null,
                        'longitude' => null,
                        'contact_number' => $record['contact_number'],
                        'status' => 'Available',
                        'updated_at' => now(),
                    ]);

                    if (! $inserted) {
                        throw new RuntimeException(sprintf(
                            'Insert failed for Tourism Malaysia event "%s".',
                            $record['experiences_name'],
                        ));
                    }

                    $counters['inserted']++;
                }
            });
        } catch (Throwable $exception) {
            // The transaction has rolled back, so no attempted inserts remain.
            $counters['inserted'] = 0;
            $counters['failed']++;
            $this->printSummary($counters, true);

            throw $exception;
        }

        $this->printSummary($counters, false);
    }

    /**
     * @return array<int, array{
     *     category_name: string,
     *     experiences_name: string,
     *     description: ?string,
     *     location_name: ?string,
     *     price: ?float,
     *     duration: ?string,
     *     start_date: string,
     *     end_date: string,
     *     operating_hours: ?string,
     *     contact_number: ?string
     * }>
     */
    private function records(): array
    {
        $data = <<<'EVENTS'
Cultural Festival|Tatreez : Reclaiming Palestine Through Embroidery|Tatreez is the Arabic word for embroidery, a visual language used by Palestinian women across generations. The exhibition presents traditional regional styles and contemporary work while preserving Palestinian textile heritage.|Special Gallery 1 & 2, Islamic Arts Museum Malaysia|\N|\N|2026-06-19|2027-04-25|\N|0320927114
Cultural Festival|Festival Kraf Utara 2026|A northern craft festival featuring Malaysian heritage products, live craft demonstrations, interactive activities and local craft entrepreneurs.|Kraftangan Malaysia Perlis Branch|0.00|\N|2026-07-23|2026-08-09|10:00-22:00 daily|04-985 5278
Cultural Festival|Temasya Orang Kedah|\N|Perkarangan Stadium Darul Aman, Alor Setar, Kedah|\N|\N|2026-07-30|2026-08-02|\N|\N
Cultural Festival|Pesta Kuantan 188|\N|Kuantan|\N|\N|2026-07-31|2026-08-02|\N|\N
Cultural Festival|Art Of Speed|An annual event featuring artwork in canvas, poster, moving visual, sculpture and rolling-art formats.|MAEPS, Serdang, Selangor|\N|\N|2026-08-01|2026-08-02|\N|+6012 262 0405
Sports Festival|Youth Performance Speed Fest|\N|Puteri Harbour, Iskandar Puteri, Johor|\N|\N|2026-08-07|2026-08-09|\N|\N
Sports Festival|Taiping Half Marathon|\N|Dataran Warisan Taiping|\N|\N|2026-08-09|2026-08-09|\N|\N
Nature Festival|Penang Hill Heritage Forest Challenge 2026 (PHHFC2026)|\N|Penang Hill|\N|\N|2026-08-16|2026-08-16|\N|\N
Sports Festival|Pahang Eco 2026|\N|Teluk Cempedak, Kuantan|\N|\N|2026-08-21|2026-08-23|\N|\N
Music Festival|A Heart Unveiled: The Music of Tchaikovsky|\N|Dewan Filharmonik PETRONAS|\N|\N|2026-08-22|2026-08-22|\N|\N
Sports Festival|The Sky Race|\N|Merdeka 118|\N|\N|2026-08-22|2026-08-23|\N|\N
Music Festival|Kodaline – Farewell Tour|\N|Idea Live Arena, W.P Kuala Lumpur|\N|\N|2026-08-26|2026-08-26|\N|\N
Music Festival|A Knight's Tale: Valor and Romance in Music|\N|Dewan Filharmonik PETRONAS|\N|\N|2026-08-29|2026-08-29|\N|\N
Sports Festival|Bukit Maras Trail Run Challenge 2.0|\N|Bukit Maras, Kuala Nerus, Terengganu|\N|\N|2026-08-29|2026-08-29|\N|\N
Sports Festival|Malaysia Marathon 2026|\N|Pavilion KL to KLCC|\N|\N|2026-08-30|2026-08-30|\N|\N
National Celebration|Merdeka Day|\N|All over Malaysia|\N|\N|2026-08-31|2026-08-31|\N|\N
Music Festival|A Tribute to Alfonso Soliano|\N|Dewan Filharmonik PETRONAS|\N|\N|2026-09-02|2026-09-02|\N|\N
Music Festival|Sepilok Jazz Festival|\N|Rainforest Discovery Centre (RDC), Sepilok, Sandakan, Sabah|\N|\N|2026-09-04|2026-09-05|\N|\N
Music Festival|Yin and Yang: A Dance Kaleidoscope|\N|Dewan Filharmonik PETRONAS|\N|\N|2026-09-05|2026-09-05|\N|\N
Sports Festival|PJ Half Marathon|\N|Laman MBPJ, Petaling Jaya|\N|\N|2026-09-06|2026-09-06|\N|\N
Sports Festival|Malaysia Ultra-Trail By Utmb|\N|Dataran Warisan Taiping, Perak|\N|\N|2026-09-10|2026-09-13|\N|\N
Music Festival|Maurice Steger's Nature Concerti|\N|Dewan Filharmonik PETRONAS|\N|\N|2026-09-12|2026-09-12|\N|\N
Sports Festival|RHB Lekas Highway Ride 2026|\N|Kajang Selatan Toll Plaza/Lekas Highway|\N|\N|2026-09-12|2026-09-12|\N|\N
Cultural Festival|Keretapi Sarong 2026|A nationwide cultural gathering themed Ethnicity, featuring traditional dance, music and storytelling that celebrates Malaysia's multicultural identity.|Klang Valley, Johor Bahru, Ipoh, Pasir Mas, Kuantan, Sungai Petani|\N|\N|2026-09-16|2026-09-16|\N|\N
Sports Festival|Powerman Malaysia 2026|\N|Dataran Putrajaya|\N|\N|2026-09-18|2026-09-20|\N|\N
Sports Festival|Malaysia Sarong Music Run 2026|\N|KLCC|\N|\N|2026-09-19|2026-09-19|\N|\N
Cultural Festival|Malaysia International Craft Fair|\N|Kuala Lumpur Craft Complex|\N|\N|2026-09-24|2026-10-05|\N|\N
Food Festival|VM2026 Food Festival @ MATIC|\N|Malaysia Tourism Centre (MaTiC)|\N|\N|2026-09-25|2026-09-27|\N|\N
Music Festival|Three By Three|\N|Dewan Filharmonik PETRONAS|\N|\N|2026-10-03|2026-10-03|\N|\N
Music Festival|The Music of Queen…Lives On!|\N|Dewan Filharmonik PETRONAS|\N|\N|2026-10-10|2026-10-10|\N|\N
Music Festival|Jaclyn Victor Gemilang Bersama MPO|\N|Dewan Filharmonik PETRONAS|\N|\N|2026-10-17|2026-10-17|\N|\N
Sports Festival|Challenge Malaysia|\N|Forest City, Johor|\N|\N|2026-10-17|2026-10-18|\N|\N
Sports Festival|KOKOL Ultra 2026|\N|Expertise Event Management|\N|\N|2026-10-23|2026-10-25|\N|\N
Sports Festival|Sarawak International Dragon Boat Regatta 2026|\N|Tebingan Sungai Kuching|\N|\N|2026-10-24|2026-10-26|\N|\N
Music Festival|A Regal Evening with Stephen Hough|An all-Beethoven concert with the Malaysian Philharmonic Orchestra, conductor Junichi Hirokami and pianist Stephen Hough.|Dewan Filharmonik PETRONAS|\N|\N|2026-10-24|2026-10-24|20:00|03 23317007
Sports Festival|Petronas Grand Prix of Malaysia (MotoGP)|\N|Sepang International Circuit, Sepang|\N|\N|2026-10-30|2026-11-01|\N|\N
Music Festival|Beats of Borneo: Alena Murang with the MPO|\N|Dewan Filharmonik PETRONAS|\N|\N|2026-10-31|2026-10-31|\N|\N
Sports Festival|Selangor Marathon 2026|\N|Persiaran Flora, Cyberjaya|\N|\N|2026-11-01|2026-11-01|\N|\N
Music Festival|LANY : Soft World Tour|\N|Unifi Arena, Bukit Jalil, W.P Kuala Lumpur|\N|\N|2026-11-01|2026-11-01|\N|\N
Music Festival|As If She Were Here: Chen Jia Sings Teresa Teng|\N|Dewan Filharmonik PETRONAS|\N|\N|2026-11-07|2026-11-29|\N|\N
National Celebration|Deepavali|\N|All over Malaysia|\N|\N|2026-11-08|2026-11-08|\N|\N
Sports Festival|The 36th Raja Muda Selangor International Regatta 2026|\N|Port Klang, Pangkor Island, Penang & Langkawi|\N|\N|2026-11-13|2026-11-21|\N|\N
Music Festival|Simfoni Mantra: Kunto Aji bersama MPO|\N|Dewan Filharmonik PETRONAS|\N|\N|2026-11-14|2026-11-14|\N|\N
Sports Festival|Penang International Dragon Boat Regatta 2026|\N|Straits Quay Marina Waterfront, Tanjung Tokong, Penang|\N|\N|2026-11-20|2026-11-22|\N|\N
Sports Festival|Ironman 70.3 Langkawi|\N|Langkawi, Kedah|\N|\N|2026-11-21|2026-11-21|\N|\N
Music Festival|Wave to Earth|\N|Idea Live Arena, W.P Kuala Lumpur|\N|\N|2026-11-22|2026-11-22|\N|\N
Sports Festival|Malaysia World Masters Golf Championship 2026|\N|Klang Valley|\N|\N|2026-11-22|2026-11-28|\N|\N
Music Festival|A Chorale Spectacular|\N|Dewan Filharmonik PETRONAS|\N|\N|2026-12-05|2026-12-05|\N|\N
Sports Festival|Sibu Bike Week|\N|Dataran Tun Tuanku Bujang Phase 2|\N|\N|2026-12-05|2026-12-06|\N|\N
Nature Festival|Nakawan Ultra 3.0|\N|Bukit Air Resort, Kompleks Eko Pelancongan, Sungai Batu Pahat, Perlis|\N|\N|2026-12-11|2026-12-12|\N|\N
Sports Festival|Penang Bridge International Marathon|\N|Batu Kawan Stadium|\N|\N|2026-12-13|2026-12-13|\N|\N
Music Festival|BTS World Tour ‘Arirang’ in Kuala Lumpur|\N|Stadium Nasional, Bukit Jalil|\N|\N|2026-12-13|2026-12-13|\N|\N
National Celebration|Christmas Celebration|\N|All over Malaysia|\N|\N|2026-12-25|2026-12-25|\N|\N
EVENTS;

        $records = [];

        foreach (preg_split('/\R/u', trim($data)) as $lineNumber => $line) {
            $fields = str_getcsv($line, '|');

            if (count($fields) !== 10) {
                throw new RuntimeException(sprintf(
                    'Invalid Tourism Malaysia event data on embedded line %d: expected 10 fields, found %d.',
                    $lineNumber + 1,
                    count($fields),
                ));
            }

            $fields = array_map(
                static fn (string $value): ?string => $value === '\\N' ? null : $value,
                $fields,
            );

            [$category, $title, $description, $location, $price, $duration, $start, $end, $hours, $contact] = $fields;

            $startDate = CarbonImmutable::createFromFormat('!Y-m-d', $start);
            $endDate = CarbonImmutable::createFromFormat('!Y-m-d', $end);

            if ($startDate === false || $endDate === false || $endDate->lt($startDate)) {
                throw new RuntimeException(sprintf(
                    'Invalid date range for Tourism Malaysia event "%s".',
                    $title,
                ));
            }

            if (! in_array($category, self::REQUIRED_CATEGORIES, true)) {
                throw new RuntimeException(sprintf(
                    'Unexpected Festival category "%s" for Tourism Malaysia event "%s".',
                    $category,
                    $title,
                ));
            }

            $records[] = [
                'category_name' => $category,
                'experiences_name' => $title,
                'description' => self::DESCRIPTION_OVERRIDES[$title]
                    ?? $description
                    ?? self::MISSING_DESCRIPTION,
                'location_name' => $location,
                'price' => $price === null ? null : (float) $price,
                'duration' => $duration,
                'start_date' => $start,
                'end_date' => $end,
                'operating_hours' => $hours,
                'contact_number' => $contact,
            ];
        }

        if (count($records) !== 53) {
            throw new RuntimeException(sprintf(
                'Tourism Malaysia festival dataset must contain 53 records; found %d.',
                count($records),
            ));
        }

        return $records;
    }

    private function normalize(?string $value): string
    {
        return Str::lower(preg_replace('/\s+/u', ' ', trim($value ?? '')) ?? '');
    }

    /** @param array{inserted: int, skipped: int, failed: int} $counters */
    private function printSummary(array $counters, bool $rolledBack): void
    {
        $message = sprintf(
            'Tourism Malaysia Festival Seeder: inserted=%d, skipped_duplicates=%d, failed=%d, transaction=%s.',
            $counters['inserted'],
            $counters['skipped'],
            $counters['failed'],
            $rolledBack ? 'rolled back' : 'committed',
        );

        if ($rolledBack) {
            $this->command?->error($message);

            return;
        }

        $this->command?->info($message);
    }
}
