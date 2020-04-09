<?php

namespace app\modules\dataGenerator\components\generators;

use app\models\Contact;
use app\models\queries\ContactQuery;
use app\models\User;
use Faker\Provider\en_US\Person;
use Yii;
use yii\db\ActiveRecord;
use yii\helpers\Console;
use yii\validators\NumberValidator;

class ContactFixture extends ARGenerator
{
    protected function providers(): array
    {
        return [Person::class];
    }

    /**
     * @return Contact|null
     * @throws ARGeneratorException
     */
    protected function factoryModel(): ?ActiveRecord
    {
        $users = $this->findUsers();

        if (empty($users)) {
            return null;
        }

        $model = new Contact();

        $model->user_id      = $users[0];
        $model->link_user_id = $users[1];
        $model->name         = self::getFaker()->name;
        $this->setDRP($model);

        return $model;
    }

    /**
     * @return array
     * @throws ARGeneratorException
     */
    private function findUsers(): array
    {
        $userQty = User::find()->active()->count();

        /** @var int $userIdFrom user, who can has additional Contact */
        $userIdFrom = User::find()
            ->select('user.id, count(contact.id) as n_contact')
            ->joinWith(['contactsFromMe' => static function (ContactQuery $query) {
                $query->virtual(false, 'andOnCondition');
            }])
            ->active()
            ->groupBy('user.id')
            ->having('n_contact < :nUser', [':nUser' => $userQty])
            ->orderBy('n_contact')
            ->limit(1)
            ->scalar();

        if (!$userIdFrom) {
            $class = self::classNameModel();
            $msg = "\n$class: creation skipped. ";
            $msg .= "Either no active User, or all Users have full set of Contacts.\n";
            $msg .= "\nIt's not error - few iterations later new User will be generated.\n";
            Yii::$app->controller->stdout($msg, Console::BG_GREY);

            return [];
        }

        /** @var int $userIdTo user, with whom $userIdFrom has no contact yet */
        $userIdTo = User::find()
            ->select('user.id')
            ->joinWith(['contactsToMe' => static function (ContactQuery $query) use ($userIdFrom) {
                $query->userOwner($userIdFrom, 'andOnCondition');
            }])
            ->active()
            ->andWhere('user.id <> :userIdFrom', [':userIdFrom' => $userIdFrom])
            ->limit(1)
            ->scalar();

        if (!$userIdTo) {
            throw new ARGeneratorException("Expected to find \$userIdTo. \$userIdFrom='$userIdFrom'");
        }

        return [$userIdFrom, $userIdTo];
    }

    private function setDRP(Contact $model): void
    {
        $hasValidator = false;

        foreach ($model->activeValidators as $v) {
            if (in_array('debt_redistribution_priority', $v->attributes, true) &&  $v instanceof NumberValidator) {
                $hasValidator = true;
                $model->debt_redistribution_priority = self::getFaker()->numberBetween($v->min, $v->max);
                break;
            }
        }

        if (!$hasValidator) {
            $model->debt_redistribution_priority = self::getFaker()->numberBetween($v->min, 255);
        }
    }
}