<?php
// Include database connection and functions
require_once('../../includes/db_connect.php');
require_once('../../includes/functions.php');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'govt') {
    header("Location: ../login.php");
    exit();
}

// Get user data
$userData = get_user_data($conn, $_SESSION['user_id']);

// Mock data for dashboard - in a real application, this would come from the database
$entrepreneurIdeas = [
    [
        'id' => 1,
        'title' => 'Smart City Waste Management',
        'entrepreneur' => 'John Smith',
        'company' => 'EcoInnovate',
        'submission_date' => '2023-09-15',
        'status' => 'Under Review',
        'match_score' => 85
    ],
    [
        'id' => 2,
        'title' => 'Renewable Energy Grid Integration',
        'entrepreneur' => 'Sarah Johnson',
        'company' => 'GreenPower Solutions',
        'submission_date' => '2023-10-05',
        'status' => 'Pending Review',
        'match_score' => 92
    ],
    [
        'id' => 3,
        'title' => 'AI-Driven Public Transport Optimization',
        'entrepreneur' => 'Michael Chen',
        'company' => 'Smart Transit',
        'submission_date' => '2023-11-20',
        'status' => 'Under Review',
        'match_score' => 78
    ],
    [
        'id' => 4,
        'title' => 'Digital Healthcare Platform for Rural Areas',
        'entrepreneur' => 'Priya Patel',
        'company' => 'HealthTech Solutions',
        'submission_date' => '2023-11-25',
        'status' => 'New Submission',
        'match_score' => 88
    ]
];

$governmentInitiatives = [
    [
        'id' => 1,
        'title' => 'Smart City Development Program',
        'department' => 'Urban Development',
        'posted_date' => '2023-10-10',
        'status' => 'Active',
        'applications' => 12
    ],
    [
        'id' => 2,
        'title' => 'Clean Energy Innovation Challenge',
        'department' => 'Energy',
        'posted_date' => '2023-11-05',
        'status' => 'Active',
        'applications' => 8
    ],
    [
        'id' => 3,
        'title' => 'Digital Governance Transformation',
        'department' => 'IT & Communication',
        'posted_date' => '2023-11-12',
        'status' => 'Draft',
        'applications' => 0
    ]
];

$activeConnections = [
    [
        'id' => 1,
        'entrepreneur' => 'John Smith',
        'company' => 'EcoInnovate',
        'project' => 'Smart City Waste Management',
        'start_date' => '2023-10-01',
        'status' => 'In Progress',
        'last_update' => '2 days ago'
    ],
    [
        'id' => 2,
        'entrepreneur' => 'Sarah Johnson',
        'company' => 'GreenPower Solutions',
        'project' => 'Renewable Energy Grid Integration',
        'start_date' => '2023-10-20',
        'status' => 'In Progress',
        'last_update' => '5 days ago'
    ]
];

$recentActivities = [
    [
        'type' => 'idea',
        'title' => 'New idea submission received: "Digital Healthcare Platform for Rural Areas"',
        'time' => '1 day ago',
        'icon' => 'fa-lightbulb'
    ],
    [
        'type' => 'update',
        'title' => 'Updated status on "Smart City Waste Management" project',
        'time' => '3 days ago',
        'icon' => 'fa-sync'
    ],
    [
        'type' => 'connection',
        'title' => 'New connection established with GreenPower Solutions',
        'time' => '1 week ago',
        'icon' => 'fa-handshake'
    ],
    [
        'type' => 'initiative',
        'title' => 'Posted new initiative: "Clean Energy Innovation Challenge"',
        'time' => '2 weeks ago',
        'icon' => 'fa-bullhorn'
    ]
];

// Calculate statistics
$totalIdeas = count($entrepreneurIdeas);
$pendingReviews = 0;
$activeInitiatives = 0;
$totalConnections = count($activeConnections);

