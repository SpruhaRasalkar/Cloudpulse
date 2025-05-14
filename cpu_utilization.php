<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>CPU Utilization Report</title>
  <style>
    body {
      background-color: #f4f6f8;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      margin: 0;
      padding: 40px;
      display: flex;
      justify-content: center;
    }

    .report-card {
      background-color: #fff;
      padding: 30px;
      border-radius: 16px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
      width: 100%;
      max-width: 500px;
    }

    .report-title {
      font-size: 22px;
      font-weight: bold;
      color: #0f1b60;
      margin-bottom: 30px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-group label {
      display: block;
      font-weight: 600;
      margin-bottom: 6px;
      color: #0f1b60;
    }

    .form-group small {
      color: #666;
      display: block;
      margin-top: 4px;
      font-size: 12px;
    }

    input[type="date"],
    select {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 14px;
    }

    select[multiple] {
      height: 100px;
    }

    .button {
      display: block;
      width: 100%;
      padding: 12px;
      font-size: 14px;
      font-weight: bold;
      color: #fff;
      background-color: #0f1b60;
      border: none;
      border-radius: 8px;
      margin-top: 10px;
      cursor: pointer;
    }

    .button:hover {
      background-color: #0b1550;
    }

    .button.secondary {
      background-color: #0f1b60;
    }

    .icon {
      font-size: 20px;
    }
  </style>
</head>
<body>
  <div class="report-card">
    <div class="report-title">
      🖥️ CPU Utilization Report
    </div>
    <form method="post" action="cpu_report.php">
      <div class="form-group">
        <label for="start_date">Start Date</label>
        <input type="date" id="start_date" name="start_date" required />
        <small>Select the start date of the report</small>
      </div>

      <div class="form-group">
        <label for="end_date">End Date</label>
        <input type="date" id="end_date" name="end_date" required />
        <small>Select the end date of the report</small>
      </div>

      <div class="form-group">
        <label for="hosts">Host Groups</label>
        <select name="hosts[]" id="hosts" multiple required>
          <option value="group1">Group 1</option>
          <option value="group2">Group 2</option>
          <option value="group3">Group 3</option>
        </select>
        <small>Hold Ctrl/Cmd to select multiple</small>
      </div>

      <button class="button secondary" type="submit" name="action" value="export_csv">📁 Export to CSV</button>
      <button class="button" type="submit" name="action" value="generate">⚙️ Generate Report</button>
    </form>
  </div>
</body>
</html>
