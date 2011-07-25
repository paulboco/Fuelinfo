<?php

/**
 * Fuelinfo Class
 *
 * Outputs information about Fuel.
 *
 * usage: Fuelinfo::all()  - to display all sections except phpinfo.
 *
 *        or for a specific section...
 *
 *        Fuelinfo::routes();
 *        Fuelinfo::request();
 *        Fuelinfo::modules();
 *        Fuelinfo::packages();
 *        Fuelinfo::database();
 *        Fuelinfo::session();
 *        Fuelinfo::config();
 *        Fuelinfo::phpinfo();
 *
 * @author  Paul Boco
 */

//namespace SpaceName;

class Fuelinfo {

	/**
	 * Output all info
	 *
	 * @return  void
	 */
	public static function all()
	{
 		self::paths();
 		self::routes();
 		self::request();
 		self::modules();
 		self::packages();
 		self::database();
 		self::session();
		self::config();
	}

	/**
	 * Output search paths info
	 *
	 * @return  void
	 */
	public static function paths()
	{
		// get all search paths
		$paths = \Fuel::get_paths();

		// begin output
		self::_block_open();
		self::_table_open('Search Paths');

		foreach ($paths as $path)
		{
			self::_table_row('td', array(array($path, 'value')));
		}

		self::_table_close();
		self::_block_close();
	}

	/**
	 * Output routes info
	 *
	 * @return  void
	 */
	public static function routes()
	{
		// get all 'routes.php' files in folders named 'config'
		// flip the array making the path the key
		$routes = array_flip(\Fuel::find_file('config', 'routes', '.php', true));

		if (empty($routes))
		{
			// routes are optional. Thx, WanWizard.
			$routes = array('%NOQUOTES%No routes found' => '%NOQUOTES%Routes are optional. Thx, WanWizard' );
		}
		else
		{
			// fetch contents of each route file
			// there must be a better way of doing this. $path => $void???
			foreach ($routes as $path => $void)
			{
				$routes[$path] = \Fuel::load($path);
			}
		}

		// begin output
		self::_block_open();
		self::_table_open('Routes');
		self::_table_row('th', array('File', 'Array'));

		foreach ($routes as $path => $data)
		{
			self::_table_row('td', array(array($path, 'key'), array($data, 'value')));
		}

		self::_table_close();
		self::_block_close();
	}

	/**
	 * Output request info
	 *
	 * @return  void
	 */
	public static function request()
	{
		// init
		$request = array();

		// get request objects
		$main = \Request::main();
		$active = \Request::active();

		// build an array for display
		foreach ($main as $key => $value)
		{
			$key = self::_remove_null_chars($key);

			if ($key == 'controller_instance')
			{
				$request[$key]['main']   = '%NOQUOTES%*RECURSION*';
				$request[$key]['active'] = '%NOQUOTES%*RECURSION*';
				continue;
			}

			$request[$key]['main']   = self::_clean_value($main->$key);
			$request[$key]['active'] = self::_clean_value($active->$key);
		}

		// begin output
		self::_block_open();
		self::_table_open('Request');
		self::_table_row('th', array('Property', 'Main', 'Active'));

		foreach ($request as $key => $value)
		{
			$key = self::_remove_null_chars($key);
			self::_table_row('td', array(array($key, 'key'), array($value['main'], 'value'), array($value['active'], 'value')));
		}

		self::_table_close();
		self::_block_close();
	}

	/**
	 * Output modules info
	 *
	 * @return  void
	 */
	public static function modules()
	{
		// init
		$store_paths = array();

		// get defined module paths
		$module_paths = array_flip(\Config::get('module_paths'));

		// process enabled module paths
		if (empty($module_paths))
		{
			$module_paths = array('no module paths defined' => array(''));
		}
		else
		{
			// there must be a better way of doing this
			// value is named $void because it's not used
			foreach ($module_paths as $path => $void)
			{
				// check that current module path exists and is a directory
				if (file_exists($path) and is_dir($path))
				{
					// get modules from current path
					$modules = array_keys(File::read_dir($path, 1));

					if (empty($modules))
					{
						// no directories exist in current module path
						$module_paths[$path] = array('%NOQUOTES%directory is empty');
					}
					else
					{
						// store valid paths for later use
						$store_paths = array_merge($store_paths, $modules);
						$module_paths[$path] = $modules;
					}
				}
				else
				{
					// current module path not found
					$module_paths[$path] = array('%NOQUOTES%directory not found');
				}
			}
		}

		// get always loaded paths
		$always_load = array_flip(\Config::get('always_load.modules'));

		if (empty($always_load))
		{
			$always_load['--'] = '%NOQUOTES%none';
		}
		else
		{
			foreach ($always_load as $path => $void)
			{
				// check that the always loaded module exists
				if (in_array($path, $store_paths))
				{
					$always_load[$path] = '%NOQUOTES%ok';
				}
				else
				{
					$always_load[$path] = '%NOQUOTES%module not found';
				}
			}
		}

		// begin output
		self::_block_open();
		self::_table_open('Modules');
		self::_table_row('th', array('Module Paths', 'Modules Found'));

		foreach ($module_paths as $module_path => $modules)
		{
			self::_table_row(
				'td',
				array(
					array($module_path, 'key'),
					array('%NOQUOTES%'.implode('<br>', $modules), 'value'),
				)
			);
		}

		self::_table_close();

		// output the html table for always loaded modules
		self::_table_open();
		self::_table_row('th', array(array('Always Loaded', null, 2)));

		foreach ($always_load as $module => $exists)
		{
			self::_table_row('td', array(array($module, 'key'), array($exists, 'value')));
		}

		self::_table_close();
		self::_block_close();
	}

