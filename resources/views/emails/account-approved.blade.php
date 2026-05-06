<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Account Approved</title>
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
            background: linear-gradient(135deg, #22c55e, #16a34a);
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
        .success-icon {
            font-size: 48px;
            margin-bottom: 20px;
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
        .features {
            margin: 20px 0;
            padding: 15px;
            background: white;
            border-radius: 8px;
        }
        .features li {
            margin: 10px 0;
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
        <div class="success-icon">✓</div>
        <h1>Account Approved!</h1>
    </div>
    <div class="content">
        <p>Dear <strong>{{ $user->firstname }}</strong>,</p>
        
        <p>Great news! Your account has been approved and you now have full access to the Cliberduche Construction Client Portal.</p>
        
        <div class="features">
            <h3>What you can now do:</h3>
            <ul>
                <li>Book appointments for consultations</li>
                <li>View your assigned projects</li>
                <li>Communicate with our team via messages</li>
                <li>Receive real-time notifications</li>
                <li>Track project progress</li>
            </ul>
        </div>
        
        <p>Log in to your dashboard to get started:</p>
        
        <p style="text-align: center;">
            <a href="#" class="btn">Access Client Portal</a>
        </p>
        
        <p>If you have any questions, feel free to reach out to our team.</p>
        
        <p>Best regards,<br>The Cliberduche Team</p>
    </div>
    <div class="footer">
        <p>&copy; {{ date('Y') }} Cliberduche Construction. All rights reserved.</p>
    </div>
</body>
</html>

