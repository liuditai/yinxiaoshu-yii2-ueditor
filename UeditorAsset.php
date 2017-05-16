<?php 

namespace yinxiaoshu\ueditor;

use yii\web\AssetBundle;

class UeditorAsset extends AssetBundle
{
	public $sourcePath = '@yinxiaoshu/ueditor/source/1_4_3_3';
	public $css = [
	];
	public $js = [
		'ueditor.config.js',
		'ueditor.all.min.js',
	];
	public $depends = [
		'yii\web\YiiAsset',
		'yii\bootstrap\BootstrapAsset',
	];
}