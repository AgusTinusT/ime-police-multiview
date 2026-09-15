<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Agency;
use App\Models\Rank;
use App\Models\Division;
use App\Models\Officer;

class AgenciesRanksDivisionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Agencies Data
        $agenciesData = [
            [
                'agency_code' => 'LSPD',
                'agency_name' => 'Los Santos Police Department',
                'jurisdiction' => 'City',
                'ranks' => [
                    ['rank_title' => 'Chief of Police', 'level' => 10, 'base_salary' => 15000],
                    ['rank_title' => 'Assistant Chief', 'level' => 9, 'base_salary' => 13500],
                    ['rank_title' => 'Deputy Chief', 'level' => 8, 'base_salary' => 12000],
                    ['rank_title' => 'Captain', 'level' => 7, 'base_salary' => 10500],
                    ['rank_title' => 'Lieutenant', 'level' => 6, 'base_salary' => 9000],
                    ['rank_title' => 'Sergeant', 'level' => 5, 'base_salary' => 7500],
                    ['rank_title' => 'Senior Officer', 'level' => 3, 'base_salary' => 6000],
                    ['rank_title' => 'Officer', 'level' => 2, 'base_salary' => 5000],
                    ['rank_title' => 'Cadet', 'level' => 1, 'base_salary' => 3500],
                ],
                'divisions' => [
                    ['division_name' => 'Metropolitan Division', 'division_code' => 'METRO'],
                    ['division_name' => 'Special Response Team', 'division_code' => 'SWAT'],
                    ['division_name' => 'Traffic Enforcement Division', 'division_code' => 'TED'],
                    ['division_name' => 'Detective Bureau', 'division_code' => 'DB'],
                    ['division_name' => 'K-9 Unit', 'division_code' => 'K9'],
                ],
            ],
            [
                'agency_code' => 'BCSO',
                'agency_name' => 'Blaine County Sheriff Office',
                'jurisdiction' => 'County',
                'ranks' => [
                    ['rank_title' => 'Sheriff', 'level' => 10, 'base_salary' => 15000],
                    ['rank_title' => 'Undersheriff', 'level' => 9, 'base_salary' => 13500],
                    ['rank_title' => 'Assistant Sheriff', 'level' => 8, 'base_salary' => 12000],
                    ['rank_title' => 'Captain', 'level' => 7, 'base_salary' => 10500],
                    ['rank_title' => 'Lieutenant', 'level' => 6, 'base_salary' => 9000],
                    ['rank_title' => 'Master Sergeant', 'level' => 5, 'base_salary' => 8000],
                    ['rank_title' => 'Sergeant', 'level' => 4, 'base_salary' => 7500],
                    ['rank_title' => 'Senior Deputy', 'level' => 3, 'base_salary' => 6000],
                    ['rank_title' => 'Deputy Sheriff', 'level' => 2, 'base_salary' => 5000],
                    ['rank_title' => 'Cadet Deputy', 'level' => 1, 'base_salary' => 3500],
                ],
                'divisions' => [
                    ['division_name' => 'County Patrol Division', 'division_code' => 'PATROL'],
                    ['division_name' => 'Special Enforcement Detail', 'division_code' => 'SED'],
                    ['division_name' => 'Off-Road & Rural Rescue', 'division_code' => 'RURAL'],
                    ['division_name' => 'Air Support Unit', 'division_code' => 'AIR'],
                ],
            ],
            [
                'agency_code' => 'SASP',
                'agency_name' => 'San Andreas State Police',
                'jurisdiction' => 'Statewide',
                'ranks' => [
                    ['rank_title' => 'State Commissioner', 'level' => 10, 'base_salary' => 16000],
                    ['rank_title' => 'Colonel', 'level' => 9, 'base_salary' => 14000],
                    ['rank_title' => 'Major', 'level' => 8, 'base_salary' => 12500],
                    ['rank_title' => 'Captain', 'level' => 7, 'base_salary' => 11000],
                    ['rank_title' => 'Lieutenant', 'level' => 6, 'base_salary' => 9500],
                    ['rank_title' => 'Staff Sergeant', 'level' => 5, 'base_salary' => 8200],
                    ['rank_title' => 'Sergeant', 'level' => 4, 'base_salary' => 7800],
                    ['rank_title' => 'Senior Trooper', 'level' => 3, 'base_salary' => 6500],
                    ['rank_title' => 'Trooper', 'level' => 2, 'base_salary' => 5500],
                    ['rank_title' => 'Probationary Trooper', 'level' => 1, 'base_salary' => 4000],
                ],
                'divisions' => [
                    ['division_name' => 'State Highway Patrol', 'division_code' => 'HP'],
                    ['division_name' => 'Tactical Response Division', 'division_code' => 'TRD'],
                    ['division_name' => 'Governor Protection Detail', 'division_code' => 'GPD'],
                    ['division_name' => 'Aviation & Marine Bureau', 'division_code' => 'AMB'],
                ],
            ],
            [
                'agency_code' => 'SAPR',
                'agency_name' => 'San Andreas Park Ranger',
                'jurisdiction' => 'State Parks',
                'ranks' => [
                    ['rank_title' => 'Chief Ranger', 'level' => 10, 'base_salary' => 14500],
                    ['rank_title' => 'Assistant Chief Ranger', 'level' => 9, 'base_salary' => 13000],
                    ['rank_title' => 'Captain Ranger', 'level' => 7, 'base_salary' => 10500],
                    ['rank_title' => 'Lieutenant Ranger', 'level' => 6, 'base_salary' => 9000],
                    ['rank_title' => 'Sergeant Ranger', 'level' => 5, 'base_salary' => 7500],
                    ['rank_title' => 'Senior Park Ranger', 'level' => 3, 'base_salary' => 6000],
                    ['rank_title' => 'Park Ranger', 'level' => 2, 'base_salary' => 5000],
                    ['rank_title' => 'Cadet Ranger', 'level' => 1, 'base_salary' => 3500],
                ],
                'divisions' => [
                    ['division_name' => 'Chiliad Wildlife Preservation', 'division_code' => 'WILD'],
                    ['division_name' => 'Maritime Search & Rescue', 'division_code' => 'RESCUE'],
                ],
            ],
        ];

        foreach ($agenciesData as $aData) {
            $ranks = $aData['ranks'];
            $divisions = $aData['divisions'];
            unset($aData['ranks'], $aData['divisions']);

            $agency = Agency::updateOrCreate(
                ['agency_code' => $aData['agency_code']],
                $aData
            );

            foreach ($ranks as $r) {
                Rank::updateOrCreate(
                    ['agency_id' => $agency->id, 'rank_title' => $r['rank_title']],
                    $r
                );
            }

            foreach ($divisions as $d) {
                Division::updateOrCreate(
                    ['agency_id' => $agency->id, 'division_code' => $d['division_code']],
                    $d
                );
            }
        }

        // 2. Link existing officers to agencies & ranks
        $officers = Officer::all();
        foreach ($officers as $officer) {
            $deptCode = $officer->department;
            if ($deptCode === 'PARK RANGER') $deptCode = 'SAPR';

            $agency = Agency::where('agency_code', $deptCode)->first();
            if (!$agency) {
                $agency = Agency::where('agency_code', 'LSPD')->first();
            }

            if ($agency) {
                $rank = Rank::where('agency_id', $agency->id)
                    ->where('rank_title', 'LIKE', "%{$officer->rank}%")
                    ->first();
                if (!$rank) {
                    $rank = Rank::where('agency_id', $agency->id)->orderBy('level', 'asc')->first();
                }

                $division = Division::where('agency_id', $agency->id)->first();

                $officer->update([
                    'agency_id' => $agency->id,
                    'rank_id' => $rank ? $rank->id : null,
                    'division_id' => $division ? $division->id : null,
                    'duty_status' => '10-8 (On-Duty)',
                ]);
            }
        }
    }
}
