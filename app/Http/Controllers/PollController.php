<?php

namespace App\Http\Controllers;
use App\Models\FeedPost;
use App\Models\FeedPostComment;
use App\Models\FeedPostCommentLike;
use App\Models\FeedPostLike;
use App\Models\FeedSaveLike;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\MainController;
use App\Models\Hashtag;
use App\Models\Group;
use App\Models\AppNotification;
use App\Models\User;
use App\Models\Poll;
use App\Models\PollOption; // If you have a separate PollOption model
use App\Models\PollVote;

class PollController extends Controller
{
    public function voteUnvote(Request $request, $pollId)
    {
        $request->validate([
            'option_id' => 'required|array', // Ensure option_id is an array
            'option_id.*' => 'exists:poll_options,id', // Validate each option in the array
        ]);
    
        $poll = Poll::findOrFail($pollId);
        $userId = Auth::id();
    
        foreach ($request->option_id as $optionId) {
            // Check if the user has already voted for this option
            $existingVote = PollVote::where('poll_id', $pollId)
                ->where('poll_option_id', $optionId)
                ->where('doctor_id', $userId)
                ->first();
    
            if ($existingVote) {
                // Unvote (remove vote)
                $existingVote->delete();
            } else {
                // If multiple choices are not allowed, remove previous votes before adding a new one
                if (!$poll->allow_multiple_choice) {
                    PollVote::where('poll_id', $pollId)->where('doctor_id', $userId)->delete();
                }
    
                // Save new vote
                PollVote::create([
                    'poll_id' => $pollId,
                    'poll_option_id' => $optionId,
                    'doctor_id' => $userId,
                ]);
            }
        }
    
        return response()->json(['message' => 'Vote updated successfully']);
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
                'message' => 'Voters retrieved successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'value' => false,
                'data' => [],
                'message' => 'Poll option not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'value' => false,
                'data' => [],
                'message' => 'An error occurred while retrieving voters'
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
        if (!$poll->allow_add_options) {
            return response()->json([
                'value' => false,
                'message' => 'Adding new options is not allowed for this poll'
            ], 403);
        }

        // Check if option already exists (to avoid duplicates)
        $existingOption = PollOption::where('poll_id', $pollId)
            ->where('option_text', $request->option_text)
            ->exists();

        if ($existingOption) {
            return response()->json([
                'value' => false,
                'message' => 'This option already exists for the poll'
            ], 400);
        }

        // Create new poll option
        $option = PollOption::create([
            'poll_id' => $pollId,
            'option_text' => $request->option_text
        ]);

        return response()->json([
            'value' => true,
            'data' => $option,
            'message' => 'Option added successfully'
        ], 201);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'value' => false,
            'data' => [],
            'message' => 'Poll not found'
        ], 404);
    } catch (\Exception $e) {
        return response()->json([
            'value' => false,
            'data' => [],
            'message' => 'An error occurred while adding the option'
        ], 500);
    }
}

}
