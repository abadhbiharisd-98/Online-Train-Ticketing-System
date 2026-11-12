<?php 

?>

<div class="container-fluid">
	
	<div class="row">
	<div class="col-lg-12">
			<button class="btn btn-primary float-right btn-sm" id="new_user"><i class="fa fa-plus"></i> New user</button>
	</div>
	</div>
	<br>
	<div class="col-lg-12">
		<div class="card ">
			<div class="card-header"><b>User List</b></div>
			<div class="card-body">
				<table class="table-striped table-bordered w-100">
			<thead>
				<tr>
					<th class="text-center">#</th>
					<th class="text-center">Name</th>
					<th class="text-center">Username</th>
					<th class="text-center">Type</th>
					<th class="text-center">Station Handles</th>
					<th class="text-center">Action</th>
				</tr>
			</thead>
			<tbody>
				<?php
 					include 'db_connect.php';
 					$type = array("","Admin","Staff");
 					$sname = array();
 					$sname[0] = "N/A";
 					if (isset($conn) && $conn instanceof mysqli) {
 						$station = $conn->query("SELECT * FROM stations");
 						if ($station && $station instanceof mysqli_result) {
 							while($row=$station->fetch_assoc()){
 								$sname[$row['id']] = ucwords($row['station']);
 							}
 						}
 						$users = $conn->query("SELECT * FROM users order by name asc");
 					} else {
 						$users = false;
 					}
 					$i = 1;
 					if ($users && $users instanceof mysqli_result):
 					while($row = $users->fetch_assoc()):
				 ?>
				 <tr>
				 	<td class="text-center">
				 		<?php echo $i++ ?>
				 	</td>
				 	<td>
				 		<?php echo htmlspecialchars(ucwords($row['name'])) ?>
				 	</td>
				 	
				 	<td>
				 		<?php echo htmlspecialchars($row['username']) ?>
				 	</td>
				 	<td>
				 		<?php echo isset($type[$row['type']]) ? $type[$row['type']] : 'Unknown' ?>
				 	</td>
				 	<td>
				 		<?php echo isset($sname[$row['station_id']]) ? htmlspecialchars($sname[$row['station_id']]) : 'Station Removed' ?>
				 	</td>
				 	<td>
				 		<center>
							<div class="btn-group">
							  <button type="button" class="btn btn-primary btn-sm">Action</button>
							  <button type="button" class="btn btn-primary btn-sm dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
							    <span class="sr-only">Toggle Dropdown</span>
							  </button>
							  <div class="dropdown-menu">
							    <a class="dropdown-item edit_user" href="javascript:void(0)" data-id = '<?php echo $row['id'] ?>'>Edit</a>
							    <div class="dropdown-divider"></div>
							    <a class="dropdown-item delete_user" href="javascript:void(0)" data-id = '<?php echo $row['id'] ?>'>Delete</a>
							  </div>
							</div>
						</center>
				 	</td>
				 </tr>
				<?php endwhile; endif; ?>
			</tbody>
		</table>
			</div>
		</div>
	</div>

</div>
<script>
	$('table').dataTable();
$('#new_user').click(function(){
	uni_modal('New User','manage_user.php')
})
$('.edit_user').click(function(){
	uni_modal('Edit User','manage_user.php?id='+$(this).attr('data-id'))
})
$('.delete_user').click(function(){
		_conf("Are you sure to delete this user?","delete_user",[$(this).attr('data-id')])
	})
	function delete_user($id){
		start_load()
		$.ajax({
			url:'ajax.php?action=delete_user',
			method:'POST',
			data:{id:$id},
			success:function(resp){
				if(resp==1){
					alert_toast("Data successfully deleted",'success')
					setTimeout(function(){
						location.reload()
					},1500)

				}
			}
		})
	}
</script>
