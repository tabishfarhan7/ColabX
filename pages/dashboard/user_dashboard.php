<?php
// Include database connection and functions
require_once('../../includes/db_connect.php');
require_once('../../includes/functions.php');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'normal') {
    header("Location: ../login.php");
    exit();
}

// Get user data
$userData = get_user_data($conn, $_SESSION['user_id']);

// Get recent user activities
$recentActivities = get_user_activities($conn, $_SESSION['user_id'], 5);

// If no activities yet, provide some default ones as examples
if (empty($recentActivities)) {
    $recentActivities = [
        [
            'type' => 'login',
            'title' => 'You logged in to your account',
            'time' => 'Just now',
            'icon' => 'fa-sign-in-alt'
        ]
    ];
}

// Mock data for dashboard - in a real application, this would come from the database
$recentIdeas = [
    [
        'title' => 'Smart City Waste Management',
        'entrepreneur' => 'John Smith',
        'company' => 'EcoInnovate',
        'date' => '2023-09-15',
        'likes' => 24,
        'comments' => 8
    ],
    [
        'title' => 'Renewable Energy Grid Integration',
        'entrepreneur' => 'Sarah Johnson',
        'company' => 'GreenPower Solutions',
        'date' => '2023-10-05',
        'likes' => 42,
        'comments' => 16
    ],
    [
        'title' => 'AI-Driven Public Transport Optimization',
        'entrepreneur' => 'Michael Chen',
        'company' => 'Smart Transit',
        'date' => '2023-11-20',
        'likes' => 18,
        'comments' => 5
    ]
];

$recentInitiatives = [
    [
        'title' => 'Smart City Development Program',
        'department' => 'Urban Development',
        'organization' => 'Ministry of Urban Development',
        'date' => '2023-10-10',
        'applications' => 24
    ],
    [
        'title' => 'Clean Energy Innovation Challenge',
        'department' => 'Energy',
        'organization' => 'Department of Energy',
        'date' => '2023-11-05',
        'applications' => 18
    ],
    [
        'title' => 'Digital Governance Transformation',
        'department' => 'IT & Communication',
        'organization' => 'Ministry of Digital Affairs',
        'date' => '2023-11-12',
        'applications' => 12
    ]
];

$upcomingEvents = [
    [
        'title' => 'Innovation Summit 2023',
        'date' => '2023-12-15',
        'location' => 'Convention Center',
        'type' => 'Conference'
    ],
    [
        'title' => 'Startup-Government Networking Event',
        'date' => '2023-12-22',
        'location' => 'Tech Hub',
        'type' => 'Networking'
    ],
    [
        'title' => 'Sustainable Cities Hackathon',
        'date' => '2024-01-10',
        'location' => 'Innovation Center',
        'type' => 'Hackathon'
    ]
];

