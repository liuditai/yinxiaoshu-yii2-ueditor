<?php

namespace yinxiaoshu\ueditor\helpers;

use Yii;
use Imagine\Image\ImageInterface;
use Imagine\Image\Box;
use Imagine\Image\BoxInterface;
use Imagine\Image\Point;
use GifFrameExtractor\GifFrameExtractor;
use GifCreator\GifCreator;
use yii\base\InvalidParamException;
use yii\base\InvalidConfigException;

class ResizeImage
{
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
    public static $driver = [self::DRIVER_GMAGICK, self::DRIVER_IMAGICK, self::DRIVER_GD2];
    /**
     * @var ImagineInterface instance.
     */
    private static $_imagine;

    /**
     * Returns the `Imagine` object that supports various image manipulations.
     * @return ImagineInterface the `Imagine` object
     */
    public static function getImagine()
    {
        if (self::$_imagine === null) {
            self::$_imagine = static::createImagine();
        }

        return self::$_imagine;
    }

    /**
     * Creates an `Imagine` object based on the specified [[driver]].
     * @return ImagineInterface the new `Imagine` object
     * @throws InvalidConfigException if [[driver]] is unknown or the system doesn't support any [[driver]].
     */
    protected static function createImagine()
    {
        foreach ((array) static::$driver as $driver) {
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
                    throw new InvalidConfigException("Unknown driver: $driver");
            }
        }
        throw new InvalidConfigException('Your system does not support any of these drivers: ' . implode(',', (array) static::$driver));
    }	
	
    protected static function getBox(BoxInterface $sourceBox, $width, $height, $keepAspectRatio = true)
    {
        if ($width === null && $height === null) {
            throw new InvalidParamException('Width and height cannot be null at same time.');
        }

        $ratio = $sourceBox->getWidth() / $sourceBox->getHeight();
        if ($keepAspectRatio === false) {
            if ($height === null) {
                $height = ceil($width / $ratio);
            } elseif ($width === null) {
                $width = ceil($height * $ratio);
            }
        } else {
            if ($height === null) {
                $height = ceil($width / $ratio);
            } elseif ($width === null) {
                $width = ceil($height * $ratio);
            } elseif ($width / $height > $ratio) {
                $width = $height * $ratio;
            } else {
                $height = $width / $ratio;
            }
        }

        return new Box($width, $height);
    }

    protected static function ensureImageInterfaceInstance($image)
    {
        if ($image instanceof ImageInterface) {
            return $image;
        }

        if (is_resource($image)) {
            return static::getImagine()->read($image);
        }

        if (is_string($image)) {
            return static::getImagine()->open(Yii::getAlias($image));
        }

        throw new InvalidParamException('File should be either ImageInterface, resource or a string containing file path.');
    }

	protected static function resize($image, $width, $height, $keepAspectRatio = true)
	{
		$img = static::ensureImageInterfaceInstance($image);
		$sourceBox = $img->getSize();
		$destinationBox = static::getBox($sourceBox, $width, $height, $keepAspectRatio);
		if ($sourceBox->getWidth() >= $destinationBox->getWidth() && $sourceBox->getHeight() >= $destinationBox->getHeight()){
			return $img->resize($destinationBox);
		} else {
			return $img;
		}
		
	}
	public static function doIt($url)
	{
		$piece = str_replace('/',DIRECTORY_SEPARATOR,$url);
		$path = Yii::getAlias('@webroot') . $piece;
		$type = strtolower(strrchr($url,'.'));

		// 缩略后的图片尺寸
		$fixed_box = [800,null];
		if (isset(Yii::$app->params['ueditor'],Yii::$app->params['ueditor']['imageSize'])) {
			$fixed_box = Yii::$app->params['ueditor']['imageSize'];
		}
        $keepAspectRatio = false;

        if (isset(Yii::$app->params['ueditor']['keepAspectRatio'])) {
            $keepAspectRatio = Yii::$app->params['ueditor']['keepAspectRatio'];
        }

		if (in_array($type,['.jpg','.jpeg','.png'])) {
			$fixed_image = static::resize($path,$fixed_box[0],$fixed_box[1],$keepAspectRatio );
			if (isset(Yii::$app->params['ueditor']['watermarkImg'])) {
				static::addWatermark($fixed_image,Yii::$app->params['ueditor']['watermarkImg'])->save($path);
			} else {
				$fixed_image->save($path);
			}
		} elseif (strcmp('.gif',$type) == 0) {
			if (GifFrameExtractor::isAnimatedGif($path)) {
				$gfe = new GifFrameExtractor();
				$gfe->extract($path,true);

				$frames = $gfe->getFrameImages();
				$durations = $gfe->getFrameDurations();

				$new_images = array(); // 存放缩略后的图片的地址

				$random_str = Yii::$app->getSecurity()->generateRandomString();
				for ($i = 0; $i < count($durations); $i++) {
					$new_image_path = Yii::getAlias('@runtime/') . $random_str . $i . '.jpg';
					imagejpeg($frames[$i],$new_image_path);
					$fixed_image = static::resize($new_image_path,$fixed_box[0],$fixed_box[1],$keepAspectRatio);
					if (isset(Yii::$app->params['ueditor']['watermarkImg'])) {
						static::addWatermark($fixed_image,Yii::$app->params['ueditor']['watermarkImg'])->save($new_image_path);
					} else {
						$fixed_image->save($new_image_path);
					}
					array_push($new_images,$new_image_path);
					unset($frames[$i]);
				}
				unset($gfe);

				$gc = new GifCreator();
				$gc->create($new_images, $durations, 0);
				foreach($new_images as $image) {
					unlink($image);
				}
				$gifBinary = $gc->getGif();
				unset($gc);
				file_put_contents($path, $gifBinary);
				unset($gifBinary);
			}
		} else {
			$path = false;
		}
		return $path;
	}

	protected static function addWatermark(ImageInterface $image, $watermark = null)
	{
		if (is_null($watermark)) {
			$imagine = static::getImagine();
			$watermark_str = base64_decode(WaterMark::$base64_str);
			$watermark2 = $imagine->load($watermark_str);
		} else {
			$watermark2 = static::ensureImageInterfaceInstance($watermark);
		}

		$w_box = $watermark2->getSize();
		$ori_box = $image->getSize();
		if ($ori_box->getWidth() >= $w_box->getWidth() && $ori_box->getHeight() >= $w_box->getHeight()) {
			$start = [$ori_box->getWidth() - $w_box->getWidth() , $ori_box->getHeight() - $w_box->getHeight()];
			return static::watermark($image,$watermark2,$start);
		} else {
			return $image;
		}
	}
	
    public static function watermark($image, $watermarkImage, array $start = [0, 0])
    {
        if (!isset($start[0], $start[1])) {
            throw new InvalidParamException('$start must be an array of two elements.');
        }
        $img = static::ensureImageInterfaceInstance($image);
        $watermark = static::ensureImageInterfaceInstance($watermarkImage);
        $img->paste($watermark, new Point($start[0], $start[1]));
        return $img;
    }	
}
