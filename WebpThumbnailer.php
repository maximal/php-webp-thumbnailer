<?php
/**
 *
 * @author MaximAL
 * @since 2018-12-07
 * @date 2018-12-07
 * @time 19:29
 * @copyright © MaximAL, Sijeko 2018
 */

namespace maximal\thumbnail;

use Imagine\Image\Box;
use Imagine\Image\ImagineInterface;
use Imagine\Image\ManipulatorInterface;
use RuntimeException;

/**
 * `WebpThumbnailer` class generates WebP thumbnails as well as thumbnails in original image format (like PNG or JPEG)
 *
 * It can be useful for optimizing graphics in your web application.
 * @package app\modules\maximal\thumbnail
 */
class WebpThumbnailer
{
	/**
	 * @var string Path of thumbnail cache directory
	 */
	public static $cachePath = 'assets/thumbnails';

	/**
	 * @var string URL of thumbnail cache directory
	 */
	public static $cacheUrl = '/assets/thumbnails';

	/**
	 * @var int Mode for new cache directories
	 */
	public static $dirMode = 0755;

	/**
	 * WebP coder command.
	 *
	 * Set to `/usr/local/bin/cwebp` or other, depending on your environment.
	 * @var string
	 */
	public static $cwebpCommand = 'cwebp';

	/**
	 * Whether to use lossless encoding for PNG images
	 * @var bool
	 */
	public static $pngLossless = false;

	/**
	 * Whether to use lossless encoding for JPEG images
	 * @var bool
	 */
	public static $jpgLossless = false;

	/**
	 * @var ImagineInterface Imagine instance.
	 */
	private static $imagine;

	/**
	 * GD2 driver definition for Imagine implementation using the GD library.
	 */
	const DRIVER_GD2 = 'gd2';
	/**
	 * imagick driver definition.
	 */
	const DRIVER_IMAGICK = 'imagick';
	/**
	 * gmagick driver definition.
	 */
	const DRIVER_GMAGICK = 'gmagick';

	/**
	 * @var array|string the driver to use. This can be either a single driver name or an array of driver names.
	 * If the latter, the first available driver will be used.
	 */
	public static $drivers = [self::DRIVER_GMAGICK, self::DRIVER_IMAGICK, self::DRIVER_GD2];


