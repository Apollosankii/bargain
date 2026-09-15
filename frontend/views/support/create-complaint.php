<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Complaint $model */

use common\models\Auction;
use common\models\Complaint;
use common\models\User;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

$this->title = 'File a Complaint — Bargain';

$auctions = Auction::find()
    ->where(['status' => [Auction::STATUS_ACTIVE, Auction::STATUS_CLOSED]])
    ->orderBy(['created_at' => SORT_DESC])
    ->limit(100)
    ->all();

$auctionOptions = ArrayHelper::map($auctions, 'auction_id', static fn (Auction $a) => $a->title . ' (#' . $a->auction_id . ')');

$users = User::find()
    ->where(['!=', 'role', User::ROLE_ADMIN])
    ->andWhere(['status' => User::STATUS_ACTIVE])
    ->orderBy(['first_name' => SORT_ASC])
    ->limit(100)
    ->all();

$userOptions = ArrayHelper::map($users, 'user_id', static fn (User $u) => $u->getFullName() . ' (' . $u->email . ')');
?>
<div class="layout-content__inner portal-page">
    <header class="portal-page__header">
        <div>
            <h1 class="portal-page__title">File a Complaint</h1>
            <p class="portal-page__subtitle">Describe the issue so our admin team can investigate and take action if needed.</p>
        </div>
        <div class="portal-page__actions">
            <a href="<?= Url::to(['/support/complaints']) ?>" class="btn btn-outline btn-sm">Back to complaints</a>
        </div>
    </header>

    <section class="portal-section">
        <div class="portal-form-card" style="max-width:640px;">
            <?php $form = ActiveForm::begin(['options' => ['class' => 'portal-form']]); ?>

            <?= $form->field($model, 'type')->dropDownList(Complaint::typeLabels(), [
                'prompt' => 'Select type…',
                'id' => 'complaint-type',
            ]) ?>

            <div id="complaint-auction-field" style="display:none;">
                <?= $form->field($model, 'auction_id')->dropDownList($auctionOptions, ['prompt' => 'Select auction…']) ?>
            </div>

            <div id="complaint-user-field" style="display:none;">
                <?= $form->field($model, 'reported_user_id')->dropDownList($userOptions, ['prompt' => 'Select user…']) ?>
            </div>

            <?= $form->field($model, 'subject')->textInput(['maxlength' => true, 'placeholder' => 'Brief summary of the issue']) ?>
            <?= $form->field($model, 'description')->textarea(['rows' => 6, 'placeholder' => 'Provide as much detail as possible…']) ?>

            <div style="margin-top:1.25rem;">
                <?= Html::submitButton('Submit complaint', ['class' => 'btn btn-primary']) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </section>
</div>
<?php
$this->registerJs(<<<JS
(function () {
    var typeEl = document.getElementById('complaint-type');
    var auctionField = document.getElementById('complaint-auction-field');
    var userField = document.getElementById('complaint-user-field');
    if (!typeEl) return;
    function sync() {
        var v = typeEl.value;
        auctionField.style.display = v === 'AUCTION' ? '' : 'none';
        userField.style.display = v === 'USER' ? '' : 'none';
    }
    typeEl.addEventListener('change', sync);
    sync();
})();
JS);
?>
