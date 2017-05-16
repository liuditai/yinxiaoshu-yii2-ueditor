<?php

namespace yinxiaoshu\ueditor\controllers;

use yii\web\Controller;
use yii\filters\AccessControl;
use yii\helpers\Html;
use Yii;
use yinxiaoshu\ueditor\models\Uploader;
use yinxiaoshu\ueditor\helpers\Upload;
use yinxiaoshu\ueditor\helpers\Config;
use yinxiaoshu\ueditor\helpers\ListFile;
use yinxiaoshu\ueditor\helpers\CatchImage;
use yinxiaoshu\ueditor\helpers\ResizeImage;
use yinxiaoshu\ueditor\helpers\ConvertBmp;

/**
 * Default controller for the `ueditor` module
 */
class DefaultController extends Controller
{
    public function beforeAction($action)
    {
        $csrf_token = Yii::$app->request->get('_csrf');
        if (\yii\base\Controller::beforeAction($action)) {
            if ($this->enableCsrfValidation && Yii::$app->getErrorHandler()->exception === null && !Yii::$app->getRequest()->validateCsrfToken($csrf_token)) {
                throw new \yii\web\BadRequestHttpException(Yii::t('yii', 'Unable to verify your data submission.'));
            }
            return true;
        }

        return false;
    }

    /*
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['handler'],
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],                    
                    ]
                ],
            ],
        ];
    }
    */


    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionHandler()
    {
        $session = Yii::$app->session;
        $auth_key = Yii::$app->request->get('auth_key','auth_key');
        $action = Yii::$app->request->get('action');
        $CONFIG = Config::doIt();
        $result = null;
        if (strcmp($action,'config') === 0) {
            $result = $CONFIG;
        } else {
            if (strcmp($session->get('ueditor_auth_key','null'),$auth_key) === 0) {
                switch ($action) {
                    case 'uploadimage':
                    case 'uploadscrawl':
                    case 'uploadvideo':
                    case 'uploadfile':
                        $result = Upload::doIt();
                        ConvertBmp::doIt($result);
                        if (strcmp($result['state'],'SUCCESS') == 0) {
                            $url = $result['url'];
                            $path = ResizeImage::doIt($url);
                            if ($path) {
                                $result['size'] = filesize($path);
                            }                  
                        }
                        break;
                    case 'listimage':
                    case 'listfile':
                        $result = ListFile::doIt();
                        break;
                    case 'catchimage':
                        set_time_limit(0);
                        $result = CatchImage::doIt();
                        ConvertBmp::doIt($result);
                        if (strcmp($result['state'],'SUCCESS') == 0) {
                            for($i=0;$i<count($result['list']);$i++) {
                                if (strcmp($result['list'][$i]['state'],'SUCCESS') == 0 && in_array($result['list'][$i]['type'],['.jpg','.jpeg','.png'])) {
                                    $url = $result['list'][$i]['url'];
                                    $path = ResizeImage::doIt($url);
                                    if ($path) {
                                        $result['list'][$i]['size'] = filesize($path);
                                    }                           
                                }
                            }
                        }
                        break;
                    default:
                        $result = array(
                            'state' => '请求地址出错'
                        );
                        break;
                }                
            } else {
                $result = array(
                    'state' => '当前用户权限不足，禁止操作'
                );
            }
        }
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return $result;
    }
}
