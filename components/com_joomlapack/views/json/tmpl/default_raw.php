<?php
/**
 * @package JoomlaPack
 * @copyright Copyright (c)2006-2009 JoomlaPack Developers
 * @license GNU General Public License version 2, or later
 * @version $id$
 * @since 1.3
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

if(!class_exists('Services_JSON'))
{
	/**
	 * Trimmed-down JSON encoder, based on PHP-JSON
	 */
	class Services_JSON
	{
		/**
		 * convert a string from one UTF-8 char to one UTF-16 char
		 *
		 * Normally should be handled by mb_convert_encoding, but
		 * provides a slower PHP-only method for installations
		 * that lack the multibye string extension.
		 *
		 * @param    string  $utf8   UTF-8 character
		 * @return   string  UTF-16 character
		 * @access   private
		 */
		function utf82utf16($utf8)
		{
			// oh please oh please oh please oh please oh please
			if(function_exists('mb_convert_encoding')) {
				return mb_convert_encoding($utf8, 'UTF-16', 'UTF-8');
			}
	
			switch(strlen($utf8)) {
				case 1:
					// this case should never be reached, because we are in ASCII range
					// see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
					return $utf8;
	
				case 2:
					// return a UTF-16 character from a 2-byte UTF-8 char
					// see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
					return chr(0x07 & (ord($utf8{0}) >> 2))
					. chr((0xC0 & (ord($utf8{0}) << 6))
					| (0x3F & ord($utf8{1})));
	
				case 3:
					// return a UTF-16 character from a 3-byte UTF-8 char
					// see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
					return chr((0xF0 & (ord($utf8{0}) << 4))
					| (0x0F & (ord($utf8{1}) >> 2)))
					. chr((0xC0 & (ord($utf8{1}) << 6))
					| (0x7F & ord($utf8{2})));
			}
	
			// ignoring UTF-32 for now, sorry
			return '';
		}
	
		/**
		 * encodes an arbitrary variable into JSON format
		 *
		 * @param    mixed   $var    any number, boolean, string, array, or object to be encoded.
		 *                           see argument 1 to Services_JSON() above for array-parsing behavior.
		 *                           if var is a strng, note that encode() always expects it
		 *                           to be in ASCII or UTF-8 format!
		 *
		 * @return   mixed   JSON string representation of input var or an error if a problem occurs
		 * @access   public
		 */
		function encode($var)
		{
			switch (gettype($var)) {
				case 'boolean':
					return $var ? 'true' : 'false';
	
				case 'NULL':
					return 'null';
	
				case 'integer':
					return (int) $var;
	
				case 'double':
				case 'float':
					return (float) $var;
	
				case 'string':
					// STRINGS ARE EXPECTED TO BE IN ASCII OR UTF-8 FORMAT
					$ascii = '';
					$strlen_var = strlen($var);
	
					/*
					 * Iterate over every character in the string,
					 * escaping with a slash or encoding to UTF-8 where necessary
					 */
					for ($c = 0; $c < $strlen_var; ++$c) {
	
						$ord_var_c = ord($var{$c});
	
						switch (true) {
							case $ord_var_c == 0x08:
								$ascii .= '\b';
								break;
							case $ord_var_c == 0x09:
								$ascii .= '\t';
								break;
							case $ord_var_c == 0x0A:
								$ascii .= '\n';
								break;
							case $ord_var_c == 0x0C:
								$ascii .= '\f';
								break;
							case $ord_var_c == 0x0D:
								$ascii .= '\r';
								break;
	
							case $ord_var_c == 0x22:
							case $ord_var_c == 0x2F:
							case $ord_var_c == 0x5C:
								// double quote, slash, slosh
								$ascii .= '\\'.$var{$c};
								break;
	
							case (($ord_var_c >= 0x20) && ($ord_var_c <= 0x7F)):
								// characters U-00000000 - U-0000007F (same as ASCII)
								$ascii .= $var{$c};
								break;
	
							case (($ord_var_c & 0xE0) == 0xC0):
								// characters U-00000080 - U-000007FF, mask 110XXXXX
								// see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
								$char = pack('C*', $ord_var_c, ord($var{$c + 1}));
								$c += 1;
								$utf16 = $this->utf82utf16($char);
								$ascii .= sprintf('\u%04s', bin2hex($utf16));
								break;
	
							case (($ord_var_c & 0xF0) == 0xE0):
								// characters U-00000800 - U-0000FFFF, mask 1110XXXX
								// see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
								$char = pack('C*', $ord_var_c,
								ord($var{$c + 1}),
								ord($var{$c + 2}));
								$c += 2;
								$utf16 = $this->utf82utf16($char);
								$ascii .= sprintf('\u%04s', bin2hex($utf16));
								break;
	
							case (($ord_var_c & 0xF8) == 0xF0):
								// characters U-00010000 - U-001FFFFF, mask 11110XXX
								// see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
								$char = pack('C*', $ord_var_c,
								ord($var{$c + 1}),
								ord($var{$c + 2}),
								ord($var{$c + 3}));
								$c += 3;
								$utf16 = $this->utf82utf16($char);
								$ascii .= sprintf('\u%04s', bin2hex($utf16));
								break;
	
							case (($ord_var_c & 0xFC) == 0xF8):
								// characters U-00200000 - U-03FFFFFF, mask 111110XX
								// see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
								$char = pack('C*', $ord_var_c,
								ord($var{$c + 1}),
								ord($var{$c + 2}),
								ord($var{$c + 3}),
								ord($var{$c + 4}));
								$c += 4;
								$utf16 = $this->utf82utf16($char);
								$ascii .= sprintf('\u%04s', bin2hex($utf16));
								break;
	
							case (($ord_var_c & 0xFE) == 0xFC):
								// characters U-04000000 - U-7FFFFFFF, mask 1111110X
								// see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
								$char = pack('C*', $ord_var_c,
								ord($var{$c + 1}),
								ord($var{$c + 2}),
								ord($var{$c + 3}),
								ord($var{$c + 4}),
								ord($var{$c + 5}));
								$c += 5;
								$utf16 = $this->utf82utf16($char);
								$ascii .= sprintf('\u%04s', bin2hex($utf16));
								break;
						}
					}
	
					return '"'.$ascii.'"';
	
							case 'array':
								/*
								 * As per JSON spec if any array key is not an integer
								 * we must treat the the whole array as an object. We
								 * also try to catch a sparsely populated associative
								 * array with numeric keys here because some JS engines
								 * will create an array with empty indexes up to
								 * max_index which can cause memory issues and because
								 * the keys, which may be relevant, will be remapped
								 * otherwise.
								 *
								 * As per the ECMA and JSON specification an object may
								 * have any string as a property. Unfortunately due to
								 * a hole in the ECMA specification if the key is a
								 * ECMA reserved word or starts with a digit the
								 * parameter is only accessible using ECMAScript's
								 * bracket notation.
								 */
	
								// treat as a JSON object
								if (is_array($var) && count($var) && (array_keys($var) !== range(0, sizeof($var) - 1))) {
									$properties = array_map(array($this, 'name_value'),
									array_keys($var),
									array_values($var));
	
									foreach($properties as $property) {
										if(Services_JSON::isError($property)) {
											return $property;
										}
									}
	
									return '{' . join(',', $properties) . '}';
								}
	
								// treat it like a regular array
								$elements = array_map(array($this, 'encode'), $var);
	
								foreach($elements as $element) {
									if(Services_JSON::isError($element)) {
										return $element;
									}
								}
	
								return '[' . join(',', $elements) . ']';
	
							case 'object':
								$vars = get_object_vars($var);
	
								$properties = array_map(array($this, 'name_value'),
								array_keys($vars),
								array_values($vars));
	
								foreach($properties as $property) {
									if(Services_JSON::isError($property)) {
										return $property;
									}
								}
	
								return '{' . join(',', $properties) . '}';
	
							default:
								return 'null';
			}
		}
	
		/**
		 * array-walking function for use in generating JSON-formatted name-value pairs
		 *
		 * @param    string  $name   name of key to use
		 * @param    mixed   $value  reference to an array element to be encoded
		 *
		 * @return   string  JSON-formatted name-value pair, like '"name":value'
		 * @access   private
		 */
		function name_value($name, $value)
		{
			$encoded_value = $this->encode($value);
	
			if(Services_JSON::isError($encoded_value)) {
				return $encoded_value;
			}
	
			return $this->encode(strval($name)) . ':' . $encoded_value;
		}
	
		function isError($data, $code = null)
		{
			if (is_object($data) && (get_class($data) == 'services_json_error' ||
			is_subclass_of($data, 'services_json_error'))) {
				return true;
			}
	
			return false;
		}
	}
}

