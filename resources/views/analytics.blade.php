<!DOCTYPE html>
<html lang="{{ $locale ?? 'en' }}" class="{{ $isDark ? 'dark' : '' }}" dir="{{ ($locale ?? 'en') === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('api.analytics_title') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Smooth scrolling optimization */
        html {
            scroll-behavior: smooth;
        }
        
        body {
            -webkit-overflow-scrolling: touch;
            overflow-x: hidden;
        }
        
        /* Optimize animations for better performance */
        * {
            -webkit-transform: translateZ(0);
            transform: translateZ(0);
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .card-hover {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            will-change: transform;
        }
        
        .card-hover:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.1), 0 5px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .stat-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .stat-card-blue {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .stat-card-green {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }
        
        .stat-card-purple {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }
        
        .stat-card-orange {
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
        }
        
        .chart-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .dark .chart-container {
            background: rgba(31, 41, 55, 0.95);
            border: 1px solid rgba(75, 85, 99, 0.3);
        }
        
        /* Fixed height for charts to prevent layout shifts */
        .chart-fixed-height {
            height: 300px;
        }
        
        /* Optimize scrollable areas */
        .scrollable-content {
            scrollbar-width: auto;
            scrollbar-color: #3b82f6 #e5e7eb;
            overflow-y: scroll !important; /* Force scrollbar to always show */
        }
        
        .scrollable-content::-webkit-scrollbar {
            width: 12px;
            -webkit-appearance: none;
        }
        
        .scrollable-content::-webkit-scrollbar-track {
            background: #e5e7eb;
            border-radius: 6px;
            -webkit-box-shadow: inset 0 0 6px rgba(0,0,0,0.1);
        }
        
        .scrollable-content::-webkit-scrollbar-thumb {
            background: #3b82f6;
            border-radius: 6px;
            border: 2px solid #e5e7eb;
            -webkit-box-shadow: inset 0 0 6px rgba(0,0,0,0.1);
        }
        
        .scrollable-content::-webkit-scrollbar-thumb:hover {
            background: #2563eb;
        }
        
        /* Ensure scrollbar is always visible */
        .scrollable-content::-webkit-scrollbar-thumb:window-inactive {
            background: #3b82f6;
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen transition-colors duration-300">
    <!-- Warning Message -->
    <div class="bg-red-600 text-white py-3">
        <div class="container mx-auto px-6">
            <div class="flex items-center justify-center">
                <i class="fas fa-exclamation-triangle mr-3"></i>
                <div class="text-center">
                    <p class="font-semibold mb-1">{{ __('api.data_use_warning_ar') }}</p>
                    <p class="text-sm opacity-90">{{ __('api.data_use_warning') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Header -->
    <div class="gradient-bg text-white py-8">
        <div class="container mx-auto px-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold mb-2">
                        <i class="fas fa-chart-line {{ ($locale ?? 'en') === 'ar' ? 'ml-3' : 'mr-3' }}"></i>
                        {{ __('api.analytics_title') }}
                    </h1>
                    <p class="text-xl opacity-90">{{ __('api.analytics_subtitle') }}</p>
                </div>
                <div class="flex items-center space-x-6">
                    <!-- Dark Mode Toggle -->
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-sun text-yellow-300"></i>
                        <button onclick="toggleDarkMode()" class="relative inline-flex h-6 w-11 items-center rounded-full bg-white bg-opacity-20 transition-colors focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2">
                            <span class="sr-only">{{ __('api.toggle_dark_mode') }}</span>
                            <span id="toggle-dot" class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $isDark ? 'translate-x-6' : 'translate-x-1' }}"></span>
                        </button>
                        <i class="fas fa-moon text-blue-200"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-lg opacity-90">{{ date('F j, Y') }}</p>
                        <p class="text-sm opacity-75">{{ __('api.real_time_insights') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-6 py-8">
        <!-- Key Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
            <!-- Total Doctors -->
            <div class="stat-card rounded-xl p-6 text-white card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90 mb-1">{{ __('api.total_doctors') }}</p>
                        <p class="text-3xl font-bold">{{ number_format($analytics['total_doctors']) }}</p>
                        <p class="text-xs opacity-75">{{ __('api.verified') }}</p>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-3">
                        <i class="fas fa-user-md text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Total Users -->
            <div class="stat-card-orange rounded-xl p-6 text-white card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90 mb-1">{{ __('api.total_users') }}</p>
                        <p class="text-3xl font-bold">{{ number_format($analytics['total_users']) }}</p>
                        <p class="text-xs opacity-75">{{ __('api.non_verified') }}</p>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-3">
                        <i class="fas fa-users text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Total Patients -->
            <div class="stat-card-blue rounded-xl p-6 text-white card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90 mb-1">{{ __('api.total_patients') }}</p>
                        <p class="text-3xl font-bold">{{ number_format($analytics['total_patients']) }}</p>
                        <p class="text-xs opacity-75">{{ __('api.active_only') }}</p>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-3">
                        <i class="fas fa-hospital-user text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Male Patients -->
            <div class="stat-card-green rounded-xl p-6 text-white card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90 mb-1">{{ __('api.male_patients') }}</p>
                        <p class="text-3xl font-bold">{{ number_format($analytics['gender_stats']['male']) }}</p>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-3">
                        <i class="fas fa-mars text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Female Patients -->
            <div class="stat-card-purple rounded-xl p-6 text-white card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90 mb-1">{{ __('api.female_patients') }}</p>
                        <p class="text-3xl font-bold">{{ number_format($analytics['gender_stats']['female']) }}</p>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-3">
                        <i class="fas fa-venus text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Gender Distribution Chart -->
            <div class="chart-container rounded-xl p-6 shadow-lg">
                <h3 class="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-200">
                    <i class="fas fa-chart-pie {{ ($locale ?? 'en') === 'ar' ? 'ml-2' : 'mr-2' }} text-blue-600 dark:text-blue-400"></i>
                    {{ __('api.gender_distribution') }}
                </h3>
                <div class="chart-fixed-height">
                    <canvas id="genderChart"></canvas>
                </div>
            </div>

            <!-- DM vs HTN Chart -->
            <div class="chart-container rounded-xl p-6 shadow-lg">
                <h3 class="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-200">
                    <i class="fas fa-chart-bar {{ ($locale ?? 'en') === 'ar' ? 'ml-2' : 'mr-2' }} text-green-600 dark:text-green-400"></i>
                    {{ __('api.dm_htn_statistics') }}
                </h3>
                <div class="chart-fixed-height">
                    <canvas id="dmHtnChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Medical Conditions Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Dialysis Percentage -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg card-hover transition-colors duration-300">
                <h3 class="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-200">
                    <i class="fas fa-percentage {{ ($locale ?? 'en') === 'ar' ? 'ml-2' : 'mr-2' }} text-red-600 dark:text-red-400"></i>
                    {{ __('api.dialysis_percentage') }}
                </h3>
                <div class="flex items-center justify-center">
                    <div class="relative w-32 h-32">
                        <svg class="w-32 h-32 transform -rotate-90" viewBox="0 0 36 36">
                            <path class="text-gray-300" stroke="currentColor" stroke-width="3" fill="none"
                                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                            <path class="text-red-500" stroke="currentColor" stroke-width="3" fill="none" stroke-linecap="round"
                                stroke-dasharray="{{ $analytics['dialysis_percentage'] }}, 100"
                                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="text-2xl font-bold text-gray-800 dark:text-gray-200">{{ $analytics['dialysis_percentage'] }}%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Department Statistics -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg card-hover transition-colors duration-300">
                <h3 class="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-200">
                    <i class="fas fa-hospital {{ ($locale ?? 'en') === 'ar' ? 'ml-2' : 'mr-2' }} text-purple-600 dark:text-purple-400"></i>
                    {{ __('api.department_distribution') }}
                </h3>
                @if(count($analytics['department_stats']) > 0)
                    <div class="space-y-3">
                        @foreach($analytics['department_stats'] as $department => $count)
                            @if(!empty($department) && $department !== null && trim($department) !== '' && $department !== 'null')
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-700 dark:text-gray-300">{{ $department }}</span>
                                    <div class="flex items-center">
                                        <div class="w-24 bg-gray-200 rounded-full h-2 mr-3">
                                            <div class="bg-purple-600 h-2 rounded-full" style="width: {{ ($count / max($analytics['department_stats'])) * 100 }}%"></div>
                                        </div>
                                        <span class="text-sm font-semibold text-gray-600 dark:text-gray-400">{{ $count }}</span>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 dark:text-gray-400 text-center py-4">{{ __('api.no_department_data') }}</p>
                @endif
            </div>
        </div>

        <!-- Diagnosis and Outcomes Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Provisional Diagnosis -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg card-hover transition-colors duration-300">
                <h3 class="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-200">
                    <i class="fas fa-stethoscope {{ ($locale ?? 'en') === 'ar' ? 'ml-2' : 'mr-2' }} text-indigo-600 dark:text-indigo-400"></i>
                    {{ __('api.provisional_diagnosis') }}
                </h3>
                @if(count($analytics['provisional_diagnosis_stats']) > 0)
                    <div class="space-y-3 max-h-64 overflow-y-auto scrollable-content">
                        @foreach($analytics['provisional_diagnosis_stats'] as $diagnosis => $count)
                            @if(!empty($diagnosis) && $diagnosis !== null && trim($diagnosis) !== '' && $diagnosis !== 'null')
                                <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded transition-colors duration-300">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ Str::limit($diagnosis, 30) }}</span>
                                    <span class="bg-indigo-100 text-indigo-800 text-xs font-semibold px-2 py-1 rounded">{{ $count }}</span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 dark:text-gray-400 text-center py-4">{{ __('api.no_diagnosis_data') }}</p>
                @endif
            </div>

            <!-- Cause of AKI -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg card-hover transition-colors duration-300">
                <h3 class="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-200">
                    <i class="fas fa-exclamation-triangle {{ ($locale ?? 'en') === 'ar' ? 'ml-2' : 'mr-2' }} text-orange-600 dark:text-orange-400"></i>
                    {{ __('api.cause_of_aki') }}
                </h3>
                @if(count($analytics['cause_of_aki_stats']) > 0)
                    <div class="space-y-3 max-h-64 overflow-y-auto scrollable-content">
                        @foreach($analytics['cause_of_aki_stats'] as $cause => $count)
                            @if(!empty($cause) && $cause !== null && trim($cause) !== '' && $cause !== 'null')
                                <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded transition-colors duration-300">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ Str::limit($cause, 30) }}</span>
                                    <span class="bg-orange-100 text-orange-800 text-xs font-semibold px-2 py-1 rounded">{{ $count }}</span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 dark:text-gray-400 text-center py-4">{{ __('api.no_aki_cause_data') }}</p>
                @endif
            </div>
        </div>

        <!-- Outcomes Section -->
        <div class="grid grid-cols-1 gap-8">
            <!-- Patient Outcomes -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg card-hover transition-colors duration-300">
                <h3 class="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-200">
                    <i class="fas fa-heartbeat {{ ($locale ?? 'en') === 'ar' ? 'ml-2' : 'mr-2' }} text-green-600 dark:text-green-400"></i>
                    {{ __('api.patient_outcomes_status') }}
                </h3>
                
                <!-- Status Counts -->
                <div class="mb-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg transition-colors duration-300">
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">{{ __('api.outcome_status') }}</p>
                            <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $analytics['outcome_stats']['outcome_status_count'] ?? 0 }}</p>
                        </div>
                        <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg transition-colors duration-300">
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">{{ __('api.submit_status') }}</p>
                            <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $analytics['outcome_stats']['submit_status_count'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>
                
                <!-- Outcome Values from Question ID 79 -->
                @if(isset($analytics['outcome_stats']['outcome_values']) && count($analytics['outcome_stats']['outcome_values']) > 0)
                    <div class="mb-4">
                        <h4 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-3">{{ __('api.outcome_values') }}</h4>
                        <div class="space-y-2 mb-4">
                            @foreach($analytics['outcome_stats']['outcome_values'] as $outcome => $data)
                                @if(!empty($outcome) && $outcome !== null && trim($outcome) !== '' && $outcome !== 'null')
                                    <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded transition-colors duration-300">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $outcome }}</span>
                                        <div class="flex items-center space-x-2">
                                            <span class="bg-green-100 text-green-800 text-xs font-semibold px-2 py-1 rounded">{{ $data['count'] }}</span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">({{ $data['percentage'] }}%)</span>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                        
                        <div class="chart-fixed-height">
                            <canvas id="outcomeChart"></canvas>
                        </div>
                    </div>
                @else
                    <p class="text-gray-500 dark:text-gray-400 text-center py-4">{{ __('api.no_outcome_data') }}</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 dark:bg-gray-900 text-white py-6 mt-12 transition-colors duration-300">
        <div class="container mx-auto px-6 text-center">
            <p>&copy; {{ date('Y') }} EGYAKIN. {{ __('api.all_rights_reserved') }} | {{ __('api.medical_analytics_footer') }}</p>
        </div>
    </footer>

    <script>
        // Gender Distribution Chart
        const genderCtx = document.getElementById('genderChart').getContext('2d');
        new Chart(genderCtx, {
            type: 'doughnut',
            data: {
                labels: ['{{ __("api.male") }}', '{{ __("api.female") }}'],
                datasets: [{
                    data: [
                        {{ $analytics['gender_stats']['male'] }},
                        {{ $analytics['gender_stats']['female'] }}
                    ],
                    backgroundColor: ['#3B82F6', '#EC4899'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 1000
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            usePointStyle: true,
                            font: {
                                size: 12
                            }
                        }
                    }
                }
            }
        });

        // DM vs HTN Chart
        const dmHtnCtx = document.getElementById('dmHtnChart').getContext('2d');
        new Chart(dmHtnCtx, {
            type: 'bar',
            data: {
                labels: ['{{ __("api.dm_yes") }}', '{{ __("api.dm_no") }}', '{{ __("api.htn_yes") }}', '{{ __("api.htn_no") }}'],
                datasets: [{
                    label: '{{ __("api.patient_count") }}',
                    data: [
                        {{ $analytics['dm_stats']['yes'] }},
                        {{ $analytics['dm_stats']['no'] }},
                        {{ $analytics['htn_stats']['yes'] }},
                        {{ $analytics['htn_stats']['no'] }}
                    ],
                    backgroundColor: ['#10B981', '#F59E0B', '#EF4444', '#6B7280'],
                    borderRadius: 6,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 1000
                },
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        },
                        ticks: {
                            font: {
                                size: 11
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 11
                            },
                            maxRotation: 45
                        }
                    }
                }
            }
        });

        @if(isset($analytics['outcome_stats']['outcome_values']) && count($analytics['outcome_stats']['outcome_values']) > 0)
        // Outcome Chart - Outcome Values from Question ID 79
        const outcomeCtx = document.getElementById('outcomeChart').getContext('2d');
        const outcomeData = @json($analytics['outcome_stats']['outcome_values']);
        const outcomeLabels = Object.keys(outcomeData).filter(label => 
            label && label !== null && label.trim() !== '' && label !== 'null'
        );
        const outcomeCounts = outcomeLabels.map(label => outcomeData[label].count);
        
        new Chart(outcomeCtx, {
            type: 'pie',
            data: {
                labels: outcomeLabels,
                datasets: [{
                    data: outcomeCounts,
                    backgroundColor: [
                        '#10B981', '#EF4444', '#F59E0B', '#3B82F6', '#8B5CF6', '#EC4899'
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 1000
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 12,
                            usePointStyle: true,
                            font: {
                                size: 11
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label;
                                const count = context.parsed;
                                const percentage = outcomeData[label].percentage;
                                return `${label}: ${count} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
        @endif

        // Dark Mode Toggle Function
        function toggleDarkMode() {
            const html = document.documentElement;
            const toggleDot = document.getElementById('toggle-dot');
            const isDark = html.classList.contains('dark');
            
            if (isDark) {
                html.classList.remove('dark');
                toggleDot.classList.remove('translate-x-6');
                toggleDot.classList.add('translate-x-1');
                // Update URL to reflect light mode
                const url = new URL(window.location);
                url.searchParams.delete('dark');
                window.history.replaceState({}, '', url);
            } else {
                html.classList.add('dark');
                toggleDot.classList.remove('translate-x-1');
                toggleDot.classList.add('translate-x-6');
                // Update URL to reflect dark mode
                const url = new URL(window.location);
                url.searchParams.set('dark', 'true');
                window.history.replaceState({}, '', url);
            }
        }

    </script>
</body>
</html>
