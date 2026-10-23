<?php

namespace Database\Seeders;

use App\Enums\AgendaItemType;
use App\Enums\AssetsStatus;
use App\Enums\SessionKind;
use App\Models\AgendaItem;
use App\Models\Conference;
use App\Models\ConferenceSession;
use App\Models\Note;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The conference programme as of "Final Version" (23-10-2026).
 * All times are Prague local time, exactly as printed in the PDF.
 */
class Spp2026ConferenceSeeder extends Seeder
{
    public const NAME = 'The International Conference for Science Park Publisher (ICSPP-2026)';

    /** Name of the conference before the final programme replaced draft 8. */
    public const DRAFT_NAME = 'SPP Online Conference 2026';

    /** Author of the notes seeded from draft 8; they no longer apply. */
    private const DRAFT_NOTES_AUTHOR = 'PDF draft 8';

    private const WELCOME = 'Welcome Address and Opening of the Plenary Session';

    private const PUBLISHER = 'published by Science Park Publisher (SPP), Prague-Czechia';

    /** Speakers whose name is spelled differently in the final programme. */
    private const RENAMED = [
        'Prof. Ayman F. Abou Hdid' => 'Prof. Ayman F. Abou Hadid',
    ];

    public function run(): void
    {
        if (Conference::where('name', self::NAME)->exists()) {
            $this->command?->warn('"'.self::NAME.'" already exists, skipping.');

            return;
        }

        $conference = DB::transaction(function (): Conference {
            $conference = Conference::create([
                'name' => self::NAME,
                'timezone' => 'Europe/Prague',
                'team_timezone' => 'Africa/Cairo',
            ]);
            $this->createProgramme($conference);

            return $conference;
        });

        $this->command?->info('Seeded "'.self::NAME.'". Team link: '.$conference->shareUrl());
    }

    /**
     * Swaps the programme of an existing conference for the final one. The team
     * link stays, and statuses and notes follow each speaker to the new programme.
     */
    public function replaceProgramme(Conference $conference): void
    {
        DB::transaction(function () use ($conference): void {
            $conference->load('days.sessions.items.notes', 'days.sessions.notes');
            $sessions = $conference->days->flatMap->sessions;
            $items = $sessions->flatMap->items->filter(fn (AgendaItem $item) => $item->type->hasSpeaker());

            $progress = $items->keyBy(fn (AgendaItem $item) => self::personKey($item->speaker_name));
            $sessionNotes = $sessions->keyBy(fn (ConferenceSession $session) => Str::lower((string) $session->title))
                ->map(fn (ConferenceSession $session) => $session->notes);

            Note::query()->forConference($conference)->delete();
            $conference->days()->delete();
            $conference->update(['name' => self::NAME]);
            $this->createProgramme($conference);

            foreach ($conference->days()->with('sessions.items')->get()->flatMap->sessions as $session) {
                $this->copyNotes($sessionNotes->get(Str::lower((string) $session->title)), $session);

                foreach ($session->items as $item) {
                    $old = $item->type->hasSpeaker() ? $progress->get(self::personKey($item->speaker_name)) : null;
                    if ($old === null) {
                        continue;
                    }

                    $item->forceFill([
                        'speaker_status' => $old->speaker_status,
                        'assets_status' => $old->assets_status,
                        'updated_by' => $old->updated_by,
                        'updated_at' => $old->updated_at,
                    ])->save();
                    $this->copyNotes($old->notes, $item);
                }
            }
        });
    }

    /**
     * "Prof. Dr. Ibrahim El-Tantawy El-Sayed" and "Ibrahim El Tantawy El-Sayed" match.
     */
    private static function personKey(?string $name): string
    {
        $name = self::RENAMED[$name] ?? (string) $name;
        $name = preg_replace('/\b(prof|dr|eng|assoc|asst|ass)\b\.?/i', '', Str::ascii($name));

        return Str::lower(preg_replace('/[^a-z]/i', '', $name));
    }

