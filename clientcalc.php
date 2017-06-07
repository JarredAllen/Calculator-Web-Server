<?php
	include 'myphpheader.php';
?>
<!DOCTYPE html>
<head>
	<title>Jarred's Calculator</title>
	<link rel="stylesheet" type="text/css" href="/calculator_style.css">

	<script>
		function validateInput() {
			//uncomment the next line to force input to validate
			//return true;
			//check valid input, to ease the user experience
			var form = document.getElementById("input");
			document.getElementById("input_error_display").innerHTML="";
			
			var one = +form.firstnumber.value;
			var two = +form.secondnumber.value;
			var errors=false;
			
			if(isNaN(one)) {
				errors=true;
				document.getElementById("input_error_display").innerHTML+="Your first number is not a valid number. ";
			}
			
			if(isNaN(two)) {
				errors=true;
				document.getElementById("input_error_display").innerHTML+="Your second number is not a valid number.";
			}
			
			return !errors;
		}
		
		function displayLogResponse() {
			document.getElementById("log_response").innerHTML = this.responseText;
		}
		
		function processData() {
			if(!validateInput()) {
				return false;
			}
			//do the calculation
			var form = document.getElementById("input");
			
			var one = form.firstnumber.value;
			var two = form.secondnumber.value;
			var op  = form.operation.value;
			var out = 0;
			var calc=one+'?'+two;
			
			switch(op) {
				case "Add":
					out=+one+(+two);
					calc=one+'+'+two;
					break;
				case "Subtract":
					out=+one-+two;
					calc=one+'-'+two;
					break;
				case "Multiply":
					out=+one*+two;
					calc=one+'*'+two;
					break;
				case "Divide":
					out=+one/+two;
					calc=one+'/'+two;
					break;
			}
			document.getElementById("answer").innerHTML=out;
			
			//log the calculation
			var req=new XMLHttpRequest();
			var params='entry='+JSON.stringify({"operation":calc, "result":out});
			req.addEventListener("load", displayLogResponse);
			req.open("POST", "/api.php/calculations", true);
			req.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
			req.send(params);
			
			//add the calculation to the local history
			var table=document.getElementById("history");
			try {
				table.deleteRow(10);
			}
			catch (err) {
				//The user does not have ten entries in the table
				//Don't remove anything, because it will break
				//Also don't remove this line
			}
			var row=table.insertRow(1);
			row.innerHTML="<td>"+calc+"</td><td>"+out+"</td>";
			
			//return false, so it doesn't POST on its own
			return false;
		}
		
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
	<p id="input_prompt">Please input the numbers and the operation.</p>
	<p id="input_error_display"></p>
	<form name="values" id="input" onSubmit="return processData()" method="post">
		<table>
		<tr><td>First Number: &emsp;&emsp;</td>			<td><input type="text" id="firstnumber" name="firstnumber" autocomplete="off" required autofocus value="<?php echo isset($_POST['firstnumber']) ? $_POST['firstnumber'] : '' ?>"></td></tr>
		<tr><td>Second Number:&emsp;</td>				<td><input type="text" id="secondnumber" name="secondnumber" autocomplete="off" required value="<?php echo isset($_POST['secondnumber']) ? $_POST['secondnumber'] : '' ?>"></br></td></tr>
		<tr><td>Operation: &emsp; &emsp; &emsp;</td>	<td><select name="operation" id="operation" value="<?php echo isset($_POST['operation']) ? $_POST['operation'] : '' ?>">
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
	
	<div class="answer" id="answer"></div>
	
	<table id="history" border="2">
	<tr><th>Operation</th><th>Result</th></tr>
	</table>
	
	<div id="log_response"></div>
</body>