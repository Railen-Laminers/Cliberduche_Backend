<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $data['subject'] }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f9f9f9;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 650px;
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

        .field {
            margin-bottom: 20px;
            padding: 12px;
            background: #f8fafc;
            border-left: 4px solid #16a34a;
            border-radius: 0 4px 4px 0;
        }

        .label {
            display: block;
            font-weight: 600;
            color: #0b2545;
            margin-bottom: 6px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .value {
            margin: 0;
            color: #374151;
            font-size: 15px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .privacy-yes {
            color: #16a34a;
            font-weight: 600;
        }

        .privacy-no {
            color: #dc2626;
            font-weight: 600;
        }

        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
        }

        .footer a {
            color: #16a34a;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>📨 New Contact Form Submission</h1>
            <p style="margin: 8px 0 0; color: #6b7280;">{{ date('F j, Y \a\t g:i A') }}</p>
        </div>

        <div class="field">
            <span class="label">Subject</span>
            <p class="value">{{ e($data['subject']) }}</p>
        </div>

        <div class="field">
            <span class="label">Name</span>
            <p class="value">{{ e($data['name']) }}</p>
        </div>

        <div class="field">
            <span class="label">Email Address</span>
            <p class="value"><a href="mailto:{{ e($data['email']) }}"
                    style="color: #16a34a;">{{ e($data['email']) }}</a></p>
        </div>

        <div class="field">
            <span class="label">Message</span>
            <p class="value">{{ nl2br(e($data['message'])) }}</p>
        </div>

        <div class="field">
            <span class="label">✅ Privacy Policy Accepted</span>
            <p class="value {{ $data['privacyAccepted'] ? 'privacy-yes' : 'privacy-no' }}">
                {{ $data['privacyAccepted'] ? 'Yes – User agreed to send their information and be contacted.' : 'No – User did NOT accept the privacy policy.' }}
            </p>
        </div>

        <div class="footer">
            <p>This message was sent via the <strong>{{ config('app.name') }}</strong> contact form.</p>
            <p>Reply directly to: <a href="mailto:{{ e($data['email']) }}">{{ e($data['email']) }}</a></p>
        </div>
    </div>
</body>

</html>