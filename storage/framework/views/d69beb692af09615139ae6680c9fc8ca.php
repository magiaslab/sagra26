<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'Cassa'); ?> — <?php echo e($impostazioni->intestazione_nome ?? 'Cassa'); ?></title>
    <link rel="stylesheet" href="/css/app.css">
    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::styles(); ?>

    <?php echo $__env->yieldPushContent('head'); ?>
</head>
<body class="cassa-body">
<?php echo $__env->yieldContent('content'); ?>
<?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::scripts(); ?>

<?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH /workspace/resources/views/layouts/cassa.blade.php ENDPATH**/ ?>