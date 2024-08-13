<?php require_once('Connections/DBConnect.php'); ?>
<?php require_once('webassist/mysqli/rsobj.php'); ?>
<?php require_once('webassist/mysqli/queryobj.php'); ?>
<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); 
error_reporting(0); 
?>
<?php
$rsSettings = new WA_MySQLi_RS("rsSettings",$DBConnect,1);
$rsSettings->setQuery("SELECT * FROM tbl_settings");
$rsSettings->execute();?>
<?php
//$sectorofinterest = '';
//$exhibitionoption = '';
$num_rows = 0;
?>
<?php
//connect to database
include('Connections/connConfig.php');

//connect with the database
$conn = new mysqli($dbHost,$dbUsername,$dbPassword,$dbName);
?>
<?php
{

//get data from form fields

//$sectorofinterest = $_POST['sectorofinterest'];

//$exhibitionoption = $_POST['exhibitionoption'];

//$country = $_POST['country'];

$query = "SELECT reg_id, verification_id AS regNumber, firstname AS Fullname,  @curRow := @curRow + 1 AS sn FROM tbl_register JOIN (SELECT @curRow := 0) r ";	

//if(isset($sectorofinterest)){
//$query .= "AND sectorofinterest = '%".$sectorofinterest."%'";
//}

//if(isset($exhibitionoption)){
//$query .= "AND exhibitionoption = '".$exhibitionoption."' ";
//}

//if(isset($country)){
//$query .= "AND country LIKE '%".$country."%'";
//}

//get number of rows

//$num_rows = $conn->query($query)->num_rows;

//assign results to a variable

//$listResults = $conn->query($query);

$listResults = $conn->query($query) or die($conn->error);
}
?>
<!DOCTYPE html>
<html lang="en"><!-- InstanceBegin template="/Templates/MainTemplate.dwt.php" codeOutsideHTMLIsLocked="false" -->
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- InstanceBeginEditable name="doctitle" -->
  <title><?php echo($rsSettings->getColumnVal("appname")); ?> <?php echo($rsSettings->getColumnVal("version")); ?> | Open Modal WIth ID</title>

  <!-- Chosen Select-->  
  <script src="https://cdn.rawgit.com/harvesthq/chosen/gh-pages/chosen.jquery.min.js"></script>  

    <!-- Include Bootstrap CDN -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
	
  <!-- Time Picker -->  
  <!-- Include Moment.js CDN -->
  <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.9.0/moment.min.js"></script>
 
  <!-- Include Bootstrap DateTimePicker CDN -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.37/css/bootstrap-datetimepicker.min.css"rel="stylesheet">
 
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.37/js/bootstrap-datetimepicker.min.js"></script>  
  
  <!-- CKEditor -->
  <script src="https://cdn.ckeditor.com/4.14.1/standard-all/ckeditor.js"></script>
  
  
<style>
.buttons-excel, .buttons-pdf, .buttons-copy, .buttons-print, .buttons-csv, .buttons-colvis {
	font-size: 14px !important;
	background-color: #FFF !important;
	border: #CCC thin solid !important;
	color: #000 !important;
}

.dataTables_info, .dataTables_paginate, .dataTables_length, .dataTables_searching {
    font-size: 14px !important;
	color: #000 !important;	
}

 .dataTables_filter {
	 padding-left: 100px !important;
 }

.dataTables_wrapper .dt-buttons {
  float: right;
  padding-left: 10px;
  m
}

.dataTables_length {
  float:left;
  height:2.5em;
  width:35% !important; 
} 

@media print {
  .buttons-excel, .buttons-pdf, .buttons-copy, .buttons-print, .buttons-csv, .dataTables_filter, .dataTables_info, .dataTables_paginate, .dataTables_length, .buttons-colvis {
    display: none !important;
  }
}
@media only screen and (max-width: 800px){
    .buttonpad { 
	padding-left: 5px; 
	padding-right: 5px; 		
	}
}
</style> 
  <!-- Chosen -->
  <link href="dist/css/component-chosen.css" rel="stylesheet">	
  <!-- DataTables -->
  <link rel="stylesheet" href="plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="plugins/datatables-buttons/css/buttons.bootstrap4.min.css">  
  <link rel="stylesheet" href="plugins/rowgroup/1.1.2/css/rowGroup.dataTables.min.css" /> 
    
  <!-- InstanceEndEditable -->
  <!-- Favicon Icon -->
  <link rel="shortcut icon" type="image/x-icon" href="images/ico/<?php echo($rsSettings->getColumnVal("favicon")); ?>"/>  
  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="dist/css/adminlte.min.css">
  <!-- SimpleMDE -->
  <link rel="stylesheet" href="plugins/simplemde/simplemde.min.css">
  <!-- InstanceBeginEditable name="head" -->
  <!-- InstanceEndEditable -->
