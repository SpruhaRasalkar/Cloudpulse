<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Host Availability Report</title>

  <!-- Font Awesome Icons -->
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    integrity="sha512-dM8KDRsRmVa3dQWb1LTVmepq9iA33dJQ0dRCp3Ym35DZVXbx8W9c5M6V+kpkaF2ST+IUIu1sDy5S6r6sYpZPaA=="
    crossorigin="anonymous"
    referrerpolicy="no-referrer"
  />

  <style>
    body {
      background-color: #f4f6f8;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      margin: 0;
      padding: 40px 20px;
      display: flex;
      justify-content: center;
    }

    .container {
      background: #fff;
      border-radius: 16px;
      padding: 40px;
      width: 100%;
      max-width: 850px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
    }

    .container h2 {
      font-size: 24px;
      color: #0f1b60;
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 30px;
    }

    .form-group {
      margin-bottom: 20px;
    }

    label {
      font-weight: 600;
      color: #0f1b60;
    }

    input[type="date"],
    select {
      width: 100%;
      padding: 12px;
      font-size: 14px;
      margin-top: 6px;
      border: 1px solid #ccc;
      border-radius: 8px;
    }

    select[multiple] {
      height: 120px;
    }

    small {
      display: block;
      font-size: 12px;
      color: #666;
      margin-top: 4px;
    }

    .btn {
      width: 100%;
      padding: 12px;
      font-size: 14px;
      font-weight: bold;
      color: #fff;
      background-color: #0f1b60;
      border: none;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      margin-top: 14px;
      cursor: pointer;
    }

    .btn:hover {
      background-color: #002f87;
    }

    @media (max-width: 600px) {
      .container {
        padding: 20px;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <h2><i class="fas fa-server"></i> Host Availability Report</h2>
    <form method="post" action="host_availability.php">
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
        <label for="host_groups">Host Groups</label>
        <select id="host_groups" name="host_groups[]" multiple required>
          <option value="group1">Group 1</option>
          <option value="group2">Group 2</option>
          <option value="group3">Group 3</option>
        </select>
        <small>Hold Ctrl/Cmd to select multiple</small>
      </div>

      <button type="submit" name="action" value="export_csv" class="btn">
        <i class="fas fa-file-csv"></i> Export to CSV
      </button>

      <button type="submit" name="action" value="generate" class="btn">
        <i class="fas fa-chart-line"></i> Generate Report
      </button>
    </form>
  </div>
</body>
</html>
