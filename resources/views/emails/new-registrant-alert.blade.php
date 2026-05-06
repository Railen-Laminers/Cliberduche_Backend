<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New Client Registration</title>
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
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .info-table th, .info-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .info-table th {
            background: #e9ecef;
            font-weight: 600;
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
        <h1>New Client Registration</h1>
    </div>
    <div class="content">
        <p>Hello Admin,</p>
        
        <p>A new client has registered with Cliberduche Construction and is awaiting approval.</p>
        
        <table class="info-table">
            <tr>
                <th>Name</th>
                <td>{{ $registrant->firstname }} {{ $registrant->lastname }}</td>
            </tr>
            <tr>
                <th>Email</th>
                <td>{{ $registrant->email }}</td>
            </tr>
            <tr>
                <th>Contact Number</th>
                <td>{{ $registrant->contact_number }}</td>
            </tr>
            <tr>
                <th>Registration Date</th>
                <td>{{ $registrant->created_at->format('F j, Y g:i A') }}</td>
            </tr>
            <tr>
                <th>Status</th>
                <td><strong>Pending Review</strong></td>
            </tr>
        </table>
        
        <p>Please log in to the admin dashboard to review and approve this registration.</p>
        
        <p>Best regards,<br>Cliberduche System</p>
    </div>
    <div class="footer">
        <p>&copy; {{ date('Y') }} Cliberduche Construction. All rights reserved.</p>
    </div>
</body>
</html>

