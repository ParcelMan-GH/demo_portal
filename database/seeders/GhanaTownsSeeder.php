<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\Location;
use Illuminate\Database\Seeder;

class GhanaTownsSeeder extends Seeder
{
    public function run(): void
    {
        // Cache all districts by code for fast lookup
        $districts = District::with('region')->get()->keyBy('code');

        foreach ($this->getTownsByDistrict() as $districtCode => $towns) {
            $district = $districts->get($districtCode);
            if (!$district) {
                continue;
            }

            foreach ($towns as $town) {
                $name = is_array($town) ? $town['name'] : $town;
                $type = is_array($town) ? ($town['type'] ?? 'town') : 'town';

                Location::updateOrCreate(
                    ['name' => $name, 'district_id' => $district->id],
                    [
                        'region_id' => $district->region_id,
                        'type'      => $type,
                        'is_active' => true,
                    ]
                );
            }
        }
    }

    /**
     * Towns indexed by district code.
     * Types: 'city', 'town', 'suburb', 'village'
     */
    private function getTownsByDistrict(): array
    {
        return [
            // =========================================================
            // GREATER ACCRA
            // =========================================================
            'GAR-AMA' => [
                ['name' => 'Accra', 'type' => 'city'],
                ['name' => 'Adabraka', 'type' => 'suburb'],
                ['name' => 'Asylum Down', 'type' => 'suburb'],
                ['name' => 'Cantonments', 'type' => 'suburb'],
                ['name' => 'Chorkor', 'type' => 'suburb'],
                ['name' => 'Jamestown', 'type' => 'suburb'],
                ['name' => 'Kokomlemle', 'type' => 'suburb'],
                ['name' => 'Kotobabi', 'type' => 'suburb'],
                ['name' => 'Labadi', 'type' => 'suburb'],
                ['name' => 'Labone', 'type' => 'suburb'],
                ['name' => 'Laterbiokorshie', 'type' => 'suburb'],
                ['name' => 'North Kaneshie', 'type' => 'suburb'],
                ['name' => 'Odorkor', 'type' => 'suburb'],
                ['name' => 'Okaishie', 'type' => 'suburb'],
                ['name' => 'Old Fadama', 'type' => 'suburb'],
                ['name' => 'Osu', 'type' => 'suburb'],
                ['name' => 'Ridge', 'type' => 'suburb'],
                ['name' => 'Ring Road', 'type' => 'suburb'],
                ['name' => 'Roman Ridge', 'type' => 'suburb'],
                ['name' => 'Tesano', 'type' => 'suburb'],
                ['name' => 'Tudu', 'type' => 'suburb'],
                ['name' => 'Ussher Town', 'type' => 'suburb'],
            ],
            'GAR-TMA' => [
                ['name' => 'Tema', 'type' => 'city'],
                ['name' => 'Community 1', 'type' => 'suburb'],
                ['name' => 'Community 2', 'type' => 'suburb'],
                ['name' => 'Community 3', 'type' => 'suburb'],
                ['name' => 'Community 4', 'type' => 'suburb'],
                ['name' => 'Community 5', 'type' => 'suburb'],
                ['name' => 'Community 6', 'type' => 'suburb'],
                ['name' => 'Community 7', 'type' => 'suburb'],
                ['name' => 'Community 8', 'type' => 'suburb'],
                ['name' => 'Community 9', 'type' => 'suburb'],
                ['name' => 'Community 10', 'type' => 'suburb'],
                ['name' => 'Community 11', 'type' => 'suburb'],
                ['name' => 'Community 12', 'type' => 'suburb'],
                ['name' => 'Tema New Town', 'type' => 'suburb'],
                ['name' => 'Tema Manhean', 'type' => 'suburb'],
                ['name' => 'Fishing Harbour', 'type' => 'suburb'],
            ],
            'GAR-GEM' => [
                ['name' => 'Dome', 'type' => 'town'],
                ['name' => 'Haatso', 'type' => 'suburb'],
                ['name' => 'Agbogba', 'type' => 'suburb'],
                ['name' => 'Ashale Botwe', 'type' => 'suburb'],
                ['name' => 'Abokobi', 'type' => 'town'],
                ['name' => 'Frafraha', 'type' => 'suburb'],
                ['name' => 'Pantang', 'type' => 'suburb'],
                ['name' => 'Nmai Dzorn', 'type' => 'suburb'],
                ['name' => 'Adenta', 'type' => 'suburb'],
                ['name' => 'Peduase', 'type' => 'town'],
            ],
            'GAR-GWM' => [
                ['name' => 'Amasaman', 'type' => 'town'],
                ['name' => 'Pokuase', 'type' => 'town'],
                ['name' => 'Afiaman', 'type' => 'suburb'],
                ['name' => 'Nsawam Road', 'type' => 'suburb'],
                ['name' => 'Ofankor', 'type' => 'suburb'],
                ['name' => 'Akweteyman', 'type' => 'suburb'],
                ['name' => 'Sowutuom', 'type' => 'suburb'],
                ['name' => 'Boi', 'type' => 'suburb'],
                ['name' => 'Taifa', 'type' => 'suburb'],
            ],
            'GAR-GNM' => [
                ['name' => 'Kwabenya', 'type' => 'town'],
                ['name' => 'Tantra Hills', 'type' => 'suburb'],
                ['name' => 'Atomic', 'type' => 'suburb'],
                ['name' => 'Accra New Town', 'type' => 'suburb'],
                ['name' => 'Achimota', 'type' => 'suburb'],
                ['name' => 'Abelemkpe', 'type' => 'suburb'],
                ['name' => 'Airport Hills', 'type' => 'suburb'],
            ],
            'GAR-GSM' => [
                ['name' => 'Weija', 'type' => 'town'],
                ['name' => 'Gbawe', 'type' => 'suburb'],
                ['name' => 'Kokrobite', 'type' => 'town'],
                ['name' => 'Kasoa', 'type' => 'city'],
                ['name' => 'Obom', 'type' => 'town'],
                ['name' => 'Bortianor', 'type' => 'suburb'],
                ['name' => 'Glefe', 'type' => 'suburb'],
                ['name' => 'Mamprobi', 'type' => 'suburb'],
                ['name' => 'Bubuashie', 'type' => 'suburb'],
            ],
            'GAR-GCM' => [
                ['name' => 'Kaneshie', 'type' => 'suburb'],
                ['name' => 'Abeka', 'type' => 'suburb'],
                ['name' => 'Darkuman', 'type' => 'suburb'],
                ['name' => 'Kwashieman', 'type' => 'suburb'],
                ['name' => 'Dansoman', 'type' => 'suburb'],
            ],
            'GAR-LMA' => [
                ['name' => 'Teshie', 'type' => 'suburb'],
                ['name' => 'Nungua', 'type' => 'suburb'],
                ['name' => 'Baatsona', 'type' => 'suburb'],
                ['name' => 'Sakumono', 'type' => 'suburb'],
                ['name' => 'Spintex', 'type' => 'suburb'],
            ],
            'GAR-KMA' => [
                ['name' => 'Nungua', 'type' => 'suburb'],
                ['name' => 'Tema Station', 'type' => 'suburb'],
                ['name' => 'Michel Camp', 'type' => 'suburb'],
            ],
            'GAR-LDK' => [
                ['name' => 'La', 'type' => 'suburb'],
                ['name' => 'Airport Residential', 'type' => 'suburb'],
                ['name' => 'East Legon', 'type' => 'suburb'],
                ['name' => 'Dzorwulu', 'type' => 'suburb'],
            ],
            'GAR-LNM' => [
                ['name' => 'Madina', 'type' => 'town'],
                ['name' => 'Nkwatia', 'type' => 'suburb'],
                ['name' => 'Ritz Junction', 'type' => 'suburb'],
                ['name' => 'Adentan', 'type' => 'suburb'],
                ['name' => 'Oduom', 'type' => 'suburb'],
            ],
            'GAR-ADM' => [
                ['name' => 'Adenta', 'type' => 'town'],
                ['name' => 'Adenta Housing Down', 'type' => 'suburb'],
                ['name' => 'Madina', 'type' => 'town'],
                ['name' => 'Frafraha', 'type' => 'suburb'],
            ],
            'GAR-ASM' => [
                ['name' => 'Ashaiman', 'type' => 'city'],
                ['name' => 'Ashaiman Old Town', 'type' => 'suburb'],
                ['name' => 'Tulaku', 'type' => 'suburb'],
                ['name' => 'Buduburam', 'type' => 'suburb'],
                ['name' => 'Chorkor', 'type' => 'suburb'],
                ['name' => 'Lashibi', 'type' => 'suburb'],
            ],
            'GAR-KKM' => [
                ['name' => 'Kpone', 'type' => 'town'],
                ['name' => 'Prampram', 'type' => 'town'],
                ['name' => 'Afienya', 'type' => 'town'],
                ['name' => 'Katamanso', 'type' => 'suburb'],
            ],
            'GAR-ADE' => [
                ['name' => 'Ada Foah', 'type' => 'town'],
                ['name' => 'Kasseh', 'type' => 'town'],
                ['name' => 'Batsonaa', 'type' => 'town'],
            ],
            'GAR-ADW' => [
                ['name' => 'Ada', 'type' => 'town'],
                ['name' => 'Sege', 'type' => 'town'],
                ['name' => 'Big Ada', 'type' => 'town'],
            ],
            'GAR-NPD' => [
                ['name' => 'Ningo', 'type' => 'town'],
                ['name' => 'Prampram', 'type' => 'town'],
                ['name' => 'Dawhenya', 'type' => 'town'],
            ],
            'GAR-SOD' => [
                ['name' => 'Dodowa', 'type' => 'town'],
                ['name' => 'Osudoku', 'type' => 'town'],
                ['name' => 'Shai Hills', 'type' => 'town'],
                ['name' => 'Akorley', 'type' => 'town'],
            ],
            'GAR-ACM' => [
                ['name' => 'Ablekuma', 'type' => 'suburb'],
                ['name' => 'Mataheko', 'type' => 'suburb'],
                ['name' => 'Dansoman', 'type' => 'suburb'],
                ['name' => 'Chorkor', 'type' => 'suburb'],
            ],
            'GAR-ANM' => [
                ['name' => 'Abeka Lapaz', 'type' => 'suburb'],
                ['name' => 'Lapaz', 'type' => 'suburb'],
                ['name' => 'Achimota', 'type' => 'suburb'],
                ['name' => 'Pent Hill', 'type' => 'suburb'],
            ],
            'GAR-AWM' => [
                ['name' => 'Odorkor', 'type' => 'suburb'],
                ['name' => 'Sakaman', 'type' => 'suburb'],
                ['name' => 'Fadama', 'type' => 'suburb'],
                ['name' => 'Mallam', 'type' => 'suburb'],
            ],
            'GAR-AYC' => [
                ['name' => 'Asylum Down', 'type' => 'suburb'],
                ['name' => 'Kokomlemle', 'type' => 'suburb'],
                ['name' => 'Accra Central', 'type' => 'suburb'],
            ],
            'GAR-AYE' => [
                ['name' => 'Okponglo', 'type' => 'suburb'],
                ['name' => 'Legon', 'type' => 'suburb'],
                ['name' => 'Haatso', 'type' => 'suburb'],
            ],
            'GAR-AYN' => [
                ['name' => 'Pokuase', 'type' => 'suburb'],
                ['name' => 'Kwabenya', 'type' => 'suburb'],
            ],
            'GAR-AYW' => [
                ['name' => 'Airport Residential Area', 'type' => 'suburb'],
                ['name' => 'Dzorwulu', 'type' => 'suburb'],
                ['name' => 'Abelemkpe', 'type' => 'suburb'],
                ['name' => 'Legon', 'type' => 'suburb'],
            ],
            'GAR-KKL' => [
                ['name' => 'Korle Bu', 'type' => 'suburb'],
                ['name' => 'Kokomlemle', 'type' => 'suburb'],
                ['name' => 'Mamprobi', 'type' => 'suburb'],
                ['name' => 'Korle Gonno', 'type' => 'suburb'],
            ],
            'GAR-OKN' => [
                ['name' => 'Newtown', 'type' => 'suburb'],
                ['name' => 'Nima', 'type' => 'suburb'],
                ['name' => 'Mamobi', 'type' => 'suburb'],
                ['name' => 'Maamobi', 'type' => 'suburb'],
            ],
            'GAR-TWM' => [
                ['name' => 'Tema West', 'type' => 'suburb'],
                ['name' => 'Adjei Kojo', 'type' => 'suburb'],
                ['name' => 'Ashaiman', 'type' => 'suburb'],
                ['name' => 'Pipiyaya', 'type' => 'suburb'],
            ],
            'GAR-WGM' => [
                ['name' => 'Weija', 'type' => 'town'],
                ['name' => 'Gbawe', 'type' => 'suburb'],
                ['name' => 'Tetegu', 'type' => 'suburb'],
                ['name' => 'Kokrobite', 'type' => 'town'],
            ],

            // =========================================================
            // ASHANTI REGION
            // =========================================================
            'ASH-KMA' => [
                ['name' => 'Kumasi', 'type' => 'city'],
                ['name' => 'Adum', 'type' => 'suburb'],
                ['name' => 'Asokwa', 'type' => 'suburb'],
                ['name' => 'Bantama', 'type' => 'suburb'],
                ['name' => 'Suame', 'type' => 'suburb'],
                ['name' => 'Manhyia', 'type' => 'suburb'],
                ['name' => 'Asawase', 'type' => 'suburb'],
                ['name' => 'Oforikrom', 'type' => 'suburb'],
                ['name' => 'Kwadaso', 'type' => 'suburb'],
                ['name' => 'Old Tafo', 'type' => 'suburb'],
                ['name' => 'Nhyiaeso', 'type' => 'suburb'],
                ['name' => 'Patasi', 'type' => 'suburb'],
                ['name' => 'Ayigya', 'type' => 'suburb'],
                ['name' => 'Buokrom', 'type' => 'suburb'],
                ['name' => 'Fante New Town', 'type' => 'suburb'],
                ['name' => 'Atonsu', 'type' => 'suburb'],
                ['name' => 'Ahodwo', 'type' => 'suburb'],
                ['name' => 'Krofrom', 'type' => 'suburb'],
            ],
            'ASH-OBM' => [
                ['name' => 'Obuasi', 'type' => 'city'],
                ['name' => 'Kenyase', 'type' => 'town'],
                ['name' => 'Sanso', 'type' => 'town'],
                ['name' => 'Adansi Asokwa', 'type' => 'town'],
            ],
            'ASH-KSD' => [
                ['name' => 'Konongo', 'type' => 'town'],
                ['name' => 'Odumasi', 'type' => 'town'],
                ['name' => 'Juaben', 'type' => 'town'],
                ['name' => 'Agogo', 'type' => 'town'],
            ],
            'ASH-MNO' => [
                ['name' => 'Mampong', 'type' => 'town'],
                ['name' => 'Nkoranza', 'type' => 'town'],
                ['name' => 'Jamasi', 'type' => 'town'],
            ],
            'ASH-AHF' => [
                ['name' => 'Bekwai', 'type' => 'town'],
                ['name' => 'Fomena', 'type' => 'town'],
                ['name' => 'Obuasi', 'type' => 'town'],
            ],
            'ASH-ACS' => [
                ['name' => 'Kumasi', 'type' => 'town'],
                ['name' => 'Sofoline', 'type' => 'suburb'],
            ],
            'ASH-KAD' => [
                ['name' => 'Denyase', 'type' => 'town'],
                ['name' => 'Nkawie', 'type' => 'town'],
                ['name' => 'Toase', 'type' => 'town'],
            ],
            'ASH-KSW' => [
                ['name' => 'Kwabre', 'type' => 'town'],
                ['name' => 'Afrancho', 'type' => 'town'],
                ['name' => 'Kodie', 'type' => 'town'],
                ['name' => 'Mamponteng', 'type' => 'town'],
            ],
            'ASH-AFN' => [
                ['name' => 'Effiduase', 'type' => 'town'],
                ['name' => 'Juaben', 'type' => 'town'],
                ['name' => 'Bomfa', 'type' => 'town'],
                ['name' => 'Ashanti Mampong', 'type' => 'town'],
            ],
            'ASH-BOS' => [
                ['name' => 'Bosomtwe', 'type' => 'town'],
                ['name' => 'Kuntanase', 'type' => 'town'],
                ['name' => 'Boankra', 'type' => 'town'],
            ],

            // =========================================================
            // WESTERN REGION
            // =========================================================
            'WES-SMA' => [
                ['name' => 'Sekondi', 'type' => 'city'],
                ['name' => 'Takoradi', 'type' => 'city'],
                ['name' => 'Kwesimintim', 'type' => 'suburb'],
                ['name' => 'New Takoradi', 'type' => 'suburb'],
                ['name' => 'Effiakuma', 'type' => 'suburb'],
                ['name' => 'Kansaworado', 'type' => 'suburb'],
                ['name' => 'Kojokrom', 'type' => 'suburb'],
                ['name' => 'Fijai', 'type' => 'suburb'],
                ['name' => 'Airport Ridge', 'type' => 'suburb'],
                ['name' => 'Anaji', 'type' => 'suburb'],
                ['name' => 'Beach Road', 'type' => 'suburb'],
                ['name' => 'Market Circle', 'type' => 'suburb'],
            ],
            'WES-AHM' => [
                ['name' => 'Ahanta West', 'type' => 'town'],
                ['name' => 'Agona Nkwanta', 'type' => 'town'],
                ['name' => 'Dixcove', 'type' => 'town'],
            ],
            'WES-TAR' => [
                ['name' => 'Tarkwa', 'type' => 'city'],
                ['name' => 'Aboso', 'type' => 'town'],
                ['name' => 'Bogoso', 'type' => 'town'],
                ['name' => 'Nsuaem', 'type' => 'town'],
                ['name' => 'Prestea', 'type' => 'town'],
                ['name' => 'Wassa Akropong', 'type' => 'town'],
            ],
            'WES-JOM' => [
                ['name' => 'Jomoro', 'type' => 'town'],
                ['name' => 'Half Assini', 'type' => 'town'],
                ['name' => 'Axim', 'type' => 'town'],
            ],
            'WES-EFF' => [
                ['name' => 'Effutu', 'type' => 'town'],
                ['name' => 'Winneba', 'type' => 'town'],
            ],
            'WES-WAS' => [
                ['name' => 'Sefwi Wiawso', 'type' => 'town'],
                ['name' => 'Asankrangwa', 'type' => 'town'],
                ['name' => 'Enchi', 'type' => 'town'],
            ],

            // =========================================================
            // CENTRAL REGION
            // =========================================================
            'CEN-CAP' => [
                ['name' => 'Cape Coast', 'type' => 'city'],
                ['name' => 'Abura', 'type' => 'suburb'],
                ['name' => 'Asebu', 'type' => 'suburb'],
                ['name' => 'Kwakorom', 'type' => 'suburb'],
                ['name' => 'Duakor', 'type' => 'suburb'],
                ['name' => 'Pedu', 'type' => 'suburb'],
                ['name' => 'Kotokuraba', 'type' => 'suburb'],
            ],
            'CEN-EFC' => [
                ['name' => 'Effutu', 'type' => 'town'],
                ['name' => 'Winneba', 'type' => 'city'],
                ['name' => 'Senya Bereku', 'type' => 'town'],
            ],
            'CEN-MFK' => [
                ['name' => 'Mankessim', 'type' => 'town'],
                ['name' => 'Anomabo', 'type' => 'town'],
                ['name' => 'Saltpond', 'type' => 'town'],
            ],
            'CEN-AWU' => [
                ['name' => 'Kasoa', 'type' => 'city'],
                ['name' => 'Awutu', 'type' => 'town'],
                ['name' => 'Millennium City', 'type' => 'suburb'],
                ['name' => 'Ofaakor', 'type' => 'suburb'],
                ['name' => 'Galilea', 'type' => 'suburb'],
            ],
            'CEN-AWE' => [
                ['name' => 'Awutu Senya', 'type' => 'town'],
                ['name' => 'Bawjiase', 'type' => 'town'],
            ],
            'CEN-AGO' => [
                ['name' => 'Agona Swedru', 'type' => 'town'],
                ['name' => 'Agona Nsaba', 'type' => 'town'],
            ],
            'CEN-ASN' => [
                ['name' => 'Assin Fosu', 'type' => 'town'],
                ['name' => 'Assin North', 'type' => 'town'],
            ],
            'CEN-TKA' => [
                ['name' => 'Twifo Ati Morkwa', 'type' => 'town'],
                ['name' => 'Twifo Praso', 'type' => 'town'],
            ],
            'CEN-GUA' => [
                ['name' => 'Gomoa East', 'type' => 'town'],
                ['name' => 'Apam', 'type' => 'town'],
                ['name' => 'Apam Junction', 'type' => 'suburb'],
            ],

            // =========================================================
            // EASTERN REGION
            // =========================================================
            'EAS-KOF' => [
                ['name' => 'Koforidua', 'type' => 'city'],
                ['name' => 'Old Estate', 'type' => 'suburb'],
                ['name' => 'Adweso', 'type' => 'suburb'],
                ['name' => 'Asokore', 'type' => 'suburb'],
                ['name' => 'New Juaben', 'type' => 'suburb'],
                ['name' => 'Effiduase', 'type' => 'suburb'],
            ],
            'EAS-AKU' => [
                ['name' => 'Akuapem North', 'type' => 'town'],
                ['name' => 'Akropong', 'type' => 'town'],
                ['name' => 'Aburi', 'type' => 'town'],
                ['name' => 'Mamfe', 'type' => 'town'],
                ['name' => 'Larteh', 'type' => 'town'],
            ],
            'EAS-AKS' => [
                ['name' => 'Nsawam', 'type' => 'city'],
                ['name' => 'Nkawkaw', 'type' => 'town'],
                ['name' => 'Suhum', 'type' => 'town'],
                ['name' => 'Asamankese', 'type' => 'town'],
                ['name' => 'Kade', 'type' => 'town'],
                ['name' => 'Anyinam', 'type' => 'town'],
                ['name' => 'Atibie', 'type' => 'town'],
                ['name' => 'Begoro', 'type' => 'town'],
                ['name' => 'Nkawkaw', 'type' => 'town'],
                ['name' => 'Tafo', 'type' => 'town'],
            ],
            'EAS-AFF' => [
                ['name' => 'Akyem Oda', 'type' => 'town'],
                ['name' => 'Kade', 'type' => 'town'],
                ['name' => 'Akim Oda', 'type' => 'town'],
            ],
            'EAS-KWA' => [
                ['name' => 'Kwahu', 'type' => 'town'],
                ['name' => 'Mpraeso', 'type' => 'town'],
                ['name' => 'Nkwatia', 'type' => 'town'],
            ],
            'EAS-BOS' => [
                ['name' => 'Birim South', 'type' => 'town'],
                ['name' => 'Achiase', 'type' => 'town'],
            ],

            // =========================================================
            // VOLTA REGION
            // =========================================================
            'VOL-HOC' => [
                ['name' => 'Ho', 'type' => 'city'],
                ['name' => 'Ho West', 'type' => 'suburb'],
                ['name' => 'Ho Central', 'type' => 'suburb'],
                ['name' => 'Bankoe', 'type' => 'suburb'],
                ['name' => 'Dome', 'type' => 'suburb'],
                ['name' => 'Sokode', 'type' => 'suburb'],
            ],
            'VOL-KET' => [
                ['name' => 'Keta', 'type' => 'town'],
                ['name' => 'Anloga', 'type' => 'town'],
                ['name' => 'Tegbi', 'type' => 'town'],
            ],
            'VOL-AFl' => [
                ['name' => 'Aflao', 'type' => 'city'],
                ['name' => 'Agbozume', 'type' => 'town'],
            ],
            'VOL-HOM' => [
                ['name' => 'Hohoe', 'type' => 'town'],
                ['name' => 'Kpando', 'type' => 'town'],
                ['name' => 'Jasikan', 'type' => 'town'],
                ['name' => 'Kadjebi', 'type' => 'town'],
            ],
            'VOL-EWE' => [
                ['name' => 'Akatsi', 'type' => 'town'],
                ['name' => 'Sogakope', 'type' => 'town'],
                ['name' => 'Adidome', 'type' => 'town'],
            ],

            // =========================================================
            // NORTHERN REGION
            // =========================================================
            'NOR-TAM' => [
                ['name' => 'Tamale', 'type' => 'city'],
                ['name' => 'Tamale Central', 'type' => 'suburb'],
                ['name' => 'Kukuo', 'type' => 'suburb'],
                ['name' => 'Lamashegu', 'type' => 'suburb'],
                ['name' => 'Nyohini', 'type' => 'suburb'],
                ['name' => 'Gbani', 'type' => 'suburb'],
                ['name' => 'Sakasaka', 'type' => 'suburb'],
                ['name' => 'Vittin', 'type' => 'suburb'],
                ['name' => 'Kalpohin', 'type' => 'suburb'],
                ['name' => 'Datoyili', 'type' => 'suburb'],
            ],
            'NOR-YEN' => [
                ['name' => 'Yendi', 'type' => 'town'],
                ['name' => 'Saboba', 'type' => 'town'],
            ],
            'NOR-SNG' => [
                ['name' => 'Savelugu', 'type' => 'town'],
                ['name' => 'Nanton', 'type' => 'town'],
                ['name' => 'Kumbungu', 'type' => 'town'],
            ],
            'NOR-TTO' => [
                ['name' => 'Tolon', 'type' => 'town'],
                ['name' => 'Kumbungu', 'type' => 'town'],
            ],
            'NOR-KAR' => [
                ['name' => 'Karaga', 'type' => 'town'],
                ['name' => 'Gushiegu', 'type' => 'town'],
            ],
            'NOR-NAL' => [
                ['name' => 'Nalerigu', 'type' => 'town'],
                ['name' => 'Gambaga', 'type' => 'town'],
                ['name' => 'Nakpayili', 'type' => 'town'],
            ],

            // =========================================================
            // UPPER EAST REGION
            // =========================================================
            'UPE-BOL' => [
                ['name' => 'Bolgatanga', 'type' => 'city'],
                ['name' => 'Bolgatanga Central', 'type' => 'suburb'],
                ['name' => 'Sumbrungu', 'type' => 'suburb'],
                ['name' => 'Zuarungu', 'type' => 'suburb'],
            ],
            'UPE-NAV' => [
                ['name' => 'Navrongo', 'type' => 'town'],
                ['name' => 'Paga', 'type' => 'town'],
                ['name' => 'Chiana', 'type' => 'town'],
            ],
            'UPE-BAN' => [
                ['name' => 'Bawku', 'type' => 'town'],
                ['name' => 'Garu', 'type' => 'town'],
            ],
            'UPE-GOR' => [
                ['name' => 'Bongo', 'type' => 'town'],
                ['name' => 'Namoo', 'type' => 'town'],
            ],
            'UPE-TAL' => [
                ['name' => 'Talensi', 'type' => 'town'],
                ['name' => 'Tongo', 'type' => 'town'],
            ],
            'UPE-KSB' => [
                ['name' => 'Kassena Nankana West', 'type' => 'town'],
                ['name' => 'Kayoro', 'type' => 'town'],
            ],
            'UPE-PUS' => [
                ['name' => 'Pusiga', 'type' => 'town'],
                ['name' => 'Zebilla', 'type' => 'town'],
            ],

            // =========================================================
            // UPPER WEST REGION
            // =========================================================
            'UPW-WAC' => [
                ['name' => 'Wa', 'type' => 'city'],
                ['name' => 'Wa Central', 'type' => 'suburb'],
                ['name' => 'Kpongu', 'type' => 'suburb'],
                ['name' => 'Busa', 'type' => 'suburb'],
            ],
            'UPW-LAW' => [
                ['name' => 'Lawra', 'type' => 'town'],
                ['name' => 'Nandom', 'type' => 'town'],
            ],
            'UPW-NAN' => [
                ['name' => 'Nandom', 'type' => 'town'],
                ['name' => 'Ko', 'type' => 'town'],
            ],
            'UPW-JIR' => [
                ['name' => 'Jirapa', 'type' => 'town'],
                ['name' => 'Lambussie', 'type' => 'town'],
            ],
            'UPW-WAE' => [
                ['name' => 'Wa East', 'type' => 'town'],
                ['name' => 'Funsi', 'type' => 'town'],
            ],
            'UPW-SIS' => [
                ['name' => 'Sissala East', 'type' => 'town'],
                ['name' => 'Tumu', 'type' => 'town'],
            ],
            'UPW-SIW' => [
                ['name' => 'Sissala West', 'type' => 'town'],
                ['name' => 'Gwolu', 'type' => 'town'],
            ],

            // =========================================================
            // BRONG-AHAFO / BONO REGION
            // =========================================================
            'BON-SUN' => [
                ['name' => 'Sunyani', 'type' => 'city'],
                ['name' => 'Sunyani Central', 'type' => 'suburb'],
                ['name' => 'Fiapre', 'type' => 'suburb'],
                ['name' => 'Odumase', 'type' => 'suburb'],
                ['name' => 'Nsuatre', 'type' => 'suburb'],
            ],
            'BON-BEA' => [
                ['name' => 'Berekum', 'type' => 'town'],
                ['name' => 'Wenchi', 'type' => 'town'],
            ],
            'BON-DRO' => [
                ['name' => 'Dormaa Ahenkro', 'type' => 'town'],
                ['name' => 'Dormaa', 'type' => 'town'],
            ],
            'BON-TEC' => [
                ['name' => 'Techiman', 'type' => 'city'],
                ['name' => 'Techiman North', 'type' => 'town'],
            ],
            'BON-NAK' => [
                ['name' => 'Nkoranza', 'type' => 'town'],
                ['name' => 'Nkoranza South', 'type' => 'town'],
            ],
            'BON-KIN' => [
                ['name' => 'Kintampo', 'type' => 'town'],
                ['name' => 'Kintampo North', 'type' => 'town'],
            ],
            'BON-ASU' => [
                ['name' => 'Atebubu', 'type' => 'town'],
                ['name' => 'Amantin', 'type' => 'town'],
            ],

            // =========================================================
            // OTI REGION
            // =========================================================
            'OTI-JAS' => [
                ['name' => 'Jasikan', 'type' => 'town'],
                ['name' => 'Dambai', 'type' => 'town'],
            ],
            'OTI-KPD' => [
                ['name' => 'Kpandai', 'type' => 'town'],
            ],
            'OTI-NAK' => [
                ['name' => 'Nkwanta North', 'type' => 'town'],
            ],
            'OTI-NKS' => [
                ['name' => 'Nkwanta South', 'type' => 'town'],
                ['name' => 'Nkwanta', 'type' => 'town'],
            ],
            'OTI-BUE' => [
                ['name' => 'Buem', 'type' => 'town'],
                ['name' => 'Baglo', 'type' => 'town'],
            ],

            // =========================================================
            // SAVANNAH REGION
            // =========================================================
            'SAV-SAV' => [
                ['name' => 'Damongo', 'type' => 'town'],
                ['name' => 'West Gonja', 'type' => 'town'],
            ],
            'SAV-EST' => [
                ['name' => 'East Gonja', 'type' => 'town'],
                ['name' => 'Salaga', 'type' => 'town'],
            ],
            'SAV-BOL' => [
                ['name' => 'Bole', 'type' => 'town'],
                ['name' => 'Sawla', 'type' => 'town'],
            ],
            'SAV-CCG' => [
                ['name' => 'Central Gonja', 'type' => 'town'],
                ['name' => 'Buipe', 'type' => 'town'],
            ],
            'SAV-NGJ' => [
                ['name' => 'North Gonja', 'type' => 'town'],
                ['name' => 'Daboya', 'type' => 'town'],
            ],

            // =========================================================
            // NORTH EAST REGION
            // =========================================================
            'NOR-NAL' => [
                ['name' => 'Nalerigu', 'type' => 'town'],
                ['name' => 'Gambaga', 'type' => 'town'],
            ],
            'NOR-MAM' => [
                ['name' => 'Mamprugu Moagduri', 'type' => 'town'],
            ],
            'NOR-WES' => [
                ['name' => 'West Mamprusi', 'type' => 'town'],
                ['name' => 'Walewale', 'type' => 'town'],
            ],

            // =========================================================
            // AHAFO REGION
            // =========================================================
            'AHA-ASU' => [
                ['name' => 'Goaso', 'type' => 'town'],
                ['name' => 'Kukuom', 'type' => 'town'],
                ['name' => 'Hwidiem', 'type' => 'town'],
            ],
            'AHA-TAN' => [
                ['name' => 'Tano North', 'type' => 'town'],
                ['name' => 'Bechem', 'type' => 'town'],
            ],
            'AHA-TAS' => [
                ['name' => 'Tano South', 'type' => 'town'],
                ['name' => 'Duayaw Nkwanta', 'type' => 'town'],
            ],

            // =========================================================
            // BONO EAST REGION
            // =========================================================
            'BOE-ATE' => [
                ['name' => 'Atebubu', 'type' => 'town'],
                ['name' => 'Amantin', 'type' => 'town'],
            ],
            'BOE-PRO' => [
                ['name' => 'Pru East', 'type' => 'town'],
                ['name' => 'Yeji', 'type' => 'town'],
            ],
            'BOE-KIN' => [
                ['name' => 'Kintampo', 'type' => 'town'],
                ['name' => 'Kintampo North', 'type' => 'town'],
            ],

            // =========================================================
            // WESTERN NORTH REGION
            // =========================================================
            'WEN-SEB' => [
                ['name' => 'Sefwi Wiawso', 'type' => 'town'],
                ['name' => 'Bibiani', 'type' => 'town'],
                ['name' => 'Anhwiaso', 'type' => 'town'],
            ],
            'WEN-WAS' => [
                ['name' => 'Wassa East', 'type' => 'town'],
                ['name' => 'Wassa Akropong', 'type' => 'town'],
            ],
            'WEN-AOK' => [
                ['name' => 'Aowin', 'type' => 'town'],
                ['name' => 'Enchi', 'type' => 'town'],
            ],
            'WEN-JOM' => [
                ['name' => 'Juaboso', 'type' => 'town'],
                ['name' => 'Dadieso', 'type' => 'town'],
            ],
        ];
    }
}
