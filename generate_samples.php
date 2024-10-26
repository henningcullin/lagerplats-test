<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Data Seeding</title>
    <style>
        #output {
            height: 95dvh;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 10px;
            font-family: Arial, sans-serif;
            font-size: 14px;
        }
    </style>
    <script>
        function scrollToBottom() {
            const outputDiv = document.getElementById('output');
            outputDiv.scrollTop = outputDiv.scrollHeight;
            document.querySelectorAll('script')?.forEach(tag => tag.remove());
        }
    </script>
</head>

<body>

    <div id="output"></div>

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
    $cities = [
        "Stockholm" => "st",
        "Göteborg" => "gb",
        "Malmö" => "ma",
        "Uppsala" => "up",
        "Örebro" => "or",
        "Halmstad" => "ha",
        "Glumslöv" => "gl",
        "Helsingborg" => "he",
        "Jönköping" => "jo",
        "Västerås" => "va",
        "Borås" => "bo",
        "Kalmar" => "ka",
        "Gävle" => "ga"
    ];

    $spare_parts = [
        "Bilbatteri",
        "Torkarblad",
        "Bilschampo",
        "Tändstift",
        "Bromsbelägg",
        "Oljefilter",
        "Luftfilter",
        "Kylvätska",
        "Motorolja",
        "Strålkastare",
        "Baklyktor",
        "Spolarvätska",
        "Bilvax",
        "Däcktrycksmätare",
        "Däckpump",
        "Sätesöverdrag",
        "Gummimattor",
        "Bilvakuum",
        "USB-laddare",
        "Mobilhållare",
        "Starthjälp",
        "Bogserlina",
        "Startkablar",
        "Nödkit",
        "Fälgrengöring",
        "Polermedel",
        "Fönsterrengöring",
        "Kraftiga arbetslampor",
        "Backkamera",
        "Bärarm",
        "Fjäder",
        "Spännrulle",
        "Generator",
        "Avgassystem",
        "Handbromsvajer",
        "Kylarvätska",
        "Bränslefilter",
        "Kupéfilter",
        "Vevaxelgivare",
        "Kylfläkt",
        "Bränslepump",
        "Vindrutetorkarmotor",
        "Stötdämpare",
        "Styrled",
        "Växellådsolja",
        "ABS-sensor",
        "Dörrhandtag",
        "Sidospeglar",
        "Fläktrem",
        "Hjullager",
        "Kopplingssats",
        "Katalysator",
        "Bakluckefjäder",
        "Takbox",
        "Reservhjul",
        "Dragkrok",
        "Hjulsidor",
        "Startmotor",
        "Bränsletank",
        "Antenn",
        "Bromsskivor",
        "Bromsvätska",
        "Krockkudde",
        "Kupévärmare",
        "Tändkablar",
        "Stänkskydd",
        "Mönsterdjupmätare",
        "Kompressor",
        "Arbetsbelysning",
        "Varningsskylt",
        "Snökedjor",
        "Batteritestare",
        "Bultcirkelmätare",
        "Skruvtving",
        "Teleskopskaft",
        "Fälgborste",
        "Verktygssats",
        "Pannlampa",
        "Multifunktionstång",
        "Skyddsglasögon",
        "Mikrofiberduk",
        "Lackstift",
        "Bromsljus"
    ];

    // Prepare and execute the main storage locations and sample data insertions
    function generate_data($conn, $cities, $spare_parts)
    {
        // Insert spare parts
        foreach ($spare_parts as $part) {
            $stmt = $conn->prepare("INSERT INTO spareparts (name) VALUES (?)");
            $stmt->bind_param("s", $part);
            $stmt->execute();
            $stmt->close();

            flush_message("INSERTED SPAREPART, " . $part);
        }

        // Insert city storages and sub-storages with shelves
        foreach ($cities as $city => $code) {
            // Insert main city storage
            $stmt = $conn->prepare("INSERT INTO storages (name, parent) VALUES (?, NULL)");
            $stmt->bind_param("s", $city);
            $stmt->execute();
            $cityStorageId = $conn->insert_id;
            $stmt->close();

            flush_message("INSERTED CITY STORAGE, " . $city);

            // Insert sub-storages within each city storage
            for ($i = 1; $i <= rand(2, 6); $i++) {
                $subStorageName = "{$code}{$i}";
                $stmt = $conn->prepare("INSERT INTO storages (name, parent) VALUES (?, ?)");
                $stmt->bind_param("si", $subStorageName, $cityStorageId);
                $stmt->execute();
                $subStorageId = $conn->insert_id;
                $stmt->close();

                flush_message("INSERTED WAREHOUSE STORAGE, " . $subStorageName . " INTO CITY, " . $city);

                // Insert shelf storages within each sub-storage
                for ($j = 1; $j <= rand(15, 25); $j++) {
                    $shelfName = "hylla{$j}";
                    $stmt = $conn->prepare("INSERT INTO storages (name, parent) VALUES (?, ?)");
                    $stmt->bind_param("si", $shelfName, $subStorageId);
                    $stmt->execute();
                    $shelfStorageId = $conn->insert_id;
                    $stmt->close();

                    // Assign random spare parts with quantities to each shelf
                    $assignedParts = array_rand($spare_parts, rand(43, 64));
                    foreach ((array)$assignedParts as $partIndex) {
                        $partId = $partIndex + 1;  // Adjust index for database ID
                        $quantity = rand(4, 526);
                        $stmt = $conn->prepare("INSERT INTO storage_spareparts (storage, sparepart, quantity) VALUES (?, ?, ?)");
                        $stmt->bind_param("iii", $shelfStorageId, $partId, $quantity);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }
        }
    }

    ob_implicit_flush(true);
    ob_end_flush();

    // Helper function to flush output
    function flush_message($message)
    {
        echo "<script>document.getElementById('output').innerHTML += '$message<br>';</script>";
        echo "<script>scrollToBottom();</script>"; // Call the scroll function
        flush(); // Flush system-level output buffers
    }

    set_time_limit(600); // needed for my harddrive :P

    // Execute data seeding
    generate_data($conn, $cities, $spare_parts);

    // Close connection
    $conn->close();

    flush_message("SUCCESSFULLY INSERTED ALL SAMPLE DATA");

    ?>
</body>

</html>