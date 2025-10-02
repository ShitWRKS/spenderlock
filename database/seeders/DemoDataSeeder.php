<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ContractCategory;
use App\Models\Supplier;
use App\Models\Contract;
use App\Models\Budget;
use App\Models\Contact;

class DemoDataSeeder extends Seeder
{
    /**
     * Seed dei dati demo per il tenant demo.
     */
    public function run(): void
    {
        $this->command->info('🎨 Creazione dati demo...');

        // Crea categorie contratto
        $categories = $this->createContractCategories();
        
        // Crea fornitori
        $suppliers = $this->createSuppliers();
        
        // Crea contatti per i fornitori
        $this->createContacts($suppliers);
        
        // Crea contratti
        $this->createContracts($suppliers, $categories);
        
        // Crea budget
        $this->createBudgets($categories);

        $this->command->info('✅ Dati demo creati con successo!');
    }

    private function createContractCategories()
    {
        $categories = [
            ['name' => 'Software e Licenze'],
            ['name' => 'Servizi IT'],
            ['name' => 'Marketing e Pubblicità'],
            ['name' => 'Consulenze'],
            ['name' => 'Manutenzioni'],
        ];

        $createdCategories = [];
        foreach ($categories as $categoryData) {
            $createdCategories[] = ContractCategory::create($categoryData);
        }

        return $createdCategories;
    }

    private function createSuppliers()
    {
        $suppliers = [
            [
                'name' => 'TechSoft Solutions SRL',
                'email' => 'info@techsoft-demo.com',
                'phone' => '+39 02 1234567',
                'address' => 'Via Milano 123, 20100 Milano',
                'vat_number' => 'IT12345678901',
                'notes' => 'Fornitore software enterprise specializzato in CRM e ERP - Website: https://techsoft-demo.com',
            ],
            [
                'name' => 'Digital Marketing Pro',
                'email' => 'contact@digitalmarketing-demo.com',
                'phone' => '+39 06 7654321',
                'address' => 'Via Roma 456, 00100 Roma',
                'vat_number' => 'IT98765432109',
                'notes' => 'Agenzia marketing digitale con focus su e-commerce - Website: https://digitalmarketing-demo.com',
            ],
            [
                'name' => 'IT Consulting Group',
                'email' => 'hello@itconsulting-demo.com',
                'phone' => '+39 011 9876543',
                'address' => 'Corso Torino 789, 10100 Torino',
                'vat_number' => 'IT11223344556',
                'notes' => 'Consulenza IT strategica e trasformazione digitale - Website: https://itconsulting-demo.com',
            ],
            [
                'name' => 'CloudHost Services',
                'email' => 'support@cloudhost-demo.com',
                'phone' => '+39 051 1122334',
                'address' => 'Via Bologna 321, 40100 Bologna',
                'vat_number' => 'IT66778899001',
                'notes' => 'Provider cloud hosting e servizi managed - Website: https://cloudhost-demo.com',
            ],
        ];

        $createdSuppliers = [];
        foreach ($suppliers as $supplierData) {
            $createdSuppliers[] = Supplier::create($supplierData);
        }

        return $createdSuppliers;
    }

    private function createContacts($suppliers)
    {
        $contacts = [
            [
                'supplier_id' => $suppliers[0]->id,
                'name' => 'Marco Rossi',
                'email' => 'marco.rossi@techsoft-demo.com',
                'phone' => '+39 02 1234567',
                'role' => 'Account Manager',
            ],
            [
                'supplier_id' => $suppliers[0]->id,
                'name' => 'Laura Bianchi',
                'email' => 'laura.bianchi@techsoft-demo.com',
                'phone' => '+39 02 1234568',
                'role' => 'Technical Support',
            ],
            [
                'supplier_id' => $suppliers[1]->id,
                'name' => 'Andrea Verdi',
                'email' => 'andrea.verdi@digitalmarketing-demo.com',
                'phone' => '+39 06 7654321',
                'role' => 'Creative Director',
            ],
            [
                'supplier_id' => $suppliers[2]->id,
                'name' => 'Giulia Neri',
                'email' => 'giulia.neri@itconsulting-demo.com',
                'phone' => '+39 011 9876543',
                'role' => 'Senior Consultant',
            ],
            [
                'supplier_id' => $suppliers[3]->id,
                'name' => 'Roberto Blu',
                'email' => 'roberto.blu@cloudhost-demo.com',
                'phone' => '+39 051 1122334',
                'role' => 'Operations Manager',
            ],
        ];

        foreach ($contacts as $contactData) {
            Contact::create($contactData);
        }
    }

