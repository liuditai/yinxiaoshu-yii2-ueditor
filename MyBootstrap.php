<?php
namespace yinxiaoshu\ueditor;

use yii\base\BootstrapInterface;
use yii\base\Application;
use Yii;

class MyBootstrap implements BootstrapInterface
{
	public function bootstrap($app)
	{
		$app->on(Application::EVENT_BEFORE_REQUEST,function($event){
			$moduleName = 'ueditor';
			if (isset(Yii::$app->params['ueditor']['moduleName'])) {
				$moduleName = Yii::$app->params['ueditor']['moduleName'];
			}
			Yii::$app->modules = array_merge(Yii::$app->modules,[
				$moduleName => [
					'class' => 'yinxiaoshu\ueditor\Module'
				]
			]);
		});
	}
}
