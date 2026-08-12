<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * One-time enrichment for the 75 existing Tourism Malaysia Experiences.
 *
 * This seeder never creates or deletes Experiences. It updates only reviewed
 * descriptions and images, plus the single approved Penang regatta date fix.
 */
class UpdateExperienceEnrichmentSeeder extends Seeder
{
    private const FALLBACK_DESCRIPTION = 'Description not provided by Tourism Malaysia.';

    public function run(): void
    {
        $counters = [
            'updated' => 0,
            'unchanged' => 0,
            'not_found' => 0,
            'failed' => 0,
        ];

        try {
            DB::transaction(function () use (&$counters): void {
                foreach ($this->enrichments() as $enrichment) {
                    $candidates = DB::table('experiences')
                        ->whereDate('start_date', $enrichment['current_start_date'])
                        ->whereDate('end_date', $enrichment['current_end_date'])
                        ->get(['experiences_id', 'experiences_name']);

                    $matches = $candidates->filter(function (object $candidate) use ($enrichment): bool {
                        return $this->normalize($candidate->experiences_name)
                            === $this->normalize($enrichment['experiences_name']);
                    })->values();

                    if ($matches->isEmpty()) {
                        $counters['not_found']++;

                        throw new RuntimeException(sprintf(
                            'Experience not found for enrichment: "%s" (%s to %s).',
                            $enrichment['experiences_name'],
                            $enrichment['current_start_date'],
                            $enrichment['current_end_date'],
                        ));
                    }

                    if ($matches->count() !== 1) {
                        throw new RuntimeException(sprintf(
                            'Ambiguous Experience match for enrichment: "%s" (%s matches).',
                            $enrichment['experiences_name'],
                            $matches->count(),
                        ));
                    }

                    $updates = [
                        'short_description' => $enrichment['short_description'],
                        'description' => $enrichment['description'],
                    ];

                    if ($enrichment['image_url'] !== null) {
                        $updates['image_url'] = $enrichment['image_url'];
                    }

                    if ($enrichment['corrected_start_date'] !== null) {
                        $updates['start_date'] = $enrichment['corrected_start_date'];
                        $updates['end_date'] = $enrichment['corrected_end_date'];
                    }

                    $affected = DB::table('experiences')
                        ->where('experiences_id', $matches->first()->experiences_id)
                        ->update($updates);

                    if ($affected === 1) {
                        $counters['updated']++;
                    } else {
                        $counters['unchanged']++;
                    }
                }
            });
        } catch (Throwable $exception) {
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
     *     current_start_date: string,
     *     current_end_date: string,
     *     corrected_start_date: ?string,
     *     corrected_end_date: ?string,
     *     short_description: string,
     *     description: string,
     *     image_url: ?string
     * }>
     */
    private function enrichments(): array
    {
        $data = <<<'ENRICHMENTS'
Malaysia Mega Sale|2026-06-15|2026-07-31|\N|\N|A nationwide retail campaign bringing seasonal promotions to shopping destinations across Malaysia.|Malaysia Mega Sale is a nationwide shopping campaign held across retail destinations throughout Malaysia. Participating malls and retailers offer promotions across goods such as apparel, crafts, electronics and lifestyle products.|https://www.malaysia.travel/storage/13515/conversions/409bf908396c9a7fee20d5ccc48979e2-large.jpg
Tatreez : Reclaiming Palestine Through Embroidery|2026-06-19|2027-04-25|\N|\N|A powerful cultural exhibition showcasing Palestinian embroidery…|A powerful cultural exhibition showcasing Palestinian embroidery art and heritage. The event is held at Special Gallery 1 & 2, Islamic Arts Museum Malaysia and is organised by Islamic Arts Museum Malaysia.|https://www.malaysia.travel/storage/13548/conversions/7fb68e6c78766fcf67a509b418e6e138-large.jpg
Malaysia Gifts Fair 2026|2026-06-30|2026-07-02|\N|\N|A trade fair showcasing Malaysian gifts, crafts and premium products.|Malaysia Gifts Fair 2026 is a trade event for Malaysian gifts, crafts and premium products. It is organised by the Malaysian Gifts and Premium Association for suppliers and other industry participants.|https://www.malaysia.travel/storage/10970/conversions/00ad33e911d76120210cac180c8e5a17-large.jpg
Pesta Sebauh 2026|2026-07-03|2026-07-12|\N|\N|A vibrant cultural festival celebrating the rich heritage of Bintulu.|A vibrant cultural festival celebrating the rich heritage of Bintulu with traditional performances and food.|https://www.malaysia.travel/storage/13200/conversions/9318e7880dd999e4aec11f5405947f0e-large.jpg
Malaysia Durian Festival 2026|2026-07-04|2026-07-05|\N|\N|A festival celebrating Malaysian durian through tastings, competitions and related food products.|Malaysia Durian Festival 2026 celebrates Malaysian durian culture at KLDEX in Kuala Lumpur. The festival includes tastings, competitions and durian-based food products.|https://www.malaysia.travel/storage/13496/conversions/da49ea792543419e74bb0cd4bf8fb9e6-large.jpg
Kinabalu Sky Fest 2026|2026-07-04|2026-07-04|\N|\N|A music festival showcasing Sabah and Native musicians alongside Malaysian indie and local artists in a celebration of culture and live performance.|Kinabalu Sky Fest is a music festival and industry platform bringing together Sabah Sounds, Native musicians, Malaysian indie bands and local Sabah artists. It promotes cultural heritage, artistic collaboration, industry engagement and live musical performances.|https://www.malaysia.travel/storage/13217/conversions/6d4729c46ad7c0386771e464d2b29821-large.jpg
Muallim Cross Country Run 2026|2026-07-05|2026-07-05|\N|\N|Challenging cross-country running event through the scenic…|Challenging cross-country running event through the scenic landscapes of Perak. The event is held at Muallim, Perak and is organised by Majlis Daerah Tanjong Malim(MDTM).|https://www.malaysia.travel/storage/13248/conversions/af829a6ddfd0ab6345fd991e00342b5d-large.jpg
I Food Expo|2026-07-10|2026-07-12|\N|\N|A consumer expo featuring products across a wide range of food and beverage categories.|I Food Expo brings food and beverage businesses and consumers together at Pavilion Bukit Jalil Exhibition Hall. Its product range spans snacks, dairy, meat, fruit and vegetables, seafood, confectionery, coffee, tea and nutritional products.|https://www.malaysia.travel/storage/13205/conversions/2ce3c84a3b931f5a9656485f69785385-large.jpg
Asian Youth Festival 2026|2026-07-10|2026-07-12|\N|\N|A youth festival bringing together performance, talent and cultural exchange in Kuala Lumpur.|Asian Youth Festival 2026 brings young participants together around passion, talent and cultural exchange at the Malaysia International Exhibition and Convention Centre. The festival presents a cross-cultural youth experience in Kuala Lumpur.|https://www.malaysia.travel/storage/13213/conversions/8b4df66e86aaf0b392a9d0e15d68350a-large.jpg
Miss SHOPhia Shopping Hunt 2026|2026-07-11|2026-07-11|\N|\N|A competitive shopping hunt staged with CatsFM in Sarawak.|Miss SHOPhia Shopping Hunt 2026 is a competitive shopping activity staged with CatsFM in Sarawak. Tourism Malaysia and CatsFM organise the event with participating strategic partners.|https://www.malaysia.travel/storage/13737/conversions/0c105dcbdcbff468874f244ab06ba125-large.jpg
Ipoh Half Marathon 2026|2026-07-12|2026-07-12|\N|\N|A community half marathon through Ipoh’s city streets.|Ipoh Half Marathon 2026 is a road-running event through Ipoh. It gives participants a route through the city and is organised by JSJT Sirna Sdn. Bhd.|https://www.malaysia.travel/storage/13199/conversions/c622be722ee1466c1f2fcb55d3a2b4ed-large.jpg
Royal Selangor Jazz Festival 2026|2026-07-12|2026-07-12|\N|\N|A live jazz festival presented across the Royal Selangor grounds.|Royal Selangor Jazz Festival 2026 presents live jazz performances across four stages at Royal Selangor. The programme gives audiences opportunities to discover different performers and sounds throughout the venue.|https://www.malaysia.travel/storage/13736/conversions/869be30685c6f31fee73ee004f2b86b8-large.jpg
The Malaysian International Food & Beverage Trade Fair|2026-07-15|2026-07-17|\N|\N|An international food and beverage trade fair for industry exhibitors.|The Malaysian International Food & Beverage Trade Fair (MIFB) is a major ASEAN food and beverage trade event connecting stakeholders across the industry. The 2026 edition focuses on sustainability, digital transformation, changing consumer trends, café culture and food technology innovation.|https://www.malaysia.travel/storage/12318/conversions/51b0f523c6a8b0a5cb6efc5ca5c3e04b-large.jpg
Penang Hill Festival 2026|2026-07-17|2026-07-19|\N|\N|A three-day celebration of Penang Hill combining nature, heritage, culture, guided activities, exhibitions, workshops and live music.|Penang Hill Festival 2026 celebrates the natural, cultural and historical heritage of Penang Hill through guided discovery walks, talks, exhibitions, workshops and performances. The three-day festival highlights the Penang Hill Biosphere Reserve and includes the signature Music on the Hill programme.|https://www.malaysia.travel/storage/13335/conversions/01e105c299e8c44af0436b9e1c9d0bc1-large.jpg
Cameron Ultra Race 2026|2026-07-17|2026-07-19|\N|\N|An ultra-trail running event based in the highlands of Tanah Rata.|Cameron Ultra Race 2026 is an ultra-trail running event based in Tanah Rata, Cameron Highlands. The highland race is organised by Kelab Larian SUKADEI and BOLT 360 Management for the trail-running community.|https://www.malaysia.travel/storage/13255/conversions/700d777017efeaa17514be10af208660-large.jpg
Malaysia International Film Festival|2026-07-18|2026-07-25|\N|\N|Showcasing the best of Malaysian and international cinema.|Showcasing the best of Malaysian and international cinema with film screenings and awards. The event is held at Kuala Lumpur and is organised by Jazzy Group.|https://www.malaysia.travel/storage/13392/conversions/d18fae2cf79a3f3c6a7f841c20c2c6d7-large.jpg
Metropolitan Rhythms: The Emigré and the American|2026-07-18|2026-07-18|\N|\N|A classical music concert at Dewan Filharmonik PETRONAS.|Metropolitan Rhythms: The Emigré and the American is a classical music programme presented at Dewan Filharmonik PETRONAS. The concert is organised by the Malaysian Philharmonic Orchestra.|https://www.malaysia.travel/storage/11927/conversions/428acdeb021893159cec899a6be05f32-large.jpg
Festival Udara Taiping 2026|2026-07-23|2026-07-26|\N|\N|An aviation festival combining aircraft displays with sports, education and family activities.|Festival Udara Taiping 2026 combines aircraft displays with sports, entertainment, education and family activities at Lapangan Terbang Tekah. It is organised by Kelab Sukan Udara Taiping.|https://www.malaysia.travel/storage/13748/bea0f5618adfc960979f279abdfcc14d.jpg
Festival Kraf Utara 2026|2026-07-23|2026-08-09|\N|\N|A northern craft festival celebrating Malaysian handicrafts and traditional artisans.|Festival Kraf Utara 2026 presents Malaysian handicrafts, craft demonstrations, exhibitions and cultural activities supporting traditional artisans. The festival is hosted at Kraftangan Malaysia’s Perlis branch by the Malaysian Handicraft Development Corporation.|https://www.malaysia.travel/storage/13780/conversions/134cf15f01e54126a5e18a33f6b40e00-large.jpg
Taiping Air Festival|2026-07-23|2026-07-26|\N|\N|An aircraft festival featuring modern and vintage aviation displays in Taiping.|Taiping Air Festival features modern and vintage aircraft displays at Lapangan Terbang Tekah in Taiping, Perak. It is organised by Flytrikemy, Festival Udara Taiping and Kelab Sukan Udara.|https://www.malaysia.travel/storage/13401/conversions/f2a81a37ca02b2cb312ca90d52fa4171-large.jpg
Citrawarna 2026|2026-07-24|2026-07-26|\N|\N|Malaysia's signature cultural parade with colorful floats…|Malaysia's signature cultural parade with colorful floats, traditional costumes, and performances. The event is held at Merdeka Square and is organised by Tourism Malaysia.|https://www.malaysia.travel/storage/13179/conversions/9ddb8d66c3afa6c0033598785663c026-large.jpg
Tampin Trans Naning Ultra|2026-07-24|2026-07-26|\N|\N|An ultra-distance trail-running event in Tampin.|Ultra-distance running event through the historic trails of Tampin. One of Peninsular Malaysia’s toughest trail races remains part of the Grandmaster Quest calendar this season, now set for a new date.|https://www.malaysia.travel/storage/13391/conversions/d772961736b130da95c70193a85cd319-large.jpg
Lenggong Outdoor Festival|2026-07-24|2026-07-26|\N|\N|Outdoor adventure festival in the UNESCO-listed Lenggong Valley.|Outdoor adventure festival in the UNESCO-listed Lenggong Valley with jungle trekking and cultural activities.|https://www.malaysia.travel/storage/13379/conversions/32b7d41cf75d6e51412eeb3d396e7437-large.jpg
Ironman 70.3 Desaru Coast|2026-07-24|2026-07-26|\N|\N|A long-distance triathlon staged at Desaru Coast.|Ironman 70.3 Desaru Coast is a long-distance triathlon at Desaru Coast, Johor. Organised by Ironman Malaysia, the event combines swimming, cycling and running.|https://www.malaysia.travel/storage/13333/conversions/d455d7ae6c8f2bd9ef176aa30cf16b37-large.jpg
Temasya Orang Kedah|2026-07-30|2026-08-02|\N|\N|A cultural celebration showcasing Kedah’s heritage through traditional performances.|Temasya Orang Kedah showcases Kedah’s heritage through traditional performances, art exhibitions, local cuisine, folk games and community activities. The event honours and preserves the traditions, customs and cultural legacy of the Kedah community.|https://www.malaysia.travel/storage/13514/conversions/db153b51eda928f61e8299b058156838-large.jpg
Pesta Kuantan 188|2026-07-31|2026-08-02|\N|\N|A community festival held in conjunction with the Sultan of Pahang’s birthday.|A community festival held in conjunction with the Sultan of Pahang’s birthday, featuring powerboat racing, fun runs, cycling activities, concerts, and a fireworks display. The Kuantan 188 Festival will be held in conjunction with the birthday of the Sultan of Pahang and various exciting events will be held such as the F3000 Power Boat Race, Kuantan 188 Fun Run & Fun Ride, the Kuantan 188 Festival Concert and a fireworks display.|https://www.malaysia.travel/storage/13747/conversions/2fc6adcb8bf76204ee1bb4f2ca7d909c-large.jpg
Art Of Speed|2026-08-01|2026-08-02|\N|\N|An annual event featuring artwork in canvas, poster, moving…|An annual event featuring artwork in canvas, poster, moving visual, sculpture and rolling-art formats.|https://www.malaysia.travel/storage/13339/conversions/b106ddd1c407bcfed4c4f6ec30691f2e-large.jpg
Youth Performance Speed Fest|2026-08-07|2026-08-09|\N|\N|An automotive festival featuring modified cars, motorcycles and performance events.|Youth Performance Speed Fest features car and motorcycle shows, drifting, test-and-tune sessions, competitions and modified vehicles. The automotive festival is staged at Puteri Harbour in Iskandar Puteri by Fasha Damia Pictures Sdn. Bhd.|https://www.malaysia.travel/storage/13265/conversions/68bf3b9d7e8b24d43592f3dfa15605f4-large.jpg
Taiping Half Marathon|2026-08-09|2026-08-09|\N|\N|A scenic running event through Taiping that encourages endurance.|A scenic running event through Taiping that encourages endurance, fitness, and community participation while highlighting the town’s natural and historical surroundings. The event is held at Dataran Warisan Taiping and is organised by Kelab Rekreasi Pelari-Pelari Larut Matang Dan Selama Perak.|https://www.malaysia.travel/storage/13266/conversions/c4bc74a8b86c1a5a35763feaba48abe7-large.jpg
Penang Hill Heritage Forest Challenge 2026 (PHHFC2026)|2026-08-16|2026-08-16|\N|\N|An annual trail running challenge through Penang Hill’s forest routes that promotes eco-tourism.|An annual trail running challenge through Penang Hill’s forest routes that promotes eco-tourism, environmental conservation, endurance, and appreciation of the biosphere reserve. Penang Hill Corporation (PHC) is excited to welcome participants back for the 11th edition of the Penang Hill Heritage Forest Challenge (PHHFC), happening on Sunday, 16 August 2026.|https://www.malaysia.travel/storage/13340/conversions/9e6cb4ffbd8e8e86cfb4ed6d8d2763a9-large.jpg
Pahang Eco 2026|2026-08-21|2026-08-23|\N|\N|An outdoor eco-sports event offering endurance challenges and recreational activities while promoting Pahang’s…|An outdoor eco-sports event offering endurance challenges and recreational activities while promoting Pahang’s natural environment and eco-tourism attractions. The event is held at Teluk Cempedak, Kuantan.|https://www.malaysia.travel/storage/13407/conversions/b1ce780b37001d37de61cba3a798d453-large.jpg
A Heart Unveiled: The Music of Tchaikovsky|2026-08-22|2026-08-22|\N|\N|A Malaysian Philharmonic Orchestra concert featuring Tchaikovsky’s Violin Concerto and Symphony No.|A Malaysian Philharmonic Orchestra concert featuring Tchaikovsky’s Violin Concerto and Symphony No. 6 in an evening of expressive and dramatic classical music.|https://www.malaysia.travel/storage/11880/conversions/a877876701ba7a3a4eb4101541afd867-large.jpg
The Sky Race|2026-08-22|2026-08-23|\N|\N|A vertical stair-climbing challenge at Merdeka 118.|A vertical stair-climbing challenge at Merdeka 118 where participants compete to conquer 118 floors in an international endurance event.|https://www.malaysia.travel/storage/13337/conversions/f2fa77b7bb6c1a048b0a0b5dcede3ad9-large.jpg
Kodaline – Farewell Tour|2026-08-26|2026-08-26|\N|\N|The final concert tour by Irish band Kodaline.|The final concert tour by Irish band Kodaline, featuring their most popular songs and music from their fifth and final studio album.|https://www.malaysia.travel/storage/13207/conversions/b4274544c32553c2df3bd34bf2258acc-large.jpg
A Knight's Tale: Valor and Romance in Music|2026-08-29|2026-08-29|\N|\N|An MPO concert exploring courage and romance through orchestral works.|A Malaysian Philharmonic Orchestra concert featuring works by Dvořák, Walton and Schumann. The programme explores themes of courage, romance and orchestral expression.|https://www.malaysia.travel/storage/11864/conversions/481fa90fdeca9cf693b6a616aa40ebcb-large.jpg
Bukit Maras Trail Run Challenge 2.0|2026-08-29|2026-08-29|\N|\N|A trail running and hiking event promoting Bukit Maras as a sports tourism destination while providing outdoor…|A trail running and hiking event promoting Bukit Maras as a sports tourism destination while providing outdoor challenges for recreational and competitive participants. It aims to position Terengganu as one of the leading destinations for hosting trail running events at both domestic and international levels, while providing a high-standard competitive platform for the established trail running community.|https://www.malaysia.travel/storage/13261/conversions/af54c955e59665419705e65aa217a93a-large.jpg
Malaysia Marathon 2026|2026-08-30|2026-08-30|\N|\N|A national marathon in Kuala Lumpur offering several race distances around National Day.|Malaysia Marathon 2026 is a national road-running event in Kuala Lumpur offering several race distances. Held around National Day, it connects fitness and community participation with the Malaysian spirit.|https://www.malaysia.travel/storage/13338/conversions/c30c3424250f27c590c6b3d0e20657f2-large.jpg
Merdeka Day|2026-08-31|2026-08-31|\N|\N|Malaysia’s national celebration commemorating independence and unity.|Malaysia’s annual Independence Day commemorates the nation’s independence. Public celebrations include patriotic ceremonies, cultural performances and national observances.|https://www.malaysia.travel/storage/10949/conversions/2f94c421f0f9ff6aa4549b23c6c6db4f-large.jpg
A Tribute to Alfonso Soliano|2026-09-02|2026-09-02|\N|\N|A tribute concert celebrating the legacy of Malaysian music pioneer Alfonso Soliano.|A tribute concert celebrating the legacy of Malaysian music pioneer Alfonso Soliano, featuring the Malaysian Philharmonic Orchestra and The Solianos in a special orchestral performance. The event is held at Dewan Filharmonik PETRONAS and is organised by Malaysian Philharmonic Orchestra.|https://www.malaysia.travel/storage/11859/conversions/e696b6655820359b3da882c95c13e6aa-large.jpg
Sepilok Jazz Festival|2026-09-04|2026-09-05|\N|\N|An annual jazz festival held in the rainforest of Sepilok.|An annual jazz festival held in the rainforest of Sepilok, bringing together local and international musicians for a unique musical experience surrounded by nature. Sepilok Jazz Festival is a unique annual music event set in the lush rainforest of Sepilok, Sandakan, Sabah.|https://www.malaysia.travel/storage/13745/conversions/335f1f3383a4c26a22906c4cdab07219-large.jpg
Yin and Yang: A Dance Kaleidoscope|2026-09-05|2026-09-05|\N|\N|A contemporary dance performance celebrating Malaysia’s multicultural heritage through storytelling.|A contemporary dance performance celebrating Malaysia’s multicultural heritage through storytelling, traditional influences, and artistic movement. The event is held at Dewan Filharmonik PETRONAS.|https://www.malaysia.travel/storage/11915/conversions/3ef1a1adb86fb3f48c0fb31e28a92ab4-large.jpg
PJ Half Marathon|2026-09-06|2026-09-06|\N|\N|One of Malaysia’s longest-running half marathons, bringing together local and international runners in a…|One of Malaysia’s longest-running half marathons, bringing together local and international runners in a community-focused road running event. The event is held at Laman MBPJ, Petaling Jaya and is organised by Tourism Selangor.|https://www.malaysia.travel/storage/13341/conversions/e252547e0257645c30c2da284bafd7bd-large.jpg
Malaysia Ultra-Trail By Utmb|2026-09-10|2026-09-13|\N|\N|An international trail-running event through Taiping’s natural landscapes.|Malaysia Ultra-Trail by UTMB is an international trail-running event through Taiping’s landscapes. Its routes highlight the Taiping Lake Gardens, including its lakes and mature rain trees.|https://www.malaysia.travel/storage/13752/7135b15a107284f0ce48288c01341132.png
Maurice Steger's Nature Concerti|2026-09-12|2026-09-12|\N|\N|Nature-inspired Baroque concerti led by recorder virtuoso Maurice Steger.|A Malaysian Philharmonic Orchestra concert led by recorder virtuoso Maurice Steger. The programme presents imaginative Baroque works inspired by nature.|https://www.malaysia.travel/storage/11856/conversions/03ea1edf40f8e9e0589479e19ed132c9-large.jpg
RHB Lekas Highway Ride 2026|2026-09-12|2026-09-12|\N|\N|A large-scale night cycling event offering 78-kilometre and…|A large-scale night cycling event offering 78-kilometre and 105-kilometre routes, including a challenging King of Mountain climb.|https://www.malaysia.travel/storage/13750/3be01d4d35fa7427d6e3745638ea0028.jpg
Keretapi Sarong 2026|2026-09-16|2026-09-16|\N|\N|A nationwide cultural gathering themed Ethnicity, featuring traditional dance, music and storytelling that…|A nationwide cultural gathering themed Ethnicity, featuring traditional dance, music and storytelling that celebrates Malaysia's multicultural identity. The event is held at Klang Valley, Johor Bahru, Ipoh, Pasir Mas, Kuantan Sungai Petani and is organised by LOCCO.|https://www.malaysia.travel/storage/13380/conversions/2355397b78496932ebad50775ab9fad9-large.jpg
Powerman Malaysia 2026|2026-09-18|2026-09-20|\N|\N|A duathlon focused on athletes, community and endurance competition.|POWERMAN Malaysia returns in 2026 with a focus on athletes, community and international competition. The duathlon combines running and cycling in an endurance format.|https://www.malaysia.travel/storage/12613/conversions/e4778c424d1ae0a13edf9f8209240ec8-large.jpg
Malaysia Sarong Music Run 2026|2026-09-19|2026-09-19|\N|\N|A cultural fun run combining Malaysian music, local food, traditional performances, and participants dressed in…|A cultural fun run combining Malaysian music, local food, traditional performances, and participants dressed in sarongs to celebrate heritage and national unity. The event is held at KLCC and is organised by SCORE.|https://www.malaysia.travel/storage/11564/conversions/fedd6dea0df5528811b912adf21bbdbb-large.jpg
Malaysia International Craft Fair|2026-09-24|2026-10-05|\N|\N|An international craft exhibition showcasing Malaysian heritage arts.|An international craft exhibition showcasing Malaysian heritage arts, traditional handicrafts, and artisanal products from local and global creators. The Malaysia International Craft Fair (MICF) 2026 is an annual showcase of heritage arts, local crafts, and global artisanal product.|https://www.malaysia.travel/storage/13428/conversions/07727ee94a33e9ab168487910c967bf0-large.jpg
VM2026 Food Festival @ MATIC|2026-09-25|2026-09-27|\N|\N|A showcase of Malaysia’s diverse food heritage and local delicacies at MaTiC.|A culinary festival at the Malaysia Tourism Centre showcasing Malaysia’s diverse food heritage. It highlights local delicacies and gastronomic traditions in conjunction with Visit Malaysia 2026.|https://www.malaysia.travel/storage/13211/conversions/235536ea5a3ac438f82f6abd40d21921-large.jpg
Three By Three|2026-10-03|2026-10-03|\N|\N|A Malaysian Philharmonic Orchestra concert featuring Beethoven’s Triple Concerto and Vaughan Williams’ Symphony No.|A Malaysian Philharmonic Orchestra concert featuring Beethoven’s Triple Concerto and Vaughan Williams’ Symphony No. 5, performed with three rising instrumental soloists.|https://www.malaysia.travel/storage/11853/conversions/231ffc997e978ac24edd6d0849ed967d-large.jpg
The Music of Queen…Lives On!|2026-10-10|2026-10-10|\N|\N|A symphonic rock concert combining the Malaysian Philharmonic Orchestra.|A symphonic rock concert combining the Malaysian Philharmonic Orchestra, a live rock band, and a vocalist to perform Queen’s most iconic songs. The event is held at Dewan Filharmonik PETRONAS and is organised by Malaysian Philharmonic Orchestra.|https://www.malaysia.travel/storage/11850/conversions/cbb290e331e48a15a109dc531d67843d-large.jpg
Jaclyn Victor Gemilang Bersama MPO|2026-10-17|2026-10-17|\N|\N|A special concert celebrating Jaclyn Victor’s two-decade musical career through new orchestral arrangements…|A special concert celebrating Jaclyn Victor’s two-decade musical career through new orchestral arrangements performed with the Malaysian Philharmonic Orchestra. The event is held at Dewan Filharmonik PETRONAS and is organised by Malaysian Philharmonic Orchestra.|https://www.malaysia.travel/storage/11847/conversions/9374504b837ae0a150b4496d2f21213c-large.jpg
Challenge Malaysia|2026-10-17|2026-10-18|\N|\N|A multi-category triathlon featuring swimming, cycling, and…|A multi-category triathlon featuring swimming, cycling, and running events for children and adults of different ages and fitness levels.|https://www.malaysia.travel/storage/13277/conversions/a05cbae84dbceaca381e9eda0234c912-large.jpg
KOKOL Ultra 2026|2026-10-23|2026-10-25|\N|\N|An international-standard ultra-trail event offering race distances from 15 to 70 kilometres.|An international-standard ultra-trail event offering race distances from 15 to 70 kilometres, including recognised qualifier and performance-point opportunities. The 70km category serves as the flagship race and is recognized as an Asia Trail Master (ATM) Qualifier, while also offering UTMB Index eligibility and ITRA Performance Points for participants.|https://www.malaysia.travel/storage/13409/conversions/a82e495b7810b44b26e9686fff7ab63a-large.jpg
Sarawak International Dragon Boat Regatta 2026|2026-10-24|2026-10-26|\N|\N|An international dragon boat regatta held at the Kuching Waterfront.|An international dragon boat regatta held at the Kuching Waterfront. Racing crews compete on the Sarawak River in this sporting event.|https://www.malaysia.travel/storage/13412/conversions/8baba3ab5bd84a4af742e90c9e7599f2-large.jpg
A Regal Evening with Stephen Hough|2026-10-24|2026-10-24|\N|\N|Stephen Hough performs Beethoven’s Emperor Concerto with the MPO.|An all-Beethoven Malaysian Philharmonic Orchestra concert featuring Stephen Hough performing the Emperor Concerto. The programme continues with Beethoven’s Symphony No. 4.|https://www.malaysia.travel/storage/11844/conversions/e3688048d7c930551400704aab894573-large.jpg
Petronas Grand Prix of Malaysia (MotoGP)|2026-10-30|2026-11-01|\N|\N|Malaysia’s premier international motorcycle racing event at Sepang Circuit.|The Petronas Grand Prix of Malaysia brings MotoGP riders to the Sepang International Circuit for international motorcycle racing. The event forms part of the 2026 MotoGP season in Malaysia.|https://www.malaysia.travel/storage/13345/conversions/f389ed21c83d188b37bb1f09ac30bad2-large.jpg
Beats of Borneo: Alena Murang with the MPO|2026-10-31|2026-10-31|\N|\N|A Malaysian Philharmonic Orchestra concert featuring Alena Murang and the premiere of a new concerto for the…|A Malaysian Philharmonic Orchestra concert featuring Alena Murang and the premiere of a new concerto for the traditional sape’, bridging Bornean heritage with orchestral music. The event is held at Dewan Filharmonik PETRONAS and is organised by Malaysian Philharmonic Orchestra.|https://www.malaysia.travel/storage/11841/conversions/e6980c9e0efc5572bad540a1892a6720-large.jpg
Selangor Marathon 2026|2026-11-01|2026-11-01|\N|\N|A road-running event held at Persiaran Flora in Cyberjaya.|A road-running event held at Persiaran Flora in Cyberjaya. It provides a community sporting experience for marathon participants and supporters.|https://www.malaysia.travel/storage/13336/conversions/d33174d33bebf09268b6be2f94c72d6a-large.jpg
LANY : Soft World Tour|2026-11-01|2026-11-01|\N|\N|American pop band LANY brings its Soft World Tour to Kuala Lumpur.|American pop band LANY brings its Soft World Tour to Unifi Arena in Kuala Lumpur. The live concert is presented by Live Nation on 1 November 2026.|https://www.malaysia.travel/storage/13220/conversions/6c756e4d90192830e807070ecfc49db5-large.jpg
As If She Were Here: Chen Jia Sings Teresa Teng|2026-11-07|2026-11-29|\N|\N|A Malaysian Philharmonic Orchestra concert featuring vocalist Chen Jia under conductor Francis Kan.|A Malaysian Philharmonic Orchestra concert featuring vocalist Chen Jia under conductor Francis Kan. The programme celebrates the music associated with Teresa Teng.|https://www.malaysia.travel/storage/11835/conversions/018a9e29e7ec1f615b107a11fe8ef36c-large.jpg
Deepavali|2026-11-08|2026-11-08|\N|\N|observed throughout Malaysia as the Hindu festival of lights.|Deepavali is observed throughout Malaysia as the Hindu festival of lights. The celebration brings communities together for religious and cultural observances.|https://www.malaysia.travel/storage/10690/conversions/0396e7c2fd6ec4626f5143f0db4aea79-large.jpg
The 36th Raja Muda Selangor International Regatta 2026|2026-11-13|2026-11-21|\N|\N|An international sailing regatta organised by the Royal Selangor Yacht Club.|An international sailing regatta organised by the Royal Selangor Yacht Club. Its route connects Port Klang, Pangkor Island, Penang and Langkawi.|https://www.malaysia.travel/storage/11540/conversions/81aa85e88bc77b210bbbfde205117700-large.jpg
Simfoni Mantra: Kunto Aji bersama MPO|2026-11-14|2026-11-14|\N|\N|An orchestral collaboration between Kunto Aji and the MPO.|Simfoni Mantra features Indonesian singer-songwriter Kunto Aji performing with the Malaysian Philharmonic Orchestra. The concert is presented at Dewan Filharmonik PETRONAS.|https://www.malaysia.travel/storage/11912/conversions/fec52d46c5ef81c849764e34b1f57865-large.jpg
Penang International Dragon Boat Regatta 2026|2026-11-20|2026-11-22|2026-11-27|2026-11-29|Competitive dragon boat racing accompanied by drumbeats and stage performances.|A dragon boat racing event featuring competitive teams, vigorous drumbeats, lively crowds and stage performances as participants compete for championship honours.|https://www.malaysia.travel/storage/13354/conversions/3bc0d4c2d12a6c5dce9c5d232291028b-large.jpg
Ironman 70.3 Langkawi|2026-11-21|2026-11-21|\N|\N|An endurance triathlon through Langkawi’s coastal, village and forest landscapes.|IRONMAN 70.3 Langkawi takes athletes through Langkawi’s coastal roads, villages and forest landscapes before finishing at Cenang Beach. The endurance race is presented alongside local culture and hospitality.|https://www.malaysia.travel/storage/7082/conversions/d1c429fbcc8b0e2a1b21a5240aa355c5-large.jpg
Wave to Earth|2026-11-22|2026-11-22|\N|\N|South Korean indie band Wave to Earth brings The Pieces Tour to Kuala Lumpur.|South Korean indie band Wave to Earth brings The Pieces Tour to Idea Live Arena in Kuala Lumpur. The concert features the band’s characteristic indie, jazz and lo-fi sound.|https://www.malaysia.travel/storage/13348/conversions/0c3ff7c8f0fe3eb5a6871d8a62d8bfbd-large.jpg
Malaysia World Masters Golf Championship 2026|2026-11-22|2026-11-28|\N|\N|An international week-long golf championship designed for club golfers.|The inaugural Malaysia World Masters Golf Championship is a week-long event designed for club golfers visiting from several countries. Staged in the Klang Valley, it is organised with international golf tour operators experienced in tournaments across Southeast Asia and Oceania.|https://www.malaysia.travel/storage/13279/conversions/ef431ef587e9c30882390b04fba29ca4-large.jpg
A Chorale Spectacular|2026-12-05|2026-12-05|\N|\N|A choral concert by the Malaysian Philharmonic Orchestra under conductor Junichi Hirokami.|A choral concert by the Malaysian Philharmonic Orchestra under conductor Junichi Hirokami, featuring the Kuala Lumpur City Opera Chorus at Dewan Filharmonik PETRONAS. The event is held at Dewan Filharmonik PETRONAS and is organised by Malaysian Philharmonic Orchestra.|https://www.malaysia.travel/storage/11838/conversions/217bde5886ea44bb2ab8b83efe70b8a6-large.jpg
Sibu Bike Week|2026-12-05|2026-12-06|\N|\N|The 14th Sibu International Bike Week is held at Dataran Tun Tuanku Bujang Phase 2.|The 14th Sibu International Bike Week is held at Dataran Tun Tuanku Bujang Phase 2. It is a gathering for bikers and motorsport enthusiasts from Borneo and the wider region.|https://www.malaysia.travel/storage/13346/conversions/1972e1accc85d01c532db041ce84437d-large.jpg
Nakawan Ultra 3.0|2026-12-11|2026-12-12|\N|\N|An ultra-trail event based at the Sungai Batu Pahat ecotourism complex in Perlis.|Nakawan Ultra 3.0 is an ultra-trail running event based at Bukit Air Resort within the Sungai Batu Pahat ecotourism complex in Perlis. The event is organised by Mep Ventures Sdn. Bhd.|https://www.malaysia.travel/storage/13441/conversions/8c9be4ba9ca670ca79c9e82607cfa615-large.jpg
Penang Bridge International Marathon|2026-12-13|2026-12-13|\N|\N|A mass-participation marathon connecting Penang Island and the mainland.|The Penang Bridge International Marathon is an annual mass-participation running event held on Penang Bridge. The route connects Penang Island and the mainland and attracts a large field of runners.|https://www.malaysia.travel/storage/3713/conversions/079db494ce4dfaba3393e579d2f4aad9-large.jpg
BTS World Tour ‘Arirang’ in Kuala Lumpur|2026-12-13|2026-12-13|\N|\N|South Korean group BTS brings its Arirang world tour to Stadium Nasional Bukit Jalil.|South Korean group BTS brings its Arirang world tour to Stadium Nasional Bukit Jalil. The Kuala Lumpur concert follows the release of the group’s fifth studio album, Arirang.|https://www.malaysia.travel/storage/13202/conversions/bae34373c20de9f9a4ae29832fc54681-large.jpg
Christmas Celebration|2026-12-25|2026-12-25|\N|\N|A national festive occasion observed across Malaysia on 25 December.|Christmas is observed across Malaysia on 25 December as a national festive occasion. Communities mark the celebration through religious and seasonal observances.|https://www.malaysia.travel/storage/11524/conversions/0f247eb435a985152d28f110a9c67e8d-large.jpg
ENRICHMENTS;

        $enrichments = [];

        foreach (preg_split('/\R/u', trim($data)) as $lineNumber => $line) {
            $fields = str_getcsv($line, '|');

            if (count($fields) !== 8) {
                throw new RuntimeException(sprintf(
                    'Invalid Experience enrichment on embedded line %d: expected 8 fields, found %d.',
                    $lineNumber + 1,
                    count($fields),
                ));
            }

            $fields = array_map(
                static fn (string $value): ?string => $value === '\\N' ? null : $value,
                $fields,
            );

            [$title, $currentStart, $currentEnd, $correctedStart, $correctedEnd, $shortDescription, $description, $imageUrl] = $fields;

            if ($title === null || $currentStart === null || $currentEnd === null) {
                throw new RuntimeException(sprintf('Missing identity field on enrichment line %d.', $lineNumber + 1));
            }

            if ($shortDescription === null || $shortDescription === '' || Str::length($shortDescription) > 255) {
                throw new RuntimeException(sprintf('Invalid short description for "%s".', $title));
            }

            if ($description === null || $description === '' || $description === self::FALLBACK_DESCRIPTION) {
                throw new RuntimeException(sprintf('Invalid full description for "%s".', $title));
            }

            if (($correctedStart === null) !== ($correctedEnd === null)) {
                throw new RuntimeException(sprintf('Incomplete corrected date range for "%s".', $title));
            }

            $enrichments[] = [
                'experiences_name' => $title,
                'current_start_date' => $currentStart,
                'current_end_date' => $currentEnd,
                'corrected_start_date' => $correctedStart,
                'corrected_end_date' => $correctedEnd,
                'short_description' => $shortDescription,
                'description' => $description,
                'image_url' => $imageUrl,
            ];
        }

        if (count($enrichments) !== 75) {
            throw new RuntimeException(sprintf(
                'Experience enrichment dataset must contain 75 records; found %d.',
                count($enrichments),
            ));
        }

        $dateCorrections = array_values(array_filter(
            $enrichments,
            static fn (array $enrichment): bool => $enrichment['corrected_start_date'] !== null,
        ));

        if (count($dateCorrections) !== 1
            || $dateCorrections[0]['experiences_name'] !== 'Penang International Dragon Boat Regatta 2026'
            || $dateCorrections[0]['current_start_date'] !== '2026-11-20'
            || $dateCorrections[0]['current_end_date'] !== '2026-11-22'
            || $dateCorrections[0]['corrected_start_date'] !== '2026-11-27'
            || $dateCorrections[0]['corrected_end_date'] !== '2026-11-29') {
            throw new RuntimeException('The enrichment dataset contains an unapproved date correction.');
        }

        return $enrichments;
    }

    private function normalize(?string $value): string
    {
        return Str::lower(preg_replace('/\s+/u', ' ', trim($value ?? '')) ?? '');
    }

    /** @param array{updated: int, unchanged: int, not_found: int, failed: int} $counters */
    private function printSummary(array $counters, bool $rolledBack): void
    {
        $message = sprintf(
            'Experience Enrichment: updated=%d, unchanged=%d, not_found=%d, failed=%d, transaction=%s.',
            $counters['updated'],
            $counters['unchanged'],
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
