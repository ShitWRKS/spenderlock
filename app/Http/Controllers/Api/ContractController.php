<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Reminder;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    public function upcoming(Request $request)
    {
        $days = (int) $request->query('days', 30);
        $today = now();
        $endDate = $today->copy()->addDays($days);

        $contracts = Contract::whereBetween('end_date', [$today, $endDate])
            ->with('supplier', 'category')
            ->orderBy('end_date', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $contracts->map(fn($contract) => $this->contractResource($contract)),
            'count' => $contracts->count(),
        ]);
    }

    public function search(Request $request)
    {
        $query = $request->query('q', '');

        if (empty($query)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Query parameter "q" is required',
            ], 400);
        }

        $contracts = Contract::with('supplier', 'category')
            ->where('title', 'like', "%{$query}%")
            ->orWhereHas('supplier', fn($q) => $q->where('name', 'like', "%{$query}%"))
            ->limit(50)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $contracts->map(fn($contract) => $this->contractResource($contract)),
            'count' => $contracts->count(),
        ]);
    }

    public function show($id)
    {
        $contract = Contract::with('supplier', 'category')->find($id);

        if (!$contract) {
            return response()->json([
                'status' => 'error',
                'message' => 'Contract not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $this->contractResource($contract),
        ]);
    }

    public function createReminder(Request $request, $id)
    {
        $contract = Contract::find($id);

        if (!$contract) {
            return response()->json([
                'status' => 'error',
                'message' => 'Contract not found',
            ], 404);
        }

        $validated = $request->validate([
            'reminder_date' => 'required|date',
            'description' => 'nullable|string|max:500',
        ]);

        $reminder = Reminder::create([
            'contract_id' => $id,
            'reminder_date' => $validated['reminder_date'],
            'description' => $validated['description'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Reminder created successfully',
            'data' => $reminder,
        ], 201);
    }

    private function contractResource($contract)
    {
        $daysRemaining = now()->diffInDays($contract->end_date, false);

        return [
            'id' => $contract->id,
            'title' => $contract->title,
            'supplier' => [
                'id' => $contract->supplier->id,
                'name' => $contract->supplier->name,
                'email' => $contract->supplier->email,
            ],
            'category' => $contract->category ? [
                'id' => $contract->category->id,
                'name' => $contract->category->name,
            ] : null,
            'start_date' => $contract->start_date->format('Y-m-d'),
            'end_date' => $contract->end_date->format('Y-m-d'),
            'days_remaining' => $daysRemaining,
            'amount_total' => $contract->amount_total,
            'amount_recurring' => $contract->amount_recurring,
            'frequency_months' => $contract->frequency_months,
            'renewal_mode' => $contract->renewal_mode,
            'payment_type' => $contract->payment_type,
        ];
    }
}
