<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Progress - EGYAKIN</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg p-8 max-w-md w-full mx-4" x-data="exportProgress('{{ $filename }}')">
        <div class="text-center">
            <div class="mb-6">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100">
                    <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Patient Export Progress</h3>
                <p class="text-sm text-gray-500">Export ID: {{ $filename }}</p>
            </div>

            <div x-show="status === 'processing'" class="mb-6">
                <div class="w-full bg-gray-200 rounded-full h-2.5 mb-4">
                    <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-300" :style="`width: ${percentage}%`"></div>
                </div>
                <p class="text-sm text-gray-600" x-text="message"></p>
                <p class="text-xs text-gray-400 mt-2" x-text="`${percentage}% complete`"></p>
            </div>

            <div x-show="status === 'completed'" class="mb-6">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
                    <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h4 class="text-lg font-medium text-green-900 mb-2">Export Completed!</h4>
                <p class="text-sm text-gray-600 mb-4">Your patient export is ready for download.</p>
                <a :href="downloadUrl" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Download Export
                </a>
            </div>

            <div x-show="status === 'failed'" class="mb-6">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
                <h4 class="text-lg font-medium text-red-900 mb-2">Export Failed</h4>
                <p class="text-sm text-gray-600" x-text="error"></p>
            </div>

            <div x-show="status === 'not_found'" class="mb-6">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 mb-4">
                    <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                </div>
                <h4 class="text-lg font-medium text-yellow-900 mb-2">Export Not Found</h4>
                <p class="text-sm text-gray-600">The export you're looking for was not found or may have expired.</p>
            </div>

            <div class="text-center">
                <button @click="checkProgress()" :disabled="loading" class="text-sm text-blue-600 hover:text-blue-500 disabled:opacity-50">
                    <span x-show="!loading">Refresh Status</span>
                    <span x-show="loading">Checking...</span>
                </button>
            </div>

            <div class="mt-6 pt-4 border-t border-gray-200">
                <a href="/admin" class="text-sm text-gray-500 hover:text-gray-700">← Back to Dashboard</a>
            </div>
        </div>
    </div>

    <script>
        function exportProgress(filename) {
            return {
                filename: filename,
                status: 'processing',
                percentage: 0,
                message: 'Checking export status...',
                downloadUrl: null,
                error: null,
                loading: false,

                init() {
                    this.checkProgress();
                    // Auto-refresh every 3 seconds while processing
                    this.interval = setInterval(() => {
                        if (this.status === 'processing') {
                            this.checkProgress();
                        } else {
                            clearInterval(this.interval);
                        }
                    }, 3000);
                },

                async checkProgress() {
                    this.loading = true;
                    try {
                        const response = await fetch(`/export/progress/${this.filename}`);
                        const data = await response.json();
                        
                        this.status = data.status;
                        this.percentage = data.percentage || 0;
                        this.message = data.message || '';
                        this.downloadUrl = data.download_url || null;
                        this.error = data.error || null;
                        
                    } catch (error) {
                        console.error('Error checking progress:', error);
                        this.status = 'error';
                        this.error = 'Failed to check export progress';
                    } finally {
                        this.loading = false;
                    }
                }
            }
        }
    </script>
</body>
</html>