	/**
	 * @param string $path Path of the original image file
	 * @param int $width Width of generated thumbnail
	 * @param int $height Height of generated thumbnail
	 * @param bool $inset `true` for `THUMBNAIL_INSET` and `false` for `THUMBNAIL_OUTBOUND` mode
	 * @param array $options Key-value pairs of HTML attributes for the `&lt;img&gt;` tag
	 * @return string `&lt;picture&gt;` HTML tag with WebP source and `&lt;img&gt;` fallback (thumbnail of initial type)
	 */
	public static function picture($path, $width, $height, $inset = true, $options = [])
	{
		if (!is_file($path)) {
			return sprintf(
				'<img src="#" alt="No File: %s" />',
				htmlspecialchars($path)
			);
		}

		$mode = $inset ? ManipulatorInterface::THUMBNAIL_INSET : ManipulatorInterface::THUMBNAIL_OUTBOUND;

		$extension = pathinfo($path, PATHINFO_EXTENSION);
		$extensionLower = strtolower($extension);
		if ($extensionLower === 'jpeg') {
			// Normalize jpeg to jpg
			$extensionLower = 'jpg';
		}

		// Lossless?
		$lossless = $extensionLower === 'png' && static::$pngLossless
			|| $extensionLower === 'jpg' && static::$jpgLossless;

		// Paths
		$thumbnailFileName = md5($path . $width . $height . $mode . $lossless) . '.' . $extensionLower;
		$thumbnailSubDir = substr($thumbnailFileName, 0, 2);
		$thumbnailDir = static::$cachePath . DIRECTORY_SEPARATOR . $thumbnailSubDir;

		$thumbnailPath = $thumbnailDir . DIRECTORY_SEPARATOR . $thumbnailFileName;
		if (!is_file($thumbnailPath) || filemtime($thumbnailPath) < filemtime($path)) {
			// Making directory if needed
			if (!is_dir($thumbnailDir)) {
				mkdir($thumbnailDir, static::$dirMode, true);
				//$expired = true;
			}

			if ($mode === ManipulatorInterface::THUMBNAIL_OUTBOUND) {
				// THUMBNAIL_OUTBOUND
				// Calculating WebP Crop
				$image = static::getImagine()->open($path);

				$initialSize = $image->getSize();
				$iWidth = $initialSize->getWidth();
				$iHeight = $initialSize->getHeight();

				$iRatio = 1.0 * $iWidth / $iHeight;
				$ratio = 1.0 * $width / $height;

				if ($ratio > $iRatio) {
					$croppedWidth = $iWidth;
					if ($height > $iHeight) {
						$croppedHeight = $iHeight;
					} else {
						$croppedHeight = max(round($croppedWidth / $ratio), $height);
					}
					$crop = ' -crop 0 ' . round(($iHeight - $croppedHeight) / 2) . ' ' .
						$croppedWidth . ' ' . $croppedHeight;
				} else {
					$croppedHeight = $iHeight;
					if ($width > $iWidth) {
						$croppedWidth = $iWidth;
					} else {
						$croppedWidth = max(round($croppedHeight * $ratio), $width);
					}
					$crop = ' -crop ' . round(($iWidth - $croppedWidth) / 2) . ' 0 ' .
						$croppedWidth . ' ' . $croppedHeight;
				}

				$image = $image->thumbnail(new Box($width, $height), $mode)
					->save($thumbnailPath);
			} else {
				// THUMBNAIL_INSET
				// Easy: just plain Imagine thumbnail
				$crop = '';
				$image = static::getImagine()->open($path)
					->thumbnail(new Box($width, $height), $mode)
					->save($thumbnailPath);
			}

			$size = $image->getSize();
			exec(
				self::$cwebpCommand . ($lossless ? ' -lossless' : '')  . $crop .
				' -resize ' . $size->getWidth() . ' ' . $size->getHeight() . ' ' .
				escapeshellarg($path) . ' -o ' . escapeshellarg($thumbnailPath . '.webp')
			);
			$cacheHit = false;
		} else {
			$cacheHit = true;
		}

		// Building HTML attributes
		$attributes = [];
		foreach ($options as $attribute => $value) {
			$attribute = preg_replace('/[^a-z0-9_-]/i', '', $attribute);
			if (strtolower($attribute) === 'src') {
				continue;
			}
			$attributes []= $attribute . '="' . htmlspecialchars($value) . '"';
		}

		$url = static::$cacheUrl . '/' . $thumbnailSubDir . '/' . $thumbnailFileName;
		return sprintf(
			'<picture data-cache="%s"><source srcset="%s" type="image/webp" />' .
			'<img src="%s"%s /></picture>',
			$cacheHit ? 'hit' : 'new',
			$url . '.webp',
			$url,
			count($attributes) > 0 ? (' ' . implode(' ', $attributes)) : ''
		);
	}

	private static function getImagine()
	{
		if (self::$imagine === null) {
			self::$imagine = static::createImagine();
		}

		return self::$imagine;
	}

	/**
	 * Creates an `Imagine` object based on the specified [[driver]].
	 * @return ImagineInterface the new `Imagine` object
	 * @throws RuntimeException if [[driver]] is unknown or the system doesn't support any [[driver]].
	 */
	protected static function createImagine()
	{
		foreach ((array) static::$drivers as $driver) {
			switch ($driver) {
				case self::DRIVER_GMAGICK:
					if (class_exists('Gmagick', false)) {
						return new \Imagine\Gmagick\Imagine();
					}
					break;
				case self::DRIVER_IMAGICK:
					if (class_exists('Imagick', false)) {
						return new \Imagine\Imagick\Imagine();
					}
					break;
				case self::DRIVER_GD2:
					if (function_exists('gd_info')) {
						return new \Imagine\Gd\Imagine();
					}
					break;
				default:
					throw new RuntimeException("Unknown driver: $driver");
			}
		}
		throw new RuntimeException(
			'Your system does not support any of these drivers: ' .
			implode(',', (array) static::$drivers)
		);
	}
}
