<?php
/**
 * Site-scoped persistent files, kept outside Geeklog 2.2.2 path_data cleanup.
 * Legacy files are copied (never moved/deleted), checked and reused.
 */
if (isset($_SERVER['PHP_SELF']) && basename($_SERVER['PHP_SELF']) === 'MonitorStorage.php') { die(); }

function MONITOR_STORAGE_base()
{
    global $_CONF;
    $path = isset($_CONF['path_data']) ? rtrim($_CONF['path_data'], '/\\') : '';
    if ($path === '') { return ''; }
    return dirname($path) . DIRECTORY_SEPARATOR . basename($path) . '-monitor'
        . DIRECTORY_SEPARATOR;
}
function MONITOR_STORAGE_ensure($subdir)
{
    $base = MONITOR_STORAGE_base();
    if ($base === '' || !in_array($subdir, array('changes', 'log-archives'), true)) {
        return '';
    }
    $dir = $base . $subdir . DIRECTORY_SEPARATOR;
    if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) { return ''; }
    return is_writable($dir) ? $dir : '';
}
function MONITOR_STORAGE_copyLegacy($source, $target)
{
    if (!is_file($source) || !is_readable($source)) { return true; }
    // Once migrated, the destination is authoritative and may have changed.
    if (file_exists($target)) { return is_file($target); }
    $temp = $target . '.' . getmypid() . '.tmp';
    if (!@copy($source, $temp)) { return false; }
    if (@filesize($source) !== @filesize($temp)
        || @hash_file('sha256', $source) !== @hash_file('sha256', $temp)) {
        @unlink($temp);
        return false;
    }
    // A second process may have completed migration while we copied.
    if (file_exists($target)) {
        @unlink($temp);
        return is_file($target);
    }
    if (!@rename($temp, $target)) { @unlink($temp); return false; }
    @chmod($target, 0640);
    return true;
}
function MONITOR_STORAGE_migrateArchive($legacyDir, $newDir)
{
    if ($newDir === '') { return false; }
    if (!is_dir($legacyDir)) { return true; }
    $files = @scandir($legacyDir);
    if (!is_array($files)) { return false; }
    foreach ($files as $name) {
        if ($name === '.' || $name === '..') { continue; }
        $from = $legacyDir . $name;
        if (!is_file($from)) { return false; }
        if (!MONITOR_STORAGE_copyLegacy($from, $newDir . $name)) { return false; }
    }
    return true;
}
