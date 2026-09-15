<?php

declare(strict_types=1);

/** @var string $label */
/** @var string|int $value */
/** @var string $icon One of: brand, success, warning, muted */
/** @var string $iconChar */

$icon = $icon ?? 'muted';
$iconChar = $iconChar ?? '•';
?>
<div class="admin-stat">
    <div class="admin-stat__icon admin-stat__icon--<?= $icon ?>" aria-hidden="true"><?= $iconChar ?></div>
    <div class="admin-stat__label"><?= $label ?></div>
    <div class="admin-stat__value"><?= $value ?></div>
</div>
