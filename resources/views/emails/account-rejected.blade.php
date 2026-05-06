<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Account Rejected</title>
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
            background: linear-gradient(135deg, #dc2626, #b91c1c);
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
        .reason-box {
            background: #fee2e2;
            border-left: 4px solid #dc2626;
            padding: 15px;
            margin: 20px 0;
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
        <h1>Account Status Update</h1>
    </div>
    <div class="content">
        <p>Dear <strong>{{ $user->firstname }}</strong>,</p>
        
        <p>Thank you for your interest in Cliberduche Construction. After careful review, we regret to inform you that your account registration has been declined.</p>
        
        <div class="reason-box">
            <h3>Reason for Rejection:</h3>
            <p>{{ $user->rejection_reason }}</p>
        </div>
        
        <p><strong>What you can do:</strong></p>
        <ul>
            <li>Update your profile information</li>
            <li>Re-submit your valid ID documents</li>
            <li>Submit a new registration request</li>
        </ul>
        
        <p>If you believe this was a mistake or would like to provide additional information, please update your profile and resubmit for review.</p>
        
        <p>We appreciate your understanding.</p>
        
        <p>Best regards,<br>The Cliberduche Team</p>
    </div>
    <div class="footer">
        <p>&copy; {{ date('Y') }} Cliberduche Construction. All rights reserved.</p>
    </div>
</body>
</html>

