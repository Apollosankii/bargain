<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var string|null $subtitle Optional description (page title is shown once in the layout topbar via $this->title) */
/** @var string|null $badgeHtml */
/** @var string|null $actionsHtml Optional right-aligned actions (buttons, forms) */

use yii\helpers\Html;

$subtitle = $subtitle ?? null;
$badgeHtml = $badgeHtml ?? null;
$actionsHtml = $actionsHtml ?? null;

if ($subtitle === null && $badgeHtml === null && $actionsHtml === null) {
    return;
}
?>
<div class="admin-page-header">
    <div class="admin-page-header__row">
        <div>
            <?php if ($subtitle !== null): ?>
                <p class="admin-page-header__subtitle"><?= $subtitle ?></p>
            <?php endif; ?>
        </div>
        <?php if ($badgeHtml !== null || $actionsHtml !== null): ?>
            <div class="admin-page-header__actions">
                <?= $badgeHtml ?>
                <?= $actionsHtml ?>
            </div>
        <?php endif; ?>
    </div>
</div>
