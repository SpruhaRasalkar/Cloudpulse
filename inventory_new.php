<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Zabbix API endpoint and credentials
$ZABBIX_URL = 'https://zabbixdemo.goapl.com/zabbix/api_jsonrpc.php';
$ZABBIX_USER = 'Spruha';           // Replace with your username
$ZABBIX_PASSWORD = 'Galaxy@123';      // Replace with your password

// Function to call the Zabbix API
function zabbix_api_call($method, $params = [], $auth = null) {
    global $ZABBIX_URL;

    $headers = ['Content-Type: application/json'];

    $data = [
        'jsonrpc' => '2.0',
        'method' => $method,
        'params' => $params,
        'id' => 1,
    ];

    if ($auth !== null) {
        $data['auth'] = $auth;
    }

    $ch = curl_init($ZABBIX_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Only for development

    $response = curl_exec($ch);
    if ($response === false) {
        die('Error in API request: ' . curl_error($ch));
    }

    curl_close($ch);

    $result = json_decode($response, true);
    if (isset($result['error'])) {
        die("API Error: " . json_encode($result['error']));
    }

    return $result;
}

// Authenticate and get token
$auth_response = zabbix_api_call('user.login', [
    'username' => $ZABBIX_USER,
    'password' => $ZABBIX_PASSWORD
]);

$AUTH_TOKEN = $auth_response['result'];

// Get all hosts with interfaces, groups, and items
$host_params = [
    'output' => ['hostid', 'name'],
    'selectInterfaces' => ['ip'],
    'selectGroups' => ['name'],
    'selectItems' => ['name', 'lastvalue'],
];

$hosts = zabbix_api_call('host.get', $host_params, $AUTH_TOKEN);

if (!isset($hosts['result']) || count($hosts['result']) === 0) {
    die("<div class='alert alert-warning'>No hosts found.</div>");
}

$data = [];
$counter = 1;

foreach ($hosts['result'] as $host) {
    $host_id = $host['hostid'];
    $ip_address = isset($host['interfaces'][0]['ip']) ? $host['interfaces'][0]['ip'] : "N/A";

    $hostgroup = "N/A";
    if (isset($host['groups']) && is_array($host['groups'])) {
        $hostgroup_names = array_map(fn($g) => $g['name'], $host['groups']);
        $hostgroup = !empty($hostgroup_names) ? implode(', ', $hostgroup_names) : "N/A";
    }

    $cpu_cores = "N/A";
    $total_memory = "N/A";
    $operating_system = "N/A";

    foreach ($host['items'] as $item) {
        if ($item['name'] === 'Number of cores') {
            $cpu_cores = $item['lastvalue'];
        } elseif ($item['name'] === 'Total memory') {
            $total_memory = round($item['lastvalue'] / 1073741824, 2) . ' GB';
        } elseif ($item['name'] === 'Operating system') {
            $operating_system = $item['lastvalue'];
        }
    }

    $data[] = [$counter, $host['name'], $hostgroup, $ip_address, $operating_system, $cpu_cores, $total_memory];
    $counter++;
}

// CSV download handler
if (isset($_GET['download_csv'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="Host_Inventory_report.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['No.', 'Host Name', 'Host Group', 'IP Address', 'Operating System', 'CPU Cores', 'Total Memory (GB)']);

    foreach ($data as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Host Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container-fluid mt-4">
    <h2 class="mb-3 text-center">Host Inventory</h2>

    <div class="d-flex align-items-center">
        <input type="text" id="searchInput" class="form-control me-2" placeholder="Search by Host Group">
        <a href="javascript:void(0);" onclick="downloadFilteredCSV();" class="btn btn-success">Download CSV</a>
    </div>

    <div class="mt-3">
        <table class="table table-striped table-bordered" id="reportTable">
            <thead class="table-dark">
                <tr>
                    <th>No.</th>
                    <th>Host Name</th>
                    <th>Host Group</th>
                    <th>IP Address</th>
                    <th>Operating System</th>
                    <th>CPU Cores</th>
                    <th>Total Memory (GB)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row[0]) ?></td>
                        <td><?= htmlspecialchars($row[1]) ?></td>
                        <td><?= htmlspecialchars($row[2]) ?></td>
                        <td><?= htmlspecialchars($row[3]) ?></td>
                        <td><?= htmlspecialchars($row[4]) ?></td>
                        <td><?= htmlspecialchars($row[5]) ?></td>
                        <td><?= htmlspecialchars($row[6]) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            document.getElementById("searchInput").addEventListener("keyup", filterTable);
        });

        function filterTable() {
            let input = document.getElementById("searchInput").value.toLowerCase();
            let rows = document.querySelectorAll("#reportTable tbody tr");

            rows.forEach(row => {
                let hostgroup = row.cells[2].textContent.toLowerCase();
                row.style.display = hostgroup.includes(input) ? "" : "none";
            });
        }

        function downloadFilteredCSV() {
            let table = document.getElementById("reportTable");
            let rows = table.querySelectorAll("tbody tr");
            let csvData = [];
            let headers = ['No.', 'Host Name', 'Host Group', 'IP Address', 'Operating System', 'CPU Cores', 'Total Memory (GB)'];
            csvData.push(headers.map(header => `"${header}"`).join(","));

            rows.forEach(row => {
                if (row.style.display !== "none") {
                    let rowData = [];
                    row.querySelectorAll("td").forEach(cell => {
                        rowData.push(`"${cell.textContent.trim()}"`);
                    });
                    csvData.push(rowData.join(","));
                }
            });

            if (csvData.length === 1) {
                alert("No matching records found for download!");
                return;
            }

            let csvBlob = new Blob([csvData.join("\n")], { type: "text/csv" });
            let csvUrl = URL.createObjectURL(csvBlob);

            let a = document.createElement("a");
            a.href = csvUrl;
            a.download = "HostInventory_report.csv";
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }
    </script>
</body>
</html>
