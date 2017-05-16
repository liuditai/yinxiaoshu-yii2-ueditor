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
			Yii::$app->modules = array_merge(Yii::$app->modules,[
				'ueditor' => [
					'class' => 'yinxiaoshu\ueditor\Module'
				]
			]);
		});
	}
}