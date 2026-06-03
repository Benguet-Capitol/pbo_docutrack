<?php

namespace Database\Seeders;

use App\Models\DocumentChecklistTemplate;
use App\Models\DocumentChecklistItem;
use Illuminate\Database\Seeder;

class SupplementalBudgetChecklistSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create or update Supplemental Budget checklist template
        $supplementalBudgetTemplate = DocumentChecklistTemplate::updateOrCreate(
            ['document_type' => 'Supplemental Budget'],
            [
                'description' => 'Documentary checklist for Supplemental Budget documents (LBR Form No. 1B)',
                'status' => 'active',
            ]
        );

        // Add checklist items for Supplemental Budget based on LBR Form No. 1B
        $items = [
            [
                'item_name' => 'Transmittal Letter',
                'signatories' => 'Secretary to the Sanggunian',
                'remarks' => json_encode(['Letter Dated' => '']),
                'order' => 1,
                'group_letter' => 'a',
                'is_subitem' => false,
            ],
            [
                'item_name' => 'Appropriation Ordinance (AO), carrying the seal of the LGU',
                'signatories' => 'Secretary to the Sanggunian, Presiding Officer, Local Chief Executive',
                'remarks' => json_encode(['AO No.' => '', 'Date Approved' => '']),
                'order' => 2,
                'group_letter' => 'b',
                'is_subitem' => false,
            ],
            [
                'item_name' => 'Funds Actually Available',
                'signatories' => '',
                'remarks' => json_encode(['Details' => '']),
                'order' => 3,
                'group_letter' => 'c',
                'is_subitem' => false,
            ],
            [
                'item_name' => 'Certified Statement of Additional Realized Income',
                'signatories' => 'Local Treasurer, Local Accountant',
                'remarks' => json_encode([]),
                'order' => 3.1,
                'group_letter' => 'c',
                'is_subitem' => true,
            ],
            [
                'item_name' => 'Certification of Savings',
                'signatories' => 'Local Treasurer, Local Accountant',
                'remarks' => json_encode([]),
                'order' => 3.2,
                'group_letter' => 'c',
                'is_subitem' => true,
            ],
            [
                'item_name' => 'New Revenue Measures',
                'signatories' => 'Local Treasurer, Local Accountant',
                'remarks' => json_encode(['Details' => '']),
                'order' => 4,
                'group_letter' => 'd',
                'is_subitem' => false,
            ],
            [
                'item_name' => 'Certified Statement of Income from New Revenue Measures',
                'signatories' => 'Local Treasurer, Local Accountant',
                'remarks' => json_encode([]),
                'order' => 4.1,
                'group_letter' => 'd',
                'is_subitem' => true,
            ],
            [
                'item_name' => 'Copy of duly enacted Tax Ordinance which imposes new local taxes, charges, fees, fines or penalties or which raises an existing local taxes, charges, fees, fines or penalties',
                'signatories' => 'Local Treasurer, Local Accountant',
                'remarks' => json_encode([]),
                'order' => 4.2,
                'group_letter' => 'd',
                'is_subitem' => true,
            ],
            [
                'item_name' => 'Copy of official communication stating that the LGU is a beneficiary of remittances, contribution & subsidies or grants in aid from the National Government or from government corporations and private entities',
                'signatories' => 'Local Treasurer, Local Accountant',
                'remarks' => json_encode([]),
                'order' => 4.3,
                'group_letter' => 'd',
                'is_subitem' => true,
            ],
            [
                'item_name' => 'Copy of official communication document covering the approved loan',
                'signatories' => 'Local Treasurer, Local Accountant',
                'remarks' => json_encode([]),
                'order' => 4.4,
                'group_letter' => 'd',
                'is_subitem' => true,
            ],
            [
                'item_name' => 'Realignment of Appropriations in Times of Public Calamity',
                'signatories' => 'Local Treasurer, Local Accountant, Local Chief Executive',
                'remarks' => json_encode(['Details' => '']),
                'order' => 5,
                'group_letter' => 'e',
                'is_subitem' => false,
            ],
            [
                'item_name' => 'Certificate of Source of Funds available for Appropriations',
                'signatories' => '',
                'remarks' => json_encode(['Details' => '']),
                'order' => 6,
                'group_letter' => 'e',
                'is_subitem' => true,
            ],
            [
                'item_name' => 'Statement of Supplemental Appropriation, unless indicated in the AO',
                'signatories' => 'Local Budget Officer (LBO), Local Chief Executive (LCE)',
                'remarks' => json_encode(['Details' => '']),
                'order' => 7,
                'group_letter' => 'f',
                'is_subitem' => false,
            ],
            [
                'item_name' => 'Supplemental Annual Investment Program (AIP), if applicable, approved by the Sanggunian',
                'signatories' => 'Local PDC, Local Budget Officer, Local Chief Executive',
                'remarks' => json_encode(['Details' => '']),
                'order' => 8,
                'group_letter' => 'g',
                'is_subitem' => false,
            ],
            [
                'item_name' => 'Resolution approving the Supplemental AIP',
                'signatories' => 'Secretary to the Sanggunian, Presiding Officer, Local Chief Executive',
                'remarks' => json_encode(['Details' => '']),
                'order' => 8.1,
                'group_letter' => 'g',
                'is_subitem' => true,
            ],
            [
                'item_name' => 'Veto message, if any',
                'signatories' => 'Local Chief Executive',
                'remarks' => json_encode(['Details' => '']),
                'order' => 9,
                'group_letter' => 'h',
                'is_subitem' => false,
            ],
            [
                'item_name' => 'Sanggunian\'s Action on veto, if any',
                'signatories' => 'Secretary to the Sanggunian, Presiding Officer',
                'remarks' => json_encode(['Details' => '']),
                'order' => 10,
                'group_letter' => 'i',
                'is_subitem' => false,
            ],
            [
                'item_name' => 'MDC Resolution',
                'signatories' => 'Local Chief Executive',
                'remarks' => json_encode(['Details' => '']),
                'order' => 11,
                'group_letter' => 'j',
                'is_subitem' => true,
            ],
            [
                'item_name' => 'Plantilla',
                'signatories' => '',
                'remarks' => json_encode(['Details' => '']),
                'order' => 12,
                'group_letter' => 'j',
                'is_subitem' => true,
            ],
            [
                'item_name' => 'Step Increment/NOSI/NOSA',
                'signatories' => '',
                'remarks' => json_encode(['Details' => '']),
                'order' => 13,
                'group_letter' => 'j',
                'is_subitem' => true,
            ],
            [
                'item_name' => 'Certification of Not Exceeding PS Cap',
                'signatories' => '',
                'remarks' => json_encode(['Details' => '']),
                'order' => 14,
                'group_letter' => 'j',
                'is_subitem' => true,
            ],
            [
                'item_name' => 'MFC Certification',
                'signatories' => '',
                'remarks' => json_encode(['Details' => '']),
                'order' => 15,
                'group_letter' => 'j',
                'is_subitem' => true,
            ],
            [
                'item_name' => 'Other Documentary Requirements',
                'signatories' => '',
                'remarks' => json_encode(['Details' => '']),
                'order' => 16,
                'group_letter' => 'j',
                'is_subitem' => true,
            ],
        ];

        // Delete existing items for this template
        DocumentChecklistItem::where('template_id', $supplementalBudgetTemplate->id)->delete();

        foreach ($items as $item) {
            DocumentChecklistItem::create([
                'template_id' => $supplementalBudgetTemplate->id,
                'item_name' => $item['item_name'],
                'signatories' => $item['signatories'],
                'remarks' => $item['remarks'],
                'order' => $item['order'],
                'group_letter' => $item['group_letter'],
                'is_subitem' => $item['is_subitem'],
            ]);
        }
    }
}
