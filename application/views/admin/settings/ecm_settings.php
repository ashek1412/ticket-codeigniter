<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
	<div class="content">
		<div class="page-title-cont clearfix">
			<h3>ECU Manager Settings</h3>
		</div>
		
			<form method="POST"  name="ecu-services">
		<div class="row">
        	
			<div class="col margin-top col-md-4">
				<div class="row min-bottom-margin">
					<div class="col col-xs-12">
						<div class="cont">
													
							<table class="ticket-info" >
								<tbody style="text-align: right; font-size:16px; ">								
									
                                    <tr>
                                        <td>ECU Service Status :</td>
                                        	<td style="text-align: left;padding: 10px;">  
                                         	<div class="onoffswitch">
                                          	  <input type="checkbox" name="isactive" class="onoffswitch-checkbox" id="myonoffswitch"  <?php  if ($settings->ecu_service_status == '1') echo "checked"; ?> />
                                           	 <label class="onoffswitch-label" for="myonoffswitch">
                                                <span class="onoffswitch-inner"></span>
                                                <span class="onoffswitch-switch"></span>
                                       	 	</label>
                                        </div>	
                                    </td>
									</tr>

									 <tr>
                                        <td>Service Start Time :</td>
                                        	<td style="text-align: left;padding: 10px;">  
                                         	<div class="input-group clockpicker">
												<input type="text" class="form-control" value="<?php  echo $settings->ecu_service_stime  ?>" id="stime" name="stime">
												<span class="input-group-addon">
													<span class="glyphicon glyphicon-time"></span>
												</span>
											</div>
										</td>
									</tr>

									 <tr>
                                        <td>Service End Time :</td>
                                        	<td style="text-align: left;padding: 10px;">  
                                         	<div class="input-group clockpicker">
												<input type="text" class="form-control" value="<?php  echo $settings->ecu_service_etime  ?>" id="etime" name="etime">
												<span class="input-group-addon">
													<span class="glyphicon glyphicon-time"></span>
												</span>
											</div>
										</td>
									</tr>

                                     <tr>
                                        <td>Max File Upload Limit in 24 hours :</td>
                                        	<td style="text-align: left;padding: 10px;">  
                                         	<div class="input-group">
												<input type="number" class="form-control" value="<?php  echo $settings->ecu_file_limit_per_day  ?>" id="file_limit" name="file_limit">
												
											</div>
										</td>
									</tr>

     						</tbody>
							</table>
							 <input type="submit" name="ecusubmit" id="sbt" class="btn btn-primary" value="Submit" style="font-size: 14px;background-color: coral; margin-left: 15px;" />

						</div>
					</div>
				</div>

				</form>
				
	</div>
	
	<script src="<?php echo asset_url(); ?>js/jquery-1.11.3.min.js"></script>
	<script src="<?php echo asset_url(); ?>js/tickerr_core.js"></script>
    <link rel="stylesheet" href="<?php echo asset_url(); ?>css/jquery-clockpicker.min.css" />	
    <script src="<?php echo asset_url(); ?>js/jquery-clockpicker.min.js"></script>
    

    
	<style>
    /* The container */
    .container1 {
        display: block;
        position: relative;
        padding-left: 30px;
        margin-bottom: 12px;
        cursor: pointer;
        font-size: 14px;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
        width:400px;
    }

    /* Hide the browser's default checkbox */
    .container1 input {
        position: absolute;
        opacity: 0;
        cursor: pointer;
        height: 0;
        width: 0;
    }

    /* Create a custom checkbox */
    .checkmark {
        position: absolute;
        top: 0;
        left: 0;
        height: 20px;
        width: 25px;
        background-color: #eee;
    }

    /* On mouse-over, add a grey background color */
    .container1:hover input ~ .checkmark {
        background-color: #ccc;
    }

    /* When the checkbox is checked, add a blue background */
    .container1 input:checked ~ .checkmark {
        background-color: #4CAF50;
    }

    /* Create the checkmark/indicator (hidden when not checked) */
    .checkmark:after {
        content: "";
        position: absolute;
        display: none;
    }

    /* Show the checkmark when checked */
    .container1 input:checked ~ .checkmark:after {
        display: block;
    }

    /* Style the checkmark/indicator */
    .container1 .checkmark:after {
        left: 9px;
        top: 5px;
        width: 5px;
        height: 10px;
        border: solid white;
        border-width: 0 3px 3px 0;
        -webkit-transform: rotate(45deg);
        -ms-transform: rotate(45deg);
        transform: rotate(45deg);
    }


    .onoffswitch {
    position: relative; width: 90px;
    -webkit-user-select:none; -moz-user-select:none; -ms-user-select: none;
}

.onoffswitch-checkbox {
    display: none;
}

.onoffswitch-label {
    display: block; overflow: hidden; cursor: pointer;
    border: 2px solid #999999; border-radius: 20px;
}

.onoffswitch-inner {
    display: block; width: 200%; margin-left: -100%;
    -moz-transition: margin 0.3s ease-in 0s; -webkit-transition: margin 0.3s ease-in 0s;
    -o-transition: margin 0.3s ease-in 0s; transition: margin 0.3s ease-in 0s;
}

.onoffswitch-inner:before, .onoffswitch-inner:after {
    display: block; float: left; width: 50%; height: 30px; padding: 0; line-height: 30px;
    font-size: 12px; color: white; font-family: Trebuchet, Arial, sans-serif; font-weight: bold;
    -moz-box-sizing: border-box; -webkit-box-sizing: border-box; box-sizing: border-box;
}

.onoffswitch-inner:before {
    content: "ON";
    padding-left: 10px;
    background-color: #4CAF50; color: #FFFFFF;
}

.onoffswitch-inner:after {
    content: "OFF";
    padding-right: 10px;
    background-color: #bc1212; color: #FFFFFF;
    text-align: right;
}

.onoffswitch-switch {
    display: block; width: 18px; margin: 6px;
    background: #FFFFFF;
    border: 2px solid #999999; border-radius: 20px;
    position: absolute; top: 0; bottom: 0; right: 56px;
    -moz-transition: all 0.3s ease-in 0s; -webkit-transition: all 0.3s ease-in 0s;
    -o-transition: all 0.3s ease-in 0s; transition: all 0.3s ease-in 0s; 
}

.onoffswitch-checkbox:checked + .onoffswitch-label .onoffswitch-inner {
    margin-left: 0;
}

.onoffswitch-checkbox:checked + .onoffswitch-label .onoffswitch-switch {
    right: 0px; 
}
</style>
	<script type="text/javascript">
		$('document').ready(function() {
			// Enable sidebar
			enable_sidebar();

			
			$('#stime').clockpicker();
			$('#etime').clockpicker();


        
            		
		});

        

      
	
	</script>
</body>
</html>