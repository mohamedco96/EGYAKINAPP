@extends('layouts.app')

@section('title', 'Apple Sign-In Test')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-md w-full text-center">
        <!-- Logo -->
        <div class="w-20 h-20 bg-black rounded-2xl mx-auto mb-6 flex items-center justify-center text-white text-3xl">
            🍎
        </div>

        <!-- Title -->
        <h1 class="text-3xl font-semibold text-gray-800 mb-2">Apple Sign-In Test</h1>
        <p class="text-gray-600 mb-8">Test your Apple Sign-In integration</p>

        <!-- Social Auth Buttons -->
        <div class="space-y-4 mb-8">
            <!-- Apple Sign-In Button -->
            <a href="{{ route('apple.redirect') }}" 
               class="w-full bg-black text-white py-4 px-6 rounded-xl font-medium hover:bg-gray-800 transition-all duration-300 transform hover:-translate-y-1 flex items-center justify-center">
                <span class="text-xl mr-3">🍎</span>
                Sign in with Apple
            </a>

            <!-- Google Sign-In Button -->
            <a href="{{ route('google.redirect') }}" 
               class="w-full bg-blue-500 text-white py-4 px-6 rounded-xl font-medium hover:bg-blue-600 transition-all duration-300 transform hover:-translate-y-1 flex items-center justify-center">
                <span class="text-xl mr-3">G</span>
                Sign in with Google
            </a>
        </div>

        <!-- Divider -->
        <div class="flex items-center my-8">
            <div class="flex-1 h-px bg-gray-300"></div>
            <span class="px-4 text-gray-500 text-sm">OR</span>
            <div class="flex-1 h-px bg-gray-300"></div>
        </div>

        <!-- API Testing Section -->
        <div class="bg-gray-50 rounded-xl p-6 text-left">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">API Testing</h3>
            <p class="text-gray-600 text-sm mb-4">Test the API endpoints directly with tokens:</p>

            <!-- Apple Token Test -->
            <div class="mb-4">
                <label for="apple-token" class="block text-sm font-medium text-gray-700 mb-2">
                    Apple Identity Token:
                </label>
                <input type="text" 
                       id="apple-token" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       placeholder="Enter Apple identity token">
                <button onclick="testAppleAPI()" 
                        class="mt-2 bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors text-sm">
                    Test Apple API
                </button>
            </div>

            <!-- Google Token Test -->
            <div class="mb-4">
                <label for="google-token" class="block text-sm font-medium text-gray-700 mb-2">
                    Google Access Token:
                </label>
                <input type="text" 
                       id="google-token" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       placeholder="Enter Google access token">
                <button onclick="testGoogleAPI()" 
                        class="mt-2 bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors text-sm">
                    Test Google API
                </button>
            </div>

            <!-- Loading Indicator -->
            <div id="loading" class="hidden text-gray-600 italic text-sm mb-4">
                Testing authentication...
            </div>

            <!-- Result Display -->
            <div id="result" class="hidden p-4 rounded-lg text-sm mb-4"></div>

            <!-- User Info Display -->
            <div id="user-info" class="hidden bg-gray-100 rounded-lg p-4 text-sm">
                <h4 class="font-semibold text-gray-800 mb-2">User Information</h4>
                <div id="user-details"></div>
            </div>
        </div>

        <!-- Instructions -->
        <div class="mt-6 text-left text-xs text-gray-500">
            <h4 class="font-semibold mb-2">How to test:</h4>
            <ol class="list-decimal list-inside space-y-1">
                <li>Click the social buttons to test OAuth flow</li>
                <li>Or use the API testing section with tokens</li>
                <li>Check browser console for detailed logs</li>
                <li>Verify responses match expected format</li>
            </ol>
        </div>
    </div>
</div>

<script>
// Test Apple API
async function testAppleAPI() {
    const token = document.getElementById('apple-token').value.trim();
    if (!token) {
        showResult('Please enter an Apple identity token', 'error');
        return;
    }

    showLoading(true);
    hideResult();
    hideUserInfo();
    
    try {
        const response = await fetch('/api/auth/social/apple', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                identity_token: token
            })
        });

        const data = await response.json();
        
        if (data.success) {
            showResult('✅ Apple authentication successful!', 'success');
            showUserInfo(data.data.user);
        } else {
            showResult(`❌ Apple authentication failed: ${data.message}`, 'error');
        }
    } catch (error) {
        showResult(`❌ Error: ${error.message}`, 'error');
    } finally {
        showLoading(false);
    }
}

// Test Google API
async function testGoogleAPI() {
    const token = document.getElementById('google-token').value.trim();
    if (!token) {
        showResult('Please enter a Google access token', 'error');
        return;
    }

    showLoading(true);
    hideResult();
    hideUserInfo();
    
    try {
        const response = await fetch('/api/auth/social/google', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                access_token: token
            })
        });

        const data = await response.json();
        
        if (data.success) {
            showResult('✅ Google authentication successful!', 'success');
            showUserInfo(data.data.user);
        } else {
            showResult(`❌ Google authentication failed: ${data.message}`, 'error');
        }
    } catch (error) {
        showResult(`❌ Error: ${error.message}`, 'error');
    } finally {
        showLoading(false);
    }
}

// Show/hide loading
function showLoading(show) {
    const loading = document.getElementById('loading');
    loading.classList.toggle('hidden', !show);
}

// Show result
function showResult(message, type) {
    const result = document.getElementById('result');
    result.textContent = message;
    result.className = `p-4 rounded-lg text-sm mb-4 ${type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`;
    result.classList.remove('hidden');
}

// Hide result
function hideResult() {
    document.getElementById('result').classList.add('hidden');
}

// Show user info
function showUserInfo(user) {
    const userDetails = document.getElementById('user-details');
    userDetails.innerHTML = `
        <p><strong>ID:</strong> ${user.id}</p>
        <p><strong>Name:</strong> ${user.name || 'Not provided'}</p>
        <p><strong>Email:</strong> ${user.email || 'Not provided'}</p>
        <p><strong>Avatar:</strong> ${user.avatar || 'Not provided'}</p>
        <p><strong>Locale:</strong> ${user.locale || 'Not set'}</p>
    `;
    document.getElementById('user-info').classList.remove('hidden');
}

// Hide user info
function hideUserInfo() {
    document.getElementById('user-info').classList.add('hidden');
}

// Handle URL parameters for testing
window.addEventListener('load', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const appleToken = urlParams.get('apple_token');
    const googleToken = urlParams.get('google_token');
    
    if (appleToken) {
        document.getElementById('apple-token').value = appleToken;
        testAppleAPI();
    }
    
    if (googleToken) {
        document.getElementById('google-token').value = googleToken;
        testGoogleAPI();
    }
});

// Add some sample tokens for testing (remove in production)
document.addEventListener('DOMContentLoaded', function() {
    // Add placeholder text with examples
    const appleInput = document.getElementById('apple-token');
    const googleInput = document.getElementById('google-token');
    
    appleInput.addEventListener('focus', function() {
        if (!this.value) {
            this.placeholder = 'Example: eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...';
        }
    });
    
    googleInput.addEventListener('focus', function() {
        if (!this.value) {
            this.placeholder = 'Example: ya29.a0AfH6SMC...';
        }
    });
});
</script>
@endsection
