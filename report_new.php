<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reports Dashboard</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: #f5f9fc;
    }
    .icon-style {
      color: #191970;
    }
    .card-link {
      text-decoration: none;
      color: inherit;
    }
  </style>
</head>
<body>

    <?php
$reports = [
    ['icon' => 'fas fa-hdd', 'title' => 'Disk Utilization', 'desc' => 'View detailed disk usage analysis.', 'link' => 'disk_util.php'],
    ['icon' => 'fas fa-memory', 'title' => 'Memory Utilization', 'desc' => 'Monitor memory consumption across systems.', 'link' => 'memory_util.php'],
    ['icon' => 'fas fa-microchip', 'title' => 'CPU Utilization', 'desc' => 'Analyze CPU usage and performance metrics.', 'link' => 'cpu_utilization.php'],
    ['icon' => 'fas fa-tachometer-alt', 'title' => 'CPU Load', 'desc' => 'Check CPU load levels in real-time.', 'link' => 'cpu_load.php'],
    ['icon' => 'fas fa-server', 'title' => 'Host Availability', 'desc' => 'Review uptime and availability of hosts.', 'link' => 'host_availability.php'],
    ['icon' => 'fas fa-network-wired', 'title' => 'Request Count on IIS', 'desc' => 'Monitor the number of requests handled by IIS.', 'link' => 'request_count_iis.php'],
    ['icon' => 'fas fa-cogs', 'title' => 'Host Item Wise Availability', 'desc' => 'Check item-wise availability for hosts.', 'link' => 'host_itemwise_avail.php'],
    ['icon' => 'fas fa-tachometer-alt', 'title' => 'Interface Traffic (Traffic In)', 'desc' => 'Analyze incoming traffic on network interfaces.', 'link' => 'interface_traff.php'],
    ['icon' => 'fas fa-heartbeat', 'title' => 'Host Health Summary', 'desc' => 'View health status of all hosts in your network.', 'link' => 'host_health_summary.php'],
    ['icon' => 'fas fa-exclamation-circle', 'title' => 'Host Alert', 'desc' => 'Monitor alerts and notifications for hosts.', 'link' => 'host_alert_report.php'],
    ['icon' => 'fas fa-check-circle', 'title' => 'Custom SLA', 'desc' => 'Track Service Level Agreements for custom services.', 'link' => 'custom_sla_report.php'],
    ['icon' => 'fas fa-cogs', 'title' => 'Discovery', 'desc' => 'View the discovered devices and network elements.', 'link' => 'discovery_report.php'],
    ['icon' => 'fas fa-archive', 'title' => 'Host Inventory', 'desc' => 'Manage and view the inventory of network hosts.', 'link' => 'host_inventory.php']
];
?>

<div class="container py-5">
    <h2 class="mb-4 text-center">Reports Dashboard</h2>
    <div class="row g-4">
        <?php foreach ($reports as $report): ?>
            <div class="col-md-6 col-lg-4">
                <a href="<?= $report['link'] ?>" class="card-link">
                    <div class="card shadow-sm text-center h-100 p-4">
                        <div class="card-body">
                            <i class="<?= $report['icon'] ?> fa-3x mb-3 icon-style"></i>
                            <h5 class="card-title"><?= $report['title'] ?></h5>
                            <p class="card-text"><?= $report['desc'] ?></p>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>


</body>
</html>
