<!DOCTYPE html>
<html>
<head>
    <title>General Ledger</title>
    <style>
        @page { margin: 1cm; }
        body { font-family: 'Helvetica', sans-serif; font-size: 10px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #444; padding-bottom: 10px; }
        .company-name { font-size: 18px; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; }
        .report-title { font-size: 14px; color: #666; font-weight: bold; }
        .period { font-size: 10px; color: #888; margin-top: 5px; }
        
        .account-section { margin-top: 25px; page-break-inside: avoid; }
        .account-header { background-color: #333; color: white; padding: 5px 10px; font-weight: bold; font-size: 11px; }
        
        table { width: 100%; border-collapse: collapse; }
        th { background-color: #f2f2f2; font-weight: bold; padding: 6px 8px; border-bottom: 1px solid #ccc; text-align: left; }
        td { padding: 5px 8px; border-bottom: 1px solid #eee; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        
        .totals-row { font-weight: bold; border-top: 1px solid #999; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: right; font-size: 8px; color: #aaa; }
        .memo { color: #666; font-style: italic; font-size: 9px; margin-top: 2px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $company->name ?? 'City Communities Inc.' }}</div>
        <div class="report-title">GENERAL LEDGER</div>
        <div class="period">Period: {{ date('M d, Y', strtotime($startDate)) }} to {{ date('M d, Y', strtotime($endDate)) }}</div>
    </div>

    @foreach($groupedLedger as $accountName => $data)
        <div class="account-section">
            <div class="account-header">{{ $accountName }}</div>
            <table>
                <thead>
                    <tr>
                        <th width="12%">Date</th>
                        <th width="15%">Reference #</th>
                        <th width="33%">Description</th>
                        <th width="13%" class="text-right">Debit</th>
                        <th width="13%" class="text-right">Credit</th>
                        <th width="14%" class="text-right">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['lines'] as $line)
                        <tr>
                            <td>{{ date('Y-m-d', strtotime($line->journalEntry->transaction_date)) }}</td>
                            <td class="font-bold">{{ $line->journalEntry->reference_no ?? '-' }}</td>
                            <td>
                                <div>{{ $line->journalEntry->description }}</div>
                                @if($line->memo)
                                    <div class="memo">{{ $line->memo }}</div>
                                @endif
                            </td>
                            <td class="text-right">{{ $line->debit > 0 ? number_format($line->debit, 2) : '-' }}</td>
                            <td class="text-right">{{ $line->credit > 0 ? number_format($line->credit, 2) : '-' }}</td>
                            <td class="text-right font-bold">{{ number_format($line->current_balance, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="totals-row">
                        <td colspan="3" class="text-right">ACCOUNT TOTALS & NET BALANCE</td>
                        <td class="text-right">{{ number_format($data['total_debit'], 2) }}</td>
                        <td class="text-right">{{ number_format($data['total_credit'], 2) }}</td>
                        <td class="text-right">{{ number_format($data['running_balance'], 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endforeach

    <div class="footer">
        Generated on {{ date('Y-m-d H:i:s') }}
    </div>
</body>
</html>
