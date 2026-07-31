<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $__env->yieldContent('title', 'Stampa'); ?></title>
    <link rel="stylesheet" href="/css/app.css">
    <?php echo $__env->yieldPushContent('head'); ?>
</head>
<body>
<?php echo $__env->yieldContent('content'); ?>
<?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH /workspace/resources/views/layouts/print.blade.php ENDPATH**/ ?>