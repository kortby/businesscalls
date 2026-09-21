<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Urgent: New Subscriber - Configure Twilio & Vapi</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #0f172a; color: #334155; margin: 0; padding: 24px; line-height: 1.6;">
    <table align="center" width="100%" style="max-width: 600px; background-color: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);" cellpadding="0" cellspacing="0">
        <!-- Header -->
        <tr>
            <td style="background-color: #dc2626; padding: 24px; text-align: center;">
                <span style="background-color: #fef2f2; color: #dc2626; font-size: 11px; font-weight: 800; text-transform: uppercase; padding: 4px 10px; border-radius: 9999px; letter-spacing: 1px;">Action Required ASAP</span>
                <h1 style="color: #ffffff; margin: 10px 0 0 0; font-size: 20px; font-weight: 800;">🚨 New Paid Subscriber</h1>
                <p style="color: #fee2e2; margin: 4px 0 0 0; font-size: 14px;">Configure Twilio Phone Number & Vapi Assistant</p>
            </td>
        </tr>

        <!-- Content -->
        <tr>
            <td style="padding: 28px 24px;">
                <p style="margin: 0 0 16px 0; font-size: 15px; color: #0f172a;">
                    A customer has just completed their subscription payment. Please configure their telephony setup as soon as possible.
                </p>

                <!-- Tenant & Subscriber Details -->
                <table width="100%" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 24px;" cellpadding="12" cellspacing="0">
                    <tr>
                        <td style="color: #64748b; font-size: 13px; font-weight: 600; width: 40%;">Organization</td>
                        <td style="color: #0f172a; font-size: 14px; font-weight: 700;">{{ $tenant->name }} (ID: #{{ $tenant->id }})</td>
                    </tr>
                    <tr>
                        <td style="color: #64748b; font-size: 13px; font-weight: 600; border-top: 1px solid #e2e8f0;">Plan Subscribed</td>
                        <td style="color: #059669; font-size: 14px; font-weight: 800; border-top: 1px solid #e2e8f0;">{{ ucfirst($plan) }} Plan @if($amount) ({{ $amount }}) @endif</td>
                    </tr>
                    @if($customerEmail)
                    <tr>
                        <td style="color: #64748b; font-size: 13px; font-weight: 600; border-top: 1px solid #e2e8f0;">Customer Email</td>
                        <td style="color: #0f172a; font-size: 14px; font-weight: 600; border-top: 1px solid #e2e8f0;">
                            <a href="mailto:{{ $customerEmail }}" style="color: #2563eb; text-decoration: none;">{{ $customerEmail }}</a>
                        </td>
                    </tr>
                    @endif
                    @if($customerPhone)
                    <tr>
                        <td style="color: #64748b; font-size: 13px; font-weight: 600; border-top: 1px solid #e2e8f0;">Customer Phone</td>
                        <td style="color: #0f172a; font-size: 14px; font-weight: 600; border-top: 1px solid #e2e8f0;">{{ $customerPhone }}</td>
                    </tr>
                    @endif
                    @if($stripeCustomerId)
                    <tr>
                        <td style="color: #64748b; font-size: 13px; font-weight: 600; border-top: 1px solid #e2e8f0;">Stripe Customer</td>
                        <td style="color: #0f172a; font-size: 13px; font-family: monospace; border-top: 1px solid #e2e8f0;">{{ $stripeCustomerId }}</td>
                    </tr>
                    @endif
                    @if($invoiceNumber)
                    <tr>
                        <td style="color: #64748b; font-size: 13px; font-weight: 600; border-top: 1px solid #e2e8f0;">Invoice Number</td>
                        <td style="color: #0f172a; font-size: 13px; font-family: monospace; border-top: 1px solid #e2e8f0;">{{ $invoiceNumber }}</td>
                    </tr>
                    @endif
                </table>

                <!-- Action Checklist -->
                <div style="background-color: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 18px; margin-bottom: 24px;">
                    <h3 style="color: #92400e; margin: 0 0 10px 0; font-size: 14px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">⚡ Required Setup Checklist</h3>
                    <ul style="color: #78350f; font-size: 13px; margin: 0; padding-left: 18px; line-height: 1.8;">
                        <li><strong>Twilio:</strong> Provision or allocate dedicated Phone Number for area code.</li>
                        <li><strong>Vapi:</strong> Bind voice assistant ID & dispatch webhook endpoint.</li>
                        <li><strong>Settings:</strong> Update Tenant telephony settings via Admin Panel or Tinker.</li>
                        <li><strong>Test:</strong> Perform end-to-end inbound test call and confirm calendar dispatch.</li>
                    </ul>
                </div>

                <!-- Admin Action Button -->
                <div style="text-align: center; margin: 24px 0 8px 0;">
                    <a href="{{ url('/admin/tenants') }}" style="display: inline-block; background-color: #0f172a; color: #ffffff; text-decoration: none; padding: 14px 28px; border-radius: 8px; font-weight: 700; font-size: 14px;">Open Admin Panel &rarr;</a>
                </div>
            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td style="background-color: #f1f5f9; padding: 16px 24px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0;">
                <p style="margin: 0;">Automated System Alert &bull; JustMascot Ops</p>
            </td>
        </tr>
    </table>
</body>
</html>
