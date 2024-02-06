<?php

namespace App\Http\Controllers;

use App\Http\Requests\Session\StartSessionRequest;
use App\Http\Requests\SubmitAnswerRequest;
use App\Models\Session;
use App\Services\QuizSessionService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

class QuizSessionController extends Controller
{
    public function __construct(private QuizSessionService $service){}

    public function showSession(int $sessionId): JsonResponse
    {
        return response()->json($this->service->getSession($sessionId));
    }

    public function startSession(StartSessionRequest $request): JsonResponse
    {
        return response()->json($this->service->startSession($request), Response::HTTP_CREATED);
    }

    public function submitAnswer(SubmitAnswerRequest $request, int $sessionId): JsonResponse
    {
        return response()->json($this->service->submitAnswer($sessionId, $request->quote_id, $request->answer_id));
    }

    public function endSession(int $sessionId): array
    {
        return $this->service->endSession($sessionId);
    }

    public function endSessionResults(int $sessionId): JsonResponse
    {
        $session = Session::with('userAnswers')->findOrFail($sessionId);
        $data = $this->service->prepareSessionDataForResponse($session);
        return response()->json($data);
    }
    public function guestUserHistory(): JsonResponse
    {
        return response()->json($this->service->getAllSessionsEndResults());
    }
    public function topScorers(): JsonResponse
    {
        $topScorers = $this->service->getTopScorers();
        return response()->json($topScorers);
    }
}
