<?php 

namespace yinxiaoshu\ueditor\helpers;

use Yii;

/**
 * 获得config.json的配置
*/
class Config
{
	public static function doIt() {
        $CONFIG = json_decode(preg_replace(
            "/\/\*[\s\S]+?\*\//", 
            "", 
            file_get_contents(Yii::getAlias("@yinxiaoshu/ueditor/source/1_4_3_3/php/config.json"))
            ),true
        );
        return $CONFIG;	
	}
}