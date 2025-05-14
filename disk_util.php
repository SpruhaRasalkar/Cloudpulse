<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Disk Utilization Report</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: #f9fafb;
            margin: 0;
            padding: 40px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
        }

        .card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            padding: 30px 40px;
            max-width: 700px;
            width: 100%;
        }

        h2 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 25px;
            color: #1e3a8a;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        h2 i {
            color: #1e3a8a;
        }

        .form-group {
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 6px;
            color: #374151;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            background: #f9fafb;
            font-size: 14px;
            color: #111827;
        }

        .form-group select {
            height: 120px;
        }

        .tooltip {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
        }

        .buttons {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 30px;
        }

        .button {
            background-color: #191970;
            color: white;
            border: none;
            padding: 14px 18px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background-color 0.2s ease;
        }

        .button:hover {
            background-color: #000080;
        }

        @media (max-width: 768px) {
            .buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="card">
        <h2><i class="fas fa-hdd"></i> Disk Utilization Report</h2>
        <form id="reportForm" method="post" action="generate_report.php" onsubmit="return validateForm()">
            <div class="form-group">
                <label for="start_date">Start Date</label>
                <input type="date" id="start_date" name="start_date" required>
                <div class="tooltip">Select the start date of the report</div>
            </div>

            <div class="form-group">
                <label for="end_date">End Date</label>
                <input type="date" id="end_date" name="end_date" required>
                <div class="tooltip">Select the end date of the report</div>
            </div>

            <div class="col-md-4">
                <label for="host_group" class="form-label">Select Hosts</label>
                <select id="host_group" name="host_group[]" class="form-select" multiple>
                    <?php
                    foreach ($hostGroup as $groupName => $hosts) {
                        echo "<optgroup label='$groupName'>";
                        foreach ($hosts as $host) {
                            echo "<option value='$host'>$host</option>";
                        }
                        echo "</optgroup>";
                    }
                    ?>
                </select>
                <div class="tooltip">Hold Ctrl/Cmd to select multiple</div>
            </div>


            <div class="buttons">
                <button type="submit" name="format" value="csv" class="button">
                    <i class="fas fa-file-csv"></i> Export to CSV
                </button>
                <button type="submit" name="format" value="html" class="button">
                    <i class="fas fa-chart-line"></i> Generate Report
                </button>
            </div>
        </form>
    </div>

    <script>
        function validateForm() {
            const startDate = document.getElementById("start_date").value;
            const endDate = document.getElementById("end_date").value;
            const hostgroups = document.getElementById("hostgroups");
            const selectedGroups = [...hostgroups.options].filter(opt => opt.selected);
            return startDate && endDate && selectedGroups.length;
        }
    </script>
</body>
</html>
