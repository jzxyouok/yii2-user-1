<?php

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use yuncms\user\widgets\Connect;


/**
 * @var yii\web\View $this
 * @var yuncms\user\models\LoginForm $model
 * @var yuncms\user\Module $module
 */

$this->title = Yii::t('user', 'Sign in');
//$this->params['breadcrumbs'][] = $this->title;
?>
<div class="col-md-6 col-md-offset-3">
    <h1 class="h4 text-center text-muted"><?= Html::encode($this->title) ?></h1>
    <?php $form = ActiveForm::begin([
        'options' => ['autocomplete' => 'off'],
        'enableAjaxValidation' => true,
        'enableClientValidation' => false,
    ]) ?>
    <?= $form->field($model, 'login', ['inputOptions' => ['autocomplete' => 'off', 'autofocus' => 'autofocus', 'tabindex' => '1']]) ?>

    <?= $form->field($model, 'password', ['inputOptions' => ['autocomplete' => 'off', 'tabindex' => '2']])->passwordInput()->label(Yii::t('user', 'Password') . ($module->enablePasswordRecovery ? ' (' . Html::a(Yii::t('user', 'Forgot password?'), ['/user/recovery/request'], ['tabindex' => '5']) . ')' : '')) ?>

    <?= $form->field($model, 'rememberMe',['options'=>['class'=>'form-group clearfix']])->checkbox([
        'tabindex' => '4','template'=>"<div class=\"checkbox pull-left\">\n{beginLabel}\n{input}\n{labelTitle}\n{endLabel}\n{error}\n{hint}\n</div><button type=\"submit\" class=\"btn btn-primary pull-right\" tabindex=\"3\">".Yii::t('user', 'Sign in')."</button>"]) ?>

    <?php ActiveForm::end(); ?>
    <hr>
    <div class="widget-login pt-30">
        <?php if ($module->enableConfirmation): ?>
            <p class="text-center">
                <?= Html::a(Yii::t('user', 'Didn\'t receive confirmation message?'), ['/user/registration/resend']) ?>
            </p>
        <?php endif ?>
        <?php if ($module->enableRegistration): ?>
            <p class="text-center">
                <?= Html::a(Yii::t('user', 'Don\'t have an account? Sign up!'), ['/user/registration/register']) ?>
            </p>
        <?php endif ?>
        <?= Connect::widget([
            'baseAuthUrl' => ['/user/security/auth'],
        ]) ?>
    </div>
</div>

