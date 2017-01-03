<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */
namespace yuncms\user\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * Coin model
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $type
 * @property double $amount
 * @property integer $created_at
 */
class Coin extends ActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_coins}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'created_at',
                ],
            ]
        ];
    }

    /** @inheritdoc */
    public function attributeLabels()
    {
        return [
            'type' => Yii::t('user', 'Coin Type'),
            'msg' => Yii::t('user', 'Coin Msg'),
            'value' => Yii::t('user', 'Coin Value'),
            'created_at' => Yii::t('user', 'Created At'),
        ];
    }

    public static function create($attribute)
    {
        $model = new static ($attribute);
        if ($model->save()) {
            return $model;
        }
        return false;
    }
}