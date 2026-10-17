<?php

namespace Database\Seeders;

use App\Enums\AgendaItemType;
use App\Enums\AssetsStatus;
use App\Enums\SessionKind;
use App\Models\AgendaItem;
use App\Models\Conference;
use App\Models\ConferenceSession;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * The conference programme as of "8th draft" (14-10-2026).
 * All times are Prague local time, exactly as printed in the PDF.
 */
class Spp2026ConferenceSeeder extends Seeder
{
    public const NAME = 'SPP Online Conference 2026';

    private const OPENS_PLENARY = 'Opens the plenary session';

    public function run(): void
    {
        if (Conference::where('name', self::NAME)->exists()) {
            $this->command?->warn('"'.self::NAME.'" already exists, skipping.');

            return;
        }

        DB::transaction(function (): void {
            $conference = Conference::create([
                'name' => self::NAME,
                'timezone' => 'Europe/Prague',
                'team_timezone' => 'Africa/Cairo',
            ]);

            foreach ($this->programme() as $dayData) {
                $day = $conference->days()->create([
                    'date' => $dayData['date'],
                    'title' => $dayData['title'],
                ]);

                foreach ($dayData['sessions'] as $sessionData) {
                    $items = $sessionData['items'] ?? [];
                    $notes = $sessionData['notes'] ?? [];
                    unset($sessionData['items'], $sessionData['notes']);

                    $session = $day->sessions()->create($sessionData);
                    $this->addNotes($session, $notes);

                    foreach ($items as $position => $itemData) {
                        $itemNotes = $itemData['notes'] ?? [];
                        unset($itemData['notes']);

                        $item = $session->items()->create([...$itemData, 'position' => $position + 1]);
                        $this->addNotes($item, $itemNotes);
                    }
                }
            }

            $this->command?->info('Seeded "'.self::NAME.'". Team link: '.$conference->shareUrl());
        });
    }

    /**
     * @param  list<string>  $notes
     */
    private function addNotes(ConferenceSession|AgendaItem $notable, array $notes): void
    {
        foreach ($notes as $body) {
            $notable->notes()->create(['author_name' => 'PDF draft 8', 'body' => $body]);
        }
    }

