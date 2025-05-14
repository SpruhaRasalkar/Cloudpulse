<?php
// Database connection details
$servername = "13.202.102.183";
$username = "root";
$password = "Galaxy@123";
$dbname = "zabbix";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all enabled hosts from database
$hosts = [];
$sql = "SELECT hostid, name FROM hosts WHERE status = 0";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $hosts[$row['hostid']] = $row['name'];
    }
}

// Fetch resource utilization data (CPU and Memory) for each host
$metrics = [
    'CPU Utilization' => 'system.cpu.util',
    'Memory Utilization' => 'vm.memory.util'
];

$host_metrics = [];
foreach ($hosts as $host_id => $host_name) {
    foreach ($metrics as $metric_name => $key) {
        $sql = "SELECT history.value, history.clock FROM items 
                JOIN history ON items.itemid = history.itemid 
                WHERE items.key_ = '$key' AND items.hostid = $host_id 
                AND history.clock >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 24 HOUR))
                ORDER BY history.clock ASC";
        $result = $conn->query($sql);
        
        while ($row = $result->fetch_assoc()) {
            $timestamp = date("Y-m-d H:i", $row['clock']);
            $host_metrics[$host_id][$timestamp][$metric_name] = floatval($row['value']);
        }
    }
}

$conn->close();

// Prepare data for the heatmap
$heatmap_data = [];
foreach ($host_metrics as $host_id => $metrics_data) {
    foreach ($metrics_data as $timestamp => $data) {
        $heatmap_data[] = [
            'time' => $timestamp,
            'hostid' => $host_id,
            'cpu' => $data['CPU Utilization'] ?? 0,
            'memory' => $data['Memory Utilization'] ?? 0
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zabbix Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/heatmap.js/2.0.2/heatmap.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; color: #333; }
        .container { display: flex; flex-wrap: wrap; justify-content: center; gap: 20px; padding: 20px; }
        .widget { width: 400px; height: 320px; background: #fff; padding: 15px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15); }
        .widget-header { background-color: #003366; color: white; font-size: 18px; font-weight: bold; text-align: center; padding: 10px; border-radius: 10px 10px 0 0; }
        h2 { text-align: center; color: #003366; }
        #heatmapContainer { width: 100%; height: 500px; }
    </style>
</head>
<body>
    <h2>Zabbix Dashboard - Resource Utilization Heatmap</h2>
    <div class="container">
        <?php foreach ($host_metrics as $host_id => $metrics): ?>
            <div class="widget">
                <div class="widget-header"><?php echo htmlspecialchars($hosts[$host_id]); ?></div>
                <canvas id="chart_<?php echo $host_id; ?>" width="380" height="250"></canvas>
            </div>
        <?php endforeach; ?>
    </div>
    
    <h2>Resource Utilization Heatmap</h2>
    <div id="heatmapContainer"></div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Generate the heatmap data from PHP
            const heatmapData = <?php echo json_encode($heatmap_data); ?>;
            
            // Convert data for heatmap.js
            const heatmapPoints = [];
            heatmapData.forEach(data => {
                // Adding CPU utilization data to the heatmap
                heatmapPoints.push({
                    x: data.time,  // Time as the X-axis
                    y: data.hostid,  // Host ID as Y-axis
                    value: data.cpu  // CPU utilization value
                });

                // Adding Memory utilization data to the heatmap
                heatmapPoints.push({
                    x: data.time,  // Time as the X-axis
                    y: data.hostid,  // Host ID as Y-axis
                    value: data.memory  // Memory utilization value
                });
            });

            // Initialize Heatmap.js
            const heatmapInstance = h337.create({
                container: document.getElementById('heatmapContainer')
            });

            // Set data to the heatmap
            heatmapInstance.setData({
                max: 100,  // Maximum value (CPU/Memory utilization in %)
                data: heatmapPoints
            });

            // Handle charts for each host's metric
            <?php foreach ($host_metrics as $host_id => $metrics): ?>
                var ctx = document.getElementById("chart_<?php echo $host_id; ?>").getContext("2d");
                var hostMetrics = <?php echo json_encode($metrics); ?>;
                var labels = Object.keys(hostMetrics);

                var datasets = [];
                var colors = {"CPU Utilization": "red", "Memory Utilization": "blue"};

                for (let metric in colors) {
                    var data = labels.map(date => hostMetrics[date][metric] ?? 0);
                    datasets.push({
                        label: metric,
                        data: data,
                        borderColor: colors[metric],
                        borderWidth: 2,
                        fill: false
                    });
                }

                new Chart(ctx, {
                    type: "line",
                    data: { labels: labels, datasets: datasets },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: true, position: 'top', align: 'start' } },
                        scales: { y: { beginAtZero: true, grid: { color: "#ddd" } }, x: { grid: { color: "#eee" } } }
                    }
                });
            <?php endforeach; ?>
        });
    </script>
</body>
</html>
