<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\Bill;
use App\Models\Disbursement;
use App\Models\PaymentSchedule;
use App\Models\PdcVault;
use Illuminate\Support\Facades\Auth;

class NotificationService
{
    /**
     * Get real-time notifications based on existing record states
     */
    public static function getActiveAlerts($user)
    {
        if (!$user) return [];

        $companyId = $user->company_id;
        $isAdmin = $user->hasRole('Admin');
        $alerts = [];

        // 1. POs Awaiting Approval
        if ($user->can('purchase_orders.approve') || $isAdmin) {
            $query = PurchaseOrder::with('vendor')->where('status', 'Draft');
            
            if ($companyId) {
                $query->where('company_id', $companyId);
            }

            $pendingPos = $query->latest()->limit(5)->get();

            foreach ($pendingPos as $po) {
                $alerts[] = [
                    'id' => "po_{$po->id}",
                    'title' => 'PO Awaiting Approval',
                    'message' => "Order #{$po->po_number} for " . ($po->vendor->name ?? 'Unknown Vendor') . " is pending review.",
                    'url' => route('accounting.purchase-orders.show', $po->id),
                    'type' => 'procurement',
                    'icon' => 'ShoppingCartIcon',
                    'created_at' => $po->created_at->diffForHumans()
                ];
            }
        }

        // 2. Bills Awaiting Approval
        if ($user->can('bills.approve') || $isAdmin) {
            $query = Bill::with('vendor')->where('status', 'Draft');
            
            if ($companyId) {
                $query->where('company_id', $companyId);
            }

            $pendingBills = $query->latest()->limit(5)->get();

            foreach ($pendingBills as $bill) {
                $alerts[] = [
                    'id' => "bill_{$bill->id}",
                    'title' => 'Bill Awaiting Approval',
                    'message' => "{$bill->type} #{$bill->bill_number} from " . ($bill->vendor->name ?? 'Unknown') . " is pending review.",
                    'url' => route('accounting.bills.show', $bill->id),
                    'type' => 'procurement',
                    'icon' => 'BanknotesIcon',
                    'created_at' => $bill->created_at->diffForHumans()
                ];
            }
        }

        // 3. Payment Vouchers Awaiting Approval
        if ($user->can('disbursements.approve') || $isAdmin) {
            $query = Disbursement::with('vendor')->where('status', 'Draft');
            
            if ($companyId) {
                $query->where('company_id', $companyId);
            }

            $pendingVouchers = $query->latest()->limit(5)->get();

            foreach ($pendingVouchers as $pv) {
                $alerts[] = [
                    'id' => "pv_{$pv->id}",
                    'title' => 'PV Awaiting Approval',
                    'message' => "Payment Voucher #{$pv->voucher_no} for " . ($pv->vendor->name ?? 'Unknown') . " is pending review.",
                    'url' => route('accounting.disbursements.show', $pv->id),
                    'type' => 'procurement',
                    'icon' => 'DocumentTextIcon',
                    'created_at' => $pv->created_at->diffForHumans()
                ];
            }
        }

        // 4. Overdue Payments
        if ($user->can('payments.view') || $isAdmin) {
            $query = PaymentSchedule::where('status', 'Pending')
                ->whereDate('due_date', '<', now()->toDateString())
                ->with(['contractedSale.customer']);

            if ($companyId) {
                $query->whereHas('contractedSale', function($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                });
            }

            $overdue = $query->latest('due_date')->limit(5)->get();

            foreach ($overdue as $s) {
                $alerts[] = [
                    'id' => "overdue_{$s->id}",
                    'title' => 'Overdue Payment',
                    'message' => "Contract #{$s->contractedSale->contract_no} (" . ($s->contractedSale->customer->full_name ?? 'Customer') . ") has an overdue installment.",
                    'url' => route('payments.show', $s->contracted_sale_id),
                    'type' => 'collections',
                    'icon' => 'ClockIcon',
                    'created_at' => $s->due_date->diffForHumans()
                ];
            }
        }

        // 5. PDC Checks Due Today
        if ($user->can('accounting.view') || $isAdmin) {
            $query = PdcVault::with(['customer', 'vendor'])->where('status', 'Pending')
                ->whereDate('check_date', '<=', now()->toDateString());

            if ($companyId) {
                $query->where('company_id', $companyId);
            }

            $duePdcs = $query->limit(5)->get();

            foreach ($duePdcs as $pdc) {
                $owner = $pdc->type === 'Inward' 
                    ? ($pdc->customer->full_name ?? 'Customer')
                    : ($pdc->vendor->name ?? 'Vendor');

                $alerts[] = [
                    'id' => "pdc_{$pdc->id}",
                    'title' => 'PDC Due Today',
                    'message' => "Check #{$pdc->check_no} from {$owner} is ready for clearing.",
                    'url' => route('accounting.disbursements.vault', ['type' => $pdc->type]),
                    'type' => 'treasury',
                    'icon' => 'BanknotesIcon',
                    'created_at' => $pdc->check_date->diffForHumans()
                ];
            }
        }

        return $alerts;
    }
}
