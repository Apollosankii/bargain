<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var string $label */
/** @var string|int|float $value */
/** @var string $icon One of: brand, warning, success, muted */
/** @var string|null $hint */
/** @var string $iconChar Single character or symbol for icon area */

$icon = $icon ?? 'muted';
$iconChar = $iconChar ?? '•';
$hint = $hint ?? null;
$label = $label ?? '';
$value = $value ?? '';
?>
<div class="portal-stat">
    <div class="portal-stat__icon portal-stat__icon--<?= $icon ?>" aria-hidden="true"><?= $iconChar ?></div>
    <div class="portal-stat__label"><?= $label ?></div>
    <div class="portal-stat__value"><?= $value ?></div>
    <?php if ($hint !== null): ?>
        <div class="portal-stat__hint"><?= $hint ?></div>
    <?php endif; ?>
</div>
