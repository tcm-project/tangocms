<?php

	/**
	 * Query builder for the sqlsrv driver.
	 *
	 * @package Sql::Query::Builder
	 * @author James Stephenson
	 **/
	class Sql_Query_Builder_Sqlsrv extends Sql_Query_Builder_Abstract
	{
		/**
		 * Build the query for SQL Server;
		 *
		 * @return string
		 * @author James Stephenson
		 **/
		public function build()
		{
			$this->_resolveSources();
			
			if ($this->hasOffset() OR $this->hasLimit()) {
				
				$fields = $this->getFields();
				$field = 'ROW_NUMBER() OVER(';
				if ($this->hasOrder()) {
					$field .= 'ORDER BY ' . $this->getOrder();
					$this->setOrder('');
				}
				$field .= ') AS [rownum]';
				$fields[] = $field;
				$this->setFields($fields);
				
				$outer = new self();
				
				$outer->setDepth($this->getDepth());
				$this->setDepth($this->getDepth() + 1);
				$outer->setIndex($this->getIndex());
				$this->setIndex(0);
				
				$bound = $this->_getLimitBoundSql($this->getOffset(), $this->getLimit());
				$this->setOffset(null);
				$this->setLimit(null);
				
				$outer->select('*', $this)
					  ->where('[subquery_' . $this->getIndex(). '].[rownum] ' . $bound[0], 
							  $bound[1]);
				
				return $outer->build();
			}
			
			$sql = 'SELECT ' . implode(', ', $this->getFields()) . ' FROM ' . implode(', ', $this->getSources());
			if ($this->hasConditions()) {
				$sql .= ' WHERE ' . $this->getConditions();
			}
			
			if ($this->hasSorts()) {
				$sql .= ' ORDER BY ' . implode(', ', $this->getSorts());
			}
			
			return array($sql, $this->getBinds(), $this->getIndex());
		}
		
		public function getOrder()
		{
			$sorts = array();
			foreach ($this->_sorts as $field => $sort) {
				$sorts[] = '[' . $field . '] ' . $sort;
			}
			
			return implode(', ', $sorts);
		}
		
		private function _getLimitBoundSql($offset, $limit)
		{
			$bound = '';
			$params = array();
			if (is_int($offset)) {
				$bound .= '> ?';
				$params[] = $offset;
			}
			
			if (is_int($limit)) {
				if (!empty($bound)) {
					$bound .= ' AND ';
				}
				
				$bound .= '<= ?';
				$params[] = $limit;
			}
			
			return array($bound, $params);
		}
		
	} // END class Sql_Query_Builder_Sqlsrv