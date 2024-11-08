
<!DOCTYPE html>

<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Dashboard</title>
  <meta content="" name="description">
  <meta content="" name="keywords">

  <!-- Favicons -->
  <link href="assets/img/logoo.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Google Fonts -->
  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="assets/vendor/quill/quill.snow.css" rel="stylesheet">
  <link href="assets/vendor/quill/quill.bubble.css" rel="stylesheet">
  <link href="assets/vendor/remixicon/remixicon.css" rel="stylesheet">
  <link href="assets/vendor/simple-datatables/style.css" rel="stylesheet">

  <!-- Template Main CSS File -->
  <link href="assets/css/style.css" rel="stylesheet">

  <!-- =======================================================
  * Template Name: NiceAdmin
  * Template URL: https://bootstrapmade.com/nice-admin-bootstrap-admin-html-template/
  * Updated: Apr 20 2024 with Bootstrap v5.3.3
  * Author: BootstrapMade.com
  * License: https://bootstrapmade.com/license/
  ======================================================== -->
</head>

<body>

  <?php include 'header.php'; ?>


  <!-- ======= Sidebar ======= -->
  <?php include 'sidebar.php'; ?>

  <main id="main" class="main">

    <div class="pagetitle">
      <h1>Dashboard</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Home</a></li>
          <li class="breadcrumb-item active">Dashboard</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    <section class="section dashboard">
      <div class="row">

        <!-- Left side columns -->
        <div class="col-lg-8">
          <div class="row">

            <!-- Sales Card -->
            <div class="col-xxl-4 col-md-6">
              <div class="card info-card sales-card">

                <div class="filter">
                  <a class="icon" href="#" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></a>
                  <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                    <li class="dropdown-header text-start">
                      <h6>Filter</h6>
                    </li>

                    <li><a class="dropdown-item" href="#">Today</a></li>
                    <li><a class="dropdown-item" href="#">This Month</a></li>
                    <li><a class="dropdown-item" href="#">This Year</a></li>
                  </ul>
                </div>

                <div class="card-body">
                  <h5 class="card-title">STORAGE USED</h5>

                  <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                      <i class="bi bi-cloud"></i>
                    </div>
                    <div class="ps-3">
                      <h6>850 GB</h6>
                      <span class="text-success small pt-1 fw-bold">8.45%</span>
                      <span class="text-muted small pt-2 ps-1">Since last month</span>
                    </div>
                  </div>
                </div>

              </div>
            </div><!-- End Sales Card -->

            <!-- Revenue Card -->
            <div class="col-xxl-4 col-md-6">
              <div class="card info-card revenue-card">

                <div class="filter">
                  <a class="icon" href="#" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></a>
                  <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                    <li class="dropdown-header text-start">
                      <h6>Filter</h6>
                    </li>

                    <li><a class="dropdown-item" href="#">Today</a></li>
                    <li><a class="dropdown-item" href="#">This Month</a></li>
                    <li><a class="dropdown-item" href="#">This Year</a></li>
                  </ul>
                </div>

                <div class="card-body">
                  <h5 class="card-title">AVAILABLE STORAGE</h5>

                  <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                      <i class="bi bi-hdd"></i>
                    </div>
                    <div class="ps-3">
                      <h6>1.15 TB</h6>
                      <span class="text-danger small pt-1 fw-bold">2.15%</span>
                      <span class="text-muted small pt-2 ps-1">Since last month</span>
                    </div>
                  </div>
                </div>

              </div>
            </div><!-- End Revenue Card -->

            <!-- Customers Card -->
            <div class="col-xxl-4 col-xl-12">

              <div class="card info-card customers-card">

                <div class="filter">
                  <a class="icon" href="#" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></a>
                  <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                    <li class="dropdown-header text-start">
                      <h6>Filter</h6>
                    </li>

                    <li><a class="dropdown-item" href="#">Today</a></li>
                    <li><a class="dropdown-item" href="#">This Month</a></li>
                    <li><a class="dropdown-item" href="#">This Year</a></li>
                  </ul>
                </div>

                <div class="card-body">
                  <h5 class="card-title">TOTAL FILES <span>| Since Last Month</span></h5>

                  <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                      <i class="bi bi-file-earmark-text" style="color: white;"></i>
                    </div>
                    <div class="ps-3">
                      <h6>120,385</h6>
                      <span class="text-success small pt-1 fw-bold">5.12%</span> 
                      <span class="text-muted small pt-2 ps-1">increase</span>
                    </div>
                  </div>

                </div>
              </div>

            </div><!-- End Customers Card -->

            <!-- Reports -->
            <div class="col-12">
              <div class="card">
                <div class="card-body">
                  <h5 class="card-title">Storage Overview</h5>
            
                  <!-- Line Chart -->
                  <div id="storageChart"></div>
            
                  <script>
                    document.addEventListener("DOMContentLoaded", () => {
                      new ApexCharts(document.querySelector("#storageChart"), {
                        series: [{
                          name: 'Storage Used (GB)',
                          data: [100, 120, 140, 160, 180, 200, 220, 240, 260, 280, 300, 320]
                        }],
                        chart: {
                          height: 350,
                          type: 'area',
                          toolbar: {
                            show: false
                          },
                        },
                        markers: {
                          size: 5,
                          colors: ['#4154f1'],
                          strokeColors: '#ffffff',
                          strokeWidth: 2,
                          hover: {
                            size: 7
                          }
                        },
                        colors: ['#4154f1'],
                        fill: {
                          type: "solid",
                          opacity: 0.2
                        },
                        dataLabels: {
                          enabled: false
                        },
                        stroke: {
                          curve: 'smooth',
                          width: 3
                        },
                        xaxis: {
                          categories: ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"],
                          title: {
                            text: 'Month',
                          }
                        },
                        yaxis: {
                          title: {
                            text: 'Storage Used (GB)',
                          },
                          min: 0,
                          max: 350,
                          tickAmount: 7
                        },
                        tooltip: {
                          x: {
                            format: 'MMMM'
                          },
                        },
                        grid: {
                          show: true,
                          borderColor: '#e0e0e0',
                        },
                        legend: {
                          position: 'right',
                          horizontalAlign: 'center',
                          labels: {
                            colors: '#4154f1'
                          },
                          markers: {
                            fillColors: ['#4154f1']
                          }
                        }
                      }).render();
                    });
                  </script>
                  <!-- End Line Chart -->
            
                </div>
              </div>
            </div>
            
            <!-- End Reports -->

            <!-- Recent Sales -->
            <div class="col-12">
              <div class="card storage-analytics overflow-auto">
            
                <div class="filter">
                  <a class="icon" href="#" data-bs-toggle="dropdown">
                    <button class="btn btn-primary btn-sm float-end me-4 mt-1">VIEW DETAILS</button>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                    <li class="dropdown-header text-start">
                      <h6>Filter</h6>
                    </li>
                    <li><a class="dropdown-item" href="#">Today</a></li>
                    <li><a class="dropdown-item" href="#">This Week</a></li>
                    <li><a class="dropdown-item" href="#">This Month</a></li>
                  </ul>
                </div>
            
                <div class="card-body">
                  <h5 class="card-title">Storage Analytics</h5>
            
                  <table class="table table-borderless">
                    <thead>
                      <tr>
                        <th scope="col">FILE TYPE</th>
                        <th scope="col">TOTAL SIZE (GB)</th>
                        <th scope="col">PERCENTAGE USED</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td>Images</td>
                        <td>120 GB</td>
                        <td>
                          40%
                          <div class="progress">
                            <div class="progress-bar" role="progressbar" style="width: 40%; background-color: #f00;" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100"></div>
                          </div>
                        </td>
                      </tr>
                      <tr>
                        <td>Videos</td>
                        <td>150 GB</td>
                        <td>
                          50%
                          <div class="progress">
                            <div class="progress-bar" role="progressbar" style="width: 50%; background-color: #ff4500;" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
                          </div>
                        </td>
                      </tr>
                      <tr>
                        <td>Documents</td>
                        <td>60 GB</td>
                        <td>
                          20%
                          <div class="progress">
                            <div class="progress-bar" role="progressbar" style="width: 20%; background-color: #ffa500;" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100"></div>
                          </div>
                        </td>
                      </tr>
                      <tr>
                        <td>Others</td>
                        <td>70 GB</td>
                        <td>
                          25%
                          <div class="progress">
                            <div class="progress-bar" role="progressbar" style="width: 25%; background-color: #ffb347;" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
            
                </div>
              </div>
            </div>
              
            <!-- End Recent Sales -->

            <!-- Top Selling -->
            <!-- <div class="col-12">
              <div class="card top-selling overflow-auto">

                <div class="filter">
                  <a class="icon" href="#" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></a>
                  <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                    <li class="dropdown-header text-start">
                      <h6>Filter</h6>
                    </li>

                    <li><a class="dropdown-item" href="#">Today</a></li>
                    <li><a class="dropdown-item" href="#">This Month</a></li>
                    <li><a class="dropdown-item" href="#">This Year</a></li>
                  </ul>
                </div>

                <div class="card-body pb-0">
                  <h5 class="card-title">Top Selling <span>| Today</span></h5>

                  <table class="table table-borderless">
                    <thead>
                      <tr>
                        <th scope="col">Preview</th>
                        <th scope="col">Product</th>
                        <th scope="col">Price</th>
                        <th scope="col">Sold</th>
                        <th scope="col">Revenue</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <th scope="row"><a href="#"><img src="assets/img/product-1.jpg" alt=""></a></th>
                        <td><a href="#" class="text-primary fw-bold">Ut inventore ipsa voluptas nulla</a></td>
                        <td>$64</td>
                        <td class="fw-bold">124</td>
                        <td>$5,828</td>
                      </tr>
                      <tr>
                        <th scope="row"><a href="#"><img src="assets/img/product-2.jpg" alt=""></a></th>
                        <td><a href="#" class="text-primary fw-bold">Exercitationem similique doloremque</a></td>
                        <td>$46</td>
                        <td class="fw-bold">98</td>
                        <td>$4,508</td>
                      </tr>
                      <tr>
                        <th scope="row"><a href="#"><img src="assets/img/product-3.jpg" alt=""></a></th>
                        <td><a href="#" class="text-primary fw-bold">Doloribus nisi exercitationem</a></td>
                        <td>$59</td>
                        <td class="fw-bold">74</td>
                        <td>$4,366</td>
                      </tr>
                      <tr>
                        <th scope="row"><a href="#"><img src="assets/img/product-4.jpg" alt=""></a></th>
                        <td><a href="#" class="text-primary fw-bold">Officiis quaerat sint rerum error</a></td>
                        <td>$32</td>
                        <td class="fw-bold">63</td>
                        <td>$2,016</td>
                      </tr>
                      <tr>
                        <th scope="row"><a href="#"><img src="assets/img/product-5.jpg" alt=""></a></th>
                        <td><a href="#" class="text-primary fw-bold">Sit unde debitis delectus repellendus</a></td>
                        <td>$79</td>
                        <td class="fw-bold">41</td>
                        <td>$3,239</td>
                      </tr>
                    </tbody>
                  </table>

                </div>

              </div>
            </div>End Top Selling -->

          </div>
        </div><!-- End Left side columns -->

        <!-- Right side columns -->
        <div class="col-lg-4">
    <div class="card">
        <div class="filter">
            <a class="icon" href="#" data-bs-toggle="dropdown">
                <button class="btn btn-primary btn-sm float-end me-4 mt-1">SEE ALL</button>
            </a>
            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                <li class="dropdown-header text-start">
                    <h6>Filter</h6>
                </li>
                <li><a class="dropdown-item" href="#">Today</a></li>
                <li><a class="dropdown-item" href="#">This Week</a></li>
                <li><a class="dropdown-item" href="#">This Month</a></li>
            </ul>
        </div>

        <div class="card-body">
            <h5 class="card-title">Recent Videos and Pictures Activity</h5>
            
            <div class="activity">
                <?php
                // Database connection parameters
                $servername = "localhost";
                $username = "root";
                $password = "capstone2425";
                $dbname = "greyhoundhub";

                // Create database connection
                $conn = new mysqli($servername, $username, $password, $dbname);
                if ($conn->connect_error) {
                    die("Connection failed: " . $conn->connect_error);
                }

                // Function to fetch recent file paths
                function getRecentFilePaths($conn) {
                    // Define allowed file extensions
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'mp3', 'wav', 'mov'];

                    // Create a string of allowed extensions for SQL IN clause
                    $allowedExtensionsSQL = "'" . implode("', '", $allowedExtensions) . "'";

                    // Update the query to filter based on file extensions and order by timestamp
                    $query = "SELECT item_name, item_type, filepath, timestamp 
                              FROM recent 
                              WHERE LOWER(SUBSTRING_INDEX(filepath, '.', -1)) IN ($allowedExtensionsSQL)
                              ORDER BY timestamp DESC
                              LIMIT 10";

                    $result = $conn->query($query);
                    if (!$result) {
                        die("Query Failed: " . $conn->error);
                    }

                    return $result->fetch_all(MYSQLI_ASSOC);
                }

                // Fetch recent files
                $recentFiles = getRecentFilePaths($conn);

                // Close the database connection
                $conn->close();

                // Function to convert timestamp to "time ago" format
