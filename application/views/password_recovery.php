<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="en">
<head>
	<meta chartset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	
	<title>Tickerr - Support Tickets System</title>
	
	<link href="<?php echo asset_url(); ?>bootstrap/css/bootstrap.min.css" rel="stylesheet" />
	<link href="<?php echo asset_url(); ?>css/main/main.css" rel="stylesheet" />
	<link href="<?php echo asset_url(); ?>css/main/ticket-bug.css" rel="stylesheet" />
	<link href="<?php echo asset_url(); ?>css/tinyeditor.css" rel="stylesheet" />
	<link href='http://fonts.googleapis.com/css?family=Source+Sans+Pro:400,600,700|PT+Sans:400,700|Open+Sans:300,400,600,700,800|Roboto' rel='stylesheet' type='text/css'>

	<!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
	<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
	<!--[if lt IE 9]>
	<script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
	<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
	<![endif]-->
</head>
<body>
	<div id="container" class="ticket-submitted">
		<img src="<?php echo asset_url(); ?>img/logos/mainlogo@1x.png" srcset="<?php echo asset_url(); ?>img/logos/mainlogo@1x.png 1x, <?php echo asset_url(); ?>img/logos/mainlogo@2x.png 2x, <?php echo asset_url(); ?>img/logos/mainlogo@3x.png 3x" width="360" height="100" title="<?php echo $site_title; ?>" />
		<?php
		if($action == 1) {
		?>
		<div id="central-container" class="clearfix">
			<h3 class="center"><?php echo $this->lang->line('text_PASSWORD_RECOVERY');?> </h3>
			<p>
                <?php echo $this->lang->line('text_To_recover_your_password,_please_write_your_email_address_below');?>

            </p>
			<?php
			if(isset($error) && $error != null)
				echo '<div id="error" style="display:block; margin:8px 0px -8px 0;">'.$error.'</div>';
			else
				echo '<div id="error" style="margin:8px 0px -8px 0;"></div>';
			?>
			<form method="POST" action="" name="recover-pass">
				<label for="email"></label>
				<input type="text" name="email" id="email" style="font-size:1.3em;" placeholder=" <?php echo $this->lang->line('text_email');?>" value="<?php echo $this->input->post('email'); ?>" />
				<input type="submit" name="submit" class="pull-right" value="<?php echo $this->lang->line('text_Recover_password');?>" style="width:100%; margin-top:-12px;" />
			</form>
		</div>
		
		<?php }elseif($action == 2) { ?>
		
		<div id="central-container" class="clearfix">
			<h3 class="center"><?php echo $this->lang->line('text_PASSWORD_RECOVERY');?></h3>
			<p>
                <?php echo $this->lang->line('text_An_email_has_been_sent_to_you._Please_follow_the_instructions_inside_the_email_to_change_your_password.');?>

            </p>
		</div>
		
		<?php }elseif($action == 3) { ?>
		
		<div id="central-container" class="clearfix">
			<h3 class="center"><?php echo $this->lang->line('text_PASSWORD_RECOVERY');?></h3>
			<p>
                <?php echo $this->lang->line('text_Hi._Please_write_your_new_password_below.');?>

            </p>
			<div id="error" style="margin:8px 0px -8px 0;"></div>
			<form method="POST" action="" name="recover-pass">
				<label for="password1"></label>
				<input type="password" name="password1" id="password1" style="margin-bottom:18px;" placeholder="<?php echo $this->lang->line('text_new_password');?>..." value="<?php echo $this->input->post('password1'); ?>" />
				<input type="password" name="password2" id="password2" style="margin-top:0px;" placeholder="<?php echo $this->lang->line('text_Password_confirmation');?>..." value="<?php echo $this->input->post('password2'); ?>" />
				<input type="submit" name="submit" class="pull-right" value="<?php echo $this->lang->line('text_change_password');?>" style="width:100%; margin-top:-12px;" />
			</form>
		</div>
		
		<?php }elseif($action == 4) { ?>
		
		<div id="central-container" class="clearfix">
			<h3 class="center"><?php echo $this->lang->line('text_ACCOUNT_HAS_BEEN_RECOVERED');?></h3>
			<p>
                <?php echo $this->lang->line('text_Great!_Your_account_has_been_successfully_recovered!_Now_you_can_log_in_using_your_new_password');?>

            </p>
		</div>
		
		<?php } ?>
	</div>
	
	<script src="<?php echo asset_url(); ?>js/jquery-1.11.3.min.js"></script>
	<script type="text/javascript">
		$('document').ready(function() {
			<?php
			if($action == 1) {
			?>
			
			$('form[name=recover-pass]').submit(function(evt) {
				var email = $('input[name=email]').val();
				
				if(validateEmail(email) == false) {
					evt.preventDefault();
					error('<?php echo $this->lang->line('text_Please_insert_a_valid_email_address');?>', '[name=email]');
					return false;
				}
			});
			
			<?php
			}elseif($action == 3) {
			?>
			
			$('form[name=recover-pass]').submit(function(evt) {
				var p1 = $('input[name=password1]').val();
				var p2 = $('input[name=password2]').val();
				
				if(p1.length < 5) {
					evt.preventDefault();
					error('<?php echo $this->lang->line('text_Your_name_must_be_at_least_5_characters_long');?>', '[name=password1]')


                    return false;
				}
				if(p1 != p2) {
					evt.preventDefault();
					error('<?php echo $this->lang->line('text_Both_passwords_must_match');?>', '[name=password1]', '[name=password2]')
					return false;


                }
			});

			<?php
			}
			?>
			
			var e_active = false;
			var e_active2 = false;
			function error(e, n, n2) {
				if(e_active != false)
					$(e_active).css('border-color', '#d0d0d0').removeClass('error');
				if(e_active2 != false)
					$(e_active2).css('border-color', '#d0d0d0').removeClass('error');
				
				$(n).css('border-color','#ff0000').addClass('error');
				e_active = n;
				
				if(n2 !== undefined) {
					$(n2).css('border-color','#ff0000').addClass('error');
					e_active2 = n2;
				}
					
				
				$('#error').slideUp(200, function() {
					$('#error').html(e).slideDown(200);
				});
			}
			
			function disable_error() {
				$(e_active).css('border-color','#d0d0d0');
				e_active = false;
			}
			
			function validateEmail(email) {
				var re = /^([\w-]+(?:\.[\w-]+)*)@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$/i;
				return re.test(email);
			}
		});
	</script>
</body>
</html>