<?php require_once('Connections/DBConnect.php'); ?>
<?php require_once('webassist/mysqli/rsobj.php'); ?>
<?php require_once('webassist/mysqli/queryobj.php'); ?>
<?php
$rsSettings = new WA_MySQLi_RS("rsSettings",$DBConnect,1);
$rsSettings->setQuery("SELECT * FROM tbl_settings");
$rsSettings->execute();?>
<?php
$rsToTalParticipants = new WA_MySQLi_RS("rsToTalParticipants",$DBConnect,0);
$rsToTalParticipants->setQuery("SELECT reg_id, surname, firstname, verification_id AS regNumber FROM tbl_register WHERE verification_id = ?");
$rsToTalParticipants->bindParam("s", "".(isset($_GET['regNumber'])?$_GET['regNumber']:"")  ."", "-1"); //colname
$rsToTalParticipants->execute();
?>
<!-- include summernote css/js -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.9/summernote.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.9/summernote.js"></script>
 
 <div class="modal-header">
       <h2><i class="fas fa-eye"></i> View Modal With ID</h2>
      <button type="button" class="close" onclick="javascript:window.location.reload()" data-dismiss="modal">X</button>
    </div>
    <div class="modal-body">
      <div class="panel panel-default">
        <div class="panel-body">

		<div class="container">		
<div class="card-body" style="font-size: 14px;">
          <div class="row">			

                <div class="col-md-2 col-sm-6 col-xs-12">
                <div align="left" class="form-group" style="font-size:14px;">
				<label for="title">Registration Number</label>
				<span class="form-group" style="font-size:14px;">
				<input name="title" type="text" class="form-control" readonly id="title" style="font-size:14px; border:#CCC thin solid; background-color:#FFF; text-align:left; color: #000;" value="<?php echo($rsToTalParticipants->getColumnVal("regNumber")); ?>">
				  </span>
				</div></div>

            <div class="col-md-5 col-sm-6 col-xs-12">
                <div align="left" class="form-group" style="font-size:14px;">
                  <label for="surname">Surname</label>
                  <span class="form-group" style="font-size:14px;">
                  <input name="surname" type="text" class="form-control" readonly id="surname" style="font-size:14px; border:#CCC thin solid; background-color:#FFF; text-align:left; color: #000;" value="<?php echo($rsToTalParticipants->getColumnVal("surname")); ?>">
                  </span>
              </div></div>

            <div class="col-md-5 col-sm-6 col-xs-12">
                <div align="left" class="form-group" style="font-size:14px;">
                  <label for="firstname">First Name</label>
                  <span class="form-group" style="font-size:14px;">
                  <input name="firstname" type="text" class="form-control" readonly id="firstname" style="font-size:14px; border:#CCC thin solid; background-color:#FFF; text-align:left; color: #000;" value="<?php echo($rsToTalParticipants->getColumnVal("firstname")); ?>">
                  </span>
              </div></div>   			  


            <div align="right" class="col-md-12 col-sm-6 col-xs-12">				
				<div class="form-group">
				 <button type="button" class="btn btn-default " onclick="javascript:window.location.reload()" data-dismiss="modal" style="font-size:14px"><i class="fas fa-window-close"></i> Close</button>
                </div>            			
			</div> 	

            </div>

          </div>		
		</div>

        </div>
      </div>

    </div>

<script>	
$('#updNews').on('hidden.bs.modal', function () {
 location.reload();
})
</script>	