<?php
$servername = "13.202.102.183";
$username = "root";
$password = "Galaxy@123";
$dbname = "zabbix";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$hosts = [];
$start_date = '';
$end_date = '';
$data = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $start_date = $_POST["start_date"];
    $end_date = $_POST["end_date"];
    $hosts = $_POST["hosts"] ?? [];

    $start_unix = strtotime($start_date . " 00:00:00");
    $end_unix = strtotime($end_date . " 23:59:59");

    $host_filter = "";
    if (!empty($hosts)) {
        $host_list = "'" . implode("','", $hosts) . "'";
        $host_filter = "AND h.host IN ($host_list)";
    }

    $query = "
        SELECT 
            h.host AS Host,
            t.description AS `Trigger`,
            COUNT(*) AS `Total Events`,
            SUM(CASE WHEN e.value = 0 THEN 1 ELSE 0 END) AS `Recovered`,
            ROUND(SUM(CASE WHEN e.value = 0 THEN 1 ELSE 0 END) / COUNT(*) * 100, 2) AS `SLA (%)`
        FROM events e
        JOIN triggers t ON e.objectid = t.triggerid
        JOIN functions f ON f.triggerid = t.triggerid
        JOIN items i ON f.itemid = i.itemid
        JOIN hosts h ON h.hostid = i.hostid
        WHERE e.clock BETWEEN $start_unix AND $end_unix
        $host_filter
        GROUP BY h.host, t.description
    ";

    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }

    if (isset($_POST["export_csv"])) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="custom_sla_report.csv"');
        $output = fopen("php://output", "w");
        fputcsv($output, array_keys($data[0]));
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Custom SLA Report</title>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1100px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 12px rgba(0,0,0,0.1);
        }
        h2 {
            color: #003366;
            margin-bottom: 25px;
            text-align: center;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-bottom: 30px;
        }
        label {
            font-weight: 600;
            color: #333;
        }
        input[type="date"],
        select {
            width: 100%;
            padding: 8px;
            border-radius: 6px;
            border: 1px solid #ccc;
            margin-top: 5px;
        }
        .buttons {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .buttons input[type="submit"] {
            background: #003366;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 15px;
            width: fit-content;
        }
        .buttons input[type="submit"]:hover {
            background: #001a33;
        }
        .table-container {
            max-height: 400px;
            overflow-y: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px 15px;
            border: 1px solid #ddd;
            text-align: center;
        }
        th {
            background: #003366;
            color: white;
            position: sticky;
            top: 0;
        }
        tr:nth-child(even) {
            background-color: #f9fbfd;
        }
        .pagination {
            text-align: center;
            margin-top: 15px;
        }
        .pagination button {
            margin: 0 5px;
            padding: 6px 12px;
            border-radius: 6px;
            border: 1px solid #003366;
            background: #f0f0f0;
            cursor: pointer;
        }
        #searchInput {
            width: 300px;
            padding: 8px;
            margin: 15px auto;
            display: block;
            border: 1px solid #ccc;
            border-radius: 6px;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Custom SLA Report</h2>
    <form method="POST">
        <div>
            <label for="start_date">Start Date:</label>
            <input type="date" name="start_date" required value="<?= $start_date ?>">
        </div>
        <div>
            <label for="end_date">End Date:</label>
            <input type="date" name="end_date" required value="<?= $end_date ?>">
        </div>
        <div>
            <label for="hosts">Select Hosts:</label>
            <select id="hosts" name="hosts[]" multiple="multiple" required>
                <?php
                $host_result = $conn->query("SELECT DISTINCT host FROM hosts ORDER BY host");
                while ($row = $host_result->fetch_assoc()) {
                    $selected = in_array($row["host"], $hosts) ? "selected" : "";
                    echo "<option value='{$row["host"]}' $selected>{$row["host"]}</option>";
                }
                ?>
            </select>
        </div>
        <div class="buttons">
            <input type="submit" name="submit" value="Generate Report" title="Generate Report">
            <?php if (!empty($data)): ?>
                <input type="submit" name="export_csv" value="Export to CSV" title="Export CSV">
            <?php endif; ?>
        </div>
    </form>

    <?php if (!empty($data)): ?>
        <input type="text" id="searchInput" placeholder="Search in table...">
        <div class="table-container">
            <table id="slaTable">
                <thead>
                    <tr>
                        <th>Host</th>
                        <th>Trigger</th>
                        <th>Total Events</th>
                        <th>Recovered</th>
                        <th>SLA (%)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['Host']) ?></td>
                            <td><?= htmlspecialchars($row['Trigger']) ?></td>
                            <td><?= htmlspecialchars($row['Total Events']) ?></td>
                            <td><?= htmlspecialchars($row['Recovered']) ?></td>
                            <td><?= htmlspecialchars($row['SLA (%)']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="pagination" id="pagination"></div>
    <?php endif; ?>
</div>

<script>
$(document).ready(function () {
    $('#hosts').select2({
        placeholder: "Select Hosts",
        width: '100%'
    });

    const rowsPerPage = 10;
    const rows = $('#slaTable tbody tr');
    const totalRows = rows.length;
    const pageCount = Math.ceil(totalRows / rowsPerPage);
    let currentPage = 0;

    function showPage(page) {
        currentPage = page;
        rows.hide();
        rows.slice(page * rowsPerPage, (page + 1) * rowsPerPage).show();
    }

    function renderPagination() {
        const pagination = $('#pagination');
        pagination.empty();

        if (pageCount > 1) {
            const prevBtn = $('<button>').text('Prev').click(() => {
                if (currentPage > 0) showPage(currentPage - 1);
            });
            pagination.append(prevBtn);

            for (let i = 0; i < pageCount; i++) {
                const btn = $('<button>').text(i + 1).click(() => showPage(i));
                if (i === currentPage) btn.css('font-weight', 'bold');
                pagination.append(btn);
            }

            const nextBtn = $('<button>').text('Next').click(() => {
                if (currentPage < pageCount - 1) showPage(currentPage + 1);
            });
            pagination.append(nextBtn);
        }
    }

    function filterTable() {
        const query = $('#searchInput').val().toLowerCase();
        rows.each(function () {
            const row = $(this);
            const match = row.text().toLowerCase().indexOf(query) > -1;
            row.toggle(match);
        });
    }

    if (totalRows > 0) {
        showPage(0);
        renderPagination();
    }

    $('#searchInput').on("keyup", function () {
        filterTable();
    });
});
</script>
</body>
</html>
