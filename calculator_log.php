<?php
	include 'myphpheader.php';
?>
<!DOCTYPE html>

<title>Logs for Jarred's calculator</title>

<style>
.hidden {
	display: none;
}

.table {
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

<style id="presentTable">#table0{display: inline;} #button0{background-color:#dddddd;}</style>

	
<script>
function displayPage(num) {
document.getElementById("presentTable").innerHTML = '#table'+num+'{display: inline;} #button'+num+'{background-color: #dddddd;}';
}
</script>


<body>
	<div id="all_tables">
		<?php 
		function startTable($num) {
			echo '<table class="table" id="table'.$num.'" border="1">';
			echo '<tr><th onclick="document.getElementById(\'TimestampForm\').submit()">Timestamp</th>	<th onclick="document.getElementById(\'IPAddressForm\').submit()">IP Address</th>';
			echo '<th onclick="document.getElementById(\'UserIDForm\').submit()">User</th> <th onclick="document.getElementById(\'UserAgentForm\').submit()">User Agent</th>';
			echo '<th onclick="document.getElementById(\'OperationForm\').submit()">Calculation</th><th onclick="document.getElementById(\'ResultForm\').submit()">Result</th></tr>';
		}
		
		function getRowsPerPage() {
			return 10;
		}
		
		function getNumLogEntries() {
			$cmd = "SELECT COUNT(*) FROM calc_log;";
			$conn = new PDO("mysql:host=localhost;dbname=mysql", databaseViewLogin()[0], databaseViewLogin()[1]);
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$stmt = $conn->prepare($cmd);
			$stmt->execute();
			return $stmt->fetchAll()[0][0];
		}
		
		try {
			//figure out how many there are
			//setting variables
			$numrows = getNumLogEntries();
			$display = getRowsPerPage();
			
			//preparing the paramaterized SQL statement
			$conn = new PDO("mysql:host=localhost;dbname=mysql", databaseViewLogin()[0], databaseViewLogin()[1]);
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$cmd = 'SELECT Timestamp, INET6_NTOA(IPAddress), UserID, UserAgent, Operation, Result FROM calc_log';
			if(isset($_POST['sortby'])) {
				$sortby=preg_split('/[ \t\n\r;\'"]+/', $_POST['sortby'], 0, PREG_SPLIT_NO_EMPTY);
				$cmd .= ' ORDER BY '.$sortby[0];
				if(isset($sortby[1]) and ($sortby[1]=='ASC' or $sortby[1]=='DESC')) {
					$cmd .= ' ' . $sortby[1];
				}
			}
			$cmd .= ' LIMIT :i, '.$display;
			$stmt = $conn->prepare($cmd);
			$stmt->bindParam(':i', $i);
			//displaying each group
			for($i=0; $i<$numrows; $i+=$display) {
				startTable($i/$display);
				// echo $cmd;
				$stmt->execute();
				$stmt->setFetchMode(PDO::FETCH_ASSOC);
				//display the data
				foreach($stmt->fetchAll() as $k=>$v) {
					$time=htmlentities($v['Timestamp']);
					$ipadd=htmlentities($v['INET6_NTOA(IPAddress)']);
					$user=$v['UserID'];
					if($user=='') {
						$user='None';
					}
					else {
						$user=htmlentities(getUserById($user));
					}
					$ua=htmlentities($v['UserAgent']);
					$op=htmlentities($v['Operation']);
					$res=htmlentities($v['Result']);
					echo '<tr><td>'.$time.'</td><td>'.$ipadd.'</td><td>'.$user.'</td><td>'.$ua.'</td><td>'.$op.'</td><td>'.$res.'</td></tr>';
				}
				echo '</table>';
			}
		}
		catch(PDOException $e) {
			echo '</br>' . $e->getMessage();
		}
		?>
	</div>
	
	<div id="buttons">
		<?php
			$numpages = intval(ceil(getNumLogEntries()/floatval(getRowsPerPage())));
			for($i=1; $i<=$numpages; $i++) {
				echo '<div class="button" id="button'.($i-1).'" onclick="displayPage('.($i-1).')">'.$i.'</div>';
			}
		?>
	</div>
	
	<div class="hidden">
		<form id="TimestampForm" method="post"> <input type="hidden" name="sortby" value="Timestamp DESC"> </form>
		<form id="IPAddressForm" method="post"> <input type="hidden" name="sortby" value="IPAddress"> </form>
		<form id="UserIDForm" method="post"> <input type="hidden" name="sortby" value="UserID"> </form>
		<form id="UserAgentForm" method="post"> <input type="hidden" name="sortby" value="UserAgent"> </form>
		<form id="OperationForm" method="post"> <input type="hidden" name="sortby" value="Operation"> </form>
		<form id="ResultForm" method="post"> <input type="hidden" name="sortby" value="Result"> </form>
	</div>
</body>