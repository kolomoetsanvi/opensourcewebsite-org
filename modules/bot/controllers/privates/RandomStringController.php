<?php

namespace app\modules\bot\controllers\privates;

use Yii;
use app\modules\bot\components\Controller as Controller;
use app\modules\bot\components\response\SendMessageCommand;

/**
 * Class RandomStringController
 *
 * @package app\modules\bot\controllers
 */
class RandomStringController extends Controller
{
    /**
     * @return string
     */
    public function actionIndex($message = '')
    {
        //TODO add flexible int $n (1-1024) from $message
        $n = 10;
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';

        for ($i = 0; $i < $n; $i++) {
            $index = rand(0, strlen($characters) - 1);
            $randomString .= $characters[$index];
        }

        return [
            new SendMessageCommand(
                $this->getTelegramChat()->chat_id,
                $randomString
            ),
        ];
    }
}
