<?php
use yii\helpers\Url;
use yii\helpers\Html;
use yii\widgets\ListView;
use yii\bootstrap\Nav;
use yuncms\user\models\User;

/**
 * @var \yii\web\View $this
 * @var  User $model
 * @var string $type
 */
$this->context->layout = 'space';
$this->params['user'] = $model;

if ($type == 'questions') {
    $what = Yii::t('user', 'Questions');
} else if ($type == 'users') {
    $what = Yii::t('user', 'Users');
} else {
    $what = '';
}

if (!Yii::$app->user->isGuest && Yii::$app->user->id == $model->id) {//Me
    $this->title = Yii::t('user', '{who} followed {what}', [
        'who' => Yii::t('user', 'My'),
        'what' => $what
    ]);
} else {
    $this->title = Yii::t('user', '{who} followed {what}', [
        'who' => empty($model->profile->name) ? Html::encode($model->username) : Html::encode($model->profile->name),
        'what' => $what
    ]);
}

?>
<div class="stream-following">
    <?= Nav::widget([
        'options' => ['class' => 'nav nav-tabs'],
        'items' => [
            //问答
            ['label' => Yii::t('user', 'Questions'), 'url' => ['/user/profile/attention', 'id' => $model->id, 'type' => 'questions'], 'visible' => Yii::$app->hasModule('question')],
            //用户
            ['label' => Yii::t('user', 'Broadcaster'), 'url' => ['/user/profile/attention', 'id' => $model->id, 'type' => 'users'],]
        ]
    ]); ?>

    <?= ListView::widget([
        'dataProvider' => $dataProvider,
        'itemOptions' => ['tag' => 'li'],
        'itemView' => '_attention',//子视图
        'viewParams' => ['type' => $type],
        'layout' => "{items}\n{pager}",
        'options' => [
            'tag' => 'ul',
            'class' => 'list-unstyled stream-following-list'
        ]
    ]); ?>
</div>

