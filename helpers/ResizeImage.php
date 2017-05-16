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

class ResizeImage extends BaseImage
{
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

		if (in_array($type,['.jpg','.jpeg','.png'])) {
			$fixed_image = static::resize($path,$fixed_box[0],$fixed_box[1],false);
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
					$fixed_image = static::resize($new_image_path,$fixed_box[0],$fixed_box[1],false);
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
		if (static::isUpscaling($w_box,$ori_box)) {
			$start = [$ori_box->getWidth() - $w_box->getWidth() , $ori_box->getHeight() - $w_box->getHeight()];
			return static::watermark($image,$watermark2,$start);
		} else {
			return $image;
		}	
	}      	
}