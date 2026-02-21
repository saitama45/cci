<!DOCTYPE html>
<html>
<head>
    <title>Trial Balance</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #444; padding-bottom: 10px; }
        .company-name { font-size: 20px; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; }
        .report-title { font-size: 16px; color: #666; font-weight: bold; }
        .period { font-size: 11px; color: #888; margin-top: 5px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background-color: #f2f2f2; font-weight: bold; padding: 10px; border-bottom: 1px solid #ccc; text-align: left; }
        td { padding: 8px 10px; border-bottom: 1px solid #eee; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        
        .totals-row { background-color: #f9f9f9; font-weight: bold; }
        .totals-row td { border-top: 2px solid #444; border-bottom: 2px solid #444; }
        
        .footer { position: fixed; bottom: 0; width: 100%; text-align: right; font-size: 10px; color: #aaa; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $company->name ?? 'City Communities Inc.' }}</div>
        <div class="report-title">TRIAL BALANCE</div>
        <div class="period">Period: {{ date('M d, Y', strtotime($startDate)) }} to {{ date('M d, Y', strtotime($endDate)) }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="15%">Account Code</th>
                <th width="45%">Account Name</th>
                <th width="10%" class="text-center">Type</th>
                <th width="15%" class="text-right">Debit</th>
                <th width="15%" class="text-right">Credit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($accounts as $account)
                <tr>
                    <td>{{ $account->code }}</td>
                    <td>{{ $account->name }}</td>
                    <td class="text-center" style="text-transform: capitalize;">{{ $account->type }}</td>
                    <td class="text-right">
                        {{ $account->total_debit > 0 ? number_format($account->total_debit, 2) : '-' }}
                    </td>
                    <td class="text-right">
                        {{ $account->total_credit > 0 ? number_format($account->total_credit, 2) : '-' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="totals-row">
                <td colspan="3" class="text-right">GRAND TOTALS</td>
                <td class="text-right">{{ number_format($totals['debit'], 2) }}</td>
                <td class="text-right">{{ number_format($totals['credit'], 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Generated on {{ date('Y-m-d H:i:s') }}
    </div>
</body>
</html>
