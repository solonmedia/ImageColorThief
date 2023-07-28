# ImageColorThief

The ImageColorThief module for ProcessWire wraps the Color Thief library and adds two methods to Pageimage object.

ColorThief uses the MMCQ (modified median cut quantization) algorithm from the [Leptonica library](http://www.leptonica.com/) to extract dominant colors from images. 

The original library was written in javascript by Lokesh Dhakar [lokeshdhakar.com](http://www.lokeshdhakar.com) and ported to PHP by Kevin Subileau [kevinsubileau.fr](http://www.kevinsubileau.fr/?utm_campaign=github&utm_term=color-thief-php_readme).

The library analyzes an image and returns either the single most dominant color present in the image, or a palette of colors ranked by prevalence in the image.

Aside from requiring ProcessWire 3, the requirements are:

- PHP >= 7.2 or >= PHP 8.0
- Fileinfo extension
- One or more PHP extensions for image processing:
  - GD >= 2.0
  - Imagick >= 2.0 (but >= 3.0 for CMYK images)
  - Gmagick >= 1.0

[The PHP port project](https://github.com/ksubileau/color-thief-php/tree/master#readme)

[The original javascript project](http://github.com/lokesh/color-thief)

[Lokesh's original live demo page](https://lokeshdhakar.com/projects/color-thief/)

JPG, GIF, PNG and WEBP images are supported. Through setting configuration options, you can control the polling region that the method uses
to calculate the colors returned. I've simplified this process by adding a number of useful presets.

# Methods

The ImageColorThief module extends the ProcessWire Pageimage object by adding two methods:

## maincolor($options = [])

The `maincolor()` method by default will return the dominant color for the image.

## palette($options = [])

The `palette()` method by default will return 10 dominant colors

# Configuration options

Configuration options should be passed via an array as the only argument.

## quality (int)

`quality` determines the level of granularity used in the calculation. The lower the number, the higher the quality. Large images will create some lag on the first calculation. Default value is `10`.

## areaprecision (int)

`areaprecision` determines the dimensions of the swatch used to calculate the color dominance measured in pixels. Default value is `1`.

## areatype (str)

`areatype` allows you to easily pick a calculation preset without the headaches of doing the math yourself. There are a number of convenient presets - most needs should be met with these.

* full (default)
  
  This setting uses the entire image to calculate the main color or palette of colors.
  
* top

  This setting uses a rectangle swatch that is as wide as the image and as tall as `areaprecision` positioned at the top of the image.

* bottom

  This setting uses a rectangle swatch that is as wide as the image and as tall as `areaprecision` positioned at the bottom of the image.

* left
  
  This setting uses a rectangle swatch that is as tall as the image and as wide as `areaprecision` positioned at the left edge of the image.

* right
  
  This setting uses a rectangle swatch that is as tall as the image and as wide as `areaprecision` positioned at the right edge of the image.

* top-left

  This setting uses a square swatch with a side of `areaprecision` positioned at the top left corner of the image.

* top-right

  This setting uses a square swatch with a side of `areaprecision` positioned at the top right corner of the image.

* bottom-left

  This setting uses a square swatch with a side of `areaprecision` positioned at the bottom left corner of the image.

* bottom-right

  This setting uses a square swatch with a side of `areaprecision` positioned at the bottom right corner of the image.

* focus

  This setting uses a square swatch with a side of `areaprecision` centered around the focus point set in the image editing box. If no focus point is set on an image, it falls back to `full`.

**NOTE:** If you attempt to calculate the colors of an image using a swatch that only contains transparent pixels, an error will result. This will be handled more gracefully in a future release. The solution in general is to either use the default `full` option or increase the `areaprecision` value.

## area (array)

If you want to do the math yourself regarding the swatch calculation, you can pass along a custom array. Just note that if you define but `areatype` and `area` in your $options configuration array, the `area` array will be used.

```
$options = [
  'area' => [
    'x' => 0, //defaults to 0 if omitted
    'y' => 0, //defaults to 0 if omitted
    'w' => 1000, //defaults to image width - x if omitted
    'h' => 1000, //defaults to image height - y if omitted
  ],
];
```

## outputformat (str)

There are several options for output available:

* rgb

  Outputs `rgb(255, 255, 255)`
  
* hex (default)

  Outputs `#ffffff`
  
* int

  Outputs RGB integer format
  
* array

  Outputs `[ 0 => 255, 1 => 255, 2 => 255 ]`
  
* obj

  Outputs `ColorThief\Color` object with red, green, blue properties.

## colorcount (int)

Tells the `getpalette()` method how many colors to calculate. Duplicate color values are possible with this method. The library doesn't check for or remove duplicate colors from the array. Ignored by the `maincolor()` method.

# Performance

As noted above, the `quality` configuration option can create minor performance issues if used on large images, or if a page has a large collection of images in a field and you wish to process them all at once.

This is mitigated through utilization of the **filedata** property of the Pageimage object. When either a single color or color palette is calculated, it is saved along with the configuration options array used to generate the color or palette for the file. As long as the options do not change, the cached color values will be returned instead of recalculated. Individual images on a single page can be refreshed without affecting the data for other images on the page, or elsewhere on the system.

# Examples
```
$page = $this->wire->pages->get('name=about');

$images = $page->get('images');

$options = [
  'quality' => 10,
  'areatype' => 'focus',
  'areaprecision' => 50,
  'outputformat' => 'hex',
  'colorcount' => 5,
];

foreach($images as $image) {
    $palette = $image->palette($options);
    $maincolor = $image->maincolor($options);
    d($palette);
    d($maincolor);
}
```
![image](https://github.com/solonmedia/ImageColorThief/assets/58667283/4a0222bf-399a-4eba-8309-aba5a89dadd7)

Note that `colorcount` is ignored by the `maincolor` method.
