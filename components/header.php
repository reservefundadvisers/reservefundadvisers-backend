
    <meta name="description" content="reserve fund advisors data visualizer">
    <meta name="author" content="anass wakrim">

    <!-- Custom fonts for this template-->
    <link href="<?php echo $base_web;?>/assets/js/fontawesome-free/css/all.css" rel="stylesheet" type="text/css">
    <link
        href="<?php echo $base_web;?>/assets/fonts/fonts.css"
        rel="stylesheet">

    <!-- BOOTSTRAP -->
    <link rel="stylesheet" href="<?php echo $base_web;?>/assets/css/bootstrap4-toggle.min.css">
    <!-- Custom styles for this template-->
    <link href="<?php echo $base_web;?>/assets/css/sb-admin-2.css?v=1.4" rel="stylesheet">
    <!-- W3.CSS -->
    <link rel="stylesheet" href="<?php echo $base_web;?>/assets/css/w3.css">
    <!-- Global CSS -->
    <link rel="stylesheet" href="<?php echo $base_web;?>/assets/css/global.css?v=<?=time();?>" type="text/css">
    <link rel="stylesheet" href="<?php echo $base_web;?>/assets/css/loaders.css">
    <link rel="stylesheet" href="<?php echo $base_web;?>/assets/css/help.css?v=<?=time();?>">
    
    <!-- UI -->
	<link rel="stylesheet" href="<?php echo $base_web;?>/assets/css/jquery-ui.css">	
    <link rel="stylesheet" href="<?php echo $base_web;?>/assets/css/ui/slider.css">
    <link rel="stylesheet" href="<?php echo $base_web;?>/assets/css/ui/input.css?v=<?=time();?>">
    <link rel="stylesheet" href="<?php echo $base_web;?>/assets/css/ui/button.css?v=<?=time();?>">
    <link rel="stylesheet" href="<?php echo $base_web;?>/assets/css/ui/switch.css?v=<?=time();?>">

    
    <!-- Shepherd -->
    
    <link href="<?php echo $base_web;?>/assets/css/shepherd.css" rel="stylesheet">
    

    <!-- Custom styles for this page -->
    
    <link href="<?php echo $base_web;?>/assets/css/tabulator/tabulator.min.css" rel="stylesheet">
    <link href="<?php echo $base_web;?>/assets/css/table.css?v=1.2" rel="stylesheet">

    <!-- Module CSS -->
    <?= print_css($headers); ?>
    <!-- ********** -->

    <script>    

        var global_role = "<?php echo $auth->role();?>";
        var global_base = "<?php echo $base_web;?>";

    </script>
