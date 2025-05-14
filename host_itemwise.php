<?php
// Database connection
$servername = "13.202.102.183";
$username = "root";
$password = "Galaxy@123";
$dbname = "zabbix";

$conn = new mysqli($servername, $username, $password, $dbname);

// Fetch active hosts for dropdown
$hostsResult = $conn->query("SELECT host FROM hosts WHERE status = 0 ORDER BY host");
$hostOptions = [];
if ($hostsResult) {
    while ($row = $hostsResult->fetch_assoc()) {
        $hostOptions[] = $row['host'];
    }
}

// Initialize
$reportData = [];
$startDate = $endDate = '';
$selectedHosts = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $startDate = $_POST["start_date"];
    $endDate = $_POST["end_date"];
    $selectedHosts = $_POST["hosts"] ?? [];

    if (!empty($startDate) && !empty($endDate) && !empty($selectedHosts)) {
        $start_unix = strtotime($startDate);
        $end_unix = strtotime($endDate);
        $hostPlaceholders = implode(',', array_fill(0, count($selectedHosts), '?'));

        $sql = "
            SELECT 
                h.host AS Hostname,
                i.name AS Item,
                ROUND(AVG(CASE WHEN e.value = 1 THEN 1 ELSE 0 END) * 100, 2) AS `Availability`,
                SEC_TO_TIME(SUM(CASE WHEN e.value = 0 THEN 1 ELSE 0 END)) AS `Downtime`,
                SEC_TO_TIME(SUM(CASE WHEN e.value = 1 THEN 1 ELSE 0 END)) AS `Uptime`
            FROM events e
            JOIN triggers t ON e.objectid = t.triggerid
            JOIN functions f ON f.triggerid = t.triggerid
            JOIN items i ON i.itemid = f.itemid
            JOIN hosts h ON h.hostid = i.hostid
            WHERE e.object = 0 
            AND e.clock BETWEEN ? AND ?
            AND h.host IN ($hostPlaceholders)
            GROUP BY h.host, i.name
            ORDER BY `Availability` ASC
        ";

        $stmt = $conn->prepare($sql);
        $types = "ii" . str_repeat("s", count($selectedHosts));
        $stmt->bind_param($types, $start_unix, $end_unix, ...$selectedHosts);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $reportData[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Host Item Wise Availability Report</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f8fd;
            font-family: 'Segoe UI', sans-serif;
        }
        .card {
            margin-top: 30px;
            border-radius: 15px;
            box-shadow: 0px 4px 12px rgba(0,0,0,0.1);
        }
        .btn-midnight {
            background-color: #191970;
            color: white;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.2em 0.8em;
        }
        .dataTables_wrapper .dataTables_length select {
            margin-left: 5px;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <h2 class="text-center mb-4">Host Item Wise Availability Report</h2>
    <form method="POST" class="card p-4">
        <div class="row mb-3">
            <div class="col-md-3">
                <label>Start Date:</label>
                <input type="date" name="start_date" class="form-control" required value="<?= htmlspecialchars($startDate) ?>">
            </div>
            <div class="col-md-3">
                <label>End Date:</label>
                <input type="date" name="end_date" class="form-control" required value="<?= htmlspecialchars($endDate) ?>">
            </div>
            <div class="col-md-4">
                <label>Select Hosts:</label>
                <select name="hosts[]" class="form-control" multiple required>
                    <?php foreach ($hostOptions as $host): ?>
                        <option value="<?= htmlspecialchars($host) ?>" <?= in_array($host, $selectedHosts) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($host) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-midnight w-100">Generate Report</button>
            </div>
        </div>
    </form>

    <?php if (!empty($reportData)): ?>
        <div class="card p-3 mt-4">
            <div class="d-flex justify-content-end mb-2">
                <button class="btn btn-midnight" onclick="exportCSV()">Export CSV</button>
            </div>
            <div class="table-responsive">
                <table id="reportTable" class="display table table-bordered table-striped" style="width:100%">
                    <thead>
                        <tr>
                            <th>Host Name</th>
                            <th>Item</th>
                            <th>Availability (%)</th>
                            <th>Down Time</th>
                            <th>Up Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reportData as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['Hostname']) ?></td>
                                <td><?= htmlspecialchars($row['Item']) ?></td>
                                <td><?= $row['Availability'] ?></td>
                                <td><?= $row['Downtime'] ?></td>
                                <td><?= $row['Uptime'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <div class="alert alert-warning mt-4 text-center">⚠️ No data found for the selected criteria.</div>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script>
    $(document).ready(function () {
        $('#reportTable').DataTable({
            pageLength: 10
        });
    });

    function exportCSV() {
        let csv = 'Host Name,Item,Availability (%),Down Time,Up Time\n';
        $('#reportTable tbody tr').each(function () {
            let row = [];
            $(this).find('td').each(function () {
                row.push('"' + $(this).text().trim().replace(/"/g, '""') + '"');
            });
            csv += row.join(',') + '\n';
        });

        let hiddenElement = document.createElement('a');
        hiddenElement.href = 'data:text/csv;charset=utf-8,' + encodeURI(csv);
        hiddenElement.target = '_blank';
        hiddenElement.download = 'host_item_availability_report.csv';
        hiddenElement.click();
    }
</script>
</body>
</html>
