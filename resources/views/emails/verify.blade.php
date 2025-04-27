<!DOCTYPE html>
<html>
<head>
    <title>Email Verification</title>
</head>
<body>
    <h2>Email Verification</h2>
    <p>Hello,</p>
    <p>Please click the button below to verify your email address:</p>
    
    <a href="{{ $url }}" 
       style="background: #4e73df; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; display: inline-block;">
        Verify Email
    </a>
    
    <p>If you did not create an account, no further action is required.</p>
    <p>This verification link will expire in 60 minutes.</p>
</body>
</html>