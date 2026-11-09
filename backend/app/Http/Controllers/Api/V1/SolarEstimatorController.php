<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Solar\SolarEstimator;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** GET /api/v1/solar-estimator: the form; POST /api/v1/solar-estimate: one estimate. Off until the owner switches it on. */
class SolarEstimatorController extends Controller
{
    public function form(SolarEstimator $estimator): JsonResponse
    {
        return response()->json(['schemaVersion' => 1] + $estimator->form(), 200, ['Cache-Control' => 'public, max-age=60'], JSON_UNESCAPED_UNICODE);
    }

    public function estimate(Request $request, SolarEstimator $estimator): JsonResponse
    {
        if (! $estimator->enabled()) {
            return response()->json(['error' => ['code' => 'DISABLED']], 404);
        }
        $answer = $estimator->estimate($request->json()->all());

        return response()->json($answer, $answer['ok'] ? 200 : 422, ['Cache-Control' => 'no-store'], JSON_UNESCAPED_UNICODE);
    }
}
