<?php
/**
 *
 * @author MaximAL
 * @since 2018-12-09
 * @date 2018-12-09
 * @time 17:13
 * @copyright © MaximAL, Sijeko 2018
 */

const CWEBP_REGEX = '/public\s+static\s+\$cwebpCommand\s+=\s+\'(.+)\'\s*;/i';

if (preg_match(CWEBP_REGEX, file_get_contents(__DIR__ . '/WebpThumbnailer.php'), $match)) {
	$cwebpCommand = $match[1];
} else {
	$cwebpCommand = 'cwebp';
}

$out = null;
$ret = null;
exec($cwebpCommand, $out, $ret);

if ($ret !== 0) {
	echo 'WebP command `', $cwebpCommand, '` not found.', PHP_EOL;
	echo 'Locate `cwepb` coder binary and set the `WebpThumbnailer::$cwebpCommand`';
	echo ' static property (default is `cwebp`).', PHP_EOL;
} else {
	echo 'WebP command `', $cwebpCommand, '` OK.', PHP_EOL;
}

exit($ret);
