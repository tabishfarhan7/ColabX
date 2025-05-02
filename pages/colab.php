<?php
// Initialize the session
session_start();

// Include database connection
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Check if user is logged in and get user type
$isLoggedIn = isset($_SESSION['user_id']);
$userType = $isLoggedIn ? $_SESSION['user_type'] : '';

// Get government initiatives from database
$dbInitiatives = [];
$sql = "SELECT i.*, u.full_name, u.company_name, u.govt_id FROM initiatives i 
        JOIN users u ON i.user_id = u.id 
        WHERE i.status = 'active' 
        ORDER BY i.created_at DESC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $dbInitiatives[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'department' => $row['department'],
            'organization' => $row['company_name'] ?: 'Ministry of ' . $row['department'],
            'posted_date' => date('Y-m-d', strtotime($row['created_at'])),
            'deadline' => $row['end_date'],
            'match_score' => rand(65, 95), // You might implement a real matching algorithm
            'description' => $row['description'],
            'requirements' => $row['objectives']
        ];
    }
}

// Define the original mock data
$mockInitiatives = [
    [
        'id' => 1000, // Adding a large ID to avoid conflicts with real data
        'title' => 'Smart City Development Program',
        'department' => 'Urban Development',
        'organization' => 'Ministry of Urban Development',
        'posted_date' => '2023-10-10',
        'deadline' => '2027-12-31',
        'match_score' => 92,
        'description' => 'We are looking for innovative solutions to transform urban areas into smart cities with integrated technology for better resource management, improved quality of life, and sustainable development. Projects may include IoT sensors, data analytics, energy efficiency, or smart transportation systems.',
        'requirements' => 'Technology startups, software companies, IoT solution providers with proven track record.'
    ],
    [
        'id' => 1001,
        'title' => 'Clean Energy Innovation Challenge',
        'department' => 'Energy',
        'organization' => 'Department of Energy',
        'posted_date' => '2023-11-05',
        'deadline' => '2027-01-15',
        'match_score' => 85,
        'description' => 'This initiative aims to develop and implement innovative renewable energy solutions to reduce carbon emissions. We are seeking proposals for solar, wind, hydro, or other clean energy technologies that can be scaled for mass adoption.',
        'requirements' => 'Renewable energy companies, research institutions, and innovative startups with demonstrable experience in the energy sector.'
    ],
    [
        'id' => 1002,
        'title' => 'Digital Governance Transformation',
        'department' => 'IT & Communication',
        'organization' => 'Ministry of Digital Affairs',
        'posted_date' => '2023-11-12',
        'deadline' => '2027-02-28',
        'match_score' => 78,
        'description' => 'We are seeking digital solutions to simplify government services and improve citizen engagement. Proposed solutions should focus on e-governance, citizen services applications, or process automation systems.',
        'requirements' => 'Software development companies, system integrators, and digital consultancies with experience in government technology projects.'
    ],
    [
        'id' => 1003,
        'title' => 'Healthcare Innovation Program',
        'department' => 'Health',
        'organization' => 'Ministry of Health',
        'posted_date' => '2023-11-20',
        'deadline' => '2027-03-15',
        'match_score' => 73,
        'description' => 'This program focuses on technological innovations in healthcare delivery, telemedicine, health information systems, and medical devices. We are looking for solutions that can improve healthcare access and quality.',
        'requirements' => 'Healthcare technology companies, medical device manufacturers, and health informatics specialists.'
    ],
    [
        'id' => 1004,
        'title' => 'Agricultural Modernization Initiative',
        'department' => 'Agriculture',
        'organization' => 'Department of Agriculture',
        'posted_date' => '2023-11-25',
        'deadline' => '2027-02-28',
        'match_score' => 68,
        'description' => 'We are seeking innovative solutions to modernize agricultural practices, including precision farming, crop monitoring, supply chain optimization, and sustainable farming technologies.',
        'requirements' => 'AgriTech companies, farm management solution providers, and agricultural consultants with proven innovations.'
    ]
];

// Combine real database initiatives with mock initiatives
$govtInitiatives = array_merge($dbInitiatives, $mockInitiatives);

