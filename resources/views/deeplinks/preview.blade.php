<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Basic Meta Tags -->
    <title>{{ $metaData['title'] }}</title>
    <meta name="description" content="{{ $metaData['description'] }}">
    
    <!-- Open Graph Meta Tags (Facebook, LinkedIn, etc.) -->
    <meta property="og:title" content="{{ $metaData['title'] }}">
    <meta property="og:description" content="{{ $metaData['description'] }}">
    <meta property="og:image" content="{{ $metaData['image'] }}">
    <meta property="og:url" content="{{ $metaData['url'] }}">
    <meta property="og:type" content="{{ $metaData['type'] }}">
    <meta property="og:site_name" content="EGYAKIN">
    <meta property="og:locale" content="en_US">
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $metaData['title'] }}">
    <meta name="twitter:description" content="{{ $metaData['description'] }}">
    <meta name="twitter:image" content="{{ $metaData['image'] }}">
    <meta name="twitter:url" content="{{ $metaData['url'] }}">
    <meta name="twitter:site" content="@EGYAKIN">
    
    <!-- Additional Meta Tags -->
    <meta name="robots" content="index, follow">
    <meta name="author" content="EGYAKIN">
    <meta name="theme-color" content="#2563eb">
    
    <!-- Favicon -->
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <link rel="apple-touch-icon" href="{{ asset('images/egyakin-logo.png') }}">
    
    <!-- Canonical URL -->
    <link rel="canonical" href="{{ $metaData['url'] }}">
    
    <!-- Structured Data (JSON-LD) -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "{{ $content['type'] === 'post' ? 'Article' : ($content['type'] === 'group' ? 'Organization' : 'MedicalWebPage') }}",
        "name": "{{ $metaData['title'] }}",
        "description": "{{ $metaData['description'] }}",
        "image": "{{ $metaData['image'] }}",
        "url": "{{ $metaData['url'] }}",
        "publisher": {
            "@type": "Organization",
            "name": "EGYAKIN",
            "logo": {
                "@type": "ImageObject",
                "url": "{{ asset('images/egyakin-logo.png') }}"
            }
        }
    }
    </script>
    
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            max-width: 600px;
            margin: 20px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            text-align: center;
        }
        
        .header {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            padding: 40px 30px;
        }
        
        .logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: bold;
            color: #2563eb;
        }
        
        .title {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .subtitle {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .content {
            padding: 40px 30px;
        }
        
        .description {
            font-size: 18px;
            color: #4b5563;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        
        .app-download {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 15px 30px;
            border-radius: 50px;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
            font-size: 16px;
            transition: transform 0.2s;
            margin: 10px;
        }
        
        .app-download:hover {
            transform: translateY(-2px);
            text-decoration: none;
            color: white;
        }
        
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .feature {
            padding: 20px;
            background: #f8fafc;
            border-radius: 15px;
        }
        
        .feature-icon {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .feature-title {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 5px;
        }
        
        .feature-desc {
            font-size: 14px;
            color: #6b7280;
        }
        
        .footer {
            background: #f8fafc;
            padding: 30px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
        }
        
        @media (max-width: 640px) {
            .container {
                margin: 10px;
                border-radius: 15px;
            }
            
            .title {
                font-size: 24px;
            }
            
            .description {
                font-size: 16px;
            }
            
            .features {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                🏥
            </div>
            <div class="title">{{ $metaData['title'] }}</div>
            <div class="subtitle">Medical Content on EGYAKIN</div>
        </div>
        
        <div class="content">
            <div class="description">
                {{ $metaData['description'] }}
            </div>
            
            @if($content['type'] !== 'not_found')
                <p style="color: #059669; font-weight: 600; margin-bottom: 20px;">
                    📱 This content is best viewed in the EGYAKIN mobile app
                </p>
            @endif
            
            <div class="features">
                <div class="feature">
                    <div class="feature-icon">👨‍⚕️</div>
                    <div class="feature-title">Medical Network</div>
                    <div class="feature-desc">Connect with healthcare professionals</div>
                </div>
                <div class="feature">
                    <div class="feature-icon">📋</div>
                    <div class="feature-title">Patient Cases</div>
                    <div class="feature-desc">Share and discuss medical cases</div>
                </div>
                <div class="feature">
                    <div class="feature-icon">💬</div>
                    <div class="feature-title">Consultations</div>
                    <div class="feature-desc">Get expert medical opinions</div>
                </div>
            </div>
            
            <div>
                <a href="https://play.google.com/store" class="app-download">
                    📱 Download for Android
                </a>
                <a href="https://apps.apple.com" class="app-download">
                    🍎 Download for iOS
                </a>
            </div>
        </div>
        
        <div class="footer">
            <p><strong>EGYAKIN</strong> - The Leading Medical Platform in Egypt</p>
            <p>Join thousands of healthcare professionals sharing knowledge and expertise.</p>
            
            @if($content['type'] !== 'not_found')
                <p style="margin-top: 20px; font-size: 14px;">
                    <strong>Content ID:</strong> {{ ucfirst($content['type']) }} #{{ $content['id'] }}
                </p>
            @endif
        </div>
    </div>
    
    <!-- Auto-redirect script for mobile devices -->
    <script>
        // Check if this is a mobile device and redirect to app
        const isMobile = /Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        const isInApp = /EGYAKIN/i.test(navigator.userAgent); // Check if already in app webview
        
        if (isMobile && !isInApp) {
            // Try to open the app after a short delay
            setTimeout(() => {
                const contentType = '{{ $content["type"] }}';
                const contentId = '{{ $content["id"] }}';
                
                if (contentType !== 'not_found') {
                    // Try to open the deep link
                    window.location.href = `egyakin://${contentType}/${contentId}`;
                    
                    // Fallback to app store after 2 seconds if app doesn't open
                    setTimeout(() => {
                        if (document.hasFocus()) {
                            // If page still has focus, app probably didn't open
                            if (/Android/i.test(navigator.userAgent)) {
                                window.location.href = 'https://play.google.com/store/apps/details?id=com.egyakin.app';
                            } else if (/iPhone|iPad|iPod/i.test(navigator.userAgent)) {
                                window.location.href = 'https://apps.apple.com/app/egyakin/id123456789';
                            }
                        }
                    }, 2000);
                }
            }, 1000);
        }
    </script>
</body>
</html>
