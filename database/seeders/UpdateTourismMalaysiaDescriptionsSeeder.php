<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * One-time data correction for descriptions on existing Tourism Malaysia events.
 *
 * This seeder never creates an Experience and never changes any field other
 * than description.
 */
class UpdateTourismMalaysiaDescriptionsSeeder extends Seeder
{
    private const FALLBACK_DESCRIPTION = 'Description not provided by Tourism Malaysia.';

    public function run(): void
    {
        $counters = [
            'updated' => 0,
            'skipped_existing_description' => 0,
            'not_found' => 0,
            'failed' => 0,
        ];

        try {
            DB::transaction(function () use (&$counters): void {
                foreach ($this->corrections() as $correction) {
                    $candidates = DB::table('experiences')
                        ->whereDate('start_date', $correction['start_date'])
                        ->whereDate('end_date', $correction['end_date'])
                        ->get(['experiences_id', 'experiences_name', 'description']);

                    $matches = $candidates->filter(function (object $candidate) use ($correction): bool {
                        return $this->normalize($candidate->experiences_name)
                            === $this->normalize($correction['experiences_name']);
                    });

                    if ($matches->isEmpty()) {
                        $counters['not_found']++;

                        continue;
                    }

                    foreach ($matches as $match) {
                        if (! $this->isReplaceable($match->description)) {
                            $counters['skipped_existing_description']++;

                            continue;
                        }

                        $updated = DB::table('experiences')
                            ->where('experiences_id', $match->experiences_id)
                            ->where(function ($query): void {
                                $query->whereNull('description')
                                    ->orWhere('description', '')
                                    ->orWhere('description', self::FALLBACK_DESCRIPTION);
                            })
                            ->update(['description' => $correction['description']]);

                        if ($updated === 1) {
                            $counters['updated']++;
                        } else {
                            // A concurrent change supplied a real description; preserve it.
                            $counters['skipped_existing_description']++;
                        }
                    }
                }
            });
        } catch (Throwable $exception) {
            // The transaction has rolled back, so none of the attempted corrections remain.
            $counters['updated'] = 0;
            $counters['failed']++;
            $this->printSummary($counters, true);

            throw $exception;
        }

        $this->printSummary($counters, false);
    }

