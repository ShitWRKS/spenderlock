<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    /**
     * Get suppliers list, with optional search
     * Query: ?q=search_term
     */
    public function index(Request $request)
    {
        $query = $request->query('q', '');

        $suppliers = Supplier::query()
            ->when($query, function ($q) use ($query) {
                return $q->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            })
            ->limit(50)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $suppliers->map(fn($supplier) => $this->supplierResource($supplier)),
            'count' => $suppliers->count(),
        ]);
    }

    /**
     * Get supplier details
     */
    public function show($id)
    {
        $supplier = Supplier::find($id);

        if (!$supplier) {
            return response()->json([
                'status' => 'error',
                'message' => 'Supplier not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $this->supplierResource($supplier),
        ]);
    }

    /**
     * Create new supplier
     * Body: { "name": "Company Name", "email": "info@company.com", "phone": "123456", "vat_number": "IT12345", "address": "..." }
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'vat_number' => 'nullable|string|max:255',
            'fiscal_code' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $supplier = Supplier::create([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'vat_number' => $validated['vat_number'] ?? null,
            'fiscal_code' => $validated['fiscal_code'] ?? null,
            'address' => $validated['address'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Supplier created successfully',
            'data' => $this->supplierResource($supplier),
        ], 201);
    }

    /**
     * Get supplier contacts
     */
    public function contacts($id)
    {
        $supplier = Supplier::find($id);

        if (!$supplier) {
            return response()->json([
                'status' => 'error',
                'message' => 'Supplier not found',
            ], 404);
        }

        $contacts = $supplier->contacts()->get();

        return response()->json([
            'status' => 'success',
            'supplier_id' => $id,
            'data' => $contacts->map(fn($contact) => [
                'id' => $contact->id,
                'name' => $contact->name,
                'role' => $contact->role,
                'email' => $contact->email,
                'phone' => $contact->phone,
            ]),
            'count' => $contacts->count(),
        ]);
    }

    /**
     * Create new contact for supplier
     * Body: { "supplier_id": 1, "name": "John Doe", "email": "john@example.com", "role": "Manager", "phone": "123456" }
     */
    public function createContact(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'role' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $supplier = Supplier::find($validated['supplier_id']);

        if (!$supplier) {
            return response()->json([
                'status' => 'error',
                'message' => 'The selected supplier id is invalid',
            ], 422);
        }

        $contact = $supplier->contacts()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Contact created successfully',
            'data' => [
                'id' => $contact->id,
                'name' => $contact->name,
                'role' => $contact->role,
                'email' => $contact->email,
                'phone' => $contact->phone,
                'supplier_id' => $supplier->id,
            ],
        ], 201);
    }

    /**
     * Format supplier data for API response
     */
    private function supplierResource($supplier)
    {
        return [
            'id' => $supplier->id,
            'name' => $supplier->name,
            'email' => $supplier->email,
            'phone' => $supplier->phone,
            'vat_number' => $supplier->vat_number,
            'fiscal_code' => $supplier->fiscal_code,
            'address' => $supplier->address,
            'contact_count' => $supplier->contacts()->count(),
        ];
    }
}
