<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnswerRequest;
use App\Http\Resources\AnswerResource;
use App\Models\Answer;
use App\Models\Quote;
use App\Services\AnswerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class AnswerController extends Controller
{
    public function __construct(private AnswerService $answerService){}
    /**
     * Display a listing of the resource.
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $answers = Answer::all();
        return AnswerResource::collection($answers);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param AnswerRequest $request
     * @return JsonResponse|AnswerResource
     */
    public function store(AnswerRequest $request): JsonResponse|AnswerResource
    {
        return $this->answerService->storeAnswer($request);
    }

    /**
     * Display the specified resource.
     * @param int $quoteId
     * @return AnonymousResourceCollection
     */
    public function show(int $quoteId): AnonymousResourceCollection
    {
        $answers = Answer::where('quote_id', $quoteId)->get();
        return AnswerResource::collection($answers);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param AnswerRequest $request
     * @param Answer $answer
     * @return JsonResponse|AnswerResource
     */
    public function update(AnswerRequest $request, Answer $answer): JsonResponse|AnswerResource
    {
        return $this->answerService->updateAnswer($request, $answer);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Answer $answer
     * @return JsonResponse
     */
    public function destroy(Answer $answer): JsonResponse
    {
        $answer->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
