<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\DocumentChecklistTemplate;
use App\Models\DocumentChecklistItem;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create or get the Budget Proposal template
        $budgetProposalTemplate = DocumentChecklistTemplate::firstOrCreate(
            ['document_type' => 'Budget Proposal'],
            [
                'description' => 'Documentary checklist for Budget Proposal documents',
                'status' => 'active',
            ]
        );

        // Only add items if template is newly created
        if ($budgetProposalTemplate->wasRecentlyCreated) {
            $items = [
                [
                    'template_id' => $budgetProposalTemplate->id,
                    'item_name' => 'LBP Form 2 (Program Appropriation & Obligation by Object of Expenditure)',
                    'signatories' => null,
                    'remarks' => json_encode(['Details' => '']),
                    'order' => 1,
                    'group_letter' => 'a',
                    'is_subitem' => false,
                ],
                [
                    'template_id' => $budgetProposalTemplate->id,
                    'item_name' => 'LBP Form 2 (Special Purpose Appropriations)',
                    'signatories' => null,
                    'remarks' => json_encode(['Details' => '']),
                    'order' => 2,
                    'group_letter' => 'b',
                    'is_subitem' => false,
                ],
                [
                    'template_id' => $budgetProposalTemplate->id,
                    'item_name' => 'LBP Form 4 (Mandate, Vision & Mission, MFO, PI & Targets)',
                    'signatories' => null,
                    'remarks' => json_encode(['Details' => '']),
                    'order' => 3,
                    'group_letter' => 'c',
                    'is_subitem' => false,
                ],
                [
                    'template_id' => $budgetProposalTemplate->id,
                    'item_name' => 'LBP Form 3 (Personnel Schedule)',
                    'signatories' => null,
                    'remarks' => json_encode(['Details' => '']),
                    'order' => 4,
                    'group_letter' => 'd',
                    'is_subitem' => false,
                ],
                [
                    'template_id' => $budgetProposalTemplate->id,
                    'item_name' => 'Project Procurement Management Plan',
                    'signatories' => null,
                    'remarks' => json_encode(['Details' => '']),
                    'order' => 5,
                    'group_letter' => 'e',
                    'is_subitem' => false,
                ],
            ];

            foreach ($items as $item) {
                DocumentChecklistItem::create($item);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Delete Budget Proposal checklist items and template
        $budgetProposalTemplate = DocumentChecklistTemplate::where('document_type', 'Budget Proposal')->first();
        if ($budgetProposalTemplate) {
            DocumentChecklistItem::where('template_id', $budgetProposalTemplate->id)->delete();
            $budgetProposalTemplate->delete();
        }
    }
};
