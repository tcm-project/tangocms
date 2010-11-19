<?php

	/**
	 * A proxy class for the builders to allow internal context switching
	 * without breaking the interface.
	 *
	 * @package Sql::Query::Builder
	 * @author James Stephenson
	 **/
	class Sql_Query_Builder_Proxy
	{
		private $_context;
		
		/**
		 * Constructor. Populates initial context.
		 *
		 * @param Sql_Query_Builder_Abstract $context
		 * @author James Stephenson
		 **/
		public function __construct(Sql_Query_Builder_Abstract $context)
		{
			$this->_context = $context;
		}
		
		/**
		 * Proxy the build method, reassigning our internal context as
		 * necessary.
		 *
		 * @return void
		 * @author James Stephenson
		 **/
		public function build()
		{
			$this->_context = $this->_context->build();
		}
		
		/**
		 * Simply forward all other calls through to the context.
		 *
		 * @param string $method
		 * @param array $args
		 * @author James Stephenson
		 **/
		public function __call($method, array $args)
		{
			call_user_func_array(array($this->_context, $method), $args);
		}
		
	} // END class Sql_Query_Builder_Proxy