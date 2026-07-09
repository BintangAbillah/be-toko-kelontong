<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CashTransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $transactions = CashTransaction::query()
            ->when($request->query('date'), fn ($query, string $date) => $query->whereDate('transaction_date', $date))
            ->when($request->query('type'), fn ($query, string $type) => $query->where('type', $type))
            ->latest('transaction_date')
            ->latest()
            ->paginate((int) $request->query('per_page', 15));

        return response()->json($transactions);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatedData($request);
        $data['user_id'] = $request->user()->getKey();

        $transaction = CashTransaction::query()->create($data);

        return response()->json(['data' => $transaction], 201);
    }

    public function show(Request $request, CashTransaction $cashTransaction): JsonResponse
    {
        return response()->json(['data' => $cashTransaction]);
    }

    public function update(Request $request, CashTransaction $cashTransaction): JsonResponse
    {
        $cashTransaction->update($this->validatedData($request));

        return response()->json(['data' => $cashTransaction->refresh()]);
    }

    public function destroy(Request $request, CashTransaction $cashTransaction): JsonResponse
    {
        $cashTransaction->delete();

        return response()->json(status: 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request): array
    {
        return $request->validate([
            'type' => ['required', Rule::in(['cash_in', 'cash_out'])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:255'],
            'transaction_date' => ['required', 'date'],
        ]);
    }
}
