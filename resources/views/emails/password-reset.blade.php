<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #1a1a1a;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #1a1a1a; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #2d2d2d; border-radius: 12px; overflow: hidden;">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #ff8234 0%, #ff5a5d 100%); padding: 40px 40px 30px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 600;">Reset Your Password</h1>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px;">
                            <p style="margin: 0 0 20px; color: #ffffff; font-size: 16px; line-height: 1.6;">
                                Hi {{ $user->name }},
                            </p>
                            
                            <p style="margin: 0 0 20px; color: rgba(255, 255, 255, 0.8); font-size: 15px; line-height: 1.6;">
                                We received a request to reset your password for your SongsLab account. Click the button below to create a new password:
                            </p>
                            
                            <!-- Button -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin: 30px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $resetUrl }}" style="display: inline-block; padding: 16px 40px; background: linear-gradient(135deg, #ff8234 0%, #ff5a5d 100%); color: #ffffff; text-decoration: none; border-radius: 8px; font-size: 16px; font-weight: 600;">
                                            Reset Password
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="margin: 30px 0 20px; color: rgba(255, 255, 255, 0.8); font-size: 14px; line-height: 1.6;">
                                Or copy and paste this link into your browser:
                            </p>
                            
                            <p style="margin: 0 0 30px; padding: 15px; background-color: rgba(255, 255, 255, 0.05); border-radius: 6px; color: #ff8234; font-size: 13px; word-break: break-all;">
                                {{ $resetUrl }}
                            </p>
                            
                            <p style="margin: 0 0 10px; color: rgba(255, 255, 255, 0.8); font-size: 14px; line-height: 1.6;">
                                This link will expire in 1 hour for security reasons.
                            </p>
                            
                            <p style="margin: 0; color: rgba(255, 255, 255, 0.6); font-size: 14px; line-height: 1.6;">
                                If you didn't request a password reset, you can safely ignore this email. Your password will remain unchanged.
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="padding: 30px 40px; border-top: 1px solid rgba(255, 255, 255, 0.1); text-align: center;">
                            <p style="margin: 0 0 10px; color: rgba(255, 255, 255, 0.6); font-size: 13px;">
                                © {{ date('Y') }} SongsLab. All rights reserved.
                            </p>
                            <p style="margin: 0; color: rgba(255, 255, 255, 0.4); font-size: 12px;">
                                This is an automated email. Please do not reply.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

