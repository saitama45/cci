<!DOCTYPE html>
<html>
<head>
    <title>AP Aging Report</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 10px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #444; padding-bottom: 10px; }
        .company-name { font-size: 18px; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; }
        .report-title { font-size: 14px; color: #666; font-weight: bold; }
        .period { font-size: 10px; color: #888; margin-top: 5px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #f2f2f2; font-weight: bold; padding: 8px 5px; border: 1px solid #ccc; text-align: left; text-transform: uppercase; font-size: 9px; }
        td { padding: 6px 5px; border: 1px solid #eee; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        
        .totals-row { background-color: #f9f9f9; font-weight: bold; }
        .totals-row td { border-top: 2px solid #444; border-bottom: 2px solid #444; }
        
        .footer { position: fixed; bottom: 0; width: 100%; text-align: right; font-size: 8px; color: #aaa; }
        .overdue { color: #dc2626; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $company->name ?? 'City Communities Inc.' }}</div>
        <div class="report-title">ACCOUNTS PAYABLE (AP) AGING REPORT</div>
        <div class="period">As of: {{ date('M d, Y', strtotime($asOfDate)) }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Vendor</th>
                <th>Bill #</th>
                <th>Project</th>
                <th class="text-center">Due Date</th>
                <th class="text-right">Current</th>
                <th class="text-right">1-30 Days</th>
                <th class="text-right">31-60 Days</th>
                <th class="text-right">61-90 Days</th>
                <th class="text-right">91+ Over</th>
                <th class="text-right">Total Unpaid</th>
            </tr>
        </thead>
        <tbody>
            @foreach($report_data as $row)
                <tr>
                    <td class="font-bold">{{ $row->vendor_name }}</td>
                    <td>{{ $row->bill_number }}</td>
                    <td>{{ $row->project_name }}</td>
                    <td class="text-center">
                        {{ $row->due_date != 'N/A' ? date('M d, Y', strtotime($row->due_date)) : 'N/A' }}
                        @if($row->days_overdue > 0)
                            <br><small class="overdue">{{ $row->days_overdue }} Days Overdue</small>
                        @endif
                    </td>
                    <td class="text-right">{{ $row->current > 0 ? number_format($row->current, 2) : '-' }}</td>
                    <td class="text-right">{{ $row->{'1_30'} > 0 ? number_format($row->{'1_30'}, 2) : '-' }}</td>
                    <td class="text-right">{{ $row->{'31_60'} > 0 ? number_format($row->{'31_60'}, 2) : '-' }}</td>
                    <td class="text-right">{{ $row->{'61_90'} > 0 ? number_format($row->{'61_90'}, 2) : '-' }}</td>
                    <td class="text-right">{{ $row->{'91_over'} > 0 ? number_format($row->{'91_over'}, 2) : '-' }}</td>
                    <td class="text-right font-bold">{{ number_format($row->unpaid_amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="totals-row">
                <td colspan="4" class="text-right">GRAND TOTALS</td>
                <td class="text-right">{{ number_format($totals['current'], 2) }}</td>
                <td class="text-right">{{ number_format($totals['1_30'], 2) }}</td>
                <td class="text-right">{{ number_format($totals['31_60'], 2) }}</td>
                <td class="text-right">{{ number_format($totals['61_90'], 2) }}</td>
                <td class="text-right">{{ number_format($totals['91_over'], 2) }}</td>
                <td class="text-right">{{ number_format($totals['total'], 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Generated on {{ date('Y-m-d H:i:s') }}
    </div>
</body>
</html>
