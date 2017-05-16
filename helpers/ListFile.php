<?php

namespace yinxiaoshu\ueditor\helpers;

use Yii;

class ListFile 
{
	public static function doIt($descend=true)
	{
		$CONFIG = Config::doIt();
		$request = Yii::$app->request;
		$action = $request->get('action');

		switch ($action) {
			case 'listfile':
				$allowFiles = $CONFIG['fileManagerAllowFiles'];
				$listSize = $CONFIG['fileManagerListSize'];
				$path = $CONFIG['fileManagerListPath'];
				break;
			case 'listimage':
			default:
				$allowFiles = $CONFIG['imageManagerAllowFiles'];
				$listSize = $CONFIG['imageManagerListSize'];
				$path = $CONFIG['imageManagerListPath'];
		}
		$allowFiles = substr(str_replace(".", "|", join("", $allowFiles)), 1);

		/* 获取参数 */
		$size = htmlspecialchars($request->get('size',$listSize));
		$start = htmlspecialchars($request->get('start',0));
		$end = $start + $size;

		/* 获取文件列表 */
		$ueditor_image_path = "@webroot/upload";
		if (isset(Yii::$app->params['ueditor'],Yii::$app->params['ueditor']['uploadPath'])) {
			$ueditor_image_path = Yii::$app->params['ueditor']['uploadPath'];
		}
		$path = Yii::getAlias($ueditor_image_path . (substr($path, 0, 1) == "/" ? "":"/") . $path);
		$files = self::getFiles($path, $allowFiles);

		if (!count($files)) {
		    return array(
		        "state" => "no match file",
		        "list" => array(),
		        "start" => $start,
		        "total" => count($files)
		    );
		}

		usort($files,self::buildSorter($descend));

		/* 获取指定范围的列表 */
		$len = count($files);
		for ($i = $start, $list = array(); $i < min($end,$len); $i++) {
			$list[] = $files[$i];
		}

		$result = array(
			"state" => "SUCCESS",
			"list" => $list,
			"start" => $start,
			"total" => count($files)
		);

		return $result;

	}

	protected static function getFiles($path, $allowFiles, &$files = array())
	{
    if (!is_dir($path)) return null;
    if(substr($path, strlen($path) - 1) != '/') $path .= '/';
    $handle = opendir($path);
    while (false !== ($file = readdir($handle))) {
        if ($file != '.' && $file != '..') {
            $path2 = $path . $file;
            if (is_dir($path2)) {
                self::getFiles($path2, $allowFiles, $files);
            } else {
                if (preg_match("/\.(".$allowFiles.")$/i", $file)) {
                    $files[] = array(
                        'url'=> substr($path2, strlen($_SERVER['DOCUMENT_ROOT'])),
                        'mtime'=> filemtime($path2)
                    );
                }
            }
        }
    }
    return $files;		
	}

	protected static function buildSorter($descend)
	{
		/**
		* 返回 false 就是第一个排在前
		* 返回 true 就是第二个排在前
		*/
		return function ($a,$b) use ($descend) {
			if ($a['mtime'] > $b['mtime']) {
				return $descend ? false : true;
			} else if ($a['mtime'] == $b['mtime']) {
				return 0;
			} else {
				return $descend? true : false;
			}					
		};
	} 
}