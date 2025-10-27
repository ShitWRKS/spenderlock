<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Supplier;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    /**
     * Get contact details
     */
    public function show($id)
    {
        $contact = Contact::with('supplier')->find($id);

        if (!$contact) {
            return response()->json([
                'status' => 'error',
                'message' => 'Contact not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $this->contactResource($contact),
        ]);
    }

    /**
     * Create new contact
     * Body: { "supplier_id": 1, "name": "John Doe", "email": "john@example.com", "role": "Manager", "phone": "123456", "notes": "..." }
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'role' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        if (!Supplier::find($validated['supplier_id'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'The selected supplier id is invalid',
            ], 422);
        }

        $contact = Contact::create([
            'supplier_id' => $validated['supplier_id'],
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Contact created successfully',
            'data' => $this->contactResource($contact->load('supplier')),
        ], 201);
    }

    /**
     * Update existing contact
     * Body: { "name": "John Doe", "email": "john@example.com", "role": "Manager", "phone": "123456", "notes": "..." }
     */
    public function update(Request $request, $id)
    {
        $contact = Contact::find($id);

        if (!$contact) {
            return response()->json([
                'status' => 'error',
                'message' => 'Contact not found',
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255',
            'role' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'supplier_id' => 'sometimes|required|integer',
        ]);

        if (isset($validated['supplier_id']) && !Supplier::find($validated['supplier_id'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'The selected supplier id is invalid',
            ], 422);
        }

        $contact->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Contact updated successfully',
            'data' => $this->contactResource($contact->load('supplier')),
        ]);
    }

    /**
     * Search contacts by email (for Gmail integration)
     * Query: ?email=john@example.com
     */
    public function searchByEmail(Request $request)
    {
        $email = $request->query('email', '');

        if (!$email) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email parameter required',
            ], 400);
        }

        $contact = Contact::with('supplier')
            ->where('email', $email)
            ->first();

        if (!$contact) {
            return response()->json([
                'status' => 'not_found',
                'message' => 'Contact not found',
                'email' => $email,
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $this->contactResource($contact),
        ]);
    }

    /**
     * Format contact data for API response
     */
    private function contactResource($contact)
    {
        return [
            'id' => $contact->id,
            'name' => $contact->name,
            'email' => $contact->email,
            'role' => $contact->role,
            'phone' => $contact->phone,
            'notes' => $contact->notes,
            'supplier_id' => $contact->supplier_id,
            'supplier' => $contact->supplier ? [
                'id' => $contact->supplier->id,
                'name' => $contact->supplier->name,
                'email' => $contact->supplier->email,
                'phone' => $contact->supplier->phone,
            ] : null,
        ];
    }
}
