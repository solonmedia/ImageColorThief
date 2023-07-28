<?php namespace ProcessWire;

require_once( __DIR__ . '/vendor/autoload.php' );

use ColorThief\ColorThief; 

class ImageColorThief extends WireData implements Module {

	/**
	 * Module information
	 */
	public static function getModuleInfo() {
		return array(
			'title' => "Image Color Thief",
			'summary' => 'Adds methods to extract dominant color palettes from an image or image edge.',
			'version' => '0.0.1',
			'author' => 'Jacob Gorny',
			'href' => 'https://github.com/',
			'icon' => 'file-image-o',
			'autoload' => true,
			'requires' => 'ProcessWire>=3.0.0, PHP>=7.2',
		);
	}

	/**
	 * Ready
	 */
	public function ready() {
		$this->addHookMethod('Pageimage::maincolor', $this, 'maincolor');
		$this->addHookMethod('Pageimage::palette', $this, 'palette');
	}

    /**
     * Get the main color from a Pageimage object
     */

    public function maincolor(Hookevent $event) {

        $quality = 10;
        $areaType = 'full';
        $areaPrecision = 1;
        $outputFormat = 'hex';
        $area = [];

        $allowedExt = [
            'jpg',
            'jpeg',
            'png',
            'webp',
        ];

        $f_pageImage = $event->object;
        $page = $f_pageImage->page;
        $u_Images = $page->getUnformatted($f_pageImage->field->name);
        $pageImage = $u_Images->getFile($f_pageImage->basename);

        $imgHeight = (int) $pageImage->height;
        $imgWidth = (int) $pageImage->width;
        
        $focus = $pageImage->focus();
        $focus_x = $focus['left'] * 0.0100 * $imgWidth;
        $focus_y = $focus['top'] * 0.0100 * $imgHeight;

        $options = ($event->arguments) ? $event->arguments(0) : [];

        if(array_key_exists('quality',$options)) {
            $quality = ( (int) $options['quality'] >= 1 ) ?
                ( (int) $options['quality']> 10)
                    ? 10 :
                    (int) $options['quality']
                : 1;
        }

        if (!array_key_exists('area',$options) && array_key_exists('areatype',$options)) {
            $areaPrecision = (array_key_exists('areaprecision',$options)) ? (int) $options['areaprecision'] : 1;

            if ($focus['default']) {
                $focus_area = [
                    'x' => 0,
                    'y' => 0,
                    'w' => $imgWidth,
                    'h' => $imgHeight,
                ];
            } else {
                $tx = (int) round( $focus_x - ($areaPrecision / 2) );
                $ty = (int) round( $focus_y - ($areaPrecision / 2) );
                $tx = ($tx < 0) ? 0 : ( ($tx > ($imgWidth - $areaPrecision) ) ? ($imgWidth - $areaPrecision) : $tx );
                $ty = ($ty < 0) ? 0 : ( ($ty > ($imgHeight - $areaPrecision) ) ? ($imgHeight - $areaPrecision) : $ty );
                $focus_area = [
                    'x' => $tx,
                    'y' => $ty,
                    'w' => $areaPrecision,
                    'h' => $areaPrecision,
                ];
            }

            switch($options['areatype']) {
                case 'full':
                    $area = [
                        'x' => 0,
                        'y' => 0,
                        'w' => $imgWidth,
                        'h' => $imgHeight,
                    ];
                    break;
                case 'top':
                    $area = [
                        'x' => 0,
                        'y' => 0,
                        'w' => $imgWidth,
                        'h' => $areaPrecision,
                    ];
                    break;
                case 'bottom':
                    $area = [
                        'x' => 0,
                        'y' => $imgHeight - $areaPrecision,
                        'w' => $imgWidth,
                        'h' => $areaPrecision,
                    ];
                    break;
                case 'left':
                    $area = [
                        'x' => 0,
                        'y' => 0,
                        'w' => $areaPrecision,
                        'h' => $imgHeight,
                    ];
                    break;
                case 'right':
                    $area = [
                        'x' => $imgWidth - $areaPrecision,
                        'y' => 0,
                        'w' => $areaPrecision,
                        'h' => $imgHeight,
                    ];
                    break;
                case 'top-left':
                    $area = [
                        'x' => 0,
                        'y' => 0,
                        'w' => $areaPrecision,
                        'h' => $areaPrecision,
                    ];
                    break;
                case 'bottom-left':
                    $area = [
                        'x' => 0,
                        'y' => $imgHeight - $areaPrecision,
                        'w' => $areaPrecision,
                        'h' => $areaPrecision,
                    ];
                    break;
                case 'top-right':
                    $area = [
                        'x' => $imgWidth - $areaPrecision,
                        'y' => 0,
                        'w' => $areaPrecision,
                        'h' => $areaPrecision,
                    ];
                    break;
                case 'bottom-right':
                    $area = [
                        'x' => $imgWidth - $areaPrecision,
                        'y' => $imgHeight - $areaPrecision,
                        'w' => $areaPrecision,
                        'h' => $areaPrecision,
                    ];
                    break;
                case 'focus' :
                    $area = [
                        'x' => $focus_area['x'],
                        'y' => $focus_area['y'],
                        'w' => $focus_area['w'],
                        'h' => $focus_area['h'],
                    ];
                    break;
                default :
                    $area = [
                        'x' => 0,
                        'y' => 0,
                        'w' => $imgWidth,
                        'h' => $imgHeight,
                    ];
            }
        }

        if (array_key_exists('area',$options)) {
            $area = $options['area']; //If areatype and area are both defined, area array takes precendence
        }
        
        $options['area'] = $area; //Makes sure to return actual calc xywh area values to $options if just areatype is set.

        $valid_formats = [
            'rgb',
            'hex',
            'int',
            'array',
            'obj',
        ];

        if (array_key_exists('outputformat',$options)) {
            $outputFormat = in_array($options['outputformat'],$valid_formats) ? $options['outputformat'] : 'hex';
        }

        if($pageImage->filedata('mainColor') && $pageImage->filedata('mcOptions') === $options) {
            $event->return = $pageImage->filedata('mainColor');
            return;
        }

        if (in_array($pageImage->ext, $allowedExt)) {
            $mainColor = ColorThief::getColor($pageImage->filename, $quality, $area, $outputFormat);
            $cur_data = $pageImage->filedata;
            $pageImage->filedata(array_merge($cur_data,[
                'mainColor' => $mainColor,
                'mcOptions' => $options,
            ]));
            $pageImage->save();
            $event->return = $mainColor;
            return;
        } else {
            $event->return = false;
            return;
        }
    }

