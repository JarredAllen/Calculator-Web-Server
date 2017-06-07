<?php
	//sample file
	//not actual login credentials
	function databaseViewLogin() {
		//credentials that can only view data
		return array('view', null);
	}
	
	function databaseInsertLogin() {
		//credentials that can view and insert data, but not delete it
		return array('insert', null);
	}
	
	function databaseModifyLogin() {
		//credentials that can modify and remove data, but not databases or tables
		return array('modify', null);
	}
?>