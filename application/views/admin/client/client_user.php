<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
	<div class="content" id="ticket-department">
		<div class="page-title-cont clearfix">
			<h3>User Details</h3>
		</div>
		
		<div class="row">
			<div class="col margin-top col-md-4">
				<div class="row min-bottom-margin">
					<div class="col col-xs-12">
						<div class="cont">
							<div class="top clearfix">
								<h4 class="pull-left">User Info</h4>
								<div class="btn btn-small btn-blue pull-right" style="margin-left:5px;" name="edit-user">EDIT</div>
								<div class="btn btn-small btn-red pull-right" name="delete-user">DELETE</div>
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
                                    <tr>
                                        <td>Membership Expires On</td>
                                        <td>
                                            <?php echo $current_user_info->exp_date; ?>
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
						<div class="cont">
							<div class="top">
								<h4>Last 10 tickets</h4>
							</div>
							
							<?php
							if($count_last_10_tickets == 0)
								echo 'No tickets';
							else{
							?>
							<table class="table tickets-w-agent">
								<thead>
									<tr>
										<th width="10%" data-sort="<?php echo $base_url; ?>panel/admin/user/<?php echo $current_user_info->id; ?>/tickets/?sort=1&w=a"><i class="fa fa-sort"></i>ID</th>
										<th width="30%" data-sort="<?php echo $base_url; ?>panel/admin/user/<?php echo $current_user_info->id; ?>/tickets/?sort=2&w=a"><i class="fa fa-sort"></i>Title</th>
										<th width="30%" data-sort="<?php echo $base_url; ?>panel/admin/user/<?php echo $current_user_info->id; ?>/tickets/?sort=3&w=a"><i class="fa fa-sort"></i>Department</th>
										<th width="30%" data-sort="<?php echo $base_url; ?>panel/admin/user/<?php echo $current_user_info->id; ?>/tickets/?sort=4&w=a"><i class="fa fa-sort hid"></i><i class="fa fa-sort-down"></i>Created On</th>
									</tr>
								</thead>
								
								<tbody>
									<?php
									foreach($get_last_10_tickets->result() as $ticket) {
										echo '<tr data-href="' . $base_url . 'panel/ticket/' . $ticket->access.'">';
										echo '<td>'.$ticket->id.'</td>';
										echo '<td>'.$ticket->subject.'</td>';
										echo '<td>'.$ticket->department_name.'</td>';
										echo '<td>'.date('M jS, Y \a\t H:i:s', strtotime($ticket->date)).'</td>';
										echo '</tr>';
									}
									?>
								</tbody>
							</table>
							
							<?php
							}
							
							if($count_last_10_tickets > 10)
								echo '<div class="load-more" name="load_more1">Load More...</div>';
							?>
						</div>
					</div>
				</div>
				
				<div class="row min-bottom-margin">
					<div class="col col-xs-12">
						<div class="cont">
							<div class="top">
								<h4>Last 10 bug reports</h4>
							</div>
							
							<?php
							if($count_last_10_bugs == 0)
								echo 'No bug reports';
							else{
							?>
							<table class="table tickets-w-agent">
								<thead>
									<tr>
										<th width="10%" data-sort="<?php echo $base_url; ?>panel/admin/user/<?php echo $current_user_info->id; ?>/bugs/?sort=1&w=a"><i class="fa fa-sort"></i>ID</th>
										<th width="30%" data-sort="<?php echo $base_url; ?>panel/admin/user/<?php echo $current_user_info->id; ?>/bugs/?sort=2&w=a"><i class="fa fa-sort"></i>Title</th>
										<th width="30%" data-sort="<?php echo $base_url; ?>panel/admin/user/<?php echo $current_user_info->id; ?>/bugs/?sort=3&w=a"><i class="fa fa-sort"></i>Client</th>
										<th width="30%" data-sort="<?php echo $base_url; ?>panel/admin/user/<?php echo $current_user_info->id; ?>/bugs/?sort=4&w=a"><i class="fa fa-sort hid"></i><i class="fa fa-sort-down"></i>Created On</th>
									</tr>
								</thead>
								
								<tbody>
									<?php
									foreach($get_last_10_bugs->result() as $report) {
										echo '<tr data-href="' . $base_url . 'panel/ticket/' . $report->access.'">';
										echo '<td>'.$report->id.'</td>';
										echo '<td>'.$report->subject.'</td>';
										echo '<td>'.$report->client_final_name.'</td>';
										echo '<td>'.date('M jS, Y \a\t H:i:s', strtotime($report->date)).'</td>';
										echo '</tr>';
									}
									?>
								</tbody>
							</table>
							
							<?php
							}
							
							if($count_last_10_bugs > 10)
								echo '<div class="load-more" name="load_more2">Load More...</div>';
							?>
						</div>
					</div>
				</div>
				
				<div class="row min-bottom-margin">
					<div class="col col-xs-12">
						<div class="cont">
							<div class="top">
								<h4>Last 10 ratings</h4>
							</div>
							
							<?php
							if($count_last_10_ratings == 0)
								echo 'No ratings';
							else{
							?>
							<table class="table ratings">
								<thead>
									<tr>
										<th width="28%" data-sort="<?php echo $base_url; ?>panel/admin/user/<?php echo $current_user_info->id; ?>/ratings/?sort=1&w=a"><i class="fa fa-sort"></i>Ticket Title</th>
										<th width="28%" data-sort="<?php echo $base_url; ?>panel/admin/user/<?php echo $current_user_info->id; ?>/ratings/?sort=2&w=a"><i class="fa fa-sort"></i>Ticket Agent</th>
										<th width="14%" data-sort="<?php echo $base_url; ?>panel/admin/user/<?php echo $current_user_info->id; ?>/ratings/?sort=3&w=a"><i class="fa fa-sort"></i>Rating</th>
										<th width="30%" data-sort="<?php echo $base_url; ?>panel/admin/user/<?php echo $current_user_info->id; ?>/ratings/?sort=4&w=a"><i class="fa fa-sort hid"></i><i class="fa fa-sort-down"></i>Last Event</th>
									</tr>
								</thead>
								
								<tbody>
									<?php
									foreach($get_last_10_ratings->result() as $ticket) {
										echo '<tr data-href="' . $base_url . 'panel/ticket/' . $ticket->access.'">';
										echo '<td>'.$ticket->subject.'</td>';
										echo '<td>'.$ticket->agent_name.'</td>';
										echo '<td>'.($ticket->rating / 2).'</td>';
										echo '<td>'.date('M jS, Y \a\t H:i:s', strtotime($ticket->last_update)).'</td>';
										echo '</tr>';
									}
									?>
								</tbody>
							</table>
							
							<?php
							}
							
							if($count_last_10_ratings > 10)
								echo '<div class="load-more" name="load_more3">Load More...</div>';
							?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	
	<div id="tooltip"></div>
	
	
	<script src="<?php echo asset_url(); ?>js/jquery-1.11.3.min.js"></script>
	<script src="<?php echo asset_url(); ?>js/tickerr_core.js"></script>
	<script src="<?php echo asset_url(); ?>js/tinyeditor.min.js"></script>
	<script type="text/javascript">
		$('document').ready(function() {
			// Enable sidebar
			enable_sidebar();
			
			$('.load-more[name=load_more1]').click(function() {
				location.href = '<?php echo $base_url; ?>panel/admin/user/<?php echo $current_user_info->id; ?>/tickets';
			});
			$('.load-more[name=load_more2]').click(function() {
				location.href = '<?php echo $base_url; ?>panel/admin/user/<?php echo $current_user_info->id; ?>/bugs';
			});
			$('.load-more[name=load_more3]').click(function() {
				location.href = '<?php echo $base_url; ?>panel/admin/user/<?php echo $current_user_info->id; ?>/ratings';
			});
			
			$('.btn[name=delete-user]').click(function() {
				var c = confirm("Are you sure you want to delete this user? All his tickets and bug reports will be deleted!");
				if(c == true)
					location.href = '<?php echo $base_url; ?>panel/admin/all-users/<?php echo $current_user_info->id; ?>/delete';
			});
			
			$('.btn[name=edit-user]').click(function() {
				location.href = '<?php echo $base_url; ?>panel/admin/user/<?php echo $current_user_info->id; ?>/edit';
			});
			
			$('thead tr th').click(function(evt) {
				if($(this).data('sort') !== undefined)
					location.href = $(this).data('sort');
			});
			
			$('tr').click(function(evt) {
				if($(this).data('href') !== undefined)
					location.href = $(this).data('href');
			});
			
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