<?php
function generateCpuLoadReport($time_from, $time_till, $hostgroup_ids) {
    // Dummy data for demo. Replace this with Zabbix API/data logic.
    return [
        ['Host' => 'Server1', 'Avg CPU Load' => '15%', 'Max CPU Load' => '28%', 'Min CPU Load' => '5%'],
        ['Host' => 'Server2', 'Avg CPU Load' => '35%', 'Max CPU Load' => '60%', 'Min CPU Load' => '22%'],
    ];
}

function exportToCSV($data, $filename = "cpu_load_report.csv") {
    header('Content-Type: text/csv');
    header("Content-Disposition: attachment; filename=\"$filename\"");
    $output = fopen("php://output", "w");
    fputcsv($output, array_keys($data[0]));
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

if (isset($_GET['export']) && $_GET['export'] == 1) {
    $time_from = $_GET['time_from'];
    $time_till = $_GET['time_till'];
    $hostgroup_ids = explode(',', $_GET['hostgroup_ids']);
    $report_data = generateCpuLoadReport($time_from, $time_till, $hostgroup_ids);
    exportToCSV($report_data);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $time_from = $_POST['time_from'];
    $time_till = $_POST['time_till'];
    $hostgroup_ids = $_POST['hostgroup_ids'];
    $report_data = generateCpuLoadReport($time_from, $time_till, $hostgroup_ids);
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>CPU Load Report</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f4f4f4;
                padding: 20px;
            }
            .widget-container {
                background-color: #fff;
                padding: 30px;
                border-radius: 10px;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                max-width: 900px;
                margin: auto;
            }
            h2 {
                margin-bottom: 20px;
                color: #333;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }
            th, td {
                border: 1px solid #ccc;
                padding: 10px;
                text-align: center;
            }
            th {
                background-color: #f0f0f0;
            }
            .btn-secondary {
                background-color: #001f4d;
                color: #fff;
                padding: 12px;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                font-size: 16px;
            }
            .btn-secondary:hover {
                background-color: #000d33;
            }
        </style>
    </head>
    <body>
    <div class="widget-container">
        <h2><i class="fa-solid fa-microchip"></i> CPU Load Report</h2>
        <table>
            <tr>
                <?php foreach (array_keys($report_data[0]) as $heading): ?>
                    <th><?= $heading ?></th>
                <?php endforeach; ?>
            </tr>
            <?php foreach ($report_data as $row): ?>
                <tr>
                    <?php foreach ($row as $cell): ?>
                        <td><?= $cell ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </table>
        <form method="get">
            <input type="hidden" name="export" value="1">
            <input type="hidden" name="time_from" value="<?= htmlspecialchars($time_from) ?>">
            <input type="hidden" name="time_till" value="<?= htmlspecialchars($time_till) ?>">
            <input type="hidden" name="hostgroup_ids" value="<?= implode(',', $hostgroup_ids) ?>">
            <button type="submit" class="btn-secondary"><i class="fa-solid fa-file-csv"></i> Export to CSV</button>
        </form>
    </div>
    </body>
    </html>
    <?php
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>CPU Load Report</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f4f4f4;
                padding: 20px;
            }
            .widget-container {
                background-color: #fff;
                padding: 30px;
                border-radius: 10px;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                max-width: 600px;
                margin: auto;
            }
            h2 {
                margin-bottom: 20px;
                color: #333;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .form-container {
                display: flex;
                flex-direction: column;
                gap: 15px;
            }
            label {
                font-weight: bold;
            }
            .form-control {
                padding: 10px;
                font-size: 16px;
                border: 1px solid #ccc;
                border-radius: 8px;
            }
            .btn-primary {
                background-color: midnightblue;
                color: #fff;
                padding: 12px;
                font-size: 16px;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                margin-top: 10px;
            }
            .btn-primary:hover {
                background-color: darkblue;
            }
            small {
                color: #777;
                font-size: 13px;
            }
        </style>
    </head>
    <body>
    <div class="widget-container">
        <h2><i class="fa-solid fa-microchip"></i> CPU Load Report</h2>
        <form method="POST" class="form-container">
            <label for="time_from">Start Date</label>
            <input type="date" id="time_from" name="time_from" class="form-control" required>
            <small>Select the start date of the report</small>

            <label for="time_till">End Date</label>
            <input type="date" id="time_till" name="time_till" class="form-control" required>
            <small>Select the end date of the report</small>

            <label for="hostgroup_ids">Host Groups</label>
            <select id="hostgroup_ids" name="hostgroup_ids[]" multiple="multiple" class="form-control" required>
                <?php
                $hostgroups = [
                    ['groupid' => 1, 'name' => 'Web Servers'],
                    ['groupid' => 2, 'name' => 'Database Servers'],
                    ['groupid' => 3, 'name' => 'Application Servers']
                ];
                foreach ($hostgroups as $hostgroup): ?>
                    <option value="<?= $hostgroup['groupid']; ?>"><?= $hostgroup['name']; ?></option>
                <?php endforeach; ?>
            </select>
            <small>Hold Ctrl/Cmd to select multiple</small>

            <button type="submit" class="btn-primary"><i class="fa-solid fa-play"></i> Generate Report</button>
        </form>
    </div>
    </body>
    </html>
    <?php
}
?>
