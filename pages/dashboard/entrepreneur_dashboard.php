<?php
// Include database connection and functions
require_once('../../includes/db_connect.php');
require_once('../../includes/functions.php');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'entrepreneur') {
    header("Location: ../login.php");
    exit();
}

// Get user data
$userData = get_user_data($conn, $_SESSION['user_id']);

// Mock data for dashboard - in a real application, this would come from the database
$userIdeas = [
    [
        'id' => 1,
        'title' => 'Smart City Waste Management',
        'submission_date' => '2023-09-15',
        'status' => 'Under Review',
        'views' => 24,
        'connections' => 2
    ],
    [
        'id' => 2,
        'title' => 'Renewable Energy Grid Integration',
        'submission_date' => '2023-10-05',
        'status' => 'Approved',
        'views' => 38,
        'connections' => 4
    ],
    [
        'id' => 3,
        'title' => 'AI-Driven Public Transport Optimization',
        'submission_date' => '2023-11-20',
        'status' => 'Pending',
        'views' => 16,
        'connections' => 1
    ]
];

$govtInitiatives = [
    [
        'id' => 1,
        'title' => 'Smart City Development Program',
        'department' => 'Urban Development',
        'posted_date' => '2023-10-10',
        'deadline' => '2023-12-31',
        'match_score' => 92
    ],
    [
        'id' => 2,
        'title' => 'Clean Energy Innovation Challenge',
        'department' => 'Energy',
        'posted_date' => '2023-11-05',
        'deadline' => '2024-01-15',
        'match_score' => 85
    ],
    [
        'id' => 3,
        'title' => 'Digital Governance Transformation',
        'department' => 'IT & Communication',
        'posted_date' => '2023-11-12',
        'deadline' => '2024-02-28',
        'match_score' => 78
    ]
];

$recentActivities = [
    [
        'type' => 'connection',
        'title' => 'New connection request from Ministry of Technology',
        'time' => '2 hours ago',
        'icon' => 'fa-handshake'
    ],
    [
        'type' => 'view',
        'title' => 'Your proposal "Smart City Waste Management" was viewed',
        'time' => '1 day ago',
        'icon' => 'fa-eye'
    ],
    [
        'type' => 'comment',
        'title' => 'New comment on your "Renewable Energy Grid Integration" idea',
        'time' => '2 days ago',
        'icon' => 'fa-comment'
    ],
    [
        'type' => 'update',
        'title' => 'Status update on your "AI-Driven Public Transport Optimization"',
        'time' => '3 days ago',
        'icon' => 'fa-refresh'
    ]
];

// Calculate statistics
$totalIdeas = count($userIdeas);
$totalConnections = 0;
$totalViews = 0;
$projectsInDevelopment = 0;

