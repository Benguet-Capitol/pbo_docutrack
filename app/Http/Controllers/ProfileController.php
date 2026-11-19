<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\V1\SharedFilingController;
use App\Http\Controllers\Api\V1\SharedLeaveController;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Employee;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * The signed-in user's profile, laid out as on pbo-registryV2: their HRIS employee (as on an ID card), their leaves and
 * filings of a year (PBO staff file and edit their own leaves, pass slips, time slips and tardiness here, with the same
 * checks as the Filing API), their name and username, and their password.
 */
class ProfileController extends Controller
{
    private const LABELS = ['leaves' => 'Leave', 'pass-slips' => 'Pass slip', 'time-slips' => 'Time slip', 'tardiness' => 'Tardiness/undertime'];

    public function edit(Request $request): Response
    {
        $year = (int) ($request->validate(['year' => 'nullable|integer|min:2000|max:2100'])['year'] ?? now()->year);
        $filer = SharedFilingController::filer($request->user()->employee_id);

        return Inertia::render('Profile/Edit', [
            'status' => session('status'),
            // PBO staff only; deferred so the profile opens at once
            'leaves' => $filer ? Inertia::defer(fn () => ['summary' => app(SharedLeaveController::class)->data($filer, $year)], 'filings') : null,
            'filings' => $filer ? Inertia::defer(fn () => ['records' => app(SharedFilingController::class)->records($filer, $year)], 'filings') : null,
            // Heads the leaves and filings panel: whose records they are
            'employee' => $filer?->only(['employee_id', 'name', 'position_title', 'parenthetical_title']),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        // Logged in Activity Logs by the User model (LogsActivity)
        $request->user()->fill($request->validated())->save();

        return Redirect::route('profile.edit')->with('status', 'Your profile has been updated.');
    }

    /** No self-delete: accounts are removed only by administrators (Users) */
    public function destroy(Request $request): RedirectResponse
    {
        return back()->withErrors([
            'profile' => 'Account deletion is handled by your administrator. Contact your administrator to delete your account.',
        ]);
    }

    /** Files a new leave / slip ($id null) or edits one of the user's own, through the module's own checks (as the Filing API does) */
    public function saveFiling(Request $request, SharedFilingController $filing, string $module, ?string $id = null): RedirectResponse
    {
        $employee = $this->filer($request);
        $values = $request->validate(['values' => 'required|array'])['values'];
        $label = self::LABELS[$module];

        try {
            $response = $filing->file($employee, $module, $id, $values, $request->user());
        } catch (HttpResponseException $e) {
            $response = $e->getResponse();
        } catch (ModelNotFoundException) {
            return back()->withErrors(['filing' => "{$label} was not found."]);
        } catch (HttpExceptionInterface $e) {
            return back()->withErrors(['filing' => $e->getMessage() ?: "{$label} could not be saved."]);
        }

        $body = $response instanceof JsonResponse ? $response->getData(true) : [];
        if ($response->getStatusCode() === 422) {
            return back()->withErrors(collect($body['errors'] ?? [])->map(fn ($messages) => is_array($messages) ? $messages[0] : $messages)->all()
                ?: ['filing' => $body['message'] ?? "{$label} could not be saved."]);
        }
        if ($response->getStatusCode() >= 500 && $response->getStatusCode() !== 503) {
            report(new \RuntimeException("Profile filing ({$module}) failed: " . ($body['error'] ?? $body['message'] ?? 'HTTP ' . $response->getStatusCode())));

            return back()->withErrors(['filing' => "{$label} could not be saved. Please tell the system administrator."]);
        }
        if ($response->getStatusCode() >= 400) {
            return back()->withErrors(['filing' => $body['error'] ?? $body['message'] ?? "{$label} could not be saved."]);
        }

        $controlNo = $body['control_no'] ?? $body['data']['control_no'] ?? $body[0]['control_no'] ?? $id;

        // Back to the same year, on the record's tab (the panel reloads from scratch, so the tab rides in the URL)
        parse_str((string) parse_url(url()->previous(), PHP_URL_QUERY), $query);

        return redirect()->route('profile.edit', array_filter(['year' => $query['year'] ?? null, 'tab' => $module]))
            ->with('status', trim("{$label} {$controlNo}") . ' has been ' . ($id === null ? 'filed.' : 'updated.'));
    }

    /** A short-lived link to the record's print preview, for the View/Print frame */
    public function filingPrint(Request $request, SharedFilingController $filing, string $module, string $id): JsonResponse
    {
        try {
            return response()->json(['url' => $filing->printPath($this->filer($request), $module, $id)]);
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'That filing was not found.'], 404);
        }
    }

    private function filer(Request $request): Employee
    {
        $employee = SharedFilingController::filer($request->user()->employee_id);
        abort_unless($employee, 403, 'Only Provincial Budget Office staff linked to an employee can file here.');

        return $employee;
    }
}