    /**
     * @param  Collection<int, Note>|null  $notes
     */
    private function copyNotes(?Collection $notes, ConferenceSession|AgendaItem $notable): void
    {
        foreach (($notes ?? collect())->reverse() as $note) {
            if ($note->author_name === self::DRAFT_NOTES_AUTHOR) {
                continue;
            }

            $notable->notes()->make($note->only('author_name', 'body'))
                ->forceFill($note->only('created_at', 'updated_at'))
                ->save();
        }
    }

    private function createProgramme(Conference $conference): void
    {
        foreach ($this->programme() as $dayData) {
            $sessions = $dayData['sessions'];
            unset($dayData['sessions']);
            $day = $conference->days()->create($dayData);

            foreach ($sessions as $sessionData) {
                $items = $sessionData['items'] ?? [];
                unset($sessionData['items']);
                $session = $day->sessions()->create($sessionData);

                foreach ($items as $position => $itemData) {
                    $session->items()->create([...$itemData, 'position' => $position + 1]);
                }
            }
        }
    }

    /**
     * @param  string|null  $role  e.g. "Session Chair", printed before "Keynote Speaker 1"
     * @return array<string, mixed>
     */
    private function keynote(int $number, string $start, string $end, string $name, string $affiliation, ?string $title, ?string $role = null): array
    {
        return [
            'type' => AgendaItemType::Keynote,
            'label' => ($role ? "{$role} & " : '')."Keynote Speaker {$number}",
            'starts_at' => $start,
            'ends_at' => $end,
            'speaker_name' => $name,
            'affiliation' => $affiliation,
            'title' => $title,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function address(string $start, string $end, string $label, string $name, string $affiliation, string $title): array
    {
        return [
            'type' => AgendaItemType::Address,
            'label' => $label,
            'starts_at' => $start,
            'ends_at' => $end,
            'speaker_name' => $name,
            'affiliation' => $affiliation,
            'title' => $title,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presenter(int $number, string $start, string $end, string $name, string $affiliation, string $title): array
    {
        return [
            'type' => AgendaItemType::Oral,
            'label' => "Presenter {$number}",
            'starts_at' => $start,
            'ends_at' => $end,
            'speaker_name' => $name,
            'affiliation' => $affiliation,
            'title' => $title,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function segment(string $start, string $end, string $title, ?string $label = null): array
    {
        return [
            'type' => AgendaItemType::Segment,
            'label' => $label,
            'starts_at' => $start,
            'ends_at' => $end,
            'title' => $title,
            'assets_status' => AssetsStatus::NotNeeded,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function orals(string $start, string $end, string $title): array
    {
        return $this->segment($start, $end, $title, 'Oral Presentations');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function programme(): array
    {
        $publisher = self::PUBLISHER;

        return [
            [
                'date' => '2026-10-24',
                'title' => 'Sustainable Materials',
                'zoom_url' => 'https://us06web.zoom.us/j/81647609918',
                'sessions' => [
                    [
                        'number' => 1,
                        'title' => 'Opening Ceremony & Welcoming Talk',
                        'starts_at' => '09:00',
                        'ends_at' => '09:30',
                        'chair' => 'Dr. Ahmed Sharf',
                        'items' => [
                            $this->address('09:00', '09:30', 'The President of ICSPP-2026', 'Dr. Ahmed Sharf', 'Founder & CEO, Science Park Publisher & Science Park s.r.o., Prague-Czechia', 'From Vision to Scientific Community: The Science Park Publisher Journey and ICSPP 2026'),
                            $this->address('09:00', '09:30', 'Honored Chairman', 'Prof. Petrica Vizureanu', 'Professor at the Technical University of Iași, Romania', self::WELCOME),
                        ],
                    ],
                    [
                        'number' => 2,
                        'journal' => 'Integrated Nano (IN)',
                        'title' => 'Nanomaterials for Energy Harvesting & Sustainable Future',
                        'starts_at' => '09:30',
                        'ends_at' => '11:00',
                        'chair' => 'Prof. Nader Shahata',
                        'items' => [
                            $this->keynote(1, '09:30', '09:50', 'Prof. Nader Shahata', "Editor-in-Chief of the Integrated Nano (IN), {$publisher} & Professor, Department of Engineering Mathematics and Physics, Faculty of Engineering, Alexandria University, Alexandria, Egypt", self::WELCOME."\nPiezoelectric Nanocomposites: Horizon of Multidisciplinary Development", 'Session Chair'),
                            $this->orals('09:50', '10:00', '10 minutes'),
                            $this->presenter(1, '09:50', '10:00', 'Ahmed M. Saleh', 'Lecturer of Analytical Chemistry, Faculty of Pharmacy, Horus University Egypt. Biosensors Research Centre, Zewail City of Science and Technology', 'Integrating AI with Green and White Analytical Chemistry Metrics (AGREE, RGB12, BAGI, VIGI, and GLANCE): A Critical Review for Sustainability Assessment and Decision-Making in Nanomaterials Analysis'),
                            $this->keynote(2, '10:00', '10:15', 'Ankur Jain', "Editorial Board Member of the Integrated Nano (IN), {$publisher} & Professor, Centre for Renewable Energy & Storage, Suresh Gyan Vihar University, Jaipur 302017, India", 'Engineering Next-Generation Hydrogen Storage Materials for a Sustainable Future'),
                            $this->keynote(3, '10:15', '10:30', 'Prof. Kamal Alameh', "Editor-in-Chief of the Integrated Nano (IN), {$publisher}; Professor of Optics, Edith Cowan University, Australia & Kuwait College of Science and Technology (KCST), Kuwait", 'Nanotechnology-Enabled Building-Integrated Photovoltaics (BIPV): Transforming Buildings into Zero-Net-Energy Powerhouses'),
                            $this->keynote(4, '10:30', '10:45', 'Dr. Vaibhav Kulshreshtha', 'Council of Scientific and Industrial Research-Central Salt and Marine Chemicals Research Institute (CSIR-CSMCRI), Bhavnagar 364002, India', 'Polyelectrolyte Membranes for Strategic Devising for Electrochemical Hydrogen Generation and Utilization'),
                            $this->orals('10:45', '10:55', '10 minutes'),
                            $this->presenter(1, '10:45', '10:55', 'Jaya Varkey', 'Department of Chemistry, St. Teresa’s College, Ernakulam, 682011, Kochi, Kerala, India', 'Binder-Free Nanocellulose Drinking Straws from Sugarcane Bagasse: A Sustainable Alternative to Single-Use Plastic Straw'),
                            $this->segment('10:55', '11:00', 'Discussions & Closing'),
                        ],
                    ],
                    [
                        'number' => 3,
                        'journal' => 'Journal of Climate Change Action (JCCA)',
                        'title' => 'Carbon Capture and Carbon Footprint',
                        'starts_at' => '11:00',
                        'ends_at' => '12:15',
                        'chair' => 'Prof. Nour F. Attia',
                        'items' => [
                            $this->keynote(1, '11:00', '11:15', 'Prof. Nour F. Attia', "Editor-in-Chief of the Journal of Climate Change Action (JCCA), {$publisher} & Gas Analysis and Fire Safety Laboratory, Chemistry Division, National Institute for Standards (NIS), 136, Giza 12211, Egypt", self::WELCOME."\nSustainable and Scalable Materials for Carbon Capture and Utilization", 'Session Chair'),
                            $this->keynote(2, '11:15', '11:30', 'Prof. Alaa G.M. Osman', "Editor-in-Chief of the New Horizons Journal of Basic and Applied Sciences (NHJBAS), {$publisher}; Dean, Faculty of Science, Al-Azhar University, Assiut, Egypt & Professor at the Zoology Department, Faculty of Science, Al-Azhar University (Assiut Branch), Assiut, Egypt", 'IoT-Enhanced Aquaponics Sustainability'),
                            $this->keynote(3, '11:30', '11:45', 'Eng. Ayman Elrafie', 'Head of Mitigation & CDM Central Management, Egypt & Head of Carbon Credits Department & Climate Change Central Department, Egyptian Ministry of Environment, Egypt', 'Carbon Footprint and Neutralization Approaches'),
                            $this->keynote(4, '11:45', '12:00', 'Dr. Ahmed Mourtada Elseman', 'Electronic & Magnetic Materials Department, Central Metallurgical Research and Development Institute (CMRDI), Helwan, Cairo, Egypt', 'From Hazardous Waste to Solar Energy: Sustainable Recovery of Lead from Spent Batteries for Inkjet-Printed Perovskite Solar Cells'),
                            $this->orals('12:00', '12:10', '10 minutes'),
                            $this->presenter(1, '12:00', '12:10', 'Muqaddas Muqaddas', 'College of Resources and Environment, University of Chinese Academy of Sciences, 19 A Yuquan Road, Beijing 100049, China', 'Carbon Removal Delivery and the Credibility of Net Zero Commitments'),
                            $this->segment('12:10', '12:15', 'Discussions & Closing'),
                        ],
                    ],
                    [
                        'number' => 4,
                        'journal' => 'Journal of Agrochemicals and Food Safety (JAFS) & Agricultural Reports International (ARI)',
                        'title' => 'Green Agrochemicals and Safe Food Systems',
                        'starts_at' => '12:15',
                        'ends_at' => '13:30',
                        'chair' => 'Prof. Atef Nassar',
                        'items' => [
                            $this->keynote(1, '12:15', '12:30', 'Prof. Atef Nassar', "Editor-in-Chief of the Journal of Agrochemicals and Food Safety (JAFS) & Agricultural Reports International (ARI), {$publisher} & Professor of Pesticide Chemistry and Toxicology, Department of Plant Protection, Faculty of Agriculture at Damanhour University, Egypt", self::WELCOME."\nAgrochemicals and Food Safety and Technologies", 'Session Chair'),
                            $this->keynote(2, '12:30', '12:45', 'Prof. Sherif M. Sherif', 'Associate Professor at the Virginia Agricultural Research and Extension Centers, Virginia Tech, Winchester, VA, USA & School of Plant and Environmental Sciences, Virginia Tech, Winchester, Virginia, USA', 'Bridging Discovery and Deployment Environmental Stability of Minicell-Based Biofungicides for Sustainable Agriculture'),
                            $this->keynote(3, '12:45', '13:00', 'Prof. Konstantinos A. Aliferis', 'Director of Laboratory of Pesticide Science, Agricultural University of Athens, Greece & Biostimulant, Biocontrol, Pesticide Metabolomics-Group Leader', 'Redefining Agricultural Biologicals: New Approaches for the Discovery and Development of Next-Generation Biostimulants and Biocontrols'),
                            $this->keynote(4, '13:00', '13:15', 'Prof. Hazem Shaheen', "Editorial Board Member, Journal of Agrochemicals and Food Safety (JAFS), {$publisher} & Professor, Department of Veterinary Pharmacology and Therapeutics, Faculty of Veterinary Medicine, Damanhur University, Egypt", 'Nano collagen in drug delivery applications'),
                            $this->segment('13:15', '13:30', 'Discussions & Closing'),
                        ],
                    ],
                    [
                        'number' => 5,
                        'title' => 'Launched Journals',
                        'starts_at' => '13:30',
                        'ends_at' => '14:00',
                        'chair' => 'Dr. Ahmed Sharf',
                        'items' => [
                            $this->segment('13:30', '14:00', 'The Journal of Polymers and Industrial Innovation (JPII)', 'Launched journal'),
                            $this->keynote(1, '13:30', '14:00', 'Prof. Dr. Ibrahim El-Tantawy El-Sayed', "Editorial Board Member, the Journal of Polymers and Industrial Innovation (JPII), {$publisher} & Organic Chemistry, Medicinal and Green Chemistry", 'Welcome Address and Glimpse about the JPII'),
                            $this->segment('13:30', '14:00', 'The Journal of Literary Insights and English Linguistics (JLIEL)', 'Launched journal'),
                            $this->keynote(1, '13:30', '14:00', 'Prof. Sama Dawood', "Editor-in-Chief of the Journal of Literary Insights and English Linguistics (JLIEL), {$publisher} & Professor of Translation, Faculty of Al-Alsun and Mass Communication, Misr International University", null),
                            $this->keynote(2, '13:30', '14:00', 'Assoc. Prof. Basma Awad Ahmed Sarhan', "Editor-in-Chief of the Journal of Literary Insights and English Linguistics (JLIEL), {$publisher} & Associate Professor of Applied Linguistics, Department of English, Faculty of Arts, Mansoura University", 'The Journal of Literary Insights and English Linguistics: An Interdisciplinary Platform for Language, Literature, and Translation'),
                            $this->segment('13:30', '14:00', 'The Journal of Undergraduate Novelty, Innovation, Opportunities & Research (JUNIOR)', 'Launched journal'),
                            $this->keynote(1, '13:30', '14:00', 'Dr. Marwa Karim', "Editor-in-Chief of the Journal of Undergraduate Novelty, Innovation, Opportunities & Research (JUNIOR), {$publisher} & Assistant Professor, Physics Department, Faculty of Science, Alexandria University, Egypt", 'Welcome Address and Glimpse about the JUNIOR'),
                            $this->segment('13:30', '14:00', 'Journal of Computational Intelligence and Information Technology (JCIIT)', 'Launched journal'),
                            $this->keynote(1, '13:30', '14:00', 'Prof. Mohamed Sayed Farag', "Editorial Board Member, Journal of Computational Intelligence and Information Technology (JCIIT), {$publisher} & Professor of Computer Science and Dean of Faculty of Computers and Artificial Intelligence, Al-Azhar University, Egypt", 'Welcome Address and Glimpse about the JCIIT'),
                        ],
                    ],
                    [
                        'number' => 6,
                        'journal' => 'Advanced Carbon Journal (ACJ)',
                        'title' => 'Sustainable Carbon Nanomaterials and Technologies',
                        'starts_at' => '14:00',
                        'ends_at' => '15:15',
                        'chair' => 'Prof. Tarek Abdel-Fattah',
                        'co_chair' => 'Prof. Hesham Hamed',
                        'items' => [
                            $this->keynote(1, '14:00', '14:15', 'Prof. Tarek Abdel-Fattah', "Editor-in-Chief of the Advanced Carbon Journal (ACJ), {$publisher} & Lawrence J. Sacks Endowed Distinguished Professor of Chemistry, Director, FRESH Lab, Christopher Newport University, Virginia, USA", self::WELCOME."\nAdvanced Carbon Materials for Sustainable Energy: Innovations in Hydrogen Storage and Nanotechnology", 'Session Chair'),
                            $this->keynote(2, '14:15', '14:30', 'Prof. Sherif Kandil', 'Professor of Materials Science, Institute of Graduate Studies and Research (IGSR), Alexandria University', 'Computer Enhanced Spectroscopy: Novel Analysis of Renal Stone Calculi'),
                            $this->keynote(3, '14:30', '14:45', 'Prof. Hesham Hamed', "Associate Editor of the Advanced Carbon Journal (ACJ), {$publisher} & Fabrication Technology Research Department, Advanced Technology and New Materials Research Institute (ATNMRI), City of Scientific Research and Technology Applications (SRTA-City), New Borg El-Arab 21934, Egypt", 'New Trends in Photocatalytic Nanomaterials for Energy and Environmental Applications', 'Session Co-Chair'),
                            $this->keynote(4, '14:45', '15:00', 'Prof. Irene S. Fahmy', "Associate Editor of the Journal of Undergraduate Novelty, Innovation, Opportunities & Research (JUNIOR), {$publisher} & (Senior Member, IEEE-EDS Chair) Professor, Program Director of Industrial and Service Engineering and Management Department, Nile University, Cairo, Egypt, and the Director for Smart Engineering Systems Research Center, Nile University", 'From Agricultural Residues to Circular Value Chains: A Nexus-Based University Model for Sustainable Materials, Water and Agritech in Egypt'),
                            $this->orals('15:00', '15:10', '10 minutes'),
                            $this->presenter(1, '15:00', '15:10', 'Yasser Abdalrahman Akl', 'CNOOC Iraq, CNOOC International & Chemistry Department, Faculty of Science, Alexandria University, Egypt', 'Proposed Comprehensive Sustainability Assessment Index – Hydrogen synthesized by PEM as a Case study'),
                            $this->segment('15:10', '15:15', 'Discussion & Closing'),
                        ],
                    ],
                    [
                        'kind' => SessionKind::Ceremony,
                        'title' => 'Closing of Day 1',
                        'starts_at' => '15:15',
                        'ends_at' => '15:30',
                    ],
                ],
            ],
            [
                'date' => '2026-10-25',
                'title' => 'Biotechnology in Health and Therapeutics',
                'zoom_url' => 'https://us06web.zoom.us/j/87008929256?pwd=tnkqMbwpKO7XGGsdV2sm1W3IXUk5tt.1',
                'note' => 'Please note that Prague, Czechia, switches to winter time on Sunday, October 25, 2026, so the time difference may change.',
                'sessions' => [
                    [
                        'number' => 7,
                        'journal' => 'Trends in Pharmacology and Drug Delivery (TPDD)',
                        'title' => 'Biotechnology Innovations in Therapeutics',
                        'starts_at' => '09:00',
                        'ends_at' => '11:30',
                        'chair' => 'Prof. Labiba El-Khordagui, Editor-in-Chief',
                        'co_chair' => 'Part I: Prof. Magda EL-Massik, Associate Editor · Part II: Prof. Maged Wasfy, Associate Editor',
                        'items' => [
                            $this->segment('09:00', '10:00', 'Biotechnology Innovations in Therapeutics', 'Session 7 Part I'),
                            $this->address('09:00', '09:15', 'Session Co-Chair, Part I', 'Prof. Magda EL-Massik', "Associate Editor of the Trends in Pharmacology and Drug Delivery (TPDD), {$publisher} & Department of Pharmaceutics, Faculty of Pharmacy, Alexandria University, Egypt", self::WELCOME),
                            $this->keynote(1, '09:00', '09:15', 'Prof. Labiba El-Khordagui', "Editor-in-Chief of the Trends in Pharmacology and Drug Delivery (TPDD), {$publisher} & Professor, Department of Pharmaceutics, Faculty of Pharmacy, Alexandria University, Egypt", 'The vision behind Trends in Pharmacology and Drug Delivery', 'Session Chair'),
                            $this->keynote(2, '09:15', '09:30', 'Prof. Hamdy AbdElkader', 'Professor in Pharmaceutics, College of Pharmacy, King Khalid University, Saudi Arabia', 'Patient-Friendly and Sustainable Drug Delivery Innovations for Age-Related Ocular Diseases'),
                            $this->keynote(3, '09:30', '09:45', 'Dr. Sarah S. Nasr', 'Department of Pharmaceutics, Faculty of Pharmacy, Alexandria University, Egypt', 'Tissue-specific Gene Delivery Approaches'),
                            $this->segment('09:45', '10:00', 'Discussions & Video-graph of Posters'),
                            $this->segment('10:00', '11:30', "Biotechnology Innovations in Therapeutics\nSession Co-Chair: Prof. Maged Wasfy, Associate Editor of the Trends in Pharmacology and Drug Delivery (TPDD), {$publisher} & Professor, Department of Pharmacology, Faculty of Pharmacy, and Deputy Vice-President, Damanhur University, Egypt", 'Session 7 Part II'),
                            $this->keynote(4, '10:00', '10:15', 'Prof. Ahmed Fawzy El-Yazby', 'Department of Pharmacology and Therapeutics, Faculty of Pharmacy, Alamein International University and Alexandria University, Egypt', 'When Metabolism Meets Pollution: Decoding Endocannabinoid Dysfunction to Develop Sustainable Therapies for Cognitive Impairment'),
                            $this->keynote(5, '10:15', '10:30', 'Dr. Swati Dabral', 'Institute of Physiology, University of Würzburg, Germany', 'Therapeutic Potential of C Type Natriuretic Peptide Signaling in Pulmonary Fibrosis'),
                            $this->segment('10:30', '10:45', "Discussions & Closing\nVideo-graph of Posters"),
                            $this->orals('10:45', '11:30', 'Four presentations (10 minutes each) & Discussion'),
                            $this->presenter(1, '10:45', '10:55', 'Hebatallah Barakat', 'Faculty of Pharmacy, Alexandria University, Egypt', 'Lactoferrin-Functionalized Methyl Dihydrojasmonate Lipid Nanoparticles as An Intracervical Nanotherapeutic Approach for Cervical Cancer: In Vitro and In Vivo Evaluation'),
                            $this->presenter(2, '10:55', '11:05', 'Asst. Prof. Ripal Mistri', 'Indubhai Patel College of Pharmacy and Research Centre, Dharmaj, India', 'Nanosponge: An Emerging Nanomaterial Drug Delivery for Psychotic Disorder'),
                            $this->presenter(3, '11:05', '11:15', 'Jalpa Suthar', 'Department of Pharmacology & Clinical Pharmacy, Ramanbhai Patel College of Pharmacy (RPCP), Charotar University of Science and Technology (CHARUSAT)', 'Depression, Anxiety, and Self-Esteem in Adolescent and Reproductive-Age PCOS: Response to Individualized Pharmacotherapy'),
                            $this->presenter(4, '11:15', '11:25', 'Dr. Sally A. Sabra', 'Department of Biotechnology, Institute of Graduate Studies and Research, Alexandria University, Egypt', 'Fisetin Potentiates Tamoxifen Antitumor Efficacy in Experimental Breast Cancer'),
                            $this->segment('11:25', '11:30', 'Discussion'),
                        ],
                    ],
                    [
                        'number' => 8,
                        'journal' => 'Molecular Medicine and Biotechnology Advances (MMBA)',
                        'title' => 'One Health Approaches in Cancer Research, Molecular Medicine and Precision Therapeutics',
                        'starts_at' => '11:30',
                        'ends_at' => '13:30',
                        'chair' => 'Prof. Ahmed Samir Sultan',
                        'items' => [
                            $this->keynote(1, '11:30', '11:55', 'Prof. Ahmed Samir Sultan', "Editor-in-Chief of the Molecular Medicine and Biotechnology Advances (MMBA), {$publisher}; Biochemistry Department, Faculty of Science, Alexandria University, 21511 Alexandria, Egypt; Oncology Department, Lombardi Comprehensive Cancer Center & School of Medicine, Georgetown University Medical Center, Washington, DC 20057, USA", self::WELCOME."\nDual Targeting of long non-coding RNAs, HOTAIR and MALAT1, via Bioengineered Delivery Platforms Abrogates CD133⁺/CD90⁺ Liver Cancer Stemness and Xenograft Growth", 'Session Chair'),
                            $this->keynote(2, '11:55', '12:20', 'Prof. Nadia M. Hamdy', 'Professor of Pharmaceutical Clinical Biochemistry and Molecular Biology, Faculty of Pharmacy, Ain Shams University, Egypt & Pharmaceutical Clinical Biochemistry and (Epi)Genetics/(Epi)Genomics Research Groups Founder and Director, Faculty of Pharmacy, ASU, Egypt', 'Oncogenic LINCOO51 Genetic Variants Implicated in Breast and Colon Cancer Risk or Prognosis; a Step-Towards ncRNA Precision'),
                            $this->keynote(3, '12:20', '12:45', 'Dr. Reem Bakir', 'Head of Clinical Pharmacy Department & Armed Forces Medical Complex in Alexandria and Lecturer of Biotechnology, Military Medical Academy, Cairo, Egypt', 'Precision Medicine from Bench to Beside'),
                            $this->orals('12:45', '13:15', 'Four presentations (5–7 minutes each)'),
                            $this->presenter(1, '12:45', '13:15', 'Hussain Q. Alrubai', 'Department of Biochemistry, Faculty of Science, Alexandria University, Alexandria, Egypt', 'Venetoclax-Induced PRNCR1 Upregulation Coincides with Total ERK and β-Catenin Suppression in Non-t(11;14) Multiple Myeloma'),
                            $this->presenter(2, '12:45', '13:15', 'Alaa Ashraf', 'Biochemistry Department, Faculty of Science, Alexandria University, Alexandria, Egypt', 'Exosome-Like Nanovesicles Delivering siRNA Targeting HOTTIP to Inhibit Colorectal Cancer via miR-744-5p and the Notch Pathway'),
                            $this->presenter(3, '12:45', '13:15', 'Aya Shoukry Shoura', 'Biochemistry Department, Faculty of Science, Alexandria University, Alexandria, Egypt', 'Long Non-Coding RNA lncRNA SNHG5, Drives Cancer Stemness in Hepatocellular Carcinoma: A Target for Therapeutic Intervention'),
                            $this->presenter(4, '12:45', '13:15', 'P. Ramachandra Reddy', 'Department of Biochemistry, Yogi Vemana University, Vemanapuram, Kadapa-516005, Andhra Pradesh, India', 'Triple-Negative Breast Cancer: Epidemiology, Genetics, and Risk Factors'),
                            $this->segment('13:15', '13:30', "Discussions & Closing\nVideo-graph of Posters"),
                        ],
                    ],
                    [
                        'number' => 9,
                        'journal' => 'Journal of Veterinary Practice and Health (J. Vet. Pract. Health)',
                        'title' => 'One Health and Sustainable Veterinary Practices',
                        'starts_at' => '13:30',
                        'ends_at' => '16:00',
                        'chair' => 'Prof. Emad Hashish',
                        'co_chair' => 'Prof. Islam M. Saadeldin',
                        'items' => [
                            $this->keynote(1, '13:30', '13:55', 'Prof. Emad Hashish', "Editor-in-Chief, Journal of Veterinary Practice and Health, {$publisher}; Professor of Clinical Pathology, Faculty of Veterinary Medicine, Zagazig University, Egypt & Former Director of Egyptian Cultural and Educational Bureau in Kuwait", self::WELCOME."\nInnovative Vaccine Strategies for Combating Zoonotic Diseases: A one Health and Environmental Perspectives", 'Session Chair'),
                            $this->keynote(2, '13:55', '14:15', 'Prof. Ayman F. Abou Hadid', 'Professor of Agriculture, Ain Shams University, Egypt & Former Minister of Agriculture, Egypt', 'The Use of Intensive Agriculture and Water Resources is Closely Correlated with Antimicrobial Resistance, Pollution from Chemicals, and the Rise of Health Threats within the One Health Framework'),
                            $this->keynote(3, '14:15', '14:35', 'Prof. Magda Hagras', 'Professor of Clinical Pharmacology, Faculty of Medicine, Suez Canal University, Egypt; Former Acting President of Suez Canal University & Former Vice President of Suez Canal University for Postgraduate Studies and Research', 'Drugs, One Health and Protecting the environment; Towards a Green and Sustainable Pharmacy'),
                            $this->keynote(4, '14:35', '14:55', 'Prof. Islam M. Saadeldin', "Editorial Board Member, Journal of Veterinary Practice and Health, {$publisher} & Research Professor, Department of Comparative Medicine, King Faisal Specialist Hospital and Research Centre, Kingdom of Saudi Arabia", 'The Therapeutic and Diagnostic Uses of Exosomes in Veterinary Medicine', 'Session Co-Chair'),
                            $this->segment('14:55', '15:00', 'Break'),
                            $this->keynote(5, '15:00', '15:20', 'Prof. Muhammed Sanusi Yahaya', 'Senior Lecturer, Department of Theriogenology and Animal Production, Faculty of Veterinary Medicine, Usmanu Danfodiyo University, Sokoto, Nigeria & currently on a one-year sabbatical appointment with the Department of Theriogenology and Production, Faculty of Veterinary Medicine, Ahmadu Bello University, Zaria, Nigeria', 'One Health Approaches to Strengthen Food Security and Sustainable Livestock Systems'),
                            $this->keynote(6, '15:20', '15:40', 'Prof. Mrigendra Rajput', "Associate Editor in Journal of Veterinary Practice and Health, {$publisher}; Associate Professor, College of Veterinary Medicine, Lincoln Memorial University, Harrogate, TN, USA", 'Role of Host RNA Binding Proteins and Outcome of Viral Infection'),
                            $this->segment('15:40', '16:00', 'Discussions & Closing'),
                        ],
                    ],
                    [
                        'kind' => SessionKind::Ceremony,
                        'title' => 'Closing Ceremony, Awards & Conference Recommendations',
                        'starts_at' => '16:00',
                        'ends_at' => '16:30',
                        'chair' => 'Dr. Ahmed Sharf',
                    ],
                ],
            ],
        ];
    }
}
