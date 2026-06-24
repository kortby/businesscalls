<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice - {{ $invoice->billing_period }}</title>
    <style>
        body {
            font-family: 'Outfit', 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: #ffffff;
            color: #3c3c3c;
            margin: 0;
            padding: 40px;
        }
        .invoice-card {
            border: 3px solid #e5e7eb;
            border-bottom: 7px solid #e5e7eb;
            border-radius: 24px;
            padding: 36px;
            max-width: 650px;
            margin: 0 auto;
            position: relative;
            background-color: #ffffff;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #f1f5f9;
            padding-bottom: 24px;
            margin-bottom: 24px;
        }
        .logo {
            font-size: 24px;
            font-weight: 900;
            color: #1cb0f6; /* Duolingo Blue */
            text-transform: uppercase;
            letter-spacing: -0.5px;
        }
        .invoice-title {
            font-size: 14px;
            font-weight: 800;
            color: #afafaf;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-align: right;
        }
        .period-badge {
            display: inline-block;
            background-color: #ddf4ff;
            color: #1899d6;
            border: 2px solid #84d8ff;
            border-bottom: 4px solid #84d8ff;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 800;
            padding: 6px 14px;
            text-transform: uppercase;
            margin-top: 4px;
        }
        .tenant-details {
            margin-bottom: 30px;
        }
        .tenant-name {
            font-size: 20px;
            font-weight: 900;
            color: #3c3c3c;
            margin: 0 0 6px 0;
        }
        .tenant-plan {
            font-size: 12px;
            font-weight: 800;
            color: #ff9600; /* Duolingo Orange */
            text-transform: uppercase;
            background-color: #fff4e5;
            padding: 3px 8px;
            border-radius: 8px;
            border: 1px solid #ffe3c2;
        }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 30px;
        }
        th {
            font-size: 11px;
            font-weight: 800;
            color: #afafaf;
            text-transform: uppercase;
            text-align: left;
            padding: 12px 16px;
            border-bottom: 3px solid #e5e7eb;
        }
        td {
            padding: 16px;
            font-size: 13px;
            font-weight: 700;
            border-bottom: 2px solid #f1f5f9;
        }
        .number-col {
            text-align: right;
        }
        .summary-box {
            background-color: #f7f9fa;
            border: 3px solid #e5e7eb;
            border-bottom: 6px solid #e5e7eb;
            border-radius: 20px;
            padding: 20px;
            margin-top: 20px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            font-weight: 800;
            color: #777777;
            margin-bottom: 10px;
        }
        .summary-row:last-child {
            margin-bottom: 0;
        }
        .total-row {
            border-top: 3px dashed #e5e7eb;
            padding-top: 14px;
            margin-top: 14px;
            display: flex;
            justify-content: space-between;
            font-size: 22px;
            font-weight: 900;
            color: #58cc02; /* Duolingo Green */
        }
        .footer {
            text-align: center;
            font-size: 11px;
            font-weight: 700;
            color: #afafaf;
            margin-top: 40px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>
    <div class="invoice-card">
        @if(isset($invoice) && $invoice->is_test_mode)
            <div style="background-color: #fee2e2; color: #991b1b; border: 2px solid #f87171; border-radius: 12px; padding: 10px; font-weight: 800; text-align: center; margin-bottom: 20px; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px;">
                *** TEST MODE / SANDBOX INVOICE ***
            </div>
        @endif
        <div class="header">
            <div class="logo">BusinessCalls</div>
            <div>
                <div class="invoice-title">Invoice</div>
                <div class="period-badge">{{ $invoice->billing_period }}</div>
            </div>
        </div>

        <div class="tenant-details">
            <h3 class="tenant-name">{{ $tenant->name }}</h3>
            <span class="tenant-plan">{{ $tenant->plan }} Plan</span>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Billing Item</th>
                    <th class="number-col">Usage</th>
                    <th class="number-col">Unit Rate</th>
                    <th class="number-col">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Base SaaS Subscription Fee</td>
                    <td class="number-col">1 Month</td>
                    <td class="number-col">${{ number_format($invoice->base_amount, 2) }}</td>
                    <td class="number-col">${{ number_format($invoice->base_amount, 2) }}</td>
                </tr>
                <tr>
                    <td>Voice Agent Minute Usage</td>
                    <td class="number-col">{{ number_format($invoice->total_duration_minutes, 2) }} mins</td>
                    <td class="number-col">${{ number_format($rate_per_minute, 2) }}/min</td>
                    <td class="number-col">${{ number_format($invoice->usage_amount, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="summary-box">
            <div class="summary-row">
                <span>Base Plan Amount:</span>
                <span>${{ number_format($invoice->base_amount, 2) }}</span>
            </div>
            <div class="summary-row">
                <span>Usage Amount:</span>
                <span>${{ number_format($invoice->usage_amount, 2) }}</span>
            </div>
            <div class="summary-row text-xs">
                <span>Total Calls Logged:</span>
                <span>{{ $invoice->total_calls_count }}</span>
            </div>
            <div class="total-row">
                <span>Total Balance:</span>
                <span>${{ number_format($invoice->total_amount, 2) }}</span>
            </div>
        </div>

        <div class="footer">
            Thank you for partnering with BusinessCalls!
        </div>
    </div>
</body>
</html>
