<?php
$servername = "13.202.102.183";
$username = "root";
$password = "Galaxy@123";
$dbname = "zabbix";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$reportData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'];
    $hosts = $_POST['hosts'] ?? [];

    $hostFilter = '';
    if (!empty($hosts)) {
        $hostList = implode("','", array_map([$conn, 'real_escape_string'], $hosts));
        $hostFilter = "AND h.host IN ('$hostList')";
    }

    $sql = "
        SELECT 
            h.host AS Hostname,
            dr.name AS 'Discovery Rule',
            i.name AS 'Discovered Item'
        FROM items i
        JOIN hosts h ON h.hostid = i.hostid
        LEFT JOIN item_discovery id ON id.itemid = i.itemid
        LEFT JOIN items dr ON dr.itemid = id.parent_itemid
        WHERE i.flags = 4
        $hostFilter
        ORDER BY h.host, dr.name
        LIMIT 1000;
    ";

    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $reportData[] = $row;
        }
    }
}

$hostsResult = $conn->query("SELECT host FROM hosts WHERE status = 0 ORDER BY host");
$hostOptions = [];
if ($hostsResult) {
    while ($row = $hostsResult->fetch_assoc()) {
        $hostOptions[] = $row['host'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Discovery Report</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
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
        .btn-midnight:hover {
            background-color: #000066;
        }
        .select2-container--default .select2-selection--multiple {
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            padding: 0.375rem;
        }
        .select2-container .select2-selection--multiple .select2-search__field {
            width: auto !important;
        }
        .midnight-header {
        background-color: #191970;
        color: white;
    }

    </style>
</head>
<body>
<div class="container mt-4">
    <h3 class="text-center mb-4">Discovery Report</h3>
    <div class="card p-4">
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Start Date:</label>
                <input type="date" name="start_date" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">End Date:</label>
                <input type="date" name="end_date" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Select Hosts:</label>
                <select name="hosts[]" class="form-control select2" multiple>
                    <?php foreach ($hostOptions as $host): ?>
                        <option value="<?= htmlspecialchars($host) ?>"><?= htmlspecialchars($host) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-midnight">
                    <i class="fas fa-play-circle"></i> Generate Report
                </button>
            </div>
        </form>

        <?php if (!empty($reportData)): ?>
            <div class="d-grid mb-3">
                <button class="btn btn-midnight" onclick="exportCSV()">
                    <i class="fas fa-file-csv"></i> Export CSV
                </button>
            </div>
            <div class="table-responsive">
                <table id="reportTable" class="table table-bordered table-striped display" style="width:100%">
                    <thead class="midnight-header">
                        <tr>
                            <th>Host</th>
                            <th>Discovery Rule</th>
                            <th>Discovered Item</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reportData as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['Hostname']) ?></td>
                                <td><?= htmlspecialchars($row['Discovery Rule']) ?></td>
                                <td><?= htmlspecialchars($row['Discovered Item']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function () {
        $('#reportTable').DataTable({ pageLength: 10 });
        $('.select2').select2({ placeholder: "Select hosts", width: '100%' });
    });

    function exportCSV() {
        let csv = 'Host,Discovery Rule,Discovered Item\n';
        $('#reportTable tbody tr').each(function () {
            let row = [];
            $(this).find('td').each(function () {
                row.push('"' + $(this).text().trim().replace(/"/g, '""') + '"');
            });
            csv += row.join(',') + '\n';
        });

        const hiddenElement = document.createElement('a');
        hiddenElement.href = 'data:text/csv;charset=utf-8,' + encodeURI(csv);
        hiddenElement.target = '_blank';
        hiddenElement.download = 'discovery_report.csv';
        hiddenElement.click();
    }
</script>
</body>
</html>
