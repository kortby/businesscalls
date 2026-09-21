<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Subscription is Confirmed</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #1e293b; margin: 0; padding: 24px; line-height: 1.6;">
    <table align="center" width="100%" style="max-width: 600px; background-color: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);" cellpadding="0" cellspacing="0">
        <!-- Header -->
        <tr>
            <td style="background-color: #0f172a; padding: 32px 24px; text-align: center;">
                <h1 style="color: #ffffff; margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.5px;">JustMascot</h1>
                <p style="color: #94a3b8; margin: 6px 0 0 0; font-size: 14px;">AI Voice Receptionist & Smart Dispatch Platform</p>
            </td>
        </tr>

        <!-- Body Content -->
        <tr>
            <td style="padding: 32px 24px;">
                <div style="background-color: #ecfdf5; border-left: 4px solid #10b981; padding: 16px; border-radius: 6px; margin-bottom: 24px;">
                    <h2 style="color: #065f46; margin: 0 0 4px 0; font-size: 18px; font-weight: 700;">Subscription Activated! 🎉</h2>
                    <p style="color: #047857; margin: 0; font-size: 14px;">Thank you for subscribing to JustMascot. Your account has been upgraded.</p>
                </div>

                <p style="font-size: 15px; margin: 0 0 16px 0;">Hello <strong>{{ $tenant->name }}</strong> team,</p>
                <p style="font-size: 15px; margin: 0 0 24px 0;">We are thrilled to confirm that your <strong>{{ ucfirst($plan) }} Plan</strong> subscription is now active.</p>

                <!-- Plan & Invoice Summary Table -->
                <table width="100%" style="background-color: #f1f5f9; border-radius: 8px; margin-bottom: 24px;" cellpadding="12" cellspacing="0">
                    <tr>
                        <td style="color: #64748b; font-size: 13px; font-weight: 600; text-transform: uppercase;">Plan</td>
                        <td align="right" style="color: #0f172a; font-size: 14px; font-weight: 700;">{{ ucfirst($plan) }} Plan</td>
                    </tr>
                    @if($amount)
                    <tr>
                        <td style="color: #64748b; font-size: 13px; font-weight: 600; text-transform: uppercase; border-top: 1px solid #e2e8f0;">Amount Paid</td>
                        <td align="right" style="color: #0f172a; font-size: 14px; font-weight: 700; border-top: 1px solid #e2e8f0;">{{ $amount }}</td>
                    </tr>
                    @endif
                    @if($invoiceNumber)
                    <tr>
                        <td style="color: #64748b; font-size: 13px; font-weight: 600; text-transform: uppercase; border-top: 1px solid #e2e8f0;">Invoice #</td>
                        <td align="right" style="color: #0f172a; font-size: 14px; font-weight: 700; border-top: 1px solid #e2e8f0;">{{ $invoiceNumber }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td style="color: #64748b; font-size: 13px; font-weight: 600; text-transform: uppercase; border-top: 1px solid #e2e8f0;">Status</td>
                        <td align="right" style="color: #10b981; font-size: 14px; font-weight: 700; border-top: 1px solid #e2e8f0;">Paid & Active</td>
                    </tr>
                </table>

                @if($invoiceUrl || $invoicePdf)
                <!-- Invoice Actions -->
                <div style="text-align: center; margin-bottom: 28px;">
                    @if($invoiceUrl)
                    <a href="{{ $invoiceUrl }}" style="display: inline-block; background-color: #10b981; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: 700; font-size: 14px; margin: 4px;">View & Download Invoice</a>
                    @endif
                    @if($invoicePdf)
                    <a href="{{ $invoicePdf }}" style="display: inline-block; background-color: #334155; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: 700; font-size: 14px; margin: 4px;">Download PDF</a>
                    @endif
                </div>
                @endif

                <!-- Next Steps: Telephony Provisioning -->
                <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 18px; margin-bottom: 24px;">
                    <h3 style="color: #0f172a; margin: 0 0 8px 0; font-size: 15px; font-weight: 700;">📞 What Happens Next?</h3>
                    <p style="color: #475569; font-size: 13px; margin: 0 0 10px 0;">Our engineering and telephony team is currently provisioning your dedicated AI voice lines and routing configuration (Twilio & Vapi).</p>
                    <ul style="color: #475569; font-size: 13px; margin: 0; padding-left: 18px;">
                        <li style="margin-bottom: 4px;">Dedicated phone number assignment & assistant calibration</li>
                        <li style="margin-bottom: 4px;">Automated dispatch & CRM sync activation</li>
                        <li>You will receive an alert once your live voice line is tested and ready to take calls!</li>
                    </ul>
                </div>

                <div style="text-align: center; margin-top: 24px;">
                    <a href="{{ url('/dashboard') }}" style="display: inline-block; color: #2563eb; text-decoration: underline; font-size: 14px; font-weight: 600;">Go to Your JustMascot Dashboard &rarr;</a>
                </div>
            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td style="background-color: #f1f5f9; padding: 20px 24px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0;">
                <p style="margin: 0 0 4px 0;">Questions? Reply directly to this email or contact support at <a href="mailto:support@justmascot.com" style="color: #2563eb;">support@justmascot.com</a>.</p>
                <p style="margin: 0;">&copy; {{ date('Y') }} JustMascot. All rights reserved.</p>
            </td>
        </tr>
    </table>
</body>
</html>
