<?php

namespace App\Http\Controllers;

use App\Services\GlobalSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The navbar search box (GlobalSearch decides what each user may find)
 */
class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate(['q' => 'nullable|string|max:100']);

        return response()->json(['groups' => GlobalSearch::run($request->user(), (string) $request->input('q'))]);
    }
}
