<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EGYAKIN Daily Report</title>
    <style>
        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #1a202c;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        
        .email-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: shimmer 3s ease-in-out infinite;
        }
        
        @keyframes shimmer {
            0%, 100% { transform: translateX(-100%) translateY(-100%) rotate(30deg); }
            50% { transform: translateX(100%) translateY(100%) rotate(30deg); }
        }
        
        .header-content {
            position: relative;
            z-index: 2;
        }
        
        .header h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .header .subtitle {
            font-size: 18px;
            opacity: 0.9;
            font-weight: 400;
        }
        
        .header .period {
            font-size: 14px;
            opacity: 0.7;
            margin-top: 5px;
        }
        
        /* Content sections */
        .content {
            padding: 30px;
            background: #f8fafc;
        }
        
        .section {
            margin: 30px 0;
            padding: 25px;
            border-radius: 12px;
            background: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
            position: relative;
            overflow: hidden;
        }
        
        .section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .section h2 {
            color: #2d3748;
            font-size: 22px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            font-weight: 600;
        }
        
        .section h2::before {
            content: '';
            width: 12px;
            height: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            margin-right: 12px;
            box-shadow: 0 2px 4px rgba(102, 126, 234, 0.3);
        }
        
        /* Stats grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .stat-number {
            font-size: 28px;
            font-weight: 700;
            color: #2d3748;
            display: block;
            margin-bottom: 8px;
        }
        
        .stat-label {
            font-size: 13px;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 500;
        }
        
        /* Highlight cards */
        .highlight-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 16px;
            margin: 30px 0;
            position: relative;
            overflow: hidden;
        }
        
        .highlight-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 4s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }
        
        .highlight-content {
            position: relative;
            z-index: 2;
        }
        
        .highlight-card h3 {
            font-size: 20px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .highlight-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        
        .highlight-item {
            text-align: center;
        }
        
        .highlight-number {
            font-size: 36px;
            font-weight: 700;
            display: block;
            margin-bottom: 8px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .highlight-label {
            font-size: 14px;
            opacity: 0.9;
            font-weight: 500;
        }
        
        /* System status */
        .system-status {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            border-radius: 12px;
            color: white;
            margin: 20px 0;
        }
        
        .status-indicator {
            width: 12px;
            height: 12px;
            background-color: #ffffff;
            border-radius: 50%;
            margin-right: 10px;
            animation: blink 2s infinite;
        }
        
        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0.3; }
        }
        
        /* Top performers */
        .performer-list {
            list-style: none;
            padding: 0;
        }

        .performer-item {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            margin: 15px 0;
            padding: 20px;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        
        .performer-item:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .performer-info {
            flex: 1;
        }

        .performer-name {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 4px;
            font-size: 16px;
        }

        .performer-details {
            font-size: 14px;
            color: #718096;
        }

        .performer-stat {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 16px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 14px;
            box-shadow: 0 2px 4px rgba(102, 126, 234, 0.3);
        }
        
        /* Footer */
        .footer {
            background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
            padding: 30px;
            text-align: center;
            color: #a0aec0;
            font-size: 14px;
        }
        
        .footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .footer a:hover {
            color: #764ba2;
        }
        
        /* Error state */
        .error-message {
            background: linear-gradient(135deg, #fed7d7 0%, #feb2b2 100%);
            border: 1px solid #fc8181;
            color: #c53030;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            font-weight: 500;
        }
        
        /* Chart-like visual elements */
        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            border: 1px solid #e2e8f0;
        }
        
        .chart-bar {
            height: 8px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 4px;
            margin: 10px 0;
            position: relative;
            overflow: hidden;
        }
        
        .chart-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            animation: slide 2s infinite;
        }
        
        @keyframes slide {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .email-container {
                width: 95% !important;
                margin: 10px auto;
            }
            
            .header {
                padding: 30px 20px;
            }
            
            .header h1 {
                font-size: 28px;
            }
            
            .content {
                padding: 20px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }
            
            .stat-card {
                padding: 15px;
            }
            
            .stat-number {
                font-size: 24px;
            }
            
            .highlight-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .performer-item {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .header h1 {
                font-size: 24px;
            }
            
            .section {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <h1>📊 EGYAKIN Analytics Dashboard</h1>
                <div class="subtitle">{{ $data['date'] ?? 'Daily Analytics Report' }}</div>
                <div class="period">{{ $data['period'] ?? '' }}</div>
            </div>
        </div>

        <div class="content">
            @if(isset($data['error']))
                <div class="error-message">
                    <strong>⚠️ Report Generation Error</strong><br>
                    {{ $data['error'] }}
                </div>
            @else
                <!-- Key Metrics Highlight -->
                <div class="highlight-card">
                    <div class="highlight-content">
                        <h3>🚀 Today's Key Metrics</h3>
                        <div class="highlight-grid">
                            <div class="highlight-item">
                                <span class="highlight-number">{{ $data['users']['new_registrations'] ?? 0 }}</span>
                                <div class="highlight-label">New Registrations</div>
                            </div>
                            <div class="highlight-item">
                                <span class="highlight-number">{{ $data['consultations']['new_consultations'] ?? 0 }}</span>
                                <div class="highlight-label">New Consultations</div>
                            </div>
                            <div class="highlight-item">
                                <span class="highlight-number">{{ $data['patients']['new_patients'] ?? 0 }}</span>
                                <div class="highlight-label">New Patients</div>
                            </div>
                            <div class="highlight-item">
                                <span class="highlight-number">{{ $data['feed']['new_posts'] ?? 0 }}</span>
                                <div class="highlight-label">New Posts</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User Statistics -->
                <div class="section">
                    <h2>👥 User Analytics</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <span class="stat-number">{{ number_format($data['users']['new_registrations'] ?? 0) }}</span>
                            <div class="stat-label">New Registrations</div>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number">{{ number_format($data['users']['total_users'] ?? 0) }}</span>
                            <div class="stat-label">Total Users</div>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number">{{ number_format($data['users']['doctors'] ?? 0) }}</span>
                            <div class="stat-label">Verified Doctors</div>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number">{{ number_format($data['users']['regular_users'] ?? 0) }}</span>
                            <div class="stat-label">Regular Users</div>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number">{{ number_format($data['users']['verified_users'] ?? 0) }}</span>
                            <div class="stat-label">Email Verified</div>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number">{{ number_format($data['users']['blocked_users'] ?? 0) }}</span>
                            <div class="stat-label">Blocked Users</div>
                        </div>
                    </div>
                    
                    <!-- Chart-like visualization -->
                    <div class="chart-container">
                        <h4 style="color: #2d3748; margin-bottom: 15px; font-weight: 600;">User Growth Trend</h4>
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                            <span style="font-size: 14px; color: #718096;">New Users Today</span>
                            <div class="chart-bar" style="width: {{ min(($data['users']['new_registrations'] ?? 0) * 10, 100) }}%;"></div>
                            <span style="font-size: 14px; color: #2d3748; font-weight: 600;">{{ $data['users']['new_registrations'] ?? 0 }}</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span style="font-size: 14px; color: #718096;">Verified Doctors</span>
                            <div class="chart-bar" style="width: {{ min(($data['users']['doctors'] ?? 0) * 2, 100) }}%;"></div>
                            <span style="font-size: 14px; color: #2d3748; font-weight: 600;">{{ $data['users']['doctors'] ?? 0 }}</span>
                        </div>
                    </div>
                </div>

                <!-- Patient Statistics -->
                <div class="section">
                    <h2>🏥 Patient Analytics</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <span class="stat-number">{{ number_format($data['patients']['new_patients'] ?? 0) }}</span>
                            <div class="stat-label">New Patients</div>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number">{{ number_format($data['patients']['total_patients'] ?? 0) }}</span>
                            <div class="stat-label">Total Patients</div>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number">{{ number_format($data['patients']['submitted_patients'] ?? 0) }}</span>
                            <div class="stat-label">Submitted</div>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number">{{ number_format($data['patients']['outcome_patients'] ?? 0) }}</span>
                            <div class="stat-label">With Outcomes</div>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number">{{ number_format($data['patients']['hidden_patients'] ?? 0) }}</span>
                            <div class="stat-label">Hidden</div>
                        </div>
                    </div>
                    
                    <!-- Patient Status Chart -->
                    <div class="chart-container">
                        <h4 style="color: #2d3748; margin-bottom: 15px; font-weight: 600;">Patient Status Distribution</h4>
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                            <span style="font-size: 14px; color: #718096;">Submitted Patients</span>
                            <div class="chart-bar" style="width: {{ min(($data['patients']['submitted_patients'] ?? 0) * 5, 100) }}%;"></div>
                            <span style="font-size: 14px; color: #2d3748; font-weight: 600;">{{ $data['patients']['submitted_patients'] ?? 0 }}</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span style="font-size: 14px; color: #718096;">Outcome Patients</span>
                            <div class="chart-bar" style="width: {{ min(($data['patients']['outcome_patients'] ?? 0) * 5, 100) }}%;"></div>
                            <span style="font-size: 14px; color: #2d3748; font-weight: 600;">{{ $data['patients']['outcome_patients'] ?? 0 }}</span>
                        </div>
                    </div>
                </div>

                <!-- Consultation Statistics -->
                <div class="section">
                    <h2>💬 Consultation Analytics</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <span class="stat-number">{{ number_format($data['consultations']['new_consultations'] ?? 0) }}</span>
                            <div class="stat-label">New Today</div>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number">{{ number_format($data['consultations']['new_ai_consultations'] ?? 0) }}</span>
                            <div class="stat-label">New AI Today</div>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number">{{ number_format($data['consultations']['ai_consultations'] ?? 0) }}</span>
                            <div class="stat-label">Total AI</div>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number">{{ number_format($data['consultations']['pending_consultations'] ?? 0) }}</span>
                            <div class="stat-label">Pending</div>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number">{{ number_format($data['consultations']['completed_consultations'] ?? 0) }}</span>
                            <div class="stat-label">Completed</div>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number">{{ number_format($data['consultations']['open_consultations'] ?? 0) }}</span>
                            <div class="stat-label">Open</div>
                        </div>
                    </div>
                    
                    <!-- Consultation Activity Chart -->
                    <div class="chart-container">
                        <h4 style="color: #2d3748; margin-bottom: 15px; font-weight: 600;">Consultation Activity</h4>
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                            <span style="font-size: 14px; color: #718096;">AI Consultations</span>
                            <div class="chart-bar" style="width: {{ min(($data['consultations']['ai_consultations'] ?? 0) * 2, 100) }}%;"></div>
                            <span style="font-size: 14px; color: #2d3748; font-weight: 600;">{{ $data['consultations']['ai_consultations'] ?? 0 }}</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span style="font-size: 14px; color: #718096;">Pending Consultations</span>
                            <div class="chart-bar" style="width: {{ min(($data['consultations']['pending_consultations'] ?? 0) * 3, 100) }}%;"></div>
                            <span style="font-size: 14px; color: #2d3748; font-weight: 600;">{{ $data['consultations']['pending_consultations'] ?? 0 }}</span>
                        </div>
                    </div>
                </div>

                <!-- Feed Activity -->
                <div class="section">
                    <h2>📱 Community Analytics</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <span class="stat-number">{{ number_format($data['feed']['new_posts'] ?? 0) }}</span>
                            <div class="stat-label">New Posts</div>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number">{{ number_format($data['feed']['posts_with_media'] ?? 0) }}</span>
                            <div class="stat-label">Media Posts</div>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number">{{ number_format($data['groups']['new_groups'] ?? 0) }}</span>
                            <div class="stat-label">New Groups</div>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number">{{ number_format($data['groups']['total_groups'] ?? 0) }}</span>
                            <div class="stat-label">Total Groups</div>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number">{{ number_format($data['feed']['group_posts'] ?? 0) }}</span>
                            <div class="stat-label">Group Posts</div>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number">{{ number_format($data['groups']['private_groups'] ?? 0) }}</span>
                            <div class="stat-label">Private Groups</div>
                        </div>
                    </div>
                    
                    <!-- Community Activity Chart -->
                    <div class="chart-container">
                        <h4 style="color: #2d3748; margin-bottom: 15px; font-weight: 600;">Community Engagement</h4>
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                            <span style="font-size: 14px; color: #718096;">Total Posts</span>
                            <div class="chart-bar" style="width: {{ min(($data['feed']['total_posts'] ?? 0) * 1, 100) }}%;"></div>
                            <span style="font-size: 14px; color: #2d3748; font-weight: 600;">{{ $data['feed']['total_posts'] ?? 0 }}</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span style="font-size: 14px; color: #718096;">Media Posts</span>
                            <div class="chart-bar" style="width: {{ min(($data['feed']['posts_with_media'] ?? 0) * 2, 100) }}%;"></div>
                            <span style="font-size: 14px; color: #2d3748; font-weight: 600;">{{ $data['feed']['posts_with_media'] ?? 0 }}</span>
                        </div>
                    </div>
                </div>

                <!-- Top Performers -->
                <div class="section">
                    <h2>🏆 Top Performers Today</h2>
                    
                    <h3 style="color: #667eea; font-size: 18px; margin: 20px 0 15px 0;">Doctors Adding Patients</h3>
                    @if(isset($data['top_performers']['doctors_with_patients']) && count($data['top_performers']['doctors_with_patients']) > 0)
                        <ul class="performer-list">
                            @foreach($data['top_performers']['doctors_with_patients'] as $doctor)
                                <li class="performer-item">
                                    <div class="performer-info">
                                        <div class="performer-name">{{ $doctor['name'] ?? 'Unknown' }}</div>
                                        <div class="performer-details">{{ $doctor['specialty'] ?? 'General' }}</div>
                                    </div>
                                    <div class="performer-stat">{{ $doctor['patients_count'] ?? 0 }} patients</div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p style="color: #7f8c8d; font-style: italic;">No doctors added patients today</p>
                    @endif

                    <h3 style="color: #667eea; font-size: 18px; margin: 25px 0 15px 0;">Doctors Creating Posts</h3>
                    @if(isset($data['top_performers']['doctors_with_posts']) && count($data['top_performers']['doctors_with_posts']) > 0)
                        <ul class="performer-list">
                            @foreach($data['top_performers']['doctors_with_posts'] as $doctor)
                                <li class="performer-item">
                                    <div class="performer-info">
                                        <div class="performer-name">{{ $doctor['name'] ?? 'Unknown' }}</div>
                                        <div class="performer-details">{{ $doctor['specialty'] ?? 'General' }}</div>
                                    </div>
                                    <div class="performer-stat">{{ $doctor['posts_count'] ?? 0 }} posts</div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p style="color: #7f8c8d; font-style: italic;">No doctors created posts today</p>
                    @endif
                </div>

                <!-- System Status -->
                <div class="section">
                    <h2>⚙️ System Health</h2>
                    <div class="system-status">
                        <div style="display: flex; align-items: center;">
                            <div class="status-indicator"></div>
                            <span>System Status: Operational</span>
                        </div>
                        <div style="font-size: 14px; color: #666;">
                            Last Updated: {{ now()->format('H:i T') }}
                        </div>
                    </div>
                    <div style="margin-top: 10px; font-size: 14px; color: #666;">
                        <div>Database Size: {{ $data['system']['database_size'] ?? 'N/A' }}</div>
                        <div>Storage Usage: {{ $data['system']['storage_usage'] ?? 'N/A' }}</div>
                        <div>Last Backup: {{ $data['system']['last_backup'] ?? 'N/A' }}</div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Footer -->
        <div class="footer">
            <p style="font-size: 16px; font-weight: 600; margin-bottom: 10px;">📊 EGYAKIN Analytics Dashboard</p>
            <p>This automated report provides comprehensive insights into your platform's daily performance.</p>
            <p style="margin: 15px 0;">© {{ date('Y') }} EGYAKIN. All rights reserved.</p>
            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #4a5568;">
                <a href="#" style="margin: 0 15px;">📈 Dashboard</a> | 
                <a href="#" style="margin: 0 15px;">📊 Reports</a> | 
                <a href="#" style="margin: 0 15px;">⚙️ Settings</a> | 
                <a href="#" style="margin: 0 15px;">💬 Support</a>
            </div>
            <p style="margin-top: 15px; font-size: 12px; opacity: 0.7;">
                Generated on {{ now()->format('F j, Y \a\t H:i T') }} | 
                Report ID: {{ strtoupper(substr(md5(now()->toDateString()), 0, 8)) }}
            </p>
        </div>
    </div>
</body>
</html>
