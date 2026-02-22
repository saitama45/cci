<!DOCTYPE html>
<html>
<head>
    <title>Overall Receivables Summary</title>
    <style>
        @page { margin: 1cm; }
        body { font-family: 'Helvetica', sans-serif; font-size: 10px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #444; padding-bottom: 10px; }
        .company-name { font-size: 18px; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; }
        .report-title { font-size: 14px; color: #666; font-weight: bold; }
        .period { font-size: 10px; color: #888; margin-top: 5px; }
        
        .section-title { font-size: 12px; font-weight: bold; margin-bottom: 10px; text-transform: uppercase; color: #1e293b; background: #f1f5f9; padding: 8px; border-left: 4px solid #334155; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { background-color: #f8fafc; font-weight: bold; padding: 10px 8px; border: 1px solid #e2e8f0; text-align: center; font-size: 9px; text-transform: uppercase; }
        td { padding: 10px 8px; border: 1px solid #f1f5f9; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        
        .totals-row { font-weight: bold; background-color: #0f172a; color: white; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: right; font-size: 8px; color: #aaa; }
        
        .report-container { margin-bottom: 40px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $company->name ?? 'City Communities Inc.' }}</div>
        <div class="report-title">OVERALL RECEIVABLES SUMMARY</div>
        <div class="period">For the month as of {{ date('F d, Y', strtotime($asOfDate)) }}</div>
    </div>

    <div class="report-container">
        <div class="section-title">Overall Outstanding Receivables</div>
        <table>
            <thead>
                <tr>
                    <th class="text-left">Aging Bracket</th>
                    <th class="text-right">Outstanding Balance</th>
                    <th class="text-center"># of Accounts</th>
                    <th class="text-right">%</th>
                </tr>
            </thead>
            <tbody>
                @foreach($outstanding_report as $bracket => $data)
                    <tr>
                        <td class="text-left font-bold">{{ $bracket }}</td>
                        <td class="text-right">{{ number_format($data['amount'], 2) }}</td>
                        <td class="text-center">{{ $data['count'] }}</td>
                        <td class="text-right">{{ $summary['total_outstanding'] > 0 ? number_format(($data['amount'] / $summary['total_outstanding']) * 100, 2) : '0.00' }}%</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="totals-row">
                    <td class="text-left">TOTAL OUTSTANDING BALANCE</td>
                    <td class="text-right">{{ number_format($summary['total_outstanding'], 2) }}</td>
                    <td class="text-center">{{ $summary['outstanding_accounts'] }}</td>
                    <td class="text-right">100.00%</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="report-container">
        <div class="section-title">Overall Installment Due</div>
        <table>
            <thead>
                <tr>
                    <th class="text-left">Aging Bracket</th>
                    <th class="text-right">Installment Due</th>
                    <th class="text-center"># of Items</th>
                    <th class="text-right">%</th>
                </tr>
            </thead>
            <tbody>
                @foreach($installment_report as $bracket => $data)
                    <tr>
                        <td class="text-left font-bold">{{ $bracket }}</td>
                        <td class="text-right">{{ number_format($data['amount'], 2) }}</td>
                        <td class="text-center">{{ $data['count'] }}</td>
                        <td class="text-right">{{ $summary['total_installment'] > 0 ? number_format(($data['amount'] / $summary['total_installment']) * 100, 2) : '0.00' }}%</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="totals-row">
                    <td class="text-left">TOTAL INSTALLMENT DUE</td>
                    <td class="text-right">{{ number_format($summary['total_installment'], 2) }}</td>
                    <td class="text-center">{{ $summary['installment_accounts'] }}</td>
                    <td class="text-right">100.00%</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="footer">
        Generated on {{ date('Y-m-d H:i:s') }}
    </div>
</body>
</html>
