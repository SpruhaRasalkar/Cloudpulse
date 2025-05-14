<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galaxy -Cloud Pulse</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            display: flex;
            height: 100vh;
        }
        .sidebar {
            width: 200px;
            background-color: #003366;
            color: white;
            padding: 20px;
            height: 100%;
            position: fixed;
        }
        .sidebar h2 {
            color: #fff;
        }
        .sidebar a {
            display: block;
            color: white;
            padding: 10px;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px 0;
        }
        .sidebar a:hover {
            background-color: #005599;
        }
        .container {
            margin-left: 220px; /* Space for sidebar */
            padding: 20px;
            flex-grow: 1;
            background-color: #f4f4f9;
        }
        iframe {
            width: 100%;
            height: calc(100vh - 40px); /* Adjust height to fit the viewport */
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2><img src="image.png" alt="GALAXY CLOUD PULSE" width="200" height="150"></h2>
        <a href="#" onclick="loadPage('dashb.php')">Dashboard</a>
        <a href="#" onclick="loadPage('resourceutilheat.php')">Heatmap</a>
        <a href="#" onclick="loadPage('Report.html')">Reports</a>
        <a href="#" onclick="loadPage('anomaly_reports.html')">Anomaly</a>
        <a href="#" onclick="loadPage('alerts.html')">Alerts</a>
    </div>
    
    <div class="container">
        <iframe id="contentFrame" src="dashb.php"></iframe>
    </div>

    <script>
        function loadPage(page) {
            document.getElementById('contentFrame').src = page;
        }
    </script>
</body>
</html>