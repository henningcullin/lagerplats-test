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

// Recursive CTE query to fetch storage hierarchy and join with spareparts
$sql = "
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
        sh.storage_name,
        sh.full_path,
        sp.id AS sparepart_id,
        sp.name AS sparepart_name,
        sp.description AS sparepart_description,
        sp.created_at AS sparepart_created_at,
        sp.updated_at AS sparepart_updated_at,
        ssp.quantity,
        ssp.comments,
        s.created_at AS storage_created_at,
        s.updated_at AS storage_updated_at
    FROM storage_hierarchy sh
    JOIN storages s ON s.id = sh.storage_id
    JOIN storage_spareparts ssp ON ssp.storage = sh.storage_id
    JOIN spareparts sp ON sp.id = ssp.sparepart
    ORDER BY sh.full_path
";

$result = $conn->query($sql);

// Organize data into the required structure
$output = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $storageId = $row['storage_id'];
        $sparepartId = $row['sparepart_id'];

        // Initialize storage entry if it doesn't exist
        if (!isset($output[$storageId])) {
            $output[$storageId] = [
                "storage" => [
                    "id" => $row["storage_id"],
                    "name" => $row["storage_name"],
                    "created_at" => $row["storage_created_at"],
                    "updated_at" => $row["storage_updated_at"]
                ],
                "spareparts" => [],
                "storage_structure" => explode(' > ', $row["full_path"]) // Convert path to an array
            ];
        }

        // Add sparepart to the storage's spareparts array
        $output[$storageId]["spareparts"][$sparepartId] = [
            "id" => $sparepartId,
            "name" => $row["sparepart_name"],
            "quantity" => $row["quantity"],
            "comments" => $row["comments"],
            "created_at" => $row["sparepart_created_at"],
            "updated_at" => $row["sparepart_updated_at"]
        ];
    }
}

header('Content-Type: application/json; charset=utf-8');

// Output JSON
echo json_encode(array_values($output), JSON_UNESCAPED_UNICODE);

// Close connection
$conn->close();
?>
