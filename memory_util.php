<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Memory Utilization Report</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">
  <style>
    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      padding: 0;
      font-family: 'Inter', sans-serif;
      background-color: #f4f6f8;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
    }

    .report-container {
      background: #fff;
      padding: 30px;
      border-radius: 16px;
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
      width: 90%;
      max-width: 600px;
    }

    .report-container h2 {
      font-size: 22px;
      color: #1e3a8a;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .form-group {
      position: relative;
      margin-bottom: 20px;
    }

    .form-group label {
      font-size: 14px;
      font-weight: 600;
      margin-bottom: 6px;
      display: block;
      color: #374151;
      padding-left: 28px;
      position: relative;
    }

    .form-group i {
      position: absolute;
      top: 38px;
      left: 10px;
      color: #6b7280;
      font-size: 15px;
    }

    .form-group input,
    .form-group select {
      width: 100%;
      padding: 12px 12px 12px 36px;
      border: 1px solid #d1d5db;
      border-radius: 10px;
      background: #f9fafb;
      font-size: 14px;
      color: #111827;
    }

    .form-group small {
      font-size: 11px;
      color: #6b7280;
      margin-top: 5px;
      display: block;
    }

    .button-group {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .btn {
      background-color: #0f1b60;
      color: white;
      border: none;
      padding: 12px;
      border-radius: 10px;
      font-size: 14px;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    .btn:hover {
      background-color: #3749c1;
    }

    .btn i {
      margin-right: 8px;
    }

    @media (max-width: 480px) {
      .report-container {
        padding: 20px;
      }

      .btn {
        font-size: 13px;
        padding: 10px;
      }
    }
  </style>
</head>
<body>
  <div class="report-container">
    <h2><i class="fas fa-memory"></i> Memory Utilization Report</h2>

    <div class="form-group">
      <label for="start-date">Start Date</label>
      <i class="fas fa-calendar-alt"></i>
      <input type="date" id="start-date" name="start-date" placeholder="dd-mm-yyyy">
      <small>Select the start date of the report</small>
    </div>

    <div class="form-group">
      <label for="end-date">End Date</label>
      <i class="fas fa-calendar-alt"></i>
      <input type="date" id="end-date" name="end-date" placeholder="dd-mm-yyyy">
      <small>Select the end date of the report</small>
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

    <div class="button-group">
      <button class="btn csv"><i class="fas fa-file-csv"></i> Export to CSV</button>
      <button class="btn generate"><i class="fas fa-chart-line"></i> Generate Report</button>
    </div>
  </div>
</body>
</html>
