<?php
// Database Connection Parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "colabx_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>ColabX Database Update - Adding idea_supports table</h2>";

// Create idea_supports table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS idea_supports (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idea_id INT(11) UNSIGNED NOT NULL,
    user_id INT(11) UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY user_idea_unique (user_id, idea_id),
    FOREIGN KEY (idea_id) REFERENCES ideas(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "<p>✅ Table 'idea_supports' created successfully or already exists.</p>";
} else {
    echo "<p>❌ Error creating table 'idea_supports': " . $conn->error . "</p>";
}

// Close the database connection
$conn->close();

echo "<p>Database update completed. <a href='index.php'>Return to home page</a></p>";
?> 