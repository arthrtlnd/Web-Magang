<?php
session_start();
require_once '../app/koneksi.php';
check_admin();

// Query untuk mengambil jumlah personel per pangkat
$query_chart = "SELECT pkt.sebutan, COUNT(p.id) as jumlah
                FROM pangkat pkt
                LEFT JOIN personel p ON p.pangkat = pkt.kd_pkt
                GROUP BY pkt.kd_pkt, pkt.sebutan
                ORDER BY pkt.kd_pkt";

$result_chart = mysqli_query($conn, $query_chart);

$pangkat_labels = [];
$pangkat_data = [];

while ($row = mysqli_fetch_assoc($result_chart)) {
    $pangkat_labels[] = $row['sebutan'];
    $pangkat_data[] = (int)$row['jumlah'];
}

// Hitung total
$total_personel = array_sum($pangkat_data);
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role='user'"))['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - KORPRAPORT</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, #7d1c1c 0%, #5d1717 100%);
            color: white;
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }
        
        .sidebar-header h2 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        
        .sidebar-header p {
            font-size: 12px;
            opacity: 0.8;
        }
        
        .sidebar-menu {
            list-style: none;
        }
        
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        
        .sidebar-menu a {
            display: block;
            padding: 12px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255,255,255,0.1);
            border-left-color: #FFB700;
        }
        
        .sidebar-menu a span {
            margin-right: 10px;
            width: 20px;
            display: inline-block;
        }
        
        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 30px;
            width: calc(100% - 250px);
        }
        
        .top-bar {
            background: white;
            padding: 20px 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .top-bar h1 {
            font-size: 24px;
            color: #333;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-info span {
            font-size: 14px;
            color: #666;
        }
        
        .chart-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .chart-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .chart-header h2 {
            font-size: 20px;
            color: #333;
            margin-bottom: 5px;
        }
        
        .chart-header p {
            font-size: 14px;
            color: #666;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid #7d1c1c;
        }
        
        .stat-card h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            color: #7d1c1c;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-250px);
                transition: transform 0.3s;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>KORPRAPORT</h2>
            <p>Admin Panel</p>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php" class="active"><span>📊</span> Dashboard</a></li>
            <li><a href="masterpersonel.php"><span>👥</span> Master Data Personel</a></li>
            <li><a href="adduser.php"><span>➕</span> Tambah User</a></li>
            <li><a href="historylog.php"><span>📋</span> History Log</a></li>
            <li><a href="../auth/logout.php"><span>🚪</span> Logout</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="top-bar">
            <h1>Dashboard Admin</h1>
            <div class="user-info">
                <span>Selamat datang, <strong>Admin (<?php echo $_SESSION['nrp']; ?>)</strong></span>
            </div>
        </div>
        
        <div class="chart-container">
            <div class="chart-header">
                <h2>Distribusi Personel Berdasarkan Pangkat</h2>
                <p>Total Personel: <strong><?php echo $total_personel; ?></strong> | Total User: <strong><?php echo $total_users; ?></strong></p>
            </div>
            <div style="position: relative; height: 500px;">
                <canvas id="pangkatChart"></canvas>
            </div>
        </div>
    </div>
    
    <script>
        const ctx = document.getElementById('pangkatChart').getContext('2d');
        const pangkatChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($pangkat_labels); ?>,
                datasets: [{
                    label: 'Jumlah Personel',
                    data: <?php echo json_encode($pangkat_data); ?>,
                    backgroundColor: 'rgba(125, 28, 28, 0.8)',
                    borderColor: 'rgba(125, 28, 28, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Jumlah: ' + context.parsed.y + ' personel';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            font: {
                                size: 12
                            }
                        },
                        grid: {
                            display: true,
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                size: 11
                            },
                            maxRotation: 45,
                            minRotation: 45
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>