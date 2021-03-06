<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	
	<title><?php echo $site_title; ?></title>
	
	<link href="<?php echo asset_url(); ?>bootstrap/css/bootstrap.min.css" rel="stylesheet" />
	<link href="<?php echo asset_url(); ?>css/dashboard.css" rel="stylesheet" />
	<link href="<?php echo asset_url(); ?>css/responsive-tables.css" rel="stylesheet" />
	<link href="<?php echo asset_url(); ?>css/forms.css" rel="stylesheet" />
	<link href="<?php echo asset_url(); ?>css/font-awesome.min.css" rel="stylesheet" />
	<link href='http://fonts.googleapis.com/css?family=Source+Sans+Pro:400,600,700|PT+Sans:400,700|Open+Sans:300,400,600,700,800|Roboto' rel='stylesheet' type='text/css'>
	<link href="<?php echo asset_url(); ?>css/tinyeditor.css" rel="stylesheet" />
	<link href="<?php echo asset_url(); ?>css/summernote.min.css" rel="stylesheet" />

	<!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
	<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
	<!--[if lt IE 9]>
	<script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
	<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
	<![endif]-->
    <style>
    .my-custom-scrollbar {
    position: relative;
    height: 700px;
    overflow: auto;
    }
    .table-wrapper-scroll-y {
    display: block;
    }
    </style>
</head>
<body>
	<nav class="navbar navbar-inverse navbar-fixed-top">
		<div class="container-fluid">
			<div class="navbar-header">
				<div class="navbar-toggle">
					<span class="sr-only">Toggle Navigation</span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</div>

				
				<a class="navbar-brand" ><img src="<?php echo asset_url(); ?>img/logos/dashlogo@1x.png" srcset="<?php echo asset_url(); ?>img/logos/dashlogo@1x.png 1x, <?php echo asset_url(); ?>img/logos/dashlogo@2x.png 2x, <?php echo asset_url(); ?>img/logos/dashlogo@3x.png 3x" width="250" height="40" title="<?php echo $site_title; ?>" /></a>
			</div>
            <?php

            $today = new DateTime();
            $later = new DateTime($user_info->exp_date);

            $diff = $later->diff($today)->format("%a");


            if($user_info->role=='1' && $diff <=7) {

                ?>

                <div style="text-align: center; width: 70%; padding-top: 10px; display:inline-block; color: yellow;">
                    <?php echo $this->lang->line('text_Your_Membership_will_be_expired_on'); ?>  <?php echo  date('M jS, Y ', strtotime($user_info->exp_date)) ; ?>. <?php echo $this->lang->line('text_Please_Contact_Service_Provider'); ?>
                </div>
                <?php
            }
            ?>


			<div style="text-align: right; width: 200px; float: right ;padding-top: 10px;">
                <select class="custom-select custom-select-sm" style="background:#2c4960;color: white;float: left;width: auto;"  onchange="javascript:window.location.href='<?php echo base_url(); ?>panel/lswitch/'+this.value;">
                    <option style="color: white;" value="english" <?php if($this->session->userdata('site_lang') == 'english') echo 'selected="selected"'; ?>>English</option>
                    <option style="color: white;"value="chinese" <?php if($this->session->userdata('site_lang') == 'chinese') echo 'selected="selected"'; ?>>Chinese</option>
                </select>
                 <ul  style="float: right;font-weight: bold;width: auto;">
					<a style="color: white;padding-right: 15px;" href="<?php echo $base_url . 'panel/logout/' ?>"><?php echo $this->lang->line('text_logout'); ?></a>
				</ul>
			</div>
		</div>
	</nav>