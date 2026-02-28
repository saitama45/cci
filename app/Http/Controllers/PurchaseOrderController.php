<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Vendor;
use App\Models\Project;
use App\Models\ChartOfAccount;
use App\Models\Bill;
use App\Models\BillItem;
use App\Helpers\LogActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id ?? (\App\Models\Company::first()->id ?? null);

        $query = PurchaseOrder::with(['vendor', 'project', 'preparedBy'])
            ->where('company_id', $companyId);

        if ($request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                  ->orWhereHas('vendor', fn($v) => $v->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $purchaseOrders = $query->latest('po_date')
            ->paginate($request->per_page ?? 10)
            ->withQueryString();

        return Inertia::render('Accounting/PurchaseOrders/Index', [
            'purchaseOrders' => $purchaseOrders,
            'filters' => $request->only(['search', 'status', 'per_page']),
        ]);
    }

    public function create()
    {
        $companyId = Auth::user()->company_id ?? (\App\Models\Company::first()->id ?? null);

        return Inertia::render('Accounting/PurchaseOrders/Create', [
            'vendors' => Vendor::where('company_id', $companyId)->where('is_active', true)->where('verification_status', 'Verified')->get(),
            'projects' => Project::where('company_id', $companyId)->get(),
            'accounts' => ChartOfAccount::where('company_id', $companyId)->whereIn('type', ['expense', 'asset'])->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'project_id' => 'nullable|exists:projects,id',
            'po_number' => 'required|string|unique:purchase_orders,po_number',
            'po_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'tax_type' => 'required|in:VAT Inclusive,VAT Exclusive,Non-VAT',
            'ewt_rate' => 'required|numeric|min:0|max:15',
            'items' => 'required|array|min:1',
            'items.*.chart_of_account_id' => 'required|exists:chart_of_accounts,id',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $companyId = Auth::user()->company_id ?? (\App\Models\Company::first()->id ?? null);

        DB::transaction(function () use ($validated, $companyId) {
            $grossAmount = collect($validated['items'])->sum(fn($i) => $i['quantity'] * $i['unit_price']);
            
            $vatAmount = 0;
            $netOfVat = $grossAmount;

            if ($validated['tax_type'] === 'VAT Inclusive') {
                $netOfVat = $grossAmount / 1.12;
                $vatAmount = $grossAmount - $netOfVat;
            } elseif ($validated['tax_type'] === 'VAT Exclusive') {
                $vatAmount = $grossAmount * 0.12;
                $grossAmount = $grossAmount + $vatAmount; // Total with VAT
            }

            $ewtAmount = $netOfVat * ($validated['ewt_rate'] / 100);
            $netAmount = $grossAmount - $ewtAmount; // Final amount to be paid

            $po = PurchaseOrder::create([
                'company_id' => $companyId,
                'vendor_id' => $validated['vendor_id'],
                'project_id' => $validated['project_id'],
                'po_number' => $validated['po_number'],
                'po_date' => $validated['po_date'],
                'expected_delivery_date' => $validated['expected_delivery_date'],
                'total_amount' => $grossAmount,
                'tax_type' => $validated['tax_type'],
                'vat_amount' => $vatAmount,
                'ewt_rate' => $validated['ewt_rate'],
                'ewt_amount' => $ewtAmount,
                'net_amount' => $netAmount,
                'status' => 'Draft',
                'notes' => $validated['notes'],
                'prepared_by' => Auth::id(),
            ]);

            LogActivity::log('Procurement', 'Created', "Created Purchase Order #{$po->po_number}", $po);

            foreach ($validated['items'] as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'chart_of_account_id' => $item['chart_of_account_id'],
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'amount' => $item['quantity'] * $item['unit_price'],
                ]);
            }
        });

        return redirect()->route('accounting.purchase-orders.index')->with('success', 'Purchase Order created successfully.');
    }

    public function edit(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'Draft') {
            return redirect()->back()->with('error', 'Only draft POs can be edited.');
        }

        $purchaseOrder->load('items');
        
        // Ensure dates are formatted correctly for input fields to avoid timezone shifts
        $purchaseOrder->po_date_formatted = $purchaseOrder->po_date->format('Y-m-d');
        if ($purchaseOrder->expected_delivery_date) {
            $purchaseOrder->expected_delivery_date_formatted = $purchaseOrder->expected_delivery_date->format('Y-m-d');
        }

        $companyId = Auth::user()->company_id ?? (\App\Models\Company::first()->id ?? null);

        return Inertia::render('Accounting/PurchaseOrders/Create', [
            'purchaseOrder' => $purchaseOrder,
            'vendors' => Vendor::where('company_id', $companyId)->where('is_active', true)->where('verification_status', 'Verified')->get(),
            'projects' => Project::where('company_id', $companyId)->get(),
            'accounts' => ChartOfAccount::where('company_id', $companyId)->whereIn('type', ['expense', 'asset'])->get(),
            'isEditing' => true,
        ]);
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'Draft') {
            return redirect()->back()->with('error', 'Approved or billed POs cannot be modified.');
        }

        $validated = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'project_id' => 'nullable|exists:projects,id',
            'po_number' => 'required|string|unique:purchase_orders,po_number,' . $purchaseOrder->id,
            'po_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'tax_type' => 'required|in:VAT Inclusive,VAT Exclusive,Non-VAT',
            'ewt_rate' => 'required|numeric|min:0|max:15',
            'items' => 'required|array|min:1',
            'items.*.chart_of_account_id' => 'required|exists:chart_of_accounts,id',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated, $purchaseOrder) {
            $grossAmount = collect($validated['items'])->sum(fn($i) => $i['quantity'] * $i['unit_price']);
            
            $vatAmount = 0;
            $netOfVat = $grossAmount;

            if ($validated['tax_type'] === 'VAT Inclusive') {
                $netOfVat = $grossAmount / 1.12;
                $vatAmount = $grossAmount - $netOfVat;
            } elseif ($validated['tax_type'] === 'VAT Exclusive') {
                $vatAmount = $grossAmount * 0.12;
                $grossAmount = $grossAmount + $vatAmount;
            }

            $ewtAmount = $netOfVat * ($validated['ewt_rate'] / 100);
            $netAmount = $grossAmount - $ewtAmount;

            $purchaseOrder->update([
                'vendor_id' => $validated['vendor_id'],
                'project_id' => $validated['project_id'],
                'po_number' => $validated['po_number'],
                'po_date' => $validated['po_date'],
                'expected_delivery_date' => $validated['expected_delivery_date'],
                'total_amount' => $grossAmount,
                'tax_type' => $validated['tax_type'],
                'vat_amount' => $vatAmount,
                'ewt_rate' => $validated['ewt_rate'],
                'ewt_amount' => $ewtAmount,
                'net_amount' => $netAmount,
                'notes' => $validated['notes'],
            ]);

            // Simple sync: delete old, create new
            $purchaseOrder->items()->delete();
            foreach ($validated['items'] as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'chart_of_account_id' => $item['chart_of_account_id'],
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'amount' => $item['quantity'] * $item['unit_price'],
                ]);
            }

            LogActivity::log('Procurement', 'Updated', "Updated Purchase Order #{$purchaseOrder->po_number}", $purchaseOrder);
        });

        return redirect()->route('accounting.purchase-orders.index')->with('success', 'Purchase Order updated successfully.');
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'Draft') {
            return redirect()->back()->with('error', 'Only draft POs can be deleted.');
        }

        LogActivity::log('Procurement', 'Deleted', "Deleted Purchase Order #{$purchaseOrder->po_number} ({$purchaseOrder->id})");
        
        $purchaseOrder->delete();

        return redirect()->route('accounting.purchase-orders.index')->with('success', 'Purchase Order deleted.');
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['vendor', 'project', 'items.account', 'preparedBy', 'approvedBy', 'bills']);
        
        return Inertia::render('Accounting/PurchaseOrders/Show', [
            'purchaseOrder' => $purchaseOrder
        ]);
    }

    public function print(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['company', 'vendor', 'project', 'items.account', 'preparedBy', 'approvedBy']);
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.purchase-order', [
            'purchaseOrder' => $purchaseOrder,
        ]);

        return $pdf->stream("PO-{$purchaseOrder->po_number}.pdf");
    }

    public function approve(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'Draft') {
            return redirect()->back()->with('error', 'Only draft POs can be approved.');
        }

        $purchaseOrder->update([
            'status' => 'Approved',
            'approved_by' => Auth::id()
        ]);

        LogActivity::log('Procurement', 'Approved', "Approved Purchase Order #{$purchaseOrder->po_number}", $purchaseOrder);

        return redirect()->back()->with('success', 'Purchase Order approved.');
    }

    /**
     * Convert PO to Bill (Partial or Full Fulfillment)
     */
    public function convertToBill(Request $request, PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status === 'Draft' || $purchaseOrder->status === 'Cancelled') {
            return redirect()->back()->with('error', 'Only approved or partially billed POs can be converted.');
        }

        $validated = $request->validate([
            'bill_number' => 'required|string|unique:bills,bill_number',
            'bill_date' => 'required|date',
            'items' => 'required|array',
            'items.*.po_item_id' => 'required|exists:purchase_order_items,id',
            'items.*.quantity_to_bill' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($purchaseOrder, $validated) {
            $totalBillAmount = 0;
            $billItemsData = [];

            foreach ($validated['items'] as $itemData) {
                if ($itemData['quantity_to_bill'] <= 0) continue;

                $poItem = PurchaseOrderItem::find($itemData['po_item_id']);
                
                // Safety check
                if ($poItem->quantity_billed + $itemData['quantity_to_bill'] > $poItem->quantity) {
                    throw new \Exception("Billing quantity for '{$poItem->description}' exceeds remaining PO quantity.");
                }

                $amount = $itemData['quantity_to_bill'] * $poItem->unit_price;
                $totalBillAmount += $amount;

                $billItemsData[] = [
                    'chart_of_account_id' => $poItem->chart_of_account_id,
                    'description' => $poItem->description,
                    'amount' => $amount,
                ];

                // Update PO Item tracked quantity
                $poItem->increment('quantity_billed', $itemData['quantity_to_bill']);
            }

            if (empty($billItemsData)) {
                throw new \Exception("No items selected for billing.");
            }

            // Calculate Proportional Taxes for this specific bill
            $vatAmount = 0;
            $netOfVat = $totalBillAmount;

            if ($purchaseOrder->tax_type === 'VAT Inclusive') {
                $netOfVat = $totalBillAmount / 1.12;
                $vatAmount = $totalBillAmount - $netOfVat;
            } elseif ($purchaseOrder->tax_type === 'VAT Exclusive') {
                $vatAmount = $totalBillAmount * 0.12;
                $totalBillAmount += $vatAmount;
            }

            $ewtAmount = $netOfVat * ($purchaseOrder->ewt_rate / 100);
            $netAmount = $totalBillAmount - $ewtAmount;

            $bill = Bill::create([
                'company_id' => $purchaseOrder->company_id,
                'vendor_id' => $purchaseOrder->vendor_id,
                'purchase_order_id' => $purchaseOrder->id,
                'project_id' => $purchaseOrder->project_id,
                'type' => 'Bill',
                'bill_number' => $validated['bill_number'],
                'bill_date' => $validated['bill_date'],
                'due_date' => \Carbon\Carbon::parse($validated['bill_date'])->addDays(30),
                'total_amount' => $totalBillAmount,
                'vat_amount' => $vatAmount,
                'ewt_amount' => $ewtAmount,
                'net_amount' => $netAmount,
                'status' => 'Draft',
                'created_by' => Auth::id(),
                'notes' => 'Fulfillment for PO #' . $purchaseOrder->po_number,
            ]);

            foreach ($billItemsData as $item) {
                BillItem::create(array_merge($item, ['bill_id' => $bill->id]));
            }

            LogActivity::log('Procurement', 'Converted', "Converted PO #{$purchaseOrder->po_number} to Bill #{$bill->bill_number}", $bill);

            // Update PO Overall Status
            $totalOrdered = $purchaseOrder->items()->sum('quantity');
            $totalBilled = $purchaseOrder->items()->sum('quantity_billed');

            if ($totalBilled >= $totalOrdered) {
                $purchaseOrder->update(['status' => 'Billed']);
            } else {
                $purchaseOrder->update(['status' => 'Partially Billed']);
            }

            return redirect()->route('accounting.bills.show', $bill->id)->with('success', 'Bill generated successfully.');
        });
    }
}
