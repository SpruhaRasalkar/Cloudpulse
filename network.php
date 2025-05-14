<?php
// Database connection
$servername = "13.126.187.154";
$username = "zabbix";
$password = "G@laxy@123";
$dbname = "zabbix";
$conn = new mysqli($servername, $username, $password, $dbname);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Network Anomaly Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 30px;
            background-color: #f4f7fa;
        }
        .card {
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        h2 {
            color: #1a1a1a;
        }
        .btn-midnight {
            background-color: #191970;
            color: white;
        }
        .btn-midnight:hover {
            background-color: #0f0f5f;
            color: white;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card p-4">
        <h2 class="mb-4">Network Anomaly Report</h2>
        <form method="POST">
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Select Hosts</label>
                    <select name="hosts[]" class="form-select" multiple required>
                        <?php
                        $host_query = $conn->query("SELECT DISTINCT host FROM hosts ORDER BY host");
                        while ($row = $host_query->fetch_assoc()) {
                            echo "<option value=\"".$row['host']."\">".$row['host']."</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            <button type="submit" name="generate" class="btn btn-midnight">Generate</button>
        </form>
    </div>

    <?php if (isset($_POST['generate'])): ?>
    <div class="card mt-5 p-4">
        <h4 class="mb-3">Results</h4>
        <table class="table table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Host</th>
                    <th>Interface</th>
                    <th>Avg Inbound MB</th>
                    <th>Max Inbound MB</th>
                    <th>Time</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $start = strtotime($_POST['start_date']);
                $end = strtotime($_POST['end_date']) + 86399;
                $selected_hosts = implode("','", $_POST['hosts']);

                $query = "
                SELECT 
                    h.host AS Hostname,
                    i.name AS Interface,
                    ROUND(AVG(t.value_avg), 2) AS 'Avg Inbound MB',
                    MAX(t.value_avg) AS 'Max Inbound MB',
                    FROM_UNIXTIME(
                        MAX(CASE 
                            WHEN t.value_avg = (
                                SELECT MAX(t2.value_avg)
                                FROM trends_uint t2
                                WHERE t2.itemid = t.itemid
                                  AND t2.clock BETWEEN $start AND $end
                            ) THEN t.clock 
                            ELSE NULL 
                        END)
                    ) AS 'Anomaly Time',
                    CASE 
                        WHEN MAX(t.value_avg) > (AVG(t.value_avg) * 1.5) THEN 'Anomaly'
                        ELSE 'Normal'
                    END AS Status
                FROM trends_uint t
                JOIN items i ON i.itemid = t.itemid
                JOIN hosts h ON h.hostid = i.hostid
                WHERE i.key_ LIKE 'net.if.in[%]'
                  AND h.host IN ('$selected_hosts')
                  AND t.clock BETWEEN $start AND $end
                GROUP BY h.host, i.name
                ";

                $result = $conn->query($query);
                $rows = [];
                while ($row = $result->fetch_assoc()) {
                    $rows[] = $row;
                    echo "<tr>
                            <td>{$row['Hostname']}</td>
                            <td>{$row['Interface']}</td>
                            <td>{$row['Avg Inbound MB']}</td>
                            <td>{$row['Max Inbound MB']}</td>
                            <td>{$row['Anomaly Time']}</td>
                            <td>{$row['Status']}</td>
                          </tr>";
                }

                // CSV Export
                $csv_file = "network_anomaly_report_" . date("YmdHis") . ".csv";
                $f = fopen($csv_file, 'w');
                fputcsv($f, ['Host', 'Interface', 'Avg Inbound MB', 'Max Inbound MB', 'Time', 'Status']);
                foreach ($rows as $line) {
                    fputcsv($f, $line);
                }
                fclose($f);
                ?>
            </tbody>
        </table>
        <a href="<?php echo $csv_file; ?>" class="btn btn-success">Download CSV</a>
    </div>
    <?php endif; ?>
</div>

</body>
</html>
