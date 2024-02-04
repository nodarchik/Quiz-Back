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
    /**
     * Display a listing of the resource.
     *
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $quotes = Quote::all();
        return QuoteResource::collection($quotes);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param QuoteRequest $request
     * @return QuoteResource
     */
    public function store(QuoteRequest $request): QuoteResource
    {
        $quote = Quote::create($request->validated());
        return new QuoteResource($quote);
    }

    /**
     * Display the specified resource.
     *
     * @param Quote $quote
     * @return QuoteResource
     */
    public function show(Quote $quote): QuoteResource
    {
        return new QuoteResource($quote);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param QuoteRequest $request
     * @param Quote $quote
     * @return QuoteResource
     */
    public function update(QuoteRequest $request, Quote $quote): QuoteResource
    {
        $quote->update($request->validated());
        return new QuoteResource($quote);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function destroy(Quote $quote): JsonResponse
    {
        $quote->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