foreach ($userIdeas as $idea) {
    $totalViews += $idea['views'];
    $totalConnections += $idea['connections'];
    if ($idea['status'] === 'Approved') {
        $projectsInDevelopment++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ColabX - Entrepreneur Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <!-- Chart.js for data visualization -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Header -->
    <header>
        <nav class="navbar flex">
            <a href="../../index.php" class="logo">
                Colab<span>X</span>
            </a>
            <ul class="navlist flex">
                <li><a href="../../index.php" class="link">Home</a></li>
                <li><a href="../colab.php" class="link">Project</a></li>
                <li><a href="../innovation.php" class="link">Innovation</a></li>
                <li><a href="../about.php" class="link">About Us</a></li>
                <li><a href="entrepreneur_dashboard.php" class="link">Dashboard</a></li>
            </ul>
            <div class="user-actions">
                <div class="notification-badge" data-count="3">
                    <i class="fas fa-bell"></i>
                </div>
                <form action="../logout.php" method="POST">
                    <button type="submit" class="btn sign-in">Logout</button>
                </form>
                
                <!-- Language Selector -->
                <div class="language-dropdown">
                    <button class="lang-btn">
                        <i class="fa-solid fa-globe"></i> EN
                    </button>
                    <ul class="language-list">
                        <li>English</li>
                        <li>አማርኛ</li>
                        <li>العربية</li>
                        <li>বাংলা</li>
                        <li>简体中文</li>
                        <li>Français</li>
                        <li>हिंदी</li>
                        <li>Bahasa Indonesia</li>
                        <li>Português</li>
                        <li>Español</li>
                        <li>Kiswahili</li>
                        <li>ไทย</li>
                        <li>اردو</li>
                        <li>Tiếng Việt</li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1 class="welcome-message">Welcome, <?php echo htmlspecialchars($userData['full_name']); ?>!</h1>
            <form action="../logout.php" method="POST">
                <button type="submit" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</button>
            </form>
        </div>
        
        <div class="dashboard-content">
            <h2>Entrepreneur Dashboard</h2>
            <p>Manage your ideas, connect with government organizations, and track your project progress.</p>
            
            <div class="action-buttons">
                <a href="../colab.php"><button class="action-btn primary-btn"><i class="fas fa-lightbulb"></i> Submit New Idea</button></a>
                <button class="action-btn secondary-btn" id="browseInitiatives"><i class="fas fa-building-columns"></i> Browse Government Initiatives</button>
                <button class="action-btn secondary-btn"><i class="fas fa-bell"></i> Manage Notifications</button>
                <button class="action-btn secondary-btn"><i class="fas fa-chart-line"></i> View Analytics</button>
            </div>
            
            <div class="stats-container">
                <div class="stat-card">
                    <div class="icon"><i class="fas fa-lightbulb"></i></div>
                    <h3>Ideas Submitted</h3>
                    <div class="stat-number"><?php echo $totalIdeas; ?></div>
                    <p>Total submissions</p>
                </div>
                
                <div class="stat-card">
                    <div class="icon"><i class="fas fa-handshake"></i></div>
                    <h3>Connections</h3>
                    <div class="stat-number"><?php echo $totalConnections; ?></div>
                    <p>With government orgs</p>
                </div>
                
                <div class="stat-card">
                    <div class="icon"><i class="fas fa-rocket"></i></div>
                    <h3>Projects</h3>
                    <div class="stat-number"><?php echo $projectsInDevelopment; ?></div>
                    <p>In development</p>
                </div>
                
                <div class="stat-card">
                    <div class="icon"><i class="fas fa-eye"></i></div>
                    <h3>Total Views</h3>
                    <div class="stat-number"><?php echo $totalViews; ?></div>
                    <p>On all your ideas</p>
                </div>
            </div>
            
            <!-- Performance Chart -->
            <div class="chart-container">
                <div class="chart-header">
                    <h3 class="chart-title">Performance Overview</h3>
                    <div class="chart-actions">
                        <button class="chart-btn active" data-period="week">Week</button>
                        <button class="chart-btn" data-period="month">Month</button>
                        <button class="chart-btn" data-period="year">Year</button>
                    </div>
                </div>
                <div style="height: 250px; max-height: 250px; position: relative;">
                    <canvas id="performanceChart"></canvas>
                </div>
            </div>
            
            <!-- Your Ideas Section -->
            <div class="dashboard-section">
                <h3>Your Ideas & Submissions</h3>
                <table class="dashboard-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Submission Date</th>
                            <th>Status</th>
                            <th>Views</th>
                            <th>Connections</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($userIdeas as $idea): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($idea['title']); ?></td>
                            <td><?php echo htmlspecialchars($idea['submission_date']); ?></td>
                            <td>
                                <?php 
                                $statusClass = '';
                                switch($idea['status']) {
                                    case 'Approved':
                                        $statusClass = 'status-approved';
                                        break;
                                    case 'Under Review':
                                        $statusClass = 'status-pending';
                                        break;
                                    case 'Pending':
                                        $statusClass = 'status-pending';
                                        break;
                                    default:
                                        $statusClass = '';
                                }
                                ?>
                                <span class="status-pill <?php echo $statusClass; ?>"><?php echo htmlspecialchars($idea['status']); ?></span>
                            </td>
                            <td><?php echo $idea['views']; ?></td>
                            <td><?php echo $idea['connections']; ?></td>
                            <td>
                                <div class="tooltip">
                                    <i class="fas fa-eye"></i>
                                    <span class="tooltip-text">View</span>
                                </div>
                                <div class="tooltip">
                                    <i class="fas fa-edit"></i>
                                    <span class="tooltip-text">Edit</span>
                                </div>
                                <div class="tooltip">
                                    <i class="fas fa-trash-alt"></i>
                                    <span class="tooltip-text">Delete</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Matching Government Initiatives -->
            <div class="dashboard-section" id="initiativesSection" style="display: none;">
                <h3>Matching Government Initiatives</h3>
                <p>Based on your profile and submitted ideas, we found these potential matches for collaboration:</p>
                
                <div class="stats-container">
                    <?php foreach ($govtInitiatives as $initiative): ?>
                    <div class="stat-card hover-card">
                        <h3><?php echo htmlspecialchars($initiative['title']); ?></h3>
                        <p><strong>Department:</strong> <?php echo htmlspecialchars($initiative['department']); ?></p>
                        <p><strong>Posted:</strong> <?php echo htmlspecialchars($initiative['posted_date']); ?></p>
                        <p><strong>Deadline:</strong> <?php echo htmlspecialchars($initiative['deadline']); ?></p>
                        <div class="match-score">
                            <div class="score-label">Match Score</div>
                            <div class="score-value"><?php echo $initiative['match_score']; ?>%</div>
                            <div class="score-bar">
                                <div class="score-fill" style="width: <?php echo $initiative['match_score']; ?>%;"></div>
                            </div>
                        </div>
                        <button class="action-btn primary-btn" style="margin-top: 15px; width: 100%;">Express Interest</button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="dashboard-section">
                <h3>Recent Activity</h3>
                <?php foreach ($recentActivities as $activity): ?>
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="fas <?php echo $activity['icon']; ?>"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title"><?php echo htmlspecialchars($activity['title']); ?></div>
                        <div class="activity-time"><?php echo htmlspecialchars($activity['time']); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Profile Section -->
            <div class="profile-section">
                <div class="profile-image">
                    <div class="profile-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="profile-name"><?php echo htmlspecialchars($userData['full_name']); ?></div>
                    <div class="profile-role">Entrepreneur</div>
                </div>
                
                <div class="profile-details">
                    <h3>Profile Information</h3>
                    <div class="profile-info">
                        <div class="info-item">
                            <strong>Full Name</strong>
                            <span><?php echo htmlspecialchars($userData['full_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <strong>Email</strong>
                            <span><?php echo htmlspecialchars($userData['email']); ?></span>
                        </div>
                        <div class="info-item">
                            <strong>Company</strong>
                            <span><?php echo htmlspecialchars($userData['company_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <strong>Business Type</strong>
                            <span><?php echo htmlspecialchars($userData['business_type']); ?></span>
                        </div>
                        <div class="info-item">
                            <strong>Account Type</strong>
                            <span>Entrepreneur</span>
                        </div>
                        <div class="info-item">
                            <strong>Member Since</strong>
                            <span>November 2023</span>
                        </div>
                    </div>
                    
                    <button class="action-btn secondary-btn" style="margin-top: 20px;">
                        <i class="fas fa-edit"></i> Edit Profile
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Toast notifications container -->
    <div class="toast-container">
        <!-- Example toast notification -->
        <div class="toast toast-success">
            <div class="toast-icon"><i class="fas fa-check-circle"></i></div>
            <div class="toast-content">
                <div class="toast-title">Success!</div>
                <div class="toast-message">Your profile has been updated successfully.</div>
            </div>
            <div class="toast-close"><i class="fas fa-times"></i></div>
        </div>
    </div>

    <script>
        // Language dropdown functionality
        document.addEventListener("DOMContentLoaded", function() {
            const langDropdown = document.querySelector(".language-dropdown");
            const langBtn = document.querySelector(".lang-btn");
            
            langBtn.addEventListener("click", function() {
                langDropdown.classList.toggle("active");
            });
            
            document.addEventListener("click", function(event) {
                if (!langDropdown.contains(event.target) && !langBtn.contains(event.target)) {
                    langDropdown.classList.remove("active");
                }
            });
            
            // Toggle government initiatives section
            const browseBtn = document.getElementById('browseInitiatives');
            const initiativesSection = document.getElementById('initiativesSection');
            
            browseBtn.addEventListener('click', function() {
                if (initiativesSection.style.display === 'none') {
                    initiativesSection.style.display = 'block';
                    // Smooth scroll to initiatives section
                    initiativesSection.scrollIntoView({ behavior: 'smooth' });
                } else {
                    initiativesSection.style.display = 'none';
                }
            });
            
            // Close toast notification
            const toastCloseBtn = document.querySelector('.toast-close');
            if (toastCloseBtn) {
                toastCloseBtn.addEventListener('click', function() {
                    this.closest('.toast').remove();
                });
                
                // Auto-hide toast after 3 seconds
                setTimeout(() => {
                    const toast = document.querySelector('.toast');
                    if (toast) toast.remove();
                }, 3000);
            }
            
            // Initialize performance chart
            const ctx = document.getElementById('performanceChart').getContext('2d');
            const performanceChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [
                        {
                            label: 'Idea Views',
                            data: [0, 0, 0, 0, 0, 0, 0, 5, 12, 24, 38, 45],
                            borderColor: '#FFE535',
                            backgroundColor: 'rgba(255, 229, 53, 0.2)',
                            borderWidth: 2,
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Connections',
                            data: [0, 0, 0, 0, 0, 0, 0, 1, 3, 5, 6, 7],
                            borderColor: '#17a2b8',
                            backgroundColor: 'rgba(23, 162, 184, 0.2)',
                            borderWidth: 2,
                            tension: 0.4,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                boxWidth: 10,
                                font: {
                                    size: 10
                                }
                            }
                        },
                        tooltip: {
                            enabled: true,
                            mode: 'index',
                            intersect: false,
                            bodyFont: {
                                size: 10
                            },
                            titleFont: {
                                size: 10
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                font: {
                                    size: 10
                                }
                            }
                        },
                        x: {
                            ticks: {
                                font: {
                                    size: 10
                                }
                            }
                        }
                    }
                }
            });
            
            // Handle chart period buttons
            const chartButtons = document.querySelectorAll('.chart-btn');
            chartButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from all buttons
                    chartButtons.forEach(btn => btn.classList.remove('active'));
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    // In a real application, you would fetch data for the selected period
                    // and update the chart. This is a simplified example.
                    const period = this.getAttribute('data-period');
                    
                    // Simulate data changes based on selected period
                    if (period === 'week') {
                        performanceChart.data.labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                        performanceChart.data.datasets[0].data = [5, 8, 7, 12, 6, 10, 8];
                        performanceChart.data.datasets[1].data = [1, 0, 2, 1, 0, 2, 1];
                    } else if (period === 'month') {
                        performanceChart.data.labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
                        performanceChart.data.datasets[0].data = [20, 28, 32, 45];
                        performanceChart.data.datasets[1].data = [2, 3, 1, 4];
                    } else if (period === 'year') {
                        performanceChart.data.labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                        performanceChart.data.datasets[0].data = [0, 0, 0, 0, 0, 0, 0, 5, 12, 24, 38, 45];
                        performanceChart.data.datasets[1].data = [0, 0, 0, 0, 0, 0, 0, 1, 3, 5, 6, 7];
                    }
                    
                    performanceChart.update();
                });
            });
            
            // Add a match score bar styles
            const scoreStyles = document.createElement('style');
            scoreStyles.innerHTML = `
                .match-score {
                    margin-top: 15px;
                }
                .score-label {
                    font-size: 0.9rem;
                    color: #666;
                    margin-bottom: 5px;
                }
                .score-value {
                    font-weight: bold;
                    color: #333;
                    margin-bottom: 5px;
                }
                .score-bar {
                    height: 6px;
                    background-color: #e9ecef;
                    border-radius: 3px;
                    overflow: hidden;
                }
                .score-fill {
                    height: 100%;
                    background-color: #28a745;
                    border-radius: 3px;
                }
            `;
            document.head.appendChild(scoreStyles);
        });
    </script>
</body>
</html> 