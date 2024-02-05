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
        $data = $this->service->getSession($sessionId);
        return response()->json($data);
    }

    public function startSession(StartSessionRequest $request): JsonResponse
    {
        $data = $this->service->startSession($request->input('mode', 'binary'));
        return response()->json($data, Response::HTTP_CREATED);
    }

    public function submitAnswer(SubmitAnswerRequest $request, int $sessionId): JsonResponse
    {
        try {
            $result = $this->service->submitAnswer($sessionId, $request->quote_id, $request->answer_id);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()],
                $e->getCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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
}