    private function createContracts($suppliers, $categories)
    {
        $contracts = [
            [
                'title' => 'Licenza Software CRM Aziendale',
                'supplier_id' => $suppliers[0]->id,
                'contract_category_id' => $categories[0]->id,
                'start_date' => now()->subMonths(8),
                'end_date' => now()->addMonths(4),
                'amount_total' => 15000.00,
                'renewal_mode' => 'automatic',
                'notes' => 'Licenza annuale per software CRM con 50 utenti, supporto e training inclusi. Rinnovo automatico se non disdetto 60 giorni prima.',
            ],
            [
                'title' => 'Hosting Cloud e Servizi Managed',
                'supplier_id' => $suppliers[3]->id,
                'contract_category_id' => $categories[1]->id,
                'start_date' => now()->subMonths(6),
                'end_date' => now()->addMonths(6),
                'amount_total' => 8400.00, // 700 x 12 mesi
                'amount_recurring' => 700.00,
                'frequency_months' => 1,
                'payment_type' => 'recurring',
                'notes' => 'Hosting cloud per applicazioni aziendali con servizi managed 24/7. Possibile upgrade delle risorse in corso di contratto.',
            ],
            [
                'title' => 'Campagna Marketing Digitale Q1-Q2',
                'supplier_id' => $suppliers[1]->id,
                'contract_category_id' => $categories[2]->id,
                'start_date' => now()->subMonths(3),
                'end_date' => now()->addDays(15),
                'amount_total' => 12000.00,
                'payment_type' => 'milestone',
                'notes' => 'Campagna marketing multi-canale per lancio nuovo prodotto. Pagamento 50% anticipo, 50% a fine campagna. Possibile estensione per Q3-Q4 con budget aggiuntivo.',
            ],
            [
                'title' => 'Consulenza Trasformazione Digitale',
                'supplier_id' => $suppliers[2]->id,
                'contract_category_id' => $categories[3]->id,
                'start_date' => now()->subYear(),
                'end_date' => now()->addDays(45),
                'amount_total' => 25000.00,
                'payment_type' => 'milestone',
                'notes' => 'Progetto di consulenza per digitalizzazione processi aziendali. Pagamento milestone-based. Valutazione fase 2 del progetto in corso.',
            ],
            [
                'title' => 'Licenze Microsoft Office 365',
                'supplier_id' => $suppliers[0]->id,
                'contract_category_id' => $categories[0]->id,
                'start_date' => now()->subMonths(2),
                'end_date' => now()->addMonths(10),
                'amount_total' => 6000.00,
                'renewal_mode' => 'automatic',
                'notes' => 'Licenze Microsoft 365 Business Premium per 100 utenti. Rinnovo automatico con possibilità di modifica numero licenze.',
            ],
            [
                'title' => 'Manutenzione Sistema IT',
                'supplier_id' => $suppliers[2]->id,
                'contract_category_id' => $categories[4]->id,
                'start_date' => now()->subMonths(1),
                'end_date' => now()->addMonths(11),
                'amount_total' => 9600.00, // 800 x 12 mesi
                'amount_recurring' => 800.00,
                'frequency_months' => 3,
                'payment_type' => 'recurring',
                'notes' => 'Contratto di manutenzione server e infrastruttura IT. Include aggiornamenti software e interventi on-site.',
            ],
        ];

        foreach ($contracts as $contractData) {
            Contract::create($contractData);
        }
    }

    private function createBudgets($categories)
    {
        $currentYear = now()->year;
        
        $budgets = [
            [
                'year' => $currentYear,
                'category' => $categories[0]->id, // Software e Licenze
                'allocated' => 60000.00,
            ],
            [
                'year' => $currentYear,
                'category' => $categories[1]->id, // Servizi IT
                'allocated' => 35000.00,
            ],
            [
                'year' => $currentYear,
                'category' => $categories[2]->id, // Marketing
                'allocated' => 40000.00,
            ],
            [
                'year' => $currentYear,
                'category' => $categories[3]->id, // Consulenze
                'allocated' => 50000.00,
            ],
            [
                'year' => $currentYear,
                'category' => $categories[4]->id, // Manutenzioni
                'allocated' => 25000.00,
            ],
        ];

        foreach ($budgets as $budgetData) {
            Budget::create($budgetData);
        }
    }
}