<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
	<div class="content" id="ticket-department">
		<div class="page-title-cont clearfix">
			<h3>Edit User</h3>
		</div>
		
		<div class="row">
			<div class="col margin-top col-md-4">
				<div class="row min-bottom-margin">
					<div class="col col-xs-12">
						<div class="cont">
							<div class="top clearfix">
								<h4 class="pull-left">User Info</h4>
							</div>
							
							
							<img src="<?php echo $base_url; ?>assets/img/profile_img/<?php echo $current_user_info->profile_img1x; ?>" srcset="<?php echo $base_url; ?>assets/img/profile_img/<?php echo $current_user_info->profile_img1x; ?> 1x, <?php echo $base_url; ?>assets/img/profile_img/<?php echo $current_user_info->profile_img2x; ?> 2x, <?php echo $base_url; ?>assets/img/profile_img/<?php echo $current_user_info->profile_img3x; ?> 3x" width="68" height="68" />
							
							<table class="ticket-info">
								<tbody>
									<tr>
										<td>ID</td>
										<td>
											<?php echo $current_user_info->id; ?>
										</td>
									</tr>
									<tr>
										<td>Name</td>
										<td>
											<?php echo $current_user_info->name; ?>
										</td>
									</tr>
									<tr>
										<td>Username</td>
										<td>
											<?php echo $current_user_info->username; ?>
										</td>
									</tr>
									<tr>
										<td>Role</td>
										<td>
											Client
										</td>
									</tr>
									<tr>
										<td>Email</td>
										<td>
											<?php echo $current_user_info->email; ?>
										</td>
									</tr>
									<tr>
										<td>Member Since</td>
										<td>
											<?php echo $created_on; ?>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
			
			<div class="col margin-top no-bottom-padding col-md-8">
				<div class="row min-bottom-margin">
					<div class="col col-xs-12">
						<div class="cont clearfix">
							<div class="top">
								<h4>Edit User</h4>
							</div>
							
							<p class="bg-danger" style="display:none"></p>
							
							<form method="POST" action="<?php echo $base_url; ?>panel/admin/user/<?php echo $current_user_info->id; ?>/edit/action" name="edit-user">
								<div class="form-group">
									<label for="user-name">Name</label>
									<input type="text" name="user-name" id="user-name" value="<?php echo $current_user_info->name; ?>" />
								</div>
								
								<div class="form-group">
									<label for="user-username">Username</label>
									<input type="text" name="user-username" id="user-username" value="<?php echo $current_user_info->username; ?>" />
								</div>
								
								<div class="form-group">
									<label for="user-email">Email</label>
									<input type="text" name="user-email" id="user-email" value="<?php echo $current_user_info->email; ?>" />
								</div>
								
								                                <div class="form-group">
                                    <label id="user-exp_date">Expires On</label>
                                    <input type="text" name="user-expdate" id="user-expdate" value="<?php echo $current_user_info->exp_date;  ?>" />
                                </div>
								<label id="user-role">Role</label>
								<div class="radio">
									<input type="radio" name="user-role" id="user-role2" class="blue" value="2" <?php if($current_user_info->role == '2') echo 'checked '; ?>/>
									<label for="user-role2">Agent</label>
								</div>
								<div class="radio">
									<input type="radio" name="user-role" id="user-role3" class="blue" value="3" <?php if($current_user_info->role == '3') echo 'checked '; ?>/>
									<label for="user-role3">Admin</label>
								</div>
								
								<br />
								
								<div class="form-group">
									<label for="responsible_agents">Ticket Departments</label>
									<span class="label_desc">Ticket departments that this agent will have access to</span>
									
									<?php
									if(count($tdepartments) == 0)
										echo 'No departments';
									else{
										$t_departments = count($tdepartments);
										$d_departments = ceil($t_departments / 2);
									?>
									<div class="row">
										<?php
										$counter = 0;
										foreach($tdepartments as $department) {
											if($counter == 0)
												echo '<div class="col col-xs-12 col-md-6">';
											if($counter == $d_departments) {
												echo '</div>';
												echo '<div class="col col-xs-12 col-md-6">';
											}
											
											echo '<div class="checkbox">';
											if($department->is_selected == true)
												echo '<input type="checkbox" id="checkbox_'.$counter.'" name="tdepartments[]" value="'.$department->id.'" Checked />';
											else
												echo '<input type="checkbox" id="checkbox_'.$counter.'" name="tdepartments[]" value="'.$department->id.'" />';
											echo '<label for="checkbox_'.$counter.'">'.$department->name.'</label>';
											echo '</div>';
											
											if(($counter-1) == $t_departments)
												echo '</div>';
											$counter++;
										}
										?>
									</div>
									
									<?php
									}
									?>
								</div>
								
								
								<div class="form-group">
									<label for="responsible_agents">Bug Reports Departments</label>
									<span class="label_desc">Bug reports departments that this agent will have access to</span>
									
									<?php
									if(count($bdepartments) == 0)
										echo 'No departments';
									else{
										$b_departments = count($bdepartments);
										$d_departments = ceil($t_departments / 2);
									?>
									<div class="row">
										<?php
										$countersum = $counter+1;
										$counter = 0;
										foreach($bdepartments as $department) {
											if($counter == 0)
												echo '<div class="col col-xs-12 col-md-6">';
											if($counter == $d_departments) {
												echo '</div>';
												echo '<div class="col col-xs-12 col-md-6">';
											}
											
											echo '<div class="checkbox">';
											if($department->is_selected == true)
												echo '<input type="checkbox" id="checkbox_'.($counter+$countersum).'" name="bdepartments[]" value="'.$department->id.'" Checked />';
											else
												echo '<input type="checkbox" id="checkbox_'.($counter+$countersum).'" name="bdepartments[]" value="'.$department->id.'" />';
											echo '<label for="checkbox_'.($counter+$countersum).'">'.$department->name.'</label>';
											echo '</div>';
											
											if(($counter-1) == $b_departments)
												echo '</div>';
											$counter++;
										}
										?>
									</div>
									
									<?php
									}
									?>
								</div>
								

								<input type="submit" name="save-changes" class="btn btn-strong-blue pull-right" value="Save changes" />
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	
	<div id="tooltip"></div>
	
	
 	<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
	<script src="<?php echo asset_url(); ?>js/jquery-1.11.3.min.js"></script>
	<script src="<?php echo asset_url(); ?>js/tickerr_core.js"></script>
	<script src="<?php echo asset_url(); ?>js/tinyeditor.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
	<script type="text/javascript">

	$( function() {

            $( "#user-expdate" ).datepicker({
                    dateFormat: 'yy-mm-dd'
                }
            );
        } );
		$('document').ready(function() {
			// Enable sidebar
			enable_sidebar();
			
			$('form[name=edit-user]').submit(function(evt) {
				var name = $('input[name=user-name]').val();
				var username = $('input[name=user-username]').val();
				var email = $('input[name=user-email]').val();

				if(name == '') {
					evt.preventDefault();
					error('Please insert the user\'s name', '[name=user-name]');
					return false;
				}
				if(name.length < 5) {
					evt.preventDefault();
					error('User\'s name must be at least 5 characters long', '[name=user-name]');
					return false;
				}
				if(username == '') {
					evt.preventDefault();
					error('Please insert the user\'s username', '[name=user-username]');
					return false;
				}
				if(/\s/.test(username)) {
					evt.preventDefault();
					error('The user\'s username cannot contain spaces', '[name=user-username]');
					return false;
				}
				if(username.length < 5) {
					evt.preventDefault();
					error('The user\'s username must be at least 5 characters long', '[name=user-username]');
					return false;
				}
				if(email == '') {
					evt.preventDefault();
					error('Please insert the user\'s email address', '[name=user-email]');
					return false;
				}
				if(validateEmail(email) == false) {
					evt.preventDefault();
					error('Please insert a valid email address', '[name=user-email]');
					return false;
				}
			});
			
			$('button[name=drop]').click(function(evt) {
				evt.preventDefault();
				var to = $(this).data('drop');
				
				$('.dropdwn[name=dropdwn-'+to+']').slideToggle(300);
			});
			
			function validateEmail(email) {
				var re = /^([\w-]+(?:\.[\w-]+)*)@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$/i;
				return re.test(email);
			}

			var e_active = false;
			function error(e, n) {
				if(e_active != false) {
					$(e_active).css('border-color', '#d0d0d0').removeClass('error');
				}
				
				$(n).css('border-color','#ff0000').addClass('error');
				e_active = n;
				
				$('p.bg-danger').slideUp(200, function() {
					$('p.bg-danger').html(e).slideDown(200);
				});
			}
		});
	</script>
</body>
</html>