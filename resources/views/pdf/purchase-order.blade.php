<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Purchase Order - {{ $purchaseOrder->po_number }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #444; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 22px; color: #000; }
        .header p { margin: 5px 0; font-size: 10px; color: #666; }
        
        .info-table { width: 100%; margin-bottom: 20px; }
        .info-table td { padding: 5px; vertical-align: top; }
        .label { font-weight: bold; width: 150px; }
        
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .items-table th { background-color: #f2f2f2; border: 1px solid #ccc; padding: 8px; text-align: left; font-size: 10px; }
        .items-table td { border: 1px solid #ccc; padding: 8px; font-size: 11px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
        .total-section { margin-top: 10px; }
        
        /* Watermark */
        .watermark {
            position: fixed;
            top: 45%;
            left: 20%;
            transform: rotate(-45deg);
            font-size: 80px;
            color: rgba(0, 128, 0, 0.2); /* Slightly increased opacity */
            z-index: 9999; /* Moved to front layer */
            font-weight: bold;
            text-transform: uppercase;
            pointer-events: none;
        }
        
        .signature-section { width: 100%; margin-top: 80px; }
        .signature-box { width: 48%; display: inline-block; text-align: center; }
        .signature-line { border-top: 1px solid #000; width: 80%; margin: 0 auto 5px; }
        
        .footer { position: fixed; bottom: 0; width: 100%; font-size: 8px; text-align: center; color: #999; }
        .terms { margin-top: 20px; font-size: 10px; color: #666; border-top: 1px solid #eee; pt: 10px; }
    </style>
</head>
<body>
    @if($purchaseOrder->status === 'Approved' || $purchaseOrder->status === 'Billed' || $purchaseOrder->status === 'Partially Billed')
        <div class="watermark">APPROVED</div>
    @endif

    <div class="header">
        <h1>PURCHASE ORDER</h1>
        <p>{{ $purchaseOrder->company->name ?? 'CCI REALTY' }}</p>
        <p>{{ $purchaseOrder->company->address ?? '' }}</p>
    </div>

    <table class="info-table">
        <tr>
            <td class="label">Vendor:</td>
            <td><strong>{{ $purchaseOrder->vendor->name }}</strong><br>{{ $purchaseOrder->vendor->address }}</td>
            <td class="label">PO Number:</td>
            <td class="text-right"><strong>{{ $purchaseOrder->po_number }}</strong></td>
        </tr>
        <tr>
            <td class="label">Project:</td>
            <td>{{ $purchaseOrder->project->name ?? 'General Allocation' }}</td>
            <td class="label">Date:</td>
            <td class="text-right">{{ $purchaseOrder->po_date->format('M d, Y') }}</td>
        </tr>
        <tr>
            <td class="label">Expected Delivery:</td>
            <td>{{ $purchaseOrder->expected_delivery_date ? $purchaseOrder->expected_delivery_date->format('M d, Y') : 'N/A' }}</td>
            <td class="label">Status:</td>
            <td class="text-right">{{ $purchaseOrder->status }}</td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 40%">Description</th>
                <th style="width: 20%">Account</th>
                <th class="text-center" style="width: 10%">Qty</th>
                <th class="text-right" style="width: 15%">Unit Price</th>
                <th class="text-right" style="width: 15%">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchaseOrder->items as $item)
            <tr>
                <td>{{ $item->description }}</td>
                <td>{{ $item->account->code }}</td>
                <td class="text-center">{{ number_format($item->quantity, 2) }}</td>
                <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                <td class="text-right">{{ number_format($item->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" class="text-right">SUBTOTAL (GROSS)</th>
                <th class="text-right">{{ number_format($purchaseOrder->total_amount, 2) }}</th>
            </tr>
            <tr>
                <th colspan="4" class="text-right">VAT (12%)</th>
                <th class="text-right">{{ number_format($purchaseOrder->vat_amount, 2) }}</th>
            </tr>
            <tr>
                <th colspan="4" class="text-right">EWT ({{ $purchaseOrder->ewt_rate }}%)</th>
                <th class="text-right">({{ number_format($purchaseOrder->ewt_amount, 2) }})</th>
            </tr>
            <tr style="background-color: #eee;">
                <th colspan="4" class="text-right">GRAND TOTAL (NET PAY)</th>
                <th class="text-right"><strong>{{ number_format($purchaseOrder->net_amount, 2) }}</strong></th>
            </tr>
        </tfoot>
    </table>

    <div style="margin-bottom: 20px;">
        <strong>Notes:</strong><br>
        <p style="font-size: 10px; font-style: italic;">{{ $purchaseOrder->notes ?? 'No special instructions.' }}</p>
    </div>

    <div class="terms">
        <strong>Standard Terms & Conditions:</strong>
        <ol>
            <li>Please include the PO Number on all invoices and shipping documents.</li>
            <li>Items are subject to inspection and approval upon delivery.</li>
            <li>Notify us immediately if delivery cannot be made by the expected date.</li>
        </ol>
    </div>

    <table style="width: 100%; margin-top: 80px; border: none;">
        <tr>
            <td style="width: 50%; text-align: center; border: none; vertical-align: bottom;">
                <div style="border-top: 1px solid #000; width: 80%; margin: 0 auto 5px;"></div>
                <strong>Prepared By:</strong><br>{{ $purchaseOrder->preparedBy->name ?? 'N/A' }}
            </td>
            <td style="width: 50%; text-align: center; border: none; vertical-align: bottom;">
                <div style="border-top: 1px solid #000; width: 80%; margin: 0 auto 5px;"></div>
                <strong>Authorized Approval:</strong><br>{{ $purchaseOrder->approvedBy->name ?? 'Authorized Signatory' }}
            </td>
        </tr>
    </table>

    <div class="footer">
        Generated on {{ now()->format('Y-m-d H:i:s') }} | PO ID: {{ $purchaseOrder->id }} | Internal Document - CCI ERP
    </div>
</body>
</html>
