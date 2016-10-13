<?php

use yii\helpers\Html;
use yii\grid\GridView;

/*
 * @var yii\web\View $this
 */

$this->title = Yii::t('user', 'Careers');
$this->params['breadcrumbs'][] = $this->title;
?>
<?= $this->render('/_alert', ['module' => Yii::$app->getModule('user')]) ?>

<div class="row">
    <div class="col-md-3">
        <?= $this->render('/settings/_menu') ?>
    </div>
    <div class="col-md-9">
        <?= $this->render('/settings/_header') ?>
        <?php
        echo GridView::widget([
            'dataProvider' => $dataProvider,
            'layout'=>"{items}\n{pager}",
            'columns' => [
                'id',
                'token',
                'rate_limit',
                'rate_period',
                'created_at',
                ['class' => 'yii\grid\ActionColumn',],
            ],
        ]);
        ?>
        <div class="form-group">
            <div class="edu-btn">
                <?= Html::a(Yii::t('user', 'Create Career'), ['create'], ['class' => 'btn btn-primary btn-block']) ?>
                <br>
            </div>
        </div>

    </div>
</div>
