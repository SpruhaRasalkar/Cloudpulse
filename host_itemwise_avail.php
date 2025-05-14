<?php
// Database connection
$servername = "13.202.102.183";
$username = "root";
$password = "Galaxy@123";
$dbname = "zabbix";

$conn = new mysqli($servername, $username, $password, $dbname);

// Fetch active hosts
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f8fd;
            font-family: 'Segoe UI', sans-serif;
        }
        .card {
            border-radius: 16px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 2rem;
            max-width: 900px;
            margin: auto;
        }
        .form-label {
            font-weight: 600;
        }
        .btn-midnight {
            background-color: #191970;
            color: white;
        }
        .btn-midnight:hover {
            background-color: #0f0f5f;
        }
        .form-select[multiple] {
            height: 120px;
        }
        .icon-title {
            font-size: 1.5rem;
        }
        .icon-title i {
            margin-right: 8px;
            color: #191970;
        }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="card mb-4">
        <h4 class="icon-title mb-4"><i class="fas fa-chart-line"></i> Host Item Wise Availability Report</h4>
        <form method="POST">
            <div class="mb-3">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" name="start_date" id="start_date" class="form-control" required value="<?= htmlspecialchars($startDate) ?>">
            </div>
            <div class="mb-3">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" name="end_date" id="end_date" class="form-control" required value="<?= htmlspecialchars($endDate) ?>">
            </div>
            <div class="mb-4">
                <label for="hosts" class="form-label">Select Hosts</label>
                <select name="hosts[]" id="hosts" class="form-select" multiple required>
                    <?php foreach ($hostOptions as $host): ?>
                        <option value="<?= htmlspecialchars($host) ?>" <?= in_array($host, $selectedHosts) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($host) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">Hold Ctrl/Cmd to select multiple</small>
            </div>
            <div class="d-grid gap-2">
                <button type="button" class="btn btn-midnight" onclick="exportCSV()"><i class="fas fa-file-csv"></i> Export to CSV</button>
                <button type="submit" class="btn btn-midnight"><i class="fas fa-chart-bar"></i> Generate Report</button>
            </div>
        </form>
    </div>

    <?php if (!empty($reportData)): ?>
        <div class="card">
            <div class="table-responsive">
                <table id="reportTable" class="display table table-bordered table-striped">
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
        <div class="alert alert-warning text-center mt-4">⚠️ No data found for the selected criteria.</div>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script>
    $(document).ready(function () {
        $('#reportTable').DataTable({ pageLength: 10 });
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