    public function palette(Hookevent $event) {

        $colorCount = 10;
        $quality = 10;
        $areaType = 'full';
        $areaPrecision = 1;
        $outputFormat = 'hex';

        $allowedExt = [
            'jpg',
            'jpeg',
            'png',
            'webp',
        ];

        $f_pageImage = $event->object;
        $page = $f_pageImage->page;
        $u_Images = $page->getUnformatted($f_pageImage->field->name);
        $pageImage = $u_Images->getFile($f_pageImage->basename);

        $imgHeight = (int) $pageImage->height;
        $imgWidth = (int) $pageImage->width;

        $focus = $pageImage->focus();
        $focus_x = $focus['left'] * 0.0100 * $imgWidth;
        $focus_y = $focus['top'] * 0.0100 * $imgHeight;

        $options = ($event->arguments) ? $event->arguments(0) : [];

        $colorCount = (array_key_exists('colorcount',$options)) ? (int) $options['colorcount'] : 10;

        if(array_key_exists('quality',$options)) {
            $quality = ( (int) $options['quality'] >= 1 ) ?
                ( (int) $options['quality']> 10)
                    ? 10 :
                    (int) $options['quality']
                : 1;
        }

        if (!array_key_exists('area',$options) && array_key_exists('areatype',$options)) {
            $areaPrecision = (array_key_exists('areaprecision',$options)) ? (int) $options['areaprecision'] : 1;
 
            if ($focus['default']) {
                $focus_area = [
                    'x' => 0,
                    'y' => 0,
                    'w' => $imgWidth,
                    'h' => $imgHeight,
                ];
            } else {
                $tx = (int) round( $focus_x - ($areaPrecision / 2) );
                $ty = (int) round( $focus_y - ($areaPrecision / 2) );
                $tx = ($tx < 0) ? 0 : ( ($tx > ($imgWidth - $areaPrecision) ) ? ($imgWidth - $areaPrecision) : $tx );
                $ty = ($ty < 0) ? 0 : ( ($ty > ($imgHeight - $areaPrecision) ) ? ($imgHeight - $areaPrecision) : $ty );
                $focus_area = [
                    'x' => $tx,
                    'y' => $ty,
                    'w' => $areaPrecision,
                    'h' => $areaPrecision,
                ];
            }

            switch($options['areatype']) {
                case 'full':
                    $area = [
                        'x' => 0,
                        'y' => 0,
                        'w' => $imgWidth,
                        'h' => $imgHeight,
                    ];
                    break;
                case 'top':
                    $area = [
                        'x' => 0,
                        'y' => 0,
                        'w' => $imgWidth,
                        'h' => $areaPrecision,
                    ];
                    break;
                case 'bottom':
                    $area = [
                        'x' => 0,
                        'y' => $imgHeight - $areaPrecision,
                        'w' => $imgWidth,
                        'h' => $areaPrecision,
                    ];
                    break;
                case 'left':
                    $area = [
                        'x' => 0,
                        'y' => 0,
                        'w' => $areaPrecision,
                        'h' => $imgHeight,
                    ];
                    break;
                case 'right':
                    $area = [
                        'x' => $imgWidth - $areaPrecision,
                        'y' => 0,
                        'w' => $areaPrecision,
                        'h' => $imgHeight,
                    ];
                    break;
                case 'top-left':
                    $area = [
                        'x' => 0,
                        'y' => 0,
                        'w' => $areaPrecision,
                        'h' => $areaPrecision,
                    ];
                    break;
                case 'bottom-left':
                    $area = [
                        'x' => 0,
                        'y' => $imgHeight - $areaPrecision,
                        'w' => $areaPrecision,
                        'h' => $areaPrecision,
                    ];
                    break;
                case 'top-right':
                    $area = [
                        'x' => $imgWidth - $areaPrecision,
                        'y' => 0,
                        'w' => $areaPrecision,
                        'h' => $areaPrecision,
                    ];
                    break;
                case 'bottom-right':
                    $area = [
                        'x' => $imgWidth - $areaPrecision,
                        'y' => $imgHeight - $areaPrecision,
                        'w' => $areaPrecision,
                        'h' => $areaPrecision,
                    ];
                    break;
                case 'focus' :
                    $area = [
                        'x' => $focus_area['x'],
                        'y' => $focus_area['y'],
                        'w' => $focus_area['w'],
                        'h' => $focus_area['h'],
                    ];
                    break;
                default :
                    $area = [
                        'x' => 0,
                        'y' => 0,
                        'w' => $imgWidth,
                        'h' => $imgHeight,
                    ];
            }
        }

        if (array_key_exists('area',$options)) {
            $area = $options['area']; //If areatype and area are both defined, area array takes precendence
        }
        
        $options['area'] = $area; //Makes sure to return actual calc xywh area values to $options if just areatype is set.
        
        $valid_formats = [
            'rgb',
            'hex',
            'int',
            'array',
            'obj',
        ];

        if (array_key_exists('outputformat',$options)) {
            $outputFormat = in_array($options['outputformat'],$valid_formats) ? $options['outputformat'] : 'hex';
        }
        if($pageImage->filedata('colorPalette') && $pageImage->filedata('cpOptions') === $options) {
            $event->return = $pageImage->filedata('colorPalette');
            return;
        }

        if( in_array($pageImage->ext, $allowedExt) ) {
            $colorPalette = ColorThief::getPalette($pageImage->filename, $colorCount, $quality, $area, $outputFormat);
            $cur_data = $pageImage->filedata;
            $pageImage->filedata(array_merge($cur_data,[
                'colorPalette' => $colorPalette,
                'cpOptions' => $options,
            ])
            );
            $pageImage->save();
            $event->return = $colorPalette;
            return;
        } else {
            $event->return = [];
            return;
        }

    }

}
