<?php
$servername = "13.202.102.183";
$username = "root";
$password = "Galaxy@123";
$dbname = "zabbix";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$hosts = [];
$start_date = '';
$end_date = '';
$data = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $start_date = $_POST["start_date"];
    $end_date = $_POST["end_date"];
    $hosts = $_POST["hosts"] ?? [];

    $start_unix = strtotime($start_date . " 00:00:00");
    $end_unix = strtotime($end_date . " 23:59:59");

    $host_filter = "";
    if (!empty($hosts)) {
        $host_list = "'" . implode("','", $hosts) . "'";
        $host_filter = "AND h.host IN ($host_list)";
    }

    $query = "
        SELECT 
            h.host AS Hostname,
            COUNT(e.eventid) AS 'Total Alerts'
        FROM events e
        JOIN triggers t ON e.objectid = t.triggerid
        JOIN functions f ON f.triggerid = t.triggerid
        JOIN items i ON f.itemid = i.itemid
        JOIN hosts h ON h.hostid = i.hostid
        WHERE e.clock > UNIX_TIMESTAMP(NOW() - INTERVAL 7 DAY)
          AND e.value = 1
        $host_filter
        GROUP BY h.host
        ORDER BY 'Total Alerts' DESC
    ";

    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }

    if (isset($_POST["export_csv"])) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="host_alert_report.csv"');
        $output = fopen("php://output", "w");
        fputcsv($output, array_keys($data[0]));
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit();
    }
}
?>



<!DOCTYPE html>
<html>
<head>
    <title>Host Alert Report</title>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free/css/all.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1100px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 12px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-bottom: 30px;
        }
        label {
            font-weight: 600;
            margin-bottom: 6px;
        }
        input[type="date"],
        select {
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
            width: 100%;
        }
        .btn-container {
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: flex-start;
        }
        input[type="submit"] {
            background: #003366;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }
        input[type="submit"]:hover {
            background: #001a33;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px 15px;
            border: 1px solid #ddd;
            text-align: center;
        }
        th {
            background: #003366;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f9fbfd;
        }
        .styled-button {
        background-color: #003366;
        color: white;
        border: 1px solid #003366;
        padding: 10px 18px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: background-color 0.3s ease;
        }
        .styled-button:hover {
            background-color: #001a33;
            border-color: #001a33;
        }

    </style>
</head>
<body>
<div class="container">
    <h2>Host Alert Report</h2>
    <form method="POST">
        <div>
            <label for="start_date">Start Date:</label>
            <input type="date" name="start_date" required value="<?= $start_date ?>">
        </div>
        <div>
            <label for="end_date">End Date:</label>
            <input type="date" name="end_date" required value="<?= $end_date ?>">
        </div>
        <div>
            <label for="hosts">Select Hosts:</label>
            <select id="hosts" name="hosts[]" multiple="multiple" required>
                <?php
                $host_result = $conn->query("SELECT DISTINCT host FROM hosts ORDER BY host");
                while ($row = $host_result->fetch_assoc()) {
                    $selected = in_array($row["host"], $hosts) ? "selected" : "";
                    echo "<option value='{$row["host"]}' $selected>{$row["host"]}</option>";
                }
                ?>
            </select>
        </div>
        <div class="btn-container">
    <button type="submit" name="submit" class="styled-button">
        🔍 Generate Report
    </button>
    <?php if (!empty($data)): ?>
        <button type="submit" name="export_csv" class="styled-button">
            ⬇ Export to CSV
        </button>
    <?php endif; ?>
</div>

    </form>

    <?php if (!empty($data)): ?>
        <table id="reportTable">
            <thead>
                <tr>
                    <th>Hostname</th>
                    <th>Total Alerts</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['Hostname']) ?></td>
                        <td><?= htmlspecialchars($row['Total Alerts']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- JS Libraries -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script>
    $(document).ready(function () {
        $('#hosts').select2({
            placeholder: "Search and select hosts",
            allowClear: true
        });
        $('#reportTable').DataTable({
            paging: true,
            pageLength: 10,
            dom: 'Bfrtip',
            buttons: ['csvHtml5']
        });
    });
</script>
</body>
</html>
