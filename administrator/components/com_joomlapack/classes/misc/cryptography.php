<?php
defined('_JEXEC') or die('Restricted access');

/************************************************************************/
/* Cryptography Class: Fiestel Network S-Block                          */
/* Encryption Algorithm using SHA1 on password                          */
/* ============================================                         */
/*                                                                      */
/* Copyright (c) 2005 by Chipgraphics llc                               */
/* http://www.chipgraphics.net                                          */
/*                                                                      */
/* This program is free software. You can redistribute it and/or modify */
/* it under the terms of the GNU General Public License as published by */
/* the Free Software Foundation; either version 2 of the License.       */
/*                                                                      */
/************************************************************************/
/* Additional information, comments, or concerns please contact         */
/* admin@chipgraphics.net -or- http://www.chipgraphics.net              */
/************************************************************************/

/************************************************************************/
/*                                                                      */
/* Cryptography Class:    Example usage                                 */
/* ============================================                         */
/*                                                                      */
/* $example = new cryptography;                                         */
/* $example->set_key("1234abcd");                                       */
/*                                                                      */
/* $string = "Some text to encrypt";                                    */
/*                                                                      */
/* $encrypted = $example->encrypt($string);                             */
/*                                                                      */
/* [*] $encrypted is base64 encoded making it safe to store in          */
/*  a cookie or a session variable without compromise of data.          */
/*                                                                      */
/*                                                                      */
/* $decrypted = $example->decrypt($encrypted);                          */
/* echo $decrypted; // outputs "Some text to encrypt";                  */
/*                                                                      */
/*                                                                      */
/************************************************************************/

class cryptography {
	 
	var $password;


	 
	function set_key($password) {
		$this->password = $password;
	}
	 
	function get_rnd_iv($iv_len) {
		$iv = '';
		while ($iv_len-- > 0) {
			$iv .= chr(mt_rand() & 0xff);
		}
		return $iv;
	}
	 
	function encrypt($plain_text, $iv_len = 16) {
		$plain_text .= "\x13";
		$n = strlen($plain_text);
		if ($n % 16) $plain_text .= str_repeat("\0", 16 - ($n % 16));
		$i = 0;
		$enc_text = cryptography::get_rnd_iv($iv_len);
		$iv = substr($this->password ^ $enc_text, 0, 512);
		while ($i < $n) {
			$block = substr($plain_text, $i, 16) ^ pack('H*', sha1($iv));
			$enc_text .= $block;
			$iv = substr($block . $iv, 0, 512) ^ $this->password;
			$i += 16;
		}
		return base64_encode($enc_text);
	}
	 
	function decrypt($enc_text, $iv_len = 16) {
		$enc_text = base64_decode($enc_text);
		$n = strlen($enc_text);
		$i = $iv_len;
		$plain_text = '';
		$iv = substr($this->password ^ substr($enc_text, 0, $iv_len), 0, 512);
		while ($i < $n) {
			$block = substr($enc_text, $i, 16);
			$plain_text .= $block ^ pack('H*', sha1($iv));
			$iv = substr($block . $iv, 0, 512) ^ $this->password;
			$i += 16;
		}
		return stripslashes(preg_replace('/\\x13\\x00*$/', '', $plain_text));
	}

}
?>