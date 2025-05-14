<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Database credentials
$servername = "13.202.102.183";
$username = "root";
$password = "Galaxy@123";
$dbname = "zabbix";
$port = 3306;

// Create database connection
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

// Default time filter
$filter_type = $_REQUEST['filter_type'] ?? 'last_3_hours';

// Time intervals for predefined filters
$time_intervals = [
    'last_1_hour' => '3600',
    'last_3_hours' => '10800',
    'last_12_hours' => '43200',
    'last_1_day' => '86400',
    'last_1_week' => '604800'
];

// SQL Filter Logic
$current_time = time();

// Check if a custom time range is selected
if ($filter_type === 'custom' && isset($_REQUEST['custom_start']) && isset($_REQUEST['custom_end'])) {
    $custom_start = $_REQUEST['custom_start'];
    $custom_end = $_REQUEST['custom_end'];

    $start_timestamp = strtotime($custom_start);
    $end_timestamp = strtotime($custom_end);

    if ($start_timestamp && $end_timestamp && $start_timestamp < $end_timestamp) {
        $sql_filter = "AND clock BETWEEN $start_timestamp AND $end_timestamp";
    } else {
        $sql_filter = "AND clock >= ($current_time - 10800)"; // Default to last 3 hours if invalid input
    }
} else {
    // Apply predefined time filters
    $sql_filter = isset($time_intervals[$filter_type])
        ? "AND clock >= ($current_time - " . $time_intervals[$filter_type] . ")"
        : "AND clock >= ($current_time - 10800)";
}

// SQL Query to fetch filtered data
$sql = "SELECT 
            client_ip, 
            COUNT(*) AS unique_request_count,
            FROM_UNIXTIME(MAX(clock)) AS last_hit
        FROM (
            SELECT 
                SUBSTRING_INDEX(SUBSTRING_INDEX(value, ' ', 9), ' ', -1) AS client_ip, 
                clock
            FROM history_log  
            WHERE itemid = '106519'
        ) AS extracted_ips
        WHERE client_ip REGEXP '^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$'
        $sql_filter
        GROUP BY client_ip
        ORDER BY unique_request_count DESC;";

$result = $conn->query($sql);

// Store data for display
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [$row["client_ip"], $row["unique_request_count"], $row["last_hit"]];
}

// Handle CSV Download Request
if (isset($_GET['download']) && $_GET['download'] == 'csv') {
    $filter_type = $_GET['filter_type'] ?? 'last_3_hours';
    $sql_filter = "";

    if ($filter_type === 'custom' && isset($_GET['custom_start']) && isset($_GET['custom_end'])) {
        $custom_start = $_GET['custom_start'];
        $custom_end = $_GET['custom_end'];

        $start_timestamp = strtotime($custom_start);
        $end_timestamp = strtotime($custom_end);

        if ($start_timestamp && $end_timestamp && $start_timestamp < $end_timestamp) {
            $sql_filter = "AND clock BETWEEN $start_timestamp AND $end_timestamp";
        } else {
            $sql_filter = "AND clock >= ($current_time - 10800)"; // Default to last 3 hours if invalid input
        }
    } else {
        $sql_filter = isset($time_intervals[$filter_type])
            ? "AND clock >= ($current_time - " . $time_intervals[$filter_type] . ")"
            : "AND clock >= ($current_time - 10800)";
    }

    // SQL Query for CSV Download
    $sql = "SELECT 
                client_ip, 
                COUNT(*) AS unique_request_count,
                FROM_UNIXTIME(MAX(clock)) AS last_hit
            FROM (
                SELECT 
                    SUBSTRING_INDEX(SUBSTRING_INDEX(value, ' ', 9), ' ', -1) AS client_ip, 
                    clock
                FROM history_log  
                WHERE itemid = '106519'
            ) AS extracted_ips
            WHERE client_ip REGEXP '^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$'
            $sql_filter
            GROUP BY client_ip
            ORDER BY unique_request_count DESC;";

    $result = $conn->query($sql);

    // Set CSV headers
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="request_count.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Sr. No.', 'Client IP', 'Unique Request Count', 'Last Hit']);

    $sr_no = 1;
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [$sr_no++, $row["client_ip"], $row["unique_request_count"], $row["last_hit"]]);
    }

    fclose($output);
    $conn->close();
    exit;
}
    

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timestamp Filter with CSV Download</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
        }

        /* Flex container for filter buttons and download button */
        .filter-container {
            display: flex;
            align-items: center;
            justify-content: space-between; /* Puts the timestamp filter on the left and download button on the right */
            margin-bottom: 20px;
            padding: 10px;
        }

        /* Timestamp filter box */
        .timestamp-container { 
            display: flex; 
            border: 1px solid #ccc; 
            border-radius: 5px; 
            padding: 8px; 
            background: white; 
            gap: 5px; /* Spacing between buttons */
        }

        /* Timestamp buttons */
        .timestamp-container button { 
            border: none; 
            background: none; 
            padding: 8px 15px; 
            font-size: 16px; 
            cursor: pointer;
            transition: all 0.2s ease-in-out;
        }

        .timestamp-container button.active { 
            font-weight: bold; 
            color: #191970; 
        }

        /* Custom date picker icon */
        .custom-icon { 
            margin-left: 5px; 
            font-size: 14px; 
        }

        /* CSV Download Button */
        .download-btn { 
            padding: 10px 15px; 
            font-size: 16px; 
            background-color: #1a7f37; 
            color: white; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            white-space: nowrap; /* Ensures button stays in one line */
        }

        .download-btn:hover { 
            background-color: #14652b; 
        }

        /* Table container */
        .table-container { 
            margin-top: 20px; 
        }

        /* Table header */
        .table th { 
            background-color: #191970; 
            color: white; 
            text-align: center; 
            padding: 10px; 
        }

        /* Table body */
        .table td { 
            text-align: center; 
            padding: 8px; 
        }


    </style>
