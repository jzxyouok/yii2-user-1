<?php
use yii\helpers\Url;
use yii\helpers\Html;

/**
 * @var \yuncms\user\models\Follow $model
 */
?>
<div class="row">
    <div class="col-md-10">
        <img class="avatar-32" src="<?=$model->user->getAvatar($model->user_id)?>" />
        <div>
            <a href="<?=Url::to(['/user/profile/show','id'=>$model->user_id])?>"><?=$model->user->username?></a>
            <div class="stream-following-followed"><?=$model->user->userData->supports?>赞同 / <?=$model->user->userData->followers?>关注</div>
        </div>
    </div>
    <div class="col-md-2 text-right">
        <?php if (!Yii::$app->user->isGuest && Yii::$app->user->identity->isFollowed(get_class($model->user), $model->model_id)): ?>
            <button type="button" class="btn btn-default btn-xs followerUser active" data-target="follow-button"
                    data-source_type="user"
                    data-source_id="<?= $model->model_id; ?>"><?= Yii::t('user', 'Followed') ?>
            </button>
        <?php else: ?>
            <button type="button" class="btn btn-default followerUser btn-xs" data-target="follow-button"
                    data-source_type="user"
                    data-source_id="<?= $model->model_id; ?>"><?= Yii::t('user', 'Follow') ?>
            </button>
        <?php endif; ?>
    </div>
</div>