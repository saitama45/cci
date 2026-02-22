<!DOCTYPE html>
<html>
<head>
    <title>Accounts Aging Report</title>
    <style>
        @page { margin: 1cm; }
        body { font-family: 'Helvetica', sans-serif; font-size: 9px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #444; padding-bottom: 10px; }
        .company-name { font-size: 16px; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; }
        .report-title { font-size: 14px; color: #666; font-weight: bold; }
        .period { font-size: 10px; color: #888; margin-top: 5px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #f2f2f2; font-weight: bold; padding: 6px 4px; border: 1px solid #ccc; text-align: center; font-size: 8px; }
        td { padding: 5px 4px; border: 1px solid #eee; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        
        .totals-row { font-weight: bold; background-color: #f9fafb; border-top: 2px solid #444; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: right; font-size: 8px; color: #aaa; }
        
        .bracket-badge { padding: 2px 4px; border-radius: 3px; font-size: 7px; font-weight: bold; text-transform: uppercase; }
        .bg-emerald { background-color: #ecfdf5; color: #065f46; }
        .bg-amber { background-color: #fffbeb; color: #92400e; }
        .bg-orange { background-color: #fff7ed; color: #9a3412; }
        .bg-rose { background-color: #fff1f2; color: #9f1239; }
        .bg-red { background-color: #fef2f2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $company->name ?? 'City Communities Inc.' }}</div>
        <div class="report-title">ACCOUNTS AGING REPORT</div>
        <div class="period">As of: {{ date('M d, Y', strtotime($asOfDate)) }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="3%">#</th>
                <th width="15%" class="text-left">Customer Name</th>
                <th width="15%" class="text-left">Unit / Lot</th>
                <th width="8%">Last Pay</th>
                <th width="8%" class="text-right">Not Yet Due</th>
                <th width="7%" class="text-right">1-30</th>
                <th width="7%" class="text-right">31-60</th>
                <th width="7%" class="text-right">61-90</th>
                <th width="7%" class="text-right">91-120</th>
                <th width="7%" class="text-right">120+</th>
                <th width="8%" class="text-right">Total Due</th>
                <th width="8%" class="text-right">Outstanding</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reportData as $index => $row)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-left font-bold">{{ $row->customer_name }}</td>
                    <td class="text-left">{{ $row->unit_name }}</td>
                    <td class="text-center">{{ $row->last_pay_date != 'No Payment' ? date('Y-m-d', strtotime($row->last_pay_date)) : '' }}</td>
                    <td class="text-right" style="color: #999;">{{ number_format($row->not_yet_due, 2) }}</td>
                    <td class="text-right font-bold">{{ $row->{'1_30'} > 0 ? number_format($row->{'1_30'}, 2) : '-' }}</td>
                    <td class="text-right font-bold">{{ $row->{'31_60'} > 0 ? number_format($row->{'31_60'}, 2) : '-' }}</td>
                    <td class="text-right font-bold">{{ $row->{'61_90'} > 0 ? number_format($row->{'61_90'}, 2) : '-' }}</td>
                    <td class="text-right font-bold">{{ $row->{'91_120'} > 0 ? number_format($row->{'91_120'}, 2) : '-' }}</td>
                    <td class="text-right font-bold">{{ $row->{'120_over'} > 0 ? number_format($row->{'120_over'}, 2) : '-' }}</td>
                    <td class="text-right font-bold" style="background-color: #fef2f2;">{{ number_format($row->total_due, 2) }}</td>
                    <td class="text-right font-bold">{{ number_format($row->outstanding_balance, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="totals-row">
                <td colspan="4" class="text-right font-bold">GRAND TOTALS</td>
                <td class="text-right">{{ number_format($totals['not_yet_due'], 2) }}</td>
                <td class="text-right">{{ number_format($totals['1_30'], 2) }}</td>
                <td class="text-right">{{ number_format($totals['31_60'], 2) }}</td>
                <td class="text-right">{{ number_format($totals['61_90'], 2) }}</td>
                <td class="text-right">{{ number_format($totals['91_120'], 2) }}</td>
                <td class="text-right">{{ number_format($totals['120_over'], 2) }}</td>
                <td class="text-right" style="background-color: #fee2e2;">{{ number_format($totals['total_due'], 2) }}</td>
                <td class="text-right">{{ number_format($totals['outstanding_balance'], 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Generated on {{ date('Y-m-d H:i:s') }}
    </div>
</body>
</html>
