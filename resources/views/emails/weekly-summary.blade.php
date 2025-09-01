<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EGYAKIN Weekly Summary</title>
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
            max-width: 700px;
            margin: 0 auto;
            background-color: #ffffff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .header .period {
            font-size: 18px;
            opacity: 0.9;
            font-weight: 300;
        }
        
        /* Content sections */
        .content {
            padding: 0 25px 25px;
        }
        
        .section {
            margin: 30px 0;
            padding: 25px;
            border-radius: 10px;
            background: linear-gradient(135deg, #f8f9ff 0%, #f0f4ff 100%);
            border: 1px solid #e8f2ff;
        }
        
        .section h2 {
            color: #2c3e50;
            font-size: 24px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        
        .section h2::before {
            content: '';
            width: 12px;
            height: 12px;
            background: linear-gradient(135deg, #3498db, #2980b9);
            border-radius: 50%;
            margin-right: 12px;
        }
        
        /* Weekly overview cards */
        .overview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin: 25px 0;
        }
        
        .overview-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #3498db;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .overview-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        }
        
        .metric-number {
            font-size: 28px;
            font-weight: 700;
            color: #2c3e50;
            display: block;
            margin-bottom: 5px;
        }
        
        .metric-label {
            font-size: 14px;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .growth-indicator {
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 12px;
            margin-top: 8px;
            display: inline-block;
            font-weight: 600;
        }
        
        .growth-positive {
            background-color: #d4edda;
            color: #155724;
        }
        
        .growth-negative {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .growth-neutral {
            background-color: #e2e3e5;
            color: #383d41;
        }
        
        /* Comparison table */
        .comparison-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .comparison-table th {
            background: #3498db;
            color: white;
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
        }
        
        .comparison-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        
        .comparison-table tr:last-child td {
            border-bottom: none;
        }
        
        .comparison-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        /* Top performers */
        .performer-list {
            list-style: none;
            padding: 0;
        }
        
        .performer-item {
            background: white;
            margin: 10px 0;
            padding: 15px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .performer-info {
            flex: 1;
        }
        
        .performer-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 4px;
        }
        
        .performer-details {
            font-size: 14px;
            color: #7f8c8d;
        }
        
        .performer-stat {
            background: #3498db;
            color: white;
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }
        
        /* Insights section */
        .insight-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            margin: 25px 0;
        }
        
        .insight-card h3 {
            font-size: 20px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .insight-card h3::before {
            content: '💡';
            margin-right: 10px;
            font-size: 24px;
        }
        
        .insight-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .insight-stat {
            text-align: center;
        }
        
        .insight-number {
            font-size: 24px;
            font-weight: 700;
            display: block;
        }
        
        .insight-label {
            font-size: 12px;
            opacity: 0.8;
            text-transform: uppercase;
            margin-top: 4px;
        }
        
        /* Footer */
        .footer {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 25px;
            text-align: center;
            font-size: 14px;
        }
        
        .footer a {
            color: #ecf0f1;
            text-decoration: none;
            margin: 0 10px;
        }
        
        .footer a:hover {
            text-decoration: underline;
        }
        
        /* Error state */
        .error-message {
            background: #fff5f5;
            border: 1px solid #fed7d7;
            color: #c53030;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
        }
        
        /* Responsive design */
        @media (max-width: 700px) {
            .email-container {
                width: 100% !important;
            }
            
            .header {
                padding: 25px 15px;
            }
            
            .header h1 {
                font-size: 28px;
            }
            
            .content {
                padding: 0 15px 20px;
            }
            
            .section {
                padding: 20px 15px;
                margin: 20px 0;
            }
            
            .overview-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }
            
            .comparison-table {
                font-size: 14px;
            }
            
            .comparison-table th,
            .comparison-table td {
                padding: 10px 8px;
            }
            
            .performer-item {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <h1>📈 Weekly Summary</h1>
            <div class="period">{{ $data['week_period'] ?? 'Weekly Analytics' }}</div>
        </div>

        <div class="content">
            @if(isset($data['error']))
                <div class="error-message">
                    <strong>⚠️ Summary Generation Error</strong><br>
                    {{ $data['error'] }}
                </div>
            @else
                <!-- Weekly Overview -->
                <div class="section">
                    <h2>📊 This Week's Performance</h2>
                    <div class="overview-grid">
                        <div class="overview-card">
                            <span class="metric-number">{{ number_format($data['current_week']['new_doctors'] ?? 0) }}</span>
                            <div class="metric-label">New Doctors</div>
                            @php
                                $growth = $data['growth']['new_doctors'] ?? 0;
                                $growthClass = $growth > 0 ? 'growth-positive' : ($growth < 0 ? 'growth-negative' : 'growth-neutral');
                                $growthIcon = $growth > 0 ? '↗️' : ($growth < 0 ? '↘️' : '➡️');
                            @endphp
                            <div class="growth-indicator {{ $growthClass }}">
                                {{ $growthIcon }} {{ $growth > 0 ? '+' : '' }}{{ $growth }}%
                            </div>
                        </div>
                        
                        <div class="overview-card">
                            <span class="metric-number">{{ number_format($data['current_week']['new_patients'] ?? 0) }}</span>
                            <div class="metric-label">New Patients</div>
                            @php
                                $growth = $data['growth']['new_patients'] ?? 0;
                                $growthClass = $growth > 0 ? 'growth-positive' : ($growth < 0 ? 'growth-negative' : 'growth-neutral');
                                $growthIcon = $growth > 0 ? '↗️' : ($growth < 0 ? '↘️' : '➡️');
                            @endphp
                            <div class="growth-indicator {{ $growthClass }}">
                                {{ $growthIcon }} {{ $growth > 0 ? '+' : '' }}{{ $growth }}%
                            </div>
                        </div>
                        
                        <div class="overview-card">
                            <span class="metric-number">{{ number_format($data['current_week']['new_ai_consultations'] ?? 0) }}</span>
                            <div class="metric-label">AI Consultations</div>
                            @php
                                $growth = $data['growth']['new_ai_consultations'] ?? 0;
                                $growthClass = $growth > 0 ? 'growth-positive' : ($growth < 0 ? 'growth-negative' : 'growth-neutral');
                                $growthIcon = $growth > 0 ? '↗️' : ($growth < 0 ? '↘️' : '➡️');
                            @endphp
                            <div class="growth-indicator {{ $growthClass }}">
                                {{ $growthIcon }} {{ $growth > 0 ? '+' : '' }}{{ $growth }}%
                            </div>
                        </div>
                        
                        <div class="overview-card">
                            <span class="metric-number">{{ number_format($data['current_week']['new_posts'] ?? 0) }}</span>
                            <div class="metric-label">New Posts</div>
                            @php
                                $growth = $data['growth']['new_posts'] ?? 0;
                                $growthClass = $growth > 0 ? 'growth-positive' : ($growth < 0 ? 'growth-negative' : 'growth-neutral');
                                $growthIcon = $growth > 0 ? '↗️' : ($growth < 0 ? '↘️' : '➡️');
                            @endphp
                            <div class="growth-indicator {{ $growthClass }}">
                                {{ $growthIcon }} {{ $growth > 0 ? '+' : '' }}{{ $growth }}%
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Week-over-Week Comparison -->
                <div class="section">
                    <h2>📈 Week-over-Week Comparison</h2>
                    <table class="comparison-table">
                        <thead>
                            <tr>
                                <th>Metric</th>
                                <th>This Week</th>
                                <th>Last Week</th>
                                <th>Change</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>New Doctors</td>
                                <td>{{ number_format($data['current_week']['new_doctors'] ?? 0) }}</td>
                                <td>{{ number_format($data['last_week']['new_doctors'] ?? 0) }}</td>
                                <td>
                                    @php $growth = $data['growth']['new_doctors'] ?? 0; @endphp
                                    <span class="growth-indicator {{ $growth > 0 ? 'growth-positive' : ($growth < 0 ? 'growth-negative' : 'growth-neutral') }}">
                                        {{ $growth > 0 ? '+' : '' }}{{ $growth }}%
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td>New Patients</td>
                                <td>{{ number_format($data['current_week']['new_patients'] ?? 0) }}</td>
                                <td>{{ number_format($data['last_week']['new_patients'] ?? 0) }}</td>
                                <td>
                                    @php $growth = $data['growth']['new_patients'] ?? 0; @endphp
                                    <span class="growth-indicator {{ $growth > 0 ? 'growth-positive' : ($growth < 0 ? 'growth-negative' : 'growth-neutral') }}">
                                        {{ $growth > 0 ? '+' : '' }}{{ $growth }}%
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td>AI Consultations</td>
                                <td>{{ number_format($data['current_week']['new_ai_consultations'] ?? 0) }}</td>
                                <td>{{ number_format($data['last_week']['new_ai_consultations'] ?? 0) }}</td>
                                <td>
                                    @php $growth = $data['growth']['new_ai_consultations'] ?? 0; @endphp
                                    <span class="growth-indicator {{ $growth > 0 ? 'growth-positive' : ($growth < 0 ? 'growth-negative' : 'growth-neutral') }}">
                                        {{ $growth > 0 ? '+' : '' }}{{ $growth }}%
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td>Community Posts</td>
                                <td>{{ number_format($data['current_week']['new_posts'] ?? 0) }}</td>
                                <td>{{ number_format($data['last_week']['new_posts'] ?? 0) }}</td>
                                <td>
                                    @php $growth = $data['growth']['new_posts'] ?? 0; @endphp
                                    <span class="growth-indicator {{ $growth > 0 ? 'growth-positive' : ($growth < 0 ? 'growth-negative' : 'growth-neutral') }}">
                                        {{ $growth > 0 ? '+' : '' }}{{ $growth }}%
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Top Performers -->
                <div class="section">
                    <h2>🏆 Top Performers This Week</h2>
                    
                    <h3 style="color: #3498db; font-size: 18px; margin: 20px 0 15px 0;">Doctors Adding Patients</h3>
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
                        <p style="color: #7f8c8d; font-style: italic;">No doctors added patients this week</p>
                    @endif

                    <h3 style="color: #3498db; font-size: 18px; margin: 25px 0 15px 0;">Doctors Creating Posts</h3>
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
                        <p style="color: #7f8c8d; font-style: italic;">No doctors created posts this week</p>
                    @endif

                    <h3 style="color: #3498db; font-size: 18px; margin: 25px 0 15px 0;">Popular Posts</h3>
                    @if(isset($data['top_performers']['popular_posts']) && count($data['top_performers']['popular_posts']) > 0)
                        <ul class="performer-list">
                            @foreach($data['top_performers']['popular_posts'] as $post)
                                <li class="performer-item">
                                    <div class="performer-info">
                                        <div class="performer-name">{{ Str::limit($post['content'] ?? '', 60) }}</div>
                                        <div class="performer-details">by {{ $post['author'] ?? 'Unknown' }}</div>
                                    </div>
                                    <div class="performer-stat">{{ $post['likes'] ?? 0 }} ❤️ {{ $post['comments'] ?? 0 }} 💬</div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p style="color: #7f8c8d; font-style: italic;">No popular posts this week</p>
                    @endif
                </div>

                <!-- Engagement Insights -->
                <div class="insight-card">
                    <h3>User Engagement Insights</h3>
                    <div class="insight-stats">
                        <div class="insight-stat">
                            <span class="insight-number">{{ $data['trends']['user_engagement']['engagement_rate'] ?? 0 }}%</span>
                            <div class="insight-label">Engagement Rate</div>
                        </div>
                        <div class="insight-stat">
                            <span class="insight-number">{{ number_format($data['trends']['content_performance']['average_likes_per_post'] ?? 0, 1) }}</span>
                            <div class="insight-label">Avg Likes/Post</div>
                        </div>
                        <div class="insight-stat">
                            <span class="insight-number">{{ number_format($data['trends']['content_performance']['average_comments_per_post'] ?? 0, 1) }}</span>
                            <div class="insight-label">Avg Comments/Post</div>
                        </div>
                        <div class="insight-stat">
                            <span class="insight-number">{{ number_format($data['trends']['consultation_patterns']['total_consultations'] ?? 0) }}</span>
                            <div class="insight-label">Total Consultations</div>
                        </div>
                    </div>
                </div>

                <!-- System Overview -->
                <div class="section">
                    <h2>🌐 Platform Overview</h2>
                    <div class="overview-grid">
                        <div class="overview-card">
                            <span class="metric-number">{{ number_format($data['system_overview']['total_doctors'] ?? 0) }}</span>
                            <div class="metric-label">Total Doctors</div>
                        </div>
                        <div class="overview-card">
                            <span class="metric-number">{{ number_format($data['system_overview']['total_patients'] ?? 0) }}</span>
                            <div class="metric-label">Total Patients</div>
                        </div>
                        <div class="overview-card">
                            <span class="metric-number">{{ number_format($data['system_overview']['total_ai_consultations'] ?? 0) }}</span>
                            <div class="metric-label">AI Consultations</div>
                        </div>
                        <div class="overview-card">
                            <span class="metric-number">{{ number_format($data['system_overview']['total_posts'] ?? 0) }}</span>
                            <div class="metric-label">Community Posts</div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>EGYAKIN Weekly Summary Report</strong></p>
            <p>Generated on {{ now()->format('F j, Y \a\t H:i T') }}</p>
            <div style="margin-top: 15px;">
                <a href="#">Dashboard</a>
                <a href="#">Analytics</a>
                <a href="#">Support</a>
                <a href="#">Settings</a>
            </div>
            <p style="margin-top: 15px; opacity: 0.8;">© {{ date('Y') }} EGYAKIN. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