// Check if user is an entrepreneur to fetch expressed interests
$userInterests = [];
if ($isLoggedIn && $userType === 'entrepreneur') {
    $userInterestsQuery = "SELECT initiative_id FROM initiative_interests WHERE user_id = ?";
    $userInterestsStmt = $conn->prepare($userInterestsQuery);
    $userInterestsStmt->bind_param("i", $_SESSION['user_id']);
    $userInterestsStmt->execute();
    $result = $userInterestsStmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $userInterests[] = $row['initiative_id'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ColabX - Government Initiatives</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../css/colab.css">
    <style>
        /* Initiatives Section Styles */
        .initiatives-section {
            padding: 30px;
            margin-top: 80px;
            animation: fadeIn 0.5s ease-in-out;
        }
        
        /* Preloader styles */
        .preloader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.5s ease-out;
        }
        
        .preloader.hidden {
            opacity: 0;
            pointer-events: none;
        }
        
        .loader {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .loader-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 4px solid #FFE535;
            border-top-color: transparent;
            margin: 0 5px;
            animation: spin 1s linear infinite;
        }
        
        .loader-text {
            margin-top: 15px;
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        
        .loader-text span {
            color: #FFE535;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .initiatives-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .initiatives-title {
            font-size: 1.8rem;
            margin-bottom: 10px;
            position: relative;
            font-weight: 600;
        }
        
        .initiatives-title::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -5px;
            height: 3px;
            width: 60px;
            background-color: #FFE535;
        }
        
        .initiatives-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .initiative-search {
            position: relative;
            width: 260px;
        }
        
        .initiative-search i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            font-size: 14px;
        }
        
        .initiative-search input {
            width: 100%;
            padding: 10px 10px 10px 35px;
            border: 1px solid #ddd;
            border-radius: 30px;
            font-size: 0.9rem;
            transition: all 0.3s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.03);
        }
        
        .initiative-search input:focus {
            border-color: #FFE535;
            box-shadow: 0 2px 8px rgba(255, 229, 53, 0.15);
            outline: none;
        }
        
        .initiative-filter {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 30px;
            background-color: white;
            font-size: 0.9rem;
            min-width: 180px;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.03);
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23555' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: calc(100% - 15px) center;
            padding-right: 35px;
        }
        
        .initiative-filter:focus {
            border-color: #FFE535;
            box-shadow: 0 2px 8px rgba(255, 229, 53, 0.15);
            outline: none;
        }
        
        .initiatives-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        
        .initiative-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            transition: all 0.3s ease;
            border-top: 4px solid #FFE535;
        }
        
        .initiative-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.12);
        }
        
        .initiative-card-header {
            padding: 20px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .initiative-card-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }
        
        .initiative-card-dept {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 3px;
        }
        
        .initiative-card-org {
            font-size: 0.85rem;
            color: #888;
        }
        
        .initiative-dates {
            display: flex;
            padding: 15px 20px;
            background-color: #f9f9f9;
            justify-content: space-between;
        }
        
        .date-item {
            text-align: center;
        }
        
        .date-label {
            font-size: 0.75rem;
            color: #777;
            margin-bottom: 3px;
        }
        
        .date-value {
            font-size: 0.9rem;
            color: #333;
            font-weight: 500;
        }
        
        .initiative-description {
            padding: 20px;
            color: #555;
            font-size: 0.9rem;
            line-height: 1.5;
            height: 100px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .initiative-description.expanded {
            height: auto;
        }
        
        .initiative-requirements {
            padding: 0 20px 15px;
            color: #555;
            font-size: 0.85rem;
            line-height: 1.5;
        }
        
        .initiative-card-footer {
            padding: 15px 20px;
            border-top: 1px solid #f0f0f0;
        }
        
        .initiative-buttons {
            display: flex;
            justify-content: space-between;
        }
        
        .btn-express-interest, .btn-like, .btn-details {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }
        
        .btn-express-interest, .btn-like {
            background-color: #FFE535;
            color: #333;
        }
        
        .btn-express-interest:hover, .btn-like:hover {
            background-color: #FFD700;
            transform: translateY(-2px);
        }
        
        .btn-express-interest.interested, .btn-like.liked {
            background-color: #28a745;
            color: white;
        }
        
        .btn-express-interest.interested:hover, .btn-like.liked:hover {
            background-color: #218838;
        }
        
        .btn-details {
            background-color: #f0f0f0;
            color: #333;
        }
        
        .btn-details:hover {
            background-color: #e0e0e0;
            transform: translateY(-2px);
        }
        
        .btn-details.less {
            background-color: #e2e2e2;
        }
        
        /* Toast Notifications Styling */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 350px;
        }
        
        .toast {
            display: flex;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            margin-bottom: 10px;
            animation: slideInRight 0.3s ease forwards, fadeOut 0.3s ease 4.7s forwards;
            opacity: 0;
            transform: translateX(100%);
            padding: 0;
            min-width: 300px;
            border-left: 4px solid #ccc;
        }
        
        @keyframes slideInRight {
            to { opacity: 1; transform: translateX(0); }
        }
        
        @keyframes fadeOut {
            to { opacity: 0; transform: translateX(10px); }
        }
        
        .toast-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            font-size: 1.5rem;
            padding: 15px;
        }
        
        .toast-content {
            flex: 1;
            padding: 15px 10px;
        }
        
        .toast-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }
        
        .toast-message {
            font-size: 0.9rem;
            color: #666;
            line-height: 1.4;
        }
        
        .toast-close {
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            color: #999;
            padding: 0 15px;
            align-self: flex-start;
            margin-top: 10px;
            transition: color 0.2s ease;
        }
        
        .toast-close:hover {
            color: #333;
        }
        
        /* Toast Types */
        .toast-success {
            border-left-color: #28a745;
        }
        
        .toast-success .toast-icon {
            color: #28a745;
            background-color: rgba(40, 167, 69, 0.1);
        }
        
        .toast-error {
            border-left-color: #dc3545;
        }
        
        .toast-error .toast-icon {
            color: #dc3545;
            background-color: rgba(220, 53, 69, 0.1);
        }
        
        .toast-warning {
            border-left-color: #ffc107;
        }
        
        .toast-warning .toast-icon {
            color: #ffc107;
            background-color: rgba(255, 193, 7, 0.1);
        }
        
        .toast-info {
            border-left-color: #17a2b8;
        }
        
        .toast-info .toast-icon {
            color: #17a2b8;
            background-color: rgba(23, 162, 184, 0.1);
        }
        
        /* Express Interest Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background-color: white;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.2);
            animation: fadeUp 0.3s ease;
        }
        
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .modal-header h3 {
            margin: 0;
            font-size: 1.5rem;
            color: #333;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            color: #999;
            transition: color 0.3s ease;
        }
        
        .modal-close:hover {
            color: #333;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .interest-initiative-details {
            margin-bottom: 20px;
        }
        
        .interest-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .interest-form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .interest-form-group label {
            font-size: 0.9rem;
            color: #555;
        }
        
        .interest-form-group textarea,
        .interest-form-group select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        
        .interest-form-group textarea:focus,
        .interest-form-group select:focus {
            border-color: #FFE535;
            outline: none;
        }
        
        .interest-form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 10px;
        }
        
        /* Entrepreneur Projects Section */
        .projects-section {
            padding: 2rem;
            margin-bottom: 3rem;
            background-color: #f8f9fa;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .projects-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        
        .projects-title {
            font-size: 1.8rem;
            color: #333;
            margin: 0;
            font-weight: 600;
        }
        
        .projects-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .project-search {
            position: relative;
            width: 250px;
        }
        
        .project-search i {
            position: absolute;
            left: 10px;
            top: 10px;
            color: #666;
        }
        
        .project-search input {
            width: 100%;
            padding: 8px 10px 8px 35px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        
        .project-filter {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: white;
            font-size: 0.9rem;
            min-width: 150px;
        }
        
        .projects-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }
        
        .project-card {
            display: flex;
            flex-direction: column;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
        }
        
        .project-card-header {
            padding: 1.2rem 1.5rem;
            border-bottom: 1px solid #eee;
            background-color: #f7f9fc;
        }
        
        .project-card-title {
            margin: 0 0 0.5rem 0;
            font-size: 1.2rem;
            color: #333;
            font-weight: 600;
        }
        
        .project-card-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .project-card-sector {
            background-color: #e9ecef;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            color: #495057;
        }
        
        .project-status {
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-pending {
            background-color: #ffeeba;
            color: #856404;
        }
        
        .status-review {
            background-color: #bee5eb;
            color: #0c5460;
        }
        
        .status-approved {
            background-color: #c3e6cb;
            color: #155724;
        }
        
        .status-rejected {
            background-color: #f5c6cb;
            color: #721c24;
        }
        
        .project-card-body {
            padding: 1.5rem;
            flex-grow: 1;
        }
        
        .project-description {
            margin: 0 0 1rem 0;
            color: #6c757d;
            line-height: 1.5;
        }
        
        .project-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-top: 1rem;
        }
        
        .project-detail-item {
            display: flex;
            flex-direction: column;
        }
        
        .detail-label {
            font-size: 0.8rem;
            color: #6c757d;
            margin-bottom: 3px;
        }
        
        .detail-value {
            font-size: 0.9rem;
            color: #333;
            font-weight: 500;
        }
        
        .project-card-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f8f9fa;
        }
        
        .project-entrepreneur {
            display: flex;
            flex-direction: column;
            font-size: 0.9rem;
        }
        
        .company-name {
            color: #6c757d;
            font-size: 0.8rem;
        }
        
        .project-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn-connect, .btn-like-project {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: background-color 0.2s;
        }
        
        .btn-connect {
            background-color: #007bff;
            color: white;
        }
        
        .btn-connect:hover {
            background-color: #0069d9;
        }
        
        .btn-like-project {
            background-color: #f8f9fa;
            color: #6c757d;
            border: 1px solid #ddd;
        }
        
        .btn-like-project:hover {
            background-color: #e2e6ea;
        }
        
        .btn-like-project.liked {
            background-color: #fce8e8;
            color: #e74c3c;
            border-color: #fad2d2;
        }
        
        .no-projects {
            grid-column: 1 / -1;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 3rem;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .empty-icon {
            font-size: 3rem;
            color: #dee2e6;
            margin-bottom: 1rem;
        }
        
        .no-projects h3 {
            margin: 0 0 0.5rem 0;
            color: #495057;
        }
        
        .no-projects p {
            margin: 0 0 1.5rem 0;
            color: #6c757d;
        }
        
        /* Toast Notifications */
        .toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .toast {
            display: flex;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.2);
            padding: 15px;
            width: 300px;
            opacity: 1;
            transition: opacity 0.3s;
        }
        
        .toast-icon {
            margin-right: 15px;
            font-size: 1.5rem;
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
            flex-grow: 1;
        }
        
        .toast-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .toast-message {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .toast-close {
            cursor: pointer;
            font-size: 0.9rem;
            color: #adb5bd;
        }
        
        .toast-close:hover {
            color: #6c757d;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .initiatives-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .initiatives-actions {
                width: 100%;
                flex-direction: column;
                gap: 10px;
            }
            
            .initiative-search {
                width: 100%;
            }
            
            .initiative-filter {
                width: 100%;
            }
            
            .projects-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .projects-actions {
                width: 100%;
            }
            
            .project-search {
                width: 100%;
            }
            
            .project-filter {
                flex-grow: 1;
            }
            
            .projects-container {
                grid-template-columns: 1fr;
            }
            
            .project-card-footer {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .project-buttons {
                width: 100%;
                justify-content: space-between;
            }
        }
    </style>
</head>

<body>

     <!-- Preloader  -->
    <div class="preloader" id="preloader"> 
        <div class="loader">
            <div class="loader-circle"></div>
            <div class="loader-circle"></div>
            <div class="loader-text">Colab<span>X</span></div>
        </div>
    </div>  
    <script>
        // Immediately begin removing the preloader
        document.getElementById('preloader').style.opacity = '0';
        document.getElementById('preloader').style.pointerEvents = 'none';
        setTimeout(function() {
            document.getElementById('preloader').style.display = 'none';
        }, 1000);
    </script>

    <!-- Header -->
    <header>
    <nav class="navbar flex">
    <a href="../index.php" class="logo">
        Colab<span>X</span>
    </a>
    <ul class="navlist flex">
        <li><a href="../index.php" class="link" data-key="home">Home</a></li>
                <li><a href="colab.php" class="link" data-key="project">Initiatives</a></li>
        <li><a href="innovation.php" class="link" data-key="innovation">Innovation</a></li>
        <li><a href="about.php" class="link" data-key="community">About Us</a></li>
    </ul>
    <div class="user-actions">
                <?php if (!$isLoggedIn): ?>
        <button class="btn register" data-key="register">Register</button>
        <button class="btn sign-in" data-key="signIn"><a href="login.php">Sign in</a></button>
                <?php else: ?>
                    <a href="dashboard/<?php echo ($userType === 'normal') ? 'user' : $userType; ?>_dashboard.php">
                        <button class="btn register" data-key="dashboard">Dashboard</button>
                    </a>
                    <a href="logout.php">
                        <button class="btn sign-in" data-key="signOut">Sign Out</button>
                    </a>
                <?php endif; ?>
    
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

    <!-- Government Initiatives Section -->
    <section class="initiatives-section">
        <div class="initiatives-header">
            <h2 class="initiatives-title">Government Initiatives</h2>
            <div class="initiatives-actions">
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
        
        <div class="initiatives-container">
            <?php foreach ($govtInitiatives as $initiative): ?>
            <div class="initiative-card" data-id="<?php echo $initiative['id']; ?>" data-department="<?php echo $initiative['department']; ?>">
                <div class="initiative-card-header">
                    <div>
                        <div class="initiative-card-title"><?php echo htmlspecialchars($initiative['title']); ?></div>
                        <div class="initiative-card-dept"><?php echo htmlspecialchars($initiative['department']); ?></div>
                        <div class="initiative-card-org"><?php echo htmlspecialchars($initiative['organization']); ?></div>
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
                        <?php if ($isLoggedIn && $userType === 'entrepreneur'): ?>
                            <button class="btn-express-interest <?php echo in_array($initiative['id'], $userInterests) ? 'interested' : ''; ?>" data-id="<?php echo $initiative['id']; ?>" data-title="<?php echo htmlspecialchars($initiative['title']); ?>">
                                <i class="fas <?php echo in_array($initiative['id'], $userInterests) ? 'fa-check' : 'fa-handshake'; ?>"></i> 
                                <?php echo in_array($initiative['id'], $userInterests) ? 'Interested' : 'Express Interest'; ?>
                            </button>
                        <?php elseif ($isLoggedIn): ?>
                            <button class="btn-like" data-id="<?php echo $initiative['id']; ?>" data-title="<?php echo htmlspecialchars($initiative['title']); ?>">
                                <i class="fas fa-thumbs-up"></i> Like
                            </button>
                        <?php endif; ?>
                        <button class="btn-details" data-id="<?php echo $initiative['id']; ?>">
                            <i class="fas fa-info-circle"></i> More Details
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    
    <!-- Entrepreneur Projects Section -->
    <section class="projects-section">
        <div class="projects-header">
            <h2 class="projects-title">Entrepreneur Projects</h2>
            <div class="projects-actions">
                <div class="project-search">
                    <i class="fas fa-search"></i>
                    <input type="text" id="project-search-input" placeholder="Search projects...">
                </div>
                <select class="project-filter" id="sector-filter">
                    <option value="">All Sectors</option>
                    <option value="Technology">Technology</option>
                    <option value="Healthcare">Healthcare</option>
                    <option value="Education">Education</option>
                    <option value="Finance">Finance</option>
                    <option value="Agriculture">Agriculture</option>
                    <option value="Energy">Energy</option>
                    <option value="Environment">Environment</option>
                    <option value="Manufacturing">Manufacturing</option>
                    <option value="Transportation">Transportation</option>
                    <option value="Other">Other</option>
                </select>
                <select class="project-filter" id="status-filter">
                    <option value="">All Statuses</option>
                    <option value="Pending">Pending</option>
                    <option value="Under Review">Under Review</option>
                    <option value="Approved">Approved</option>
                    <option value="Rejected">Rejected</option>
                </select>
            </div>
        </div>
        
        <div class="projects-container">
            <?php
            // Connect to database
            require_once('../includes/db_connect.php');
            
            // Check if database is connected
            if (!isset($conn) || $conn->connect_error) {
                echo '<div class="no-projects">
                    <div class="empty-icon"><i class="fas fa-database"></i></div>
                    <h3>Database Connection Error</h3>
                    <p>Could not connect to the database. Please try again later.</p>
                </div>';
            } else {
                // Fetch entrepreneur projects from ideas table
                $sql = "SELECT i.*, u.full_name, u.company_name 
                        FROM ideas i 
                        JOIN users u ON i.user_id = u.id 
                        WHERE i.status IN ('pending', 'under_review', 'approved')
                        ORDER BY i.created_at DESC
                        LIMIT 10";
                
                $result = $conn->query($sql);
                
                if ($result && $result->num_rows > 0) {
                    while ($project = $result->fetch_assoc()) {
                        // Get status class for styling
                        $statusClass = '';
                        switch ($project['status']) {
                            case 'pending':
                                $statusClass = 'status-pending';
                                break;
                            case 'under_review':
                                $statusClass = 'status-review';
                                break;
                            case 'approved':
                                $statusClass = 'status-approved';
                                break;
                            case 'rejected':
                                $statusClass = 'status-rejected';
                                break;
                        }
                        
                        // Format date
                        $postedDate = date('M d, Y', strtotime($project['created_at']));
                        
                        // Truncate description if too long
                        $shortDescription = strlen($project['description']) > 150 
                            ? substr($project['description'], 0, 150) . '...' 
                            : $project['description'];
                        
                        ?>
                        <div class="project-card" data-sector="<?php echo htmlspecialchars($project['sector']); ?>" data-status="<?php echo htmlspecialchars($project['status']); ?>">
                            <div class="project-card-header">
                                <h3 class="project-card-title"><?php echo htmlspecialchars($project['title']); ?></h3>
                                <div class="project-card-info">
                                    <span class="project-card-sector"><?php echo htmlspecialchars($project['sector']); ?></span>
                                    <span class="project-status <?php echo $statusClass; ?>"><?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($project['status']))); ?></span>
                                </div>
                            </div>
                            <div class="project-card-body">
                                <p class="project-description"><?php echo htmlspecialchars($shortDescription); ?></p>
                                <div class="project-details">
                                    <div class="project-detail-item">
                                        <span class="detail-label">Budget</span>
                                        <span class="detail-value"><?php echo isset($project['budget']) ? htmlspecialchars($project['budget']) : 'N/A'; ?></span>
                                    </div>
                                    <div class="project-detail-item">
                                        <span class="detail-label">Timeline</span>
                                        <span class="detail-value"><?php echo isset($project['timeline']) ? htmlspecialchars($project['timeline']) : 'N/A'; ?></span>
                                    </div>
                                    <div class="project-detail-item">
                                        <span class="detail-label">Technology</span>
                                        <span class="detail-value"><?php echo isset($project['technology_used']) ? htmlspecialchars($project['technology_used']) : 'N/A'; ?></span>
                                    </div>
                                    <div class="project-detail-item">
                                        <span class="detail-label">Posted</span>
                                        <span class="detail-value"><?php echo $postedDate; ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="project-card-footer">
                                <div class="project-entrepreneur">
                                    <span><?php echo htmlspecialchars($project['full_name']); ?></span>
                                    <?php if (!empty($project['company_name'])): ?>
                                        <span class="company-name"><?php echo htmlspecialchars($project['company_name']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="project-buttons">
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <a href="view_idea.php?id=<?php echo $project['id']; ?>" class="btn-connect">
                                            <i class="fas fa-eye"></i> View Details
                                        </a>
                                        <?php 
                                        // Check if this idea is already supported by the user
                                        $supportCheck = false;
                                        if (isset($_SESSION['user_id'])) {
                                            $supportQuery = "SELECT id FROM idea_supports WHERE idea_id = ? AND user_id = ?";
                                            $supportStmt = $conn->prepare($supportQuery);
                                            $supportStmt->bind_param("ii", $project['id'], $_SESSION['user_id']);
                                            $supportStmt->execute();
                                            $supportResult = $supportStmt->get_result();
                                            $supportCheck = ($supportResult->num_rows > 0);
                                        }
                                        ?>
                                        <button class="btn-like-project <?php echo $supportCheck ? 'liked' : ''; ?>" data-project-id="<?php echo $project['id']; ?>">
                                            <i class="<?php echo $supportCheck ? 'fas' : 'far'; ?> fa-heart"></i> <?php echo $supportCheck ? 'Supported' : 'Support'; ?>
                                        </button>
                                    <?php else: ?>
                                        <a href="login.php" class="btn-connect">
                                            <i class="fas fa-sign-in-alt"></i> Login to Connect
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    ?>
                    <div class="no-projects">
                        <div class="empty-icon"><i class="fas fa-clipboard-list"></i></div>
                        <h3>No Projects Available Yet</h3>
                        <p>Be the first to submit your entrepreneurial project or check back later for updates.</p>
                        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'entrepreneur'): ?>
                            <a href="dashboard/entrepreneur_dashboard.php" class="btn-connect">
                                <i class="fas fa-plus"></i> Submit Your Project
                            </a>
                        <?php elseif (isset($_SESSION['user_id'])): ?>
                            <a href="dashboard/<?php echo ($_SESSION['user_type'] === 'normal') ? 'user' : $_SESSION['user_type']; ?>_dashboard.php" class="btn-connect">
                                <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                            </a>
                        <?php elseif (!isset($_SESSION['user_id'])): ?>
                            <a href="register.php" class="btn-connect">
                                <i class="fas fa-user-plus"></i> Register as Entrepreneur
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
    </section>
    
    <!-- Toast notifications container -->
    <div class="toast-container"></div>

    <?php if ($isLoggedIn && $userType === 'entrepreneur'): ?>
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
                
                <form id="expressInterestForm" class="interest-form" action="../includes/express_interest.php" method="POST">
                    <input type="hidden" name="initiative_id" id="interestInitiativeId">
                    <input type="hidden" name="user_id" value="<?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : ''; ?>">
                    
                    <div class="interest-form-group">
                        <label for="interestProposal">Briefly describe how you can contribute to this initiative</label>
                        <textarea id="interestProposal" name="proposal" rows="4" placeholder="Describe your proposed approach and how your expertise aligns with this initiative..." required></textarea>
                    </div>
                    
                    <div class="interest-form-group">
                        <label for="interestIdea">Select a relevant idea from your portfolio (optional)</label>
                        <select id="interestIdea" name="idea_id">
                            <option value="">Select an idea</option>
                            <?php 
                            if ($isLoggedIn && $userType === 'entrepreneur') {
                                // Get user ideas
                                $ideasQuery = "SELECT id, title FROM ideas WHERE user_id = ? ORDER BY created_at DESC";
                                $ideasStmt = $conn->prepare($ideasQuery);
                                $ideasStmt->bind_param("i", $_SESSION['user_id']);
                                $ideasStmt->execute();
                                $result = $ideasStmt->get_result();
                                
                                while ($idea = $result->fetch_assoc()): ?>
                                    <option value="<?php echo $idea['id']; ?>"><?php echo htmlspecialchars($idea['title']); ?></option>
                                <?php endwhile;
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="interest-form-actions">
                        <button type="submit" class="btn register">Submit Interest</button>
                        <button type="button" class="btn sign-in modal-cancel">Cancel</button>
                    </div>
        </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

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
            
            // Search and filter initiatives
            const initiativeSearch = document.getElementById('initiativeSearch');
            const initiativeDeptFilter = document.getElementById('initiativeDeptFilter');
            const initiativeCards = document.querySelectorAll('.initiative-card');
            
            function filterInitiatives() {
                const searchTerm = initiativeSearch.value.toLowerCase();
                const department = initiativeDeptFilter.value;
                
                initiativeCards.forEach(card => {
                    const cardTitle = card.querySelector('.initiative-card-title').textContent.toLowerCase();
                    const cardDept = card.dataset.department;
                    
                    // Check if matches search term and department filter
                    const matchesSearch = cardTitle.includes(searchTerm);
                    const matchesDept = department === '' || cardDept === department;
                    
                    // Show/hide based on filters
                    card.style.display = (matchesSearch && matchesDept) ? 'block' : 'none';
                });
            }
            
            initiativeSearch.addEventListener('input', filterInitiatives);
            initiativeDeptFilter.addEventListener('change', filterInitiatives);
            
            // More Details button functionality
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
            
            <?php if ($isLoggedIn && $userType === 'entrepreneur'): ?>
            // Express Interest functionality
            const expressInterestModal = document.getElementById('expressInterestModal');
            const modalClose = document.querySelector('.modal-close');
            const modalCancel = document.querySelector('.modal-cancel');
            
            // Show modal when Express Interest button is clicked
            document.querySelectorAll('.btn-express-interest').forEach(button => {
                button.addEventListener('click', function() {
                    const initiativeId = this.dataset.id;
                    const initiativeTitle = this.dataset.title;
                    
                    // If already interested, we can toggle it off
                    if (this.classList.contains('interested')) {
                        // Create form data for removal
                        const formData = new FormData();
                        formData.append('initiative_id', initiativeId);
                        formData.append('user_id', <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '0'; ?>);
                        formData.append('action', 'remove');
                        
                        // Show loading state
                        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                        
                        // Submit the data using fetch API
                        fetch('../includes/express_interest.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => {
                            // Update UI
                            this.classList.remove('interested');
                            this.innerHTML = '<i class="fas fa-handshake"></i> Express Interest';
                            
                            // Show toast
                            createToast('Interest Removed', `You're no longer interested in "${initiativeTitle}"`, 'info');
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
                    expressInterestModal.classList.add('active');
                    document.body.style.overflow = 'hidden';
                    
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
            
            // Close modal when close button or cancel is clicked
            if (modalClose) {
                modalClose.addEventListener('click', function() {
                    expressInterestModal.classList.remove('active');
                    document.body.style.overflow = 'auto';
                });
            }
            
            if (modalCancel) {
                modalCancel.addEventListener('click', function() {
                    expressInterestModal.classList.remove('active');
                    document.body.style.overflow = 'auto';
                });
            }
            
            // Close modal when clicking outside of it
            window.addEventListener('click', function(event) {
                if (event.target === expressInterestModal) {
                    expressInterestModal.classList.remove('active');
                    document.body.style.overflow = 'auto';
                }
            });
            
            // Handle form submission
            const expressInterestForm = document.getElementById('expressInterestForm');
            expressInterestForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const initiativeId = document.getElementById('interestInitiativeId').value;
                const initiativeButton = document.querySelector(`.btn-express-interest[data-id="${initiativeId}"]`);
                const initiativeTitle = initiativeButton.dataset.title;
                
                // Show loading state
                initiativeButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                
                // Submit form data via fetch
                fetch('../includes/express_interest.php', {
                    method: 'POST',
                    body: new FormData(this)
                })
                .then(response => {
                    // Update button state
                    initiativeButton.classList.add('interested');
                    initiativeButton.innerHTML = '<i class="fas fa-check"></i> Interested';
                    
                    // Close the modal
                    expressInterestModal.classList.remove('active');
                    document.body.style.overflow = 'auto';
                    
                    // Show success notification
                    createToast('Interest Expressed', `Your interest in "${initiativeTitle}" has been submitted`, 'success');
                    
                    // Reset form
                    expressInterestForm.reset();
                })
                .catch(error => {
                    console.error('Error:', error);
                    createToast('Error', 'Failed to submit your interest. Please try again.', 'error');
                    initiativeButton.innerHTML = '<i class="fas fa-handshake"></i> Express Interest';
                });
            });
            <?php elseif ($isLoggedIn): ?>
            // Like button functionality for regular users
            document.querySelectorAll('.btn-like').forEach(button => {
                button.addEventListener('click', function() {
                    const initiativeId = this.dataset.id;
                    const initiativeTitle = this.dataset.title;
                    
                    // Toggle like state
                    this.classList.toggle('liked');
                    
                    if (this.classList.contains('liked')) {
                        this.innerHTML = '<i class="fas fa-thumbs-up"></i> Liked';
                        createToast('Liked', `You liked "${initiativeTitle}"`, 'success');
                        
                        // Send like to server
                        const formData = new FormData();
                        formData.append('initiative_id', initiativeId);
                        formData.append('action', 'like');
                        
                        fetch('../actions/like_initiative.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (!data.success) {
                                // If server error, revert UI
                                this.classList.remove('liked');
                                this.innerHTML = '<i class="fas fa-thumbs-up"></i> Like';
                                createToast('Error', data.message || 'Failed to save like', 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            // Revert UI on network error
                            this.classList.remove('liked');
                            this.innerHTML = '<i class="fas fa-thumbs-up"></i> Like';
                            createToast('Error', 'Network error occurred', 'error');
                        });
                    } else {
                        this.innerHTML = '<i class="fas fa-thumbs-up"></i> Like';
                        createToast('Unliked', `You removed your like from "${initiativeTitle}"`, 'info');
                        
                        // Send unlike to server
                        const formData = new FormData();
                        formData.append('initiative_id', initiativeId);
                        formData.append('action', 'unlike');
                        
                        fetch('../actions/like_initiative.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (!data.success) {
                                // If server error, revert UI
                                this.classList.add('liked');
                                this.innerHTML = '<i class="fas fa-thumbs-up"></i> Liked';
                                createToast('Error', data.message || 'Failed to remove like', 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            // Revert UI on network error
                            this.classList.add('liked');
                            this.innerHTML = '<i class="fas fa-thumbs-up"></i> Liked';
                            createToast('Error', 'Network error occurred', 'error');
                        });
                    }
                });
                
                // Check if this initiative was previously liked
                const initiativeId = button.dataset.id;
                fetch(`../actions/check_initiative_like.php?initiative_id=${initiativeId}`, {
                    method: 'GET'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.liked) {
                        button.classList.add('liked');
                        button.innerHTML = '<i class="fas fa-thumbs-up"></i> Liked';
                    }
                })
                .catch(error => console.error('Error checking like status:', error));
            });
            <?php endif; ?>
            
            // Function to create toast notifications
            function createToast(title, message, type = 'info') {
                const toastContainer = document.querySelector('.toast-container');
                const toast = document.createElement('div');
                toast.className = `toast toast-${type}`;
                toast.innerHTML = `
                    <div class="toast-icon">
                        <i class="fas ${type === 'success' ? 'fa-check-circle' : 
                                      type === 'error' ? 'fa-exclamation-circle' : 
                                      type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle'}"></i>
                    </div>
                    <div class="toast-content">
                        <div class="toast-title">${title}</div>
                        <div class="toast-message">${message}</div>
                    </div>
                    <button class="toast-close">&times;</button>
                `;
                
                toastContainer.appendChild(toast);
                
                // Add event listener to close button
                toast.querySelector('.toast-close').addEventListener('click', function() {
                    toast.remove();
                });
                
                // Auto-remove toast after 5 seconds
                setTimeout(() => {
                    toast.style.opacity = '0';
                    setTimeout(() => {
                        toast.remove();
                    }, 300);
                }, 5000);
            }
        });

        // JavaScript for the Entrepreneur Projects section
        document.addEventListener('DOMContentLoaded', function() {
            // Project search and filtering functionality
            const searchInput = document.getElementById('project-search-input');
            const sectorFilter = document.getElementById('sector-filter');
            const statusFilter = document.getElementById('status-filter');
            const projectCards = document.querySelectorAll('.project-card');
            
            if (searchInput && projectCards.length > 0) {
                // Apply filters when input changes
                function filterProjects() {
                    const searchTerm = searchInput.value.toLowerCase();
                    const selectedSector = sectorFilter.value;
                    const selectedStatus = statusFilter.value;
                    
                    let visibleCount = 0;
                    
                    projectCards.forEach(card => {
                        const title = card.querySelector('.project-card-title').textContent.toLowerCase();
                        const description = card.querySelector('.project-description').textContent.toLowerCase();
                        const sector = card.getAttribute('data-sector');
                        const status = card.getAttribute('data-status');
                        
                        const matchesSearch = searchTerm === '' || 
                                              title.includes(searchTerm) || 
                                              description.includes(searchTerm);
                        
                        const matchesSector = selectedSector === '' || sector === selectedSector;
                        const matchesStatus = selectedStatus === '' || status === selectedStatus;
                        
                        if (matchesSearch && matchesSector && matchesStatus) {
                            card.style.display = 'flex';
                            visibleCount++;
                        } else {
                            card.style.display = 'none';
                        }
                    });
                    
                    // If no results, show message
                    const noResultsEl = document.querySelector('.no-filter-results');
                    if (visibleCount === 0 && !noResultsEl) {
                        const container = document.querySelector('.projects-container');
                        const noResults = document.createElement('div');
                        noResults.className = 'no-projects no-filter-results';
                        noResults.innerHTML = `
                            <div class="empty-icon"><i class="fas fa-search"></i></div>
                            <h3>No Matching Projects</h3>
                            <p>Try adjusting your search criteria or filters.</p>
                            <button class="btn-connect" id="reset-filters">Reset Filters</button>
                        `;
                        container.appendChild(noResults);
                        
                        // Add click event for reset button
                        document.getElementById('reset-filters').addEventListener('click', function() {
                            searchInput.value = '';
                            sectorFilter.value = '';
                            statusFilter.value = '';
                            filterProjects();
                        });
                    } else if (visibleCount > 0 && noResultsEl) {
                        noResultsEl.remove();
                    }
                }
                
                // Add event listeners
                searchInput.addEventListener('input', filterProjects);
                sectorFilter.addEventListener('change', filterProjects);
                statusFilter.addEventListener('change', filterProjects);
            }
            
            // Connect with project functionality
            const connectButtons = document.querySelectorAll('.btn-connect[data-project-id]');
            connectButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const projectId = this.getAttribute('data-project-id');
                    
                    // Send connection request
                    fetch('actions/connect_project.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `project_id=${projectId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Show success message
                            showToast('Connection Request', 'Your request has been sent successfully!', 'success');
                            
                            // Update button
                            this.innerHTML = '<i class="fas fa-check"></i> Request Sent';
                            this.disabled = true;
                            this.classList.add('btn-sent');
                        } else {
                            // Show error message
                            showToast('Connection Error', data.message || 'Failed to send request', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('Connection Error', 'An unexpected error occurred', 'error');
                    });
                });
            });
            
            // Support/like project functionality
            const likeButtons = document.querySelectorAll('.btn-like-project[data-project-id]');
            likeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const projectId = this.getAttribute('data-project-id');
                    const isLiked = this.classList.contains('liked');
                    
                    // Toggle button state
                    if (isLiked) {
                        this.classList.remove('liked');
                        this.innerHTML = '<i class="far fa-heart"></i> Support';
                    } else {
                        this.classList.add('liked');
                        this.innerHTML = '<i class="fas fa-heart"></i> Supported';
                    }
                    
                    // Create form data for AJAX request
                    const formData = new FormData();
                    formData.append('project_id', projectId);
                    formData.append('action', isLiked ? 'unlike' : 'like');
                    
                    // Send support/unsupport request
                    fetch('../actions/support_project.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Show success message
                            showToast(
                                'Idea Support', 
                                isLiked ? 'Support removed successfully' : 'Idea supported successfully!', 
                                'success'
                            );
                        } else {
                            // Revert button state on error
                            if (isLiked) {
                                this.classList.add('liked');
                                this.innerHTML = '<i class="fas fa-heart"></i> Supported';
                            } else {
                                this.classList.remove('liked');
                                this.innerHTML = '<i class="far fa-heart"></i> Support';
                            }
                            
                            // Show error message
                            showToast('Support Error', data.message || 'Failed to update support', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        // Revert button state
                        if (isLiked) {
                            this.classList.add('liked');
                            this.innerHTML = '<i class="fas fa-heart"></i> Supported';
                        } else {
                            this.classList.remove('liked');
                            this.innerHTML = '<i class="far fa-heart"></i> Support';
                        }
                        
                        showToast('Support Error', 'An unexpected error occurred', 'error');
                    });
                });
            });
            
            // Toast notification function
            window.showToast = function(title, message, type = 'info') {
                const toastContainer = document.querySelector('.toast-container');
                if (!toastContainer) return;
                
                const toast = document.createElement('div');
                toast.className = `toast toast-${type}`;
                
                let iconClass = 'fas fa-info-circle';
                switch (type) {
                    case 'success':
                        iconClass = 'fas fa-check-circle';
                        break;
                    case 'error':
                        iconClass = 'fas fa-exclamation-circle';
                        break;
                    case 'warning':
                        iconClass = 'fas fa-exclamation-triangle';
                        break;
                }
                
                toast.innerHTML = `
                    <div class="toast-icon"><i class="${iconClass}"></i></div>
                    <div class="toast-content">
                        <div class="toast-title">${title}</div>
                        <div class="toast-message">${message}</div>
                    </div>
                    <div class="toast-close"><i class="fas fa-times"></i></div>
                `;
                
                toastContainer.appendChild(toast);
                
                // Auto remove after 5 seconds
                setTimeout(() => {
                    toast.style.opacity = '0';
                    setTimeout(() => {
                        toast.remove();
                    }, 300);
                }, 5000);
                
                // Close on click
                const closeBtn = toast.querySelector('.toast-close');
                closeBtn.addEventListener('click', () => {
                    toast.style.opacity = '0';
                    setTimeout(() => {
                        toast.remove();
                    }, 300);
                });
            };
        });
    </script>
</body>
</html>