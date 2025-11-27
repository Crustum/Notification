<?php
/**
 * Default email layout (HTML)
 *
 * @var \Cake\View\View $this
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->fetch('title') ?></title>
</head>
<body style="margin: 0; padding: 20px; background-color: #f4f4f4; font-family: Arial, sans-serif;">
    <div style="background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <?= $this->fetch('content') ?>
    </div>
</body>
</html>

