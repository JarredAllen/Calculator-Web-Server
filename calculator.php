<?php
	include 'myphpheader.php';
?>
<!DOCTYPE html>

<head>
	<title>Jarred's Calculator</title>
	<link rel="stylesheet" type="text/css" href="/calculator_style.css">

	<script>
		function displayHistory() {
			var tableData=JSON.parse(this.responseText);
			var table=document.getElementById("history");
			
			for(rowNum in tableData) {
				var row=table.insertRow(parseInt(rowNum));
				
				row.insertCell(0).innerHTML=tableData[rowNum][4];
				row.insertCell(1).innerHTML=tableData[rowNum][5];
			}
		}
		
		function loadHistory() {
			var histreq=new XMLHttpRequest();
			histreq.addEventListener("load", displayHistory);
			histreq.open("GET", "/api.php/calculations?page=1&sortby=timestamp&user="+this.responseText);
			histreq.send();
		}
		
		function onReceiveBanner() {
			document.getElementById("banner_holder").innerHTML = this.responseText;
			
			var ureq=new XMLHttpRequest();
			ureq.addEventListener("load", loadHistory);
			ureq.open("GET", "/api.php/userid");
			ureq.send();
		}
		var req=new XMLHttpRequest();
		req.addEventListener("load", onReceiveBanner);
		req.open("GET", "/backend/banner.php");
		req.send();
	</script>
</head>

<body>
	<div id="banner_holder"></div>
	<div class="input">
	<p>Please input the numbers and the operation.</p>
	<form name="values" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
		<table>
		<tr><td>First Number: &emsp;&emsp;</td>			<td><input type="text" name="firstnumber" required value="<?php echo isset($_POST['firstnumber']) ? $_POST['firstnumber'] : '' ?>"></td></tr>
		<tr><td>Second Number:&emsp;</td>				<td><input type="text" name="secondnumber" required value="<?php echo isset($_POST['secondnumber']) ? $_POST['secondnumber'] : '' ?>"></br></td></tr>
		<tr><td>Operation: &emsp; &emsp; &emsp;</td>	<td><select name="operation" value="<?php echo isset($_POST['operation']) ? $_POST['operation'] : '' ?>">
											<?php
											$operations=["Add", "Subtract", "Multiply", "Divide"];
											foreach($operations as $op) {
												if(isset($_POST['operation'])) {
													echo "<option" . ($_POST['operation']==$op ? ' selected>' : '>') . $op . '</option>';
												}
												else {
													echo "<option>" . $op . "</option>";
												}
											}
											?></select></td></tr>
		</table>
		<input type="submit" value="Calculate!">
	</form>
	</div>
	
	<div class="answer"><?php
	if(isset($_POST['firstnumber'])) {
		//Display the correct value to the user
		$result=0;
		switch($_POST['operation']) {
			case 'Add':
				$result=((double)$_POST["firstnumber"])+((double)$_POST["secondnumber"]);
				break;
			case 'Subtract':
				$result=((double)$_POST["firstnumber"])-((double)$_POST["secondnumber"]);
				break;
			case 'Multiply':
				$result=((double)$_POST["firstnumber"])*((double)$_POST["secondnumber"]);
				break;
			case 'Divide':
				if($_POST['secondnumber']=='0') {
					$result='<div id="answer_error">ERROR: Division by zero</div>';
				}
				else {
					$result=((double)$_POST["firstnumber"])/((double)$_POST["secondnumber"]);
				}
				break;
			default;
				$result="Invalid operation";
				break;
		}
		echo $result;
		//log the calculation
		$timestamp = date("m/d/Y h:i:sa");
		$ipaddress = $_SERVER['REMOTE_ADDR'];
		$userAgent = 'No user agent';
		$op = $_POST['firstnumber'];
		if(isset($_SERVER['HTTP_USER_AGENT'])) {
			$userAgent = $_SERVER['HTTP_USER_AGENT'];
		}
		switch($_POST['operation']) {
			case 'Add':
				$op = $op . '+';
				break;
			
			case 'Subtract':
				$op = $op . '-';
				break;
			
			case 'Multiply':
				$op = $op . '*';
				break;
			
			case 'Divide':
				$op = $op . '/';
				break;
			
			default:
				$op = $op . '?';
				break;
		}
		$op = $op . $_POST['secondnumber'];
		
		echo '</div><div class="errors">';
		
		try {
			$conn = new PDO("mysql:host=localhost;dbname=mysql", 'insert', null);
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$cmd = "INSERT INTO calc_log (IPAddress, UserAgent, Operation, Result) VALUES (INET6_ATON(:ipaddress), :userAgent, :op, :result)";
			//echo $cmd;
			$stmt = $conn->prepare($cmd);
			$stmt->bindParam(':ipaddress', $ipaddress);
			$stmt->bindParam(':userAgent', $userAgent);
			$stmt->bindParam(':op', $op);
			$stmt->bindParam(':result', $result);
			$stmt->execute();
		}
		catch(PDOException $e) {
			echo '</br>' . $e->getMessage();
		}
	}
	?></div>
	<table id="history" border="2">
	<tr><th>Operation</th><th>Result</th></tr>
	</table>
</body>