<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var frontend\models\AuctionForm $model */
/** @var array $categories */

use frontend\models\AuctionForm;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

$this->title = 'Create New Auction — Bargain';
$minImages = AuctionForm::MIN_IMAGES;
$maxImages = AuctionForm::MAX_IMAGES;
$maxBytes = 5 * 1024 * 1024;
?>
<div class="layout-content__inner portal-page">
    <header class="portal-page__header">
        <div>
            <h1 class="portal-page__title">Create New Auction</h1>
            <p class="portal-page__subtitle">List your item with photos, pricing, and a schedule. All fields are required unless noted.</p>
        </div>
        <div class="portal-page__actions">
            <a href="<?= Url::to(['/dashboard/auctioneer']) ?>" class="btn btn-outline btn-sm">Cancel</a>
        </div>
    </header>

    <div class="portal-form portal-form--wide">
        <?php $form = ActiveForm::begin([
            'id' => 'auction-form',
            'options' => ['enctype' => 'multipart/form-data', 'class' => 'portal-form'],
            'fieldConfig' => [
                'template' => "{label}\n{input}\n{hint}\n{error}",
                'options' => ['class' => 'form-group'],
            ],
        ]); ?>

        <section class="portal-form-section">
            <h2 class="portal-form-section__title">Basic Information</h2>
            <?= $form->field($model, 'title')->textInput(['placeholder' => 'e.g. Vintage Leather Watch']) ?>
            <?= $form->field($model, 'description')->textarea(['rows' => 4, 'placeholder' => 'Describe your item — condition, history, and any details bidders should know.']) ?>
            <?= $form->field($model, 'category_id')->dropDownList($categories, ['prompt' => 'Select category']) ?>
        </section>

        <section class="portal-form-section">
            <h2 class="portal-form-section__title">Auction Photos</h2>
            <div class="form-group<?= $model->hasErrors('imageFiles') ? ' has-error' : '' ?>" id="photo-picker">
                <label class="control-label" for="auction-photo-picker">Upload photos (min <?= $minImages ?>, max <?= $maxImages ?>)</label>

                <input
                    type="file"
                    id="auction-photo-picker"
                    accept="image/png,image/jpeg,image/gif,image/webp"
                    multiple
                    hidden
                >
                <?= Html::activeFileInput($model, 'imageFiles[]', [
                    'id' => 'auctionform-imagefiles',
                    'class' => 'photo-submit-input',
                    'multiple' => true,
                    'accept' => 'image/png,image/jpeg,image/gif,image/webp',
                    'tabindex' => -1,
                    'aria-hidden' => 'true',
                ]) ?>

                <div class="photo-picker-toolbar">
                    <button type="button" class="btn btn-outline btn-sm" id="photo-add-btn">Add photos</button>
                    <span class="photo-picker-count" id="photo-count">0 / <?= $minImages ?> selected</span>
                </div>

                <p class="hint-block">
                    Add photos one by one or select several at once. Click a preview to enlarge.
                    PNG, JPG, GIF, or WebP up to 5 MB each.
                </p>

                <div class="photo-preview-grid" id="photo-preview-grid"></div>

                <?php if ($model->hasErrors('imageFiles')): ?>
                    <div class="help-block help-block-error">
                        <?= Html::encode(implode(' ', $model->getErrors('imageFiles'))) ?>
                    </div>
                <?php endif; ?>
                <div class="help-block help-block-error" id="photo-client-error" hidden></div>
            </div>
        </section>

        <section class="portal-form-section">
            <h2 class="portal-form-section__title">Pricing</h2>
            <?= $form->field($model, 'starting_bid')->textInput(['type' => 'number', 'min' => 1, 'placeholder' => 'Starting bid in KES']) ?>
        </section>

        <section class="portal-form-section">
            <h2 class="portal-form-section__title">Schedule</h2>
            <div class="form-row">
                <?= $form->field($model, 'start_time')->textInput(['type' => 'datetime-local']) ?>
                <?= $form->field($model, 'end_time')->textInput(['type' => 'datetime-local']) ?>
            </div>
        </section>

        <div class="portal-form__actions">
            <a href="<?= Url::to(['/dashboard/auctioneer']) ?>" class="btn btn-outline">Cancel</a>
            <?= Html::submitButton('Create Auction', ['class' => 'btn btn-primary', 'id' => 'auction-submit']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>

<div class="image-lightbox" id="create-photo-lightbox" hidden>
    <button type="button" class="image-lightbox-close" id="create-lightbox-close" aria-label="Close">&times;</button>
    <img id="create-lightbox-image" src="" alt="Photo preview">
</div>

<?php
$minJs = Json::htmlEncode($minImages);
$maxJs = Json::htmlEncode($maxImages);
$maxBytesJs = Json::htmlEncode($maxBytes);
$this->registerJs(<<<JS
(function () {
    const minImages = {$minJs};
    const maxImages = {$maxJs};
    const maxBytes = {$maxBytesJs};
    const allowed = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];

    const picker = document.getElementById('auction-photo-picker');
    const submitInput = document.getElementById('auctionform-imagefiles');
    const addBtn = document.getElementById('photo-add-btn');
    const grid = document.getElementById('photo-preview-grid');
    const countEl = document.getElementById('photo-count');
    const errorEl = document.getElementById('photo-client-error');
    const form = document.getElementById('auction-form');
    const lightbox = document.getElementById('create-photo-lightbox');
    const lightboxImg = document.getElementById('create-lightbox-image');
    const lightboxClose = document.getElementById('create-lightbox-close');

    /** @type {{file: File, url: string}[]} */
    let photos = [];

    function showError(message) {
        if (!errorEl) return;
        if (!message) {
            errorEl.hidden = true;
            errorEl.textContent = '';
            return;
        }
        errorEl.hidden = false;
        errorEl.textContent = message;
    }

    function syncSubmitInput() {
        const dt = new DataTransfer();
        photos.forEach(function (item) { dt.items.add(item.file); });
        submitInput.files = dt.files;
    }

    function updateCount() {
        const ready = photos.length >= minImages;
        countEl.textContent = photos.length + ' / ' + minImages + ' selected'
            + (photos.length > minImages ? ' (max ' + maxImages + ')' : '');
        countEl.classList.toggle('is-ready', ready);
        addBtn.disabled = photos.length >= maxImages;
        addBtn.textContent = photos.length >= maxImages ? 'Photo limit reached' : 'Add photos';
    }

    function render() {
        grid.innerHTML = '';
        photos.forEach(function (item, index) {
            const card = document.createElement('div');
            card.className = 'photo-preview-card';

            const img = document.createElement('img');
            img.src = item.url;
            img.alt = 'Photo ' + (index + 1);
            img.title = 'Click to enlarge';
            img.addEventListener('click', function () {
                lightboxImg.src = item.url;
                lightbox.hidden = false;
                document.body.style.overflow = 'hidden';
            });

            const meta = document.createElement('div');
            meta.className = 'photo-preview-meta';
            meta.textContent = 'Photo ' + (index + 1);

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'photo-preview-remove';
            remove.setAttribute('aria-label', 'Remove photo');
            remove.textContent = '×';
            remove.addEventListener('click', function () {
                URL.revokeObjectURL(item.url);
                photos.splice(index, 1);
                syncSubmitInput();
                render();
                showError('');
            });

            card.appendChild(img);
            card.appendChild(meta);
            card.appendChild(remove);
            grid.appendChild(card);
        });
        updateCount();
    }

    function addFiles(fileList) {
        showError('');
        const incoming = Array.from(fileList || []);
        if (!incoming.length) return;

        for (const file of incoming) {
            if (photos.length >= maxImages) {
                showError('You can upload at most ' + maxImages + ' photos.');
                break;
            }
            if (!allowed.includes(file.type)) {
                showError('"' + file.name + '" is not a supported image type.');
                continue;
            }
            if (file.size > maxBytes) {
                showError('"' + file.name + '" is larger than 5 MB.');
                continue;
            }
            const duplicate = photos.some(function (p) {
                return p.file.name === file.name && p.file.size === file.size && p.file.lastModified === file.lastModified;
            });
            if (duplicate) {
                continue;
            }
            photos.push({ file: file, url: URL.createObjectURL(file) });
        }

        syncSubmitInput();
        render();
        picker.value = '';
    }

    addBtn.addEventListener('click', function () {
        if (photos.length >= maxImages) return;
        picker.click();
    });

    picker.addEventListener('change', function () {
        addFiles(picker.files);
    });

    form.addEventListener('submit', function (e) {
        syncSubmitInput();
        if (photos.length < minImages) {
            e.preventDefault();
            showError('Add at least ' + minImages + ' photos before creating the auction.');
            return;
        }
    });

    function closeLightbox() {
        lightbox.hidden = true;
        lightboxImg.src = '';
        document.body.style.overflow = '';
    }

    lightboxClose.addEventListener('click', closeLightbox);
    lightbox.addEventListener('click', function (e) {
        if (e.target === lightbox) closeLightbox();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !lightbox.hidden) closeLightbox();
    });
})();
JS);
?>
