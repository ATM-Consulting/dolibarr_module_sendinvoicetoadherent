<?php

class TSendInvoiceToAdherent extends TObjetStd
{
	function __construct() 
	{
        $this->_init_vars('errors');
	    $this->start();
		
		$this->errors = array();
	}
	
	function load(&$PDOdb, $id) 
	{
		parent::load($PDOdb, $id);
	}
	
	function save($PDOdb)
	{
		parent::save($PDOdb);
	}
	
}
