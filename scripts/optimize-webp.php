#!/usr/bin/env php
<?php

if(PHP_SAPI !== 'cli')
{
    fwrite(STDERR, "CLI only\n");
    exit(1);
}

$autoloadCandidates = array(
    dirname(__DIR__).'/domains/phucthinhsolar.com/public_html/libraries/WebpConvert/vendor/autoload.php',
    '/var/www/html/libraries/WebpConvert/vendor/autoload.php'
);
$autoload = '';
foreach($autoloadCandidates as $candidate) if(is_file($candidate)) { $autoload = $candidate; break; }
if($autoload === '')
{
    fwrite(STDERR, "Không tìm thấy WebpConvert autoload.\n");
    exit(1);
}
require $autoload;

use WebPConvert\WebPConvert;

$input = isset($argv[1]) ? $argv[1] : '';
$quality = 82;
$force = false;
$dryRun = false;
$minBytes = 0;

foreach(array_slice($argv, 2) as $argument)
{
    if($argument === '--force') $force = true;
    elseif($argument === '--dry-run') $dryRun = true;
    elseif(strpos($argument, '--quality=') === 0) $quality = (int) substr($argument, 10);
    elseif(strpos($argument, '--min-bytes=') === 0) $minBytes = max(0, (int) substr($argument, 12));
}

if($input === '' || (!is_file($input) && !is_dir($input)))
{
    fwrite(STDERR, "Usage: php scripts/optimize-webp.php <file|directory> [--quality=82] [--min-bytes=0] [--force] [--dry-run]\n");
    exit(1);
}

$quality = max(55, min(92, $quality));
$files = array();
if(is_file($input))
{
    $files[] = $input;
}
else
{
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($input, FilesystemIterator::SKIP_DOTS));
    foreach($iterator as $file)
    {
        if($file->isFile()) $files[] = $file->getPathname();
    }
}

$isValidWebp = static function($path) {
    if(!is_file($path) || filesize($path) < 16) return false;
    $header = @file_get_contents($path, false, null, 0, 12);
    return strlen((string) $header) === 12 && substr($header, 0, 4) === 'RIFF' && substr($header, 8, 4) === 'WEBP';
};

$summary = array('converted' => 0, 'skipped' => 0, 'failed' => 0, 'source_bytes' => 0, 'webp_bytes' => 0);
foreach($files as $source)
{
    $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));
    $sourceSize = is_file($source) ? filesize($source) : 0;
    if(!in_array($extension, array('jpg','jpeg','png'), true) || $sourceSize < $minBytes)
    {
        $summary['skipped']++;
        continue;
    }

    $destination = $source.'.webp';
    if(!$force && $isValidWebp($destination) && filemtime($destination) >= filemtime($source) && filesize($destination) < $sourceSize)
    {
        $summary['skipped']++;
        continue;
    }

    if($dryRun)
    {
        echo "WOULD_CONVERT\t{$source}\n";
        $summary['converted']++;
        continue;
    }

    $temp = $destination.'.tmp-'.getmypid().'-'.uniqid('', true).'.webp';
    try
    {
        WebPConvert::convert($source, $temp, array(
            'quality' => $quality,
            'metadata' => 'none',
            'converters' => array('cwebp','imagick','imagemagick','gd')
        ));

        if(!$isValidWebp($temp) || filesize($temp) >= $sourceSize)
        {
            if(is_file($temp)) @unlink($temp);
            $summary['skipped']++;
            echo "NOT_SMALLER\t{$source}\n";
            continue;
        }

        if(!@rename($temp, $destination)) throw new RuntimeException('Không thể ghi file đích');
        $webpSize = filesize($destination);
        $summary['converted']++;
        $summary['source_bytes'] += $sourceSize;
        $summary['webp_bytes'] += $webpSize;
        echo "CONVERTED\t{$source}\t{$sourceSize}\t{$webpSize}\n";
    }
    catch(Throwable $exception)
    {
        if(is_file($temp)) @unlink($temp);
        $summary['failed']++;
        fwrite(STDERR, "FAILED\t{$source}\t".$exception->getMessage()."\n");
    }
}

echo json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL;
exit($summary['failed'] > 0 ? 2 : 0);
