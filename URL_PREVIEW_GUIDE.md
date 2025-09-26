# URL Preview & Deeplink System Guide

## Overview

This system enables rich URL previews when sharing content from the EGYAKIN app on social media platforms, messaging apps, and other services. It includes Open Graph meta tags, Twitter Cards, and smart mobile redirection.

## Features

✅ **Rich URL Previews** - Beautiful previews with images, titles, and descriptions  
✅ **Open Graph Tags** - Facebook, LinkedIn, WhatsApp support  
✅ **Twitter Cards** - Enhanced Twitter sharing  
✅ **Mobile Detection** - Auto-redirect to app on mobile devices  
✅ **Deeplink Support** - Custom URL scheme (egyakin://)  
✅ **Multi-Content Types** - Posts, Patients, Groups, Consultations  
✅ **API Integration** - Generate share URLs programmatically  
✅ **SEO Optimized** - Search engine friendly metadata  

## Supported Content Types

### 1. Posts (`/post/{id}`)
- **URL**: `https://yourdomain.com/post/123`
- **Deeplink**: `egyakin://post/123`
- **Preview**: Shows post content, author with Dr. prefix, post image

### 2. Patients (`/patient/{id}`)
- **URL**: `https://yourdomain.com/patient/456`
- **Deeplink**: `egyakin://patient/456`
- **Preview**: Shows patient name, doctor, hospital information

### 3. Groups (`/group/{id}`)
- **URL**: `https://yourdomain.com/group/789`
- **Deeplink**: `egyakin://group/789`
- **Preview**: Shows group name, description, creator

### 4. Consultations (`/consultation/{id}`)
- **URL**: `https://yourdomain.com/consultation/101`
- **Deeplink**: `egyakin://consultation/101`
- **Preview**: Shows consultation details, patient, doctor

## API Endpoints

### Generate Share URL

**POST** `/api/v1/share/generate`

```json
{
  "type": "post",
  "id": 123
}
```

**Response:**
```json
{
  "value": true,
  "message": "Share URL generated successfully",
  "data": {
    "share_url": "https://yourdomain.com/post/123",
    "deeplink": "egyakin://post/123",
    "type": "post",
    "id": 123
  }
}
```

### Generate Multiple URLs

**POST** `/api/v1/share/bulk`

```json
{
  "items": [
    {"type": "post", "id": 123},
    {"type": "patient", "id": 456},
    {"type": "group", "id": 789}
  ]
}
```

### Get Preview Data

**GET** `/api/v1/share/preview?type=post&id=123`

**Response:**
```json
{
  "value": true,
  "message": "Preview data retrieved successfully",
  "data": {
    "title": "Dr. Ahmed Ali - EGYAKIN Post",
    "description": "Medical discussion about...",
    "image": "https://yourdomain.com/storage/images/post.jpg",
    "url": "https://yourdomain.com/post/123"
  }
}
```

## How It Works

### 1. URL Sharing Flow
```
User shares content → App generates share URL → Recipient clicks URL
    ↓
Mobile device? → YES → Redirect to app (egyakin://...)
    ↓
Web/Social crawler? → YES → Show rich preview with meta tags
```

### 2. Meta Tags Generated

**Open Graph (Facebook, LinkedIn, WhatsApp):**
```html
<meta property="og:title" content="Dr. Ahmed Ali - EGYAKIN Post">
<meta property="og:description" content="Medical discussion...">
<meta property="og:image" content="https://yourdomain.com/image.jpg">
<meta property="og:url" content="https://yourdomain.com/post/123">
<meta property="og:type" content="article">
<meta property="og:site_name" content="EGYAKIN">
```

**Twitter Cards:**
```html
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Dr. Ahmed Ali - EGYAKIN Post">
<meta name="twitter:description" content="Medical discussion...">
<meta name="twitter:image" content="https://yourdomain.com/image.jpg">
```

### 3. Mobile Detection
```javascript
const isMobile = /Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
if (isMobile) {
    window.location.href = `egyakin://post/123`;
}
```

## Integration in Your App

### 1. Generate Share URLs

```dart
// Flutter example
Future<String> generateShareUrl(String type, int id) async {
  final response = await http.post(
    Uri.parse('${apiBase}/share/generate'),
    headers: {'Authorization': 'Bearer $token'},
    body: json.encode({'type': type, 'id': id}),
  );
  
  final data = json.decode(response.body);
  return data['data']['share_url'];
}
```

### 2. Share Content

```dart
// Share using native sharing
import 'package:share_plus/share_plus.dart';

