<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Welcome to AI Lawyer</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f6f9fc; padding: 32px 16px; color: #1a1712;">
    <div style="max-width: 580px; margin: 0 auto; background: #ffffff; border-radius: 12px; border: 1px solid #e6ebf1; padding: 32px;">
        <h1 style="color: #0a2540; font-size: 24px; margin-top: 0;">Welcome to AI Lawyer, {{ $user->name }}!</h1>
        <p style="font-size: 15px; line-height: 1.6; color: #425466;">
            Your payment for the <strong>{{ $planName }}</strong> ({{ ucfirst($interval) }}) subscription has been successfully processed. Your account is now fully active.
        </p>

        <div style="background: #faf8f5; border: 1px solid #efe8da; border-radius: 8px; padding: 18px; margin: 24px 0;">
            <div style="font-size: 13px; text-transform: uppercase; color: #8a6a12; font-weight: 700; margin-bottom: 8px;">Subscription Summary</div>
            <div style="font-size: 14px; margin-bottom: 4px;"><strong>Plan:</strong> {{ $planName }} ({{ $amountLabel }})</div>
            <div style="font-size: 14px; margin-bottom: 4px;"><strong>Billing:</strong> {{ ucfirst($interval) }}</div>
            <div style="font-size: 14px; margin-bottom: 4px;"><strong>Account Type:</strong> {{ $user->account_type === 'firm' ? 'Law Firm Workspace' : 'Individual Lawyer' }}</div>
            <div style="font-size: 14px;"><strong>Email:</strong> {{ $user->email }}</div>
        </div>

        <div style="text-align: center; margin: 32px 0;">
            <a href="{{ route('login') }}" style="background: #0a2540; color: #ffffff; text-decoration: none; padding: 12px 28px; border-radius: 8px; font-weight: 600; display: inline-block;">
                Access Your Dashboard
            </a>
        </div>

        <p style="font-size: 13px; color: #8898aa; line-height: 1.5; margin-bottom: 0;">
            If you have any questions or need assistance setting up your AI Receptionist, feel free to reply to this email.
        </p>
    </div>
</body>
</html>
