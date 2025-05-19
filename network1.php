<?php
$servername = "13.202.102.183";
$username = "root";
$password = "Galaxy@123";
$dbname = "zabbix";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch host groups
$groupQuery = "
    SELECT DISTINCT g.name 
    FROM groups g
    JOIN hosts_groups hg ON hg.groupid = g.groupid
    JOIN hosts h ON h.hostid = hg.hostid
    WHERE h.status = 0
    ORDER BY g.name
";
$groupResult = $conn->query($groupQuery);

$hostGroups = [];

if (!$groupResult) {
    die("Host group query failed: " . $conn->error);
}

while ($row = $groupResult->fetch_assoc()) {
    $hostGroups[] = $row['name'];
}


// CSV Export
if (isset($_GET['export']) && $_GET['export'] === '1') {
    $startDate = $_GET['start_date'];
    $endDate = $_GET['end_date'];
    $hostGroups = $_GET['host_group'];

    if (empty($hostGroups)) exit('No host groups selected.');

    $inClause = "'" . implode("','", array_map([$conn, 'real_escape_string'], $hostGroups)) . "'";

    $sql = "
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
                          AND t2.clock > UNIX_TIMESTAMP('$startDate')
                          AND t2.clock < UNIX_TIMESTAMP('$endDate')
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
        JOIN hosts_groups hg ON hg.hostid = h.hostid
        JOIN `groups` g ON g.groupid = hg.groupid
        WHERE i.key_ LIKE 'net.if.in[%]'
          AND t.clock > UNIX_TIMESTAMP('$startDate')
          AND t.clock < UNIX_TIMESTAMP('$endDate')
          AND g.name IN ($inClause)
        GROUP BY h.host, i.name
    ";

    $result = $conn->query($sql);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=network_anomaly_report.csv');
    $output = fopen('php://output', 'w');

    if ($result->num_rows > 0) {
        $headers = array_keys($result->fetch_assoc());
        fputcsv($output, $headers);
        $result->data_seek(0);
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }
    } else {
        fputcsv($output, ['No data found.']);
    }
    fclose($output);
    exit;
}

// AJAX Data Fetch
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: application/json');
    $startDate = $_GET['start_date'];
    $endDate = $_GET['end_date'];
    $hostGroups = $_GET['host_group'];

    if (empty($hostGroups)) {
        echo json_encode([]);
        exit;
    }

    $inClause = "'" . implode("','", array_map([$conn, 'real_escape_string'], $hostGroups)) . "'";

    $sql = "
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
                          AND t2.clock > UNIX_TIMESTAMP('$startDate')
                          AND t2.clock < UNIX_TIMESTAMP('$endDate')
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
        JOIN hosts_groups hg ON hg.hostid = h.hostid
        JOIN `groups` g ON g.groupid = hg.groupid
        WHERE i.key_ LIKE 'net.if.in[%]'
          AND t.clock > UNIX_TIMESTAMP('$startDate')
          AND t.clock < UNIX_TIMESTAMP('$endDate')
          AND g.name IN ($inClause)
        GROUP BY h.host, i.name
    ";

    $result = $conn->query($sql);
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode($data);
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Network Anomaly Report</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f4f6fa; }
    .btn-primary, .table thead { background-color: #191970; color: white; }
    .btn-primary:hover { background-color: #000080; }
    .card { box-shadow: 0 0 10px rgba(0,0,0,0.1); }
  </style>
</head>
<body>
<div class="container mt-5">
  <div class="card p-4">
    <h3 class="text-center text-primary">Network Anomaly Report</h3>
    <form id="reportForm" class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Start Date</label>
        <input type="date" name="start_date" class="form-control" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">End Date</label>
        <input type="date" name="end_date" class="form-control" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Host Groups</label>
        <select name="host_group[]" class="form-select" multiple required>
          <?php foreach ($hostGroups as $group): ?>
            <option value="<?= htmlspecialchars($group) ?>"><?= htmlspecialchars($group) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 text-end">
        <button type="submit" class="btn btn-primary">Generate Report</button>
        <button type="button" class="btn btn-outline-primary" id="exportBtn">Export CSV</button>
      </div>
    </form>
  </div>

  <div class="card mt-4 p-4">
    <table class="table table-bordered table-hover" id="reportTable" style="display:none;">
      <thead>
        <tr>
          <th>Hostname</th>
          <th>Interface</th>
          <th>Avg Inbound MB</th>
          <th>Max Inbound MB</th>
          <th>Anomaly Time</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<script>
  const form = document.getElementById('reportForm');
  const exportBtn = document.getElementById('exportBtn');

  form.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(form);
    const params = new URLSearchParams();
    formData.forEach((value, key) => {
      params.append(key, value);
    });
    params.append('ajax', '1');

    fetch('?' + params.toString())
      .then(res => res.json())
      .then(data => {
        const tbody = document.querySelector('#reportTable tbody');
        tbody.innerHTML = '';
        if (data.length === 0) {
          tbody.innerHTML = '<tr><td colspan="6" class="text-center">No data found</td></tr>';
        } else {
          data.forEach(row => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
              <td>${row.Hostname}</td>
              <td>${row.Interface}</td>
              <td>${row['Avg Inbound MB']}</td>
              <td>${row['Max Inbound MB']}</td>
              <td>${row['Anomaly Time']}</td>
              <td>${row.Status}</td>
            `;
            tbody.appendChild(tr);
          });
        }
        document.getElementById('reportTable').style.display = 'table';
      });
  });

  exportBtn.addEventListener('click', () => {
    const formData = new FormData(form);
    const params = new URLSearchParams();
    formData.forEach((value, key) => {
      params.append(key, value);
    });
    params.append('export', '1');
    window.location.href = '?' + params.toString();
  });
</script>
</body>
</html>