	/**
	 * Output packages info
	 *
	 * @return  void
	 */
	public static function packages()
	{
		// get always loaded packages
		$always_load = array_flip(\Config::get('always_load.packages'));

		if (empty($always_load))
		{
			$always_load['--'] = '%NOQUOTES%none';
		}
		else
		{
			foreach ($always_load as $path => $void)
			{
				// check that the always loaded package exists
				if (file_exists(PKGPATH.$path) and is_dir(PKGPATH.$path))
				{
					if (file_exists(PKGPATH.$path.DS.'bootstrap.php'))
					{
						$always_load[$path] = '%NOQUOTES%ok';
					}
					else
					{
						$always_load[$path] = '%NOQUOTES%bootstrap.php not found';
					}
				}
				else
				{
					$always_load[$path] = '%NOQUOTES%package not found';
				}
			}
		}

		// get packages from current path
		$packages = array_keys(File::read_dir(PKGPATH, 1));

		// begin output
		self::_block_open();
		self::_table_open('Packages');

		self::_table_row('th', 'Packages Available');
		self::_table_row('td', array(array('%NOQUOTES%'.implode('<br>', $packages), 'value')));

		self::_table_close();

		// output the html table for packages found
		self::_table_open();
		self::_table_row('th', array(array('Always Loaded', null, 2)));

		foreach ($always_load as $package => $exists)
		{
			self::_table_row('td', array(array($package, 'key'), array($exists, 'value')));
		}

		self::_table_close();
		self::_block_close();
	}

	/**
	 * Output database info
	 *
	 * @return  void
	 */
	public static function database()
	{
		// load database config
		\Config::load('db', true);

		// issue error msg if db config not found
		if ( ! \Config::get('db'))
		{
			$msg  = '%NOQUOTES%';
			$msg .= 'Fuel database configuration file not found. Check that database ';
			$msg .= 'configuration file <code>APPPATH'.DS.'config'.DS.'db.php</code> ';
			$msg .= 'exists and is properly formatted. See ';
			$msg .= \Html::anchor(
				'http://fuelphp.com/docs/classes/database/introduction.html',
				'Fuel documentation',
				array('target' => '_blank')
			);

			// begin output
			self::_block_open();
			self::_table_open('Database');
			self::_table_row('td', array(array($msg, 'error')));
			self::_table_close();
			self::_block_close();

			return;
		}

		$tables =  '%NOQUOTES%'.implode('<br>', \DB::list_tables());

		$database = \Config::get('db');
		$active = $database['active'];
		$current = array($active => $database[$active], 'tables' => $tables);
		unset($database['active']);
		unset($database[$active]);

		// begin output
		self::_block_open();
		self::_table_open('Database');
		self::_table_row('th', array(array('Active Configuration', null, 2)));

		foreach ($current as $key => $value)
		{
			$key = self::_remove_null_chars($key);
			self::_table_row('td', array(array($key, 'key'), array($value, 'value')));
		}
		self::_table_close();

		self::_table_open();
		self::_table_row('th', array(array('Inactive Configurations', null, 2)));

		foreach ($database as $key => $value)
		{
			$key = self::_remove_null_chars($key);
			self::_table_row('td', array(array($key, 'key'), array($value, 'value')));
		}

		self::_table_close();
		self::_block_close();
	}

	/**
	 * Output session data
	 *
	 * @return  void
	 */
	public static function session()
	{
		// get all session instance
		$session = (array) \Session::instance();

		// begin output
		self::_block_open();
		self::_table_open('Session');

		if (is_array($session))
		{
			foreach ($session as $key => $value)
			{
				$key = self::_remove_null_chars($key);
				self::_table_row('th', array(array($key, null, 2)));

				if (is_array($value))
				{
					if (empty($value))
					{
						self::_table_row('td', array(array('%NOQUOTES%array()', 'value', 2)));
					}
					else
					{
						foreach ($value as $k => $v)
						{
							$k = self::_remove_null_chars($k);
							$v = self::_clean_value($v);
							self::_table_row('td', array(array($k, 'key'), array($v, 'value')));
						}
					}
				}
				else
				{
					$value = self::_clean_value($value);
					self::_table_row('td', array(array($value, 'value', 2)));
				}
			}
		}
		else
		{
			self::_table_row('td', array(array('%NOQUOTES%No session data found', 'error')));
		}

		self::_table_close();
		self::_block_close();
	}

