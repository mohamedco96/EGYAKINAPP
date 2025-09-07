<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EGYAKIN Analytics Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
        
        /* Fixed height for charts to prevent layout shifts */
        .chart-fixed-height {
            height: 300px;
        }
        
        /* Optimize scrollable areas */
        .scrollable-content {
            scrollbar-width: thin;
            scrollbar-color: #cbd5e0 #f7fafc;
        }
        
        .scrollable-content::-webkit-scrollbar {
            width: 6px;
        }
        
        .scrollable-content::-webkit-scrollbar-track {
            background: #f7fafc;
            border-radius: 3px;
        }
        
        .scrollable-content::-webkit-scrollbar-thumb {
            background: #cbd5e0;
            border-radius: 3px;
        }
        
        .scrollable-content::-webkit-scrollbar-thumb:hover {
            background: #a0aec0;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Warning Message -->
    <div class="bg-red-600 text-white py-3">
        <div class="container mx-auto px-6">
            <div class="flex items-center justify-center">
                <i class="fas fa-exclamation-triangle mr-3"></i>
                <div class="text-center">
                    <p class="font-semibold mb-1">لا يُسمح باستخدام البيانات دون موافقتنا.</p>
                    <p class="text-sm opacity-90">Data use is not permitted without our approval.</p>
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
                        <i class="fas fa-chart-line mr-3"></i>
                        EGYAKIN Analytics Dashboard
                    </h1>
                    <p class="text-xl opacity-90">Comprehensive Medical Data Analytics</p>
                </div>
                <div class="text-right">
                    <p class="text-lg opacity-90">{{ date('F j, Y') }}</p>
                    <p class="text-sm opacity-75">Real-time Data Insights</p>
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
                        <p class="text-sm opacity-90 mb-1">Total Doctors</p>
                        <p class="text-3xl font-bold">{{ number_format($analytics['total_doctors']) }}</p>
                        <p class="text-xs opacity-75">Verified</p>
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
                        <p class="text-sm opacity-90 mb-1">Total Users</p>
                        <p class="text-3xl font-bold">{{ number_format($analytics['total_users']) }}</p>
                        <p class="text-xs opacity-75">Non-verified</p>
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
                        <p class="text-sm opacity-90 mb-1">Total Patients</p>
                        <p class="text-3xl font-bold">{{ number_format($analytics['total_patients']) }}</p>
                        <p class="text-xs opacity-75">Active only</p>
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
                        <p class="text-sm opacity-90 mb-1">Male Patients</p>
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
                        <p class="text-sm opacity-90 mb-1">Female Patients</p>
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
                <h3 class="text-xl font-semibold mb-4 text-gray-800">
                    <i class="fas fa-chart-pie mr-2 text-blue-600"></i>
                    Gender Distribution
                </h3>
                <div class="chart-fixed-height">
                    <canvas id="genderChart"></canvas>
                </div>
            </div>

            <!-- DM vs HTN Chart -->
            <div class="chart-container rounded-xl p-6 shadow-lg">
                <h3 class="text-xl font-semibold mb-4 text-gray-800">
                    <i class="fas fa-chart-bar mr-2 text-green-600"></i>
                    DM vs HTN Statistics
                </h3>
                <div class="chart-fixed-height">
                    <canvas id="dmHtnChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Medical Conditions Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Dialysis Percentage -->
            <div class="bg-white rounded-xl p-6 shadow-lg card-hover">
                <h3 class="text-xl font-semibold mb-4 text-gray-800">
                    <i class="fas fa-percentage mr-2 text-red-600"></i>
                    Dialysis Percentage
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
                            <span class="text-2xl font-bold text-gray-800">{{ $analytics['dialysis_percentage'] }}%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Department Statistics -->
            <div class="bg-white rounded-xl p-6 shadow-lg card-hover">
                <h3 class="text-xl font-semibold mb-4 text-gray-800">
                    <i class="fas fa-hospital mr-2 text-purple-600"></i>
                    Department Distribution
                </h3>
                @if(count($analytics['department_stats']) > 0)
                    <div class="space-y-3">
                        @foreach($analytics['department_stats'] as $department => $count)
                            <div class="flex items-center justify-between">
                                <span class="text-gray-700">{{ $department }}</span>
                                <div class="flex items-center">
                                    <div class="w-24 bg-gray-200 rounded-full h-2 mr-3">
                                        <div class="bg-purple-600 h-2 rounded-full" style="width: {{ ($count / max($analytics['department_stats'])) * 100 }}%"></div>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-600">{{ $count }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-center py-4">No department data available</p>
                @endif
            </div>
        </div>

        <!-- Diagnosis and Outcomes Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Provisional Diagnosis -->
            <div class="bg-white rounded-xl p-6 shadow-lg card-hover">
                <h3 class="text-xl font-semibold mb-4 text-gray-800">
                    <i class="fas fa-stethoscope mr-2 text-indigo-600"></i>
                    Provisional Diagnosis
                </h3>
                @if(count($analytics['provisional_diagnosis_stats']) > 0)
                    <div class="space-y-3 max-h-64 overflow-y-auto scrollable-content">
                        @foreach($analytics['provisional_diagnosis_stats'] as $diagnosis => $count)
                            <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                <span class="text-sm text-gray-700">{{ Str::limit($diagnosis, 30) }}</span>
                                <span class="bg-indigo-100 text-indigo-800 text-xs font-semibold px-2 py-1 rounded">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-center py-4">No diagnosis data available</p>
                @endif
            </div>

            <!-- Cause of AKI -->
            <div class="bg-white rounded-xl p-6 shadow-lg card-hover">
                <h3 class="text-xl font-semibold mb-4 text-gray-800">
                    <i class="fas fa-exclamation-triangle mr-2 text-orange-600"></i>
                    Cause of AKI
                </h3>
                @if(count($analytics['cause_of_aki_stats']) > 0)
                    <div class="space-y-3 max-h-64 overflow-y-auto scrollable-content">
                        @foreach($analytics['cause_of_aki_stats'] as $cause => $count)
                            <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                <span class="text-sm text-gray-700">{{ Str::limit($cause, 30) }}</span>
                                <span class="bg-orange-100 text-orange-800 text-xs font-semibold px-2 py-1 rounded">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-center py-4">No AKI cause data available</p>
                @endif
            </div>
        </div>

        <!-- Outcomes Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Patient Outcomes -->
            <div class="bg-white rounded-xl p-6 shadow-lg card-hover">
                <h3 class="text-xl font-semibold mb-4 text-gray-800">
                    <i class="fas fa-heartbeat mr-2 text-green-600"></i>
                    Patient Outcomes
                </h3>
                @if(isset($analytics['outcome_stats']['outcome_statuses']) && count($analytics['outcome_stats']['outcome_statuses']) > 0)
                    <div class="mb-4">
                        <h4 class="text-md font-medium text-gray-700 mb-3">Outcome Status</h4>
                        <div class="space-y-2">
                            @foreach($analytics['outcome_stats']['outcome_statuses'] as $status => $data)
                                <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                    <span class="text-sm text-gray-700">{{ $status }}</span>
                                    <div class="flex items-center space-x-2">
                                        <span class="bg-green-100 text-green-800 text-xs font-semibold px-2 py-1 rounded">{{ $data['count'] }}</span>
                                        <span class="text-xs text-gray-500">({{ $data['percentage'] }}%)</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    
                    @if(isset($analytics['outcome_stats']['survivor_death']) && count($analytics['outcome_stats']['survivor_death']) > 0)
                        <div class="chart-fixed-height">
                            <canvas id="outcomeChart"></canvas>
                        </div>
                    @endif
                @else
                    <p class="text-gray-500 text-center py-4">No outcome data available</p>
                @endif
            </div>

            <!-- Final Status -->
            <div class="bg-white rounded-xl p-6 shadow-lg card-hover">
                <h3 class="text-xl font-semibold mb-4 text-gray-800">
                    <i class="fas fa-clipboard-check mr-2 text-blue-600"></i>
                    Submit Status
                </h3>
                @if(isset($analytics['final_status_stats']['submit_statuses']) && count($analytics['final_status_stats']['submit_statuses']) > 0)
                    <div class="mb-4">
                        <h4 class="text-md font-medium text-gray-700 mb-3">Submit Status</h4>
                        <div class="space-y-2">
                            @foreach($analytics['final_status_stats']['submit_statuses'] as $status => $data)
                                <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                    <span class="text-sm text-gray-700">{{ $status }}</span>
                                    <div class="flex items-center space-x-2">
                                        <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2 py-1 rounded">{{ $data['count'] }}</span>
                                        <span class="text-xs text-gray-500">({{ $data['percentage'] }}%)</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    
                    <div class="chart-fixed-height">
                        <canvas id="finalStatusChart"></canvas>
                    </div>
                @else
                    <p class="text-gray-500 text-center py-4">No submit status data available</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-6 mt-12">
        <div class="container mx-auto px-6 text-center">
            <p>&copy; {{ date('Y') }} EGYAKIN. All rights reserved. | Medical Analytics Dashboard</p>
        </div>
    </footer>

    <script>
        // Gender Distribution Chart
        const genderCtx = document.getElementById('genderChart').getContext('2d');
        new Chart(genderCtx, {
            type: 'doughnut',
            data: {
                labels: ['Male', 'Female'],
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
                labels: ['DM (Yes)', 'DM (No)', 'HTN (Yes)', 'HTN (No)'],
                datasets: [{
                    label: 'Patient Count',
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

        @if(isset($analytics['outcome_stats']['survivor_death']) && count($analytics['outcome_stats']['survivor_death']) > 0)
        // Outcome Chart - Survivor/Death breakdown
        const outcomeCtx = document.getElementById('outcomeChart').getContext('2d');
        const outcomeData = @json($analytics['outcome_stats']['survivor_death']);
        const outcomeLabels = Object.keys(outcomeData);
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

        @if(isset($analytics['final_status_stats']['submit_statuses']) && count($analytics['final_status_stats']['submit_statuses']) > 0)
        // Final Status Chart
        const finalStatusCtx = document.getElementById('finalStatusChart').getContext('2d');
        const finalStatusData = @json($analytics['final_status_stats']['submit_statuses']);
        const finalStatusLabels = Object.keys(finalStatusData);
        const finalStatusCounts = finalStatusLabels.map(label => finalStatusData[label].count);
        
        new Chart(finalStatusCtx, {
            type: 'polarArea',
            data: {
                labels: finalStatusLabels,
                datasets: [{
                    data: finalStatusCounts,
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(139, 92, 246, 0.8)',
                        'rgba(236, 72, 153, 0.8)'
                    ],
                    borderColor: [
                        '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899'
                    ],
                    borderWidth: 2
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
                                const percentage = finalStatusData[label].percentage;
                                return `${label}: ${count} (${percentage}%)`;
                            }
                        }
                    }
                },
                scales: {
                    r: {
                        beginAtZero: true,
                        ticks: {
                            font: {
                                size: 10
                            }
                        }
                    }
                }
            }
        });
        @endif
    </script>
</body>
</html>
