<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<div class="content">
    <div class="page-title-cont clearfix">
        <h3>New ECU</h3>
    </div>

    <div class="row">
        <div class="col col-xs-12">
            <div class="cont clearfix">
                <div class="top clearfix">
                    <h4 class="pull-left">ECU Details</h4>
                </div>

                <?php
                if(!isset($error))
                    echo '<p class="bg-danger" style="display:none"></p>';
                else
                    echo '<p class="bg-danger">'.$error.'</p>';
                ?>

                <?php

                echo '<form method="POST" action="" name="new-vehicle">';
                ?>
                <div class="row min-bottom-margin">
                    <div class="col col-md-6">
                        <div class="form-group">
                            <label for="subject">ECU Use</label>
                            <select id="vuse" name="vtype">
                            						
							<option value="ABS">ABS</option>                        
                            <option value="CPC">CPC</option>                        
                            <option value="Engine" selected>Engine</option>                        
                            <option value="Gear">Gear</option>                        
							 
                            </select>
                          
                        </div>

						<div class="form-group">
                            <label for="subject">ECU Producer</label>
                            <select class="js-example-basic-single" id="producer" name="producer">
                             <option value='0'>- Search Producer -</option>

                            </select>
                        </div>

                        <div class="form-group">
                            <label for="subject">ECU Builds</label>
                            <select class="js-example-basic-single" id="vbuild" name="vbuild">
                            <option value='0'>- Search Build -</option>
                            </select>
                            <input type="submit" name="submit" class="btn btn-strong-blue pull-left" value="Add" />
                        </div>



                   
                </div>
				

                </form>
            </div>
        </div>
    </div>
</div>

<div id="tooltip"></div>


<script src="<?php echo asset_url(); ?>js/jquery-1.11.3.min.js"></script>
<script src="<?php echo asset_url(); ?>js/tickerr_core.js"></script>
<script src="<?php echo asset_url(); ?>/bootstrap/js/bootstrap.js"></script>
<script src="<?php echo asset_url(); ?>js/summernote.min.js"></script>

<link href="<?php echo asset_url(); ?>css/select2.min.css" rel="stylesheet" />
<script src="<?php echo asset_url(); ?>js/select2.min.js"></script>


<script type="text/javascript">

  
    $('document').ready(function() {
        // Enable sidebar
        enable_sidebar();

       
      

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

          
         //   console.log(vtype);






        $('form[name=new-vehicle]').submit(function(evt) {

            var vtype = $('input[name=vuse]').val();
			var producer = $('input[name=producer]').val();
		
			var vbuild = $('input[name=vbuild]').val();
		




            if(vtype == '' || vtype == "0") {
                evt.preventDefault();
                error('Enter Use', '[name=vtype]');
                return false;
            }
			if(producer == '' || producer == "0") {
                evt.preventDefault();
                error('Enter Producer', '[name=producer]');
                return false;
            }
           
		   
		    if(vbuild == '' || vbuild == "0") {
                evt.preventDefault();
                error('Enter Build', '[name=vbuild]');
                return false;
            }
           
		   
           
           



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


         $("#producer").select2({         
         
            tags: true,
			ajax: { 
            url:  "https://support.vz-performance.com/magic/api/get_ecu_producer",
            type: "get",
            dataType: 'json',
            delay: 250,
            data: function (params) {
                gvtype = $("#vuse").val();
                return {
                searchTerm: params.term,
                vtype :gvtype
               
                };
            },
           	 processResults: function (response) {
				 return {
                results: $.map(response, function (item) {
                    return {
                        text: item.name,                        
                        id: item.name
                    }
                })
            };
      	 	
            },
            cache: true
            }
           
        });

         

         $("#vbuild").select2({         
             tags: true,
			ajax: { 
            url:  "https://support.vz-performance.com/magic/api/get_ecu_builds",
            type: "get",
            dataType: 'json',
            delay: 250,
            data: function (params) {
                
                gvproducer = $("#producer").val();
                
                return {
                searchTerm: params.term, // search term
                vprod :gvproducer
                
                };
            },
           	 processResults: function (response) {
				 return {
                results: $.map(response, function (item) {
                    return {
                        text: item.name,                        
                        id: item.name
                    }
                })
            };
      	 	
            },
            cache: true
            }
        });

        
          

</script>
</body>
</html>