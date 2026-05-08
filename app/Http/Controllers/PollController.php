<?php

namespace App\Http\Controllers;

use App\Models\Poll;
use App\Models\PollOption;
use App\Models\PollVote;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request; // If you have a separate PollOption model
use Illuminate\Support\Facades\Auth;

class PollController extends Controller
{
    public function voteUnvote(Request $request, $pollId)
    {
        $request->validate([
            'option_id' => 'required|exists:poll_options,id',
        ]);

        $poll = Poll::findOrFail($pollId);
        $userId = Auth::id();

        // Check if the user has already voted for this option
        $existingVote = PollVote::where('poll_id', $pollId)
            ->where('poll_option_id', $request->option_id)
            ->where('doctor_id', $userId)
            ->first();

        if ($existingVote) {
            // Unvote (remove vote)
            $existingVote->delete();

            return response()->json(['message' => 'Vote removed successfully']);
        }

        // If multiple choices are not allowed, remove previous votes
        if (! $poll->allow_multiple_choice) {
            PollVote::where('poll_id', $pollId)->where('doctor_id', $userId)->delete();
        }

        // Save new vote
        PollVote::create([
            'poll_id' => $pollId,
            'poll_option_id' => $request->option_id,
            'doctor_id' => $userId,
        ]);

        return response()->json(['message' => 'Vote submitted successfully']);
    }

    public function getVotersByOption($pollId, $optionId)
    {
        try {
            // Validate that the poll and option exist
            $pollOption = PollOption::where('id', $optionId)->where('poll_id', $pollId)->firstOrFail();

            // Fetch doctors who voted for this option
            $voters = PollVote::where('poll_option_id', $optionId)
                ->with('doctor:id,name,lname,image,email,syndicate_card,isSyndicateCardRequired')
                ->get()
                ->pluck('doctor'); // Extract doctor directly

            return response()->json([
                'value' => true,
                'data' => $voters,
                'message' => 'Voters retrieved successfully',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'value' => false,
                'data' => [],
                'message' => 'Poll option not found',
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'value' => false,
                'data' => [],
                'message' => 'An error occurred while retrieving voters',
            ], 500);
        }
    }

    public function addPollOption(Request $request, $pollId)
    {
        try {
            $request->validate([
                'option_text' => 'required|string|max:255',
            ]);

            $poll = Poll::findOrFail($pollId);

            // Check if the poll allows adding new options
            if (! $poll->allow_add_options) {
                return response()->json([
                    'value' => false,
                    'message' => 'Adding new options is not allowed for this poll',
                ], 403);
            }

            // Check if option already exists (to avoid duplicates)
            $existingOption = PollOption::where('poll_id', $pollId)
                ->where('option_text', $request->option_text)
                ->exists();

            if ($existingOption) {
                return response()->json([
                    'value' => false,
                    'message' => 'This option already exists for the poll',
                ], 400);
            }

            // Create new poll option
            $option = PollOption::create([
                'poll_id' => $pollId,
                'option_text' => $request->option_text,
            ]);

            return response()->json([
                'value' => true,
                'data' => $option,
                'message' => 'Option added successfully',
            ], 201);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'value' => false,
                'data' => [],
                'message' => 'Poll not found',
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'value' => false,
                'data' => [],
                'message' => 'An error occurred while adding the option',
            ], 500);
        }
    }
}
