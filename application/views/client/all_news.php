<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
	<div class="content">
		<div class="page-title-cont clearfix">
			<h3><?php echo $this->lang->line('text_All_News');?>            </h3>
		</div>
		
		<div class="row">
			<div class="col margin-top col-sm-12">


                <div  id="accordion" >
                <?php
               		foreach($all_news->result() as $row) {
                ?>
                    <h4 ><b><?php echo $row->subject; ?></b></br></br>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $this->lang->line('text_CREATED')." : ". date('Y-m-d H:i:s', strtotime($row->create_at)); ?></h4>
                    <div style="margin-bottom: 10px;">
                        <p><?php echo $row->description; ?></p>
                    </div>


                <?php
                }
               	?>

                </div>

				</div>
			</div>
		</div>
	</div>

    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
	<script src="<?php echo asset_url(); ?>js/jquery-1.11.3.min.js"></script>
	<script src="<?php echo asset_url(); ?>js/tickerr_core.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
	<script type="text/javascript">
		$('document').ready(function() {
			// Enable sidebar
			enable_sidebar();

            $( function() {
                $( "#accordion" ).accordion({
                    active: false,
                    collapsible: true
                });
            } );

			
			$('thead tr th').on('mouseover', function() {
				$(this).children('i.fa-sort').addClass('active');
				$(this).children('.hid').css('visibility','visible');
			}).on('mouseout', function() {
				$(this).children('i.fa-sort').removeClass('active');
				$(this).children('.hid').css('visibility','hidden');
			});

			$('thead tr th').click(function(evt) {
				if($(this).data('sort') !== undefined)
					location.href = $(this).data('sort');
			});
			
			$('tr').click(function(evt) {
				if($(this).data('href') !== undefined)
					location.href = $(this).data('href');
			});
			

			
			$('a[name=delete-admin]').click(function(evt) {
				var c = confirm("Are you sure you want to delete this News");
				if(c == false) {
					evt.preventDefault();
					return false;
				}
			});
			

			$('button[name=drop]').click(function(evt) {
				evt.preventDefault();
				var to = $(this).data('drop');
				
				$('.dropdwn[name=dropdwn-'+to+']').slideToggle(300);
			});
			
			var e_active = false;
			function error(e, n) {
				if(e_active != false) {
					$(e_active).removeClass('error');
				}
				
				$(n).addClass('error');
				e_active = n;
				
				$('p.bg-danger').slideUp(200, function() {
					$('p.bg-danger').html(e).slideDown(200);
				});
			}
			


		});
	</script>
</body>
</html>