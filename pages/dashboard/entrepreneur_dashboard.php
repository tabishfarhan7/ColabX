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

// Get actual ideas from database
$ideasQuery = "SELECT id, title, sector, status, views, created_at 
              FROM ideas 
              WHERE user_id = " . $_SESSION['user_id'] . "
              ORDER BY created_at DESC";
$ideasResult = $conn->query($ideasQuery);

$userIdeas = [];
if ($ideasResult && $ideasResult->num_rows > 0) {
    while ($row = $ideasResult->fetch_assoc()) {
        // Count connections for this idea
        $connectionsQuery = "SELECT COUNT(*) as count FROM connections WHERE idea_id = " . $row['id'];
        $connectionsResult = $conn->query($connectionsQuery);
        $connectionsCount = 0;
        if ($connectionsResult && $connectionsResult->num_rows > 0) {
            $connectionsCount = $connectionsResult->fetch_assoc()['count'];
        }
        
        $userIdeas[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'submission_date' => date('Y-m-d', strtotime($row['created_at'])),
            'status' => ucfirst(str_replace('_', ' ', $row['status'])),
            'views' => $row['views'],
            'connections' => $connectionsCount
        ];
    }
}

// If no ideas found, use empty array
if (empty($userIdeas)) {
    $userIdeas = [];
}

$govtInitiatives = [
    [
        'id' => 1,
        'title' => 'Smart City Development Program',
        'department' => 'Urban Development',
        'organization' => 'Ministry of Urban Development',
        'posted_date' => '2023-10-10',
        'deadline' => '2023-12-31',
        'match_score' => 92,
        'description' => 'We are looking for innovative solutions to transform urban areas into smart cities with integrated technology for better resource management, improved quality of life, and sustainable development. Projects may include IoT sensors, data analytics, energy efficiency, or smart transportation systems.',
        'requirements' => 'Technology startups, software companies, IoT solution providers with proven track record.'
    ],
    [
        'id' => 2,
        'title' => 'Clean Energy Innovation Challenge',
        'department' => 'Energy',
        'organization' => 'Department of Energy',
        'posted_date' => '2023-11-05',
        'deadline' => '2024-01-15',
        'match_score' => 85,
        'description' => 'This initiative aims to develop and implement innovative renewable energy solutions to reduce carbon emissions. We are seeking proposals for solar, wind, hydro, or other clean energy technologies that can be scaled for mass adoption.',
        'requirements' => 'Renewable energy companies, research institutions, and innovative startups with demonstrable experience in the energy sector.'
    ],
    [
        'id' => 3,
        'title' => 'Digital Governance Transformation',
        'department' => 'IT & Communication',
        'organization' => 'Ministry of Digital Affairs',
        'posted_date' => '2023-11-12',
        'deadline' => '2024-02-28',
        'match_score' => 78,
        'description' => 'We are seeking digital solutions to simplify government services and improve citizen engagement. Proposed solutions should focus on e-governance, citizen services applications, or process automation systems.',
        'requirements' => 'Software development companies, system integrators, and digital consultancies with experience in government technology projects.'
    ],
    [
        'id' => 4,
        'title' => 'Healthcare Innovation Program',
        'department' => 'Health',
        'organization' => 'Ministry of Health',
        'posted_date' => '2023-11-20',
        'deadline' => '2024-03-15',
        'match_score' => 73,
        'description' => 'This program focuses on technological innovations in healthcare delivery, telemedicine, health information systems, and medical devices. We are looking for solutions that can improve healthcare access and quality.',
        'requirements' => 'Healthcare technology companies, medical device manufacturers, and health informatics specialists.'
    ],
    [
        'id' => 5,
        'title' => 'Agricultural Modernization Initiative',
        'department' => 'Agriculture',
        'organization' => 'Department of Agriculture',
        'posted_date' => '2023-11-25',
        'deadline' => '2024-02-28',
        'match_score' => 68,
        'description' => 'We are seeking innovative solutions to modernize agricultural practices, including precision farming, crop monitoring, supply chain optimization, and sustainable farming technologies.',
        'requirements' => 'AgriTech companies, farm management solution providers, and agricultural consultants with proven innovations.'
    ]
];

// Get recent user activities from database
$recentActivities = get_user_activities($conn, $_SESSION['user_id'], 5);

// If no activities yet, provide some default ones as examples
if (empty($recentActivities)) {
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
    
    // Record initial login activity for new users
    record_activity($conn, $_SESSION['user_id'], 'login', 'You logged in to your account');
}

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

// Get user's expressed interests
$userInterestsQuery = "SELECT initiative_id, proposal, idea_id, created_at FROM initiative_interests WHERE user_id = ?";
$userInterestsStmt = $conn->prepare($userInterestsQuery);
$userInterestsStmt->bind_param("i", $_SESSION['user_id']);
$userInterestsStmt->execute();
$result = $userInterestsStmt->get_result();
$userInterests = [];
while ($row = $result->fetch_assoc()) {
    $userInterests[] = $row;
}