	/**
	 * Output config info
	 *
	 * @return  void
	 */
	public static function config()
	{
		// get configuration
		$configs = self::get_configs();

		if ( ! $configs)
		{
			$msg  = '%NOQUOTES%';
			$msg .= 'No configuration files found! Check that the default ';
			$msg .= 'configuration file <code>APPPATH'.DS.'config'.DS.'config.php</code> ';
			$msg .= 'exists and is properly formatted. See ';
			$msg .= \Html::anchor(
				'http://fuelphp.com/docs/classes/config.html',
				'Fuel documentation',
				array('target' => '_blank')
			);

			// begin output
			self::_block_open();
			self::_table_open('Configuration');
			self::_table_row('td', array(array($msg, 'error')));
			self::_table_close();
			self::_block_close();

			return;
		}

		// begin output
		self::_block_open();
		echo \Html::h('Configuration', 1, array('style' => self::$_styles['h1'])).PHP_EOL;

		// loop through each path
		foreach ($configs as $path => $files)
		{
			// display path as H2
			echo \Html::h($path, 2, array('style' => self::$_styles['h2'])).PHP_EOL;

			foreach ($files as $file => $content)
			{
				// open table and display filename as header row
				self::_table_open();
				self::_table_row('th', array(array($file, null, 2)));

				if (empty($content))
				{
					self::_table_row('td', array(array('%NOQUOTES%array()', 'value', 2)));
				}
				else
				{
					foreach ($content as $key => $value)
					{
						$key = self::_remove_null_chars($key);
						self::_table_row('td', array(array($key, 'key'), array($value, 'value')));
					}
				}

				self::_table_close();
			}
		}

		self::_block_close();
	}

	/**
	 * Returns an array of all files in directories named 'config'. Each top-most
	 * element is structured as follows:
	 *     'path' => array(
	 *         'filename' => array(
	 *             contents of file...
	 *         )
	 *     );
	 *
	 * @return  array  returns array of configuration data
	 */
	public static function get_configs()
	{
		// get application path
		$apppath = dirname(APPPATH);

		// get list of all files in folders named 'config'
		$files = \Fuel::list_files('config');

		if (empty($files))
		{
			return false;
		}

		// create array of configuration files
		foreach ($files as $file)
		{
			$path = ltrim(str_replace($apppath, '', dirname($file)), DS);
			$path = dirname($file);
			$dir = basename($file);
			$configs[$path][$dir] = \Fuel::load($file);
		}

		// reverse order of array
		$configs = array_reverse($configs);

		return $configs;
	}

	/**
	 * Create an html table of key => value pairs recursively.
	 *
	 * @param   array   the array to build html table(s) from
	 * @return  string  the html produced
	 */
	public static function array_htmltable_recursive($var)
	{
		if (is_array($var))
		{
			$html = PHP_EOL.'<table style="'.self::$_styles['inner']['table'].'">'.PHP_EOL;

			foreach ($var as $key => $value)
			{
				$key = self::_remove_null_chars($key);

				if (is_object($value))
				{
					$value = (array) $value;
				}

				if (is_array($value))
				{
					if (empty($value))
					{
						$html .= '<tr><td style="'.self::$_styles['inner']['key'].'">'.$key.'</td><td style="'.self::$_styles['inner']['value'].'">array()</td></tr>'.PHP_EOL;
					}
					else
					{
						$html .= '<tr><td style="'.self::$_styles['inner']['key'].'">'.$key.'</td><td style="'.self::$_styles['inner']['value'].'">'.self::array_htmltable_recursive($value).'</td></tr>'.PHP_EOL;
					}
				}
				else
				{
					$html .= '<tr><td style="'.self::$_styles['inner']['key'].'">'.$key.'</td><td style="'.self::$_styles['inner']['value'].'">'.var_export($value, 1).'</td></tr>'.PHP_EOL;
				}
			}

			$html.= '</table>'.PHP_EOL;
		}
		else
		{
			$html = var_export($var, 1);
		}

		return $html;
	}

	/**
	 * Output opening div tag
	 *
	 * @return  void
	 */
	private static function _block_open()
	{
		echo '<div style="'.self::$_styles['div'].'">'.PHP_EOL;
	}

	/**
	 * Output closing div tag
	 *
	 * @return  void
	 */
	private static function _block_close()
	{
		echo '</div>'.PHP_EOL;
	}

