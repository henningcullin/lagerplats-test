<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "lagerplatser";  // Ensure this matches your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Define sample cities, sub-storage, and spare parts
$cities = ["Stockholm" => "st", "Göteborg" => "gb", "Malmö" => "ma", "Uppsala" => "up", "Örebro" => "or"];
$spareParts = [
    "Biltema Battery", "Wiper Blade", "Car Wash Soap", "Spark Plug", "Brake Pads", "Oil Filter", "Air Filter", 
    "Radiator Coolant", "Engine Oil", "Headlights", "Tail Lights", "Windshield Washer Fluid", "Car Wax", 
    "Tire Pressure Gauge", "Tire Inflator", "Seat Covers", "Floor Mats", "Car Vacuum", "USB Car Charger",
    "Car Mount Holder", "Jump Starter", "Tow Rope", "Jumper Cables", "Emergency Kit", "Wheel Cleaner"
];

// Prepare and execute the main storage locations and sample data insertions
function seedData($conn, $cities, $spareParts) {
    // Insert spare parts
    foreach ($spareParts as $part) {
        $stmt = $conn->prepare("INSERT INTO spareparts (name) VALUES (?)");
        $stmt->bind_param("s", $part);
        $stmt->execute();
        $stmt->close();
    }

    // Insert city storages and sub-storages with shelves
    foreach ($cities as $city => $code) {
        // Insert main city storage
        $stmt = $conn->prepare("INSERT INTO storages (name, parent) VALUES (?, NULL)");
        $stmt->bind_param("s", $city);
        $stmt->execute();
        $cityStorageId = $conn->insert_id;
        $stmt->close();

        // Insert sub-storages within each city storage
        for ($i = 1; $i <= rand(3, 5); $i++) {
            $subStorageName = "{$code}{$i}";
            $stmt = $conn->prepare("INSERT INTO storages (name, parent) VALUES (?, ?)");
            $stmt->bind_param("si", $subStorageName, $cityStorageId);
            $stmt->execute();
            $subStorageId = $conn->insert_id;
            $stmt->close();

            // Insert shelf storages within each sub-storage
            for ($j = 1; $j <= rand(4, 7); $j++) {
                $shelfName = "hylla{$j}";
                $stmt = $conn->prepare("INSERT INTO storages (name, parent) VALUES (?, ?)");
                $stmt->bind_param("si", $shelfName, $subStorageId);
                $stmt->execute();
                $shelfStorageId = $conn->insert_id;
                $stmt->close();

                // Assign random spare parts with quantities to each shelf
                $assignedParts = array_rand($spareParts, rand(4, 10));
                foreach ((array)$assignedParts as $partIndex) {
                    $partId = $partIndex + 1;  // Adjust index for database ID
                    $quantity = rand(1, 20);
                    $stmt = $conn->prepare("INSERT INTO storage_spareparts (storage, sparepart, quantity) VALUES (?, ?, ?)");
                    $stmt->bind_param("iii", $shelfStorageId, $partId, $quantity);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
    }
}

// Execute data seeding
seedData($conn, $cities, $spareParts);

// Close connection
$conn->close();
echo "Sample data inserted successfully!";
?>
