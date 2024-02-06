<?php

namespace App\Services;

use App\Http\Requests\Session\StartSessionRequest;
use App\Http\Resources\QuoteResource;
use App\Http\Resources\SessionResource;
use App\Http\Resources\UserResource;
use App\Models\Answer;
use App\Models\Quote;
use App\Models\Session;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class QuizSessionService
{
    public function getSession($sessionId): array
    {
        $session = Session::with('userAnswers')->findOrFail($sessionId);
        $quotes = $this->getQuotesByType($session->mode);
        return [
            'session' => new SessionResource($session),
            'quotes' => QuoteResource::collection($quotes),
        ];
    }

    public function startSession(StartSessionRequest $request): array
    {
        $mode = $request->input('mode', 'binary');
        $user = Auth::user();

        $activeSessions = $user->sessions()->whereNull('ended_at')->get();

        foreach ($activeSessions as $activeSession) {
            $activeSession->ended_at = now();
            $activeSession->save();
        }

        $quotes = $this->getQuotesByType($mode);
        $session = $this->createUserSession($user, $mode, count($quotes));

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
        $this->authorizeSession($sessionId);
        $this->validateSessionState($sessionId);
        $this->checkAlreadyAnswered($sessionId, $quoteId);
        $isCorrect = $this->recordAnswer($sessionId, $quoteId, $answerId);
        $message = $this->constructAnswerResponseMessage($quoteId, $isCorrect, $answerId);
        return [
            'correct' => $isCorrect,
            'message' => $message,
        ];
    }

    public function endSession(int $sessionId): array
    {
        $session = $this->endAndUpdateSession($sessionId);
        return $this->prepareSessionDataForResponse($session);
    }

    private function getQuotesByType($type): Collection|array
    {
        return Quote::with('answers')->where('type', $type)->get();
    }

    private function createUserSession($user, $mode, $totalQuestions): Session
    {
        return $user->sessions()->create([
            'mode' => $mode,
            'started_at' => Carbon::now(),
            'total_questions' => $totalQuestions,
        ]);
    }

    /**
     * @throws Exception
     */
    private function authorizeSession(int $sessionId): void
    {
        $session = Session::findOrFail($sessionId);
        if ($session->user_id != Auth::id()) {
            throw new Exception('Unauthorized access to the session.', Response::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * @throws Exception
     */
    private function validateSessionState(int $sessionId): void
    {
        $session = Session::findOrFail($sessionId);
        if ($session->ended_at) {
            throw new Exception('This session has already ended.', 400);
        }
    }

    /**
     * @throws Exception
     */
    private function checkAlreadyAnswered($sessionId, $quoteId): void
    {
        $session = Session::findOrFail($sessionId);
        $alreadyAnswered = $session->userAnswers()->where('quote_id', $quoteId)->exists();
        if ($alreadyAnswered) {
            throw new Exception('You have already answered this question.', Response::HTTP_BAD_REQUEST);
        }
    }

    private function recordAnswer(int $sessionId, int $quoteId, int $answerId)
    {
        $answer = Answer::where('id', $answerId)->where('quote_id', $quoteId)->firstOrFail();
        $session = Session::findOrFail($sessionId);
        $session->userAnswers()->create([
            'quote_id' => $quoteId,
            'answer_id' => $answerId,
            'is_correct' => $answer->is_correct,
        ]);
        return $answer->is_correct;
    }

    private function constructAnswerResponseMessage(int $quoteId, int $isCorrect, int $answerId): string
    {
        $correctAnswer = Answer::where('quote_id', $quoteId)->where('is_correct', true)->first();
        $userAnswer = Answer::findOrFail($answerId);

        if (!$userAnswer || !$correctAnswer) {
            throw new Exception('Answer not found.', Response::HTTP_NOT_FOUND);
        }

        return $isCorrect
            ? 'Correct! The right answer is ' . $userAnswer->answer
            : 'Sorry, you are wrong! The right answer is ' . $correctAnswer->answer;
    }

    private function endAndUpdateSession($sessionId): Builder|array|Collection|Model
    {
        $session = Session::with('userAnswers')->findOrFail($sessionId);
        if (!$session->ended_at) {
            $session->ended_at = Carbon::now();
            $session->score = $session->userAnswers->where('is_correct', true)->count();
            $session->save();
        }
        return $session;
    }

    public function prepareSessionDataForResponse(Session $session): array
    {
        $endedAt = Carbon::parse($session->ended_at);
        $startedAt = Carbon::parse($session->started_at);

        $totalTime = $session->ended_at
            ? sprintf('%d minutes %d seconds',
                $endedAt->diffInMinutes($startedAt),
                $endedAt->diffInSeconds($startedAt) % 60)
            : 'Session not ended';

        $unansweredQuestions = $session->total_questions - $session->userAnswers->count();
        $sessionData = new SessionResource($session);
        $sessionDataArray = $sessionData->toArray(request());
        $sessionDataArray['total_time'] = $totalTime;
        $sessionDataArray['unanswered_questions'] = $unansweredQuestions;

        return $sessionDataArray;
    }

    public function getAllSessionsEndResults(): array
    {
        $sessions = Session::with('user')->get();
        $results = [];
        foreach ($sessions as $session) {
            $sessionData = $this->prepareSessionDataForResponse($session);
            $sessionData['user'] = new UserResource($session->user);
            $results[] = $sessionData;
        }
        return $results;
    }

    public function getTopScorers(): array
    {
        $sessions = Session::with('user')
            ->whereNotNull('score') // Ensure the session is completed
            ->get()
            ->map(function ($session) {
                // Convert total_time to a more readable format
                $endedAt = Carbon::parse($session->ended_at);
                $startedAt = Carbon::parse($session->started_at);

                $totalTime = $session->ended_at
                    ? sprintf('%d minutes %d seconds',
                        $endedAt->diffInMinutes($startedAt),
                        $endedAt->diffInSeconds($startedAt) % 60)
                    : 'Session not ended';

                return [
                    'first_name' => $session->user->first_name,
                    'last_name' => $session->user->last_name,
                    'email' => $session->user->email,
                    'total_score' => $session->score,
                    'total_time' => $totalTime,
                ];
            })
            ->sortByDesc('total_score')
            ->values() // Reset the keys after sorting
            ->all();

        return $sessions;
    }
}
