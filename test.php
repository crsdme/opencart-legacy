<?php
$start = microtime(true);

$files = new RecursiveIteratorIterator(
  new RecursiveDirectoryIterator(__DIR__)
);

$count = 0;

foreach ($files as $file) {
  if ($file->isFile()) {
    $count++;
    file_exists($file->getPathname());
  }

  if ($count >= 3000) {
    break;
  }
}

echo 'Files checked: ' . $count . '<br>';
echo 'FS time: ' . round(microtime(true) - $start, 4) . ' sec';
