<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Check - {{ $disbursement->voucher_no }}</title>
    <style>
        body { 
            font-family: 'Courier New', Courier, monospace;
            font-size: 14px; 
            color: #000; 
            margin: 0;
            padding: 0;
            position: relative;
        }
        
        .date {
            position: absolute;
            top: {{ $config['date_top'] }}px; 
            right: {{ $config['date_right'] }}px;
            font-weight: bold;
            letter-spacing: 2px;
        }

        .payee {
            position: absolute;
            top: {{ $config['payee_top'] }}px; 
            left: {{ $config['payee_left'] }}px;
            font-weight: bold;
            width: 350px;
        }

        .amount-number {
            position: absolute;
            top: {{ $config['amount_top'] }}px; 
            right: {{ $config['amount_right'] }}px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .amount-words {
            position: absolute;
            top: {{ $config['words_top'] }}px; 
            left: {{ $config['words_left'] }}px;
            font-weight: bold;
            width: 500px;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="date">
        {{ $checkDate->format('m  d  Y') }}
    </div>

    <div class="payee">
        ** {{ strtoupper($disbursement->vendor->name) }} **
    </div>

    <div class="amount-number">
        ** {{ number_format($disbursement->total_amount, 2) }} **
    </div>

    <div class="amount-words">
        {{ $amountInWords }}
    </div>
</body>
</html>
