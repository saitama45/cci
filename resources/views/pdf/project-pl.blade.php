<!DOCTYPE html>
<html>
<head>
    <title>Project P&L Report</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 10px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #444; padding-bottom: 10px; }
        .company-name { font-size: 18px; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; }
        .report-title { font-size: 14px; color: #666; font-weight: bold; }
        .period { font-size: 10px; color: #888; margin-top: 5px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; table-layout: fixed; }
        th { background-color: #f2f2f2; font-weight: bold; padding: 8px 5px; border: 1px solid #ccc; text-align: left; text-transform: uppercase; font-size: 8px; }
        td { padding: 6px 5px; border: 1px solid #eee; overflow: hidden; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        
        .revenue { color: #059669; }
        .expenses { color: #dc2626; }
        .committed { color: #d97706; }
        .profit { font-weight: bold; }
        
        .totals-row { background-color: #f9f9f9; font-weight: bold; font-size: 9px; }
        .totals-row td { border-top: 2px solid #444; border-bottom: 2px solid #444; }
        
        .footer { position: fixed; bottom: 0; width: 100%; text-align: right; font-size: 8px; color: #aaa; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $company->name ?? 'CCI REALTY' }}</div>
        <div class="report-title">PROJECT PROFIT & LOSS (BUDGET VS ACTUAL)</div>
        <div class="period">Period: {{ date('M d, Y', strtotime($startDate)) }} to {{ date('M d, Y', strtotime($endDate)) }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 25%">Project Name</th>
                <th style="width: 12%" class="text-right">Budget</th>
                <th style="width: 12%" class="text-right">Revenue</th>
                <th style="width: 12%" class="text-right">Spent (Act)</th>
                <th style="width: 12%" class="text-right">Committed</th>
                <th style="width: 15%" class="text-right">Variance</th>
                <th style="width: 12%" class="text-center">Margin</th>
            </tr>
        </thead>
        <tbody>
            @foreach($projects as $project)
                <tr>
                    <td class="font-bold">{{ $project->name }}</td>
                    <td class="text-right">{{ number_format($project->budget, 2) }}</td>
                    <td class="text-right revenue">{{ number_format($project->revenue, 2) }}</td>
                    <td class="text-right expenses">{{ number_format($project->expenses, 2) }}</td>
                    <td class="text-right committed italic">{{ number_format($project->committed, 2) }}</td>
                    <td class="text-right font-bold">{{ number_format($project->variance, 2) }}</td>
                    <td class="text-center">
                        {{ $project->revenue > 0 ? number_format(($project->net_profit / $project->revenue) * 100, 2) . '%' : '0.00%' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="totals-row">
                <td class="text-right">GRAND TOTALS</td>
                <td class="text-right">{{ number_format($totals['budget'], 2) }}</td>
                <td class="text-right revenue">{{ number_format($totals['revenue'], 2) }}</td>
                <td class="text-right expenses">{{ number_format($totals['expenses'], 2) }}</td>
                <td class="text-right committed">{{ number_format($totals['committed'], 2) }}</td>
                <td class="text-right profit">{{ number_format($totals['net_profit'], 2) }}</td>
                <td class="text-center">
                    {{ $totals['revenue'] > 0 ? number_format(($totals['net_profit'] / $totals['revenue']) * 100, 2) . '%' : '-' }}
                </td>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top: 20px; font-size: 8px; color: #666;">
        <p><strong>Definition of Terms:</strong></p>
        <ul>
            <li><strong>Spent (Act):</strong> Actual costs from approved vendor bills.</li>
            <li><strong>Committed:</strong> Approved Purchase Orders that have not yet been billed.</li>
            <li><strong>Variance:</strong> Remaining budget (Budget - Spent - Committed).</li>
        </ul>
    </div>

    <div class="footer">
        Generated on {{ date('Y-m-d H:i:s') }} | Internal Financial Document
    </div>
</body>
</html>
