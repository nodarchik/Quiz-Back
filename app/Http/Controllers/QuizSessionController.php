<?php

namespace App\Http\Controllers;

use App\Http\Requests\Session\StartSessionRequest;
use App\Http\Requests\SubmitAnswerRequest;
use App\Http\Resources\QuoteResource;
use App\Http\Resources\SessionResource;
use App\Models\Answer;
use App\Models\Quote;
use App\Models\Session;
use App\Models\UserAnswer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;
class QuizSessionController extends Controller
{
    /**
     * Get all sessions for the authenticated user
     *
     * @return JsonResponse
     */
    public function getSessions(): JsonResponse
    {
        $user = Auth::user();

        $sessions = $user->sessions()->with('userAnswers')->get();

        $sessionsResource = SessionResource::collection($sessions);

        return response()->json([
            'sessions' => $sessionsResource,
            'message' => 'Retrieved sessions successfully.',
        ]);
    }

    /**
     * Start a new quiz session
     *
     * @param StartSessionRequest $request
     * @return JsonResponse
     */
    public function startSession(StartSessionRequest $request): JsonResponse
    {
        $user = Auth::user();
        $mode = $request->input('mode', 'binary');

        if (!in_array($mode, ['binary', 'multiple_choice'])) {
            return response()->json(['message' => 'Invalid mode specified.'], Response::HTTP_BAD_REQUEST);
        }

        $quotes = Quote::with('answers')->where('type', $mode)->inRandomOrder()->limit(10)->get();
        $quotesResource = QuoteResource::collection($quotes);

        $session = $user->sessions()->create([
            'mode' => $mode,
            'started_at' => Carbon::now(),
        ]);

        $sessionResource = new SessionResource($session);

        return response()->json([
            'session' => $sessionResource,
            'quotes' => $quotesResource,
            'message' => 'Quiz session started successfully.',
        ]);
    }

    /**
     * Submit an answer to a question
     *
     * @param SubmitAnswerRequest $request
     * @param Session $session
     * @return JsonResponse
     */
    public function submitAnswer(SubmitAnswerRequest $request, Session $session): JsonResponse
    {
        // Ensure the session belongs to the current authenticated user
        if ($session->user_id != Auth::id()) {
            return response()->json(['message' => 'Unauthorized access to the session.'], 403);
        }

        $quoteId = $request->quote_id;
        $answerId = $request->answer_id;
        $quote = Quote::findOrFail($quoteId);
        // Retrieve the answer from the database and ensure it belongs to the correct quote
        $answer = Answer::where('id', $answerId)->where('quote_id', $quoteId)->firstOrFail();

        // Check if the answer is correct
        $isCorrect = $answer->is_correct;

        // Record the answer attempt
        $session->userAnswers()->create([
            'quote_id' => $quoteId,
            'answer_id' => $answerId,
            'is_correct' => $isCorrect,
        ]);

        // Respond with whether the answer was correct
        return response()->json([
            'correct' => $isCorrect,
            'message' => $isCorrect ? 'Correct! The right answer is '. $answer->answer
                : 'Sorry, you are wrong! The right answer is '
                . $quote->answers()->where('is_correct', true)->first()->answer,
        ]);
    }


    /**
     * End the current quiz session
     *
     * @param $sessionId
     * @return JsonResponse
     */
    public function endSession($sessionId): JsonResponse
    {
        $session = Session::with('userAnswers')->findOrFail($sessionId);

        $score = $session->userAnswers->where('is_correct', true)->count();

        $session->update([
            'ended_at' => Carbon::now(),
            'score' => $score,
        ]);

        return response()->json([
            'message' => 'Quiz session ended.',
            'score' => $score,
            'mode' => $session->mode,
            'total_time' => sprintf('%d minutes %d seconds', $session->ended_at->diffInMinutes($session->started_at), $session->ended_at->diffInSeconds($session->started_at) % 60),            'started_at' => $session->started_at,
            'ended_at' => $session->ended_at,
        ]);
    }
}
