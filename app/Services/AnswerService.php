<?php

namespace App\Services;

use App\Http\Requests\AnswerRequest;
use App\Http\Resources\AnswerResource;
use App\Models\Answer;
use App\Models\Quote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class AnswerService
{
    public function storeAnswer(AnswerRequest $request): JsonResponse|AnswerResource
    {
        $quote = Quote::findOrFail($request->quote_id);
        if ($request->is_correct && $quote->answers()->where('is_correct', true)->exists()) {
            return response()->
            json(['message' => 'A correct answer already exists for this quote.'], Response::HTTP_BAD_REQUEST);
        }
        return match ($quote->type) {
            'binary' => $this->handleBinaryAnswer($quote, $request),
            'multiple_choice' => $this->handleMultipleChoiceAnswer($quote, $request),
            default => response()->json(['message' => 'Invalid quote type.'], Response::HTTP_BAD_REQUEST),
        };
    }

    protected function handleBinaryAnswer(Quote $quote, AnswerRequest $request, Answer $answer = null): JsonResponse|AnswerResource
    {
        if ($quote->answers()->count() >= 2) {
            return response()->
            json(['message' => 'Binary quotes cannot have more than 2 answers.'], Response::HTTP_BAD_REQUEST);
        }

        $lowerCaseAnswer = strtolower($request->answer);
        if (!in_array($lowerCaseAnswer, ['yes', 'no'])) {
            return response()->
            json(['message' => "Binary quotes can only have 'Yes' or 'No' as answers"], Response::HTTP_BAD_REQUEST);
        }

        if ($quote->answers()->whereRaw('LOWER(answer) = ?', [$lowerCaseAnswer])->exists()) {
            return response()->
            json(['message' => "Binary quotes cannot have duplicate answers"], Response::HTTP_BAD_REQUEST);
        }

        return $this->createAnswer($quote, $request);
    }

    protected function handleMultipleChoiceAnswer(Quote $quote, AnswerRequest $request, Answer $answer = null): JsonResponse|AnswerResource
    {
        if ($quote->answers()->count() >= 3) {
            return response()->
            json(['message' => 'Multiple-choice quotes cannot have more than 3 answers.'], Response::HTTP_BAD_REQUEST);
        }

        $lowerCaseAnswer = strtolower($request->answer);
        if ($quote->answers()->whereRaw('LOWER(answer) = ?', [$lowerCaseAnswer])->exists()) {
            return response()->
            json(['message' => "This answer already exists"], Response::HTTP_BAD_REQUEST);
        }

        $existingIncorrectAnswers = $quote->answers()->where('is_correct', false)->count();
        if ($existingIncorrectAnswers >= 2 && !$request->is_correct) {
            return response()->
            json(['message' => "At least one answer must be correct"], Response::HTTP_BAD_REQUEST);
        }

        return $this->createAnswer($quote, $request);
    }

    protected function createAnswer(Quote $quote, AnswerRequest $request): AnswerResource
    {
        $answer = $quote->answers()->create([
            'answer' => $request->answer,
            'is_correct' => $request->is_correct,
        ]);

        return new AnswerResource($answer);
    }
    public function updateAnswer(AnswerRequest $request, Answer $answer): JsonResponse|AnswerResource
    {
        $quote = Quote::findOrFail($request->quote_id);

        return match ($quote->type) {
            'binary' => $this->handleBinaryAnswer($quote, $request, $answer),
            'multiple_choice' => $this->handleMultipleChoiceAnswer($quote, $request, $answer),
            default => response()->json(['message' => 'Invalid quote type.'], Response::HTTP_BAD_REQUEST),
        };
    }
}
