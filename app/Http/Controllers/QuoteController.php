<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuoteRequest;
use App\Http\Resources\QuoteResource;
use App\Models\Quote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class QuoteController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $quotes = Quote::all();
        return QuoteResource::collection($quotes);
    }
    public function questionnaire(): AnonymousResourceCollection
    {
        $quotes = Quote::has('answers')->with('answers')->get();
        return QuoteResource::collection($quotes);
    }
    public function store(QuoteRequest $request): QuoteResource
    {
        $quote = Quote::create($request->validated());
        return new QuoteResource($quote);
    }

    public function show(Quote $quote): QuoteResource
    {
        return new QuoteResource($quote);
    }

    public function update(QuoteRequest $request, Quote $quote): QuoteResource
    {
        $quote->update($request->validated());
        return new QuoteResource($quote);
    }

    public function destroy(Quote $quote): JsonResponse
    {
        $quote->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
