<?php

namespace App\Http\Controllers;

use App\Http\Requests\DiscoveryAssistantMessageRequest;
use App\Services\Experience\CulturalDiscoveryAssistantService;
use Illuminate\Http\JsonResponse;

class DiscoveryAssistantController extends Controller
{
    public function __invoke(
        DiscoveryAssistantMessageRequest $request,
        CulturalDiscoveryAssistantService $assistant,
    ): JsonResponse {
        $validated = $request->validated();

        return response()->json($assistant->respond(
            $validated['message'],
            $request->user(),
            $validated['context_experience_id'] ?? null,
        ));
    }

    public function reset(CulturalDiscoveryAssistantService $assistant): JsonResponse
    {
        $assistant->clearContext();

        return response()->json(['message' => 'Conversation context cleared.']);
    }
}