function timeAgo($timestamp) {
  $time_ago = strtotime($timestamp); // Convert the timestamp to a UNIX timestamp
  $current_time = time(); // Get the current time as a UNIX timestamp
  $time_difference = $current_time - $time_ago; // Calculate the time difference

  // Calculate the time differences in various units
  $seconds = $time_difference;
  $minutes = round($seconds / 60);
  $hours = round($seconds / 3600);
  $days = round($seconds / 86400);
  $weeks = round($seconds / 604800);
  $months = round($seconds / 2629440);
  $years = round($seconds / 31553280);

  // Determine the appropriate time ago message
  if ($seconds < 60) {
      return "Just Now";
  } elseif ($minutes < 60) {
      return ($minutes == 1) ? "one minute ago" : "$minutes minutes ago";
  } elseif ($hours < 24) {
      return ($hours == 1) ? "an hour ago" : "$hours hours ago";
  } elseif ($days < 7) {
      return ($days == 1) ? "yesterday" : "$days days ago";
  } elseif ($weeks < 4) {
      return ($weeks == 1) ? "a week ago" : "$weeks weeks ago";
  } elseif ($months < 12) {
      return ($months == 1) ? "a month ago" : "$months months ago";
  } else {
      return ($years == 1) ? "one year ago" : "$years years ago";
  }
}


                // Display activity items
