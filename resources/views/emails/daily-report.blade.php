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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f6f9;
        }
        
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .header .subtitle {
            font-size: 16px;
            opacity: 0.9;
        }
        
        /* Content sections */
        .content {
            padding: 0 20px 20px;
        }
        
        .section {
            margin: 25px 0;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
            background-color: #f8f9ff;
        }
        
        .section h2 {
            color: #667eea;
            font-size: 20px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .section h2::before {
            content: '';
            width: 8px;
            height: 8px;
            background-color: #667eea;
            border-radius: 50%;
            margin-right: 10px;
        }
        
        /* Stats grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }
        
        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: #667eea;
            display: block;
        }
        
        .stat-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 5px;
        }
        
        /* Highlight cards */
        .highlight-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .highlight-card h3 {
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .highlight-number {
            font-size: 32px;
            font-weight: 700;
            display: block;
        }
        
        /* System status */
        .system-status {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #e8f5e8;
            border-radius: 6px;
            border: 1px solid #4caf50;
        }
        
        .status-indicator {
            width: 12px;
            height: 12px;
            background-color: #4caf50;
            border-radius: 50%;
            margin-right: 8px;
        }
        
        /* Footer */
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #e9ecef;
            color: #666;
            font-size: 14px;
        }
        
        .footer a {
            color: #667eea;
            text-decoration: none;
        }
        
        /* Error state */
        .error-message {
            background: #fff5f5;
            border: 1px solid #fed7d7;
            color: #c53030;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
        }
        
        /* Responsive design */
        @media (max-width: 600px) {
            .email-container {
                width: 100% !important;
            }
            
            .header {
                padding: 20px 15px;
            }
            
            .header h1 {
                font-size: 24px;
            }
            
            .content {
                padding: 0 15px 15px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
            
            .stat-card {
                padding: 12px;
            }
            
            .stat-number {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <h1>📊 EGYAKIN Daily Report</h1>
            <div class="subtitle">{{ $data['date'] ?? 'Daily Analytics' }}</div>
            <div class="subtitle" style="opacity: 0.7; font-size: 14px;">{{ $data['period'] ?? '' }}</div>
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
                    <h3>🚀 Today's Highlights</h3>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-top: 15px;">
                        <div>
                            <span class="highlight-number">{{ $data['users']['new_registrations'] ?? 0 }}</span>
                            <div>New Doctors</div>
                        </div>
                        <div>
                            <span class="highlight-number">{{ $data['consultations']['new_consultations'] ?? 0 }}</span>
                            <div>New Consultations</div>
                        </div>
                    </div>
                </div>

                <!-- User Statistics -->
                <div class="section">
                    <h2>👥 User Statistics</h2>
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
                            <span class="stat-number">{{ number_format($data['users']['verified_users'] ?? 0) }}</span>
                            <div class="stat-label">Verified Users</div>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number">{{ number_format($data['users']['active_users'] ?? 0) }}</span>
                            <div class="stat-label">Active Users</div>
                        </div>
                    </div>
                </div>

                <!-- Patient Statistics -->
                <div class="section">
                    <h2>🏥 Patient Management</h2>
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
                            <span class="stat-number">{{ number_format($data['patients']['hidden_patients'] ?? 0) }}</span>
                            <div class="stat-label">Archived</div>
                        </div>
                    </div>
                </div>

                <!-- Consultation Statistics -->
                <div class="section">
                    <h2>💬 Consultations</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <span class="stat-number">{{ number_format($data['consultations']['new_consultations'] ?? 0) }}</span>
                            <div class="stat-label">New Today</div>
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
                </div>

                <!-- Feed Activity -->
                <div class="section">
                    <h2>📱 Community Activity</h2>
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
                    </div>
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
            <p>This is an automated report generated by EGYAKIN system.</p>
            <p>© {{ date('Y') }} EGYAKIN. All rights reserved.</p>
            <p style="margin-top: 10px;">
                <a href="#">Dashboard</a> | 
                <a href="#">Support</a> | 
                <a href="#">Settings</a>
            </p>
        </div>
    </div>
</body>
</html>
