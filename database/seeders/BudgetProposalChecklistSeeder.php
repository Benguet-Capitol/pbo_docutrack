<?php

namespace Database\Seeders;

use App\Models\DocumentChecklistTemplate;
use App\Models\DocumentChecklistItem;
use Illuminate\Database\Seeder;

class BudgetProposalChecklistSeeder extends Seeder
{
    /**
     * Run the database seeds for Budget Proposal checklist items
     */
    public function run(): void
    {
        // Create or update Budget Proposal checklist template
        $budgetProposalTemplate = DocumentChecklistTemplate::updateOrCreate(
            ['document_type' => 'Budget Proposals'],
            [
                'description' => 'Documentary checklist for Budget Proposal documents',
                'status' => 'active',
            ]
        );

        // Add checklist items for Budget Proposal (NO SIGNATORIES)
        // Required items: LBP Form 2 (Program Appropriation) and Project Procurement Management Plan
        $budgetProposalItems = [
            [
                'item_name' => 'LBP Form 2 (Program Appropriation & Obligation by Object of Expenditure)',
                'signatories' => null,
                'remarks' => json_encode(['Details' => '']),
                'order' => 1,
                'group_letter' => 'a',
                'is_subitem' => false,
            ],
            [
                'item_name' => 'LBP Form 2 (Special Purpose Appropriations)',
                'signatories' => null,
                'remarks' => json_encode(['Details' => '']),
                'order' => 2,
                'group_letter' => 'b',
                'is_subitem' => false,
            ],
            [
                'item_name' => 'LBP Form 4 (Mandate, Vision & Mission, MFO, PI & Targets)',
                'signatories' => null,
                'remarks' => json_encode(['Details' => '']),
                'order' => 3,
                'group_letter' => 'c',
                'is_subitem' => false,
            ],
            [
                'item_name' => 'LBP Form 3 (Personnel Schedule)',
                'signatories' => null,
                'remarks' => json_encode(['Details' => '']),
                'order' => 4,
                'group_letter' => 'd',
                'is_subitem' => false,
            ],
            [
                'item_name' => 'Project Procurement Management Plan',
                'signatories' => null,
                'remarks' => json_encode(['Details' => '']),
                'order' => 5,
                'group_letter' => 'e',
                'is_subitem' => false,
            ],
        ];

        // Delete existing items for Budget Proposal template
        DocumentChecklistItem::where('template_id', $budgetProposalTemplate->id)->delete();

        foreach ($budgetProposalItems as $item) {
            DocumentChecklistItem::create([
                'template_id' => $budgetProposalTemplate->id,
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
