<?php
/**
 * Default email notification template (HTML)
 *
 * @var \Cake\View\View $this
 * @var string $level
 * @var string|null $greeting
 * @var array<string> $introLines
 * @var string|null $actionText
 * @var string|null $actionUrl
 * @var array<string> $outroLines
 * @var string|null $salutation
 */

$colors = [
    'success' => '#28a745',
    'error' => '#dc3545',
    'warning' => '#ffc107',
    'info' => '#007bff',
];
$color = $colors[$level] ?? $colors['info'];
?>
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <?php if ($greeting): ?>
        <h2 style="color: <?= h($color) ?>;">
            <?= h($greeting) ?>
        </h2>
    <?php endif; ?>

    <?php foreach ($introLines as $line): ?>
        <p style="line-height: 1.6; margin: 10px 0;">
            <?= h($line) ?>
        </p>
    <?php endforeach; ?>

    <?php if ($actionText && $actionUrl): ?>
        <div style="text-align: center; margin: 30px 0;">
            <a href="<?= h($actionUrl) ?>"
               style="background-color: <?= h($color) ?>;
                      color: white;
                      padding: 12px 24px;
                      text-decoration: none;
                      border-radius: 4px;
                      display: inline-block;
                      font-weight: bold;">
                <?= h($actionText) ?>
            </a>
        </div>
    <?php endif; ?>

    <?php foreach ($outroLines as $line): ?>
        <p style="line-height: 1.6; margin: 10px 0;">
            <?= h($line) ?>
        </p>
    <?php endforeach; ?>

    <?php if ($salutation): ?>
        <p style="margin-top: 30px;">
            <?= h($salutation) ?>
        </p>
    <?php endif; ?>
</div>

