<?php

namespace App\Services;

use App\Http\Resources\QuoteResource;
use App\Http\Resources\SessionResource;
use App\Models\Answer;
use App\Models\Quote;
use App\Models\Session;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class QuizSessionService
{
    public function getSessions(): AnonymousResourceCollection
    {
        $user = Auth::user();
        $sessions = $user->sessions()->with('userAnswers')->get();
        return SessionResource::collection($sessions);
    }

    public function startSession($mode): array
    {
        $user = Auth::user();
        $quotes = Quote::with('answers')->where('type', $mode)->inRandomOrder()->limit(10)->get();
        $session = $user->sessions()->create([
            'mode' => $mode,
            'started_at' => Carbon::now(),
            'total_questions' => $quotes->count(),
        ]);
        return [
            'session' => new SessionResource($session),
            'quotes' => QuoteResource::collection($quotes),
        ];
    }

    /**
     * @throws Exception
     */
    public function submitAnswer(int $sessionId, int $quoteId, int $answerId): array
    {
        $session = Session::findOrFail($sessionId);
        $quote = Quote::with('answers')->findOrFail($quoteId);

        if ($session->user_id != Auth::id()) {
            throw new \Exception('Unauthorized access to the session.', Response::HTTP_UNAUTHORIZED);
        }

        if ($session->ended_at) {
            throw new \Exception('This session has already ended.', 400);
        }

        $alreadyAnswered = $session->userAnswers()->where('quote_id', $quoteId)->exists();
        if ($alreadyAnswered) {
            throw new \Exception('You have already answered this question.', Response::HTTP_BAD_REQUEST);
        }

        $answer = Answer::where('id', $answerId)->where('quote_id', $quoteId)->firstOrFail();
        $isCorrect = $answer->is_correct;

        // Record the answer attempt
        $session->userAnswers()->create([
            'quote_id' => $quoteId,
            'answer_id' => $answerId,
            'is_correct' => $isCorrect,
        ]);

        $correctAnswer = $quote->answers()->where('is_correct', true)->first();

        // Construct message based on correctness of the user's answer
        $message = $isCorrect
            ? 'Correct! The right answer is ' . $answer->answer
            : 'Sorry, you are wrong! The right answer is ' . $correctAnswer->answer;

        return [
            'correct' => $isCorrect,
            'message' => $message,
        ];
    }


    public function endSession(int $sessionId): array
    {
        $session = Session::with('userAnswers')->findOrFail($sessionId);
        if ($session->ended_at) {
            return [
                'message' => 'This session has already been ended.',
                'session' => new SessionResource($session)
            ];
        }
        $session->update([
            'ended_at' => Carbon::now(),
            'score' => $session->userAnswers->where('is_correct', true)->count(),
        ]);

        $totalTime = sprintf('%d minutes %d seconds',
            $session->ended_at->diffInMinutes($session->started_at),
            $session->ended_at->diffInSeconds($session->started_at) % 60);

        $sessionData = new SessionResource($session);
        $sessionDataArray = $sessionData->toArray(request());
        $sessionDataArray['total_time'] = $totalTime;

        return $sessionDataArray;
    }
}
