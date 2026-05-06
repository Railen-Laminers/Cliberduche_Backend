<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank you for contacting us</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f9f9f9;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .header {
            border-bottom: 3px solid #16a34a;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }

        .header h1 {
            color: #0b2545;
            margin: 0;
            font-size: 24px;
        }

        .message {
            margin-bottom: 20px;
        }

        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>✅ We received your message</h1>
        </div>
        <div class="message">
            <p>Dear {{ $data['name'] }},</p>
            <p>Thank you for contacting <strong>{{ config('app.name') }}</strong>.</p>
            <p>Your message with the subject <strong>"{{ $data['subject'] }}"</strong> has been received and is now
                under review. We will get back to you as soon as possible.</p>
            <p>For your reference, here is a copy of your message:</p>
            <blockquote style="background: #f8fafc; padding: 15px; border-left: 4px solid #16a34a; margin: 15px 0;">
                {{ $data['message'] }}
            </blockquote>
            <p>If you have any further questions, feel free to reply to this email.</p>
        </div>
        <div class="footer">
            <p>Best regards,<br>The {{ config('app.name') }} Team</p>
        </div>
    </div>
</body>

</html>