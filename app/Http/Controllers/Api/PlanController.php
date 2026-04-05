<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePlanRequest;
use App\Http\Requests\UpdatePlanRequest;
use App\Http\Resources\PlanResource;
use App\Models\Plan;
use App\Services\PlanService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class PlanController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private readonly PlanService $planService) {}

    public function index()
    {
        $plans = Plan::with('planVariants')->latest()->paginate();

        return $this->successResponse(PlanResource::collection($plans), 'Plans fetched successfully.');
    }

    public function store(StorePlanRequest $request)
    {
        $validatedData = $request->validated();
        $plan = $this->planService->create($validatedData);

        return $this->successResponse( new PlanResource($plan->load('planVariants')),'Plan created successfully.');
    }

    public function show(Plan $plan): JsonResponse
    {
        return $this->successResponse(new PlanResource($plan->load('planVariants')),'Plan fetched successfully.');
    }

public function update(UpdatePlanRequest $request, int $id): JsonResponse
{
    $plan = Plan::find($id);

    if (! $plan) {
        return $this->errorResponse('Plan not found.', null);
    }

    $validatedData = $request->validated();
    $plan = $this->planService->update($plan, $validatedData);

    return $this->successResponse( new PlanResource($plan->load('planVariants')),'Plan updated successfully.');
}

    public function destroy(Plan $plan): JsonResponse
    {
        $this->planService->delete($plan);

        return $this->successResponse( null,'Plan deleted successfully.');
    }
}
