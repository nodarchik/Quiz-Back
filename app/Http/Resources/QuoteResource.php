<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuoteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'quote' => $this->quote,
            'type' => $this->type,
            'answers' => AnswerResource::collection($this->whenLoaded('answers')),
        ];
    }
}
