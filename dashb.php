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

// Fetch monitoring metrics per host
$metrics = [
    'CPU Utilization' => 'system.cpu.util',
    'Memory Utilization' => 'vm.memory.util',
    'Space Utilization' => 'vfs.fs.size[/,pused]'
    
];

$host_metrics = [];
foreach ($hosts as $host_id => $host_name) {
    foreach ($metrics as $metric_name => $key) {
        $sql = "SELECT history.value, history.clock FROM items 
                JOIN history ON items.itemid = history.itemid 
                WHERE items.key_ = '$key' AND items.hostid = $host_id 
                AND history.clock >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 5 DAY))
                ORDER BY history.clock ASC";
        $result = $conn->query($sql);
        
        while ($row = $result->fetch_assoc()) {
            $timestamp = date("Y-m-d", $row['clock']);
            $host_metrics[$host_id][$timestamp][$metric_name] = floatval($row['value']);
        }
    }
}
$conn->close();

// Remove hosts with no data
$host_metrics = array_filter($host_metrics);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galaxy Cloud Pulse - Monitoring Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/regression/2.0.1/regression.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; color: #333; }
        .container { display: flex; flex-wrap: wrap; justify-content: center; gap: 20px; padding: 20px; }
        .widget { width: 400px; height: 320px; background: #fff; padding: 15px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15); position: relative; }
        .widget-header { background-color: #003366; color: white; font-size: 18px; font-weight: bold; text-align: center; padding: 10px; border-radius: 10px 10px 0 0; position: relative; }
        .maximize-btn { position: absolute; right: 10px; top: 10px; background: white; border: none; cursor: pointer; font-size: 14px; padding: 5px; border-radius: 5px; }
        .maximize-btn:hover { background: #ddd; }
        .fullscreen { position: fixed !important; top: 0 !important; left: 0 !important; width: 100vw !important; height: 100vh !important; background: white !important; z-index: 1000 !important; padding: 20px !important; }
        .fullscreen canvas { width: 100% !important; height: 90% !important; }
        .alerts-container { margin-top: 20px; padding: 20px; }
        .alert-info { background-color: #e3f7ff; color: #0066cc; padding: 10px; border: 1px solid #0066cc; margin-top: 10px; border-radius: 5px; 
        .fullscreen {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    background: white !important;
    z-index: 1000 !important;
    padding: 20px !important;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

/* Style for Close Link */
.close-link {
    position: absolute;
    top: -25px;  /* Moves it above the header */
    left: 50%;  /* Centers it horizontally */
    transform: translateX(-50%);  /* Ensures perfect centering */
    font-size: 14px;
    color: red;
    text-decoration: none;
    font-weight: bold;
    background: white;
    padding: 5px 10px;
    border-radius: 5px;
    box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.2);
    display: block;
    text-align: center;
}

.close-link:hover {
    text-decoration: underline;
}





    </style>
</head>
<body>
    <h2>Galaxy Cloud Pulse - Monitoring Dashboard</h2>
    <div class="container">
        <?php foreach ($host_metrics as $host_id => $metrics): ?>
            <div class="widget" id="widget_<?php echo $host_id; ?>">

                <div class="widget-header"><?php echo htmlspecialchars($hosts[$host_id]); ?></div>
                <button class="export-btn" onclick="exportToCSV('<?php echo $host_id; ?>')">📥 Export CSV</button>
                 <button class="maximize-btn" onclick="maximizeGraph('<?php echo $host_id; ?>')">⛶</button>
                <canvas id="chart_<?php echo $host_id; ?>" width="380" height="250"></canvas>
            </div>
        <?php endforeach; ?>
    </div>
      <script>
        function exportToCSV(hostId) {
    let data = <?php echo json_encode($host_metrics); ?>;
    let hostData = data[hostId];
    if (!hostData) return;

    console.log("Host ID:", hostId);
    console.log("CPU Utilization Data:", JSON.stringify(hostData, null, 2));
    console.log("Predicting Next Values...");

    let csvContent = "Date,CPU Utilization,Memory Utilization,Space Utilization,Predicted CPU,Predicted Memory,Predicted Space\n";
    
    let dates = Object.keys(hostData);
    dates.sort();
    
    let predictedData = {
        'CPU Utilization': predictNextValues(hostData, 'CPU Utilization', 7),
        'Memory Utilization': predictNextValues(hostData, 'Memory Utilization', 7),
        'Space Utilization': predictNextValues(hostData, 'Space Utilization', 7)
    };

    dates.forEach(date => {
        let row = [date, hostData[date]['CPU Utilization'] ?? "", hostData[date]['Memory Utilization'] ?? "", hostData[date]['Space Utilization'] ?? "", "", "", ""].join(",");
        csvContent += row + "\n";
    });

    let lastDate = new Date(dates[dates.length - 1]);
    for (let i = 0; i < 7; i++) {
        lastDate.setDate(lastDate.getDate() + 1);
        let dateString = lastDate.toISOString().split('T')[0];
        let predictedRow = [dateString, "", "", "", predictedData['CPU Utilization'][i], predictedData['Memory Utilization'][i], predictedData['Space Utilization'][i]].join(",");
        csvContent += predictedRow + "\n";
    }

    let blob = new Blob([csvContent], { type: 'text/csv' });
    let link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = "host_" + hostId + "_metrics.csv";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}


        function predictNextValues(data, key, days) {
    let parsedData = Object.keys(data)
        .map(date => [new Date(date).getTime(), data[date][key] ?? null])
        .filter(d => d[1] !== null)
        .sort((a, b) => a[0] - b[0]);

    console.log(`\n--- Predicting for ${key} ---`);
    console.log("Parsed Data:", parsedData);

    if (parsedData.length < 2) {
        console.warn(`Insufficient data for ${key}, using fallback.`);
        return Array(days).fill(parsedData.length ? parsedData[0][1] : null);
    }

    // Normalize timestamps to make regression more effective
    let minTime = parsedData[0][0];
    let normalizedData = parsedData.map(([time, value]) => [(time - minTime) / 86400000, value]);

    console.log("Normalized Data for Regression:", normalizedData);

    let result = regression.linear(normalizedData);

    console.log("Regression Equation:", result.equation);

    let predictions = [];
    for (let i = 1; i <= days; i++) {
        let futureDay = (parsedData[parsedData.length - 1][0] - minTime) / 86400000 + i;
        let predictedValue = result.predict(futureDay)[1];
        predictions.push(predictedValue);
    }

    console.log("Predicted Values:", predictions);
    return predictions;
}


    </script>

   <script>
    function maximizeGraph(hostId) {
    let widget = document.getElementById("widget_" + hostId);
    let header = widget ? widget.querySelector(".widget-header") : null;

    console.log("Maximizing Graph for:", hostId);

    if (!widget) {
        console.warn("Widget not found for host:", hostId);
        return;
    }

    let isFullscreen = widget.classList.contains("fullscreen");

    if (!isFullscreen) {
        // Remove any existing close link
        let existingCloseLink = document.getElementById("close-link-" + hostId);
        if (existingCloseLink) {
            existingCloseLink.remove();
        }

        // Create close link
        let closeLink = document.createElement("a");
        closeLink.innerHTML = "Close";
        closeLink.classList.add("close-link");
        closeLink.setAttribute("id", "close-link-" + hostId);
        closeLink.href = "javascript:void(0);";
        closeLink.onclick = function () { maximizeGraph(hostId); };

        // Append close link before the header (above it)
        if (header) {
            header.parentNode.insertBefore(closeLink, header);
            console.log("Close link added above header!");
        } else {
            console.warn("Header not found inside widget for host:", hostId);
        }
    } else {
        // Remove close link on exit
        let closeLink = document.getElementById("close-link-" + hostId);
        if (closeLink) {
            closeLink.remove();
            console.log("Close link removed!");
        }
    }

    widget.classList.toggle("fullscreen");
}



</script>


    <div class="alerts-container" id="alertsContainer"></div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let alertsContainer = document.getElementById("alertsContainer");
            
            <?php foreach ($host_metrics as $host_id => $metrics): ?>
                var ctx = document.getElementById("chart_<?php echo $host_id; ?>").getContext("2d");
                var hostMetrics = <?php echo json_encode($metrics); ?>;
                var labels = Object.keys(hostMetrics);

                function movingAverage(data, windowSize) {
                    let result = [];
                    for (let i = 0; i < data.length; i++) {
                        if (i < windowSize - 1) {
                            result.push(null);
                        } else {
                            let sum = 0;
                            for (let j = 0; j < windowSize; j++) {
                                sum += data[i - j];
                            }
                            result.push(sum / windowSize);
                        }
                    }
                    return result;
                }

                function predictNextDays(data, days) {
                    let points = data.map((value, index) => [index, value]);
                    let result = regression.linear(points);
                    
                    let predictions = [];
                    let futureLabels = [];

                    let lastDate = new Date(labels[labels.length - 1]);

                    for (let i = 0; i < days; i++) {
                        let nextDate = new Date(lastDate);
                        nextDate.setDate(nextDate.getDate() + i + 1);
                        futureLabels.push(nextDate.toISOString().split("T")[0]);
                        predictions.push(result.predict(points.length + i)[1]);
                    }

                    return { futureLabels, predictions };
                }

                // Function to calculate the mean
                function calculateMean(data) {
                    const sum = data.reduce((acc, val) => acc + val, 0);
                    return sum / data.length;
                }

                // Function to calculate the standard deviation
                function calculateStandardDeviation(data, mean) {
                    const squaredDifferences = data.map(value => Math.pow(value - mean, 2));
                    const averageSquaredDifference = calculateMean(squaredDifferences);
                    return Math.sqrt(averageSquaredDifference);
                }

                // Function to detect anomalies based on Z-score
                function detectAnomalies(data) {
                    const mean = calculateMean(data);
                    const stdDev = calculateStandardDeviation(data, mean);
                    const threshold = 2; // Z-score threshold for anomaly detection
                    return data.map((value) => {
                        const zScore = (value - mean) / stdDev;
                        return Math.abs(zScore) > threshold ? 1 : 0; // 1 means anomaly
                    });
                }

                var datasets = [];
                var colors = {"CPU Utilization": "red", "Memory Utilization": "blue", "Space Utilization": "green"};
                
                var anomaliesDetected = false;
                var anomalyTimestamps = [];
                for (let metric in colors) {
                    var data = labels.map(date => hostMetrics[date][metric] ?? 0);
                    var movingAvg = movingAverage(data, 3);
                    var { futureLabels, predictions } = predictNextDays(data, 7);
                    var extendedLabels = [...labels, ...futureLabels];

                    // Detect anomalies in the data
                    var anomalies = detectAnomalies(data);

                    // If any anomaly is detected, set the flag
                    if (anomalies.includes(1)) {
                        anomaliesDetected = true;
                        // Capture the timestamps when anomalies were detected
                        anomalies.forEach((anomaly, index) => {
                            if (anomaly === 1) {
                                anomalyTimestamps.push(labels[index]);
                            }
                        });
                    }

                    // Prepare dataset for the chart
                    datasets.push({ 
                        label: metric, 
                        data: data, 
                        borderColor: colors[metric], 
                        borderWidth: 2, 
                        fill: false 
                    });
                    datasets.push({
                        label: metric + " Moving Avg", 
                        data: movingAvg, 
                        borderColor: colors[metric], 
                        borderDash: [5, 5], 
                        fill: false
                    });
                    datasets.push({
                        label: metric + " Prediction", 
                        data: [...Array(data.length).fill(null), ...predictions], 
                        borderColor: colors[metric], 
                        borderDash: [2, 2], 
                        fill: false
                    });

                    // Add anomaly markers to the chart
                    datasets.push({
                        label: metric + " Anomalies", 
                        data: anomalies.map((anomaly, index) => anomaly ? data[index] : null),
                        borderColor: "black", 
                        backgroundColor: "red", 
                        borderWidth: 3, 
                        pointRadius: 5, 
                        fill: false
                    });
                }

                // Create the chart
                new Chart(ctx, {
                    type: "line",
                    data: {
                        labels: extendedLabels, 
                        datasets: datasets
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { 
                            legend: { display: true, position: 'top', align: 'start' }
                        },
                        scales: {
                            y: {
                                beginAtZero: true, 
                                grid: { color: "#ddd" }
                            },
                            x: { grid: { color: "#eee" } }
                        }
                    }
                });

                // If anomalies are detected, show an alert and fetch related Zabbix alerts
                if (anomaliesDetected) {
                    $.ajax({
                        url: 'get_zabbix_alerts.php',
                        type: 'POST',
                        data: { 
                            host_id: <?php echo $host_id; ?>, 
                            timestamps: anomalyTimestamps 
                        },
                        success: function(response) {
                            if (response) {
                                alertsContainer.innerHTML = response;
                            } else {
                                alertsContainer.innerHTML = '<div class="alert-info">No Zabbix alerts found for anomalies.</div>';
                            }
                        },
                        error: function(xhr, status, error) {
                            alertsContainer.innerHTML = '<div class="alert">Error fetching Zabbix alerts.</div>';
                        }
                    });
                }
            <?php endforeach; ?>
        });
    </script>
</body>
</html>
