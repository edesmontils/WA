<?php
abstract class Singleton {
	private static $_instances = Array();
	protected function __construct(){}
	protected static function getInstance($instance_id = NULL) {
		if (!isset(self::$_instances[$instance_id])){
			if (!class_exists($instance_id)) {
				echo "-----> Echec Singleton::getIntance($instance_id) !";
				return NULL;
			} else {
				self::$_instances[$instance_id] = new $instance_id();
				return self::$_instances[$instance_id];
			}
		} else return self::$_instances[$instance_id];
	}
}

?>