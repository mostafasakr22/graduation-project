<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Mail</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
        }
        .container {
            max-width: 500px;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .otp {
            font-size: 24px;
            font-weight: bold;
            color: #2d89ff;
            background-color: #e8f0fe;
            padding: 10px;
            border-radius: 5px;
            display: inline-block;
            margin: 10px 0;
        }
        .footer {
            font-size: 12px;
            color: #888;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Reset Your Password</h2>
        <p>Hello,</p>
        <p>Your OTP code for password reset is:</p>
        <p class="otp">{{ $otp }}</p>
        <p>This code will expire in **10 minutes**.</p>
        <p>If you didn't request this, please ignore this email.</p>
        <p>Thank you for using our application!</p>
        <hr>
        <p class="footer">Need help? Contact our support team.</p>
    </div>
</body>
</html>