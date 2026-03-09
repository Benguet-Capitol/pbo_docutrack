<?php

namespace Database\Seeders;

use App\Models\DocumentChecklistTemplate;
use App\Models\DocumentChecklistItem;
use Illuminate\Database\Seeder;

class DocumentChecklistSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create or update Annual Budget checklist template
        $annualBudgetTemplate = DocumentChecklistTemplate::updateOrCreate(
            ['document_type' => 'Annual Budget'],
            [
                'description' => 'Documentary checklist for Annual Budget documents',
                'status' => 'active',
            ]
        );

        // Add checklist items for Annual Budget
        $items = [
            [
                'item_name' => 'Transmittal Letter',
                'signatories' => 'Secretary to the Sanggunian',
                'remarks' => json_encode(['Letter dated' => '']),
                'order' => 1,
                'group_letter' => 'a',
                'is_subitem' => false,
            ],
            [
                'item_name' => 'Budget Message',
                'signatories' => 'Local Chief Executive',
                'remarks' => json_encode(['Details' => '']),
                'order' => 2,
                'group_letter' => 'b',
                'is_subitem' => false,
            ],
            [
                'item_name' => 'Appropriation Ordinance',
                'signatories' => 'Secretary to the Sanggunian, Presiding Officer, Local Chief Executive',
                'remarks' => json_encode(['AO No.' => '', 'Date Approved' => '', 'Amount' => '']),
                'order' => 3,
                'group_letter' => 'c',
                'is_subitem' => false,
            ],
            [
                'item_name' => 'Annual Investment Program',
                'signatories' => 'Local PDC, Local Budget Officer, Local Chief Executive',
                'remarks' => json_encode(['Date Attested' => '']),
                'order' => 4,
                'group_letter' => 'c',
                'is_subitem' => true,
            ],
            [
                'item_name' => 'Resolution approving the Annual Investment Program',
                'signatories' => 'Secretary to the Sanggunian, Presiding Officer, Local Chief Executive',
                'remarks' => json_encode(['Resolution No.' => '', 'Date Approved' => '']),
                'order' => 5,
                'group_letter' => 'c',
                'is_subitem' => true,
            ],
            [
                'item_name' => 'Annual Investment Program to include Annexes',
                'signatories' => 'Local Chief Executive, Local PDC, Local Budget Officer',
                'remarks' => json_encode(['Date Approved' => '']),
                'order' => 6,
                'group_letter' => 'c',
                'is_subitem' => true,
            ],
            [
                'item_name' => 'E-1: GAD Plan & Budget',
                'signatories' => '',
                'remarks' => json_encode(['Details' => '']),
                'order' => 6.1,
                'group_letter' => 'c',
                'is_subitem' => true,
            ],
            [
                'item_name' => 'E-2: LDRRM Plan',
                'signatories' => '',
                'remarks' => json_encode(['Details' => '']),
                'order' => 6.2,
                'group_letter' => 'c',
                'is_subitem' => true,
            ],
            [
                'item_name' => 'E-3: HIV & AIDS Agenda',
                'signatories' => '',
                'remarks' => json_encode(['Details' => '']),
                'order' => 6.3,
                'group_letter' => 'c',
                'is_subitem' => true,
            ],
            [
                'item_name' => 'E-4: CCC Action Plan',
                'signatories' => '',
                'remarks' => json_encode(['Details' => '']),
                'order' => 6.4,
                'group_letter' => 'c',
                'is_subitem' => true,
            ],
            [
                'item_name' => 'E-5: Disaster Risk Reduction & Management',
                'signatories' => '',
                'remarks' => json_encode(['Details' => '']),
                'order' => 6.5,
                'group_letter' => 'c',
                'is_subitem' => true,
            ],
            [
                'item_name' => 'E-6: Anti-Poverty Program',
                'signatories' => '',
                'remarks' => json_encode(['Details' => '']),
                'order' => 6.6,
                'group_letter' => 'c',
                'is_subitem' => true,
            ],
            [
                'item_name' => 'E-7: Environmental Management & Protection',
                'signatories' => '',
                'remarks' => json_encode(['Details' => '']),
                'order' => 6.7,
                'group_letter' => 'c',
                'is_subitem' => true,
            ],
            [
                'item_name' => 'E-8: Support to Peace and Reconciliation',
                'signatories' => '',
                'remarks' => json_encode(['Details' => '']),
                'order' => 6.8,
                'group_letter' => 'c',
                'is_subitem' => true,
            ],
            [
                'item_name' => 'Annual Operating Budget of each Local Economic Enterprise (LEE) if any, and the AO for newly-created LEE',
                'signatories' => 'Department Head, Local Budget Officer, Local Chief Executive',
                'remarks' => json_encode(['Details' => '']),
                'order' => 7,
                'group_letter' => 'd',
                'is_subitem' => false,
            ],
            [
                'item_name' => 'Veto Message, if any',
                'signatories' => 'Local Chief Executive',
                'remarks' => json_encode(['Details' => '']),
                'order' => 8,
                'group_letter' => 'e',
                'is_subitem' => false,
            ],
            [
                'item_name' => 'Sanggunian\'s Action on veto, if any',
                'signatories' => 'Secretary to the Sanggunian, Presiding Officer',
                'remarks' => json_encode(['Details' => '']),
                'order' => 9,
                'group_letter' => 'f',
                'is_subitem' => false,
            ],
            [
                'item_name' => 'MDC Resolution',
                'signatories' => 'Local Chief Executive',
                'remarks' => json_encode(['Date Approved' => '']),
                'order' => 10,
                'group_letter' => 'g',
                'is_subitem' => true,
            ],
            [
                'item_name' => 'Plantilla',
                'signatories' => '',
                'remarks' => json_encode(['Details' => '']),
                'order' => 11,
                'group_letter' => 'g',
                'is_subitem' => true,
            ],
            [
                'item_name' => 'Step Increment/NOSI/NOSA',
                'signatories' => '',
                'remarks' => json_encode(['Details' => '']),
                'order' => 12,
                'group_letter' => 'g',
                'is_subitem' => true,
            ],
            [
                'item_name' => 'Certification of Not Exceeding PS Cap',
                'signatories' => '',
                'remarks' => json_encode(['Details' => '']),
                'order' => 13,
                'group_letter' => 'g',
                'is_subitem' => true,
            ],
            [
                'item_name' => 'MFC Certification',
                'signatories' => '',
                'remarks' => json_encode(['Details' => '']),
                'order' => 14,
                'group_letter' => 'g',
                'is_subitem' => true,
            ],
            [
                'item_name' => 'Other Documentary Requirements',
                'signatories' => '',
                'remarks' => json_encode(['Details' => '']),
                'order' => 15,
                'group_letter' => 'g',
                'is_subitem' => true,
            ],
        ];

        // Delete existing items for this template
        DocumentChecklistItem::where('template_id', $annualBudgetTemplate->id)->delete();

        foreach ($items as $item) {
            DocumentChecklistItem::create([
                'template_id' => $annualBudgetTemplate->id,
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