</head>
<body class="hold-transition sidebar-mini sidebar-collapse">
<div class="wrapper">
  <!-- Navbar -->

  <!-- /.navbar -->

  <!-- Main Sidebar Container -->

  <!-- Content Wrapper. Contains page content --><!-- InstanceBeginEditable name="MainTextArea" -->
  <div class="content-wrapper-">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1><i class="fas fa-users"></i> Open Modal WIth ID</h1>
          </div>		  
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">		
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Open Modal WIth ID</li>
            </ol>
          </div>
        </div>
      </div>
      <!-- /.container-fluid -->
    </section>
    <!-- Main content -->
    <section class="content">
      <div class="row" style="margin-top:-20px">
        <div class="col-md-12">
          <div class="card card-primary card-outline">
            <!-- /.card-header -->

  <div id="viewFiltered" class="modal fade text-left">
    <div class="modal-dialog modal-xl" role="document">
      <div class="modal-content">
      </div>
    </div>
  </div>

<script>
$('#viewFiltered').on('hidden.bs.modal', function () {

});
</script>
              
			<div class="card-body">			  

			<table id="filterTable" class="table table-bordered table-striped" style="width:100%; font-size:14px; color:#000; margin-top:10px;">
			<thead>
                    <th>SN</th>
                    <th>Reg. Number</th> 					
                    <th>Full Name</th>
					<th data-sortable="false"></th>					
            </tr>
			</thead>
			<tbody>
              <?php while($row = $listResults->fetch_assoc()) { ?>
              <tr style="color:#000; font-size:14px;">
					<td><?php echo $row['sn']; ?></td>
					<td><?php echo $row['regNumber']; ?></td>	
					<td><?php echo $row['Fullname']; ?></td>
					<td data-sortable="false"> 
					<div class="dropdown">
						<button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" >
						</button>
						<div class="dropdown-menu dropdown-menu-right">					
						<a class="dropdown-item" href="viewFiltered?regNumber=<?php echo $row['regNumber']; ?>" data-toggle="modal" data-target="#viewFiltered" name="viewFiltered" ><i class="fa fa-eye" aria-hidden="true"></i> View Participant</a>
						</div>
						</div>						
					</td> 					
				</tr>
                <?php } ?>
			</tbody>   
		</table>


              </div>
              <!-- /.card-body -->

          </div>
        </div>
        <!-- /.col-->
      </div>
      <!-- ./row -->
      <!-- ./row -->
    </section>
    <!-- /.content -->

 </div>
  <!-- InstanceEndEditable --><!-- /.content-wrapper -->
  <footer class="main-footer" style="font-size:14px">
    <div class="float-right d-none d-sm-block">
      <span>Version</span> <?php echo($rsSettings->getColumnVal("version")); ?>
    </div>
    <span><?php echo($rsSettings->getColumnVal("appname")); ?> © 2023 Cross River State Government - All rights reserved. Developed & Maintained By <a href="https://netdataflow.com">NetDataflow Support Systems Ltd.</a>.</span> 
  </footer>

  <!-- Control Sidebar -->
  <aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
  </aside>
  <!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="dist/js/adminlte.min.js"></script>
<!-- AdminLTE for demo purposes -->
<script src="dist/js/demo.js"></script>
<!-- Page specific script -->

</body>
<!-- InstanceEnd --></html>
<!-- DataTables  & Plugins -->
<script src="plugins/datatables/jquery.dataTables.min.js"></script>
<script src='plugins/rowgroup/1.1.2/js/dataTables.rowGroup.min.js'></script>
<script src="plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
<script src="plugins/jszip/jszip.min.js"></script>
<script src="plugins/pdfmake/pdfmake.min.js"></script>
<script src="plugins/pdfmake/vfs_fonts.js"></script>
<script src="plugins/datatables-buttons/js/buttons.html5.min.js"></script>
<script src="plugins/datatables-buttons/js/buttons.print.min.js"></script>
<script src="plugins/datatables-buttons/js/buttons.colVis.min.js"></script>
<!-- Page specific script -->
<script>
  $(function () {
    $("#filterTable").DataTable({
      "responsive": true, "autoWidth": true, "ordering": true,
	  dom: 'Blfrtip',
      "buttons": ["copy", "excel", "print", "colvis"],
	  "paging": true,
      "lengthChange": true,
      "searching": true,
      "info": true,		  
	  "lengthMenu": [[100, 250, 500, 1000, -1], [100, 250, 500, 1000, "All"]],
    }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');

  });
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/chosen/1.8.6/chosen.jquery.min.js"></script>

<script type="text/javascript">
$('.form-control-chosen').chosen({
  allow_single_deselect: true,
  width: '100%'
});
  </script>
  
<script>
$(".chosen-select").chosen({
  no_results_text: "Oops, nothing found!"
})
</script>  
 