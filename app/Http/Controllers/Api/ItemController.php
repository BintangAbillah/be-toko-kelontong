<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ItemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = Item::query()
            ->forUser($request->user())
            ->when($request->query('search'), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate((int) $request->query('per_page', 15));

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
        $this->authorizeUserItem($request, $item);

        return response()->json(['data' => $item->load('stockMovements')]);
    }

    public function update(Request $request, Item $item): JsonResponse
    {
        $this->authorizeUserItem($request, $item);
        $item->update($this->validatedData($request, $item));

        return response()->json(['data' => $item->refresh()]);
    }

    public function destroy(Request $request, Item $item): JsonResponse
    {
        $this->authorizeUserItem($request, $item);
        $item->delete();

        return response()->json(status: 204);
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
                Rule::unique('items')->where('user_id', $request->user()->getKey())->ignore($item),
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

    private function authorizeUserItem(Request $request, Item $item): void
    {
        abort_unless($item->user_id === $request->user()->getKey(), 404);
    }
}