// Calculate statistics
$totalIdeasViewed = 15;
$totalInitiativesExplored = 8;
$totalEventsAttended = 3;
$totalInteractions = 27;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ColabX - User Dashboard</title>
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
                <li><a href="user_dashboard.php" class="link">Dashboard</a></li>
            </ul>
            <div class="user-actions">
                <div class="notification-badge" data-count="2">
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
            <div class="header-date">
                <i class="fas fa-calendar-day"></i> <?php echo date('l, F j, Y'); ?>
            </div>
        </div>
        
        <div class="dashboard-content">
            <h2>User Dashboard</h2>
            <p>Explore innovations, discover government initiatives, and engage with the ColabX community.</p>
            
            <div class="action-buttons">
                <button class="action-btn primary-btn" id="exploreIdeas"><i class="fas fa-lightbulb"></i> Explore Ideas</button>
                <button class="action-btn secondary-btn" id="browseInitiatives"><i class="fas fa-building-columns"></i> Browse Initiatives</button>
                <button class="action-btn secondary-btn"><i class="fas fa-calendar-alt"></i> Upcoming Events</button>
                <button class="action-btn secondary-btn"><i class="fas fa-user-friends"></i> My Network</button>
            </div>
            
            <div class="stats-container">
                <div class="stat-card">
                    <div class="icon-container">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Ideas Viewed</h3>
                        <div class="stat-number"><?php echo $totalIdeasViewed; ?></div>
                        <p>Entrepreneur innovations</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="icon-container">
                        <i class="fas fa-building-columns"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Initiatives Explored</h3>
                        <div class="stat-number"><?php echo $totalInitiativesExplored; ?></div>
                        <p>Government programs</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="icon-container">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Events Attended</h3>
                        <div class="stat-number"><?php echo $totalEventsAttended; ?></div>
                        <p>Past events</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="icon-container">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Interactions</h3>
                        <div class="stat-number"><?php echo $totalInteractions; ?></div>
                        <p>Comments and likes</p>
                    </div>
                </div>
            </div>
            
            <!-- Activity Chart -->
            <div class="chart-container">
                <div class="chart-header">
                    <h3 class="chart-title">Your Activity</h3>
                    <div class="chart-actions">
                        <button class="chart-btn active" data-period="week">Week</button>
                        <button class="chart-btn" data-period="month">Month</button>
                        <button class="chart-btn" data-period="year">Year</button>
                    </div>
                </div>
                <div style="height: 250px; max-height: 250px; position: relative;">
                    <canvas id="activityChart"></canvas>
                </div>
            </div>
            
            <!-- Content Tabs -->
            <div class="dashboard-tabs">
                <div class="tab-headers">
                    <button class="tab-btn active" data-tab="ideas">Trending Ideas</button>
                    <button class="tab-btn" data-tab="initiatives">Government Initiatives</button>
                    <button class="tab-btn" data-tab="events">Upcoming Events</button>
                </div>
                
                <div class="tab-content">
                    <!-- Trending Ideas Tab -->
                    <div class="tab-pane active" id="ideas-tab">
                        <div class="stats-container">
                            <?php foreach ($recentIdeas as $idea): ?>
                            <div class="stat-card hover-card">
                                <h3><?php echo htmlspecialchars($idea['title']); ?></h3>
                                <p><strong>By:</strong> <?php echo htmlspecialchars($idea['entrepreneur']); ?> (<?php echo htmlspecialchars($idea['company']); ?>)</p>
                                <p><strong>Posted:</strong> <?php echo htmlspecialchars($idea['date']); ?></p>
                                <div class="card-stats">
                                    <span><i class="fas fa-heart"></i> <?php echo $idea['likes']; ?></span>
                                    <span><i class="fas fa-comment"></i> <?php echo $idea['comments']; ?></span>
                                </div>
                                <div class="card-actions">
                                    <button class="action-btn primary-btn"><i class="fas fa-eye"></i> View Details</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Government Initiatives Tab -->
                    <div class="tab-pane" id="initiatives-tab">
                        <div class="stats-container">
                            <?php foreach ($recentInitiatives as $initiative): ?>
                            <div class="stat-card hover-card">
                                <h3><?php echo htmlspecialchars($initiative['title']); ?></h3>
                                <p><strong>Department:</strong> <?php echo htmlspecialchars($initiative['department']); ?></p>
                                <p><strong>Organization:</strong> <?php echo htmlspecialchars($initiative['organization']); ?></p>
                                <p><strong>Posted:</strong> <?php echo htmlspecialchars($initiative['date']); ?></p>
                                <p><strong>Applications:</strong> <?php echo $initiative['applications']; ?></p>
                                <div class="card-actions">
                                    <button class="action-btn primary-btn"><i class="fas fa-info-circle"></i> More Info</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Upcoming Events Tab -->
                    <div class="tab-pane" id="events-tab">
                        <div class="stats-container">
                            <?php foreach ($upcomingEvents as $event): ?>
                            <div class="stat-card hover-card">
                                <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                                <p><strong>Date:</strong> <?php echo htmlspecialchars($event['date']); ?></p>
                                <p><strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?></p>
                                <p><strong>Type:</strong> <?php echo htmlspecialchars($event['type']); ?></p>
                                <div class="card-actions">
                                    <button class="action-btn primary-btn"><i class="fas fa-calendar-plus"></i> Add to Calendar</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="dashboard-section">
                <h3>Recent Activity</h3>
                <p class="section-description">Your latest actions and interactions on the platform</p>
                
                <div class="activity-container">
                    <?php if (empty($recentActivities)): ?>
                        <div class="empty-state">
                            <i class="fas fa-history empty-icon"></i>
                            <p>No activity recorded yet. As you interact with the platform, your activities will appear here.</p>
                        </div>
                    <?php else: ?>
                        <?php 
                        // Store PHP current time for JavaScript
                        $current_timestamp = time();
                        
                        // Debug information (only shown if admin user)
                        if ($_SESSION['user_type'] === 'normal' && isset($_GET['debug'])):
                        ?>
                        <div style="background: #f8f9fa; padding: 10px; margin-bottom: 15px; border-radius: 4px; font-size: 12px;">
                            <h4 style="margin-top: 0;">Debug Info:</h4>
                            <p>Current server time: <?php echo date('Y-m-d H:i:s', $current_timestamp); ?> (<?php echo $current_timestamp; ?>)</p>
                            <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
                                <tr>
                                    <th style="border: 1px solid #ddd; padding: 4px; text-align: left;">Activity</th>
                                    <th style="border: 1px solid #ddd; padding: 4px; text-align: left;">Timestamp</th>
                                    <th style="border: 1px solid #ddd; padding: 4px; text-align: left;">Created At</th>
                                    <th style="border: 1px solid #ddd; padding: 4px; text-align: left;">Time Ago</th>
                                </tr>
                                <?php foreach ($recentActivities as $activity): ?>
                                <tr>
                                    <td style="border: 1px solid #ddd; padding: 4px;"><?php echo htmlspecialchars($activity['title']); ?></td>
                                    <td style="border: 1px solid #ddd; padding: 4px;"><?php echo $activity['timestamp']; ?></td>
                                    <td style="border: 1px solid #ddd; padding: 4px;"><?php echo $activity['raw_date']; ?></td>
                                    <td style="border: 1px solid #ddd; padding: 4px;"><?php echo htmlspecialchars($activity['time']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                        <?php endif; ?>
                        
                        <script>
                            // Store current server time for calculations
                            var serverTime = <?php echo $current_timestamp; ?>;
                            var clientTime = Math.floor(Date.now() / 1000);
                            var timeDiff = clientTime - serverTime; // Time difference between client and server
                            
                            // Log time difference info to console
                            console.log("Server time: " + serverTime + " (" + new Date(serverTime * 1000).toISOString() + ")");
                            console.log("Client time: " + clientTime + " (" + new Date(clientTime * 1000).toISOString() + ")");
                            console.log("Time difference: " + timeDiff + " seconds");
                        </script>
                        
                        <?php foreach ($recentActivities as $activity): ?>
                            <div class="activity-item" data-activity-id="<?php echo htmlspecialchars($activity['id']); ?>">
                                <div class="activity-icon activity-icon-<?php echo $activity['type']; ?>">
                                    <i class="fas <?php echo $activity['icon']; ?>"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title"><?php echo htmlspecialchars($activity['title']); ?></div>
                                    <div class="activity-time" 
                                        data-raw-time="<?php echo isset($activity['raw_date']) ? htmlspecialchars($activity['raw_date']) : ''; ?>"
                                        data-unix-time="<?php echo isset($activity['timestamp']) ? htmlspecialchars($activity['timestamp']) : '0'; ?>"
                                        data-time-text="<?php echo htmlspecialchars($activity['time']); ?>"
                                    >
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
                    <div class="profile-role">Community Member</div>
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
                            <div class="info-label">Interests</div>
                            <div class="info-value">Smart Cities, Renewable Energy, Education</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Account Type</div>
                            <div class="info-value">Normal User</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Member Since</div>
                            <div class="info-value">November 2023</div>
                        </div>
                    </div>
                    
                    <div class="profile-actions">
                        <button class="action-btn secondary-btn">
                            <i class="fas fa-edit"></i> Edit Profile
                        </button>
                        <button class="action-btn secondary-btn">
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
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($userData['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="interests">Interests (comma separated)</label>
                        <input type="text" id="interests" name="interests" value="<?php echo isset($userData['interests']) ? htmlspecialchars($userData['interests']) : 'Smart Cities, Renewable Energy, Education'; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="bio">Bio</label>
                        <textarea id="bio" name="bio" rows="3" placeholder="Tell us about yourself..."><?php echo isset($userData['bio']) ? htmlspecialchars($userData['bio']) : ''; ?></textarea>
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
                            <input type="checkbox" id="eventReminders" name="notifications[events]" value="1" <?php echo isset($userData['event_reminders']) && $userData['event_reminders'] ? 'checked' : ''; ?>>
                            <label for="eventReminders">Event Reminders</label>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h4>Account Privacy</h4>
                        <div class="form-group radio-group">
                            <div class="radio-option">
                                <input type="radio" id="privacyPublic" name="privacyLevel" value="public" <?php echo (!isset($userData['privacy_level']) || $userData['privacy_level'] == 'public') ? 'checked' : ''; ?>>
                                <label for="privacyPublic">Public Profile</label>
                                <p class="help-text">Anyone can see your profile and activities</p>
                            </div>
                            
                            <div class="radio-option">
                                <input type="radio" id="privacyLimited" name="privacyLevel" value="limited" <?php echo (isset($userData['privacy_level']) && $userData['privacy_level'] == 'limited') ? 'checked' : ''; ?>>
                                <label for="privacyLimited">Limited Profile</label>
                                <p class="help-text">Only registered users can see your profile</p>
                            </div>
                            
                            <div class="radio-option">
                                <input type="radio" id="privacyPrivate" name="privacyLevel" value="private" <?php echo (isset($userData['privacy_level']) && $userData['privacy_level'] == 'private') ? 'checked' : ''; ?>>
                                <label for="privacyPrivate">Private Profile</label>
                                <p class="help-text">Only connections can see your profile</p>
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
            
            // Remove initial toast if it exists
            const existingToasts = document.querySelectorAll('.toast');
            existingToasts.forEach(toast => toast.remove());
            
            // Initialize activity chart
            const ctx = document.getElementById('activityChart').getContext('2d');
            const activityChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [
                        {
                            label: 'Ideas Viewed',
                            data: [2, 3, 1, 5, 2, 0, 2],
                            borderColor: '#FFE535',
                            backgroundColor: 'rgba(255, 229, 53, 0.2)',
                            borderWidth: 2,
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Initiatives Explored',
                            data: [1, 0, 2, 1, 3, 0, 1],
                            borderColor: '#17a2b8',
                            backgroundColor: 'rgba(23, 162, 184, 0.2)',
                            borderWidth: 2,
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Interactions',
                            data: [5, 3, 6, 2, 4, 1, 6],
                            borderColor: '#28a745',
                            backgroundColor: 'rgba(40, 167, 69, 0.2)',
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
                            intersect: false
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
                        activityChart.data.labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                        activityChart.data.datasets[0].data = [2, 3, 1, 5, 2, 0, 2];
                        activityChart.data.datasets[1].data = [1, 0, 2, 1, 3, 0, 1];
                        activityChart.data.datasets[2].data = [5, 3, 6, 2, 4, 1, 6];
                    } else if (period === 'month') {
                        activityChart.data.labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
                        activityChart.data.datasets[0].data = [8, 12, 6, 9];
                        activityChart.data.datasets[1].data = [3, 4, 2, 6];
                        activityChart.data.datasets[2].data = [12, 15, 9, 18];
                    } else if (period === 'year') {
                        activityChart.data.labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                        activityChart.data.datasets[0].data = [0, 0, 0, 0, 0, 0, 0, 0, 5, 12, 22, 15];
                        activityChart.data.datasets[1].data = [0, 0, 0, 0, 0, 0, 0, 0, 2, 6, 8, 8];
                        activityChart.data.datasets[2].data = [0, 0, 0, 0, 0, 0, 0, 0, 8, 18, 24, 27];
                    }
                    
                    activityChart.update();
                });
            });
            
            // Handle tab switching
            const tabButtons = document.querySelectorAll('.tab-btn');
            const tabPanes = document.querySelectorAll('.tab-pane');
            
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from all buttons and panes
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabPanes.forEach(pane => pane.classList.remove('active'));
                    
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    // Show corresponding pane
                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(`${tabId}-tab`).classList.add('active');
                });
            });
            
            // Add styles for tabs
            const tabStyles = document.createElement('style');
            tabStyles.innerHTML = `
                .dashboard-tabs {
                    margin-top: 30px;
                }
                
                .tab-headers {
                    display: flex;
                    gap: 10px;
                    margin-bottom: 20px;
                    border-bottom: 1px solid #eee;
                    padding-bottom: 10px;
                }
                
                .tab-btn {
                    padding: 10px 15px;
                    background: none;
                    border: none;
                    border-radius: 5px;
                    cursor: pointer;
                    font-weight: 500;
                    color: #666;
                    transition: all 0.3s ease;
                }
                
                .tab-btn:hover {
                    background-color: #f5f5f5;
                    color: #333;
                }
                
                .tab-btn.active {
                    background-color: var(--primary-color);
                    color: var(--secondary-color);
                }
                
                .tab-content {
                    position: relative;
                }
                
                .tab-pane {
                    display: none;
                    animation: fadeIn 0.5s ease;
                }
                
                .tab-pane.active {
                    display: block;
                }
                
                .card-stats {
                    display: flex;
                    gap: 15px;
                    margin: 15px 0;
                }
                
                .card-stats span {
                    font-size: 0.9rem;
                    color: #666;
                }
                
                .card-stats span i {
                    margin-right: 5px;
                    color: var(--primary-color);
                }
                
                .profile-actions {
                    display: flex;
                    gap: 10px;
                    margin-top: 20px;
                }
            `;
            document.head.appendChild(tabStyles);
            
            // Action buttons functionality
            document.getElementById('exploreIdeas').addEventListener('click', function() {
                document.querySelector('[data-tab="ideas"]').click();
            });
            
            document.getElementById('browseInitiatives').addEventListener('click', function() {
                document.querySelector('[data-tab="initiatives"]').click();
            });
            
            // Add custom styles for improved layout
            const customStyles = document.createElement('style');
            customStyles.innerHTML = `
                /* Fix for icon positioning in stat cards */
                .icon-container {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    width: 50px;
                    height: 50px;
                    border-radius: 50%;
                    background-color: rgba(255, 229, 53, 0.2);
                    margin-bottom: 15px;
                }
                
                .stat-card {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    text-align: center;
                    padding: 20px 15px;
                }
                
                .stat-card:nth-child(1) .icon-container {
                    background-color: rgba(255, 229, 53, 0.2);
                }
                .stat-card:nth-child(1) .icon-container i {
                    color: var(--primary-dark);
                }
                
                .stat-card:nth-child(2) .icon-container {
                    background-color: rgba(23, 162, 184, 0.2);
                }
                .stat-card:nth-child(2) .icon-container i {
                    color: var(--info-color);
                }
                
                .stat-card:nth-child(3) .icon-container {
                    background-color: rgba(40, 167, 69, 0.2);
                }
                .stat-card:nth-child(3) .icon-container i {
                    color: var(--success-color);
                }
                
                .stat-card:nth-child(4) .icon-container {
                    background-color: rgba(255, 193, 7, 0.2);
                }
                .stat-card:nth-child(4) .icon-container i {
                    color: var(--warning-color);
                }
                
                .stat-content {
                    width: 100%;
                }
                
                /* Improved activity section */
                .activity-container {
                    display: flex;
                    flex-direction: column;
                    gap: 12px;
                    margin-top: 15px;
                }
                
                .activity-item {
                    display: flex;
                    align-items: flex-start;
                    background-color: #f9f9f9;
                    border-radius: 8px;
                    padding: 12px 15px;
                    transition: var(--transition);
                    border-left: 3px solid var(--primary-color);
                }
                
                .activity-item:hover {
                    background-color: #f0f0f0;
                    transform: translateX(5px);
                }
                
                .activity-icon {
                    width: 40px;
                    height: 40px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin-right: 15px;
                    flex-shrink: 0;
                }
                
                .activity-icon-like {
                    background-color: rgba(220, 53, 69, 0.15);
                    color: #dc3545;
                }
                
                .activity-icon-comment {
                    background-color: rgba(23, 162, 184, 0.15);
                    color: #17a2b8;
                }
                
                .activity-icon-view {
                    background-color: rgba(40, 167, 69, 0.15);
                    color: #28a745;
                }
                
                .activity-icon-save {
                    background-color: rgba(255, 193, 7, 0.15);
                    color: #ffc107;
                }
                
                .activity-icon-login {
                    background-color: rgba(13, 110, 253, 0.15);
                    color: #0d6efd;
                }
                
                .activity-icon-logout {
                    background-color: rgba(108, 117, 125, 0.15);
                    color: #6c757d;
                }
                
                .activity-icon-password_change {
                    background-color: rgba(111, 66, 193, 0.15);
                    color: #6f42c1;
                }
                
                .activity-icon-settings_update {
                    background-color: rgba(13, 202, 240, 0.15);
                    color: #0dcaf0;
                }
                
                .activity-icon-profile_update {
                    background-color: rgba(25, 135, 84, 0.15);
                    color: #198754;
                }
                
                .activity-icon-photo_update {
                    background-color: rgba(102, 16, 242, 0.15);
                    color: #6610f2;
                }
                
                .activity-title {
                    font-weight: 500;
                    margin-bottom: 5px;
                    color: var(--text-color);
                }
                
                .activity-time {
                    font-size: 0.8rem;
                    color: #6c757d;
                }
                
                /* Improved tab-pane spacing */
                .tab-pane .stats-container {
                    gap: 15px;
                }
                
                .tab-pane .stat-card {
                    min-width: calc(33.333% - 15px);
                    padding: 15px;
                }
                
                /* Card title and content improvements */
                .tab-pane .stat-card h3 {
                    font-size: 1.1rem;
                    margin-top: 5px;
                    margin-bottom: 10px;
                    color: var(--text-color);
                }
                
                .tab-pane .stat-card p {
                    margin: 5px 0;
                    font-size: 0.9rem;
                }
                
                .card-actions {
                    margin-top: 15px;
                }
                
                /* Responsive adjustments */
                @media (max-width: 768px) {
                    .tab-pane .stat-card {
                        min-width: 100%;
                    }
                    
                    .action-buttons {
                        gap: 10px;
                    }
                }
                
                /* Dashboard section spacing */
                .dashboard-section {
                    margin-top: 35px;
                    padding-top: 25px;
                }
                
                /* Profile section improvements */
                .profile-section {
                    gap: 20px;
                    margin-top: 40px;
                    border-top: 1px solid rgba(0,0,0,0.1);
                    padding-top: 30px;
                }
                
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
                    transition: var(--transition);
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
                    color: var(--text-color);
                }
                
                .profile-actions {
                    display: flex;
                    gap: 10px;
                    margin-top: 20px;
                }
                
                /* Empty state styling */
                .empty-state {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    padding: 30px 20px;
                    text-align: center;
                    background-color: #f9f9f9;
                    border-radius: 8px;
                    margin: 15px 0;
                }
                
                .empty-icon {
                    font-size: 3rem;
                    color: #d0d0d0;
                    margin-bottom: 15px;
                }
                
                .empty-state p {
                    color: #777;
                    max-width: 400px;
                    margin: 0 auto;
                }
                
                .section-description {
                    color: #6c757d;
                    margin-top: -5px;
                    margin-bottom: 15px;
                    font-size: 0.95rem;
                }
                
                @media (max-width: 768px) {
                    .profile-section {
                        grid-template-columns: 1fr;
                    }
                    
                    .profile-info {
                        grid-template-columns: 1fr;
                    }
                    
                    .profile-actions {
                        flex-direction: column;
                        gap: 10px;
                    }
                    
                    .profile-actions .action-btn {
                        width: 100%;
                    }
                }
                
                /* Action button spacing */
                .action-buttons {
                    margin: 25px 0 30px;
                    display: flex;
                    flex-wrap: wrap;
                    gap: 15px;
                }
                
                /* Header date styling */
                .header-date {
                    display: flex;
                    align-items: center;
                    color: #6c757d;
                    font-size: 0.95rem;
                }
                
                .header-date i {
                    margin-right: 8px;
                    color: var(--primary-color);
                }
                
                .dashboard-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 30px;
                    padding-bottom: 15px;
                    border-bottom: 1px solid rgba(0,0,0,0.1);
                }
                
                /* Profile avatar styling */
                .profile-avatar {
                    width: 100px;
                    height: 100px;
                    border-radius: 50%;
                    background-color: #f5f5f5;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0 auto 15px;
                    border: 3px solid var(--primary-color);
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
            `;
            document.head.appendChild(customStyles);
            
            // Modal functionality
            const modals = document.querySelectorAll('.modal');
            const editProfileBtn = document.querySelector('.profile-actions .action-btn:nth-child(1)');
            const accountSettingsBtn = document.querySelector('.profile-actions .action-btn:nth-child(2)');
            const modalCloseButtons = document.querySelectorAll('.modal-close, .modal-cancel');
            
            // Open Edit Profile modal
            editProfileBtn.addEventListener('click', function() {
                document.getElementById('editProfileModal').classList.add('active');
                document.body.classList.add('modal-open');
            });
            
            // Open Account Settings modal
            accountSettingsBtn.addEventListener('click', function() {
                document.getElementById('accountSettingsModal').classList.add('active');
                document.body.classList.add('modal-open');
            });
            
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
            
            editProfileForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                // Show loading indicator
                showToast('Processing', 'Updating your profile...', 'info');
                
                fetch('../../includes/update_profile.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update displayed name if it was changed
                        if (data.updatedName) {
                            document.querySelector('.profile-name').textContent = data.updatedName;
                            document.querySelector('.welcome-message').textContent = `Welcome, ${data.updatedName}!`;
                            document.querySelector('.info-item:nth-child(1) .info-value').textContent = data.updatedName;
                        }
                        
                        // Close the modal
                        document.getElementById('editProfileModal').classList.remove('active');
                        document.body.classList.remove('modal-open');
                        
                        // Show success message
                        showToast('Profile Updated', data.message, 'success');
                        
                        // Reload page if profile picture was updated
                        if (data.reloadPage) {
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        }
                    } else {
                        showToast('Error', data.message, 'error');
                    }
                })
                .catch(error => {
                    showToast('Error', 'An unexpected error occurred. Please try again.', 'error');
                    console.error('Error:', error);
                });
            });
            
            accountSettingsForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                // Password validation on the client side
                const newPassword = document.getElementById('newPassword').value;
                const confirmPassword = document.getElementById('confirmPassword').value;
                
                if (newPassword && newPassword !== confirmPassword) {
                    showToast('Password Error', 'New passwords do not match', 'error');
                    return;
                }
                
                fetch('../../includes/update_settings.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Close the modal
                        document.getElementById('accountSettingsModal').classList.remove('active');
                        document.body.classList.remove('modal-open');
                        
                        // Show success message
                        showToast('Settings Saved', data.message, 'success');
                    } else {
                        showToast('Error', data.message, 'error');
                    }
                })
                .catch(error => {
                    showToast('Error', 'An unexpected error occurred. Please try again.', 'error');
                    console.error('Error:', error);
                });
            });
            
            // Show toast notification
            function showToast(title, message, type = 'success') {
                // Remove existing toasts
                const existingToasts = document.querySelectorAll('.toast');
                existingToasts.forEach(toast => toast.remove());
                
                // Create new toast
                const toastContainer = document.querySelector('.toast-container');
                const toast = document.createElement('div');
                toast.className = `toast toast-${type}`;
                
                const iconClass = type === 'success' ? 'fa-check-circle' : 
                                 type === 'error' ? 'fa-exclamation-circle' : 
                                 type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle';
                
                toast.innerHTML = `
                    <div class="toast-icon"><i class="fas ${iconClass}"></i></div>
                    <div class="toast-content">
                        <div class="toast-title">${title}</div>
                        <div class="toast-message">${message}</div>
                    </div>
                    <div class="toast-close"><i class="fas fa-times"></i></div>
                `;
                
                toastContainer.appendChild(toast);
                
                // Add close functionality to new toast
                toast.querySelector('.toast-close').addEventListener('click', function() {
                    toast.remove();
                });
                
                // Auto-hide toast after 3 seconds
                setTimeout(() => {
                    toast.remove();
                }, 3000);
            }
            
            // Add modal styles
            const modalStyles = document.createElement('style');
            modalStyles.innerHTML = `
                /* Modal styles */
                .modal {
                    display: none;
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0, 0, 0, 0.5);
                    z-index: 1000;
                    justify-content: center;
                    align-items: center;
                    overflow-y: auto;
                    padding: 20px;
                }
                
                .modal.active {
                    display: flex;
                }
                
                body.modal-open {
                    overflow: hidden;
                }
                
                .modal-content {
                    background-color: white;
                    border-radius: 8px;
                    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
                    width: 100%;
                    max-width: 600px;
                    animation: modalFadeIn 0.3s ease;
                    max-height: 90vh;
                    display: flex;
                    flex-direction: column;
                }
                
                @keyframes modalFadeIn {
                    from {
                        opacity: 0;
                        transform: translateY(-20px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
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
                    color: var(--text-color);
                }
                
                .modal-close {
                    background: none;
                    border: none;
                    font-size: 1.2rem;
                    cursor: pointer;
                    color: #777;
                    transition: color 0.2s;
                }
                
                .modal-close:hover {
                    color: #333;
                }
                
                .modal-body {
                    padding: 20px;
                    overflow-y: auto;
                }
                
                /* Form styles */
                .form-group {
                    margin-bottom: 20px;
                }
                
                .form-group label {
                    display: block;
                    margin-bottom: 5px;
                    font-weight: 500;
                    color: #444;
                }
                
                .form-group input[type="text"],
                .form-group input[type="email"],
                .form-group input[type="password"],
                .form-group textarea {
                    width: 100%;
                    padding: 10px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    font-size: 0.95rem;
                    transition: border-color 0.3s;
                }
                
                .form-group input[type="text"]:focus,
                .form-group input[type="email"]:focus,
                .form-group input[type="password"]:focus,
                .form-group textarea:focus {
                    border-color: var(--primary-color);
                    outline: none;
                }
                
                .form-section {
                    margin-bottom: 25px;
                    padding-bottom: 20px;
                    border-bottom: 1px solid #eee;
                }
                
                .form-section h4 {
                    margin-top: 0;
                    margin-bottom: 15px;
                    color: #333;
                }
                
                .form-section:last-child {
                    border-bottom: none;
                    margin-bottom: 15px;
                }
                
                .checkbox-group, .radio-group {
                    margin-bottom: 10px;
                }
                
                .checkbox-group label, .radio-option label {
                    display: inline-block;
                    margin-left: 8px;
                    font-weight: 500;
                }
                
                .radio-option {
                    margin-bottom: 10px;
                }
                
                .help-text {
                    margin: 3px 0 0 25px;
                    font-size: 0.8rem;
                    color: #6c757d;
                }
                
                .form-actions {
                    display: flex;
                    gap: 10px;
                    margin-top: 25px;
                }
                
                @media (max-width: 768px) {
                    .form-actions {
                        flex-direction: column;
                    }
                    
                    .form-actions button {
                        width: 100%;
                    }
                }
                
                /* Toast types */
                .toast-error {
                    background-color: #f8d7da;
                    border-left: 4px solid #dc3545;
                }
                
                .toast-error .toast-icon {
                    color: #dc3545;
                }
                
                .toast-warning {
                    background-color: #fff3cd;
                    border-left: 4px solid #ffc107;
                }
                
                .toast-warning .toast-icon {
                    color: #ffc107;
                }
                
                .toast-info {
                    background-color: #d1ecf1;
                    border-left: 4px solid #17a2b8;
                }
                
                .toast-info .toast-icon {
                    color: #17a2b8;
                }
            `;
            document.head.appendChild(modalStyles);
            
            // Real-time activity time updates
            function updateActivityTimes() {
                const activityItems = document.querySelectorAll('.activity-time');
                const now = Math.floor(Date.now() / 1000); // Current client time
                
                activityItems.forEach(function(item, index) {
                    // Get timestamp from data attribute and adjust for server-client time difference
                    let timestamp = parseInt(item.getAttribute('data-unix-time'));
                    const rawTime = item.getAttribute('data-raw-time');
                    
                    // If we don't have a valid timestamp, try to extract it from raw time
                    if (!timestamp || timestamp <= 0) {
                        if (rawTime) {
                            timestamp = convertRawTimeToTimestamp(rawTime);
                            if (timestamp > 0) {
                                // Store the converted timestamp
                                item.setAttribute('data-unix-time', timestamp);
                            }
                        }
                    }
                    
                    // Only proceed if we have a valid timestamp
                    if (timestamp && timestamp > 0) {
                        // Calculate time difference, accounting for server-client time offset
                        // If timeDiff is positive, client is ahead of server; if negative, client is behind
                        // Since the timestamp is from the server, we need to adjust it to client time
                        const adjustedTimestamp = timestamp + timeDiff;
                        const diff = now - adjustedTimestamp;
                        
                        // Log detailed timestamp info for debugging
                        console.log(
                            "Activity " + index + 
                            ": Original timestamp = " + timestamp + 
                            ", Adjusted = " + adjustedTimestamp +
                            ", Now = " + now + 
                            ", Diff = " + diff + 
                            " seconds"
                        );
                        
                        // Format time string
                        let timeText = '';
                        if (diff < 10) {
                            timeText = 'Just now';
                        } else if (diff < 60) {
                            timeText = `${Math.floor(diff)} seconds ago`;
                        } else if (diff < 3600) {
                            const minutes = Math.floor(diff / 60);
                            timeText = minutes + ' minute' + (minutes > 1 ? 's' : '') + ' ago';
                        } else if (diff < 86400) {
                            const hours = Math.floor(diff / 3600);
                            timeText = hours + ' hour' + (hours > 1 ? 's' : '') + ' ago';
                        } else if (diff < 604800) {
                            const days = Math.floor(diff / 86400);
                            timeText = days + ' day' + (days > 1 ? 's' : '') + ' ago';
                        } else {
                            const date = new Date(timestamp * 1000);
                            timeText = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                        }
                        
                        // Only update if text has changed
                        if (item.textContent.trim() !== timeText) {
                            item.textContent = timeText;
                            
                            // Also update the data attribute for reference
                            item.setAttribute('data-time-text', timeText);
                        }
                    }
                });
            }
            
            // Helper function to convert raw MySQL datetime to timestamp
            function convertRawTimeToTimestamp(rawTime) {
                if (!rawTime) return 0;
                
                // Parse the MySQL timestamp format (YYYY-MM-DD HH:MM:SS)
                const parts = rawTime.match(/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/);
                if (parts) {
                    // JavaScript months are 0-based, so subtract 1 from the month
                    const year = parseInt(parts[1]);
                    const month = parseInt(parts[2]) - 1;
                    const day = parseInt(parts[3]);
                    const hour = parseInt(parts[4]);
                    const minute = parseInt(parts[5]);
                    const second = parseInt(parts[6]);
                    
                    // Create Date object and get timestamp
                    const date = new Date(year, month, day, hour, minute, second);
                    return Math.floor(date.getTime() / 1000);
                }
                return 0;
            }
            
            // Initialize times when the page loads
            document.addEventListener('DOMContentLoaded', function() {
                // Force an immediate update when the page loads
                updateActivityTimes();
                
                // Update times every 10 seconds to keep them current
                setInterval(updateActivityTimes, 10000);
            });
        });
    </script>
</body>
</html> 