    /**
     * @param  list<string>|string  $emails
     * @return array<string, mixed>
     */
    private function keynote(int $number, string $start, string $end, string $name, ?string $title, array|string $emails, ?string $role = null, ?string $affiliation = null): array
    {
        return [
            'type' => AgendaItemType::Keynote,
            'label' => "Keynote {$number}",
            'starts_at' => $start,
            'ends_at' => $end,
            'speaker_name' => $name,
            'speaker_role' => $role,
            'affiliation' => $affiliation,
            'emails' => (array) $emails,
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
    private function planAB(string $start, string $end): array
    {
        return $this->segment($start, $end, "Plan-A: Discussions & Closing (15 min)\nPlan-B: Video-graph of Posters", 'Plan-A / Plan-B');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function programme(): array
    {
        return [
            [
                'date' => '2026-10-24',
                'title' => 'Sustainable Materials',
                'sessions' => [
                    [
                        'kind' => SessionKind::Ceremony,
                        'title' => 'Opening Ceremony',
                        'starts_at' => '09:00',
                        'ends_at' => '09:30',
                        'items' => [
                            [
                                'type' => AgendaItemType::Address,
                                'label' => 'Welcoming Talk',
                                'starts_at' => '09:00',
                                'ends_at' => '09:15',
                                'speaker_name' => 'Dr. Ahmed Sharf',
                                'speaker_role' => 'President of the Conference · Founder & CEO',
                                'affiliation' => 'Science Park Publisher, Prague-Czechia',
                                'emails' => ['Sharf@scipark.cz'],
                                'title' => 'Opening Ceremony & Welcoming Talk by the President of the Conference',
                            ],
                            [
                                'type' => AgendaItemType::Address,
                                'label' => 'Opening Address',
                                'starts_at' => '09:15',
                                'ends_at' => '09:30',
                                'speaker_name' => 'Prof. Petrica Vizureanu',
                                'speaker_role' => 'Honored Chairman',
                                'emails' => ['peviz2002@yahoo.com'],
                                'title' => 'Conference Opening Address by Honored Chairman',
                            ],
                        ],
                    ],
                    [
                        'number' => 1,
                        'journal' => 'Integrated Nano (IN)',
                        'title' => 'Nanomaterials for Energy Harvesting & Sustainable Future',
                        'starts_at' => '09:30',
                        'ends_at' => '11:00',
                        'spp_moderator' => 'Sara Ayman',
                        'items' => [
                            $this->keynote(1, '09:30', '09:45', 'Prof. Nader Shahata', 'Piezoelectric Nanocomposites: Horizon of Multidisciplinary Development', 'nader83@vt.edu', 'Editor-in-Chief · '.self::OPENS_PLENARY),
                            $this->keynote(2, '09:45', '10:00', 'Prof. Veera Rajendran', null, 'veerajendran@gmail.com'),
                            $this->keynote(3, '10:00', '10:15', 'Ankur Jain', 'Engineering Next-Generation Hydrogen Storage Materials for a Sustainable Future', 'ankur.j.ankur@gmail.com', 'Editorial Board Member'),
                            $this->keynote(4, '10:15', '10:30', 'Prof. Kamal Alameh', 'Nanotechnology-Enabled Building-Integrated Photovoltaics (BIPV): Transforming Buildings into Zero-Net-Energy Powerhouses', 'k.alameh@sciparkpub.com', 'Editor-in-Chief'),
                            $this->keynote(5, '10:30', '10:45', 'Dr. Vaibhav Kulshreshtha', 'Polyelectrolyte Membranes for Strategic Devising of Electrochemical Energy Systems', 'vaibhavphy@gmail.com'),
                            $this->planAB('10:45', '11:00'),
                        ],
                    ],
                    [
                        'number' => 2,
                        'journal' => 'Journal of Climate Change Action (JCCA)',
                        'title' => 'Carbon Capture and Carbon Footprint',
                        'starts_at' => '11:00',
                        'ends_at' => '12:15',
                        'spp_moderator' => 'Dr. Fatma Ahmed',
                        'items' => [
                            $this->keynote(1, '11:00', '11:15', 'Prof. Nour F. Attia', 'Sustainable and Scalable Materials for Carbon Capture and Utilization', 'drnour2005@yahoo.com', 'Editor-in-Chief · '.self::OPENS_PLENARY),
                            $this->keynote(2, '11:15', '11:30', 'Prof. Alaa G.M. Osman', 'IoT-Enhanced Aquaponics Sustainability', 'agosman@azhar.edu.eg', 'Editor-in-Chief of the New Horizons Journal of Basic and Applied Sciences'),
                            $this->keynote(3, '11:30', '11:45', 'Eng. Ayman Elrafie', 'Carbon Footprint and Neutralization Approaches', 'airpeace@gmail.com'),
                            $this->keynote(4, '11:45', '12:00', 'Dr. Ahmed Mourtada Elseman', 'From Hazardous Waste to Solar Energy: Sustainable Recovery of Lead from Spent Batteries for Inkjet-Printed Perovskite Solar Cells', ['AhmedMourtada5555@yahoo.com', 'amourtada@cmrdi.sci.eg'], null, 'Electronic & Magnetic Materials Department, Central Metallurgical Research and Development Institute (CMRDI), Helwan, P.O. Box 87, Cairo 11421, Egypt'),
                            $this->planAB('12:00', '12:15'),
                        ],
                    ],
                    [
                        'number' => 3,
                        'journal' => 'Journal of Agrochemicals and Food Safety (JAFS) & Agricultural Reports International (ARI)',
                        'title' => 'Green Agrochemicals and Safe Food Systems',
                        'starts_at' => '12:15',
                        'ends_at' => '13:30',
                        'spp_moderator' => 'Noha Atef',
                        'items' => [
                            $this->keynote(1, '12:15', '12:30', 'Prof. Atef Nassar', 'Agrochemicals and Food Safety and Technologies', 'atef.nassar@dmu.edu.eg', 'Editor-in-Chief · '.self::OPENS_PLENARY),
                            $this->keynote(2, '12:30', '12:45', 'Prof. Sherif M. Sherif', 'Bridging Discovery and Deployment Environmental Stability of Minicell-Based Biofungicides for Sustainable Agriculture', 'Ssherif@vt.edu'),
                            $this->keynote(3, '12:45', '13:00', 'Prof. Konstantinos A. Aliferis', 'Redefining Agricultural Biologicals: New Approaches for the Discovery and Development of Next-Generation Biostimulants and Biocontrols', 'konstantinos.aliferis@aua.gr'),
                            $this->keynote(4, '13:00', '13:15', 'Prof. Hazem Shaheen', 'Could abscisic acid, a plant hormone, have uses in medicine for animals and human', 'dr_hazemshaheen3010@yahoo.com', 'Editorial Board Member'),
                            $this->planAB('13:15', '13:30'),
                        ],
                    ],
                    [
                        'number' => 4,
                        'journal' => 'Launched Journals (Polymer, Computer, Linguistics, Junior)',
                        'title' => 'Oral Presentations (4 talks × 5–7 min)',
                        'starts_at' => '13:30',
                        'ends_at' => '14:00',
                        'items' => [
                            [
                                'type' => AgendaItemType::Oral,
                                'label' => 'Polymer',
                                'speaker_name' => 'Prof. Dr. Ibrahim El-Tantawy El-Sayed',
                                'affiliation' => 'Organic Chemistry, Medicinal and Green Chemistry',
                                'emails' => ['ibrahimtantawy@science.menofia.edu.eg'],
                            ],
                            [
                                'type' => AgendaItemType::Oral,
                                'label' => 'JUNIOR',
                                'speaker_name' => 'Dr. Marwa Karim',
                                'speaker_role' => 'Assistant Professor',
                                'affiliation' => 'Physics Department, Faculty of Science, Alexandria University, Egypt',
                                'emails' => ['marwakarim@alexu.edu.eg'],
                            ],
                            [
                                'type' => AgendaItemType::Oral,
                                'label' => 'Linguistics',
                                'speaker_name' => 'Dr. Sama & Dr. Basma',
                            ],
                            [
                                'type' => AgendaItemType::Oral,
                                'label' => 'Computer',
                                'speaker_name' => 'Dr. Hesham',
                                'notes' => ['Speaker not confirmed in the PDF: "??? Dr. Hesham (Editor-in-Chief: Frag (WhatsApp) & Hazem Bakery)".'],
                            ],
                        ],
                    ],
                    [
                        'number' => 5,
                        'journal' => 'Advanced Carbon Journal (ACJ)',
                        'title' => 'Sustainable Carbon Nanomaterials and Technologies',
                        'starts_at' => '14:00',
                        'ends_at' => '15:15',
                        'spp_moderator' => 'Rania Adel',
                        'items' => [
                            $this->keynote(1, '14:00', '14:15', 'Prof. Tarek Abdel-Fattah', 'Advanced Carbon Materials for Sustainable Energy: Innovations in Hydrogen Storage and Nanotechnology', 'fattah@cnu.edu', 'Editor-in-Chief · '.self::OPENS_PLENARY),
                            $this->keynote(2, '14:15', '14:30', 'Prof. Sherif Kandil', 'Computer Enhanced Spectroscopy: Novel Analysis of Renal Stone Calculi', 'S.kandil@usa.net'),
                            $this->keynote(3, '14:30', '14:45', 'Prof. Hesham Hamed', 'New Trends in Photocatalytic Nanomaterials for Energy and Environmental Applications', ['heshamaterials@hotmail.com', 'inv.heshamaterials@ugr.es'], 'Associate Editor'),
                            $this->keynote(4, '14:45', '15:00', 'Prof. Irene S. Fahmy', 'Manufacturing of Non-Plastic Single-Use Tableware using Sugarcane Bagasse', 'isamy@nu.edu.eg', 'Associate Editor of the Journal of Undergraduate Novelty, Innovation, Opportunities & Research (JUNIOR)'),
                            $this->planAB('15:00', '15:15'),
                        ],
                    ],
                    [
                        'kind' => SessionKind::Ceremony,
                        'title' => 'Discussions & Closing of Day 1',
                        'starts_at' => '15:15',
                        'ends_at' => '15:30',
                    ],
                ],
            ],
            [
                'date' => '2026-10-25',
                'title' => 'Biotechnology in Health and Therapeutics',
                'sessions' => [
                    [
                        'number' => 6,
                        'journal' => 'Trends in Pharmacology and Drug Delivery (TPDD)',
                        'title' => 'Biotechnology Innovations in Therapeutics',
                        'starts_at' => '09:00',
                        'ends_at' => '11:30',
                        'spp_moderator' => 'Yomna El Morshdy',
                        'journal_moderator' => 'Prof. Magda EL-Massik (Keynotes 1–3) · Prof. Maged Wasfy (Keynotes 4–5)',
                        'notes' => [
                            'Prof. Magda EL-Massik, Associate Editor, Department of Pharmaceutics, Faculty of Pharmacy, Alexandria University. Emails: magdaelmassik@gmail.com, magda.elmassik@alexu.edu.eg',
                            'Prof. Maged Wasfy, Associate Editor, Department of Pharmacology, Faculty of Pharmacy, and Deputy Vice-President, Damanhur University, Egypt. Email: maged.helmy@pharm.dmu.edu.eg',
                        ],
                        'items' => [
                            $this->keynote(1, '09:00', '09:15', 'Prof. Labiba El-Khordagui', 'The vision behind Trends in Pharmacology and Drug Delivery', 'labiba.elkhordagui@alexu.edu.eg', 'Editor-in-Chief · '.self::OPENS_PLENARY, 'Department of Pharmaceutics, Faculty of Pharmacy, Alexandria University'),
                            $this->keynote(2, '09:15', '09:30', 'Prof. Hamdy AbdElkader', 'Patient-Friendly and Sustainable Drug Delivery Innovations for Age-Related Ocular Diseases', 'habdelkader@kku.edu.sa', 'Professor in Pharmaceutics', 'College of Pharmacy, King Khalid University, Saudi Arabia'),
                            $this->keynote(3, '09:30', '09:45', 'Dr. Sarah S. Nasr', 'Tissue-specific Gene Delivery Approaches', 'sara.nasr@alexu.edu.eg', null, 'Department of Pharmaceutics, Faculty of Pharmacy, Alexandria University'),
                            $this->segment('09:45', '10:00', 'Discussions & Video-graph of Posters (15 min)'),
                            $this->keynote(4, '10:00', '10:15', 'Prof. Ahmed Fawzy El-Yazby', 'When Metabolism Meets Pollution: Decoding Endocannabinoid Dysfunction to Develop Sustainable Therapies for Cognitive Impairment', 'ahmed.fawzy.aly@alexu.edu.eg', null, 'Department of Pharmacology and Therapeutics, Faculty of Pharmacy, Alamein International University and Alexandria University, Egypt'),
                            $this->keynote(5, '10:15', '10:30', 'Dr. Swati Dabral', 'Therapeutic potential of C-type natriuretic peptide in Pulmonary Fibrosis', 'swati.dabral@uni-wuerzburg.de', null, 'Institute of Physiology, University of Würzburg, Germany'),
                            $this->segment('10:30', '10:45', 'Discussions & Video-graph of Posters'),
                            $this->segment('10:45', '11:30', '4 Oral Presentations (10 min each) & Discussion'),
                        ],
                    ],
                    [
                        'number' => 7,
                        'journal' => 'Molecular Medicine and Biotechnology Advances (MMBA)',
                        'title' => 'One Health Approaches in Cancer Research, Molecular Medicine and Precision Therapeutics',
                        'starts_at' => '11:30',
                        'ends_at' => '13:30',
                        'spp_moderator' => 'Sara Ayman',
                        'items' => [
                            $this->keynote(1, '11:30', '11:55', 'Prof. Ahmed Samir Sultan', null, ['dr_asultan@alexu.edu.eg', 'as4048@georgetown.edu'], 'Editor-in-Chief · '.self::OPENS_PLENARY),
                            $this->keynote(2, '11:55', '12:20', 'Prof. Nadia M. Hamdy', 'Oncogenic LINCOO51 Genetic Variants Implicated in Breast and Colon Cancer Risk or Prognosis; a Step-Towards ncRNA Precision', 'Nadia_hamdy@pharma.asu.edu.eg'),
                            $this->keynote(3, '12:20', '12:45', 'Dr. Reem Bakir', 'Precision Medicine from Bench to Beside', 'igsr.reem_bakir@alexu.edu.eg'),
                            $this->segment('12:45', '13:15', 'Oral Presentations & Video-graph of Posters (3 × 10 min)'),
                            $this->segment('13:15', '13:30', 'Discussions & Closing (15 min)'),
                        ],
                    ],
                    [
                        'number' => 8,
                        'journal' => 'Journal of Veterinary Practice and Health (JVPH)',
                        'title' => 'One Health and Sustainable Veterinary Practices',
                        'starts_at' => '13:30',
                        'ends_at' => '16:30',
                        'spp_moderator' => 'Sara Hifny',
                        'items' => [
                            $this->keynote(1, '13:30', '13:45', 'Prof. Emad Hashish', 'Innovative Vaccine Strategies for Combating Zoonotic Diseases: A One Health and Environmental Perspectives', 'emadhashish@hotmail.com', 'Editor-in-Chief · '.self::OPENS_PLENARY),
                            $this->keynote(2, '13:45', '14:00', 'Prof. Ayman F. Abou Hdid', 'The Use of Intensive Agriculture and Water Resources is Closely Correlated with Antimicrobial Resistance, Pollution from Chemicals, and the Rise of Health Threats within the One Health Framework', 'aabouhadid@yahoo.com'),
                            $this->keynote(3, '14:00', '14:15', 'Prof. Mrigendra Rajput', 'Role of Host RNA Binding Proteins and Outcome of Viral Infection', 'mrigendra.rajput@lmunet.edu'),
                            $this->keynote(4, '14:15', '14:30', 'Prof. Vikas Pathak', 'One Health Perspectives on Sustainable Animal-Derived Food Systems: Ensuring Food Safety, Quality, and Public Health', 'pathakvet@gmail.com'),
                            $this->keynote(5, '14:30', '14:45', 'Prof. Magda Hagras', 'Drugs, One Health and Protecting the environment; Towards a Green and Sustainable Pharmacy', 'Magda_hagras@med.suez.edu.eg'),
                            $this->keynote(6, '14:45', '15:00', 'Prof. Islam M. Saadeldin', 'The Therapeutic and Diagnostic Uses of Exosomes in Veterinary Medicine', 'imohamed@kfshrc.edu.sa'),
                            $this->keynote(7, '15:00', '15:15', 'Prof. Muhammed Sanusi Yahaya', 'One Health Approaches to Strengthen Food Security and Sustainable Livestock Systems', 'sanusi.yahaya@udusok.edu.ng'),
                            $this->segment('15:15', '16:15', 'Oral Presentations (4 × 15 min) & Video-graph of Posters'),
                            $this->segment('16:15', '16:30', 'Discussions & Closing (15 min)'),
                        ],
                    ],
                    [
                        'kind' => SessionKind::Ceremony,
                        'title' => 'Closing Ceremony, Best Presentation Awards, Abstract Awards & Recommendations',
                        'starts_at' => '16:30',
                        'ends_at' => '17:15',
                    ],
                ],
            ],
        ];
    }
}
