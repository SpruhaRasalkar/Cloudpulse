<?php
// Database connection details
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
$results = [];

$host_sql = "SELECT hostid, host FROM hosts WHERE status = 0 ORDER BY host ASC";
$host_result = $conn->query($host_sql);
while ($row = $host_result->fetch_assoc()) {
    $hosts[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_hosts = $_POST['hosts'] ?? [];
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';

    if (!empty($selected_hosts) && $start_date && $end_date) {
        $host_list = "'" . implode("','", array_map([$conn, 'real_escape_string'], $selected_hosts)) . "'";

        $start_ts = strtotime($start_date . " 00:00:00");
        $end_ts = strtotime($end_date . " 23:59:59");

        $query = "
            SELECT 
                h.host AS Hostname,
                i.name AS Interface,
                ROUND(AVG(his.value)/1048576, 2) AS `Avg Traffic (MB/s)`
            FROM history_uint his
            JOIN items i ON his.itemid = i.itemid
            JOIN hosts h ON h.hostid = i.hostid
            WHERE i.key_ LIKE 'net.if.in[%]'
              AND his.clock BETWEEN $start_ts AND $end_ts
              AND h.host IN ($host_list)
            GROUP BY h.host, i.name
            ORDER BY `Avg Traffic (MB/s)` DESC
        ";

        $result = $conn->query($query);
        while ($row = $result->fetch_assoc()) {
            $results[] = $row;
        }
    }

    if (isset($_POST['export_csv'])) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="interface_traffic_report.csv"');
        $fp = fopen('php://output', 'w');
        fputcsv($fp, ['Host', 'Interface', 'Avg Traffic (MB/s)']);
        foreach ($results as $r) {
            fputcsv($fp, $r);
        }
        fclose($fp);
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Interface Traffic Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css">
    <style>
        body { padding: 20px; background-color: #f8f9fa; }
        .select2-container--default .select2-selection--multiple { border: 1px solid #ced4da; border-radius: 0.375rem; }
        .btn-primary { background-color: #191970; border-color: #191970; }
    </style>
</head>
<body>
<div class="container shadow rounded p-4 bg-white">
    <h3 class="mb-4">Interface Traffic Report</h3>
    <form method="POST">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" id="start_date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>" required>
            </div>
            <div class="col-md-3">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" id="end_date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>" required>
            </div>
            <div class="col-md-4">
                <label for="hosts" class="form-label">Select Hosts</label>
                <select id="hosts" name="hosts[]" class="form-select" multiple required>
                    <?php foreach ($hosts as $host): ?>
                        <option value="<?= htmlspecialchars($host['host']) ?>" <?= (in_array($host['host'], $_POST['hosts'] ?? []) ? 'selected' : '') ?>><?= htmlspecialchars($host['host']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" name="generate" class="btn btn-primary w-100">Generate Report</button>
            </div>
        </div>
    </form>

    <?php if (!empty($results)): ?>
        <form method="POST" class="mt-3">
            <?php foreach ($_POST as $k => $v):
                if (is_array($v)) {
                    foreach ($v as $val) {
                        echo '<input type="hidden" name="' . htmlspecialchars($k) . '[]" value="' . htmlspecialchars($val) . '">';
                    }
                } else {
                    echo '<input type="hidden" name="' . htmlspecialchars($k) . '" value="' . htmlspecialchars($v) . '">';
                }
            endforeach; ?>
            <button type="submit" name="export_csv" class="btn btn-success mb-2">Export CSV</button>
        </form>

        <div class="table-responsive">
            <table class="table table-striped table-bordered" id="reportTable">
                <thead class="table-dark">
                    <tr>
                        <th>Host</th>
                        <th>Interface</th>
                        <th>Avg Traffic (MB/s)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['Hostname']) ?></td>
                            <td><?= htmlspecialchars($row['Interface']) ?></td>
                            <td><?= htmlspecialchars($row['Avg Traffic (MB/s)']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>

<script>
    $(document).ready(function () {
        $('#hosts').select2({ placeholder: "Select Hosts" });
        $('#reportTable').DataTable({
            pageLength: 10,
            dom: 'Bfrtip',
            buttons: [
                'csvHtml5',
                'pdfHtml5'
            ]
        });
    });
</script>
</body>
</html>
