<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\UserNumber;
use Illuminate\Http\Request;

class UserDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $survey = $user->survey;

        return view('user.dashboard', [
            'survey' => $survey,
            'submissionStatus' => $survey
                ? ($survey->submitted_at ? 'submitted' : 'draft')
                : 'not_started',
            'userNumber' => $user->userNumber,
        ]);
    }

    /**
     * Upsert the graduate's GCash number - same action handles both the
     * first submission and later corrections. Submitting a new number
     * after a reward was already marked "Done" does NOT reset that flag;
     * an admin can revisit it manually from Admin > Integrations if a
     * correction genuinely requires re-sending the reward.
     */
    public function saveNumber(Request $request)
    {
        abort_unless($request->user()->survey?->submitted_at, 403, 'Complete your survey before claiming the reward.');
        $validated = $request->validate([
            'number' => ['required', 'string', 'max:20'],
        ]);

        UserNumber::updateOrCreate(
            ['user_id' => $request->user()->id],
            ['number' => $validated['number']]
        );

        return redirect()
            ->route('user.dashboard')
            ->with('status', 'Thanks! Your GCash number has been saved.');
    }
}
