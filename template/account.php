<h5>Account</h5>

		<table class="table table-hover">
			<thead>
				<th>Account ID</th>
				<th>Email</th>
				
				<th>Last Login</th>
				<th>Status</th>
				<th>Validate</th>
				<th>Security</th>
				<th>Status</th>
				<th>Wallet BTC</th>
			</thead>
			<tbody>
				<?php foreach ($data as $key => $value) { ?>
					<tr>
						<td width="5%"><?php echo $value->id;?></td>
						<td><?php echo $value->email;?></td>
						
						<td><?php echo $value->updated_at;?></td>
						<td></td>
						<td></td>
						<td>
							<?php if($value->validate_f2a_code){ ?>
								F2A
							<?php } ?>
							<?php if($value->validate_email){ ?>
								Email
							<?php } ?>
						</td>
						<td><?php echo ($value->status == 1 ? "Online" : "Block");?></td>
						<td class="text-right"><?php echo ($value->btc_address ? $value->btc_address : "<a class='btn btn-sm btn-outline-info' href='".store_url("account/createwalletbtc/".$value->id)."'>Make</a>");?></td>
					</tr>
					
				<?php } ?>
			</tbody>
		</table>
	