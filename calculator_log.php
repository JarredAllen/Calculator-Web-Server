<?php
	include 'myphpheader.php';
		
	function getRowsPerPage() {
		return 10;
	}
	
	function getNumLogEntries() {
		$cmd = "SELECT COUNT(*) FROM calc_log;";
		$conn = new PDO("mysql:host=localhost;dbname=mysql", view_username, view_password);
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$stmt = $conn->prepare($cmd);
		$stmt->execute();
		return $stmt->fetchAll()[0][0];
	}
	
	if(!hasAdminAccess()) {
		http_response_code(403);
		echo 'Please <a href="/login.php?redirect=/calculator_log.php&logout">log in</a> as an admin to continue.';
		if(isLoggedIn()) {
			echo ' You do not have admin access with your account.';
		}
		//echo ' '.getUserId(null);
		die();
	}
?>
<!DOCTYPE html>

<title>Logs for Jarred's calculator</title>

<style>
.hidden {
	display: none;
}

.button {
	width: 35px;
	height: 35px;
	font-size: 30px;
	display: inline-block;
	
	text-align: center;
	vertical-align: middle;
}

.button:hover {
	background-color:#f0f0f0;
}

#buttons {
	border: 1px solid #111111;
	display: table;
	margin: auto;
	text-align: center;
}
</style>

<style id="presentTable"> #button1{background-color:#dddddd;} </style>

	
<script>
	function htmlEntities(str) {
		return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
	}
	
	function displayPage(num) {
		document.getElementById("presentTable").innerHTML = '#button'+num+'{background-color: #dddddd;}';
		var table=document.getElementById("log_table");
		try {
			while(true) {
				table.deleteRow(1);
			}
		}
		catch(e) {}
		
		function onReceive() {
			var table=document.getElementById("log_table");
			var data=JSON.parse(this.responseText);
			for(rownum in data) {
				var row = table.insertRow(rownum);
				var rowData=data[rownum];
				row.insertCell(0).innerHTML=htmlEntities(rowData[0]);
				row.insertCell(1).innerHTML=htmlEntities(rowData[1]);
				if(rowData[2]===null) {
					row.insertCell(2);
				}
				else {
					row.insertCell(2).innerHTML=htmlEntities(rowData[2]);	
				}
				row.insertCell(3).innerHTML=htmlEntities(rowData[3]);
				row.insertCell(4).innerHTML=htmlEntities(rowData[4]);
				row.insertCell(5).innerHTML=htmlEntities(rowData[5]);
			}
		}
		
		var req=new XMLHttpRequest();
		req.addEventListener("load", onReceive);
		req.open("POST", "/api.php/calculations?page="+num+"<?php if(isset($_POST['sortby'])){ echo $_POST['sortby'].'"';} else{ echo '"';} ?>);
		req.send();
	}
	
	displayPage(1);
</script>


<body>
	<table id="log_table" border="1">
		<tr>
			<th onclick="document.getElementById('TimestampForm').submit()">Timestamp</th><th onclick="document.getElementById('IPAddressForm').submit()">IP Address</th>
			<th onclick="document.getElementById('UserIDForm').submit()">User</th> <th onclick="document.getElementById('UserAgentForm').submit()">User Agent</th>
			<th onclick="document.getElementById('OperationForm').submit()">Calculation</th><th onclick="document.getElementById('ResultForm').submit()">Result</th>
		</tr>
	</div>
	
	<div id="buttons">
		<?php
			$numpages = intval(ceil(getNumLogEntries()/floatval(getRowsPerPage())));
			for($i=1; $i<=$numpages; $i++) {
				echo '<div class="button" id="button'.$i.'" onclick="displayPage('.$i.')">'.$i.'</div>';
			}
		?>
	</div>
	
	<div class="hidden">
		<form id="TimestampForm" method="post"> <input type="hidden" name="sortby" value="&orderby=Timestamp&order=DESC"> </form>
		<form id="IPAddressForm" method="post"> <input type="hidden" name="sortby" value="&orderby=IPAddress"> </form>
		<form id="UserIDForm"    method="post"> <input type="hidden" name="sortby" value="&orderby=UserID"> </form>
		<form id="UserAgentForm" method="post"> <input type="hidden" name="sortby" value="&orderby=UserAgent"> </form>
		<form id="OperationForm" method="post"> <input type="hidden" name="sortby" value="&orderby=Operation"> </form>
		<form id="ResultForm"    method="post"> <input type="hidden" name="sortby" value="&orderby=Result"> </form>
	</div>
</body>