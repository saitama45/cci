<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\ContractedSale;
use App\Models\Bill;
use App\Models\PurchaseOrder;
use App\Models\Project;
use App\Models\Unit;
use App\Models\Company;
use App\Models\Disbursement;
use App\Models\Vendor;
use App\Models\PdcVault;
use App\Models\Broker;
use App\Models\JournalEntry;
use App\Http\Services\RoleService;
use Illuminate\Support\Facades\Auth;

class GlobalSearchController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->get('q');
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        // Use a fallback for company_id to ensure we find records even if the session is slightly out of sync
        $user = Auth::user();
        $companyId = $user->company_id ?: (Company::first()->id ?? 1);
        
        $results = [];

        // 0. Sidebar Modules / Menu Items
        $modules = RoleService::getLandingPageOptions();
        $filteredModules = collect($modules)->filter(function($item) use ($query) {
            return str_contains(strtolower($item['label']), strtolower($query));
        })->map(function($item) {
            // Map common modules to better icons for global search
            $icon = 'ClockIcon'; // Default
            if (str_contains($item['label'], 'Customer')) $icon = 'UserIcon';
            if (str_contains($item['label'], 'Contract')) $icon = 'ClipboardDocumentCheckIcon';
            if (str_contains($item['label'], 'Project')) $icon = 'BuildingOfficeIcon';
            if (str_contains($item['label'], 'Bill') || str_contains($item['label'], 'Voucher')) $icon = 'BanknotesIcon';
            if (str_contains($item['label'], 'User')) $icon = 'UserIcon';
            if (str_contains($item['label'], 'Broker')) $icon = 'UserGroupIcon';
            
            return [
                'id' => $item['route'],
                'title' => $item['label'],
                'subtitle' => 'System Module',
                'type' => 'Menu',
                'url' => route($item['route']),
                'icon' => $icon
            ];
        });
        $results = array_merge($results, $filteredModules->toArray());

        // 1. Customers
        $customers = Customer::where(function($q) use ($query) {
                $q->where('first_name', 'like', "%{$query}%")
                  ->orWhere('last_name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%")
                  ->orWhere('contact_no', 'like', "%{$query}%")
                  ->orWhere('account_no', 'like', "%{$query}%");
            })
            ->limit(5)
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->first_name . ' ' . $item->last_name,
                    'subtitle' => 'Customer • ' . ($item->account_no ?? 'No Account #'),
                    'type' => 'Customer',
                    'url' => route('customers.show', $item->id),
                    'icon' => 'UserIcon'
                ];
            });
        $results = array_merge($results, $customers->toArray());

        // 2. Contracted Sales (Search by Contract # OR Customer Name)
        $sales = ContractedSale::where('company_id', $companyId)
            ->where(function($q) use ($query) {
                $q->where('contract_no', 'like', "%{$query}%")
                  ->orWhereHas('customer', function($sub) use ($query) {
                      $sub->where('first_name', 'like', "%{$query}%")
                        ->orWhere('last_name', 'like', "%{$query}%");
                  });
            })
            ->limit(5)
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->contract_no,
                    'subtitle' => 'Contract for ' . ($item->customer->first_name ?? '') . ' ' . ($item->customer->last_name ?? ''),
                    'type' => 'Contract',
                    'url' => route('contracted-sales.show', $item->id),
                    'icon' => 'ClipboardDocumentCheckIcon'
                ];
            });
        $results = array_merge($results, $sales->toArray());

        // 3. Projects
        $projects = Project::where('company_id', $companyId)
            ->where('name', 'like', "%{$query}%")
            ->limit(3)
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->name,
                    'subtitle' => 'Project Location',
                    'type' => 'Project',
                    'url' => route('projects.show', $item->id),
                    'icon' => 'BuildingOfficeIcon'
                ];
            });
        $results = array_merge($results, $projects->toArray());

        // 4. Bills
        $bills = Bill::where('company_id', $companyId)
            ->where('bill_number', 'like', "%{$query}%")
            ->limit(5)
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->bill_number,
                    'subtitle' => 'Vendor Bill',
                    'type' => 'Bill',
                    'url' => route('accounting.bills.show', $item->id),
                    'icon' => 'BanknotesIcon'
                ];
            });
        $results = array_merge($results, $bills->toArray());

        // 5. Purchase Orders
        $pos = PurchaseOrder::where('company_id', $companyId)
            ->where('po_number', 'like', "%{$query}%")
            ->limit(5)
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->po_number,
                    'subtitle' => 'Purchase Order',
                    'type' => 'PO',
                    'url' => route('accounting.purchase-orders.show', $item->id),
                    'icon' => 'ClipboardDocumentCheckIcon'
                ];
            });
        $results = array_merge($results, $pos->toArray());

        // 6. Disbursements (Payment Vouchers)
        $disbursements = Disbursement::where('company_id', $companyId)
            ->where(function($q) use ($query) {
                $q->where('voucher_no', 'like', "%{$query}%")
                  ->orWhereHas('vendor', function($sub) use ($query) {
                      $sub->where('name', 'like', "%{$query}%");
                  });
            })
            ->limit(5)
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->voucher_no,
                    'subtitle' => 'Payment Voucher for ' . ($item->vendor->name ?? 'Unknown'),
                    'type' => 'Voucher',
                    'url' => route('accounting.disbursements.show', $item->id),
                    'icon' => 'BanknotesIcon'
                ];
            });
        $results = array_merge($results, $disbursements->toArray());

        // 7. Vendors
        $vendors = Vendor::where('company_id', $companyId)
            ->where('name', 'like', "%{$query}%")
            ->limit(5)
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->name,
                    'subtitle' => 'Supplier / Vendor',
                    'type' => 'Vendor',
                    'url' => route('vendors.show', $item->id),
                    'icon' => 'UserIcon'
                ];
            });
        $results = array_merge($results, $vendors->toArray());

        // 8. Brokers & Agents
        $brokers = Broker::where('name', 'like', "%{$query}%")
            ->orWhere('prc_license', 'like', "%{$query}%")
            ->limit(5)
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->name,
                    'subtitle' => 'Broker/Agent • ' . ($item->prc_license ?? 'No PRC License'),
                    'type' => 'Broker',
                    'url' => route('brokers.index'),
                    'icon' => 'UserGroupIcon'
                ];
            });
        $results = array_merge($results, $brokers->toArray());

        // 9. Journal Entries
        $jes = JournalEntry::where('company_id', $companyId)
            ->where(function($q) use ($query) {
                $q->where('reference_no', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->limit(5)
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->reference_no ?? 'Manual JE',
                    'subtitle' => 'Journal Entry • ' . $item->description,
                    'type' => 'JE',
                    'url' => route('journal-entries.show', $item->id),
                    'icon' => 'DocumentTextIcon'
                ];
            });
        $results = array_merge($results, $jes->toArray());

        // 10. PDC Vault (Check Numbers or Bank)
        $pdcs = PdcVault::with(['customer', 'vendor', 'payment.reservation.contractedSale'])
            ->where('company_id', $companyId)
            ->where(function($q) use ($query) {
                $q->where('check_no', 'like', "%{$query}%")
                  ->orWhere('bank_name', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get()
            ->map(function($item) {
                $owner = 'Unknown';
                if ($item->type === 'Inward' && $item->customer) {
                    $owner = ($item->customer->first_name ?? '') . ' ' . ($item->customer->last_name ?? '');
                } elseif ($item->type === 'Outward' && $item->vendor) {
                    $owner = $item->vendor->name;
                }

                // Determine URL
                $url = route('accounting.disbursements.vault', ['type' => $item->type]);
                if ($item->type === 'Outward' && $item->disbursement_id) {
                    $url = route('accounting.disbursements.show', $item->disbursement_id);
                } elseif ($item->type === 'Inward' && $item->payment && $item->payment->reservation) {
                    $sale = ContractedSale::where('reservation_id', $item->payment->reservation_id)->first();
                    if ($sale) {
                        $url = route('payments.show', $sale->id);
                    }
                }

                return [
                    'id' => $item->id,
                    'title' => 'Check #' . $item->check_no . ' (' . $item->bank_name . ')',
                    'subtitle' => $item->type . ' PDC • ' . $owner . ' • ₱' . number_format($item->amount, 2),
                    'type' => 'PDC',
                    'url' => $url,
                    'icon' => 'BanknotesIcon'
                ];
            });
        $results = array_merge($results, $pdcs->toArray());

        return response()->json($results);
    }
}