</head>
<body>

<div class="filter-container">
    <h2 class="text-center mt-3">Request Count</h2>

    <!-- Timestamp Filter UI -->
    <form method="POST" class="timestamp-container">
        <button type="submit" name="filter_type" value="last_1_hour" class="<?= $filter_type == 'last_1_hour' ? 'active' : '' ?>">1h</button>
        <button type="submit" name="filter_type" value="last_3_hours" class="<?= $filter_type == 'last_3_hours' ? 'active' : '' ?>">3h</button>
        <button type="submit" name="filter_type" value="last_12_hours" class="<?= $filter_type == 'last_12_hours' ? 'active' : '' ?>">12h</button>
        <button type="submit" name="filter_type" value="last_1_day" class="<?= $filter_type == 'last_1_day' ? 'active' : '' ?>">1d</button>
        <button type="submit" name="filter_type" value="last_1_week" class="<?= $filter_type == 'last_1_week' ? 'active' : '' ?>">1w</button>
        <button type="button" class="<?= $filter_type == 'custom' ? 'active' : '' ?>" data-bs-toggle="modal" data-bs-target="#customModal">
            Custom <span class="custom-icon">&#128197;</span>
        </button>
    </form>

    <!-- CSV Download Button (Now includes custom time values dynamically) -->
    <!-- CSV Download Button (Now includes custom time values dynamically) -->
    <a id="csvDownloadLink" href="?download=csv&filter_type=last_3_hours" class="btn btn-success download-btn">Download CSV</a>

</div>  

    <!-- Table Results -->
    <div class="table-container">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Sr. No.</th>
                    <th>Client IP</th>
                    <th>Unique Request Count</th>
                    <th>Last Hit</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $sr_no = 1;
                foreach ($data as $row): ?>
                    <tr>
                        <td><?= $sr_no++ ?></td>
                        <td><?= htmlspecialchars($row[0]) ?></td>
                        <td><?= htmlspecialchars($row[1]) ?></td>
                        <td><?= htmlspecialchars($row[2]) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<!-- Custom Time Range Modal -->
<div class="modal fade" id="customModal" tabindex="-1" aria-labelledby="customModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="customModalLabel">Select Custom Time Range</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <label for="custom_start">Start Time:</label>
                    <input type="text" id="custom_start" name="custom_start" class="form-control datetimepicker" required>

                    <label for="custom_end" class="mt-2">End Time:</label>
                    <input type="text" id="custom_end" name="custom_end" class="form-control datetimepicker" required>

                    <input type="hidden" name="filter_type" value="custom">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Apply Filter</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Initialize flatpickr for datetime input fields
        flatpickr(".datetimepicker", {
            enableTime: true,
            dateFormat: "Y-m-d H:i:S",
            time_24hr: true,
            defaultDate: new Date()
        });

        // Function to update CSV download link
        function updateCSVDownloadLink() {
            let filterType = document.querySelector("input[name='filter_type']").value;
            let csvDownloadLink = document.getElementById("csvDownloadLink");

            if (filterType === "custom") {
                let startTime = document.getElementById("custom_start").value;
                let endTime = document.getElementById("custom_end").value;

                if (startTime && endTime) {
                    csvDownloadLink.href = `?download=csv&filter_type=custom&custom_start=${encodeURIComponent(startTime)}&custom_end=${encodeURIComponent(endTime)}`;
                }
            }
        }

        // Update CSV link when form is submitted
        document.querySelector("form").addEventListener("submit", function () {
            updateCSVDownloadLink();
        });

        // Update CSV link when custom time is selected
        document.getElementById("custom_start").addEventListener("change", updateCSVDownloadLink);
        document.getElementById("custom_end").addEventListener("change", updateCSVDownloadLink);
    });
</script>



</body>
</html>

