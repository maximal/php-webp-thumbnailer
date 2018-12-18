# PHP thumbnailer with WebP support

`WebpThumbnailer` is a thumbnail helper which allows you to generate
and cache image thumbnails in your PHP application on the fly.


## Installation

Install this library with Composer:
```bash
php composer.phar require "maximal/php-webp-thumbnailer" "*"
```
or add
```
"maximal/php-webp-thumbnailer": "*"
```
to the `require` section of your app’s `composer.json` file.


## Checking the environment

You will need WebP coder (`cwebp` command) installed in your system.

For instance in Ubuntu/Debian it is included in `webp` package:
```bash
sudo apt install webp
```

Check the command:
```bash
cwebp -version
```
You should get an output with version number (like `0.6.1`).

If you have installed `cwebp` to a different command or path, configure
the static property `WebpThumbnailer::$cwebpCommand` before using the helper
(see the example below).

More info about WebP: https://developers.google.com/speed/webp/


## Generating thumbnails

Use this thumbnailer in your PHP application:
```php
use maximal\thumbnail\WebpThumbnailer;

echo WebpThumbnailer::picture('/path/to/img/image.png', $width, $height);
```

More options (`outbound`  instead of default `inset`; `alt` and `class`
attribute added):
```php
use maximal\thumbnail\WebpThumbnailer;

echo WebpThumbnailer::picture(
	'/path/to/img/image.png',
	$width,
	$height,
	false,
	['alt' => 'Alt attribute', 'class' => 'img-responsive']
);
```

Custom `cwebp` command:
```php
use maximal\thumbnail\WebpThumbnailer;

WebpThumbnailer::$cwebpCommand = '/usr/local/bin/cwebp';
echo WebpThumbnailer::picture('/path/to/img/image.jpg', $width, $height);
```

The helper’s `picture()` method uses modern `<picture>` HTML tag as follows:
```html
<picture data-cache="hit|new">
	<source srcset="/assets/thumbnails/...image.png.webp" type="image/webp" />
	<img src="/assets/thumbnails/...image.png" other-attributes="" />
</picture>
```

Here you have `image/webp` source for
[browsers which support WebP images](https://caniuse.com/#search=WebP)
and traditional (PNG, JPEG, TIFF, GIF) image fallback.


# Author

* Websites: https://maximals.ru and https://sijeko.ru
* StackOverflow story: http://stackoverflow.com/users/story/1021887
* Twitter: https://twitter.com/almaximal
* Telegram: https://t.me/maximal
