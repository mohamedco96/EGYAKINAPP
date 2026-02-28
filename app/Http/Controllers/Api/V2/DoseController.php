<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\DoseController as V1DoseController;
use App\Modules\Doses\Requests\StoreDoseRequest;
use App\Modules\Doses\Requests\UpdateDoseRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DoseController extends Controller
{
    protected $doseController;

    public function __construct(V1DoseController $doseController)
    {
        $this->doseController = $doseController;
    }

    public function index()
    {
        return $this->doseController->index();
    }

    public function store(StoreDoseRequest $request)
    {
        $user = Auth::user();

        if ($user->isSyndicateCardRequired !== 'Verified') {
            return response()->json([
                'value' => false,
                'message' => 'Unauthorized: Your syndicate card has not been verified.',
            ], 403);
        }

        return $this->doseController->store($request);
    }

    public function show($id)
    {
        return $this->doseController->show($id);
    }

    public function update(UpdateDoseRequest $request, $id)
    {
        return $this->doseController->update($request, $id);
    }

    public function destroy($id)
    {
        return $this->doseController->destroy($id);
    }

    public function doseSearch(Request $request, $query)
    {
        return $this->doseController->doseSearch($request, $query);
    }
}
