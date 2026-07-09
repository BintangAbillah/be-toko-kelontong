<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashTransaction;
use App\Models\Item;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ItemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Item::query();

        if ($request->user()->isCustomer()) {
            $query->where('is_active', true);
        }

        $items = $query
            ->when($request->query('search'), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate((int) $request->query('per_page', 15));

        if ($request->user()->isCustomer()) {
            $items->getCollection()->transform(fn (Item $item) => $this->publicItemData($item));
        }

        return response()->json($items);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatedData($request);
        $data['user_id'] = $request->user()->getKey();

        $item = Item::query()->create($data);

        return response()->json(['data' => $item], 201);
    }

    public function show(Request $request, Item $item): JsonResponse
    {
        if ($request->user()->isCustomer()) {
            abort_unless($item->is_active, 404);

            return response()->json(['data' => $this->publicItemData($item)]);
        }

        return response()->json(['data' => $item->load('stockMovements')]);
    }

    public function update(Request $request, Item $item): JsonResponse
    {
        $this->authorizeUserItem($request);
        $item->update($this->validatedData($request, $item));

        return response()->json(['data' => $item->refresh()]);
    }

    public function destroy(Request $request, Item $item): JsonResponse
    {
        $this->authorizeUserItem($request);
        $item->delete();

        return response()->json(status: 204);
    }

    public function purchase(Request $request, Item $item): JsonResponse
    {
        if ($request->user()->isCustomer()) {
            abort_unless($item->is_active, 404);
        }

        $data = $request->validate([
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'transaction_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $result = DB::transaction(function () use ($request, $item, $data): array {
            $lockedItem = Item::query()->lockForUpdate()->findOrFail($item->getKey());
            $quantity = (float) $data['quantity'];
            $stockBefore = (float) $lockedItem->current_stock;

            if ($stockBefore < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => ['Stock is not enough for this purchase.'],
                ]);
            }

            $stockAfter = $stockBefore - $quantity;
            $transactionDate = $data['transaction_date'] ?? now()->toDateString();
            $occurredAt = Carbon::parse($transactionDate);
            $totalAmount = (float) $lockedItem->selling_price * $quantity;

            $lockedItem->forceFill(['current_stock' => $stockAfter])->save();

            $movement = StockMovement::query()->create([
                'user_id' => $request->user()->getKey(),
                'item_id' => $lockedItem->getKey(),
                'type' => 'out',
                'quantity' => $quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'notes' => $data['notes'] ?? sprintf('Purchase of %s', $lockedItem->name),
                'occurred_at' => $occurredAt,
            ]);

            $cashTransaction = CashTransaction::query()->create([
                'user_id' => $request->user()->getKey(),
                'type' => 'cash_in',
                'amount' => $totalAmount,
                'description' => $data['notes'] ?? sprintf('Purchase of %s', $lockedItem->name),
                'transaction_date' => $transactionDate,
            ]);

            return [
                'item' => $lockedItem->refresh(),
                'movement' => $movement->load('item'),
                'cash_transaction' => $cashTransaction->refresh(),
                'total_amount' => $totalAmount,
            ];
        });

        return response()->json([
            'data' => [
                'item' => $this->publicItemData($result['item']),
                'stock_movement' => $result['movement'],
                'cash_transaction' => $result['cash_transaction'],
                'total_amount' => $result['total_amount'],
            ],
        ], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?Item $item = null): array
    {
        return $request->validate([
            'sku' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('items', 'sku')->ignore($item),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'unit' => ['required', 'string', 'max:50'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
            'minimum_stock' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function authorizeUserItem(Request $request): void
    {
        abort_unless($request->user()->isShopOwner(), 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function publicItemData(Item $item): array
    {
        return [
            'id' => $item->getKey(),
            'sku' => $item->sku,
            'name' => $item->name,
            'description' => $item->description,
            'unit' => $item->unit,
            'selling_price' => $item->selling_price,
            'current_stock' => $item->current_stock,
            'is_active' => $item->is_active,
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
        ];
    }
}
