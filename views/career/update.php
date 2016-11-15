<?php

/*
 * @var yii\web\View $this
 */
$this->title = Yii::t('user', 'Update Career: ') . ' ' . $model->name;
$this->params['breadcrumbs'][] = [
    'label' => Yii::t('user', 'Careers'),
    'url' => ['index']
];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('user', 'Update');
?>

<div class="row">
    <div class="col-md-3">
        <?= $this->render('/setting/_menu') ?>
    </div>
    <div class="col-md-9">
        <?= $this->render('_form', ['model' => $model]) ?>
    </div>
</div>