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

// Fetch government initiatives from database
$governmentInitiatives = [];
$initiativeQuery = "SELECT * FROM initiatives WHERE user_id = ? ORDER BY created_at DESC";
$initiativeStmt = $conn->prepare($initiativeQuery);
$initiativeStmt->bind_param("i", $_SESSION['user_id']);
$initiativeStmt->execute();
$initiativeResult = $initiativeStmt->get_result();

if ($initiativeResult->num_rows > 0) {
    while ($row = $initiativeResult->fetch_assoc()) {
        $governmentInitiatives[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'department' => $row['department'],
            'posted_date' => date('Y-m-d', strtotime($row['created_at'])),
            'status' => $row['status'],
            'applications' => 0 // You might want to count actual applications in the future
        ];
    }
}

// Mock data for dashboard - in a real application, this would come from the database
$entrepreneurIdeas = [
    [
        'id' => 1001, // Using 1000+ IDs for mock data to avoid conflicts
        'title' => 'Smart City Waste Management',
        'entrepreneur' => 'John Smith',
        'company' => 'EcoInnovate',
        'submission_date' => '2023-09-15',
        'status' => 'Under Review',
        'match_score' => 85
    ],
    [
        'id' => 1002,
        'title' => 'Renewable Energy Grid Integration',
        'entrepreneur' => 'Sarah Johnson',
        'company' => 'GreenPower Solutions',
        'submission_date' => '2023-10-05',
        'status' => 'Pending Review',
        'match_score' => 92
    ],
    [
        'id' => 1003,
        'title' => 'AI-Driven Public Transport Optimization',
        'entrepreneur' => 'Michael Chen',
        'company' => 'Smart Transit',
        'submission_date' => '2023-11-20',
        'status' => 'Under Review',
        'match_score' => 78
    ],
    [
        'id' => 1004,
        'title' => 'Digital Healthcare Platform for Rural Areas',
        'entrepreneur' => 'Priya Patel',
        'company' => 'HealthTech Solutions',
        'submission_date' => '2023-11-25',
        'status' => 'New Submission',
        'match_score' => 88
    ]
];

// Fetch real entrepreneur ideas from the database
$realIdeas = [];
$ideasQuery = "SELECT i.*, u.full_name, u.company_name 
               FROM ideas i 
               JOIN users u ON i.user_id = u.id 
               WHERE i.status IN ('pending', 'under_review') 
               ORDER BY i.created_at DESC";
$ideasResult = $conn->query($ideasQuery);

if ($ideasResult && $ideasResult->num_rows > 0) {
    while ($row = $ideasResult->fetch_assoc()) {
        // Calculate a random match score between 65-95
        $matchScore = rand(65, 95);
        
        $realIdeas[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'entrepreneur' => $row['full_name'],
            'company' => $row['company_name'] ?: 'Individual Entrepreneur',
            'submission_date' => date('Y-m-d', strtotime($row['created_at'])),
            'status' => ucwords(str_replace('_', ' ', $row['status'])),
            'match_score' => $matchScore,
            'description' => $row['description'],
            'sector' => $row['sector']
        ];
    }
}

// Combine mock and real ideas
$combinedIdeas = array_merge($entrepreneurIdeas, $realIdeas);

// Sort by submission date (newest first)
usort($combinedIdeas, function($a, $b) {
    return strtotime($b['submission_date']) - strtotime($a['submission_date']);
});

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

// Recalculate statistics with actual ideas
$totalIdeas = count($combinedIdeas);
$pendingReviews = 0;
$activeInitiatives = 0;
$totalConnections = count($activeConnections);

