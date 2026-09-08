<?php
$dir = new RecursiveDirectoryIterator(__DIR__);
$ite = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($ite, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

foreach($files as $file) {
    $path = $file[0];
    if (strpos($path, 'fix_redirects.php') !== false) continue;
    
    $content = file_get_contents($path);
    // Find action="/modules/..." and change to action="<?= APP_BASE ?>/modules/..."
    $newContent = preg_replace('/action="\/modules\//i', 'action="<?= APP_BASE ?>/modules/', $content);
    
    if ($content !== $newContent) {
        file_put_contents($path, $newContent);
        echo "Fixed action in: " . $path . PHP_EOL;
    }
}
