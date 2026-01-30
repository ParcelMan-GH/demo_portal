<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\Region;
use Illuminate\Database\Seeder;

class GhanaLocationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $regions = $this->getRegionsWithDistricts();

        foreach ($regions as $regionData) {
            $region = Region::updateOrCreate(
                ['code' => $regionData['code']],
                [
                    'name' => $regionData['name'],
                    'is_active' => true,
                ]
            );

            foreach ($regionData['districts'] as $districtData) {
                District::updateOrCreate(
                    ['code' => $districtData['code']],
                    [
                        'region_id' => $region->id,
                        'name' => $districtData['name'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }

    /**
     * Get all Ghana regions with their districts.
     */
    private function getRegionsWithDistricts(): array
    {
        return [
            // 1. Greater Accra Region
            [
                'name' => 'Greater Accra',
                'code' => 'GAR',
                'districts' => [
                    ['name' => 'Accra Metropolitan', 'code' => 'GAR-AMA'],
                    ['name' => 'Tema Metropolitan', 'code' => 'GAR-TMA'],
                    ['name' => 'Ga East Municipal', 'code' => 'GAR-GEM'],
                    ['name' => 'Ga West Municipal', 'code' => 'GAR-GWM'],
                    ['name' => 'Ga North Municipal', 'code' => 'GAR-GNM'],
                    ['name' => 'Ga South Municipal', 'code' => 'GAR-GSM'],
                    ['name' => 'Ga Central Municipal', 'code' => 'GAR-GCM'],
                    ['name' => 'Ledzokuku Municipal', 'code' => 'GAR-LMA'],
                    ['name' => 'Krowor Municipal', 'code' => 'GAR-KMA'],
                    ['name' => 'La Dade-Kotopon Municipal', 'code' => 'GAR-LDK'],
                    ['name' => 'La Nkwantanang-Madina Municipal', 'code' => 'GAR-LNM'],
                    ['name' => 'Adentan Municipal', 'code' => 'GAR-ADM'],
                    ['name' => 'Ashaiman Municipal', 'code' => 'GAR-ASM'],
                    ['name' => 'Kpone Katamanso Municipal', 'code' => 'GAR-KKM'],
                    ['name' => 'Ada East', 'code' => 'GAR-ADE'],
                    ['name' => 'Ada West', 'code' => 'GAR-ADW'],
                    ['name' => 'Ningo-Prampram', 'code' => 'GAR-NPD'],
                    ['name' => 'Shai-Osudoku', 'code' => 'GAR-SOD'],
                    ['name' => 'Ablekuma Central Municipal', 'code' => 'GAR-ACM'],
                    ['name' => 'Ablekuma North Municipal', 'code' => 'GAR-ANM'],
                    ['name' => 'Ablekuma West Municipal', 'code' => 'GAR-AWM'],
                    ['name' => 'Ayawaso Central Municipal', 'code' => 'GAR-AYC'],
                    ['name' => 'Ayawaso East Municipal', 'code' => 'GAR-AYE'],
                    ['name' => 'Ayawaso North Municipal', 'code' => 'GAR-AYN'],
                    ['name' => 'Ayawaso West Municipal', 'code' => 'GAR-AYW'],
                    ['name' => 'Korle Klottey Municipal', 'code' => 'GAR-KKL'],
                    ['name' => 'Okaikwei North Municipal', 'code' => 'GAR-OKN'],
                    ['name' => 'Tema West Municipal', 'code' => 'GAR-TWM'],
                    ['name' => 'Weija-Gbawe Municipal', 'code' => 'GAR-WGM'],
                ],
            ],

            // 2. Ashanti Region
            [
                'name' => 'Ashanti',
                'code' => 'ASH',
                'districts' => [
                    ['name' => 'Kumasi Metropolitan', 'code' => 'ASH-KMA'],
                    ['name' => 'Obuasi Municipal', 'code' => 'ASH-OBM'],
                    ['name' => 'Asokore Mampong Municipal', 'code' => 'ASH-AMM'],
                    ['name' => 'Bekwai Municipal', 'code' => 'ASH-BKM'],
                    ['name' => 'Ejisu Municipal', 'code' => 'ASH-EJM'],
                    ['name' => 'Mampong Municipal', 'code' => 'ASH-MPM'],
                    ['name' => 'Offinso North', 'code' => 'ASH-OFN'],
                    ['name' => 'Offinso Municipal', 'code' => 'ASH-OFM'],
                    ['name' => 'Afigya Kwabre South', 'code' => 'ASH-AKS'],
                    ['name' => 'Afigya Kwabre North', 'code' => 'ASH-AKN'],
                    ['name' => 'Ahafo Ano North Municipal', 'code' => 'ASH-AAN'],
                    ['name' => 'Ahafo Ano South East', 'code' => 'ASH-AAS'],
                    ['name' => 'Ahafo Ano South West', 'code' => 'ASH-AAW'],
                    ['name' => 'Amansie Central', 'code' => 'ASH-AMC'],
                    ['name' => 'Amansie South', 'code' => 'ASH-AMS'],
                    ['name' => 'Amansie West', 'code' => 'ASH-AMW'],
                    ['name' => 'Asante Akim Central Municipal', 'code' => 'ASH-AAC'],
                    ['name' => 'Asante Akim North', 'code' => 'ASH-AAN2'],
                    ['name' => 'Asante Akim South', 'code' => 'ASH-AAS2'],
                    ['name' => 'Asokwa Municipal', 'code' => 'ASH-ASK'],
                    ['name' => 'Atwima Kwanwoma', 'code' => 'ASH-ATK'],
                    ['name' => 'Atwima Mponua', 'code' => 'ASH-ATM'],
                    ['name' => 'Atwima Nwabiagya Municipal', 'code' => 'ASH-ANM'],
                    ['name' => 'Atwima Nwabiagya North', 'code' => 'ASH-ANN'],
                    ['name' => 'Bosome Freho', 'code' => 'ASH-BFR'],
                    ['name' => 'Bosomtwe', 'code' => 'ASH-BOS'],
                    ['name' => 'Ejura Sekyedumase Municipal', 'code' => 'ASH-ESM'],
                    ['name' => 'Juaben Municipal', 'code' => 'ASH-JBM'],
                    ['name' => 'Kwabre East Municipal', 'code' => 'ASH-KEM'],
                    ['name' => 'Kwadaso Municipal', 'code' => 'ASH-KDM'],
                    ['name' => 'Nhyiaeso Municipal', 'code' => 'ASH-NHM'],
                    ['name' => 'Obuasi East', 'code' => 'ASH-OBE'],
                    ['name' => 'Oforikrom Municipal', 'code' => 'ASH-OFR'],
                    ['name' => 'Old Tafo Municipal', 'code' => 'ASH-OTM'],
                    ['name' => 'Sekyere Afram Plains', 'code' => 'ASH-SAP'],
                    ['name' => 'Sekyere Central', 'code' => 'ASH-SEC'],
                    ['name' => 'Sekyere East', 'code' => 'ASH-SEE'],
                    ['name' => 'Sekyere Kumawu', 'code' => 'ASH-SKU'],
                    ['name' => 'Sekyere South', 'code' => 'ASH-SES'],
                    ['name' => 'Suame Municipal', 'code' => 'ASH-SUM'],
                    ['name' => 'Adansi Asokwa', 'code' => 'ASH-ADA'],
                    ['name' => 'Adansi North', 'code' => 'ASH-ADN'],
                    ['name' => 'Adansi South', 'code' => 'ASH-ADS'],
                ],
            ],

            // 3. Western Region
            [
                'name' => 'Western',
                'code' => 'WES',
                'districts' => [
                    ['name' => 'Sekondi-Takoradi Metropolitan', 'code' => 'WES-STM'],
                    ['name' => 'Tarkwa-Nsuaem Municipal', 'code' => 'WES-TNM'],
                    ['name' => 'Prestea-Huni Valley Municipal', 'code' => 'WES-PHV'],
                    ['name' => 'Effia-Kwesimintsim Municipal', 'code' => 'WES-EKM'],
                    ['name' => 'Ahanta West Municipal', 'code' => 'WES-AWM'],
                    ['name' => 'Ellembelle', 'code' => 'WES-ELL'],
                    ['name' => 'Jomoro Municipal', 'code' => 'WES-JOM'],
                    ['name' => 'Mpohor', 'code' => 'WES-MPO'],
                    ['name' => 'Nzema East Municipal', 'code' => 'WES-NZE'],
                    ['name' => 'Shama', 'code' => 'WES-SHA'],
                    ['name' => 'Wassa Amenfi Central', 'code' => 'WES-WAC'],
                    ['name' => 'Wassa Amenfi East', 'code' => 'WES-WAE'],
                    ['name' => 'Wassa Amenfi West', 'code' => 'WES-WAW'],
                    ['name' => 'Wassa East', 'code' => 'WES-WEA'],
                ],
            ],

            // 4. Central Region
            [
                'name' => 'Central',
                'code' => 'CEN',
                'districts' => [
                    ['name' => 'Cape Coast Metropolitan', 'code' => 'CEN-CCM'],
                    ['name' => 'Komenda-Edina-Eguafo-Abirem Municipal', 'code' => 'CEN-KEE'],
                    ['name' => 'Mfantseman Municipal', 'code' => 'CEN-MFM'],
                    ['name' => 'Abura-Asebu-Kwamankese', 'code' => 'CEN-AAK'],
                    ['name' => 'Agona East', 'code' => 'CEN-AGE'],
                    ['name' => 'Agona West Municipal', 'code' => 'CEN-AGW'],
                    ['name' => 'Ajumako-Enyan-Esiam', 'code' => 'CEN-AEE'],
                    ['name' => 'Asikuma-Odoben-Brakwa', 'code' => 'CEN-AOB'],
                    ['name' => 'Assin Central Municipal', 'code' => 'CEN-ACM'],
                    ['name' => 'Assin North', 'code' => 'CEN-ASN'],
                    ['name' => 'Assin South', 'code' => 'CEN-ASS'],
                    ['name' => 'Awutu Senya East Municipal', 'code' => 'CEN-ASE'],
                    ['name' => 'Awutu Senya West', 'code' => 'CEN-ASW'],
                    ['name' => 'Effutu Municipal', 'code' => 'CEN-EFM'],
                    ['name' => 'Ekumfi', 'code' => 'CEN-EKU'],
                    ['name' => 'Gomoa Central', 'code' => 'CEN-GOC'],
                    ['name' => 'Gomoa East', 'code' => 'CEN-GOE'],
                    ['name' => 'Gomoa West', 'code' => 'CEN-GOW'],
                    ['name' => 'Twifo Atti-Morkwa', 'code' => 'CEN-TAM'],
                    ['name' => 'Twifo-Hemang-Lower Denkyira', 'code' => 'CEN-THD'],
                    ['name' => 'Upper Denkyira East Municipal', 'code' => 'CEN-UDE'],
                    ['name' => 'Upper Denkyira West', 'code' => 'CEN-UDW'],
                ],
            ],

            // 5. Eastern Region
            [
                'name' => 'Eastern',
                'code' => 'EAS',
                'districts' => [
                    ['name' => 'New Juaben South Municipal', 'code' => 'EAS-NJS'],
                    ['name' => 'New Juaben North Municipal', 'code' => 'EAS-NJN'],
                    ['name' => 'Achiase', 'code' => 'EAS-ACH'],
                    ['name' => 'Akyemansa', 'code' => 'EAS-AKY'],
                    ['name' => 'Akuapem North Municipal', 'code' => 'EAS-ANM'],
                    ['name' => 'Akuapem South', 'code' => 'EAS-AKS'],
                    ['name' => 'Asuogyaman', 'code' => 'EAS-ASU'],
                    ['name' => 'Atiwa East', 'code' => 'EAS-ATE'],
                    ['name' => 'Atiwa West', 'code' => 'EAS-ATW'],
                    ['name' => 'Ayensuano', 'code' => 'EAS-AYE'],
                    ['name' => 'Birim Central Municipal', 'code' => 'EAS-BCM'],
                    ['name' => 'Birim North', 'code' => 'EAS-BIN'],
                    ['name' => 'Birim South', 'code' => 'EAS-BIS'],
                    ['name' => 'Denkyembuor', 'code' => 'EAS-DEN'],
                    ['name' => 'East Akim Municipal', 'code' => 'EAS-EAM'],
                    ['name' => 'Fanteakwa North', 'code' => 'EAS-FAN'],
                    ['name' => 'Fanteakwa South', 'code' => 'EAS-FAS'],
                    ['name' => 'Kwaebibirem Municipal', 'code' => 'EAS-KWM'],
                    ['name' => 'Kwahu Afram Plains North', 'code' => 'EAS-KAN'],
                    ['name' => 'Kwahu Afram Plains South', 'code' => 'EAS-KAS'],
                    ['name' => 'Kwahu East', 'code' => 'EAS-KWE'],
                    ['name' => 'Kwahu South', 'code' => 'EAS-KWS'],
                    ['name' => 'Kwahu West Municipal', 'code' => 'EAS-KWW'],
                    ['name' => 'Lower Manya Krobo Municipal', 'code' => 'EAS-LMK'],
                    ['name' => 'Nsawam-Adoagyiri Municipal', 'code' => 'EAS-NAM'],
                    ['name' => 'Okere', 'code' => 'EAS-OKE'],
                    ['name' => 'Suhum Municipal', 'code' => 'EAS-SUM'],
                    ['name' => 'Upper Manya Krobo', 'code' => 'EAS-UMK'],
                    ['name' => 'Upper West Akim', 'code' => 'EAS-UWA'],
                    ['name' => 'West Akim Municipal', 'code' => 'EAS-WAM'],
                    ['name' => 'Yilo Krobo Municipal', 'code' => 'EAS-YKM'],
                ],
            ],

            // 6. Volta Region
            [
                'name' => 'Volta',
                'code' => 'VOL',
                'districts' => [
                    ['name' => 'Ho Municipal', 'code' => 'VOL-HOM'],
                    ['name' => 'Hohoe Municipal', 'code' => 'VOL-HOH'],
                    ['name' => 'Keta Municipal', 'code' => 'VOL-KET'],
                    ['name' => 'Ketu South Municipal', 'code' => 'VOL-KSM'],
                    ['name' => 'Adaklu', 'code' => 'VOL-ADA'],
                    ['name' => 'Afadjato South', 'code' => 'VOL-AFS'],
                    ['name' => 'Agotime-Ziope', 'code' => 'VOL-AGZ'],
                    ['name' => 'Akatsi North', 'code' => 'VOL-AKN'],
                    ['name' => 'Akatsi South', 'code' => 'VOL-AKS'],
                    ['name' => 'Anloga', 'code' => 'VOL-ANL'],
                    ['name' => 'Central Tongu', 'code' => 'VOL-CET'],
                    ['name' => 'Ho West', 'code' => 'VOL-HOW'],
                    ['name' => 'Ketu North', 'code' => 'VOL-KEN'],
                    ['name' => 'North Dayi', 'code' => 'VOL-NOD'],
                    ['name' => 'North Tongu', 'code' => 'VOL-NOT'],
                    ['name' => 'South Dayi', 'code' => 'VOL-SOD'],
                    ['name' => 'South Tongu', 'code' => 'VOL-SOT'],
                ],
            ],

            // 7. Northern Region
            [
                'name' => 'Northern',
                'code' => 'NOR',
                'districts' => [
                    ['name' => 'Tamale Metropolitan', 'code' => 'NOR-TAM'],
                    ['name' => 'Sagnarigu Municipal', 'code' => 'NOR-SAG'],
                    ['name' => 'Gushegu Municipal', 'code' => 'NOR-GUS'],
                    ['name' => 'Yendi Municipal', 'code' => 'NOR-YEN'],
                    ['name' => 'Karaga', 'code' => 'NOR-KAR'],
                    ['name' => 'Kpandai', 'code' => 'NOR-KPA'],
                    ['name' => 'Kumbungu', 'code' => 'NOR-KUM'],
                    ['name' => 'Mion', 'code' => 'NOR-MIO'],
                    ['name' => 'Nanton', 'code' => 'NOR-NAN'],
                    ['name' => 'Nanumba North Municipal', 'code' => 'NOR-NNM'],
                    ['name' => 'Nanumba South', 'code' => 'NOR-NAS'],
                    ['name' => 'Saboba', 'code' => 'NOR-SAB'],
                    ['name' => 'Savelugu Municipal', 'code' => 'NOR-SAV'],
                    ['name' => 'Tatale-Sanguli', 'code' => 'NOR-TAS'],
                    ['name' => 'Tolon', 'code' => 'NOR-TOL'],
                    ['name' => 'Zabzugu', 'code' => 'NOR-ZAB'],
                ],
            ],

            // 8. Upper East Region
            [
                'name' => 'Upper East',
                'code' => 'UPE',
                'districts' => [
                    ['name' => 'Bolgatanga Municipal', 'code' => 'UPE-BOL'],
                    ['name' => 'Bawku Municipal', 'code' => 'UPE-BAW'],
                    ['name' => 'Bawku West', 'code' => 'UPE-BWW'],
                    ['name' => 'Binduri', 'code' => 'UPE-BIN'],
                    ['name' => 'Bolgatanga East', 'code' => 'UPE-BOE'],
                    ['name' => 'Bongo', 'code' => 'UPE-BON'],
                    ['name' => 'Builsa North', 'code' => 'UPE-BUN'],
                    ['name' => 'Builsa South', 'code' => 'UPE-BUS'],
                    ['name' => 'Garu', 'code' => 'UPE-GAR'],
                    ['name' => 'Kassena-Nankana Municipal', 'code' => 'UPE-KNM'],
                    ['name' => 'Kassena-Nankana West', 'code' => 'UPE-KNW'],
                    ['name' => 'Nabdam', 'code' => 'UPE-NAB'],
                    ['name' => 'Pusiga', 'code' => 'UPE-PUS'],
                    ['name' => 'Talensi', 'code' => 'UPE-TAL'],
                    ['name' => 'Tempane', 'code' => 'UPE-TEM'],
                ],
            ],

            // 9. Upper West Region
            [
                'name' => 'Upper West',
                'code' => 'UPW',
                'districts' => [
                    ['name' => 'Wa Municipal', 'code' => 'UPW-WAM'],
                    ['name' => 'Daffiama-Bussie-Issa', 'code' => 'UPW-DBI'],
                    ['name' => 'Jirapa Municipal', 'code' => 'UPW-JIR'],
                    ['name' => 'Lambussie-Karni', 'code' => 'UPW-LAK'],
                    ['name' => 'Lawra Municipal', 'code' => 'UPW-LAW'],
                    ['name' => 'Nadowli-Kaleo', 'code' => 'UPW-NAK'],
                    ['name' => 'Nandom Municipal', 'code' => 'UPW-NAN'],
                    ['name' => 'Sissala East Municipal', 'code' => 'UPW-SEM'],
                    ['name' => 'Sissala West', 'code' => 'UPW-SIW'],
                    ['name' => 'Wa East', 'code' => 'UPW-WAE'],
                    ['name' => 'Wa West', 'code' => 'UPW-WAW'],
                ],
            ],

            // 10. Bono Region
            [
                'name' => 'Bono',
                'code' => 'BON',
                'districts' => [
                    ['name' => 'Sunyani Municipal', 'code' => 'BON-SUM'],
                    ['name' => 'Sunyani West', 'code' => 'BON-SUW'],
                    ['name' => 'Berekum East Municipal', 'code' => 'BON-BEM'],
                    ['name' => 'Berekum West', 'code' => 'BON-BEW'],
                    ['name' => 'Dormaa Central Municipal', 'code' => 'BON-DCM'],
                    ['name' => 'Dormaa East', 'code' => 'BON-DOE'],
                    ['name' => 'Dormaa West', 'code' => 'BON-DOW'],
                    ['name' => 'Jaman North', 'code' => 'BON-JAN'],
                    ['name' => 'Jaman South Municipal', 'code' => 'BON-JSM'],
                    ['name' => 'Tain', 'code' => 'BON-TAI'],
                    ['name' => 'Wenchi Municipal', 'code' => 'BON-WEN'],
                ],
            ],

            // 11. Bono East Region
            [
                'name' => 'Bono East',
                'code' => 'BOE',
                'districts' => [
                    ['name' => 'Techiman Municipal', 'code' => 'BOE-TEM'],
                    ['name' => 'Techiman North', 'code' => 'BOE-TEN'],
                    ['name' => 'Atebubu-Amantin Municipal', 'code' => 'BOE-AAM'],
                    ['name' => 'Kintampo North Municipal', 'code' => 'BOE-KNM'],
                    ['name' => 'Kintampo South', 'code' => 'BOE-KIS'],
                    ['name' => 'Nkoranza North', 'code' => 'BOE-NKN'],
                    ['name' => 'Nkoranza South Municipal', 'code' => 'BOE-NSM'],
                    ['name' => 'Pru East', 'code' => 'BOE-PRE'],
                    ['name' => 'Pru West', 'code' => 'BOE-PRW'],
                    ['name' => 'Sene East', 'code' => 'BOE-SEE'],
                    ['name' => 'Sene West', 'code' => 'BOE-SEW'],
                ],
            ],

            // 12. Ahafo Region
            [
                'name' => 'Ahafo',
                'code' => 'AHA',
                'districts' => [
                    ['name' => 'Asunafo North Municipal', 'code' => 'AHA-ANM'],
                    ['name' => 'Asunafo South', 'code' => 'AHA-ASS'],
                    ['name' => 'Asutifi North', 'code' => 'AHA-ASN'],
                    ['name' => 'Asutifi South', 'code' => 'AHA-ATS'],
                    ['name' => 'Tano North Municipal', 'code' => 'AHA-TNM'],
                    ['name' => 'Tano South Municipal', 'code' => 'AHA-TSM'],
                ],
            ],

            // 13. Western North Region
            [
                'name' => 'Western North',
                'code' => 'WEN',
                'districts' => [
                    ['name' => 'Sefwi Wiawso Municipal', 'code' => 'WEN-SWM'],
                    ['name' => 'Bibiani-Anhwiaso-Bekwai Municipal', 'code' => 'WEN-BAB'],
                    ['name' => 'Aowin Municipal', 'code' => 'WEN-AOM'],
                    ['name' => 'Bia East', 'code' => 'WEN-BIE'],
                    ['name' => 'Bia West', 'code' => 'WEN-BIW'],
                    ['name' => 'Bodi', 'code' => 'WEN-BOD'],
                    ['name' => 'Juaboso', 'code' => 'WEN-JUA'],
                    ['name' => 'Sefwi Akontombra', 'code' => 'WEN-SAK'],
                    ['name' => 'Suaman', 'code' => 'WEN-SUA'],
                ],
            ],

            // 14. Oti Region
            [
                'name' => 'Oti',
                'code' => 'OTI',
                'districts' => [
                    ['name' => 'Kadjebi', 'code' => 'OTI-KAD'],
                    ['name' => 'Krachi East Municipal', 'code' => 'OTI-KEM'],
                    ['name' => 'Krachi Nchumuru', 'code' => 'OTI-KNC'],
                    ['name' => 'Krachi West', 'code' => 'OTI-KRW'],
                    ['name' => 'Nkwanta North', 'code' => 'OTI-NKN'],
                    ['name' => 'Nkwanta South Municipal', 'code' => 'OTI-NSM'],
                    ['name' => 'Biakoye', 'code' => 'OTI-BIA'],
                    ['name' => 'Jasikan', 'code' => 'OTI-JAS'],
                ],
            ],

            // 15. Savannah Region
            [
                'name' => 'Savannah',
                'code' => 'SAV',
                'districts' => [
                    ['name' => 'Bole', 'code' => 'SAV-BOL'],
                    ['name' => 'Central Gonja', 'code' => 'SAV-CEG'],
                    ['name' => 'East Gonja Municipal', 'code' => 'SAV-EGM'],
                    ['name' => 'North East Gonja', 'code' => 'SAV-NEG'],
                    ['name' => 'North Gonja', 'code' => 'SAV-NOG'],
                    ['name' => 'Sawla-Tuna-Kalba', 'code' => 'SAV-STK'],
                    ['name' => 'West Gonja Municipal', 'code' => 'SAV-WGM'],
                ],
            ],

            // 16. North East Region
            [
                'name' => 'North East',
                'code' => 'NEA',
                'districts' => [
                    ['name' => 'Bunkpurugu-Nakpanduri', 'code' => 'NEA-BUN'],
                    ['name' => 'Chereponi', 'code' => 'NEA-CHE'],
                    ['name' => 'East Mamprusi Municipal', 'code' => 'NEA-EMM'],
                    ['name' => 'Mamprugu Moagduri', 'code' => 'NEA-MAM'],
                    ['name' => 'West Mamprusi Municipal', 'code' => 'NEA-WMM'],
                    ['name' => 'Yunyoo-Nasuan', 'code' => 'NEA-YUN'],
                ],
            ],
        ];
    }
}
