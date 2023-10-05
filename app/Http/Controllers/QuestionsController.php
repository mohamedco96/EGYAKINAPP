<?php

namespace App\Http\Controllers;

use App\Models\Questions;
use App\Http\Requests\StoreQuestionsRequest;
use App\Http\Requests\UpdateQuestionsRequest;
use Illuminate\Support\Facades\DB;

class QuestionsController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //$Questions = Questions::latest()->paginate(10);
        $Questions = Questions::get();

        if($Questions!=null){
            $response = [
                'value' => true,
                'data' => $Questions
            ];
            return response($response, 200);
        }else {
            $response = [
                'value' => false
            ];
            return response($response, 404);
        }
    }

    //@param \Illuminate\Http\Request $request
    // @return \Illuminate\Http\Response
    public function store(StoreQuestionsRequest $request)
    {
        $Questions = Questions::create($request->all());

        if($Questions!=null){
            $response = [
                'value' => true,
                'data' => $Questions
            ];
            return response($response, 200);
        }else {
            $response = [
                'value' => false,
                'message' => 'No Questions was found'
            ];
            return response($response, 404);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $Questions = Questions::where('section_id', $id)->get();
        if($Questions!=null){
            $response = [
                'value' => true,
                'data' => $Questions
            ];
            return response($response, 200);
        }else {
            $response = [
                'value' => false,
                'message' => 'No Questions was found'
            ];
            return response($response, 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateQuestionsRequest $request, $id)
    {
        $Questions = Questions::find($id)->first();

        if($Questions!=null){
            $Questions->update($request->all());
            $response = [
                'value' => true,
                'data' => $Questions,
                'message' => 'Questions Updated Successfully'
            ];
            return response($response, 200);
        }else {
            $response = [
                'value' => false,
                'message' => 'No Questions was found'
            ];
            return response($response, 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $Questions = Questions::find($id)->first();

        if($Questions!=null){
            DB::table('questions')->where('section_id', $id)->delete();
            $response = [
                'value' => true,
                'message' => 'Questions Deleted Successfully'
            ];
            return response($response, 200);
        }else {
            $response = [
                'value' => false,
                'message' => 'No Questions was found'
            ];
            return response($response, 404);
        }
    }
}