foreach ($combinedIdeas as $idea) {
    if ($idea['status'] === 'Pending Review' || $idea['status'] === 'Under Review' || $idea['status'] === 'New Submission' || $idea['status'] === 'Pending') {
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
    <style>
        /* Modal styles for idea details */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background-color: white;
            border-radius: 8px;
            width: 90%;
            max-width: 700px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.3);
            animation: fadeInUp 0.3s ease;
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
        }
        
        .modal-header h3 {
            margin: 0;
            font-size: 1.3rem;
            color: #333;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #777;
            transition: color 0.3s;
        }
        
        .modal-close:hover {
            color: #333;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .idea-detail-section {
            line-height: 1.6;
        }
        
        .idea-detail-section h4 {
            color: #333;
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 1.2rem;
        }
        
        .idea-detail-section p {
            margin-bottom: 10px;
            color: #555;
        }
        
        .idea-detail-section strong {
            font-weight: 600;
            color: #333;
        }
        
        /* Tooltip improvements */
        .tooltip {
            position: relative;
            display: inline-block;
            margin: 0 5px;
        }
        
        .tooltip i {
            cursor: pointer;
            color: #666;
            font-size: 1rem;
            padding: 5px;
            transition: color 0.3s;
        }
        
        .tooltip:hover i {
            color: #333;
        }
        
        /* Button states */
        .disabled {
            opacity: 0.6;
            pointer-events: none;
        }
        
        /* Profile section improvements */
        .profile-info {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .info-item {
            padding: 10px;
            background-color: #f9f9f9;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .info-item:hover {
            background-color: #f0f0f0;
        }
        
        .info-label {
            font-size: 0.8rem;
            color: #6c757d;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 1rem;
            font-weight: 500;
            color: #333;
        }
        
        .profile-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            border: 3px solid #FFE535;
            overflow: hidden;
        }
        
        .profile-avatar i {
            font-size: 3rem;
            color: #aaa;
        }
        
        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        /* Form styles for modals */
        .form-section {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .form-section h4 {
            margin-bottom: 15px;
            font-size: 1.1rem;
            color: #333;
        }
        
        .checkbox-group, .radio-group {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .checkbox-group input[type="checkbox"], 
        .radio-group input[type="radio"] {
            margin-right: 10px;
        }
        
        .help-text {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .readonly-field {
            background-color: #f5f5f5;
            cursor: not-allowed;
        }
        
        .radio-options-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
            width: 100%;
        }
        
        .radio-option {
            padding: 10px;
            border-radius: 5px;
            background-color: #f9f9f9;
            transition: all 0.3s ease;
        }
        
        .radio-option:hover {
            background-color: #f0f0f0;
        }
        
        .radio-header {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }
        
        .radio-header input {
            margin-right: 10px;
        }
        
        .radio-header label {
            font-weight: 500;
        }
        
        .current-image {
            margin-top: 10px;
        }
        
        /* Add these styles inside your existing style block */
        .chart-wrapper {
            height: 300px;
            max-height: 300px;
            position: relative;
            padding: 1rem 0;
        }
        
        .chart-legend {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 15px;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.85rem;
        }
        
        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 3px;
        }
        
        .stat-change {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 10px;
            font-size: 0.9rem;
        }
        
        .stat-change.positive {
            color: #28a745;
        }
        
        .stat-change.negative {
            color: #dc3545;
        }
        
        .chart-tabs {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .chart-tab {
            padding: 10px 15px;
            background-color: #f8f9fa;
            border-radius: 10px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .chart-tab.active {
            background-color: #FFE535;
            color: #333;
            box-shadow: 0 3px 8px rgba(255, 229, 53, 0.3);
        }
        
        .chart-tab:hover:not(.active) {
            background-color: #f0f0f0;
        }
        
        .stats-row {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        
        .stat-item {
            flex: 1;
            min-width: 150px;
            padding: 10px 15px;
            border-radius: 8px;
        }
    </style>
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
                <div class="notification-badge" data-count="4" id="notificationIcon">
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
        </div>
        
        <div class="dashboard-content">
            <h2>Government Organization Dashboard</h2>
            <p>Review entrepreneur ideas, manage initiatives, and foster innovation through public-private partnerships.</p>
            
            <div class="action-buttons">
                <button class="action-btn primary-btn" id="createInitiative"><i class="fas fa-plus-circle"></i> Create New Initiative</button>
                <button class="action-btn secondary-btn" id="viewInitiatives"><i class="fas fa-list"></i> View My Initiatives</button>
                <button class="action-btn secondary-btn" id="reviewIdeas"><i class="fas fa-clipboard-check"></i> Review Pending Ideas</button>
                <button class="action-btn secondary-btn" id="viewInterests"><i class="fas fa-handshake"></i> View Interest Expressions</button>
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
                
                <div class="chart-tabs">
                    <div class="chart-tab active"><i class="fas fa-chart-line"></i> Growth</div>
                    <div class="chart-tab"><i class="fas fa-chart-bar"></i> Engagement</div>
                    <div class="chart-tab"><i class="fas fa-chart-pie"></i> Distribution</div>
                </div>
                
                <div class="chart-stats">
                    <div class="stats-row" style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                        <div class="stat-item">
                            <div style="font-size: 0.85rem; color: #6c757d;">Total Reviews</div>
                            <div style="font-size: 1.5rem; font-weight: 600; color: #333;"><?php echo $totalIdeas; ?></div>
                            <div class="stat-change positive">
                                <i class="fas fa-arrow-up"></i> 18% from last period
                            </div>
                        </div>
                        <div class="stat-item">
                            <div style="font-size: 0.85rem; color: #6c757d;">Initiatives</div>
                            <div style="font-size: 1.5rem; font-weight: 600; color: #333;"><?php echo $activeInitiatives; ?></div>
                            <div class="stat-change positive">
                                <i class="fas fa-arrow-up"></i> 15% from last period
                            </div>
                        </div>
                        <div class="stat-item">
                            <div style="font-size: 0.85rem; color: #6c757d;">Approval Rate</div>
                            <div style="font-size: 1.5rem; font-weight: 600; color: #333;">72.5%</div>
                            <div class="stat-change negative">
                                <i class="fas fa-arrow-down"></i> 4% from last period
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="chart-wrapper">
                    <canvas id="performanceChart"></canvas>
                </div>
                
                <div class="chart-legend">
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: rgba(255, 229, 53, 0.8);"></div>
                        <div>Ideas Reviewed</div>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: rgba(23, 162, 184, 0.8);"></div>
                        <div>Initiatives Created</div>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: rgba(40, 167, 69, 0.8);"></div>
                        <div>Collaborations</div>
                    </div>
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
                        <?php foreach ($combinedIdeas as $idea): ?>
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
                                    case 'Pending':
                                        $statusClass = 'status-pending';
                                        break;
                                    case 'New Submission':
                                        $statusClass = 'status-pending';
                                        break;
                                    case 'Rejected':
                                        $statusClass = 'status-rejected';
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
                                    <?php if ($idea['id'] < 1000): ?>
                                        <!-- Real idea -->
                                        <a href="../view_idea.php?id=<?php echo $idea['id']; ?>" target="_blank"><i class="fas fa-eye"></i></a>
                                    <?php else: ?>
                                        <!-- Mock idea -->
                                        <a href="javascript:void(0);" onclick="showIdeaDetails(<?php echo $idea['id']; ?>, '<?php echo addslashes($idea['title']); ?>')"><i class="fas fa-eye"></i></a>
                                    <?php endif; ?>
                                    <span class="tooltip-text">View Details</span>
                                </div>
                                
                                <?php if ($idea['status'] !== 'Approved' && $idea['status'] !== 'Rejected'): ?>
                                <div class="tooltip">
                                    <a href="javascript:void(0);" class="approve-idea" data-id="<?php echo $idea['id']; ?>" data-title="<?php echo htmlspecialchars($idea['title']); ?>">
                                        <i class="fas fa-check-circle"></i>
                                    </a>
                                    <span class="tooltip-text">Approve</span>
                                </div>
                                
                                <div class="tooltip">
                                    <a href="javascript:void(0);" class="reject-idea" data-id="<?php echo $idea['id']; ?>" data-title="<?php echo htmlspecialchars($idea['title']); ?>">
                                        <i class="fas fa-times-circle"></i>
                                    </a>
                                    <span class="tooltip-text">Reject</span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($idea['id'] < 1000): ?>
                                <div class="tooltip">
                                    <a href="mailto:<?php echo isset($idea['email']) ? $idea['email'] : ''; ?>"><i class="fas fa-comments"></i></a>
                                    <span class="tooltip-text">Contact Entrepreneur</span>
                                </div>
                                <?php else: ?>
                                <div class="tooltip">
                                    <i class="fas fa-comments"></i>
                                    <span class="tooltip-text">Discuss</span>
                                </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Idea Details Modal -->
            <div class="modal" id="ideaDetailsModal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 id="modalIdeaTitle">Idea Details</h3>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body" id="modalIdeaContent">
                        <!-- Content will be dynamically inserted here -->
                    </div>
                </div>
            </div>
            
            <!-- Your Initiatives Section -->
            <div class="dashboard-section" id="initiativesSection" style="display: none;">
                <h3>Your Department's Initiatives</h3>
                <?php if (empty($governmentInitiatives)): ?>
                <div class="empty-state">
                    <i class="fas fa-bullhorn empty-icon"></i>
                    <h4>No Initiatives Yet</h4>
                    <p>You haven't created any initiatives yet. Click the "Create New Initiative" button to get started.</p>
                </div>
                <?php else: ?>
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
                                    case 'active':
                                        $statusClass = 'status-approved';
                                        break;
                                    case 'draft':
                                        $statusClass = 'status-pending';
                                        break;
                                    case 'closed':
                                        $statusClass = 'status-rejected';
                                        break;
                                    default:
                                        $statusClass = '';
                                }
                                ?>
                                <span class="status-pill <?php echo $statusClass; ?>"><?php echo ucfirst(htmlspecialchars($initiative['status'])); ?></span>
                            </td>
                            <td><?php echo $initiative['applications']; ?></td>
                            <td>
                                <div class="tooltip">
                                    <a href="../colab.php?initiative_id=<?php echo $initiative['id']; ?>"><i class="fas fa-eye"></i></a>
                                    <span class="tooltip-text">View on Colab Page</span>
                                </div>
                                <div class="tooltip">
                                    <a href="javascript:void(0);" class="edit-initiative" data-id="<?php echo $initiative['id']; ?>"><i class="fas fa-edit"></i></a>
                                    <span class="tooltip-text">Edit</span>
                                </div>
                                <div class="tooltip">
                                    <a href="javascript:void(0);" class="delete-initiative" data-id="<?php echo $initiative['id']; ?>"><i class="fas fa-trash"></i></a>
                                    <span class="tooltip-text">Delete</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
                
                <div id="initiativeForm" style="display: none; margin-top: 30px;">
                    <h3>Create New Initiative</h3>
                    
                    <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success">
                        <?php 
                        echo $_SESSION['success_message']; 
                        unset($_SESSION['success_message']); 
                        ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger">
                        <?php 
                        echo $_SESSION['error_message']; 
                        unset($_SESSION['error_message']); 
                        ?>
                    </div>
                    <?php endif; ?>
                    
                    <form class="dashboard-form" action="../../actions/create_initiative.php" method="POST">
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
            
            <!-- Interest Expressions Section -->
            <div class="dashboard-section" id="interestsSection" style="display: none;">
                <h3>Interest Expressions from Entrepreneurs</h3>
                
                <?php
                // Fetch interest expressions for initiatives created by this user
                $interestQuery = "
                    SELECT i.*, 
                           init.title as initiative_title, 
                           u.full_name as entrepreneur_name,
                           u.company_name as company_name,
                           u.email as entrepreneur_email,
                           id.title as idea_title
                    FROM initiative_interests i
                    JOIN initiatives init ON i.initiative_id = init.id
                    JOIN users u ON i.user_id = u.id
                    LEFT JOIN ideas id ON i.idea_id = id.id
                    WHERE init.user_id = ?
                    ORDER BY i.created_at DESC";
                
                $interestStmt = $conn->prepare($interestQuery);
                $interestStmt->bind_param("i", $_SESSION['user_id']);
                $interestStmt->execute();
                $interestResult = $interestStmt->get_result();
                
                if ($interestResult->num_rows > 0):
                ?>
                <table class="dashboard-table">
                    <thead>
                        <tr>
                            <th>Initiative</th>
                            <th>Entrepreneur</th>
                            <th>Company</th>
                            <th>Proposal</th>
                            <th>Submitted</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($interest = $interestResult->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($interest['initiative_title']); ?></td>
                            <td><?php echo htmlspecialchars($interest['entrepreneur_name']); ?></td>
                            <td><?php echo htmlspecialchars($interest['company_name']); ?></td>
                            <td>
                                <div class="proposal-preview">
                                    <?php echo substr(htmlspecialchars($interest['proposal']), 0, 100); ?>
                                    <?php if (strlen($interest['proposal']) > 100): ?>
                                        <span class="read-more" data-proposal="<?php echo htmlspecialchars($interest['proposal']); ?>">... Read More</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($interest['created_at'])); ?></td>
                            <td>
                                <?php 
                                $statusClass = '';
                                switch($interest['status']) {
                                    case 'approved':
                                        $statusClass = 'status-approved';
                                        break;
                                    case 'pending':
                                        $statusClass = 'status-pending';
                                        break;
                                    case 'rejected':
                                        $statusClass = 'status-rejected';
                                        break;
                                    default:
                                        $statusClass = '';
                                }
                                ?>
                                <span class="status-pill <?php echo $statusClass; ?>"><?php echo ucfirst(htmlspecialchars($interest['status'])); ?></span>
                            </td>
                            <td>
                                <?php if ($interest['status'] === 'pending'): ?>
                                <div class="tooltip">
                                    <a href="javascript:void(0);" class="approve-interest" data-id="<?php echo $interest['id']; ?>"><i class="fas fa-check"></i></a>
                                    <span class="tooltip-text">Approve</span>
                                </div>
                                <div class="tooltip">
                                    <a href="javascript:void(0);" class="reject-interest" data-id="<?php echo $interest['id']; ?>"><i class="fas fa-times"></i></a>
                                    <span class="tooltip-text">Reject</span>
                                </div>
                                <?php endif; ?>
                                <div class="tooltip">
                                    <a href="mailto:<?php echo $interest['entrepreneur_email']; ?>"><i class="fas fa-envelope"></i></a>
                                    <span class="tooltip-text">Contact</span>
                                </div>
                                <?php if (!empty($interest['idea_title'])): ?>
                                <div class="tooltip">
                                    <a href="../view_idea.php?id=<?php echo $interest['idea_id']; ?>"><i class="fas fa-lightbulb"></i></a>
                                    <span class="tooltip-text">View Idea: <?php echo htmlspecialchars($interest['idea_title']); ?></span>
                                </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-handshake empty-icon"></i>
                    <h4>No Interest Expressions Yet</h4>
                    <p>You haven't received any interest expressions from entrepreneurs for your initiatives yet.</p>
                </div>
                <?php endif; ?>
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
                        <?php if(!empty($userData['profile_pic'])): ?>
                            <img src="/ColabX/<?php echo htmlspecialchars($userData['profile_pic']); ?>" alt="Profile Picture">
                        <?php else: ?>
                        <i class="fas fa-user-tie"></i>
                        <?php endif; ?>
                    </div>
                    <div class="profile-name"><?php echo htmlspecialchars($userData['full_name']); ?></div>
                    <div class="profile-role">Government Official</div>
                </div>
                
                <div class="profile-details">
                    <h3>Profile Information</h3>
                    <div class="profile-info">
                        <div class="info-item">
                            <div class="info-label">Full Name</div>
                            <div class="info-value"><?php echo htmlspecialchars($userData['full_name']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Email</div>
                            <div class="info-value"><?php echo htmlspecialchars($userData['email']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Government ID</div>
                            <div class="info-value"><?php echo htmlspecialchars($userData['govt_id']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Department</div>
                            <div class="info-value"><?php echo !empty($userData['department']) ? htmlspecialchars($userData['department']) : 'Government Department'; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Bio</div>
                            <div class="info-value"><?php echo !empty($userData['bio']) ? htmlspecialchars($userData['bio']) : 'No bio provided'; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Account Type</div>
                            <div class="info-value">Government Organization</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Member Since</div>
                            <div class="info-value"><?php echo !empty($userData['created_at']) ? date('F Y', strtotime($userData['created_at'])) : 'November 2023'; ?></div>
                        </div>
                    </div>
                    
                    <div class="profile-actions">
                        <button class="action-btn secondary-btn" id="editProfileBtn">
                            <i class="fas fa-edit"></i> Edit Profile
                        </button>
                        <button class="action-btn secondary-btn" id="accountSettingsBtn">
                            <i class="fas fa-cog"></i> Account Settings
                        </button>
                    </div>
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

    <!-- Notification Modal -->
    <div id="notificationModal" class="notification-modal">
        <div class="notification-modal-content">
            <div class="notification-modal-header">
                <h3>Notifications</h3>
                <span class="close-notification-modal">&times;</span>
            </div>
            <div class="notification-modal-body">
                <div class="notification-item unread">
                    <div class="notification-icon">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <div class="notification-content">
                        <div class="notification-title">New idea submission received</div>
                        <div class="notification-message">Digital Healthcare Platform for Rural Areas by Priya Patel</div>
                        <div class="notification-time">2 hours ago</div>
                    </div>
                    <div class="notification-actions">
                        <button class="notification-btn mark-read" title="Mark as read"><i class="fas fa-check"></i></button>
                    </div>
                </div>
                
                <div class="notification-item unread">
                    <div class="notification-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <div class="notification-content">
                        <div class="notification-title">New interest expression</div>
                        <div class="notification-message">Sarah Johnson from GreenPower Solutions expressed interest in your Clean Energy initiative</div>
                        <div class="notification-time">8 hours ago</div>
                    </div>
                    <div class="notification-actions">
                        <button class="notification-btn mark-read" title="Mark as read"><i class="fas fa-check"></i></button>
                    </div>
                </div>
                
                <div class="notification-item">
                    <div class="notification-icon">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <div class="notification-content">
                        <div class="notification-title">Initiative published</div>
                        <div class="notification-message">Your "Smart City Development Program" is now live and visible to entrepreneurs</div>
                        <div class="notification-time">1 day ago</div>
                    </div>
                </div>
                
                <div class="notification-item">
                    <div class="notification-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="notification-content">
                        <div class="notification-title">New account created</div>
                        <div class="notification-message">Welcome to ColabX! Your government account has been activated.</div>
                        <div class="notification-time">3 days ago</div>
                    </div>
                </div>
            </div>
            <div class="notification-modal-footer">
                <button class="mark-all-read">Mark all as read</button>
            </div>
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
            
            // Notification modal functionality
            const notificationIcon = document.getElementById('notificationIcon');
            const notificationModal = document.getElementById('notificationModal');
            const closeNotificationBtn = document.querySelector('.close-notification-modal');
            const markReadBtns = document.querySelectorAll('.mark-read');
            const markAllReadBtn = document.querySelector('.mark-all-read');
            
            // Show modal when clicking notification icon
            notificationIcon.addEventListener('click', function() {
                notificationModal.classList.add('show');
                document.body.style.overflow = 'hidden'; // Prevent scrolling behind modal
            });
            
            // Hide modal when clicking close button
            closeNotificationBtn.addEventListener('click', function() {
                notificationModal.classList.remove('show');
                document.body.style.overflow = ''; // Restore scrolling
            });
            
            // Hide modal when clicking outside
            notificationModal.addEventListener('click', function(e) {
                if (e.target === notificationModal) {
                    notificationModal.classList.remove('show');
                    document.body.style.overflow = ''; // Restore scrolling
                }
            });
            
            // Handle mark as read for individual notifications
            markReadBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const notificationItem = this.closest('.notification-item');
                    notificationItem.classList.remove('unread');
                    this.parentNode.remove(); // Remove the actions buttons
                    
                    // Update notification count
                    updateNotificationCount();
                });
            });
            
            // Handle mark all as read
            markAllReadBtn.addEventListener('click', function() {
                const unreadItems = document.querySelectorAll('.notification-item.unread');
                unreadItems.forEach(item => {
                    item.classList.remove('unread');
                    const actionBtn = item.querySelector('.notification-actions');
                    if (actionBtn) actionBtn.remove();
                });
                
                // Update notification count
                updateNotificationCount();
            });
            
            // Function to update notification count
            function updateNotificationCount() {
                const unreadCount = document.querySelectorAll('.notification-item.unread').length;
                notificationIcon.setAttribute('data-count', unreadCount);
                
                // Hide the badge if no unread notifications
                if (unreadCount === 0) {
                    notificationIcon.style.setProperty('--badge-display', 'none');
                } else {
                    notificationIcon.style.setProperty('--badge-display', 'block');
                }
            }
            
            // Toggle sections
            const reviewIdeasBtn = document.getElementById('reviewIdeas');
            const createInitiativeBtn = document.getElementById('createInitiative');
            const viewInitiativesBtn = document.getElementById('viewInitiatives');
            const viewInterestsBtn = document.getElementById('viewInterests');
            const pendingIdeasSection = document.getElementById('pendingIdeasSection');
            const initiativesSection = document.getElementById('initiativesSection');
            const interestsSection = document.getElementById('interestsSection');
            const initiativeForm = document.getElementById('initiativeForm');
            const cancelInitiativeBtn = document.getElementById('cancelInitiative');
            
            reviewIdeasBtn.addEventListener('click', function() {
                pendingIdeasSection.style.display = 'block';
                initiativesSection.style.display = 'none';
                interestsSection.style.display = 'none';
                initiativeForm.style.display = 'none';
                pendingIdeasSection.scrollIntoView({ behavior: 'smooth' });
            });
            
            createInitiativeBtn.addEventListener('click', function() {
                pendingIdeasSection.style.display = 'none';
                initiativesSection.style.display = 'block';
                interestsSection.style.display = 'none';
                initiativeForm.style.display = 'block';
                initiativesSection.scrollIntoView({ behavior: 'smooth' });
            });
            
            viewInitiativesBtn.addEventListener('click', function() {
                pendingIdeasSection.style.display = 'none';
                initiativesSection.style.display = 'block';
                interestsSection.style.display = 'none';
                initiativeForm.style.display = 'none';
                initiativesSection.scrollIntoView({ behavior: 'smooth' });
            });
            
            viewInterestsBtn.addEventListener('click', function() {
                pendingIdeasSection.style.display = 'none';
                initiativesSection.style.display = 'none';
                interestsSection.style.display = 'block';
                initiativeForm.style.display = 'none';
                interestsSection.scrollIntoView({ behavior: 'smooth' });
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
            
            // Add alert styles
            const alertStyles = document.createElement('style');
            alertStyles.innerHTML = `
                .alert {
                    padding: 15px;
                    margin-bottom: 20px;
                    border: 1px solid transparent;
                    border-radius: 5px;
                }
                
                .alert-success {
                    color: #155724;
                    background-color: #d4edda;
                    border-color: #c3e6cb;
                }
                
                .alert-danger {
                    color: #721c24;
                    background-color: #f8d7da;
                    border-color: #f5c6cb;
                }
                
                .empty-state {
                    text-align: center;
                    padding: 40px 20px;
                    background-color: #f9f9f9;
                    border-radius: 8px;
                    margin: 20px 0;
                }
                
                .empty-icon {
                    font-size: 3rem;
                    color: #ccc;
                    margin-bottom: 15px;
                }
                
                /* Modal styles */
                .modal {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0, 0, 0, 0.5);
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    opacity: 0;
                    visibility: hidden;
                    transition: all 0.3s ease;
                    z-index: 9999;
                }
                
                .modal.show {
                    opacity: 1;
                    visibility: visible;
                }
                
                .modal-content {
                    background-color: white;
                    padding: 25px;
                    border-radius: 10px;
                    max-width: 600px;
                    width: 90%;
                    max-height: 80vh;
                    overflow-y: auto;
                    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
                    position: relative;
                    transform: translateY(20px);
                    transition: transform 0.3s ease;
                }
                
                .modal.show .modal-content {
                    transform: translateY(0);
                }
                
                .close-modal {
                    position: absolute;
                    top: 15px;
                    right: 20px;
                    font-size: 24px;
                    cursor: pointer;
                    color: #aaa;
                    transition: color 0.3s ease;
                }
                
                .close-modal:hover {
                    color: #333;
                }
                
                .full-proposal {
                    margin-top: 15px;
                    line-height: 1.6;
                    white-space: pre-wrap;
                }
                
                .proposal-preview {
                    position: relative;
                }
                
                .read-more {
                    color: #0066cc;
                    cursor: pointer;
                    font-weight: 500;
                }
                
                .read-more:hover {
                    text-decoration: underline;
                }
                
                /* Notification Modal Styles */
                .notification-modal {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0, 0, 0, 0.5);
                    z-index: 9999;
                    display: none;
                    justify-content: center;
                    align-items: flex-start;
                    overflow-y: auto;
                    padding-top: 80px;
                }
                
                .notification-modal.show {
                    display: flex;
                }
                
                .notification-modal-content {
                    background-color: white;
                    width: 400px;
                    max-width: 95%;
                    border-radius: 10px;
                    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
                    overflow: hidden;
                    animation: slideDown 0.3s ease;
                }
                
                @keyframes slideDown {
                    from { transform: translateY(-20px); opacity: 0; }
                    to { transform: translateY(0); opacity: 1; }
                }
                
                .notification-modal-header {
                    padding: 15px 20px;
                    background-color: #FFE535;
                    color: #333;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    border-bottom: 1px solid #e0e0e0;
                }
                
                .notification-modal-header h3 {
                    margin: 0;
                    font-size: 1.2rem;
                }
                
                .close-notification-modal {
                    font-size: 1.5rem;
                    cursor: pointer;
                    color: #333;
                    transition: color 0.2s;
                }
                
                .close-notification-modal:hover {
                    color: #666;
                }
                
                .notification-modal-body {
                    max-height: 400px;
                    overflow-y: auto;
                }
                
                .notification-item {
                    padding: 15px 20px;
                    display: flex;
                    border-bottom: 1px solid #f0f0f0;
                    transition: background-color 0.2s;
                    position: relative;
                }
                
                .notification-item:hover {
                    background-color: #f9f9f9;
                }
                
                .notification-item.unread {
                    background-color: #f5f9ff;
                }
                
                .notification-item.unread:hover {
                    background-color: #edf3fa;
                }
                
                .notification-icon {
                    width: 36px;
                    height: 36px;
                    border-radius: 50%;
                    background-color: #FFE535;
                    color: #333;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    margin-right: 15px;
                    flex-shrink: 0;
                }
                
                .notification-content {
                    flex: 1;
                }
                
                .notification-title {
                    font-weight: 600;
                    font-size: 0.95rem;
                    margin-bottom: 4px;
                    color: #333;
                }
                
                .notification-message {
                    font-size: 0.85rem;
                    color: #666;
                    line-height: 1.4;
                    margin-bottom: 5px;
                }
                
                .notification-time {
                    font-size: 0.75rem;
                    color: #999;
                }
                
                .notification-actions {
                    display: flex;
                    margin-left: 10px;
                }
                
                .notification-btn {
                    background: none;
                    border: none;
                    color: #999;
                    cursor: pointer;
                    font-size: 0.85rem;
                    padding: 3px 6px;
                    border-radius: 3px;
                    transition: all 0.2s;
                }
                
                .notification-btn:hover {
                    background-color: #f0f0f0;
                    color: #333;
                }
                
                .notification-modal-footer {
                    padding: 12px 20px;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    background-color: #f9f9f9;
                    border-top: 1px solid #e0e0e0;
                }
                
                .mark-all-read {
                    background: none;
                    border: none;
                    color: #555;
                    font-size: 0.85rem;
                    cursor: pointer;
                    padding: 5px 10px;
                    border-radius: 4px;
                    transition: background-color 0.2s;
                }
                
                .mark-all-read:hover {
                    background-color: #f0f0f0;
                }
                
                /* Notification badge styles */
                .notification-badge {
                    position: relative;
                    cursor: pointer;
                    margin-right: 15px;
                    font-size: 1.1rem;
                    color: #333;
                    --badge-display: block;
                }
                
                .notification-badge::after {
                    content: attr(data-count);
                    position: absolute;
                    top: -8px;
                    right: -8px;
                    background-color: #e74c3c;
                    color: white;
                    font-size: 0.7rem;
                    font-weight: 700;
                    padding: 1px 6px;
                    border-radius: 10px;
                    min-width: 10px;
                    text-align: center;
                    display: var(--badge-display);
                }
                
                .notification-badge:hover {
                    color: #0066cc;
                }
                
                /* Toast styles */
                .toast-container {
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    z-index: 9999;
                }
                
                .toast {
                    display: flex;
                    background-color: white;
                    border-radius: 5px;
                    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
                    margin-bottom: 10px;
                    width: 300px;
                    overflow: hidden;
                    transform: translateX(100%);
                    opacity: 0;
                    transition: all 0.3s ease;
                }
                
                .toast.show {
                    transform: translateX(0);
                    opacity: 1;
                }
                
                .toast-icon {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 0 15px;
                    font-size: 1.2rem;
                }
                
                .toast-success .toast-icon {
                    background-color: #28a745;
                    color: white;
                }
                
                .toast-error .toast-icon {
                    background-color: #dc3545;
                    color: white;
                }
                
                .toast-info .toast-icon {
                    background-color: #17a2b8;
                    color: white;
                }
                
                .toast-content {
                    flex: 1;
                    padding: 15px 5px 15px 15px;
                }
                
                .toast-title {
                    font-weight: bold;
                    margin-bottom: 5px;
                }
                
                .toast-message {
                    color: #6c757d;
                    font-size: 0.9rem;
                }
                
                .toast-close {
                    display: flex;
                    align-items: center;
                    padding: 0 10px;
                    cursor: pointer;
                    color: #aaa;
                }
                
                .toast-close:hover {
                    color: #333;
                }
            `;
            document.head.appendChild(alertStyles);
            
            // Handle delete initiative
            const deleteButtons = document.querySelectorAll('.delete-initiative');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const initiativeId = this.getAttribute('data-id');
                    
                    if (confirm('Are you sure you want to delete this initiative? This action cannot be undone.')) {
                        window.location.href = `../../actions/delete_initiative.php?id=${initiativeId}`;
                    }
                });
            });
            
            // Handle edit initiative
            const editButtons = document.querySelectorAll('.edit-initiative');
            editButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const initiativeId = this.getAttribute('data-id');
                    window.location.href = `../edit_initiative.php?id=${initiativeId}`;
                });
            });
            
            // Handle "Read More" for proposal previews
            const readMoreLinks = document.querySelectorAll('.read-more');
            readMoreLinks.forEach(link => {
                link.addEventListener('click', function() {
                    const fullProposal = this.getAttribute('data-proposal');
                    
                    // Create modal for full proposal
                    const modal = document.createElement('div');
                    modal.className = 'modal';
                    modal.innerHTML = `
                        <div class="modal-content">
                            <span class="close-modal">&times;</span>
                            <h3>Full Proposal</h3>
                            <div class="full-proposal">
                                ${fullProposal.replace(/\n/g, '<br>')}
                            </div>
                        </div>
                    `;
                    
                    document.body.appendChild(modal);
                    
                    // Show modal
                    setTimeout(() => {
                        modal.classList.add('show');
                    }, 10);
                    
                    // Handle close
                    const closeBtn = modal.querySelector('.close-modal');
                    closeBtn.addEventListener('click', function() {
                        modal.classList.remove('show');
                        setTimeout(() => {
                            document.body.removeChild(modal);
                        }, 300);
                    });
                    
                    // Close when clicking outside
                    modal.addEventListener('click', function(e) {
                        if (e.target === modal) {
                            modal.classList.remove('show');
                            setTimeout(() => {
                                document.body.removeChild(modal);
                            }, 300);
                        }
                    });
                });
            });
            
            // Function to show idea details in modal
            function showIdeaDetails(ideaId, ideaTitle) {
                // Set the title in the modal
                document.getElementById('modalIdeaTitle').textContent = ideaTitle;
                
                // For mock ideas, just show static content
                if (ideaId >= 1000) {
                    let mockContent = '';
                    
                    // Find the idea details from our mock data
                    switch(ideaId) {
                        case 1001:
                            mockContent = `
                                <div class="idea-detail-section">
                                    <h4>Smart City Waste Management</h4>
                                    <p><strong>Sector:</strong> Urban Development</p>
                                    <p><strong>Status:</strong> Under Review</p>
                                    <p><strong>Description:</strong> An IoT-based waste management system that optimizes collection routes and schedules based on real-time fill-level monitoring of waste containers. The system includes sensors, a central monitoring platform, and mobile applications for waste collection crews.</p>
                                    <p><strong>Target Impact:</strong> Reduces collection costs by 30%, fuel consumption by 40%, and improves overall city cleanliness scores.</p>
                                    <p><strong>Technology:</strong> IoT sensors, cloud computing, mobile applications, data analytics</p>
                                </div>
                            `;
                            break;
                        case 1002:
                            mockContent = `
                                <div class="idea-detail-section">
                                    <h4>Renewable Energy Grid Integration</h4>
                                    <p><strong>Sector:</strong> Energy</p>
                                    <p><strong>Status:</strong> Pending Review</p>
                                    <p><strong>Description:</strong> A comprehensive solution for integrating distributed renewable energy sources (solar, wind) into the existing power grid. Includes smart inverters, grid management software, and AI-based forecasting for demand and supply.</p>
                                    <p><strong>Target Impact:</strong> Increases renewable energy capacity by 45%, reduces grid instability by 60%, and lowers carbon emissions by 25%.</p>
                                    <p><strong>Technology:</strong> Smart grid technology, AI forecasting, distributed energy resource management</p>
                                </div>
                            `;
                            break;
                        case 1003:
                            mockContent = `
                                <div class="idea-detail-section">
                                    <h4>AI-Driven Public Transport Optimization</h4>
                                    <p><strong>Sector:</strong> Transportation</p>
                                    <p><strong>Status:</strong> Under Review</p>
                                    <p><strong>Description:</strong> An AI-powered platform that optimizes public transport routes, frequency, and capacity based on real-time demand, traffic conditions, and special events. Includes passenger counting systems, mobile apps for users, and a central management dashboard.</p>
                                    <p><strong>Target Impact:</strong> Reduces wait times by 35%, increases ridership by 25%, and lowers operational costs by 20%.</p>
                                    <p><strong>Technology:</strong> Machine learning, real-time analytics, mobile applications, IoT sensors</p>
                                </div>
                            `;
                            break;
                        case 1004:
                            mockContent = `
                                <div class="idea-detail-section">
                                    <h4>Digital Healthcare Platform for Rural Areas</h4>
                                    <p><strong>Sector:</strong> Healthcare</p>
                                    <p><strong>Status:</strong> New Submission</p>
                                    <p><strong>Description:</strong> A comprehensive digital healthcare platform designed for rural communities with limited access to medical facilities. Features include telemedicine, AI-assisted diagnostics, health worker training modules, and medication delivery tracking.</p>
                                    <p><strong>Target Impact:</strong> Increases healthcare access for 500,000+ rural residents, reduces diagnostic delays by 60%, and improves treatment adherence by 40%.</p>
                                    <p><strong>Technology:</strong> Telemedicine, AI diagnostics, mobile health applications, rural connectivity solutions</p>
                                </div>
                            `;
                            break;
                        default:
                            mockContent = '<p>Idea details not available.</p>';
                    }
                    
                    document.getElementById('modalIdeaContent').innerHTML = mockContent;
                } else {
                    // For real ideas, fetch data from the server
                    fetch(`../../actions/get_idea_details.php?id=${ideaId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                let content = `
                                    <div class="idea-detail-section">
                                        <h4>${data.idea.title}</h4>
                                        <p><strong>Sector:</strong> ${data.idea.sector}</p>
                                        <p><strong>Status:</strong> ${data.idea.status}</p>
                                        <p><strong>Description:</strong> ${data.idea.description}</p>
                                        ${data.idea.budget ? `<p><strong>Budget:</strong> ${data.idea.budget}</p>` : ''}
                                        ${data.idea.timeline ? `<p><strong>Timeline:</strong> ${data.idea.timeline}</p>` : ''}
                                        ${data.idea.technology_used ? `<p><strong>Technology:</strong> ${data.idea.technology_used}</p>` : ''}
                                        ${data.idea.target_audience ? `<p><strong>Target Audience:</strong> ${data.idea.target_audience}</p>` : ''}
                                        ${data.idea.expected_impact ? `<p><strong>Expected Impact:</strong> ${data.idea.expected_impact}</p>` : ''}
                                    </div>
                                `;
                                document.getElementById('modalIdeaContent').innerHTML = content;
                            } else {
                                document.getElementById('modalIdeaContent').innerHTML = '<p>Failed to load idea details. Please try again later.</p>';
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching idea details:', error);
                            document.getElementById('modalIdeaContent').innerHTML = '<p>An error occurred while loading idea details.</p>';
                        });
                }
                
                // Show the modal
                document.getElementById('ideaDetailsModal').style.display = 'flex';
            }
            
            // Handle approve idea
            const approveButtons = document.querySelectorAll('.approve-idea');
            approveButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const ideaId = this.getAttribute('data-id');
                    const ideaTitle = this.getAttribute('data-title');
                    
                    if (confirm(`Are you sure you want to approve idea "${ideaTitle}"? The entrepreneur will be notified.`)) {
                        // Disable button to prevent multiple clicks
                        this.classList.add('disabled');
                        
                        // Send AJAX request to update status
                        fetch('../../actions/update_idea_status.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `id=${ideaId}&action=approve`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Update UI - change status pill
                                const row = this.closest('tr');
                                const statusPill = row.querySelector('.status-pill');
                                statusPill.textContent = 'Approved';
                                statusPill.className = 'status-pill status-approved';
                                
                                // Remove approve/reject buttons
                                row.querySelector('.reject-idea').parentNode.remove();
                                
                                // Show success toast
                                showToast('Success', 'Idea approved successfully!', 'success');
                            } else {
                                // Re-enable button
                                this.classList.remove('disabled');
                                showToast('Error', data.message || 'Failed to approve idea.', 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            // Re-enable button
                            this.classList.remove('disabled');
                            showToast('Error', 'An unexpected error occurred', 'error');
                        });
                    }
                });
            });
            
            // Handle reject idea
            const rejectButtons = document.querySelectorAll('.reject-idea');
            rejectButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const ideaId = this.getAttribute('data-id');
                    const ideaTitle = this.getAttribute('data-title');
                    
                    if (confirm(`Are you sure you want to reject idea "${ideaTitle}"? This action cannot be undone.`)) {
                        // Disable button to prevent multiple clicks
                        this.classList.add('disabled');
                        
                        // Send AJAX request to update status
                        fetch('../../actions/update_idea_status.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `id=${ideaId}&action=reject`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Update UI - change status pill
                                const row = this.closest('tr');
                                const statusPill = row.querySelector('.status-pill');
                                statusPill.textContent = 'Rejected';
                                statusPill.className = 'status-pill status-rejected';
                                
                                // Remove approve/reject buttons
                                row.querySelector('.approve-idea').parentNode.remove();
                                row.querySelector('.reject-idea').parentNode.remove();
                                
                                // Show success toast
                                showToast('Notice', 'Idea rejected.', 'info');
                            } else {
                                // Re-enable button
                                this.classList.remove('disabled');
                                showToast('Error', data.message || 'Failed to reject idea.', 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            // Re-enable button
                            this.classList.remove('disabled');
                            showToast('Error', 'An unexpected error occurred', 'error');
                        });
                    }
                });
            });
            
            // Close idea details modal
            document.querySelector('#ideaDetailsModal .modal-close').addEventListener('click', function() {
                document.getElementById('ideaDetailsModal').style.display = 'none';
            });
            
            // Close modal when clicking outside of it
            window.addEventListener('click', function(event) {
                const modal = document.getElementById('ideaDetailsModal');
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
            
            // Function to show toast notifications
            function showToast(title, message, type = 'info') {
                const toast = document.createElement('div');
                toast.className = `toast toast-${type}`;
                
                let icon = 'fa-info-circle';
                if (type === 'success') icon = 'fa-check-circle';
                if (type === 'error') icon = 'fa-exclamation-circle';
                
                toast.innerHTML = `
                    <div class="toast-icon"><i class="fas ${icon}"></i></div>
                    <div class="toast-content">
                        <div class="toast-title">${title}</div>
                        <div class="toast-message">${message}</div>
                    </div>
                    <div class="toast-close"><i class="fas fa-times"></i></div>
                `;
                
                const toastContainer = document.querySelector('.toast-container');
                toastContainer.appendChild(toast);
                
                // Animate in
                setTimeout(() => {
                    toast.classList.add('show');
                }, 10);
                
                // Auto remove after 5 seconds
                setTimeout(() => {
                    toast.classList.remove('show');
                    setTimeout(() => {
                        toastContainer.removeChild(toast);
                    }, 300);
                }, 5000);
                
                // Handle manual close
                const closeBtn = toast.querySelector('.toast-close');
                closeBtn.addEventListener('click', function() {
                    toast.classList.remove('show');
                    setTimeout(() => {
                        toastContainer.removeChild(toast);
                    }, 300);
                });
            }
            
            // Profile and Account Settings Modal Functionality
            const editProfileBtn = document.getElementById('editProfileBtn');
            const accountSettingsBtn = document.getElementById('accountSettingsBtn');
            const profileModal = document.getElementById('editProfileModal');
            const accountSettingsModal = document.getElementById('accountSettingsModal');
            const closeButtons = document.querySelectorAll('.modal-close, .modal-cancel');
            
            // Show Edit Profile Modal
            if (editProfileBtn) {
                editProfileBtn.addEventListener('click', function() {
                    profileModal.classList.add('show');
                });
            }
            
            // Show Account Settings Modal
            if (accountSettingsBtn) {
                accountSettingsBtn.addEventListener('click', function() {
                    accountSettingsModal.classList.add('show');
                });
            }
            
            // Close modals
            closeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const modal = this.closest('.modal');
                    if (modal) modal.classList.remove('show');
                });
            });
            
            // Close modal when clicking outside
            window.addEventListener('click', function(e) {
                if (e.target.classList.contains('modal')) {
                    e.target.classList.remove('show');
                }
            });
            
            // Add this code after the distribution chart code
            
            // Initialize performance chart
            const perfCtx = document.getElementById('performanceChart').getContext('2d');
            
            // Create gradient fills for datasets
            const ideasReviewedGradient = perfCtx.createLinearGradient(0, 0, 0, 400);
            ideasReviewedGradient.addColorStop(0, 'rgba(255, 229, 53, 0.6)');
            ideasReviewedGradient.addColorStop(1, 'rgba(255, 229, 53, 0.1)');
            
            const initiativesGradient = perfCtx.createLinearGradient(0, 0, 0, 400);
            initiativesGradient.addColorStop(0, 'rgba(23, 162, 184, 0.6)');
            initiativesGradient.addColorStop(1, 'rgba(23, 162, 184, 0.1)');
            
            const collaborationsGradient = perfCtx.createLinearGradient(0, 0, 0, 400);
            collaborationsGradient.addColorStop(0, 'rgba(40, 167, 69, 0.6)');
            collaborationsGradient.addColorStop(1, 'rgba(40, 167, 69, 0.1)');
            
            const performanceChart = new Chart(perfCtx, {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [
                        {
                            label: 'Ideas Reviewed',
                            data: [7, 12, 8, 15, 10, 5, 13],
                            borderColor: 'rgba(255, 229, 53, 1)',
                            backgroundColor: ideasReviewedGradient,
                            borderWidth: 3,
                            tension: 0.4,
                            fill: true,
                            pointBackgroundColor: '#fff',
                            pointBorderColor: 'rgba(255, 229, 53, 1)',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        },
                        {
                            label: 'Initiatives Created',
                            data: [1, 0, 2, 1, 3, 0, 2],
                            borderColor: 'rgba(23, 162, 184, 1)',
                            backgroundColor: initiativesGradient,
                            borderWidth: 3,
                            tension: 0.4,
                            fill: true,
                            pointBackgroundColor: '#fff',
                            pointBorderColor: 'rgba(23, 162, 184, 1)',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        },
                        {
                            label: 'Collaborations',
                            data: [0, 2, 3, 1, 4, 2, 3],
                            borderColor: 'rgba(40, 167, 69, 1)',
                            backgroundColor: collaborationsGradient,
                            borderWidth: 3,
                            tension: 0.4,
                            fill: true,
                            pointBackgroundColor: '#fff',
                            pointBorderColor: 'rgba(40, 167, 69, 1)',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            backgroundColor: 'rgba(255, 255, 255, 0.95)',
                            titleColor: '#333',
                            bodyColor: '#666',
                            borderColor: 'rgba(0, 0, 0, 0.1)',
                            borderWidth: 1,
                            padding: 12,
                            boxPadding: 6,
                            bodyFont: {
                                size: 12,
                                weight: '500'
                            },
                            titleFont: {
                                size: 14,
                                weight: '600'
                            },
                            displayColors: true,
                            boxWidth: 8,
                            boxHeight: 8,
                            usePointStyle: true,
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                display: true,
                                color: 'rgba(0, 0, 0, 0.05)',
                                drawBorder: false
                            },
                            ticks: {
                                font: {
                                    size: 11
                                },
                                color: '#999',
                                padding: 10
                            },
                            border: {
                                display: false
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
                                color: '#999',
                                padding: 10
                            },
                            border: {
                                display: false
                            }
                        }
                    },
                    elements: {
                        line: {
                            borderWidth: 3
                        },
                        point: {
                            hoverRadius: 6,
                            hoverBorderWidth: 3
                        }
                    }
                }
            });
            
            // Add functionality to chart tabs for performance chart
            document.querySelectorAll('.chart-tab').forEach((tab, index) => {
                tab.addEventListener('click', function() {
                    // Remove active class from all tabs
                    document.querySelectorAll('.chart-tab').forEach(t => t.classList.remove('active'));
                    // Add active class to clicked tab
                    this.classList.add('active');
                    
                    // Show/hide datasets based on selected tab
                    if (index === 0) { // Growth
                        performanceChart.data.datasets[0].hidden = false;
                        performanceChart.data.datasets[1].hidden = false;
                        performanceChart.data.datasets[2].hidden = true;
                    } else if (index === 1) { // Engagement
                        performanceChart.data.datasets[0].hidden = false;
                        performanceChart.data.datasets[1].hidden = true;
                        performanceChart.data.datasets[2].hidden = false;
                    } else if (index === 2) { // Distribution
                        performanceChart.data.datasets[0].hidden = true;
                        performanceChart.data.datasets[1].hidden = false;
                        performanceChart.data.datasets[2].hidden = false;
                    }
                    
                    performanceChart.update();
                });
            });
            
            // Handle performance chart period buttons
            const perfChartButtons = document.querySelectorAll('.chart-btn[data-period]');
            perfChartButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from all buttons
                    perfChartButtons.forEach(btn => btn.classList.remove('active'));
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    const period = this.getAttribute('data-period');
                    
                    // Simulate data changes based on selected period
                    if (period === 'week') {
                        performanceChart.data.labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                        performanceChart.data.datasets[0].data = [7, 12, 8, 15, 10, 5, 13];
                        performanceChart.data.datasets[1].data = [1, 0, 2, 1, 3, 0, 2];
                        performanceChart.data.datasets[2].data = [0, 2, 3, 1, 4, 2, 3];
                    } else if (period === 'month') {
                        performanceChart.data.labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
                        performanceChart.data.datasets[0].data = [35, 42, 50, 38];
                        performanceChart.data.datasets[1].data = [5, 8, 7, 10];
                        performanceChart.data.datasets[2].data = [10, 12, 15, 18];
                    } else if (period === 'year') {
                        performanceChart.data.labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                        performanceChart.data.datasets[0].data = [150, 180, 210, 250, 220, 240, 280, 260, 300, 320, 280, 330];
                        performanceChart.data.datasets[1].data = [20, 25, 30, 22, 28, 35, 30, 32, 40, 45, 38, 42];
                        performanceChart.data.datasets[2].data = [35, 40, 45, 50, 55, 60, 58, 62, 70, 75, 78, 85];
                    }
                    
                    performanceChart.update();
                });
            });
        });
    </script>
    
    <!-- Edit Profile Modal -->
    <div class="modal" id="editProfileModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Profile</h3>
                <button class="modal-close"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form id="editProfileForm" action="../../includes/update_profile.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="user_id" value="<?php echo $_SESSION['user_id']; ?>">
                    <div class="form-group">
                        <label for="fullName">Full Name</label>
                        <input type="text" id="fullName" name="fullName" value="<?php echo htmlspecialchars($userData['full_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($userData['email']); ?>" readonly class="readonly-field">
                        <p class="help-text">Email address cannot be changed for security reasons.</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="govtId">Government ID</label>
                        <input type="text" id="govtId" name="govtId" value="<?php echo htmlspecialchars($userData['govt_id']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="department">Department</label>
                        <input type="text" id="department" name="department" value="<?php echo isset($userData['department']) ? htmlspecialchars($userData['department']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="bio">Bio</label>
                        <textarea id="bio" name="bio" rows="3" placeholder="Tell us about your department and role..."><?php echo isset($userData['bio']) ? htmlspecialchars($userData['bio']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="profilePicture">Profile Picture</label>
                        <input type="file" id="profilePicture" name="profilePicture" accept="image/*">
                        <?php if(isset($userData['profile_pic']) && !empty($userData['profile_pic'])): ?>
                        <div class="current-image">
                            <p>Current image: </p>
                            <img src="/ColabX/<?php echo htmlspecialchars($userData['profile_pic']); ?>" alt="Current Profile Picture" style="max-width: 100px; max-height: 100px; margin-top: 10px;">
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="action-btn primary-btn">Save Changes</button>
                        <button type="button" class="action-btn secondary-btn modal-cancel">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Account Settings Modal -->
    <div class="modal" id="accountSettingsModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Account Settings</h3>
                <button class="modal-close"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form id="accountSettingsForm" action="../../includes/update_settings.php" method="POST">
                    <input type="hidden" name="user_id" value="<?php echo $_SESSION['user_id']; ?>">
                    <div class="form-section">
                        <h4>Change Password</h4>
                        <div class="form-group">
                            <label for="currentPassword">Current Password</label>
                            <input type="password" id="currentPassword" name="currentPassword">
                        </div>
                        
                        <div class="form-group">
                            <label for="newPassword">New Password</label>
                            <input type="password" id="newPassword" name="newPassword">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirmPassword">Confirm New Password</label>
                            <input type="password" id="confirmPassword" name="confirmPassword">
                        </div>
                        <p class="help-text">Leave password fields empty if you don't want to change it</p>
                    </div>
                    
                    <div class="form-section">
                        <h4>Notification Settings</h4>
                        <div class="form-group checkbox-group">
                            <input type="checkbox" id="emailNotifications" name="notifications[email]" value="1" <?php echo isset($userData['email_notifications']) && $userData['email_notifications'] ? 'checked' : ''; ?>>
                            <label for="emailNotifications">Email Notifications</label>
                        </div>
                        
                        <div class="form-group checkbox-group">
                            <input type="checkbox" id="initiativeAlerts" name="notifications[initiatives]" value="1" <?php echo isset($userData['initiative_alerts']) && $userData['initiative_alerts'] ? 'checked' : ''; ?>>
                            <label for="initiativeAlerts">Initiative Alerts</label>
                        </div>
                        
                        <div class="form-group checkbox-group">
                            <input type="checkbox" id="ideaReviews" name="notifications[reviews]" value="1" <?php echo isset($userData['idea_reviews']) && $userData['idea_reviews'] ? 'checked' : ''; ?>>
                            <label for="ideaReviews">Idea Review Notifications</label>
                        </div>
                        
                        <div class="form-group checkbox-group">
                            <input type="checkbox" id="connectionAlerts" name="notifications[connections]" value="1" <?php echo isset($userData['connection_alerts']) && $userData['connection_alerts'] ? 'checked' : ''; ?>>
                            <label for="connectionAlerts">Connection Alerts</label>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h4>Account Privacy</h4>
                        <div class="form-group radio-group">
                            <div class="radio-options-container">
                                <div class="radio-option">
                                    <div class="radio-header">
                                        <input type="radio" id="privacyPublic" name="privacyLevel" value="public" <?php echo (!isset($userData['privacy_level']) || $userData['privacy_level'] == 'public') ? 'checked' : ''; ?>>
                                        <label for="privacyPublic">Public Profile</label>
                                    </div>
                                    <p class="help-text">Anyone can see your profile and department information</p>
                                </div>
                                
                                <div class="radio-option">
                                    <div class="radio-header">
                                        <input type="radio" id="privacyLimited" name="privacyLevel" value="limited" <?php echo (isset($userData['privacy_level']) && $userData['privacy_level'] == 'limited') ? 'checked' : ''; ?>>
                                        <label for="privacyLimited">Limited Profile</label>
                                    </div>
                                    <p class="help-text">Only registered users can see your profile details</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="action-btn primary-btn">Save Settings</button>
                        <button type="button" class="action-btn secondary-btn modal-cancel">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html> 