if (empty($recentFiles)): ?>
  <p>No recent activity found.</p>
<?php else: ?>
  <?php foreach ($recentFiles as $item): ?>
      <div class="activity-item d-flex">
          <div class="activite-label"><?php echo htmlspecialchars(timeAgo($item['timestamp'])); ?></div>
          <i class="bi bi-circle-fill activity-badge 
              <?php 
                  $ext = strtolower(pathinfo($item['filepath'], PATHINFO_EXTENSION));
                  echo $ext === 'jpg' || $ext === 'png' ? 'text-success' : 
                       ($ext === 'mp4' ? 'text-danger' : 'text-primary'); 
              ?> align-self-start"></i>
          <div class="activity-content">
              <?php echo ucfirst($item['item_type']); ?> 
              <a href="#" class="fw-bold text-dark"><?php echo htmlspecialchars($item['item_name']); ?></a>
          </div>
      </div><!-- End activity item-->
  <?php endforeach; ?>
<?php endif; ?>

</div>
        </div>
    </div>

          
          <!-- End Recent Activity -->

          <!-- Website Traffic -->
          <div class="card">
            <div class="filter">
              <a class="icon" href="#" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></a>
              <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                <li class="dropdown-header text-start">
                  <h6>Filter</h6>
                </li>

                <li><a class="dropdown-item" href="#">Today</a></li>
                <li><a class="dropdown-item" href="#">This Month</a></li>
                <li><a class="dropdown-item" href="#">This Year</a></li>
              </ul>
            </div>

            <div class="card-body pb-0">
              <h5 class="card-title">Storage Details <span>| Today</span></h5>

              <div id="PieChart" style="min-height: 400px;" class="echart"></div>

              <script>
  document.addEventListener("DOMContentLoaded", () => {
    echarts.init(document.querySelector("#PieChart")).setOption({
      tooltip: {
        trigger: 'item'
      },
      legend: {
        top: '5%',
        left: 'center'
      },
      series: [{
        name: 'Access From',
        type: 'pie',
        radius: ['40%', '70%'],
        avoidLabelOverlap: false,
        label: {
          show: false,
          position: 'center'
        },
        emphasis: {
          label: {
            show: true,
            fontSize: '18',
            fontWeight: 'bold'
          }
        },
        labelLine: {
          show: false
        },
        data: [
          {
            value: 1048,
            name: 'Storage Used',
            itemStyle: {
              color: '#9A1B2F'  // Set color to maroon
            }
          },
          {
            value: 735,
            name: 'Storage Left',
            itemStyle: {
              color: '#6c757d'  // Set color to gray
            }
          }
        ]
      }]
    });
  });
