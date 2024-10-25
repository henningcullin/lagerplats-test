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

// Get sparepart ID from query parameter
$sparepart_id = isset($_GET['id']) ? intval($_GET['id']) : 1; // Default to 1 if not set

// Query to fetch sparepart details
$sparepart_query = "SELECT * FROM spareparts WHERE id = ?";
$stmt = $conn->prepare($sparepart_query);
$stmt->bind_param("i", $sparepart_id);
$stmt->execute();
$sparepart = $stmt->get_result()->fetch_assoc();

// Query to get storage locations for this sparepart with hierarchy
$storage_query = "
    WITH RECURSIVE storage_hierarchy AS (
        SELECT 
            id AS storage_id,
            name AS storage_name,
            parent AS parent_id,
            CAST(name AS CHAR(255)) AS full_path
        FROM storages
        WHERE parent IS NULL
        UNION ALL
        SELECT 
            s.id AS storage_id,
            s.name AS storage_name,
            s.parent AS parent_id,
            CONCAT(sh.full_path, ' > ', s.name) AS full_path
        FROM storages s
        INNER JOIN storage_hierarchy sh ON s.parent = sh.storage_id
    )
    SELECT 
        sh.storage_id,
        sh.full_path,
        ssp.quantity
    FROM storage_hierarchy sh
    JOIN storage_spareparts ssp ON ssp.storage = sh.storage_id
    WHERE ssp.sparepart = ?
    ORDER BY sh.full_path
";

$stmt = $conn->prepare($storage_query);
$stmt->bind_param("i", $sparepart_id);
$stmt->execute();
$storage_locations = $stmt->get_result();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spare Part Details</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fomantic-ui/2.8.8/semantic.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fomantic-ui/2.8.8/semantic.min.js"></script>
    <style>
        .ui.divided.items .content {
            padding: 0.5em;
        }
    </style>
</head>

<body>
    <div class="ui container" style="margin-top: 40px;">
        <h2 class="ui header">Spare Part Details</h2>

        <!-- Tabs -->
        <div class="ui top attached tabular menu">
            <a class="item active" data-tab="details">Details</a>
            <a class="item" data-tab="locations">Storage Locations</a>
        </div>

        <div class="ui bottom attached tab segment active" data-tab="details">
            <div class="ui divided items">
                <div class="item" style="background-color: #f9f9f9;">
                    <div class="content">
                        <div class="header">ID</div>
                        <div class="description"><?= $sparepart['id'] ?></div>
                    </div>
                </div>
                <div class="item">
                    <div class="content">
                        <div class="header">Name</div>
                        <div class="description"><?= htmlspecialchars($sparepart['name']) ?></div>
                    </div>
                </div>
                <div class="item" style="background-color: #f9f9f9;">
                    <div class="content">
                        <div class="header">Description</div>
                        <div class="description"><?= htmlspecialchars($sparepart['description']) ?></div>
                    </div>
                </div>
                <div class="item">
                    <div class="content">
                        <div class="header">Created At</div>
                        <div class="description"><?= $sparepart['created_at'] ?></div>
                    </div>
                </div>
                <div class="item" style="background-color: #f9f9f9;">
                    <div class="content">
                        <div class="header">Updated At</div>
                        <div class="description"><?= $sparepart['updated_at'] ?></div>
                    </div>
                </div>
            </div>
        </div>


        <div class="ui bottom attached tab segment" data-tab="locations">
            <!-- Storage Locations Table -->
            <table class="ui celled table">
                <thead>
                    <tr>
                        <th>Storage Location</th>
                        <th>Quantity</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($location = $storage_locations->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="ui breadcrumb" style="user-select: none;">
                                    <?php
                                    $path_parts = explode(' > ', $location['full_path']);
                                    foreach ($path_parts as $index => $part):
                                        if ($index < count($path_parts) - 1): ?>
                                            <a class="section"><?= htmlspecialchars($part) ?></a>
                                            <div class="divider">/</div>
                                        <?php else: ?>
                                            <a class="active section"><?= htmlspecialchars($part) ?></a>
                                    <?php endif;
                                    endforeach; ?>
                                </div>
                            </td>
                            <td><?= $location['quantity'] ?></td>
                            <td class="center aligned">
                                <!-- Inleverans Button -->
                                <button class="basic circular ui icon button" data-tooltip="Inleverans" data-position="top center">
                                    <i class="plus icon"></i>
                                </button>

                                <!-- Uttag Button -->
                                <button class="basic circular ui icon button" data-tooltip="Uttag" data-position="top center">
                                    <i class="minus icon"></i>
                                </button>

                                <!-- Uttag Button -->
                                <button class="basic orange circular ui red icon button" data-tooltip="Redigera" data-position="top center">
                                    <i class="pen icon"></i>
                                </button>

                                <!-- Uttag Button -->
                                <button class="basic red circular ui red icon button" data-tooltip="Ta bort" data-position="top center">
                                    <i class="trash icon"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Fomantic UI Tabs
        $('.menu .item').tab();
    </script>
</body>

</html>