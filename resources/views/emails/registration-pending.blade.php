<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registration Pending</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #0b2545, #1f7a8c);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }
        .btn {
            display: inline-block;
            background: #22c55e;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Welcome to Cliberduche Construction!</h1>
    </div>
    <div class="content">
        <p>Dear <strong>{{ $user->firstname }}</strong>,</p>
        
        <p>Thank you for registering with Cliberduche Construction. Your account has been created successfully!</p>
        
        <p><strong>Status:</strong> Pending Approval</p>
        
        <p>Your registration is currently under review by our administrative team. This process typically takes 1-2 business days. Once your account is approved, you will receive full access to our client portal.</p>
        
        <p>During the pending period, you can:</p>
        <ul>
            <li>View your profile information</li>
            <li>Update your contact details</li>
            <li>Re-submit your valid ID if needed</li>
        </ul>
        
        <p>If you have any questions, please don't hesitate to contact us.</p>
        
        <p>Best regards,<br>The Cliberduche Team</p>
    </div>
    <div class="footer">
        <p>&copy; {{ date('Y') }} Cliberduche Construction. All rights reserved.</p>
    </div>
</body>
</html>