</script>


            </div>
          </div><!-- End Website Traffic -->

          <!-- News & Updates Traffic -->
          <!-- <div class="card">
            <div class="filter">
              <a class="icon" href="#" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></a>
              <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                <li class="dropdown-header text-start">
                  <h6>Filter</h6>
                </li>

                <li><a class="dropdown-item" href="#">Today</a></li>
                <li><a class="dropdown-item" href="#">This Month</a></li>
                <li><a class="dropdown-item" href="#">This Year</a></li>
              </ul>
            </div>

            <div class="card-body pb-0">
              <h5 class="card-title">News &amp; Updates <span>| Today</span></h5>

              <div class="news">
                <div class="post-item clearfix">
                  <img src="assets/img/news-1.jpg" alt="">
                  <h4><a href="#">Nihil blanditiis at in nihil autem</a></h4>
                  <p>Sit recusandae non aspernatur laboriosam. Quia enim eligendi sed ut harum...</p>
                </div>

                <div class="post-item clearfix">
                  <img src="assets/img/news-2.jpg" alt="">
                  <h4><a href="#">Quidem autem et impedit</a></h4>
                  <p>Illo nemo neque maiores vitae officiis cum eum turos elan dries werona nande...</p>
                </div>

                <div class="post-item clearfix">
                  <img src="assets/img/news-3.jpg" alt="">
                  <h4><a href="#">Id quia et et ut maxime similique occaecati ut</a></h4>
                  <p>Fugiat voluptas vero eaque accusantium eos. Consequuntur sed ipsam et totam...</p>
                </div>

                <div class="post-item clearfix">
                  <img src="assets/img/news-4.jpg" alt="">
                  <h4><a href="#">Laborum corporis quo dara net para</a></h4>
                  <p>Qui enim quia optio. Eligendi aut asperiores enim repellendusvel rerum cuder...</p>
                </div>

                <div class="post-item clearfix">
                  <img src="assets/img/news-5.jpg" alt="">
                  <h4><a href="#">Et dolores corrupti quae illo quod dolor</a></h4>
                  <p>Odit ut eveniet modi reiciendis. Atque cupiditate libero beatae dignissimos eius...</p>
                </div>

              </div>End sidebar recent posts -->

            </div>
          </div><!-- End News & Updates -->

        </div><!-- End Right side columns -->

      </div>
    </section>

  </main><!-- End #main -->

  <!-- ======= Footer ======= -->
  <footer id="footer" class="footer">
    <div class="copyright">
      &copy; Copyright <strong><span>NiceAdmin</span></strong>. All Rights Reserved
    </div>
    <div class="credits">
      <!-- All the links in the footer should remain intact. -->
      <!-- You can delete the links only if you purchased the pro version. -->
      <!-- Licensing information: https://bootstrapmade.com/license/ -->
      <!-- Purchase the pro version with working PHP/AJAX contact form: https://bootstrapmade.com/nice-admin-bootstrap-admin-html-template/ -->
      Designed by <a href="https://bootstrapmade.com/">BootstrapMade</a>
    </div>
  </footer><!-- End Footer -->

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/chart.js/chart.umd.js"></script>
  <script src="assets/vendor/echarts/echarts.min.js"></script>
  <script src="assets/vendor/quill/quill.js"></script>
  <script src="assets/vendor/simple-datatables/simple-datatables.js"></script>
  <script src="assets/vendor/tinymce/tinymce.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>

  <!-- Template Main JS File -->
  <script src="assets/js/main.js"></script>

</body>

</html>
