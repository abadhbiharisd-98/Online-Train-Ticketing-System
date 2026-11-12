<?php
    include 'db_connect.php';
    $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
?>
<div class="container-fluid">
    <div class="col-lg-12">
        <div class="card">
            <div class="card_body">
            <div class="row justify-content-center pt-4">
                <label for="date" class="mt-2 mr-2"><b>Date</b></label>
                <div class="col-sm-3">
                    <input type="date" name="date" id="date" value="<?php echo htmlspecialchars($date) ?>" class="form-control">
                </div>
            </div>
            <hr>
            <div class="col-md-12">
                <table class="table table-bordered" id='report-list'>
                    <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th class="">Ticket No.</th>
                            <th class="">Origin</th>
                            <th class="">Destination</th>
                            <th class="">Passenger Type</th>
                            <th class="">Amount</th>
                            <th class="">Processed By</th>
                        </tr>
                    </thead>
                    <tbody>
			          <?php
                      $i = 1;
                      $total = 0;
                      $sname_arr = array();
                      if (isset($conn) && $conn instanceof mysqli) {
                          $station = $conn->query("SELECT * FROM stations");
                          if ($station && $station instanceof mysqli_result) {
                              while($row = $station->fetch_array()){
                                  $sname_arr[$row['id']] = ucwords($row['station']);
                              }
                          }
                          $safe_date = $conn->real_escape_string($date);
                          $tickets = $conn->query("SELECT t.*,u.name as uname FROM tickets t inner join users u on u.id = t.processed_by where date(t.date_created) = '$safe_date' order by unix_timestamp(t.date_created) asc ");
                      } else {
                          $tickets = false;
                      }
                      $ptype = array('','Adult','Student','Senior');
                      if($tickets && $tickets instanceof mysqli_result && $tickets->num_rows > 0):
			          while($row = $tickets->fetch_array()):
                      $total += $row['price'];
			          ?>
			          <tr>
                        <td class="text-center"><?php echo $i++ ?></td>
                        <td>
                            <p> <b><?php echo htmlspecialchars($row['ticket_no']) ?></b></p>
                        </td>
                        <td>
                            <p> <b><?php echo isset($sname_arr[$row['station_from']]) ? htmlspecialchars($sname_arr[$row['station_from']]) : 'N/A' ?></b></p>
                        </td>
                        <td>
                            <p> <b><?php echo isset($sname_arr[$row['station_to']]) ? htmlspecialchars($sname_arr[$row['station_to']]) : 'N/A' ?></b></p>
                        </td>
                         <td>
                            <p> <b><?php echo isset($ptype[$row['passenger_type']]) ? $ptype[$row['passenger_type']] : 'Unknown' ?></b></p>
                        </td>
                        <td>
                            <p class="text-right"> <b><?php echo number_format($row['price'],2) ?></b></p>
                        </td>
                        <td>
                            <p> <b><?php echo htmlspecialchars(ucwords($row['uname'])) ?></b></p>
                        </td>
                    </tr>
                    <?php 
                        endwhile;
                        endif;
                    ?>
			        </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="5" class="text-right">Total</th>
                            <th class="text-right"><?php echo number_format($total,2) ?></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
                <hr>
                <div class="col-md-12 mb-4">
                    <center>
                        <button class="btn btn-success btn-sm col-sm-3" type="button" id="print"><i class="fa fa-print"></i> Print</button>
                    </center>
                </div>
            </div>
            </div>
        </div>
    </div>
</div>
<noscript>
	<style>
		table#report-list{
			width:100%;
			border-collapse:collapse
		}
		table#report-list td,table#report-list th{
			border:1px solid
		}
        p{
            margin:unset;
        }
		.text-center{
			text-align:center
		}
        .text-right{
            text-align:right
		}
	</style>
</noscript>
<script>
$('#report-list').dataTable()
$('#date').change(function(){
    location.replace('index.php?page=daily_report&date='+$(this).val())
})
$('#print').click(function(){
        $('#report-list').dataTable().fnDestroy()
		var _c = $('#report-list').clone();
		var ns = $('noscript').clone();
            ns.append(_c)
		var nw = window.open('','_blank','width=900,height=600')
		nw.document.write('<p class="text-center"><b>Daily Report (<?php echo date("F d,Y",strtotime($date)) ?>)</b></p>')
		nw.document.write(ns.html())
		nw.document.close()
		nw.print()
		setTimeout(() => {
			nw.close()
            $('#report-list').dataTable()
		}, 500);
	})
</script>
