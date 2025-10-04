<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PollController as BasePollController;
use Illuminate\Http\Request;

class PollController extends Controller
{
    protected $pollController;

    public function __construct(BasePollController $pollController)
    {
        $this->pollController = $pollController;
    }

    public function voteUnvote(Request $request, $pollId)
    {
        return $this->pollController->voteUnvote($request, $pollId);
    }

    public function getVotersByOption($pollId, $optionId)
    {
        return $this->pollController->getVotersByOption($pollId, $optionId);
    }

    public function addPollOption(Request $request, $pollId)
    {
        return $this->pollController->addPollOption($request, $pollId);
    }
}
