<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Verification Code</title>
</head>
<body style="font-family: Arial, sans-serif">

<h2>Hello {{ $userName }}</h2>

<p>Your verification code is:</p>

<h1 style="
    letter-spacing:5px;
    color:#0d6efd;
    font-size:40px;
">
    {{ $otp }}
</h1>

<p>
This code is valid for <strong>24 hours</strong>.
</p>

<p>
If you did not request this account, please ignore this email.
</p>

<hr>

<p>
Hospital Warehouse System
</p>

</body>
</html>