void sharePost(int postId) async {
  final shareUrl = await generateShareUrl('post', postId);
  await Share.share(
    'Check out this medical post on EGYAKIN: $shareUrl',
    subject: 'EGYAKIN Medical Post'
  );
}
```

### 3. Handle Incoming Deeplinks

```dart
// Configure URL scheme in your app
// iOS: Info.plist
// Android: AndroidManifest.xml

void handleDeeplink(String link) {
  // egyakin://post/123
  final uri = Uri.parse(link);
  final type = uri.pathSegments[0]; // 'post'
  final id = int.parse(uri.pathSegments[1]); // 123
  
  // Navigate to appropriate screen
  navigateToContent(type, id);
}
```

## Preview Examples

### WhatsApp Preview
```
🏥 Dr. Ahmed Ali - EGYAKIN Post
Medical discussion about kidney function and GFR calculations...
[Image thumbnail]
egyakin.com
```

### Twitter Preview
```
Dr. Ahmed Ali - EGYAKIN Post
Medical discussion about kidney function and GFR calculations...
[Large image]
egyakin.com
```

### Facebook Preview
```
[Large image]
Dr. Ahmed Ali - EGYAKIN Post
Medical discussion about kidney function and GFR calculations...
EGYAKIN.COM
```

## Configuration

### 1. Update App URLs
Update `config/app.php`:
```php
'url' => env('APP_URL', 'https://yourdomain.com'),
```

### 2. Add Logo Image
The system now uses your EGYAKIN logo from: `https://test.egyakin.com/storage/profile_images/profile_image.jpg`

### 3. Configure App Store Links
Update the preview template with your actual app store URLs:
```javascript
// Android
'https://play.google.com/store/apps/details?id=com.egyakin.app'

// iOS  
'https://apps.apple.com/app/egyakin/id123456789'
```

### 4. Custom URL Scheme
Configure your mobile app to handle `egyakin://` URLs.

## Testing

### 1. Test URL Previews
- Share URLs on WhatsApp, Facebook, Twitter
- Use Facebook's [Sharing Debugger](https://developers.facebook.com/tools/debug/)
- Use Twitter's [Card Validator](https://cards-dev.twitter.com/validator)

### 2. Test Mobile Redirects
- Open share URLs on mobile devices
- Verify app opens correctly
- Test fallback to app store

### 3. Test API Endpoints
```bash
# Generate share URL
curl -X POST https://yourdomain.com/api/v1/share/generate \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"type": "post", "id": 123}'

# Get preview data
curl "https://yourdomain.com/api/v1/share/preview?type=post&id=123" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Troubleshooting

### 1. Previews Not Showing
- Check meta tags in page source
- Verify image URLs are accessible
- Test with Facebook Sharing Debugger

### 2. Mobile Redirect Not Working
- Verify URL scheme configuration
- Check User-Agent detection
- Test deeplink handling in app

### 3. API Errors
- Check authentication tokens
- Verify content exists (post/patient/group)
- Check server logs for errors

## Files Created

- `app/Http/Controllers/DeepLinkController.php` - Main deeplink handler
- `app/Services/ShareUrlService.php` - URL generation service
- `app/Http/Controllers/Api/V1/ShareController.php` - API endpoints
- `resources/views/deeplinks/preview.blade.php` - Preview template
- Updated `routes/web.php` - Deeplink routes
- Updated `routes/api/v1.php` - API routes

## Next Steps

1. **Add App Store Links** - Update with your actual app store URLs
2. **Logo Updated** - Now using your EGYAKIN logo from `https://test.egyakin.com/storage/profile_images/profile_image.jpg`
3. **Test Sharing** - Share URLs and verify previews work
4. **Configure Mobile App** - Set up deeplink handling in your mobile app
5. **Monitor Analytics** - Track shared URL performance

The URL preview system is now ready to provide rich sharing experiences for your EGYAKIN medical platform! 🚀
