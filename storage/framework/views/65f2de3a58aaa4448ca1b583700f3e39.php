<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

        <title inertia><?php echo e(config('app.name', 'Laravel')); ?></title>

        <!-- Favicon / Browser Icon (With Cache Buster) -->
        <link rel="icon" type="image/x-icon" href="<?php echo e(asset('favicon.ico')); ?>?v=<?php echo e(filemtime(public_path('favicon.ico'))); ?>">
        <link rel="shortcut icon" type="image/x-icon" href="<?php echo e(asset('favicon.ico')); ?>?v=<?php echo e(filemtime(public_path('favicon.ico'))); ?>">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        <?php echo app('Tighten\Ziggy\BladeRouteGenerator')->generate(); ?>
        <?php echo app('Illuminate\Foundation\Vite')(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"]); ?>
        <?php if (!isset($__inertiaSsrDispatched)) { $__inertiaSsrDispatched = true; $__inertiaSsrResponse = app(\Inertia\Ssr\Gateway::class)->dispatch($page); }  if ($__inertiaSsrResponse) { echo $__inertiaSsrResponse->head; } ?>

        <?php
            $gaId = config('services.google_analytics.id');
        ?>
        <?php if(!empty($gaId)): ?>
            <!-- Google tag (gtag.js) -->
            <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo e($gaId); ?>"></script>
            <script>
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                gtag('js', new Date());
                gtag('config', '<?php echo e($gaId); ?>', {
                    send_page_view: false
                });
            </script>
        <?php endif; ?>
    </head>
    <body class="font-sans antialiased">
        <?php if (!isset($__inertiaSsrDispatched)) { $__inertiaSsrDispatched = true; $__inertiaSsrResponse = app(\Inertia\Ssr\Gateway::class)->dispatch($page); }  if ($__inertiaSsrResponse) { echo $__inertiaSsrResponse->body; } elseif (config('inertia.use_script_element_for_initial_page')) { ?><script data-page="app" type="application/json"><?php echo json_encode($page); ?></script><div id="app"></div><?php } else { ?><div id="app" data-page="<?php echo e(json_encode($page)); ?>"></div><?php } ?>
    </body>
</html>
<?php /**PATH C:\Development\laragon\www\ime-police-multiview\resources\views/app.blade.php ENDPATH**/ ?>