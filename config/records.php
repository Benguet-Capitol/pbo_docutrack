<?php

/*
 * Record categories (records.record_type) and their subcategories (records.record_subtype).
 *
 * "shared" categories are offered to other PBO systems through the Records API (/api/v1/records), e.g. pbo-registryV2's
 * Archives. Administrative records are internal and never shared.
 * Keep the subcategories in sync with recordTypesHierarchy in resources/js/Pages/Records/Composables/useRecordsData.ts.
 */
return [
    'categories' => [
        'provincial-budget' => [
            'name' => 'Provincial Budget',
            'code' => 'PB',
            'shared' => true,
            'subcategories' => [],
        ],
        'municipal-budget' => [
            'name' => 'Municipal Budget',
            'code' => 'MB',
            'shared' => true,
            'subcategories' => [
                'Atok', 'Bakun', 'Bokod', 'Buguias', 'Itogon', 'Kabayan', 'Kapangan', 'Kibungan', 'La Trinidad',
                'Mankayan', 'Sablan', 'Tuba', 'Tublay',
            ],
        ],
        'issuances' => [
            'name' => 'Issuances / Circulars / Other References and Documents',
            'code' => 'ISO',
            'shared' => true,
            'subcategories' => [
                'Provincial Ordinances',
                'DILG Memorandum Circulars',
                'DOF Department Orders',
                'Memorandum Circulars (Office of the President)',
                'PAG-IBIG',
                'COMELEC Resolutions',
                'DBM Budget Circulars',
                'COA Circulars',
                'CSC Circulars',
                'Local Budget Circulars',
                'Local Budget Memorandums',
                'DBM Orders / Circular Letters',
                'Joint Circulars',
                'Executive Orders (Office of the President)',
                'Presidential Decrees',
                'Republic Acts',
                'GPPB Circulars',
                'GSIS Memorandum Circulars',
                'DOH Circulars / Administrative Orders',
                'PHIC Circulars',
                'National Budget Circulars / Memorandums',
                'CHED Memorandum Circulars',
                'Budget Call',
                'Queries',
                'Annual Budget Transmittal / Indorsement to SPO',
                'Transmitted PPMPs to BAC',
                'SP Indorsement to DBM (APB)',
                'PBO Certifications to Plans / Other Reports',
                'DBM Letters / Reports / Matters',
                'List of Documentary Requirements',
                'Provincial NTAs',
                'Municipal NTAs',
                'PLGU Annual Budget Review',
            ],
        ],
        'administrative' => [
            'name' => 'Administrative',
            'code' => 'ADM',
            'shared' => false,
            'subcategories' => [],
        ],
    ],
];
