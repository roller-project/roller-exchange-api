<h5>Coind Services</h5>
<table class="table table-hover">
	<thead>
		<th>Symbol</th>
		<th>Name</th>
		<th>Base Wallet</th>
		<th>Class</th>
		<th>Server</th>
		<th>Chain</th>
		<th>Blocks</th>
		<th>Peer</th>
	</thead>
	<tbody>
		<?php foreach ($data as $key => $value) { ?>
			
		<tr id="status_<?php echo $value->symbol_name;?>">
			<td><?php echo $value->symbol_name;?></td>
			<td><?php echo ($value->symbol_icon ? '<img src="'.$value->symbol_icon.'">' : "");?> <?php echo $value->symbol_fullname;?></td>
			<td class="basewallet">Wallet</td>
			<td><?php echo $value->symbol_class;?></td>
			<td><?php echo $value->rpc_server;?>:<?php echo $value->rpc_port;?></td>
			<td class="chain"></td>
			<td class="blocks"></td>
			<td class="peer"></td>
		</tr>
		<?php } ?>
	</tbody>
</table>

<script type="text/javascript">

	function checkStatus(){
		$.getJSON( "<?php echo store_url("coind/status");?>", function( data ) {

				  $.each( data, function( key, val ) {
				  	
				    $("#status_"+key).find(".basewallet").text(val.basewallet);
				    $("#status_"+key).find(".blocks").text(val.block);
				    $("#status_"+key).find(".chain").text(val.chain);
				    $("#status_"+key).find(".peer").text(val.peer);
				  });
				 
				 
			});
	}
	jQuery(document).ready(function(){
		checkStatus();
		setInterval(function(){
			checkStatus();
		},1000*60);
	});
</script>