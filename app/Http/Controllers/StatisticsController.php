<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class StatisticsController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::with(['sessions' => function ($query) {
            $query->whereNotNull('end_time'); // Consider only completed sessions
        }])->get()->map(function ($user) {
            // Calculate total score and average time for each user
            $totalScore = 0;
            $totalTime = 0;
            foreach ($user->sessions as $session) {
                $totalScore += $session->userAnswers()->where('is_correct', true)->count();
                $totalTime += $session->created_at->diffInSeconds($session->end_time);
            }
            return [
                'name' => $user->name,
                'email' => $user->email,
                'total_score' => $totalScore,
                'average_time' => gmdate('H:i:s', $totalTime / count($user->sessions)),
            ];
        });

        // Sort by score and time
        $sortedUsers = $users->sortByDesc('total_score')
            ->sortBy('average_time');

        return response()->json($sortedUsers->values()->all());
    }
}
