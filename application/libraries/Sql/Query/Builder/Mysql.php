<?php

	/**
	 * MySQL dialect SQL query builder.
	 *
	 * @package Sql::Query::Builder
	 * @author James Stephenson
	 **/
	class Sql_Query_Builder_Mysql extends Sql_Query_Builder_Abstract
	{
		public function build()
		{
			$this->_resolveSources();
			
			$sql = 'SELECT ' . implode(', ', $this->getFields()) . ' FROM ' . implode(', ', $this->getSources());
			if ($this->hasConditions()) {
				$sql .= ' WHERE ' . $this->getConditions();
			}
			
			if ($this->hasSorts()) {
				$sql .= ' ORDER BY ' . $this->getOrderBy();
			}
			
			if ($this->hasOffset() OR $this->hasLimit()) {
				$sql .= ' LIMIT ' . $this->getOffset() . ', ' . $this->getLimit();
			}
			
			$this->setSql($sql);
			
			return $this;
		}
		
	} // END class Sql_Query_Builder_Mysql implements Sql_Query_Builder_Abstract