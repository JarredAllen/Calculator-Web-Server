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
			
			var errors=false;
			
			for(var i=1;i<=parseInt(form.getAttribute("data-operand-count")); i++) {
				var num = +document.getElementById("operand"+i).value;
				if(isNaN(num)) {
					errors=true;
					document.getElementById("input_error_display").innerHTML+="Operand #"+i+" is not a valid number";
				}
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
			var operands=[];
			for(var i=1;i<=parseInt(form.getAttribute("data-operand-count")); i++) {
				operands[i-1] = document.getElementById("operand"+i).value;
				//console.log("operand #"+i+"="+document.getElementById("operand"+i).value);
			}
			var op  = form.operation.value;
			
			function displayAnswer() {
				var ans = this.responseText.split("=");
				document.getElementById("answer").innerHTML=ans[1];
				
				var table=document.getElementById("history");
				try {
					table.deleteRow(10);
				}
				catch (err) {
					//The user does not have ten entries in the table
					//Don't remove anything, because it will break
				}
				var row=table.insertRow(1);
				row.innerHTML="<td>"+ans[0]+"</td><td>"+ans[1]+"</td>";
			}
			var res = new XMLHttpRequest();
			res.addEventListener("load",displayAnswer);
			var uri="/api.php/calculate/"+op;
			for (operand in operands) {
				uri+="/"+operands[operand]
			}
			res.open("GET", uri);
			res.send();
			
			//return false, so it doesn't POST on its own
			return false;
		}
		
		function htmlEntities(str) {
			return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
		}
		
		function displayHistory() {
			var tableData=JSON.parse(this.responseText);
			var table=document.getElementById("history");
			
			for(rowNum in tableData) {
				var row=table.insertRow(parseInt(rowNum));
				
				row.insertCell(0).innerHTML=htmlEntities(tableData[rowNum][4]);
				row.insertCell(1).innerHTML=htmlEntities(tableData[rowNum][5]);
			}
		}
		
		function loadHistory() {
			var histreq=new XMLHttpRequest();
			histreq.addEventListener("load", displayHistory);
			histreq.open("GET", "/api.php/calculations?page=1&sortby=timestamp&user="+JSON.parse(this.responseText).id);
			histreq.send();
		}
		
		var ureq=new XMLHttpRequest();
		ureq.addEventListener("load", loadHistory);
		ureq.open("GET", "/api.php/userid");
		ureq.send();
		
		function onReceiveBanner() {
			document.getElementById("banner_holder").innerHTML = this.responseText;
		}
		var req=new XMLHttpRequest();
		req.addEventListener("load", onReceiveBanner);
		req.open("GET", "/backend/banner.php");
		req.send();
		
		var operations=new Object();
		function loadOperations() {
			operations=JSON.parse(this.responseText);
			var opDropdown=document.getElementById("operation");
			for(op in operations) {
				opDropdown.innerHTML+="<option id=\"operation_"+op+"_option\">"+op.charAt(0).toUpperCase()+op.slice(1)+"</option>";
				document.getElementById("operation_"+op+"_option").setAttribute("data-number", ""+operations[op].numbers);
			}
			adjustFormSize();
			// document.getElementById("debug_stuff").innerHTML=this.responseText;
		}
		req=new XMLHttpRequest();
		req.addEventListener("load", loadOperations);
		req.open("GET", "/api.php/calculate/operations");
		req.send();
		
		function adjustFormSize() {
			var select = document.getElementById("input").operation;
			var option = document.getElementById("operation_"+select.value.toLowerCase()+"_option");
			var operands=parseInt(option.getAttribute("data-number"));
			document.getElementById("input").setAttribute("data-operand-count", ""+operands);
			var table=document.getElementById("input_table");
			while(table.rows.length>1) {
				table.deleteRow(0);
			}
			for(var i=1; i<=operands; i++) {
				var row = table.insertRow(i-1);
				row.insertCell(0).innerHTML = 'Operand #'+i;
				row.insertCell(1).innerHTML = '<input type="text" id="operand'+i+'" name="operand'+i+'" autocomplete="off" required>';
			}
		}
	</script>
</head>

<body>
	<div id="banner_holder"></div>

	<div class="input">
	<p id="input_prompt">Please input the numbers and the operation.</p>
	<p id="input_error_display"></p>
	<form name="values" id="input" onSubmit="return processData()" method="post">
		<table id="input_table">
		<tr><td>Operand #1: </td>		<td><input type="text" id="operand1" name="operand1" autocomplete="off" required autofocus></td></tr>
		<tr><td>Operand #2: </td>		<td><input type="text" id="operand2" name="operand2" autocomplete="off" required></br></td></tr>
		<tr><td>Operation:&emsp;</td>	<td><select name="operation" id="operation" oninput="adjustFormSize()"></select></td></tr>
		</table>
		<input type="submit" value="Calculate!">
	</form>
	</div>
	
	<div class="answer" id="answer"></div>
	
	<table id="history" border="2">
	<tr><th>Operation</th><th>Result</th></tr>
	</table>
	
	<div id="log_response"></div>
	
	<div id="debug_stuff"></div>
</body>