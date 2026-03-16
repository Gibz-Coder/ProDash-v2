<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QcDefectClassSeeder extends Seeder
{
    public function run(): void
    {
        $defects = [
            ['AC',         'Active Cover Crack',              'QC Analysis',       'QC Analysis'],
            ['BB',         'Blister',                         'QC Analysis',       'QC Analysis'],
            ['BRB',        'Broken Body',                     "Tech'l Verification", "Tech'l Verification"],
            ['BRC',        'Broken Corner',                   "Tech'l Verification", "Tech'l Verification"],
            ['BRT',        'Broken Term',                     "Tech'l Verification", "Tech'l Verification"],
            ['BST',        'Black Spot (Ba, Ti, Zr, Si)',     'QC Analysis',       'QC Analysis'],
            ['BU',         'Burst',                           "Tech'l Verification", "Tech'l Verification"],
            ['BW',         'Bandwidth',                       'QC Analysis',       'QC Analysis'],
            ['CC',         'Center Crack',                    'QC Analysis',       'QC Analysis'],
            ['CD',         'Ceramic Discoloration',           'QC Analysis',       'QC Analysis'],
            ['CH',         'Chipping',                        "Tech'l Verification", "Tech'l Verification"],
            ['CMT',        'Circular Mode Top',               'QC Analysis',       'QC Analysis'],
            ['CS',         'Cutting Scratch /Chip Scratch',   "Tech'l Verification", "Tech'l Verification"],
            ['CUE',        'Copper Exposed',                  "Tech'l Verification", "Tech'l Verification"],
            ['DT',         'Dark Term',                       'QC Analysis',       'QC Analysis'],
            ['EBC',        'Eyebrow Crack',                   'QC Analysis',       'QC Analysis'],
            ['EED',        'Dotted',                          'QC Analysis',       'QC Analysis'],
            ['EEP',        'Exposed Electrode Plate',         "Tech'l Verification", "Tech'l Verification"],
            ['EER',        'Exposed Electrode Reflection',    'QC Analysis',       'QC Analysis'],
            ['EEU',        'EEU',                             'QC Analysis',       'QC Analysis'],
            ['FC',         'Firing Crack',                    'QC Analysis',       'QC Analysis'],
            ['FMB',        'Foreign Matter Body',             "Tech'l Verification", "Tech'l Verification"],
            ['FMT',        'Foreign Matter Term',             "Tech'l Verification", "Tech'l Verification"],
            ['LD',         'Lamination Defect',               "Tech'l Verification", "Tech'l Verification"],
            ['MC',         'Mixed Color',                     'QC Analysis',       'QC Analysis'],
            ['ML',         'Mixed Lot',                       'QC Analysis',       'QC Analysis'],
            ['MSC',        'Miscutting',                      "Tech'l Verification", "Tech'l Verification"],
            ['NP',         'No Plating',                      'QC Analysis',       'QC Analysis'],
            ['NT',         'No Term',                         "Tech'l Verification", "Tech'l Verification"],
            ['OF',         'Overflow',                        'QC Analysis',       'QC Analysis'],
            ['OT',         'Overtumbled',                     "Tech'l Verification", "Tech'l Verification"],
            ['PD',         'Plating Discoloration',           'QC Analysis',       'QC Analysis'],
            ['PH',         'Pinhole',                         'QC Analysis',       'QC Analysis'],
            ['PLOF',       'Plating Overflow',                'QC Analysis',       'QC Analysis'],
            ['PO',         'Peeled Off Body',                 "Tech'l Verification", "Tech'l Verification"],
            ['POC',        'Peeled Off Corner',               "Tech'l Verification", "Tech'l Verification"],
            ['POT',        'Peeled Off Top',                  "Tech'l Verification", "Tech'l Verification"],
            ['POTBELLY',   'Potbelly',                        'QC Analysis',       'QC Analysis'],
            ['PPO',        'Plating peeled-off',              "Tech'l Verification", "Tech'l Verification"],
            ['PS',         'Printed Sheet',                   'QC Analysis',       'QC Analysis'],
            ['FT',         'Flattened Top',                   'QC Analysis',       'QC Analysis'],
            ['ROUGH',      'Rough',                           "Tech'l Verification", "Tech'l Verification"],
            ['RT',         'Raptured Term',                   'QC Analysis',       'QC Analysis'],
            ['SCB',        'Sorting Crack Body',              "Tech'l Verification", "Tech'l Verification"],
            ['SCT',        'Sorting Crack Top',               "Tech'l Verification", "Tech'l Verification"],
            ['SD',         'Side Delam',                      'QC Analysis',       'QC Analysis'],
            ['SFC',        'Surface Crack',                   "Tech'l Verification", "Tech'l Verification"],
            ['SLT',        'Slant Term',                      "Tech'l Verification", "Tech'l Verification"],
            ['SNG',        'Size NG (LWT)',                   'QC Analysis',       'QC Analysis'],
            ['SSB',        'Sorting Scratch Body',            'QC Analysis',       'QC Analysis'],
            ['SST',        'Sorting Scratch Top',             'QC Analysis',       'QC Analysis'],
            ['STAIN',      'STAIN',                           'QC Analysis',       'QC Analysis'],
            ['TBU',        'term burst',                      'QC Analysis',       'QC Analysis'],
            ['TC',         'Term Crack',                      'QC Analysis',       'QC Analysis'],
            ['THT',        'Thick Term',                      "Tech'l Verification", "Tech'l Verification"],
            ['TIN SPLASH', 'Tin Splash',                      "Tech'l Verification", "Tech'l Verification"],
            ['TT',         'Thin Term',                       "Tech'l Verification", "Tech'l Verification"],
            ['UNPL',       'Unplated Plating',                'QC Analysis',       'QC Analysis'],
            ['UNPS',       'Unplated Sorting Scratch',        'QC Analysis',       'QC Analysis'],
            ['UNT',        'Uneven Term',                     'QC Analysis',       'QC Analysis'],
            ['UNUSUAL',    'QCA - Defect',                    'QC Analysis',       'QC Analysis'],
            ['UT',         'Untumbled',                       'QC Analysis',       'QC Analysis'],
            ['VC',         'Vertical Crack',                  'QC Analysis',       'QC Analysis'],
            ['VL',         'Vertical Line',                   'QC Analysis',       'QC Analysis'],
            ['VT',         'Vampire Tooth',                   'QC Analysis',       'QC Analysis'],
            ['WST',        'White Spot',                      'QC Analysis',       'QC Analysis'],
            ['WRINKLES',   'Wrinkles',                        'QC Analysis',       'QC Analysis'],
            ['DC',         'Dark Chips',                      'QC Analysis',       'QC Analysis'],
            ['FLATTENED',  'Flattened',                       'QC Analysis',       'QC Analysis'],
            ['BULGE',      'Bulge',                           'QC Analysis',       'QC Analysis'],
            ['MOISTURE',   'Moisture',                        'QC Analysis',       'QC Analysis'],
            ['CONDUCTIVE', 'Conductive',                      'QC Analysis',       'QC Analysis'],
            ['POBC',       'Peeled Off Crack',                'QC Analysis',       'QC Analysis'],
            ['DEBONDING',  'Debonding',                       'QC Analysis',       'QC Analysis'],
            ['INSULATE',   'Insulate',                        "Tech'l Verification", "Tech'l Verification"],
            ['CHS',        'Chip Stuck',                      "Tech'l Verification", "Tech'l Verification"],
            ['MXC',        'Mix Color',                       'QC Analysis',       'QC Analysis'],
            ['PHT',        'Pinhole Top',                     'QC Analysis',       'QC Analysis'],
        ];

        $now = now();

        foreach ($defects as [$code, $name, $class, $flow]) {
            DB::table('qc_defect_class')->updateOrInsert(
                ['defect_code' => $code],
                [
                    'defect_name'  => $name,
                    'defect_class' => $class,
                    'defect_flow'  => $flow,
                    'created_by'   => 'HAPITA, GILBERT HIBE',
                    'remarks'      => null,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ]
            );
        }
    }
}
