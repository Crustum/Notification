<?php
/**
 * Default email notification template (Text)
 *
 * @var string|null $greeting
 * @var array<string> $introLines
 * @var string|null $actionText
 * @var string|null $actionUrl
 * @var array<string> $outroLines
 * @var string|null $salutation
 */
?>
<?php if ($greeting): ?>
<?= $greeting ?>

<?php endif; ?>
<?php foreach ($introLines as $line): ?>
<?= $line ?>

<?php endforeach; ?>
<?php if ($actionText && $actionUrl): ?>

<?= $actionText ?>: <?= $actionUrl ?>

<?php endif; ?>
<?php foreach ($outroLines as $line): ?>
<?= $line ?>

<?php endforeach; ?>
<?php if ($salutation): ?>

<?= $salutation ?>
<?php endif; ?>

