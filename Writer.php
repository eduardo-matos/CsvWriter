<?php

class Writer implements ArrayAccess
{
	protected $_header = array();
	protected $_rows   = array();
	protected $_fileHandler;
	protected $_delimiter;
	protected $_keys = array();
	protected $_currentRow = array();
	
	const COLUMN_DOES_NOT_EXIST = 'Column does not exist';

	public function __construct()
	{
		$this->_createFileHandler();
		$this->setDelimiter(';');
	}

	public function save($destination = null)
	{
		if(!empty($this->_currentRow)) {
			$this->addRow($this->_currentRow);
		}
		
		$this->_ensemble();
		fclose($this->_fileHandler);
		if($destination) {
			rename('./csv.csv',$destination);
		}
		return true;
	}

	public function addValue($value = '') 
	{
		array_push($this->_currentRow,$value);
		return $this;
	}

	public function nextRow() 
	{
		$this->addRow($this->_currentRow);
		$this->_currentRow = Array();
		return $this;
	}

	public function __set($name,$value)
	{
		if(isset($this->_keys[$name])) {
			$this->_currentRow[$this->_keys[$name]] = $value;
		} else { 
			throw new Exception(Writer::COLUMN_DOES_NOT_EXIST);
		}
	}

	protected function _createFilehandler()
	{
		$this->_fileHandler = fopen('csv.csv','w');
	}

	public function setHeader($values = array())
	{
		$this->_header = $values;
		$this->_keys = array_flip($values);
		return $this;
	}

	public function addRow($row = array())
	{
		ksort($row);
		if(count($row)) {
			array_push($this->_rows,$row);
		}
		return $this;
	}

	public function setRows($rows = array())
	{
		foreach($rows as $row) {
			$this->addRow($row);
		}
		return $this;
	}

	public function setDelimiter($delimiter)
	{
		$this->_delimiter = $delimiter;
		return $this;
	}

	protected function _writeRow($row)
	{
		$r = $this->_quoteValues($row);
		
		fwrite(
			$this->_fileHandler,
			implode($this->_delimiter,$r) . "\r\n"
		);
	}

	protected function _ensemble()
	{
		$this->_writeRow(
			$this->_header
		);
		
		foreach($this->_rows as $row) {
			$this->_writeRow($row);
		}	
	}

	protected function _quoteValues($values)
	{
		return array_map(function($value){
				return '"' . $value . '"';
		},$values);
	}

	public function offsetExists($offset) 
	{
		return isset($this->_currentRow[$this->_keys[$offset]]);
	}
	
	public function offsetGet($offset) 
	{
		return isset($this->_currentRow[$this->_keys[$offset]]) ? $this->_currentRow[$this->_keys[$offset]] : null;
	}

	public function offsetSet($offset,$value)
	{
		if($this->offsetExists($offset)) {
			$this->_currentRow[$this->_keys[$offset]] = $value;
		} else {
			throw new Exception(Writer::COLUMN_DOES_NOT_EXIST);
		}
	}

	public function offsetUnset($offset) 
	{
		unset($this->_currentRow[$this->_keys[$offset]]);
		return $this;
	}

}