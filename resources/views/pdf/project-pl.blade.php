<!DOCTYPE html>
<html>
<head>
    <title>Project P&L Report</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #444; padding-bottom: 10px; }
        .company-name { font-size: 20px; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; }
        .report-title { font-size: 16px; color: #666; font-weight: bold; }
        .period { font-size: 11px; color: #888; margin-top: 5px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background-color: #f2f2f2; font-weight: bold; padding: 10px; border: 1px solid #ccc; text-align: left; text-transform: uppercase; }
        td { padding: 8px 10px; border: 1px solid #eee; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        
        .revenue { color: #059669; font-weight: bold; }
        .expenses { color: #dc2626; font-weight: bold; }
        .profit { font-weight: bold; }
        
        .totals-row { background-color: #f9f9f9; font-weight: bold; }
        .totals-row td { border-top: 2px solid #444; border-bottom: 2px solid #444; }
        
        .footer { position: fixed; bottom: 0; width: 100%; text-align: right; font-size: 10px; color: #aaa; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $company->name ?? 'City Communities Inc.' }}</div>
        <div class="report-title">PROJECT PROFIT & LOSS REPORT</div>
        <div class="period">Period: {{ date('M d, Y', strtotime($startDate)) }} to {{ date('M d, Y', strtotime($endDate)) }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="40%">Project Name</th>
                <th width="20%" class="text-right">Revenue</th>
                <th width="20%" class="text-right">Expenses</th>
                <th width="20%" class="text-right">Net Profit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($projects as $project)
                <tr>
                    <td class="font-bold">{{ $project->name }}</td>
                    <td class="text-right revenue">{{ number_format($project->revenue, 2) }}</td>
                    <td class="text-right expenses">{{ number_format($project->expenses, 2) }}</td>
                    <td class="text-right profit">{{ number_format($project->net_profit, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="totals-row">
                <td class="text-right">GRAND TOTALS</td>
                <td class="text-right revenue">{{ number_format($totals['revenue'], 2) }}</td>
                <td class="text-right expenses">{{ number_format($totals['expenses'], 2) }}</td>
                <td class="text-right profit">{{ number_format($totals['net_profit'], 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Generated on {{ date('Y-m-d H:i:s') }}
    </div>
</body>
</html>
