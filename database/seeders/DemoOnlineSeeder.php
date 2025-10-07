<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Supplier;
use App\Models\ContractCategory;
use App\Models\Contract;
use App\Models\Budget;
use App\Models\Contact;

class DemoOnlineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder creates a demo tenant with related demo data suitable for an online demo.
     */
    public function run()
    {
        // Suppliers
        $suppliers = collect([
            ['name' => 'Alpha Supplies', 'email' => 'alpha@suppliers.example'],
            ['name' => 'Beta Services', 'email' => 'beta@suppliers.example'],
            ['name' => 'Gamma Contractors', 'email' => 'gamma@suppliers.example'],
        ])->map(function ($data) {
            return Supplier::firstOrCreate(
                ['email' => $data['email']],
                $data
            );
        });

        // Contract categories
        $categories = collect(['Maintenance', 'Cleaning', 'Security', 'Utilities'])
            ->map(function ($name) {
                return ContractCategory::firstOrCreate(
                        ['name' => $name],
                        []
                    );
            });

        // Create contracts
        $contracts = collect([
            [
                'title' => 'Annual Maintenance Agreement',
                'supplier_id' => $suppliers[0]->id,
                'category_id' => $categories[0]->id,
                'start_date' => now()->subMonths(3)->toDateString(),
                'end_date' => now()->addMonths(9)->toDateString(),
                'amount' => 12000,
            ],
            [
                'title' => 'Monthly Cleaning Service',
                'supplier_id' => $suppliers[1]->id,
                'category_id' => $categories[1]->id,
                'start_date' => now()->subMonths(1)->toDateString(),
                'end_date' => now()->addMonths(11)->toDateString(),
                'amount' => 3600,
            ],
        ])->map(function ($data) {
            // adjust field names to match model
            $payload = [
                'supplier_id' => $data['supplier_id'],
                'title' => $data['title'],
                'contract_category_id' => $data['category_id'] ?? ($data['contract_category_id'] ?? null),
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'amount_total' => $data['amount'] ?? null,
            ];

            return Contract::firstOrCreate([
                'title' => $payload['title'],
                'supplier_id' => $payload['supplier_id'],
            ], $payload);
        });

        // Budgets
        $budgets = collect([
            ['name' => 'Maintenance Budget', 'year' => now()->year, 'amount' => 15000],
            ['name' => 'Operations Budget', 'year' => now()->year, 'amount' => 5000],
        ])->map(function ($data) {
            // Budget model has fields: year, category, allocated
            $payload = [
                'year' => $data['year'],
                'category' => $data['name'],
                'allocated' => $data['amount'],
            ];
            return Budget::firstOrCreate(['year' => $payload['year'], 'category' => $payload['category']], $payload);
        });

        // Contacts (assign to an existing supplier to satisfy NOT NULL constraint)
        $contacts = collect([
            ['name' => 'Luca Rossi', 'email' => 'luca.rossi@example.com', 'phone' => '+39 012 3456'],
            ['name' => 'Maria Bianchi', 'email' => 'maria.bianchi@example.com', 'phone' => '+39 098 7654'],
        ])->map(function ($data) use ($suppliers) {
            $data['supplier_id'] = $suppliers->first()->id ?? null;
            return Contact::firstOrCreate(['email' => $data['email']], $data);
        });

        // Link some contracts to budgets or contacts if pivot exists
        foreach ($contracts as $contract) {
            // Attach a budget if relation exists
            if (method_exists($contract, 'budgets')) {
                try {
                    $contract->budgets()->syncWithoutDetaching([$budgets->first()->id]);
                } catch (\Throwable $e) {
                    // ignore
                }
            }

            // Attach a contact if relation exists
            if (method_exists($contract, 'contacts')) {
                try {
                    $contract->contacts()->syncWithoutDetaching([$contacts->first()->id]);
                } catch (\Throwable $e) {
                    // ignore
                }
            }
        }

        $this->command->info('Demo online data seeded.');
    }
}