	/**
	 * Output opening html table tag
	 *
	 * @return  void
	 */
	private static function _table_open($heading = false, $level = '1')
	{
		if ($heading)
		{
			echo \Html::h($heading, $level, array('style' => self::$_styles['h'.$level])).PHP_EOL;
		}

		echo '<table style="'.self::$_styles['table'].'">'.PHP_EOL;
	}

	/**
	 * Output closing html table tag
	 *
	 * @return  void
	 */
	private static function _table_close()
	{
		echo '</table>'.PHP_EOL;
	}

	/**
	 * Output html table row
	 *
	 * @param   string|array   string for single label or array of class=>label pairs
	 * @param   integer        number of columns to span
	 * @return  void
	 */
	private static function _table_row($tag, $columns)
	{
		echo '<tr>'.PHP_EOL;;

		if (is_array($columns))
		{
			foreach ($columns as $column)
			{
				if (is_array($column))
				{
					// set colspan if present
					$colspan = '';
					if (isset($column[2]) and $column[2])
					{
						$colspan = ' colspan="'.$column[2].'"';
					}

					// set style if present
					$style = '';
					if (isset($column[1]) and $column[1])
					{
						$style = ' style="'.self::$_styles[$tag][$column[1]].'"';
					}
					else
					{
						$style = ' style="'.self::$_styles[$tag].'"';
					}

					if (is_array($column[0]))
					{
						if (empty($column[0]))
						{
							echo '<'.$tag.$colspan.$style.'>array()</'.$tag.'>'.PHP_EOL;;
						}
						else
						{
							echo '<'.$tag.$colspan.$style.'>'.self::array_htmltable_recursive($column[0]).'</'.$tag.'>'.PHP_EOL;;
						}
					}
					else
					{
						if ($tag == 'th' or $column[1] == 'key' or strpos($column[0], '%NOQUOTES%') === 0)
						{
							// display plain text if tag is 'th'
							// or column[1]'s value is 'key'
							// or %NOQUOTES%' is found in string
							$column[0] = str_replace('%NOQUOTES%', '', $column[0]);
							echo '<'.$tag.$colspan.$style.'>'.$column[0].'</'.$tag.'>'.PHP_EOL;;
						}
						else
						{
							// else var_export the value
							echo '<'.$tag.$colspan.$style.'>'.var_export($column[0], 1).'</'.$tag.'>'.PHP_EOL;;
						}
					}
				}
				else
				{
					echo '<'.$tag.' style="'.self::$_styles[$tag].'">'.$column.'</'.$tag.'>'.PHP_EOL;;
				}
			}
		}
		else
		{
			echo '<'.$tag.' style="'.self::$_styles[$tag].'">'.$columns.'</'.$tag.'>'.PHP_EOL;;
		}

		echo '</tr>'.PHP_EOL;
	}

	/**
	 * Clean value
	 *
	 * Casts an object to an array.
	 * Casts an string to an array.
	 *
	 * @param   mixed   the var to check
	 * @return  array   the cleansed array
	 */
	private static function _clean_value($var)
	{
		if (is_object($var))
		{
			return (array) $var;
		}

		if (is_array($var) and empty($var))
		{
			return '%NOQUOTES%array()';
		}

		return $var;
	}

	/**
	 * Remove null characters
	 *
	 * Removes null "\0" characters from a string.
	 *
	 * @param   string   the string to clean
	 * @return  string   the cleansed string
	 */
	public static function _remove_null_chars($string)
	{
		$string = str_replace("\0", '', $string);
		return $string;
	}

	/**
	 * @var  array  CSS inline styles
	 */
	private static $_styles = array(
		'div' => 'text-align:left; margin:10px; font-family:Helvetica, Arial, sans-serif; font-size:small; color:#000;',
		'table' => 'border-collapse:collapse; text-align:left; margin-bottom:10px;',
		'th' => 'border:1px solid; padding:7px 7px; background-color:#D788FF; font-weight:bold; text-align:center;',
		'td' => array(
			'key'   => 'border:1px solid; padding:5px 7px; background-color:#EAACFF; font-weight:bold;',
			'value' => 'border:1px solid; padding:5px 7px; background-color:#CCC;',
			'error' => 'border:1px solid; padding:5px 7px; background-color:#FFA; width:400px;',
		),
		'inner' => array(
			'table' => 'border-collapse: collapse; margin:0;',
			'key'   => 'border:solid 1px #888; padding:5px 7px; background-color:#E4D4EA; font-weight:bold;',
			'value' => 'border:solid 1px #888; padding:5px 7px; white-space:wrap;',
		),
		'h1' => 'font-size:x-large; margin:0; padding:40px 0 10px;',
		'h2' => 'font-size:large; margin:0; padding:20px 0 10px;',
	);

}

/* End of file fuelinfo.php */