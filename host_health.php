<?php
// Database connection
$servername = "13.202.102.183";
$username = "root";
$password = "Galaxy@123";
$dbname = "zabbix";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Default values
$start_date = date('Y-m-01');
$end_date = date('Y-m-d');
$selected_hosts = [];
$report_data = [];

// Fetch hosts
$hosts_result = $conn->query("SELECT DISTINCT host FROM hosts WHERE status = 0");
$hosts = [];
while ($row = $hosts_result->fetch_assoc()) {
    $hosts[] = $row['host'];
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $selected_hosts = $_POST['hosts'] ?? [];

    if (!empty($selected_hosts)) {
        $host_list = implode("','", array_map([$conn, 'real_escape_string'], $selected_hosts));

        $start_ts = strtotime($start_date);
        $end_ts = strtotime($end_date . ' 23:59:59');

        $sql = "
            SELECT
                h.host AS Service,
                t.description AS Component,
                FROM_UNIXTIME(MAX(e.clock)) AS 'Last Issue',
                COUNT(*) AS 'Total Events',
                SUM(CASE WHEN e.value = 1 THEN 1 ELSE 0 END) AS 'Times Problematic'
            FROM events e
            JOIN triggers t ON e.objectid = t.triggerid
            JOIN functions f ON f.triggerid = t.triggerid
            JOIN items i ON f.itemid = i.itemid
            JOIN hosts h ON h.hostid = i.hostid
            WHERE e.clock BETWEEN $start_ts AND $end_ts
              AND h.host IN ('$host_list')
            GROUP BY h.host, t.description
            ORDER BY `Times Problematic` DESC
        ";

        $result = $conn->query($sql);
        while ($row = $result->fetch_assoc()) {
            $report_data[] = [
                'Service' => $row['Service'],
                'Component' => $row['Component'],
                'LastIssue' => $row['Last Issue'],
                'TotalEvents' => $row['Total Events'],
                'TimesProblematic' => $row['Times Problematic']
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Host Health Summary</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
    <style>
        body {
            padding: 40px;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 1100px;
        }
        .table th {
            background-color: #dc3545;
            color: white;
        }
        .form-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0px 2px 8px rgba(0, 0, 0, 0.1);
        }
        h2 {
            margin-bottom: 25px;
        }
        .select2-container .select2-selection--multiple {
            height: auto !important;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="form-section">
        <h2 class="text-center">🔴 Host Health Summary</h2>
        <form method="POST">
            <div class="row mb-3">
                <div class="col">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" required value="<?= $start_date ?>">
                </div>
                <div class="col">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" required value="<?= $end_date ?>">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Select Hosts</label>
                <select name="hosts[]" class="form-select select2-multi" multiple required>
                    <?php foreach ($hosts as $host): ?>
                        <option value="<?= htmlspecialchars($host) ?>" <?= in_array($host, $selected_hosts) ? "selected" : "" ?>>
                            <?= htmlspecialchars($host) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-danger w-100">Generate Report</button>
        </form>
    </div>

    <?php if (!empty($report_data)): ?>
        <div class="mt-5">
            <h4 class="text-center mb-3">📝 Host Health Report</h4>
            <div class="table-responsive">
                <table id="healthTable" class="table table-bordered table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Host</th>
                            <th>Component</th>
                            <th>Last Issue</th>
                            <th>Total Events</th>
                            <th>Times Problematic</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report_data as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['Service']) ?></td>
                                <td><?= htmlspecialchars($row['Component']) ?></td>
                                <td><?= htmlspecialchars($row['LastIssue']) ?></td>
                                <td><?= $row['TotalEvents'] ?></td>
                                <td><?= $row['TimesProblematic'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php elseif ($_SERVER["REQUEST_METHOD"] == "POST"): ?>
        <div class="alert alert-warning mt-4 text-center">⚠️ No data found for the selected criteria.</div>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- DataTables & Buttons -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

<script>
    $(document).ready(function() {
        $('.select2-multi').select2({
            placeholder: "Select hosts",
            width: '100%'
        });

        $('#healthTable').DataTable({
            pageLength: 10,
            lengthChange: false,
            ordering: true,
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'csvHtml5',
                    className: 'btn btn-outline-primary btn-sm',
                    text: 'Export CSV'
                },
                {
                    extend: 'pdfHtml5',
                    className: 'btn btn-outline-danger btn-sm',
                    text: 'Export PDF',
                    orientation: 'landscape',
                    pageSize: 'A4'
                }
            ]
        });
    });
</script>
</body>
</html>