if(!class_exists('Services_JSON_Error'))
{
	class Services_JSON_Error
	{
		function Services_JSON_Error($message = 'unknown error', $code = null,
		$mode = null, $options = null, $userinfo = null)
		{
		}
	}
}

function to_json($var)
{
	// Prefer native PHP 5.2.1+ JSON encoder (uber-fast!)...
	if(function_exists('json_encode'))
	{
		return json_encode($var);
	}
	else
	// ...or fall back to PEAR JSON encoder (PHP-JSON)
	{
		$encoder = new Services_JSON();
		return $encoder->encode($var);
	}
}

$task = JRequest::getCmd('task','');

switch($task)
{
	case 'getdirectory':
		// Return the output directory in JSON format
		$registry =& JoomlapackModelRegistry::getInstance();
		$outdir = $registry->get('OutputDirectory');
		// # Fix 2.4: Drop the output buffer
		if(function_exists('ob_clean')) @ob_clean();
		echo to_json($outdir);
		break;
		
	default:
		// Return the CUBE array in JSON format
		$cube =& JoomlapackCUBE::getInstance();
		$array = $cube->getCUBEArray();
		// # Fix 2.4: Drop the output buffer
		if(function_exists('ob_clean')) @ob_clean();
		echo to_json($array);
		break;
}

# Fix 2.4: Die the script in order to avoid misbehaving modules from ruining the output
die();
