<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Statement of Account - {{ $sale->contract_no }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #333; line-height: 1.4; }
        .header { text-align: center; margin-bottom: 30px; }
        .company-name { font-size: 18px; font-weight: bold; text-transform: uppercase; }
        .soa-title { font-size: 16px; font-weight: bold; margin-top: 10px; border-bottom: 2px solid #000; display: inline-block; padding: 0 20px; }
        
        .info-section { width: 100%; margin-bottom: 20px; }
        .info-box { width: 48%; display: inline-block; vertical-align: top; }
        
        .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table th { background-color: #f2f2f2; padding: 8px; border: 1px solid #ccc; text-align: left; text-transform: uppercase; font-size: 9px; }
        .table td { padding: 8px; border: 1px solid #eee; }
        
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        
        .summary-box { background-color: #f9f9f9; padding: 15px; border: 1px solid #ddd; margin-top: 20px; }
        .total-row { font-size: 14px; font-weight: bold; color: #b91c1c; }
        
        .footer { position: fixed; bottom: 30px; width: 100%; font-size: 9px; color: #777; }
        .notice { margin-top: 30px; font-style: italic; color: #666; font-size: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $sale->company->name ?? 'CCI REALTY' }}</div>
        <div class="soa-title">STATEMENT OF ACCOUNT</div>
    </div>

    <div class="info-section">
        <div class="info-box">
            <strong>BILL TO:</strong><br>
            <span style="font-size: 13px; font-weight: bold;">{{ $sale->customer->full_name }}</span><br>
            {{ $sale->customer->address ?? 'N/A' }}<br>
            Contact: {{ $sale->customer->contact_no ?? 'N/A' }}
        </div>
        <div class="info-box text-right">
            <strong>Contract No:</strong> {{ $sale->contract_no }}<br>
            <strong>Project:</strong> {{ $sale->unit->project->name ?? 'N/A' }}<br>
            <strong>Unit:</strong> {{ $sale->unit->name }}<br>
            <strong>Statement Date:</strong> {{ $asOfDate->format('M d, Y') }}
        </div>
    </div>

    @if($arrears->count() > 0)
    <h3 style="color: #b91c1c; border-bottom: 1px solid #fecaca; padding-bottom: 5px;">PAST DUE / ARREARS</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Due Date</th>
                <th>Description</th>
                <th class="text-right">Amount Due</th>
            </tr>
        </thead>
        <tbody>
            @foreach($arrears as $item)
            <tr>
                <td>{{ $item->due_date->format('M d, Y') }}</td>
                <td>{{ $item->type }} #{{ $item->installment_no }}</td>
                <td class="text-right">{{ number_format($item->amount_due, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="font-bold">
                <td colspan="2" class="text-right">TOTAL ARREARS</td>
                <td class="text-right">{{ number_format($arrears->sum('amount_due'), 2) }}</td>
            </tr>
        </tfoot>
    </table>
    @endif

    <h3 style="color: #1e40af; border-bottom: 1px solid #bfdbfe; padding-bottom: 5px;">CURRENT DUE</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Due Date</th>
                <th>Description</th>
                <th class="text-right">Amount Due</th>
            </tr>
        </thead>
        <tbody>
            @if($currentDue->count() > 0)
                @foreach($currentDue as $item)
                <tr>
                    <td>{{ $item->due_date->format('M d, Y') }}</td>
                    <td>{{ $item->type }} #{{ $item->installment_no }}</td>
                    <td class="text-right">{{ number_format($item->amount_due, 2) }}</td>
                </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="3" style="text-align: center; color: #999;">No payments due for this statement period.</td>
                </tr>
            @endif
        </tbody>
    </table>

    @if($futureDues->count() > 0)
    <h3 style="color: #475569; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px;">FUTURE INSTALLMENT SCHEDULE</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Due Date</th>
                <th>Description</th>
                <th class="text-right">Amount Due</th>
            </tr>
        </thead>
        <tbody>
            @foreach($futureDues as $item)
            <tr>
                <td>{{ $item->due_date->format('M d, Y') }}</td>
                <td>{{ $item->type }} #{{ $item->installment_no }}</td>
                <td class="text-right">{{ number_format($item->amount_due, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="font-bold">
                <td colspan="2" class="text-right">TOTAL FUTURE DUES</td>
                <td class="text-right">{{ number_format($futureDues->sum('amount_due'), 2) }}</td>
            </tr>
        </tfoot>
    </table>
    @endif

    <div class="summary-box">
        <table style="width: 100%;">
            <tr>
                <td class="font-bold">Immediate Payout (Arrears + Current):</td>
                <td class="text-right total-row">PHP {{ number_format($arrears->sum('amount_due') + $currentDue->sum('amount_due'), 2) }}</td>
            </tr>
            <tr>
                <td style="font-size: 10px; color: #666; padding-top: 5px;">Total Net Outstanding Balance:</td>
                <td class="text-right" style="font-size: 11px; color: #666; padding-top: 5px;">PHP {{ number_format($arrears->sum('amount_due') + $currentDue->sum('amount_due') + $futureDues->sum('amount_due'), 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="notice">
        <strong>Important Notice:</strong><br>
        1. Please pay on or before the due date to avoid penalties (3% per month).<br>
        2. Disregard this statement if payment has already been made.<br>
        3. Make all checks payable to <strong>{{ $sale->company->name ?? 'CCI REALTY' }}</strong>.
    </div>

    <div class="footer">
        This is a system-generated document. No signature is required.<br>
        Horizon ERP - Real Estate Management System | {{ now()->format('Y-m-d H:i:s') }}
    </div>
</body>
</html>
