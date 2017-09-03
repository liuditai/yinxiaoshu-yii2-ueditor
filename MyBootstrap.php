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
			Yii::$app->modules = array_merge([
				$moduleName => [
					'class' => 'yinxiaoshu\ueditor\Module'
				]
			],Yii::$app->modules);
			
			// 使得以 $moduleName 打头的 url 都从这个模块中来处理
			$rule = Yii::createObject([
				'class' => 'yii\web\UrlRule',
				'pattern' => $moduleName.'/<controller>/<action>',
				'route' => $moduleName.'/<controller>/<action>',
			]);
			array_unshift(Yii::$app->urlManager->rules,$rule);
		});
	}
}
