<?php
 /**
 * index.php
 */
    if (!file_exists('settings/config.php')) {
    // install
    header('Location: install/index.php');
    exit;
    }

    $cfg = include 'settings/config.php';

    // ค่าเหล่านี้มาจาก config ของ tenant ที่ init() merge ให้แล้ว (ไม่ใช่ค่ากลาง)
    $reversion = rawurlencode((string) $cfg['reversion'] ?? '');
    $webTitle = (string) ($cfg['web_title'] ?? 'Admin System');
    $webDescription = (string) ($cfg['web_description'] ?? 'Admin panel to manage site contents and settings.');
?>
<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($webTitle, ENT_QUOTES); ?></title>
  <meta name="description" content="<?php echo htmlspecialchars($webDescription, ENT_QUOTES); ?>">

  <!-- Framework CSS -->
  <link rel="stylesheet" href="Now/dist/now.core.min.css?v=<?php echo $reversion; ?>">
  <link rel="stylesheet" href="Now/css/fonts.css?v=<?php echo $reversion; ?>">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="css/styles.css?v=<?php echo $reversion; ?>">

  <!-- PWA -->
  <link rel="manifest" href="manifest.json?v=<?php echo $reversion; ?>">
  <meta name="theme-color" content="#4f46e5">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <meta name="apple-mobile-web-app-title" content="<?php echo htmlspecialchars($webTitle, ENT_QUOTES); ?>">
  <link rel="apple-touch-icon" href="images/web-app-manifest-192x192.png">
</head>

<body>
  <main id="main" role="main">
    <!-- Main Content -->
  </main>

  <!-- Framework Scripts -->
  <script src="Now/dist/now.core.min.js?v=<?php echo $reversion; ?>"></script>
  <script src="Now/dist/now.table.min.js?v=<?php echo $reversion; ?>"></script>
  <script src="Now/dist/now.graph.min.js?v=<?php echo $reversion; ?>"></script>
  <!-- ServiceWorkerManager is not part of now.core.min.js, so it loads
       separately and must come before js/main.js, which calls its init() -->
  <script src="Now/dist/now.serviceworker.min.js?v=<?php echo $reversion; ?>"></script>

  <!-- App Scripts -->

  <?php
      $skipModules = ['index'];
      foreach (['modules'] as $dir) {
          $path = __DIR__.'/'.$dir;
          if (is_dir($path)) {
              foreach (scandir($path) as $name) {
            if ($name[0] !== '.' && !in_array($name, $skipModules, true)) {
                if (is_file($path.'/'.$name.'/admin.js')) {
                    echo '<script src="'.$dir.'/'.$name.'/admin.js?v='.$reversion.'"></script>'."\n";
                }
                if (is_file($path.'/'.$name.'/styles.css')) {
                    echo '<link rel="stylesheet" href="'.$dir.'/'.$name.'/styles.css?v='.$reversion.'">'."\n";
                }
                if (is_file('templates/'.$name.'/styles.css')) {
                    echo '<link rel="stylesheet" href="templates/'.$name.'/styles.css?v='.$reversion.'">'."\n";
                }
            }
              }
          }
      }
  ?>

  <!-- App Scripts -->
  <script src="js/main.js?v=<?php echo $reversion; ?>"></script>
  <script src="js/global.js?v=<?php echo $reversion; ?>"></script>
</body>

</html>
