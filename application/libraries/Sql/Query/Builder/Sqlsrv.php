<?php

	/**
	 * Query builder for the sqlsrv driver.
	 *
	 * @package Sql::Query::Builder
	 * @author James Stephenson
	 **/
	class Sql_Query_Builder_Sqlsrv extends Sql_Query_Builder_Abstract
	{
		protected $_replacements = array(
			'NOW' => 'SYSUTCDATETIME',
			'UTC_TIMESTAMP' => 'SYSUTCDATETIME',
			'TIMESTAMPADD' => 'DATEADD'
		);
			
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
				$field = 'ROW_NUMBER() OVER(ORDER BY ';
				if ($this->hasSorts()) {
					$field .= $this->getOrderBy();
					$this->setSorts(array());
				} else {
					$field .= 'id ASC';
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
				$sql .= ' ORDER BY ' . $this->getOrderBy();
			}
			
			$sql = $this->_resolveTokens($sql);
			$this->setSql($sql);
			
			return true;
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
				if (is_int($limit)) {
					$bound .= 'BETWEEN ? AND ?';
					$params = array($offset, $offset + $limit);
				} else {
					$bound .= '> ?';
					$params[] = $offset;
				}
			} else {
				if (is_int($limit)) {
					$bound .= '<= ?';
					$params[] = $limit;
				}
			}
			
			return array($bound, $params);
		}
		
	} // END class Sql_Query_Builder_Sqlsrv