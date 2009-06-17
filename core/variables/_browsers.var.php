<?php
/**
 * Balupton's Resource Library (balPHP)
 * Copyright (C) 2008 Benjamin Arthur Lupton
 * http://www.balupton.com/
 *
 * This file is part of Balupton's Resource Library (balPHP).
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with Balupton's Resource Library (balPHP).  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package balphp
 * @subpackage core
 * @version 0.1.0-final, April 21, 2008
 * @since 0.1.0-final, April 21, 2008
 * @author Benjamin "balupton" Lupton <contact@balupton.com> - {@link http://www.balupton.com/}
 * @copyright Copyright (c) 2008, Benjamin Arthur Lupton - {@link http://www.balupton.com/}
 * @license http://www.gnu.org/licenses/agpl.html GNU Affero General Public License
 */

	$GLOBALS['browsers'] = array(
		'IE' => false,
		'FF' => false,
		'O' => false,
		'Other' => false
	);
	
	global $browsers;
	
	$browsers['browser'] = $h_u_a = !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'MSIE';
	
	if ( strstr($h_u_a,'Firefox') )
		$browsers['FF']	= ( strstr($h_u_a,'Firefox/2') )	? 2 : 1;
	elseif ( strstr($h_u_a,'Opera') )
		$browsers['O']	= ( strstr($h_u_a,'Opera/9') )		? 9 : 8;
	elseif( strstr($h_u_a,'MSIE') )
		$browsers['IE']	= ( strstr($h_u_a,'MSIE 7') )	? 7 : 6;
	else
		$browsers['Other'] = true;

	unset($h_u_a);
	
 ?>