<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StockMovementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $movements = StockMovement::query()
            ->with('item')
            ->when($request->query('item_id'), fn ($query, string $itemId) => $query->where('item_id', $itemId))
            ->when($request->query('type'), fn ($query, string $type) => $query->where('type', $type))
            ->latest('occurred_at')
            ->paginate((int) $request->query('per_page', 15));

        return response()->json($movements);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'item_id' => ['required', 'integer'],
            'type' => ['required', Rule::in(['in', 'out', 'adjustment'])],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:255'],
            'occurred_at' => ['nullable', 'date'],
        ]);

        $movement = DB::transaction(function () use ($request, $data): StockMovement {
            $item = Item::query()
                ->lockForUpdate()
                ->findOrFail($data['item_id']);

            $stockBefore = (float) $item->current_stock;
            $quantity = (float) $data['quantity'];
            $stockAfter = match ($data['type']) {
                'in' => $stockBefore + $quantity,
                'out' => $stockBefore - $quantity,
                'adjustment' => $quantity,
            };

            if ($stockAfter < 0) {
                throw ValidationException::withMessages([
                    'quantity' => ['Stock cannot be negative.'],
                ]);
            }

            $item->forceFill(['current_stock' => $stockAfter])->save();

            return StockMovement::query()->create([
                'user_id' => $request->user()->getKey(),
                'item_id' => $item->getKey(),
                'type' => $data['type'],
                'quantity' => $quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'notes' => $data['notes'] ?? null,
                'occurred_at' => $data['occurred_at'] ?? now(),
            ]);
        });

        return response()->json(['data' => $movement->load('item')], 201);
    }

    public function show(Request $request, StockMovement $stockMovement): JsonResponse
    {
        return response()->json(['data' => $stockMovement->load('item')]);
    }
}