    /**
     * @return array<int, array{
     *     experiences_name: string,
     *     start_date: string,
     *     end_date: string,
     *     description: string
     * }>
     */
    private function corrections(): array
    {
        $data = <<<'CORRECTIONS'
Tatreez : Reclaiming Palestine Through Embroidery|2026-06-19|2027-04-25|An exhibition showcasing the rich heritage of Palestinian embroidery through traditional and contemporary textile works, highlighting cultural identity, resilience, and the preservation of regional traditions.
Festival Kraf Utara 2026|2026-07-23|2026-08-09|A national craft festival featuring Malaysian handicrafts, live craft demonstrations, exhibitions, cultural activities, and interactive programmes that support traditional artisans and the northern craft industry.
Temasya Orang Kedah|2026-07-30|2026-08-02|A cultural celebration showcasing Kedah’s heritage through traditional performances, art exhibitions, local cuisine, folk games, and community cultural activities.
Pesta Kuantan 188|2026-07-31|2026-08-02|A community festival held in conjunction with the Sultan of Pahang’s birthday, featuring powerboat racing, fun runs, cycling activities, concerts, and a fireworks display.
Art Of Speed|2026-08-01|2026-08-02|An annual automotive and lifestyle festival showcasing custom vehicles, visual art, sculptures, creative exhibitions, and Malaysia’s automotive culture.
Youth Performance Speed Fest|2026-08-07|2026-08-09|A high-energy automotive festival featuring car and motorcycle shows, drifting, test-and-tune sessions, competitions, vendor activities, and modified vehicles.
Taiping Half Marathon|2026-08-09|2026-08-09|A scenic running event through Taiping that encourages endurance, fitness, and community participation while highlighting the town’s natural and historical surroundings.
Penang Hill Heritage Forest Challenge 2026 (PHHFC2026)|2026-08-16|2026-08-16|An annual trail running challenge through Penang Hill’s forest routes that promotes eco-tourism, environmental conservation, endurance, and appreciation of the biosphere reserve.
Pahang Eco 2026|2026-08-21|2026-08-23|An outdoor eco-sports event offering endurance challenges and recreational activities while promoting Pahang’s natural environment and eco-tourism attractions.
The Sky Race|2026-08-22|2026-08-23|A vertical stair-climbing challenge at Merdeka 118 where participants compete to conquer 118 floors in an international endurance event.
A Heart Unveiled: The Music of Tchaikovsky|2026-08-22|2026-08-22|A Malaysian Philharmonic Orchestra concert featuring Tchaikovsky’s Violin Concerto and Symphony No. 6 in an evening of expressive and dramatic classical music.
Kodaline – Farewell Tour|2026-08-26|2026-08-26|The final concert tour by Irish band Kodaline, featuring their most popular songs and music from their fifth and final studio album.
Bukit Maras Trail Run Challenge 2.0|2026-08-29|2026-08-29|A trail running and hiking event promoting Bukit Maras as a sports tourism destination while providing outdoor challenges for recreational and competitive participants.
A Knight's Tale: Valor and Romance in Music|2026-08-29|2026-08-29|A Malaysian Philharmonic Orchestra concert featuring works by Dvořák, Walton, and Schumann in a programme celebrating resilience, romance, and orchestral expression.
Malaysia Marathon 2026|2026-08-30|2026-08-30|A national marathon in Kuala Lumpur offering several race distances while celebrating National Day, fitness, community participation, and the Malaysian spirit.
Merdeka Day|2026-08-31|2026-08-31|Malaysia’s annual Independence Day celebration commemorating the nation’s independence through national parades, patriotic ceremonies, cultural performances, and public celebrations.
A Tribute to Alfonso Soliano|2026-09-02|2026-09-02|A tribute concert celebrating the legacy of Malaysian music pioneer Alfonso Soliano, featuring the Malaysian Philharmonic Orchestra and The Solianos in a special orchestral performance.
Sepilok Jazz Festival|2026-09-04|2026-09-05|An annual jazz festival held in the rainforest of Sepilok, bringing together local and international musicians for a unique musical experience surrounded by nature.
Yin and Yang: A Dance Kaleidoscope|2026-09-05|2026-09-05|A contemporary dance performance celebrating Malaysia’s multicultural heritage through storytelling, traditional influences, and artistic movement.
PJ Half Marathon|2026-09-06|2026-09-06|One of Malaysia’s longest-running half marathons, bringing together local and international runners in a community-focused road running event.
Malaysia Ultra-Trail By Utmb|2026-09-10|2026-09-13|An international trail running event that takes participants through the scenic landscapes of Taiping, offering a challenging world-class ultra-trail experience.
Maurice Steger's Nature Concerti|2026-09-12|2026-09-12|A Malaysian Philharmonic Orchestra concert featuring imaginative Baroque works inspired by nature, led by recorder virtuoso Maurice Steger.
RHB Lekas Highway Ride 2026|2026-09-12|2026-09-12|A large-scale night cycling event offering 78-kilometre and 105-kilometre routes, including a challenging King of Mountain climb.
Keretapi Sarong 2026|2026-09-16|2026-09-16|A multicultural celebration bringing Malaysians together in traditional attire through music, dance, storytelling, and cultural showcases that represent the country’s diverse communities.
Powerman Malaysia 2026|2026-09-18|2026-09-20|An international duathlon competition combining running and cycling while promoting athletic excellence, community participation, and world-class endurance racing.
Malaysia Sarong Music Run 2026|2026-09-19|2026-09-19|A cultural fun run combining Malaysian music, local food, traditional performances, and participants dressed in sarongs to celebrate heritage and national unity.
Malaysia International Craft Fair|2026-09-24|2026-10-05|An international craft exhibition showcasing Malaysian heritage arts, traditional handicrafts, and artisanal products from local and global creators.
VM2026 Food Festival @ MATIC|2026-09-25|2026-09-27|A culinary festival at the Malaysia Tourism Centre showcasing Malaysia’s diverse food heritage, local delicacies, and gastronomic traditions in conjunction with Visit Malaysia 2026.
Three By Three|2026-10-03|2026-10-03|A Malaysian Philharmonic Orchestra concert featuring Beethoven’s Triple Concerto and Vaughan Williams’ Symphony No. 5, performed with three rising instrumental soloists.
The Music of Queen…Lives On!|2026-10-10|2026-10-10|A symphonic rock concert combining the Malaysian Philharmonic Orchestra, a live rock band, and a vocalist to perform Queen’s most iconic songs.
Jaclyn Victor Gemilang Bersama MPO|2026-10-17|2026-10-17|A special concert celebrating Jaclyn Victor’s two-decade musical career through new orchestral arrangements performed with the Malaysian Philharmonic Orchestra.
Challenge Malaysia|2026-10-17|2026-10-18|A multi-category triathlon featuring swimming, cycling, and running events for children and adults of different ages and fitness levels.
KOKOL Ultra 2026|2026-10-23|2026-10-25|An international-standard ultra-trail event offering race distances from 15 to 70 kilometres, including recognised qualifier and performance-point opportunities.
A Regal Evening with Stephen Hough|2026-10-24|2026-10-24|An all-Beethoven concert featuring pianist Stephen Hough performing the Emperor Concerto with the Malaysian Philharmonic Orchestra, followed by Symphony No. 4.
Petronas Grand Prix of Malaysia (MotoGP)|2026-10-30|2026-11-01|Malaysia’s premier MotoGP event featuring world-class motorcycle riders competing in high-speed international races at the Sepang International Circuit.
Beats of Borneo: Alena Murang with the MPO|2026-10-31|2026-10-31|A Malaysian Philharmonic Orchestra concert featuring Alena Murang and the premiere of a new concerto for the traditional sape’, bridging Bornean heritage with orchestral music.
CORRECTIONS;

        $corrections = [];

        foreach (preg_split('/\R/u', trim($data)) as $lineNumber => $line) {
            $fields = str_getcsv($line, '|');

            if (count($fields) !== 4) {
                throw new RuntimeException(sprintf(
                    'Invalid description correction on embedded line %d.',
                    $lineNumber + 1,
                ));
            }

            [$title, $startDate, $endDate, $description] = $fields;

            $corrections[] = [
                'experiences_name' => $title,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'description' => $description,
            ];
        }

        if (count($corrections) !== 36) {
            throw new RuntimeException(sprintf(
                'Tourism Malaysia description correction dataset must contain 36 records; found %d.',
                count($corrections),
            ));
        }

        return $corrections;
    }

    private function normalize(?string $value): string
    {
        return Str::lower(preg_replace('/\s+/u', ' ', trim($value ?? '')) ?? '');
    }

    private function isReplaceable(?string $description): bool
    {
        return $description === null
            || trim($description) === ''
            || $description === self::FALLBACK_DESCRIPTION;
    }

    /**
     * @param array{
     *     updated: int,
     *     skipped_existing_description: int,
     *     not_found: int,
     *     failed: int
     * } $counters
     */
    private function printSummary(array $counters, bool $rolledBack): void
    {
        $message = sprintf(
            'Tourism Malaysia Description Update: updated=%d, skipped_existing_description=%d, not_found=%d, failed=%d, transaction=%s.',
            $counters['updated'],
            $counters['skipped_existing_description'],
            $counters['not_found'],
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
