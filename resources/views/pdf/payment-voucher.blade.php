<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment Voucher - {{ $disbursement->voucher_no }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #444; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 22px; color: #000; }
        .header p { margin: 5px 0; font-size: 10px; color: #666; }
        
        .info-table { width: 100%; margin-bottom: 20px; }
        .info-table td { padding: 5px; vertical-align: top; }
        .label { font-weight: bold; width: 150px; }
        
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .items-table th { background-color: #f2f2f2; border: 1px solid #ccc; padding: 8px; text-align: left; }
        .items-table td { border: 1px solid #ccc; padding: 8px; }
        .text-right { text-align: right; }
        
        .total-section { margin-top: 10px; }
        .amount-in-words { font-style: italic; margin-bottom: 20px; padding: 10px; background-color: #f9f9f9; border: 1px solid #eee; }
        
        .signature-section { width: 100%; margin-top: 50px; }
        .signature-box { width: 30%; display: inline-block; text-align: center; }
        .signature-line { border-top: 1px solid #000; width: 80%; margin: 40px auto 5px; }
        
        .footer { position: fixed; bottom: 0; width: 100%; font-size: 8px; text-align: center; color: #999; }
        .pdc-badge { border: 2px solid #e53e3e; color: #e53e3e; padding: 5px; font-weight: bold; display: inline-block; transform: rotate(-10deg); margin-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>PAYMENT VOUCHER</h1>
        <p>{{ $disbursement->company->name ?? 'CCI REALTY' }}</p>
        <p>{{ $disbursement->company->address ?? '' }}</p>
    </div>

    <table class="info-table">
        <tr>
            <td class="label">Payee:</td>
            <td><strong>{{ $disbursement->vendor->name }}</strong><br>{{ $disbursement->vendor->address }}</td>
            <td class="label">Voucher No:</td>
            <td class="text-right"><strong>{{ $disbursement->voucher_no }}</strong></td>
        </tr>
        <tr>
            <td class="label">Date:</td>
            <td>{{ $disbursement->payment_date->format('M d, Y') }}</td>
            <td class="label">Payment Method:</td>
            <td class="text-right">{{ $disbursement->payment_method }}</td>
        </tr>
        <tr>
            <td class="label">Bank Account:</td>
            <td>{{ $disbursement->bankAccount->name ?? 'N/A' }}</td>
            <td class="label">Status:</td>
            <td class="text-right">{{ $disbursement->status }}</td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th>Bill Number</th>
                <th>Description</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($disbursement->items as $item)
            <tr>
                <td>{{ $item->bill->bill_number }}</td>
                <td>{{ $item->bill->notes }}</td>
                <td class="text-right">{{ number_format($item->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="2" class="text-right">TOTAL AMOUNT (PHP)</th>
                <th class="text-right">{{ number_format($disbursement->total_amount, 2) }}</th>
            </tr>
        </tfoot>
    </table>

    <div class="amount-in-words">
        <strong>Amount in Words:</strong> {{ $amountInWords }} Pesos Only
    </div>

    @if($disbursement->payment_method === 'PDC' && $disbursement->pdcDetail)
    <div style="border: 1px solid #ccc; padding: 10px; background: #fffaf0;">
        <h3 style="margin-top: 0;">PDC Details</h3>
        <table style="width: 100%;">
            <tr>
                <td><strong>Check No:</strong> {{ $disbursement->pdcDetail->check_no }}</td>
                <td><strong>Bank:</strong> {{ $disbursement->pdcDetail->bank_name }}</td>
                <td><strong>Maturity Date:</strong> {{ $disbursement->pdcDetail->check_date->format('M d, Y') }}</td>
            </tr>
        </table>
    </div>
    @endif

    <table style="width: 100%; margin-top: 80px; border: none;">
        <tr>
            <td style="width: 33%; text-align: center; border: none; vertical-align: bottom;">
                <div style="border-top: 1px solid #000; width: 80%; margin: 0 auto 5px;"></div>
                <strong>Prepared By:</strong><br>{{ $disbursement->preparedBy->name ?? 'N/A' }}
            </td>
            <td style="width: 33%; text-align: center; border: none; vertical-align: bottom;">
                <div style="border-top: 1px solid #000; width: 80%; margin: 0 auto 5px;"></div>
                <strong>Reviewed By:</strong><br>{{ $disbursement->reviewedBy->name ?? 'Internal Audit' }}
            </td>
            <td style="width: 33%; text-align: center; border: none; vertical-align: bottom;">
                <div style="border-top: 1px solid #000; width: 80%; margin: 0 auto 5px;"></div>
                <strong>Approved By:</strong><br>{{ $disbursement->approvedBy->name ?? 'Authorized Signatory' }}
            </td>
        </tr>
    </table>

    <div class="footer">
        Generated on {{ now()->format('Y-m-d H:i:s') }} | Voucher ID: {{ $disbursement->id }} | Internal Document - CCI ERP
    </div>
</body>
</html>
