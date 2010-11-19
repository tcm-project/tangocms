<?php

	/**
	 * SQL query builder factory.
	 *
	 * @package SQL::Query::Builder
	 * @author James Stephenson
	 **/
	final class Sql_Query_Builder_Factory
	{	
		/**
		 * Private constructor - static factory.
		 *
		 * @author James Stephenson
		 **/
		private function __construct()
		{
		}
		
		/**
		 * Factory method to return a new query builder instance of the
		 * appropriate type.
		 *
		 * @return Sql_Query_Builder
		 * @author James Stephenson
		 **/
		public static function make($type)
		{
			$class = 'Sql_Query_Builder_' . ucfirst(strtolower($type));
			if (class_exists($class)) {
				return new $class;
			}
			throw new Exception('No query builder for driver: ' . $type);
		}
	} // END final class Sql_Query_Builder_Factory