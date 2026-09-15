<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\SupportTicket $ticket */
/** @var string $body */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

$this->title = 'New Support Message — Bargain';
?>
<div class="layout-content__inner portal-page">
    <header class="portal-page__header">
        <div>
            <h1 class="portal-page__title">New Support Message</h1>
            <p class="portal-page__subtitle">Tell us what you need help with. An admin will reply in this thread.</p>
        </div>
        <div class="portal-page__actions">
            <a href="<?= Url::to(['/support/support']) ?>" class="btn btn-outline btn-sm">Back to support</a>
        </div>
    </header>

    <section class="portal-section">
        <div class="portal-form-card" style="max-width:640px;">
            <?php $form = ActiveForm::begin(['options' => ['class' => 'portal-form']]); ?>

            <?= $form->field($ticket, 'subject')->textInput(['maxlength' => true, 'placeholder' => 'What do you need help with?']) ?>

            <div class="form-group field-body required">
                <label class="form-label" for="body">Message</label>
                <textarea id="body" class="form-control" name="body" rows="6" placeholder="Describe your issue…" required><?= Html::encode($body) ?></textarea>
            </div>

            <div style="margin-top:1.25rem;">
                <?= Html::submitButton('Send message', ['class' => 'btn btn-primary']) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </section>
</div>
