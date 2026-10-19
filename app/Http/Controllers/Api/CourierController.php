<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexCourierRequest;
use App\Http\Requests\StoreCourierRequest;
use App\Http\Requests\UpdateCourierRequest;
use App\Http\Resources\CourierCollection;
use App\Http\Resources\CourierResource;
use App\Models\Courier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;

class CourierController extends Controller
{
    public function index(IndexCourierRequest $request): CourierCollection
    {
        $couriers = Courier::query()
            ->when($request->validated('search'), fn (Builder $query, string $search) => $query->search($search))
            ->when($request->levels(), fn (Builder $query, array $levels) => $query->whereIn('level', $levels))
            ->orderBy($request->validated('sort_by') ?? 'name', $request->validated('sort_dir') ?? 'asc')
            ->orderBy('id')
            ->paginate($request->integer('per_page') ?: 15)
            ->withQueryString();

        return CourierCollection::make($couriers);
    }

    public function store(StoreCourierRequest $request): CourierResource
    {
        $courier = Courier::create($request->courierData());

        return CourierResource::make($courier->refresh());
    }

    public function show(Courier $courier): CourierResource
    {
        return CourierResource::make($courier);
    }

    public function update(UpdateCourierRequest $request, Courier $courier): CourierResource
    {
        $courier->update($request->courierData());

        return CourierResource::make($courier);
    }

    public function destroy(Courier $courier): Response
    {
        $courier->delete();

        return response()->noContent();
    }
}
