<?php
// ... existing code ...
                    // Store session data
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['name'] = $full_name;
                    $_SESSION['email'] = $email;
                    $_SESSION['user_type'] = $user_type;
                    
                    // Store login timestamp for accurate "Just now" display on dashboard
                    $_SESSION['login_time'] = time();
                    
                    // Log the login activity
                    record_activity($conn, $user_id, 'login', 'You logged in to your account');
                    
                    // Redirect to appropriate dashboard
                    redirect_to_dashboard($user_type);
// ... existing code ... 