foreach ($entrepreneurIdeas as $idea) {
    if ($idea['status'] === 'Pending Review' || $idea['status'] === 'Under Review' || $idea['status'] === 'New Submission') {
        $pendingReviews++;
    }
}

foreach ($governmentInitiatives as $initiative) {
    if ($initiative['status'] === 'Active') {
        $activeInitiatives++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ColabX - Government Dashboard</title>
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
                <li><a href="govt_dashboard.php" class="link">Dashboard</a></li>
            </ul>
            <div class="user-actions">
                <div class="notification-badge" data-count="4">
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
            <h2>Government Organization Dashboard</h2>
            <p>Review entrepreneur ideas, manage initiatives, and foster innovation through public-private partnerships.</p>
            
            <div class="action-buttons">
                <button class="action-btn primary-btn" id="createInitiative"><i class="fas fa-plus-circle"></i> Create New Initiative</button>
                <button class="action-btn secondary-btn" id="reviewIdeas"><i class="fas fa-clipboard-check"></i> Review Pending Ideas</button>
                <button class="action-btn secondary-btn"><i class="fas fa-chart-pie"></i> View Department Analytics</button>
                <button class="action-btn secondary-btn"><i class="fas fa-comments"></i> Message Center</button>
            </div>
            
            <div class="stats-container">
                <div class="stat-card">
                    <div class="icon"><i class="fas fa-lightbulb"></i></div>
                    <h3>Entrepreneur Ideas</h3>
                    <div class="stat-number"><?php echo $totalIdeas; ?></div>
                    <p>Total submissions</p>
                </div>
                
                <div class="stat-card">
                    <div class="icon"><i class="fas fa-clipboard-check"></i></div>
                    <h3>Pending Reviews</h3>
                    <div class="stat-number"><?php echo $pendingReviews; ?></div>
                    <p>Awaiting assessment</p>
                </div>
                
                <div class="stat-card">
                    <div class="icon"><i class="fas fa-bullhorn"></i></div>
                    <h3>Active Initiatives</h3>
                    <div class="stat-number"><?php echo $activeInitiatives; ?></div>
                    <p>Open for applications</p>
                </div>
                
                <div class="stat-card">
                    <div class="icon"><i class="fas fa-handshake"></i></div>
                    <h3>Connections</h3>
                    <div class="stat-number"><?php echo $totalConnections; ?></div>
                    <p>With entrepreneurs</p>
                </div>
            </div>
            
            <!-- Chart - Idea Distribution by Sector -->
            <div class="chart-container">
                <div class="chart-header">
                    <h3 class="chart-title">Idea Distribution</h3>
                    <div class="chart-actions">
                        <button class="chart-btn active" data-chart="sector">By Sector</button>
                        <button class="chart-btn" data-chart="status">By Status</button>
                    </div>
                </div>
                <div style="height: 250px; max-height: 250px; position: relative;">
                    <canvas id="distributionChart"></canvas>
                </div>
            </div>
            
            <!-- Pending Ideas Section -->
            <div class="dashboard-section" id="pendingIdeasSection">
                <h3>Pending Idea Reviews</h3>
                <table class="dashboard-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Entrepreneur</th>
                            <th>Company</th>
                            <th>Submission Date</th>
                            <th>Status</th>
                            <th>Match Score</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entrepreneurIdeas as $idea): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($idea['title']); ?></td>
                            <td><?php echo htmlspecialchars($idea['entrepreneur']); ?></td>
                            <td><?php echo htmlspecialchars($idea['company']); ?></td>
                            <td><?php echo htmlspecialchars($idea['submission_date']); ?></td>
                            <td>
                                <?php 
                                $statusClass = '';
                                switch($idea['status']) {
                                    case 'Approved':
                                        $statusClass = 'status-approved';
                                        break;
                                    case 'Under Review':
                                    case 'Pending Review':
                                        $statusClass = 'status-pending';
                                        break;
                                    case 'New Submission':
                                        $statusClass = 'status-pending';
                                        break;
                                    default:
                                        $statusClass = '';
                                }
                                ?>
                                <span class="status-pill <?php echo $statusClass; ?>"><?php echo htmlspecialchars($idea['status']); ?></span>
                            </td>
                            <td>
                                <div class="match-score-inline">
                                    <span class="match-value"><?php echo $idea['match_score']; ?>%</span>
                                    <div class="match-bar">
                                        <div class="match-fill" style="width: <?php echo $idea['match_score']; ?>%;"></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="tooltip">
                                    <i class="fas fa-eye"></i>
                                    <span class="tooltip-text">View Details</span>
                                </div>
                                <div class="tooltip">
                                    <i class="fas fa-check-circle"></i>
                                    <span class="tooltip-text">Approve</span>
                                </div>
                                <div class="tooltip">
                                    <i class="fas fa-comments"></i>
                                    <span class="tooltip-text">Discuss</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Your Initiatives Section -->
            <div class="dashboard-section" id="initiativesSection" style="display: none;">
                <h3>Your Department's Initiatives</h3>
                <table class="dashboard-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Department</th>
                            <th>Posted Date</th>
                            <th>Status</th>
                            <th>Applications</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($governmentInitiatives as $initiative): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($initiative['title']); ?></td>
                            <td><?php echo htmlspecialchars($initiative['department']); ?></td>
                            <td><?php echo htmlspecialchars($initiative['posted_date']); ?></td>
                            <td>
                                <?php 
                                $statusClass = '';
                                switch($initiative['status']) {
                                    case 'Active':
                                        $statusClass = 'status-approved';
                                        break;
                                    case 'Draft':
                                        $statusClass = 'status-pending';
                                        break;
                                    default:
                                        $statusClass = '';
                                }
                                ?>
                                <span class="status-pill <?php echo $statusClass; ?>"><?php echo htmlspecialchars($initiative['status']); ?></span>
                            </td>
                            <td><?php echo $initiative['applications']; ?></td>
                            <td>
                                <div class="tooltip">
                                    <i class="fas fa-eye"></i>
                                    <span class="tooltip-text">View Details</span>
                                </div>
                                <div class="tooltip">
                                    <i class="fas fa-edit"></i>
                                    <span class="tooltip-text">Edit</span>
                                </div>
                                <div class="tooltip">
                                    <i class="fas fa-bullhorn"></i>
                                    <span class="tooltip-text">Publish</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div id="initiativeForm" style="display: none; margin-top: 30px;">
                    <h3>Create New Initiative</h3>
                    <form class="dashboard-form">
                        <div class="form-group">
                            <label for="initiativeTitle">Initiative Title</label>
                            <input type="text" id="initiativeTitle" name="initiativeTitle" placeholder="Enter initiative title" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="initiativeDepartment">Department</label>
                            <select id="initiativeDepartment" name="initiativeDepartment" required>
                                <option value="">Select Department</option>
                                <option value="Urban Development">Urban Development</option>
                                <option value="Energy">Energy</option>
                                <option value="IT & Communication">IT & Communication</option>
                                <option value="Healthcare">Healthcare</option>
                                <option value="Agriculture">Agriculture</option>
                                <option value="Education">Education</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="initiativeDescription">Description</label>
                            <textarea id="initiativeDescription" name="initiativeDescription" placeholder="Describe the initiative..." rows="4" required></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group half">
                                <label for="startDate">Start Date</label>
                                <input type="date" id="startDate" name="startDate" required>
                            </div>
                            
                            <div class="form-group half">
                                <label for="endDate">End Date</label>
                                <input type="date" id="endDate" name="endDate" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="initiativeObjectives">Objectives</label>
                            <textarea id="initiativeObjectives" name="initiativeObjectives" placeholder="List the initiative objectives..." rows="3" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="initiativeBudget">Budget (USD)</label>
                            <input type="number" id="initiativeBudget" name="initiativeBudget" placeholder="Enter budget amount" min="0" step="1000">
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="action-btn primary-btn">Create Initiative</button>
                            <button type="button" class="action-btn secondary-btn" id="cancelInitiative">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Active Connections -->
            <div class="dashboard-section">
                <h3>Active Connections</h3>
                <div class="stats-container">
                    <?php foreach ($activeConnections as $connection): ?>
                    <div class="stat-card hover-card">
                        <h3><?php echo htmlspecialchars($connection['project']); ?></h3>
                        <p><strong>Entrepreneur:</strong> <?php echo htmlspecialchars($connection['entrepreneur']); ?></p>
                        <p><strong>Company:</strong> <?php echo htmlspecialchars($connection['company']); ?></p>
                        <p><strong>Started:</strong> <?php echo htmlspecialchars($connection['start_date']); ?></p>
                        <p><strong>Status:</strong> <?php echo htmlspecialchars($connection['status']); ?></p>
                        <p><strong>Last Update:</strong> <?php echo htmlspecialchars($connection['last_update']); ?></p>
                        <div class="card-actions">
                            <button class="action-btn secondary-btn"><i class="fas fa-comments"></i> Message</button>
                            <button class="action-btn secondary-btn"><i class="fas fa-file-alt"></i> View Report</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <!-- Add connection card -->
                    <div class="stat-card add-card hover-card">
                        <div class="add-icon">
                            <i class="fas fa-plus"></i>
                        </div>
                        <h3>Create New Connection</h3>
                        <p>Connect with entrepreneurs to collaborate on innovative projects</p>
                        <button class="action-btn primary-btn" style="width: 100%; margin-top: 15px;"><i class="fas fa-link"></i> Connect</button>
                    </div>
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
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="profile-name"><?php echo htmlspecialchars($userData['full_name']); ?></div>
                    <div class="profile-role">Government Official</div>
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
                            <strong>Government ID</strong>
                            <span><?php echo htmlspecialchars($userData['govt_id']); ?></span>
                        </div>
                        <div class="info-item">
                            <strong>Department</strong>
                            <span>Urban Development</span>
                        </div>
                        <div class="info-item">
                            <strong>Account Type</strong>
                            <span>Government Organization</span>
                        </div>
                        <div class="info-item">
                            <strong>Member Since</strong>
                            <span>October 2023</span>
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
        <div class="toast toast-info">
            <div class="toast-icon"><i class="fas fa-info-circle"></i></div>
            <div class="toast-content">
                <div class="toast-title">New Idea Submission</div>
                <div class="toast-message">You have a new idea to review: "Digital Healthcare Platform for Rural Areas"</div>
            </div>
            <div class="toast-close"><i class="fas fa-times"></i></div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Language dropdown functionality
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
            
            // Toggle sections
            const reviewIdeasBtn = document.getElementById('reviewIdeas');
            const createInitiativeBtn = document.getElementById('createInitiative');
            const pendingIdeasSection = document.getElementById('pendingIdeasSection');
            const initiativesSection = document.getElementById('initiativesSection');
            const initiativeForm = document.getElementById('initiativeForm');
            const cancelInitiativeBtn = document.getElementById('cancelInitiative');
            
            reviewIdeasBtn.addEventListener('click', function() {
                pendingIdeasSection.style.display = 'block';
                initiativesSection.style.display = 'none';
                initiativeForm.style.display = 'none';
                pendingIdeasSection.scrollIntoView({ behavior: 'smooth' });
            });
            
            createInitiativeBtn.addEventListener('click', function() {
                pendingIdeasSection.style.display = 'none';
                initiativesSection.style.display = 'block';
                initiativeForm.style.display = 'block';
                initiativesSection.scrollIntoView({ behavior: 'smooth' });
            });
            
            if (cancelInitiativeBtn) {
                cancelInitiativeBtn.addEventListener('click', function() {
                    initiativeForm.style.display = 'none';
                });
            }
            
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
            
            // Initialize distribution chart
            const ctx = document.getElementById('distributionChart').getContext('2d');
            
            // Initial chart data - by sector
            const sectorData = {
                labels: ['Urban Tech', 'Energy', 'Healthcare', 'Education', 'Agriculture', 'Transportation'],
                datasets: [{
                    data: [8, 6, 4, 3, 2, 1],
                    backgroundColor: [
                        '#FFE535',
                        '#17a2b8',
                        '#28a745',
                        '#6f42c1',
                        '#fd7e14',
                        '#20c997'
                    ],
                    borderWidth: 0
                }]
            };
            
            // Status data for toggle
            const statusData = {
                labels: ['Under Review', 'Pending', 'Approved', 'Rejected', 'Implemented'],
                datasets: [{
                    data: [12, 8, 6, 3, 2],
                    backgroundColor: [
                        '#17a2b8',
                        '#ffc107',
                        '#28a745',
                        '#dc3545',
                        '#6f42c1'
                    ],
                    borderWidth: 0
                }]
            };
            
            const distributionChart = new Chart(ctx, {
                type: 'doughnut',
                data: sectorData,
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 10,
                                font: {
                                    size: 10
                                }
                            }
                        },
                        tooltip: {
                            bodyFont: {
                                size: 10
                            },
                            titleFont: {
                                size: 10
                            }
                        }
                    },
                    cutout: '60%',
                    animation: {
                        animateScale: true,
                        animateRotate: true
                    }
                }
            });
            
            // Handle chart toggle buttons
            const chartButtons = document.querySelectorAll('.chart-btn');
            chartButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from all buttons
                    chartButtons.forEach(btn => btn.classList.remove('active'));
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    const chartType = this.getAttribute('data-chart');
                    
                    if (chartType === 'sector') {
                        distributionChart.data = sectorData;
                    } else if (chartType === 'status') {
                        distributionChart.data = statusData;
                    }
                    
                    distributionChart.update();
                });
            });
            
            // Add form styles
            const formStyles = document.createElement('style');
            formStyles.innerHTML = `
                .dashboard-form {
                    background-color: white;
                    padding: 20px;
                    border-radius: 10px;
                    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
                }
                
                .form-group {
                    margin-bottom: 15px;
                }
                
                .form-row {
                    display: flex;
                    gap: 15px;
                }
                
                .form-group.half {
                    flex: 1;
                }
                
                .form-group label {
                    display: block;
                    margin-bottom: 5px;
                    font-weight: 500;
                    color: #333;
                }
                
                .form-group input,
                .form-group select,
                .form-group textarea {
                    width: 100%;
                    padding: 10px;
                    border: 1px solid #ddd;
                    border-radius: 5px;
                    font-size: 0.9rem;
                }
                
                .form-actions {
                    display: flex;
                    gap: 10px;
                    margin-top: 20px;
                }
                
                .match-score-inline {
                    display: flex;
                    flex-direction: column;
                    gap: 5px;
                }
                
                .match-value {
                    font-weight: bold;
                }
                
                .match-bar {
                    height: 5px;
                    background-color: #e9ecef;
                    border-radius: 2.5px;
                    width: 60px;
                    overflow: hidden;
                }
                
                .match-fill {
                    height: 100%;
                    background-color: #28a745;
                    border-radius: 2.5px;
                }
                
                .card-actions {
                    display: flex;
                    gap: 10px;
                    margin-top: 15px;
                }
                
                .add-card {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    text-align: center;
                    border: 2px dashed #ddd;
                    background-color: #f9f9f9;
                }
                
                .add-icon {
                    font-size: 2rem;
                    color: #aaa;
                    margin-bottom: 15px;
                }
            `;
            document.head.appendChild(formStyles);
        });
    </script>
</body>
</html> 