// Format interests for JavaScript
$formattedInterests = [];
foreach ($userInterests as $interest) {
    $formattedInterests[$interest['initiative_id']] = [
        'timestamp' => $interest['created_at'],
        'proposal' => $interest['proposal'],
        'ideaId' => $interest['idea_id']
    ];
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
    <style>
        /* Dashboard Container */
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
            margin-top: 80px;
        }

        /* Dashboard Header */
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .welcome-message {
            font-size: 1.5rem;
            color: #333;
            font-weight: 500;
        }

        .logout-btn {
            padding: 8px 15px;
            background-color: #f5f5f5;
            color: #333;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background-color: #e0e0e0;
        }

        /* Dashboard Content */
        .dashboard-content {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .dashboard-content h2 {
            margin-top: 0;
            margin-bottom: 0.5rem;
            color: #333;
            font-size: 1.8rem;
            position: relative;
            display: inline-block;
        }

        .dashboard-content h2::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -5px;
            width: 50px;
            height: 3px;
            background-color: #FFE535;
        }

        /* Dashboard Sections */
        .dashboard-section {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #f0f0f0;
        }

        .dashboard-section h3 {
            margin-top: 1.5rem;
            margin-bottom: 1rem;
            font-size: 1.3rem;
            color: #333;
        }

        /* Stats Container */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: #fff;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            position: relative;
            transition: all 0.3s ease;
            text-align: center;
            overflow: hidden;
            border-left: 4px solid #f0f0f0;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .stat-card h3 {
            font-size: 1rem;
            color: #666;
            margin-top: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .stat-card p {
            font-size: 0.85rem;
            color: #999;
            margin-bottom: 0;
        }

        .stat-card .icon {
            font-size: 1.5rem;
            width: 50px;
            height: 50px;
            line-height: 50px;
            text-align: center;
            border-radius: 50%;
            margin: 0 auto 1rem;
            color: #fff;
        }

        .stat-card:nth-child(1) {
            border-left-color: #FFE535;
        }
        .stat-card:nth-child(1) .icon {
            background-color: #FFE535;
        }

        .stat-card:nth-child(2) {
            border-left-color: #17a2b8;
        }
        .stat-card:nth-child(2) .icon {
            background-color: #17a2b8;
        }

        .stat-card:nth-child(3) {
            border-left-color: #28a745;
        }
        .stat-card:nth-child(3) .icon {
            background-color: #28a745;
        }

        .stat-card:nth-child(4) {
            border-left-color: #dc3545;
        }
        .stat-card:nth-child(4) .icon {
            background-color: #dc3545;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .action-btn {
            padding: 0.75rem 1.5rem;
            border-radius: 5px;
            font-size: 0.9rem;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .primary-btn {
            background-color: #FFE535;
            color: #333;
        }

        .primary-btn:hover {
            background-color: #FFD700;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .secondary-btn {
            background-color: #f5f5f5;
            color: #333;
        }

        .secondary-btn:hover {
            background-color: #e0e0e0;
            transform: translateY(-2px);
        }

        /* Dashboard Table */
        .dashboard-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            border-radius: 5px;
            overflow: hidden;
        }

        .dashboard-table th {
            background-color: #f5f5f5;
            color: #333;
            padding: 1rem;
            text-align: left;
            font-weight: 500;
            border-bottom: 1px solid #eee;
        }

        .dashboard-table td {
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }

        .dashboard-table tr:hover {
            background-color: #f9f9f9;
        }

        /* Chart Container - Updated Modern Style */
        .chart-container {
            background-color: #fff;
            border-radius: 15px;
            padding: 1.8rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            margin-bottom: 2.5rem;
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.03);
            position: relative;
            overflow: hidden;
        }
        
        .chart-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #FFE535, #FFC107);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.8rem;
            padding-bottom: 0.8rem;
            border-bottom: 1px solid #f0f0f0;
        }

        .chart-title {
            font-size: 1.2rem;
            color: #333;
            margin: 0;
            font-weight: 600;
            position: relative;
            padding-left: 15px;
        }
        
        .chart-title::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 5px;
            height: 20px;
            background: #FFE535;
            border-radius: 3px;
        }

        .chart-actions {
            display: flex;
            gap: 0.5rem;
            background: #f5f5f5;
            padding: 5px;
            border-radius: 10px;
        }

        .chart-btn {
            padding: 0.5rem 1rem;
            background-color: transparent;
            border: none;
            border-radius: 8px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 500;
        }

        .chart-btn:hover {
            background-color: rgba(255, 229, 53, 0.2);
        }

        .chart-btn.active {
            background-color: #FFE535;
            color: #333;
            box-shadow: 0 3px 8px rgba(255, 229, 53, 0.3);
        }
        
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
        }

        .chart-tab i {
            font-size: 1rem;
        }

        /* Status badges */
        .status-badge {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
        }

        .status-badge.pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-badge.under-review {
            background-color: #cce5ff;
            color: #004085;
        }

        .status-badge.approved {
            background-color: #d4edda;
            color: #155724;
        }

        .status-badge.rejected {
            background-color: #f8d7da;
            color: #721c24;
        }

        /* Action Links */
        .actions {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
        }

        .action-link {
            color: #6c757d;
            font-size: 1rem;
            transition: all 0.2s ease;
        }

        .action-link.view:hover {
            color: #007bff;
        }

        .action-link.edit:hover {
            color: #17a2b8;
        }

        .action-link.delete:hover {
            color: #dc3545;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin: 2rem 0;
        }

        .empty-state .empty-icon {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 1rem;
        }

        .empty-state h4 {
            margin-bottom: 0.5rem;
            color: #343a40;
        }

        .empty-state p {
            color: #6c757d;
            margin-bottom: 1.5rem;
        }

        /* Activity items - Updated with modern styling */
        .activity-section {
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            margin-bottom: 2.5rem;
            border: 1px solid rgba(0, 0, 0, 0.03);
            position: relative;
            overflow: hidden;
        }
        
        .activity-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #17a2b8, #28a745);
        }
        
        .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 1.8rem;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .activity-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin: 0;
            position: relative;
            padding-left: 15px;
        }
        
        .activity-title::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 5px;
            height: 20px;
            background: #17a2b8;
            border-radius: 3px;
        }
        
        .activity-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .refresh-activities {
            background-color: transparent;
            border: none;
            color: #6c757d;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.2s;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .refresh-activities:hover {
            background-color: #f8f9fa;
            color: #17a2b8;
        }
        
        .refresh-activities.loading {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .activity-container {
            padding: 0;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .activity-item {
            display: flex;
            align-items: flex-start;
            padding: 1.2rem 1.8rem;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.2s;
            animation: fadeIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .activity-item:hover {
            background-color: #f8f9fa;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            min-width: 40px;
            line-height: 40px;
            text-align: center;
            border-radius: 50%;
            margin-right: 1rem;
            color: #fff;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .activity-icon.connection {
            background-color: #17a2b8;
        }
        
        .activity-icon.view {
            background-color: #28a745;
        }
        
        .activity-icon.comment {
            background-color: #fd7e14;
        }
        
        .activity-icon.update {
            background-color: #6f42c1;
        }
        
        .activity-icon.login {
            background-color: #007bff;
        }
        
        .activity-icon.profile_update {
            background-color: #20c997;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-text {
            font-size: 0.95rem;
            margin-bottom: 0.3rem;
            color: #343a40;
            line-height: 1.4;
        }
        
        .activity-time {
            font-size: 0.8rem;
            color: #6c757d;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .activity-time i {
            font-size: 0.75rem;
        }
        
        .activity-empty {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }

        /* Section Actions */
        .section-actions {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 1rem;
        }

        /* Alert Messages */
        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 5px;
            font-size: 0.95rem;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        body.modal-open {
            overflow: hidden;
        }

        .modal-content {
            background-color: #fff;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            animation: modalFadeIn 0.3s ease;
        }
        
        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            margin: 0;
            font-size: 1.3rem;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            color: #999;
            transition: color 0.2s;
        }
        
        .modal-close:hover {
            color: #333;
        }
        
        .modal-body {
            padding: 20px;
            max-height: 70vh;
            overflow-y: auto;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-size: 0.9rem;
            margin-bottom: 8px;
            color: #555;
        }
        
        .form-group input, 
        .form-group textarea, 
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        
        .readonly-field {
            background-color: #f9f9f9;
            cursor: not-allowed;
        }
        
        .help-text {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 30px;
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

        /* Grid Layout */
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
            
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .profile-section {
                flex-direction: column;
            }
            
            .profile-image {
                border-right: none;
                border-bottom: 1px solid #f0f0f0;
                padding-bottom: 1.5rem;
            }
            
            .grid-2 {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .action-btn {
                width: 100%;
            }
        }

        /* Toast notifications */
        .toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 350px;
        }
        
        .toast {
            background-color: #fff;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: flex-start;
            gap: 12px;
            opacity: 1;
            transform: translateX(0);
            transition: all 0.3s ease;
            border-left: 4px solid #ddd;
            animation: toastIn 0.3s ease forwards;
        }
        
        @keyframes toastIn {
            from { opacity: 0; transform: translateX(50px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        .toast.toast-hide {
            opacity: 0;
            transform: translateX(50px);
        }
        
        .toast.toast-success {
            border-left-color: #28a745;
        }
        
        .toast.toast-error {
            border-left-color: #dc3545;
        }
        
        .toast.toast-warning {
            border-left-color: #ffc107;
        }
        
        .toast.toast-info {
            border-left-color: #17a2b8;
        }
        
        .toast-icon {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            flex-shrink: 0;
        }
        
        .toast-success .toast-icon {
            color: #28a745;
        }
        
        .toast-error .toast-icon {
            color: #dc3545;
        }
        
        .toast-warning .toast-icon {
            color: #ffc107;
        }
        
        .toast-info .toast-icon {
            color: #17a2b8;
        }
        
        .toast-content {
            flex: 1;
        }
        
        .toast-title {
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 3px;
            color: #333;
        }
        
        .toast-message {
            font-size: 0.85rem;
            color: #666;
        }
        
        .toast-close {
            color: #aaa;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
            padding: 5px;
            margin: -5px;
        }
        
        .toast-close:hover {
            color: #666;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 30px;
        }
        
        .form-section {
            margin-bottom: 25px;
        }
        
        .form-section h4 {
            font-size: 1.1rem;
            margin-top: 0;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .checkbox-group, .radio-group {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .checkbox-group input[type="checkbox"],
        .radio-group input[type="radio"] {
            width: auto;
            margin-right: 10px;
        }
        
        .radio-options-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
            width: 100%;
        }
        
        .radio-option {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 12px;
            transition: all 0.2s;
        }
        
        .radio-option:hover {
            background-color: #f0f0f0;
        }
        
        .radio-header {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }
        
        .radio-header label {
            margin-bottom: 0;
            font-weight: 500;
        }
        
        .current-image {
            margin-top: 10px;
        }

        /* Government Initiatives Section */
        .initiative-section {
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            margin-bottom: 2.5rem;
            border: 1px solid rgba(0, 0, 0, 0.03);
            position: relative;
            overflow: hidden;
        }
        
        .initiative-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #6f42c1, #007bff);
        }
        
        .initiative-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 1.8rem;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .initiative-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin: 0;
            position: relative;
            padding-left: 15px;
        }
        
        .initiative-title::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 5px;
            height: 20px;
            background: #6f42c1;
            border-radius: 3px;
        }
        
        .initiative-actions {
            display: flex;
            gap: 10px;
        }
        
        .initiative-filter {
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #fff;
            font-size: 0.9rem;
        }
        
        .initiative-search {
            display: flex;
            align-items: center;
            position: relative;
        }
        
        .initiative-search input {
            padding: 0.5rem 1rem 0.5rem 2.5rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 0.9rem;
            width: 200px;
        }
        
        .initiative-search i {
            position: absolute;
            left: 10px;
            color: #6c757d;
        }
        
        .initiative-card {
            border-radius: 10px;
            border: 1px solid #eee;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            background-color: #fff;
            position: relative;
            overflow: hidden;
        }
        
        .initiative-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            border-color: #d0d0d0;
        }
        
        .initiative-card.active {
            border-color: #6f42c1;
        }
        
        .initiative-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .initiative-card-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .initiative-card-dept {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 0.75rem;
        }
        
        .initiative-card-org {
            font-weight: 500;
            color: #495057;
            font-size: 0.95rem;
        }
        
        .match-score-badge {
            background: linear-gradient(135deg, #6f42c1, #007bff);
            color: white;
            border-radius: 20px;
            padding: 0.3rem 1rem;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .initiative-dates {
            display: flex;
            gap: 1.5rem;
            margin: 1rem 0;
        }
        
        .date-item {
            display: flex;
            flex-direction: column;
        }
        
        .date-label {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .date-value {
            font-weight: 500;
            color: #343a40;
        }
        
        .initiative-description {
            color: #495057;
            font-size: 0.95rem;
            line-height: 1.5;
            margin: 1rem 0;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .initiative-description.expanded {
            -webkit-line-clamp: unset;
        }
        
        .initiative-requirements {
            font-size: 0.9rem;
            color: #495057;
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            border-left: 4px solid #6f42c1;
            margin: 1rem 0;
        }
        
        .initiative-requirements strong {
            color: #343a40;
        }
        
        .initiative-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
        }
        
        .initiative-buttons {
            display: flex;
            gap: 10px;
        }
        
        .btn-express-interest {
            background-color: #6f42c1;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 0.6rem 1.2rem;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-express-interest:hover {
            background-color: #5e35b1;
        }
        
        .btn-express-interest.interested {
            background-color: #28a745;
        }
        
        .btn-express-interest.interested:hover {
            background-color: #218838;
        }
        
        .btn-details {
            background-color: transparent;
            border: 1px solid #6c757d;
            color: #6c757d;
            border-radius: 5px;
            padding: 0.6rem 1.2rem;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-details:hover {
            background-color: #f8f9fa;
            color: #495057;
        }
        
        .btn-details.less {
            border-color: #6f42c1;
            color: #6f42c1;
        }
        
        .initiative-empty {
            text-align: center;
            padding: 3rem 2rem;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin: 2rem 0;
        }
        
        .initiative-empty .empty-icon {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 1rem;
        }
        
        .initiative-empty h4 {
            margin-bottom: 0.5rem;
            color: #343a40;
        }
        
        .initiative-empty p {
            color: #6c757d;
            margin-bottom: 1.5rem;
        }
        
        .initiative-container {
            padding: 1.8rem;
        }
        
        .initiative-stats {
            display: flex;
            gap: 15px;
            margin-bottom: 1.5rem;
        }
        
        .initiative-stat {
            flex: 1;
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
            transition: all 0.3s;
        }
        
        .initiative-stat:hover {
            background-color: #f1f3f5;
            transform: translateY(-3px);
        }
        
        .initiative-stat-number {
            font-size: 1.8rem;
            font-weight: 600;
            color: #6f42c1;
            margin-bottom: 0.5rem;
        }
        
        .initiative-stat-label {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        /* Express Interest Modal */
        .interest-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .interest-form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .interest-form-group label {
            font-size: 0.9rem;
            font-weight: 500;
            color: #495057;
        }
        
        .interest-form-group textarea,
        .interest-form-group select {
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 0.9rem;
            resize: vertical;
        }
        
        .interest-form-group textarea:focus,
        .interest-form-group select:focus {
            border-color: #6f42c1;
            outline: none;
            box-shadow: 0 0 0 3px rgba(111, 66, 193, 0.2);
        }
        
        .interest-form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 1rem;
        }
        
        /* Animation for when interest is expressed */
        @keyframes successPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .interest-success {
            animation: successPulse 0.5s ease-in-out;
        }
        
        .initiative-filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .initiative-filter-group {
            display: flex;
            gap: 8px;
        }
        
        .initiative-filter-btn {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .initiative-filter-btn:hover {
            background-color: #e9ecef;
        }
        
        .initiative-filter-btn.active {
            background-color: #6f42c1;
            color: white;
            border-color: #6f42c1;
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
        
        <!-- Display success/error messages if they exist -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php 
                echo htmlspecialchars($_SESSION['success_message']);
                unset($_SESSION['success_message']); // Clear the message after displaying
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <?php 
                echo htmlspecialchars($_SESSION['error_message']);
                unset($_SESSION['error_message']); // Clear the message after displaying
                ?>
            </div>
        <?php endif; ?>
        
        <div class="dashboard-content">
            <h2>Entrepreneur Dashboard</h2>
            <p>Manage your ideas, connect with government organizations, and track your project progress.</p>
            
            <div class="action-buttons">
                <a href="../idea_form.php"><button class="action-btn primary-btn"><i class="fas fa-lightbulb"></i> Submit New Idea</button></a>
                <button class="action-btn secondary-btn" id="browseInitiatives"><i class="fas fa-building-columns"></i> Browse Government Initiatives</button>
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
                
                <div class="chart-tabs">
                    <div class="chart-tab active"><i class="fas fa-chart-line"></i> Growth</div>
                    <div class="chart-tab"><i class="fas fa-chart-bar"></i> Engagement</div>
                    <div class="chart-tab"><i class="fas fa-chart-pie"></i> Distribution</div>
                </div>
                
                <div class="chart-stats">
                    <div class="stats-row" style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                        <div class="stat-item">
                            <div style="font-size: 0.85rem; color: #6c757d;">Total Views</div>
                            <div style="font-size: 1.5rem; font-weight: 600; color: #333;"><?php echo $totalViews; ?></div>
                            <div class="stat-change positive">
                                <i class="fas fa-arrow-up"></i> 23% from last period
                            </div>
                        </div>
                        <div class="stat-item">
                            <div style="font-size: 0.85rem; color: #6c757d;">Connections</div>
                            <div style="font-size: 1.5rem; font-weight: 600; color: #333;"><?php echo $totalConnections; ?></div>
                            <div class="stat-change positive">
                                <i class="fas fa-arrow-up"></i> 12% from last period
                            </div>
                        </div>
                        <div class="stat-item">
                            <div style="font-size: 0.85rem; color: #6c757d;">Conversion Rate</div>
                            <div style="font-size: 1.5rem; font-weight: 600; color: #333;">18.3%</div>
                            <div class="stat-change negative">
                                <i class="fas fa-arrow-down"></i> 3% from last period
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
                        <div>Idea Views</div>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: rgba(23, 162, 184, 0.8);"></div>
                        <div>Connections</div>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: rgba(40, 167, 69, 0.8);"></div>
                        <div>Conversion Rate</div>
                    </div>
                </div>
            </div>
            
            <!-- Your Ideas Section -->
            <div class="dashboard-section">
                <h3>Your Ideas & Submissions</h3>
                <div class="section-actions">
                    <a href="../idea_form.php" class="btn primary-btn"><i class="fas fa-plus"></i> Add New Idea</a>
                </div>
                <?php if (empty($userIdeas)): ?>
                    <div class="empty-state">
                        <i class="fas fa-lightbulb empty-icon"></i>
                        <h4>No Ideas Yet</h4>
                        <p>You haven't submitted any ideas yet. Create your first innovative idea to connect with government initiatives.</p>
                        <a href="../idea_form.php" class="btn primary-btn">Submit Your First Idea</a>
                    </div>
                <?php else: ?>
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
                                <span class="status-badge <?php echo strtolower(str_replace(' ', '-', $idea['status'])); ?>">
                                    <?php echo htmlspecialchars($idea['status']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($idea['views']); ?></td>
                            <td><?php echo htmlspecialchars($idea['connections']); ?></td>
                            <td class="actions">
                                <a href="../view_idea.php?id=<?php echo $idea['id']; ?>" class="view-link"><i class="fas fa-eye"></i> View</a>
                                <a href="../idea_form.php?id=<?php echo $idea['id']; ?>" class="edit-link"><i class="fas fa-edit"></i> Edit</a>
                                <a href="#" class="delete-link" data-id="<?php echo $idea['id']; ?>"><i class="fas fa-trash"></i> Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
            
            <!-- Matching Government Initiatives -->
            <div class="initiative-section" id="initiativesSection" style="display: none;">
                <div class="initiative-header">
                    <h3 class="initiative-title">Browse Government Initiatives</h3>
                    <div class="initiative-actions">
                        <div class="initiative-search">
                            <i class="fas fa-search"></i>
                            <input type="text" id="initiativeSearch" placeholder="Search initiatives...">
                        </div>
                        <select class="initiative-filter" id="initiativeDeptFilter">
                            <option value="">All Departments</option>
                            <option value="Urban Development">Urban Development</option>
                            <option value="Energy">Energy</option>
                            <option value="IT & Communication">IT & Communication</option>
                            <option value="Health">Health</option>
                            <option value="Agriculture">Agriculture</option>
                        </select>
                    </div>
                </div>
                
                <div class="initiative-container">
                    <div class="initiative-stats">
                        <div class="initiative-stat">
                            <div class="initiative-stat-number"><?php echo count($govtInitiatives); ?></div>
                            <div class="initiative-stat-label">Total Initiatives</div>
                        </div>
                        <div class="initiative-stat">
                            <div class="initiative-stat-number" id="matchingInitiativesCount">3</div>
                            <div class="initiative-stat-label">Matching Your Profile</div>
                        </div>
                        <div class="initiative-stat">
                            <div class="initiative-stat-number" id="interestedInitiativesCount">0</div>
                            <div class="initiative-stat-label">Interested In</div>
                        </div>
                    </div>
                
                    <div class="initiative-filters">
                        <div class="initiative-filter-group">
                            <button class="initiative-filter-btn active" data-filter="all">All</button>
                            <button class="initiative-filter-btn" data-filter="match">Best Matches</button>
                            <button class="initiative-filter-btn" data-filter="deadline">Upcoming Deadlines</button>
                            <button class="initiative-filter-btn" data-filter="interested">Interested</button>
                        </div>
                    </div>
                    
                    <div id="initiativesList">
                    <?php foreach ($govtInitiatives as $initiative): ?>
                        <div class="initiative-card" data-id="<?php echo $initiative['id']; ?>" data-department="<?php echo $initiative['department']; ?>" data-match="<?php echo $initiative['match_score']; ?>">
                            <div class="initiative-card-header">
                                <div>
                                    <div class="initiative-card-title"><?php echo htmlspecialchars($initiative['title']); ?></div>
                                    <div class="initiative-card-dept"><?php echo htmlspecialchars($initiative['department']); ?></div>
                                    <div class="initiative-card-org"><?php echo htmlspecialchars($initiative['organization']); ?></div>
                            </div>
                                <div class="match-score-badge">
                                    <i class="fas fa-chart-line"></i> <?php echo $initiative['match_score']; ?>% Match
                        </div>
                            </div>
                            
                            <div class="initiative-dates">
                                <div class="date-item">
                                    <div class="date-label">Posted</div>
                                    <div class="date-value"><?php echo htmlspecialchars($initiative['posted_date']); ?></div>
                                </div>
                                <div class="date-item">
                                    <div class="date-label">Deadline</div>
                                    <div class="date-value"><?php echo htmlspecialchars($initiative['deadline']); ?></div>
                                </div>
                            </div>
                            
                            <div class="initiative-description" id="description-<?php echo $initiative['id']; ?>">
                                <?php echo htmlspecialchars($initiative['description']); ?>
                            </div>
                            
                            <div class="initiative-requirements" style="display: none;" id="requirements-<?php echo $initiative['id']; ?>">
                                <strong>Requirements:</strong> <?php echo htmlspecialchars($initiative['requirements']); ?>
                            </div>
                            
                            <div class="initiative-card-footer">
                                <div class="initiative-buttons">
                                    <button class="btn-express-interest" data-id="<?php echo $initiative['id']; ?>" data-title="<?php echo htmlspecialchars($initiative['title']); ?>">
                                        <i class="fas fa-handshake"></i> Express Interest
                                    </button>
                                    <button class="btn-details" data-id="<?php echo $initiative['id']; ?>">
                                        <i class="fas fa-info-circle"></i> More Details
                                    </button>
                                </div>
                            </div>
                    </div>
                    <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="activity-section">
                <div class="activity-header">
                    <h3 class="activity-title">Recent Activity</h3>
                    <div class="activity-actions">
                        <button id="refreshActivities" class="refresh-activities" title="Refresh activities">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
                <div id="activityContainer" class="activity-container">
                    <?php if (empty($recentActivities)): ?>
                        <div class="activity-empty">
                            <i class="fas fa-info-circle"></i> No recent activities yet
                        </div>
                    <?php else: ?>
                <?php foreach ($recentActivities as $activity): ?>
                <div class="activity-item">
                                <div class="activity-icon <?php echo $activity['type']; ?>">
                        <i class="fas <?php echo $activity['icon']; ?>"></i>
                    </div>
                    <div class="activity-content">
                                    <div class="activity-text"><?php echo htmlspecialchars($activity['title']); ?></div>
                                    <div class="activity-time">
                                        <i class="far fa-clock"></i>
                                        <?php echo htmlspecialchars($activity['time']); ?>
                                    </div>
                    </div>
                </div>
                <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Profile Section -->
            <div class="profile-section">
                <div class="profile-image">
                    <div class="profile-avatar">
                        <?php if(!empty($userData['profile_pic'])): ?>
                            <img src="/ColabX/<?php echo htmlspecialchars($userData['profile_pic']); ?>" alt="Profile Picture">
                        <?php else: ?>
                        <i class="fas fa-user"></i>
                        <?php endif; ?>
                    </div>
                    <div class="profile-name"><?php echo htmlspecialchars($userData['full_name']); ?></div>
                    <div class="profile-role">Entrepreneur</div>
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
                            <div class="info-label">Company</div>
                            <div class="info-value"><?php echo htmlspecialchars($userData['company_name']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Business Type</div>
                            <div class="info-value"><?php echo htmlspecialchars($userData['business_type']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Bio</div>
                            <div class="info-value"><?php echo !empty($userData['bio']) ? htmlspecialchars($userData['bio']) : 'No bio provided'; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Account Type</div>
                            <div class="info-value">Entrepreneur</div>
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
        <!-- Toasts will be dynamically inserted here -->
            </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h3>Delete Idea</h3>
            <p>Are you sure you want to delete this idea? This action cannot be undone.</p>
            <div class="modal-actions">
                <button id="confirmDelete" class="btn danger-btn">Delete</button>
                <button id="cancelDelete" class="btn secondary-btn">Cancel</button>
            </div>
        </div>
    </div>
    
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
                        <label for="companyName">Company Name</label>
                        <input type="text" id="companyName" name="companyName" value="<?php echo htmlspecialchars($userData['company_name']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="bio">Bio</label>
                        <textarea id="bio" name="bio" rows="3" placeholder="Tell us about yourself and your company..."><?php echo isset($userData['bio']) ? htmlspecialchars($userData['bio']) : ''; ?></textarea>
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
                            <input type="checkbox" id="ideaUpdates" name="notifications[ideas]" value="1" <?php echo isset($userData['idea_updates']) && $userData['idea_updates'] ? 'checked' : ''; ?>>
                            <label for="ideaUpdates">Idea Updates</label>
                        </div>
                        
                        <div class="form-group checkbox-group">
                            <input type="checkbox" id="initiativeAlerts" name="notifications[initiatives]" value="1" <?php echo isset($userData['initiative_alerts']) && $userData['initiative_alerts'] ? 'checked' : ''; ?>>
                            <label for="initiativeAlerts">Initiative Alerts</label>
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
                                    <p class="help-text">Anyone can see your profile, ideas, and company information</p>
                                </div>
                                
                                <div class="radio-option">
                                    <div class="radio-header">
                                        <input type="radio" id="privacyLimited" name="privacyLevel" value="limited" <?php echo (isset($userData['privacy_level']) && $userData['privacy_level'] == 'limited') ? 'checked' : ''; ?>>
                                        <label for="privacyLimited">Limited Profile</label>
                                    </div>
                                    <p class="help-text">Only registered users can see your profile and ideas</p>
                                </div>
                                
                                <div class="radio-option">
                                    <div class="radio-header">
                                        <input type="radio" id="privacyPrivate" name="privacyLevel" value="private" <?php echo (isset($userData['privacy_level']) && $userData['privacy_level'] == 'private') ? 'checked' : ''; ?>>
                                        <label for="privacyPrivate">Private Profile</label>
                                    </div>
                                    <p class="help-text">Only approved connections can see your ideas</p>
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

    <!-- Express Interest Modal -->
    <div class="modal" id="expressInterestModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Express Interest</h3>
                <button class="modal-close"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <div id="initiativeDetails" class="interest-initiative-details">
                    <!-- Initiative details will be dynamically inserted here -->
                </div>
                
                <form id="expressInterestForm" class="interest-form" action="../../includes/express_interest.php" method="POST">
                    <input type="hidden" name="initiative_id" id="interestInitiativeId">
                    <input type="hidden" name="user_id" value="<?php echo $_SESSION['user_id']; ?>">
                    
                    <div class="interest-form-group">
                        <label for="interestProposal">Briefly describe how you can contribute to this initiative</label>
                        <textarea id="interestProposal" name="proposal" rows="4" placeholder="Describe your proposed approach and how your expertise aligns with this initiative..." required></textarea>
                    </div>
                    
                    <div class="interest-form-group">
                        <label for="interestIdea">Select a relevant idea from your portfolio (optional)</label>
                        <select id="interestIdea" name="idea_id">
                            <option value="">Select an idea</option>
                            <?php foreach ($userIdeas as $idea): ?>
                            <option value="<?php echo $idea['id']; ?>"><?php echo htmlspecialchars($idea['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="interest-form-actions">
                        <button type="submit" class="action-btn primary-btn">Submit Interest</button>
                        <button type="button" class="action-btn secondary-btn modal-cancel">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Language dropdown functionality
        document.addEventListener("DOMContentLoaded", function() {
            const langDropdown = document.querySelector(".language-dropdown");
            const langBtn = document.querySelector(".lang-btn");
            
            // Initialize interested initiatives from database and localStorage
            window.interestedInitiatives = <?php echo !empty($formattedInterests) ? json_encode($formattedInterests) : '{}'; ?>;
            
            // Also check localStorage in case there are any pending interests not yet in the database
            const storedInterests = JSON.parse(localStorage.getItem('interestedInitiatives') || '{}');
            for (const [initiativeId, interest] of Object.entries(storedInterests)) {
                if (!window.interestedInitiatives[initiativeId]) {
                    window.interestedInitiatives[initiativeId] = interest;
                }
            }
            
            // Update UI to show which initiatives the user has already expressed interest in
            document.querySelectorAll('.btn-express-interest').forEach(button => {
                const initiativeId = button.dataset.id;
                if (window.interestedInitiatives[initiativeId]) {
                    button.classList.add('interested');
                    button.innerHTML = '<i class="fas fa-check"></i> Interested';
                }
            });
            
            // Update the count of interested initiatives
            updateInterestedCount();
            
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
            
            // Create gradient fills for datasets
            const ideaViewsGradient = ctx.createLinearGradient(0, 0, 0, 400);
            ideaViewsGradient.addColorStop(0, 'rgba(255, 229, 53, 0.6)');
            ideaViewsGradient.addColorStop(1, 'rgba(255, 229, 53, 0.1)');
            
            const connectionsGradient = ctx.createLinearGradient(0, 0, 0, 400);
            connectionsGradient.addColorStop(0, 'rgba(23, 162, 184, 0.6)');
            connectionsGradient.addColorStop(1, 'rgba(23, 162, 184, 0.1)');
            
            const conversionGradient = ctx.createLinearGradient(0, 0, 0, 400);
            conversionGradient.addColorStop(0, 'rgba(40, 167, 69, 0.6)');
            conversionGradient.addColorStop(1, 'rgba(40, 167, 69, 0.1)');
            
            Chart.defaults.font.family = "'Poppins', 'Helvetica', 'Arial', sans-serif";
            
            const performanceChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [
                        {
                            label: 'Idea Views',
                            data: [5, 10, 15, 12, 18, 22, 25],
                            borderColor: 'rgba(255, 229, 53, 1)',
                            backgroundColor: ideaViewsGradient,
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
                            label: 'Connections',
                            data: [1, 2, 3, 2, 4, 3, 5],
                            borderColor: 'rgba(23, 162, 184, 1)',
                            backgroundColor: connectionsGradient,
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
                            label: 'Conversion Rate %',
                            data: [10, 15, 12, 18, 15, 20, 22],
                            borderColor: 'rgba(40, 167, 69, 1)',
                            backgroundColor: conversionGradient,
                            borderWidth: 3,
                            tension: 0.4,
                            fill: true,
                            pointBackgroundColor: '#fff',
                            pointBorderColor: 'rgba(40, 167, 69, 1)',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            hidden: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            enabled: true,
                            mode: 'index',
                            intersect: false,
                            backgroundColor: 'rgba(255, 255, 255, 0.9)',
                            titleColor: '#333',
                            bodyColor: '#666',
                            borderColor: 'rgba(0, 0, 0, 0.1)',
                            borderWidth: 1,
                            padding: 10,
                            boxPadding: 5,
                            usePointStyle: true,
                            bodyFont: {
                                size: 12
                            },
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            callbacks: {
                                labelPointStyle: function(context) {
                                    return {
                                        pointStyle: 'circle',
                                        rotation: 0
                                    };
                                }
                            }
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
            
            // Add functionality to chart tabs
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
                        performanceChart.data.datasets[0].data = [5, 10, 15, 12, 18, 22, 25];
                        performanceChart.data.datasets[1].data = [1, 2, 3, 2, 4, 3, 5];
                        performanceChart.data.datasets[2].data = [10, 15, 12, 18, 15, 20, 22];
                    } else if (period === 'month') {
                        performanceChart.data.labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
                        performanceChart.data.datasets[0].data = [20, 35, 45, 60];
                        performanceChart.data.datasets[1].data = [4, 7, 9, 12];
                        performanceChart.data.datasets[2].data = [15, 18, 20, 25];
                    } else if (period === 'year') {
                        performanceChart.data.labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                        performanceChart.data.datasets[0].data = [10, 15, 20, 25, 30, 35, 40, 45, 50, 55, 60, 70];
                        performanceChart.data.datasets[1].data = [2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 14];
                        performanceChart.data.datasets[2].data = [8, 10, 12, 14, 16, 18, 19, 20, 22, 23, 25, 28];
                    }
                    
                    performanceChart.update();
                });
                });
            });
            
        // Add script for delete functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Handle delete idea
            const deleteModal = document.getElementById('deleteModal');
            const closeModal = document.querySelector('#deleteModal .close-modal');
            const cancelDelete = document.getElementById('cancelDelete');
            const confirmDelete = document.getElementById('confirmDelete');
            let ideaToDelete = null;
            
            // Show delete confirmation modal
            document.querySelectorAll('.delete-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    ideaToDelete = this.dataset.id;
                    deleteModal.style.display = 'block';
                });
            });
            
            // Close modal when clicking the X or Cancel
            closeModal.addEventListener('click', () => deleteModal.style.display = 'none');
            cancelDelete.addEventListener('click', () => deleteModal.style.display = 'none');
            
            // Handle confirm delete
            confirmDelete.addEventListener('click', function() {
                if (ideaToDelete) {
                    window.location.href = `../delete_idea.php?id=${ideaToDelete}`;
                }
            });
            
            // Close modal if clicking outside of it
            window.addEventListener('click', function(event) {
                if (event.target == deleteModal) {
                    deleteModal.style.display = 'none';
                }
            });
        });

        // Add a real-time activity refresh system
        const refreshActivitiesBtn = document.getElementById('refreshActivities');
        const activityContainer = document.getElementById('activityContainer');
        
        if (refreshActivitiesBtn) {
            refreshActivitiesBtn.addEventListener('click', function() {
                // Add loading animation
                this.classList.add('loading');
                
                // Create a fetch request to get updated activities
                fetch('../../includes/get_activities.php')
                    .then(response => response.json())
                    .then(data => {
                        // Clear loading state
                        refreshActivitiesBtn.classList.remove('loading');
                        
                        if (data.success && data.activities.length > 0) {
                            // Clear existing activities and add new ones
                            activityContainer.innerHTML = '';
                            
                            data.activities.forEach(activity => {
                                const activityItem = document.createElement('div');
                                activityItem.className = 'activity-item';
                                
                                activityItem.innerHTML = `
                                    <div class="activity-icon ${activity.type}">
                                        <i class="fas ${activity.icon}"></i>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-text">${activity.title}</div>
                                        <div class="activity-time">
                                            <i class="far fa-clock"></i>
                                            ${activity.time}
                                        </div>
                                    </div>
                                `;
                                
                                activityContainer.appendChild(activityItem);
                            });
                            
                            // Create toast notification
                            createToast('Activities updated', 'Your activities have been refreshed', 'success');
                        } else {
                            // Show empty state if no activities
                            activityContainer.innerHTML = `
                                <div class="activity-empty">
                                    <i class="fas fa-info-circle"></i> No recent activities yet
                                </div>
                            `;
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching activities:', error);
                        refreshActivitiesBtn.classList.remove('loading');
                        createToast('Error', 'Could not refresh activities', 'error');
                    });
            });
        }
        
        // Function to create toast notifications
        function createToast(title, message, type = 'success') {
            const toastContainer = document.querySelector('.toast-container');
            if (!toastContainer) return;
            
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            
            const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
            
            toast.innerHTML = `
                <div class="toast-icon"><i class="fas fa-${icon}"></i></div>
                <div class="toast-content">
                    <div class="toast-title">${title}</div>
                    <div class="toast-message">${message}</div>
                </div>
                <div class="toast-close"><i class="fas fa-times"></i></div>
            `;
            
            toastContainer.appendChild(toast);
            
            // Add event listener to close button
            toast.querySelector('.toast-close').addEventListener('click', function() {
                toast.remove();
            });
            
            // Auto-hide toast after 3 seconds
            setTimeout(() => {
                toast.classList.add('toast-hide');
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }, 3000);
        }

        // Modal functionality
        const modals = document.querySelectorAll('.modal');
        const editProfileBtn = document.getElementById('editProfileBtn');
        const accountSettingsBtn = document.getElementById('accountSettingsBtn');
        const modalCloseButtons = document.querySelectorAll('.modal-close, .modal-cancel');
        
        // Open Edit Profile modal
        if (editProfileBtn) {
            editProfileBtn.addEventListener('click', function() {
                document.getElementById('editProfileModal').classList.add('active');
                document.body.classList.add('modal-open');
            });
        }
        
        // Open Account Settings modal
        if (accountSettingsBtn) {
            accountSettingsBtn.addEventListener('click', function() {
                document.getElementById('accountSettingsModal').classList.add('active');
                document.body.classList.add('modal-open');
            });
        }
        
        // Close modals
        modalCloseButtons.forEach(button => {
            button.addEventListener('click', function() {
                modals.forEach(modal => {
                    modal.classList.remove('active');
                });
                document.body.classList.remove('modal-open');
            });
        });
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.classList.remove('active');
                    document.body.classList.remove('modal-open');
                }
            });
        });
        
        // Form submissions with AJAX
        const editProfileForm = document.getElementById('editProfileForm');
        const accountSettingsForm = document.getElementById('accountSettingsForm');
        
        if (editProfileForm) {
            editProfileForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                fetch('../../includes/update_profile.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        createToast('Success', data.message || 'Profile updated successfully', 'success');
                        
                        // Close the modal
                        document.getElementById('editProfileModal').classList.remove('active');
                        document.body.classList.remove('modal-open');
                        
                        // Refresh the page after a delay to show updated information
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        createToast('Error', data.message || 'Could not update profile', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error updating profile:', error);
                    createToast('Error', 'An unexpected error occurred', 'error');
                });
            });
        }
        
        if (accountSettingsForm) {
            accountSettingsForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                fetch('../../includes/update_settings.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        createToast('Success', data.message || 'Settings updated successfully', 'success');
                        
                        // Close the modal
                        document.getElementById('accountSettingsModal').classList.remove('active');
                        document.body.classList.remove('modal-open');
                        
                        // Refresh the page after a delay to show updated information
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        createToast('Error', data.message || 'Could not update settings', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error updating settings:', error);
                    createToast('Error', 'An unexpected error occurred', 'error');
                });
            });
        }

        // Browse Government Initiatives functionality
        const initiativeSearch = document.getElementById('initiativeSearch');
        const initiativeDeptFilter = document.getElementById('initiativeDeptFilter');
        const initiativesList = document.getElementById('initiativesList');
        const initiativeCards = document.querySelectorAll('.initiative-card');
        const initiativeFilterBtns = document.querySelectorAll('.initiative-filter-btn');
        const initiativeContainer = document.querySelector('.initiative-container');
        const interestedInitiativesCount = document.getElementById('interestedInitiativesCount');
        
        // Update the count of interested initiatives
        function updateInterestedCount() {
            const count = Object.keys(window.interestedInitiatives || {}).length;
            document.getElementById('interestedInitiativesCount').textContent = count;
        }
        
        // Express Interest button click
        document.querySelectorAll('.btn-express-interest').forEach(button => {
            button.addEventListener('click', function() {
                const initiativeId = this.dataset.id;
                const initiativeTitle = this.dataset.title;
                
                // If already interested, we can toggle it off
                if (window.interestedInitiatives && window.interestedInitiatives[initiativeId]) {
                    // Create form data for removal
                    const formData = new FormData();
                    formData.append('initiative_id', initiativeId);
                    formData.append('user_id', <?php echo $_SESSION['user_id']; ?>);
                    formData.append('action', 'remove');
                    
                    // Show loading state
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                    
                    // Submit the data using fetch API
                    fetch('../../includes/express_interest.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        // Update UI
                        delete window.interestedInitiatives[initiativeId];
                        this.classList.remove('interested');
                        this.innerHTML = '<i class="fas fa-handshake"></i> Express Interest';
                        
                        // Update local storage
                        localStorage.setItem('interestedInitiatives', JSON.stringify(window.interestedInitiatives));
                        
                        // Show toast
                        createToast('Interest Removed', `You're no longer interested in "${initiativeTitle}"`, 'info');
                        
                        // Update the count
                        updateInterestedCount();
                        
                        // Record activity
                        recordInterestActivity(initiativeId, initiativeTitle, false);
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        // Show error notification
                        createToast('Error', 'Failed to remove your interest. Please try again.', 'error');
                        this.innerHTML = '<i class="fas fa-check"></i> Interested';
                    });
                    return;
                }
                
                // Show the express interest modal
                const modal = document.getElementById('expressInterestModal');
                modal.classList.add('active');
                document.body.classList.add('modal-open');
                
                // Populate modal with initiative details
                const initiativeDetails = document.getElementById('initiativeDetails');
                const initiativeCard = this.closest('.initiative-card').cloneNode(true);
                
                // Remove buttons from the cloned card
                initiativeCard.querySelector('.initiative-card-footer').remove();
                
                // Show all the hidden elements in the cloned card
                const description = initiativeCard.querySelector('.initiative-description');
                description.style.display = 'block';
                description.classList.add('expanded');
                
                const requirements = initiativeCard.querySelector('.initiative-requirements');
                requirements.style.display = 'block';
                
                initiativeDetails.innerHTML = '';
                initiativeDetails.appendChild(initiativeCard);
                
                // Set initiative ID in form
                document.getElementById('interestInitiativeId').value = initiativeId;
            });
        });
        
        // Handle express interest form submission
        const expressInterestForm = document.getElementById('expressInterestForm');
        expressInterestForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const initiativeId = document.getElementById('interestInitiativeId').value;
            const proposal = document.getElementById('interestProposal').value;
            const ideaId = document.getElementById('interestIdea').value;
            const userId = <?php echo $_SESSION['user_id']; ?>;
            
            // Get initiative title from the button data-title attribute
            const initiativeButton = document.querySelector(`.btn-express-interest[data-id="${initiativeId}"]`);
            const initiativeTitle = initiativeButton.dataset.title;
            
            // Create form data
            const formData = new FormData();
            formData.append('initiative_id', initiativeId);
            formData.append('user_id', userId);
            formData.append('proposal', proposal);
            if (ideaId) {
                formData.append('idea_id', ideaId);
            }
            
            // Show loading state
            initiativeButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            // Submit the data using fetch API
            fetch('../../includes/express_interest.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // Update UI regardless of response (we'll handle errors on next page load)
                // In a real app, you'd parse the JSON response
                
                // Update button state
                initiativeButton.classList.add('interested');
                initiativeButton.innerHTML = '<i class="fas fa-check"></i> Interested';
                initiativeButton.closest('.initiative-card').classList.add('interest-success');
                
                // Also track locally to maintain UI state
                if (!window.interestedInitiatives) {
                    window.interestedInitiatives = {};
                }
                
                // Add to interested initiatives
                window.interestedInitiatives[initiativeId] = {
                    timestamp: new Date().toISOString(),
                    proposal: proposal,
                    ideaId: ideaId
                };
                
                // Store in localStorage as a backup
                localStorage.setItem('interestedInitiatives', JSON.stringify(window.interestedInitiatives));
                
                // Close the modal
                document.getElementById('expressInterestModal').classList.remove('active');
                document.body.classList.remove('modal-open');
                
                // Show toast notification
                createToast('Interest Expressed', `Your interest in "${initiativeTitle}" has been submitted`, 'success');
                
                // Update the count
                updateInterestedCount();
                
                // Record activity in UI
                recordInterestActivity(initiativeId, initiativeTitle, true);
                
                // Reset form
                expressInterestForm.reset();
                
                // Remove animation class after animation completes
                setTimeout(() => {
                    initiativeButton.closest('.initiative-card').classList.remove('interest-success');
                }, 500);
            })
            .catch(error => {
                console.error('Error:', error);
                // Show error notification
                createToast('Error', 'Failed to submit your interest. Please try again.', 'error');
                initiativeButton.innerHTML = '<i class="fas fa-handshake"></i> Express Interest';
            });
        });
        
        // Record interest activity
        function recordInterestActivity(initiativeId, initiativeTitle, isInterested) {
            // In a real application, this would send data to the server to record the activity
            // For this demo, we'll just update the UI
            
            // Create activity item
            const activityItem = document.createElement('div');
            activityItem.className = 'activity-item';
            
            if (isInterested) {
                activityItem.innerHTML = `
                    <div class="activity-icon connection">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-text">You expressed interest in "${initiativeTitle}"</div>
                        <div class="activity-time">
                            <i class="far fa-clock"></i>
                            Just now
                        </div>
                    </div>
                `;
            } else {
                activityItem.innerHTML = `
                    <div class="activity-icon update">
                        <i class="fas fa-undo"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-text">You withdrew interest from "${initiativeTitle}"</div>
                        <div class="activity-time">
                            <i class="far fa-clock"></i>
                            Just now
                        </div>
                    </div>
                `;
            }
            
            // Add to activity container
            const activityContainer = document.getElementById('activityContainer');
            
            // Remove empty state if it exists
            const emptyState = activityContainer.querySelector('.activity-empty');
            if (emptyState) {
                emptyState.remove();
            }
            
            // Add new activity at the top
            activityContainer.insertBefore(activityItem, activityContainer.firstChild);
        }
        
        // More Details button click
        document.querySelectorAll('.btn-details').forEach(button => {
            button.addEventListener('click', function() {
                const initiativeId = this.dataset.id;
                const description = document.getElementById(`description-${initiativeId}`);
                const requirements = document.getElementById(`requirements-${initiativeId}`);
                
                // Toggle description expansion
                description.classList.toggle('expanded');
                
                // Toggle requirements visibility
                if (requirements.style.display === 'none') {
                    requirements.style.display = 'block';
                    this.innerHTML = '<i class="fas fa-minus-circle"></i> Less Details';
                    this.classList.add('less');
                } else {
                    requirements.style.display = 'none';
                    this.innerHTML = '<i class="fas fa-info-circle"></i> More Details';
                    this.classList.remove('less');
                }
            });
        });
        
        // Search functionality
        initiativeSearch.addEventListener('input', filterInitiatives);
        initiativeDeptFilter.addEventListener('change', filterInitiatives);
        
        // Filter buttons
        initiativeFilterBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                // Remove active class from all buttons
                initiativeFilterBtns.forEach(b => b.classList.remove('active'));
                
                // Add active class to clicked button
                this.classList.add('active');
                
                // Apply filter
                filterInitiatives();
            });
        });
        
        function filterInitiatives() {
            const searchTerm = initiativeSearch.value.toLowerCase();
            const department = initiativeDeptFilter.value;
            const filterType = document.querySelector('.initiative-filter-btn.active').dataset.filter;
            
            let visibleCount = 0;
            let matchingCount = 0;
            
            initiativeCards.forEach(card => {
                const cardTitle = card.querySelector('.initiative-card-title').textContent.toLowerCase();
                const cardDept = card.dataset.department;
                const cardMatch = parseInt(card.dataset.match);
                const cardId = card.dataset.id;
                
                // Check if matches search term
                const matchesSearch = cardTitle.includes(searchTerm);
                
                // Check if matches department filter
                const matchesDept = department === '' || cardDept === department;
                
                // Check if matches type filter
                let matchesTypeFilter = true;
                if (filterType === 'match') {
                    matchesTypeFilter = cardMatch >= 70; // Show only high matches
                } else if (filterType === 'deadline') {
                    // This would normally check the deadline date
                    // For demo purposes, we're just checking if the deadline contains "Dec"
                    const deadline = card.querySelector('.date-value:last-child').textContent;
                    matchesTypeFilter = deadline.includes('Dec');
                } else if (filterType === 'interested') {
                    matchesTypeFilter = window.interestedInitiatives[cardId] !== undefined;
                }
                
                // Only count and show if all filters match
                const visible = matchesSearch && matchesDept && matchesTypeFilter;
                card.style.display = visible ? 'block' : 'none';
                
                if (visible) {
                    visibleCount++;
                }
                
                // Count high matches for the stats
                if (cardMatch >= 75) {
                    matchingCount++;
                }
            });
            
            // Update the matching initiatives count
            document.getElementById('matchingInitiativesCount').textContent = matchingCount;
            
            // Show empty state if no results
            let emptyState = initiativeContainer.querySelector('.initiative-empty');
            
            if (visibleCount === 0) {
                // If empty state doesn't exist, create it
                if (!emptyState) {
                    emptyState = document.createElement('div');
                    emptyState.className = 'initiative-empty';
                    emptyState.innerHTML = `
                        <div class="empty-icon"><i class="fas fa-search"></i></div>
                        <h4>No matching initiatives found</h4>
                        <p>Try adjusting your search criteria or filters</p>
                        <button class="action-btn primary-btn" id="resetFilters">
                            <i class="fas fa-redo"></i> Reset Filters
                        </button>
                    `;
                    initiativesList.appendChild(emptyState);
                    
                    // Add reset filters functionality
                    document.getElementById('resetFilters').addEventListener('click', function() {
                        initiativeSearch.value = '';
                        initiativeDeptFilter.value = '';
                        document.querySelector('.initiative-filter-btn[data-filter="all"]').click();
                        filterInitiatives();
                    });
                }
            } else if (emptyState) {
                // Remove empty state if there are results
                emptyState.remove();
            }
        }
    </script>
</body>
</html> 