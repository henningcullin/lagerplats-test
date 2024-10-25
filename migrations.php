<?php
// Database configuration
$servername = "localhost";   // Default XAMPP MySQL server
$username = "root";          // Default username for XAMPP MySQL
$password = "";              // Default password for XAMPP MySQL (usually empty)
$dbname = "lagerplatser";    // Name of your database
$sqlFile = __DIR__ . DIRECTORY_SEPARATOR . 'migrations.sql';

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to execute a query
function executeQuery($conn, $query) {
    if ($conn->query($query) === TRUE) {
        echo "Query executed successfully: $query\n";
    } else {
        echo "Error executing query: " . $conn->error . "\n";
    }
}

// Step 1: Drop the database if it exists
executeQuery($conn, "DROP DATABASE IF EXISTS $dbname");

// Step 2: Create the database
executeQuery($conn, "CREATE DATABASE $dbname");

// Step 3: Select the newly created database
$conn->select_db($dbname);

// Step 4: Load the SQL migration file
if (!file_exists($sqlFile)) {
    die("SQL file not found: $sqlFile");
}

// Step 5: Read the SQL file and execute the queries
$sql = file_get_contents($sqlFile);
if ($sql === false) {
    die("Error reading SQL file: $sqlFile");
}

// Split the SQL file into individual statements
$queries = explode(';', $sql);

// Execute each query
foreach ($queries as $query) {
    $trimmedQuery = trim($query);
    if (!empty($trimmedQuery)) {
        executeQuery($conn, $trimmedQuery);
    }
}

// Close connection
$conn->close();
?>
