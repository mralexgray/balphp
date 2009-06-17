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

require_once(dirname(__FILE__).'/_general.funcs.php');
require_once(dirname(__FILE__).'/_scan_dir.funcs.php');

if ( !function_exists('file_put_contents') && function_compare('file_put_contents', 1, true, __FILE__, __LINE__) )
{	
	define('FILE_APPEND', 0);
	
	/**
	 * If file_put_contents doesn't exist, make it (PHP4)
	 * 
	 * @version 1, July 23, 2006
	 * 
	 * @author egingell at sisna dot com
	 * 
	 * @param string $filename	 
	 * @param mixed $data
	 * @param mixed $flags
	 * 
	 * @return mixed|NULL needle, or nothing
	 */
	function file_put_contents($filename, $data, $flags = false)
	{
		$mode = ($flags == FILE_APPEND || strtoupper($flags) == 'FILE_APPEND') ? 'a' : 'w';
		$file_handle = @fopen($filename, $mode);
		if ($file_handle === false) {
			return 0;
		} else {
			if (is_array($data)) $data = implode($data);
			$bytes_written = fwrite($file_handle, $data);
			fclose($file_handle);
			return $bytes_written;
		}
	}
}

if( function_compare( 'become_file_download', 1, true, __FILE__, __LINE__ ) )
{	/**
	 * Become a file download, should be the last script that runs in your program
	 * 
	 * http://tools.ietf.org/id/draft-ietf-http-range-retrieval-00.txt
	 * 
	 * @version 2, August 11, 2007
	 * 
	 * @author Benjamin "balupton" Lupton <contact@balupton.com> - {@link http://www.balupton.com/}
	 * 
	 * @param string	$file_path	 
	 * @param string	$content_type
	 * @param int		$buffer_size
	 * 
	 * @return boolean	true on success, false on error
	 */
	function become_file_download ( $file_path, $content_type = NULL, $buffer_size = 4096 )
	{	
		// Check and open file
		if ( file_exists($file_path) && $file_descriptor = fopen($file_path, 'rb') )
		{	// We have a file that we can open
			
			// Define variables
			$file_name 		= basename($file_path);
			$file_size 		= filesize($file_path);
			$file_time 		= filemtime($file_path);
			
			$etag  			= md5($file_time.$file_name);
			$date			= gmdate('D, d M Y H:i:s').' GMT';
			$expires		= gmdate('D, d M Y H:i:s', strtotime('+1 month')).' GMT';
			$last_modified	= gmdate('D, d M Y H:i:s', $file_time).' GMT';
			
			if ( empty($content_type) )
				$content_type = 'application/force-download';
			
			// Say we can go on forever
			set_time_limit(0);
			
			// Check relevance
			$etag_relevant = !empty($_SERVER['HTTP_IF_NONE_MATCH']) && trim(stripslashes($_SERVER['HTTP_IF_NONE_MATCH']), '\'"') === $etag;
			$date_relevant = !empty($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $file_time;
			
			// Handle download
			if ( $etag_relevant || $date_relevant )
			{	// Not modified
				header('HTTP/1.0 304 Not Modified');
				header('Status: 304 Not Modified');
				
				header('Pragma: public');
				header('Cache-Control: private');
					
				header('ETag: "'.				$etag.'"');
				header('Date: '.				$date);
				header('Expires: '.				$expires);
				header('Last-modified: '.		$last_modified);
				return true;
			}
			elseif ( !empty($_SERVER['HTTP_RANGE']) )
			{	// Partial download
				
				/* 
				 * bytes=0-99,500-1499,4000-
				 */ 
				
				// Explode RANGE
				list($size_unit, $ranges) = explode($_SERVER['HTTP_RANGE'], '=', 2);
				
				// Explode RANGES
				$ranges = explode(',', $ranges);
				
				// Cycle through ranges
				foreach ( $ranges as $range )
				{	// We have a range
					
					/* 
					 * All bytes until the end of document, except for the first 500 bytes:
					 * Content-Range: bytes 500-1233/1234
					 */
					
					// Set range start
					$range_start;
					if ( !empty($range[0]) && is_numeric($range[0]) )
					{	// The range has a start
						$range_start = intval($range[0]);
					} else
						$range_start = 0;
					
					// Set range end
					if ( !empty($range[1]) && is_numeric($range[1]) )
					{	// The range has an end
						$range_end = intval($range[1]);
					} else
						$range_end = $file_size - 1;
					
					// Set the range size
					$range_size = $range_end - $range_start + 1;
					
					// Set the headers
					header('HTTP/1.1 206 Partial Content');
					
					header('Pragma: public');
					header('Cache-Control: private');
					
					header('ETag: "'.$etag.'"');
					header('Date: '.				$date);
					header('Expires: '.				$expires);
					header('Last-modified: '.		$last_modified);
					
					header('Content-Transfer-Encoding: binary');
					header('Accept-Ranges: bytes');
					
					header('Content-Range: bytes '.	$range_start.'-'.$range_end.'/'.$file_size);
					header('Content-Length: '.		$range_size);
					
					header('Content-Type: '.		$content_type);
					if ( $content_type === 'application/force-download' )
						header('Content-Disposition: attachment; filename='.$file_name);
					
					// Seek to our location
					fseek($file_descriptor, $range_start);
					
					// Read the file
					$remaining = $range_size;
					while ( $remaining > 0 )
					{
						// 0-6   | buffer = 3 | remaining = 7
						// 0,1,2 | buffer = 3 | remaining = 4
						// 3,4,5 | buffer = 3 | remaining = 1
						// 6     | buffer = 1 | remaining = 0
						
						// Set buffer size
						$buffer_size = min($buffer_size, $remaining);
						
						// Output file contents
						echo fread($file_descriptor, $buffer_size);
						flush();
						ob_flush();
						
						// Update remaining
						$remaining -= $buffer_size;
					}
				}

			}
			else 
			{	// Usual download
			
				// header('Pragma: public');
				// header('Cache-control: must-revalidate, post-check=0, pre-check=0');
				// header('Expires: '.		gmdate('D, d M Y H:i:s').' GMT');
				
				// Set headers
				header('HTTP/1.1 200 OK');
				
				header('Pragma: public');
				header('Cache-Control: private');
				
				header('ETag: "'.$etag.'"');
				header('Date: '.				$date);
				header('Expires: '.				$expires);
				header('Last-modified: '.		$last_modified);
				
				header('Content-Transfer-Encoding: binary');
				header('Accept-Ranges: bytes');
				
				header('Content-Length: '.		$file_size);
				
				header('Content-Type: '.		$content_type);
				if ( $content_type === 'application/force-download' )
				  header('Content-Disposition: attachment; filename='.$file_name);
				
				// Read the file
				$file_descriptor = fopen($file_path, 'r');
				while ( !feof($file_descriptor) )
				{	// Output file contents
					echo fread($file_descriptor, $buffer_size);
					flush();
					ob_flush();
				}
			}
			
			// Close the file
			fclose($file_descriptor);
		}
		else
		{	// We couldn't find the file
			header('HTTP/1.1 404 Not Found');
			return false;
		}
		
		// Done
		return true;
	}
}

if ( !isset($GLOBALS['FILE_TYPES']) )
$GLOBALS['FILE_TYPES'] = array(
	'exe'	=> 'Application',
	'jpg'	=> 'Image',
	'jpeg'	=> 'Image',
	'png'	=> 'Image',
	'tiff'	=> 'Image',
	'bmp'	=> 'Image',
	'zip'	=> 'ZIP Archive',
	'rar'	=> 'WinRAR Archive',
	'pdf'	=> 'Adobe Reader Document',
	'doc'	=> 'Word Document',
	'txt'	=> 'Document',
	'rtf'	=> 'Rich Text File'
);

if( function_compare( 'filetype_human', 1.1, true, __FILE__, __LINE__ ) )
{	/**
	 * Convert a filetype to a human format
	 * 
	 * Uses mime type as well if the file exists and mime functions are available (if not 'File' is used)
	 * 
	 * @version 1.1, April 23, 2008
	 * 
	 * @param string	$file_path		path to the file
	 * @param string	$format			sprintf format
	 * 
	 * @return string
	 */
	function filetype_human ( $file_path, $format = '%s (.%s)'  )
	{	/*
		 * Changelog
		 * 
		 * version 1.1, April 23, 2008
		 * - added is_file check
		 * - now uses global
		 * 
		 * version 1, 2007
		 * - initial
		 */
		
		// Prepare
		global $FILE_TYPES;
		$extension = get_extension($file_path);
		
		// Default
		if ( function_exists('mime_content_type') && is_file($file_path) )
		{
			$mime_type = mime_content_type($file_path);
			$type = $mime_type;
		}
		else
		{
			$mime_type = false;
			$type = 'File';
		}
		
		// Do we know?
		if ( isset($FILE_TYPES[$mime_type]) )
		{
			$type = $FILE_TYPES[$mime_type];
			
		} elseif ( isset($FILE_TYPES[$extension]) )
		{
			$type = $FILE_TYPES[$extension];
		}
		
		// Return
		return sprintf($format, $type, $extension);
	}
}


if( function_compare( 'get_estimated_download_time', 1, true, __FILE__, __LINE__ ) )
{	/**
	 * Get the estimated download time, based on the size of the file and specified speed
	 * 
	 * @version 1, 2007
	 * 
	 * @param int		$size				size in bytes
	 * @param int		$speed				speed in bytes
	 * @param boplean	$round_seconds		don't care for seconds, just have minutes
	 * @param array		$formats			array of sprintf formats
	 * 
	 * @return string
	 */
	function get_estimated_download_time ( $size, $speed, $round_seconds = true, $formats = array() )
	{	
		if ( !isset($formats['format']) )
			$formats['format'] = '%1$s (Estimate based on %2$s)';
		if ( !isset($formats['hours']) )
			$formats['hours'] = '%s hour%s';
		if ( !isset($formats['minutes']) )
			$formats['minutes'] = '%s minute%s';
		if ( !isset($formats['seconds']) )
			$formats['seconds'] = '%s second%s';
		
		// Convert size into kilobytes
		$size /= 1000;
		
		// Convert speed into Kbps from Mbps
		if ( $speed < 56 )	// 1.5 = 1500
			$speed *= 100;
		
		// Convert speed into KBps
		$speed /= 8;
		
		// Set remaining size
		$remaining_size = $size;
		
		// Figure out the hours
		$max = $speed * 60 /* mins */ * 60 /* hours */;
		$hours = floor($remaining_size / $max);
		$remaining_size = $remaining_size % $max;
		
		// Figure out the minutes
		$max = $speed * 60 /* mins */;
		$minutes = floor($remaining_size / $max);
		$remaining_size = $remaining_size % $max;
		
		// Figure out the seconds
		$max = $speed /* seconds */;
		$seconds = ceil($remaining_size / $max);
		$remaining_size = 0;
		
		// Round it
		if ( $round_seconds )
		{
			if ( $seconds >= 30 )
				++$minutes;
			$seconds = 0;
		}
		
		// Times
		$times = array();
		if ( $hours )	$times['hours']		=  sprintf($formats['hours'], $hours, ($hours > 1 ? 's' : ''));
		if ( $minutes )	$times['minutes']	=  sprintf($formats['minutes'], $minutes, ($minutes > 1 ? 's' : ''));
		if ( $seconds )	$times['seconds']	=  sprintf($formats['seconds'], $seconds, ($seconds > 1 ? 's' : ''));
		$times = implode(', ', $times);
		if ( empty($times) )
			$times = 'Unknown';
		
		// Do display
		$result = 
			sprintf($formats['format'],	$times, ($speed*8).'Kbps');
		
		// Return
		return $result;
	}
}

if ( !isset($GLOBALS['FILESIZE_LEVELS']) )
$GLOBALS['FILESIZE_LEVELS'] = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');

if( function_compare( 'filesize_from_human', 1, true, __FILE__, __LINE__ ) )
{	/**
	 * Converts a filesize from human to bytes
	 * 
	 * @version 1, 2007
	 * 
	 * @param string	$filesize_human		"5.0 MB"
	 * 
	 * @return int bytes
	 */
	function filesize_from_human( $filesize_human )
	{
		$levels = $GLOBALS['FILESIZE_LEVELS'];
		
		$filesize_human = strtoupper($filesize_human);
		
		$matches = array();
		$matches_length = preg_match('/([a-zA-Z]*)$/', $filesize_human, $matches);
		if ( $matches_length === 1 && in_array(($match = $matches[0]), $levels))
			$level = $match;
		else
			$level = 'MB';
			
		$filesize = floatval(trim(substr($filesize_human, 0, strlen($filesize_human)-strlen($level))));
		
		$depth = array_search($level, $levels);
		++$depth; // B should be 1
		
		for ( $i = 0, $n = $depth-1; $i < $n; ++$i )
			$filesize *= 1024;
		
		return $filesize;
	}
}

if( function_compare( 'filesize_to_human', 1, true, __FILE__, __LINE__ ) )
{	/**
	 * Converts a filesize from human to bytes
	 * 
	 * @version 1.2, April 27, 2008
	 * 
	 * @param int	$size				bytes
	 * @param int	$decimal_places		how many decimal places in the result
	 * @param int	$round_up_after		round up after this value, so with 0.1 it turns 110 KB into 0.11 MB
	 * 
	 * @return string "5.0 MB"
	 */
	function filesize_to_human( $size, $decimal_places = 2, $round_up_after = 0.100 )
	{	
		
		$levels = $GLOBALS['FILESIZE_LEVELS'];
		
		if ( version_compare(phpversion(), '5.1', '<') )
		{	// For some crazy reason PHP4 adds a extra decimal
			--$decimal_places;
		}
		
		$level = 0;
		while ( ($new_size = $size / 1024) > $round_up_after )
		{
			$size = $new_size;
			++$level;
		}
		$filesize_human = substr($size,0,strpos($size,'.')+1+$decimal_places).' '.$levels[$level];
		return $filesize_human;
	}
	/*
	 * Changelog
	 *
	 * version 1.2, April 27, 2008
	 * - Fixed decimal place for php4
	 * 
	 * v1.1, April 24, 2008
	 * - Fixed decimal places
	 * 
	 * v1.2
	 * - added place_holder, should be 1 by default but client required 0.100
	 */
}

if( function_compare( 'filesize_human', 1, true, __FILE__, __LINE__ ) )
{	/**
	 * Alias for filesize_to_human
	 *
	 * @see filesize_to_human
	 */
	function filesize_human ( $size, $decimal_places = 3, $round_up_after = 0.100 )
	{
		return filesize_to_human ( $size, $decimal_places, $round_up_after );
	}
}

if( function_compare( 'dirsize', 1, true, __FILE__, __LINE__ ) )
{	/**
	 * Get the total size of a directory (filesize of all contents)
	 * 
	 * @version 1, April 24, 2008
	 * 
	 * @param string	$dir_path		directory location
	 * 
	 * @return int bytes
	 */
	function dirsize ( $dir_path )
	{	
		$dir_path = create_valid_path($dir_path);	
		$files = scan_dir($dir_path);
		$dirsize = 0;
		for ( $i = 0, $n = sizeof($files); $i < $n; ++$i )
		{
			$file_path = $dir_path.$files[$i];
			$filesize = filesize($file_path);
			$dirsize += $filesize;
		}
		return $dirsize;
	}
}

if( function_compare( 'get_filename', 2, true, __FILE__, __LINE__ ) )
{	/**
	 * Get filename of a file, with or without the extension
	 * 
	 * @version 2
	 * 
	 * @param string	$file				filename or path
	 * @param boolean	$with_extension		include the extension when returning
	 * 
	 * @return string
	 */
	function get_filename ( $file, $with_extension = true )
	{
		$file = basename($file);
		if ( !$with_extension )
		{
			$end = strrpos($file,'.');
			if ( $end !== false )
				$file = substr($file,0,$end);
		}
		return $file;
	}
}

if( function_compare( 'get_extension', 1, true, __FILE__, __LINE__ ) )
{	/**
	 * Get the extension of a file
	 * 
	 * @version 1
	 * 
	 * @param string	$file				filename or path
	 * 
	 * @return string
	 */
	function get_extension( $file )
	{
		$end = strrpos($file,'.');
		if ( $end !== false )
			return substr($file,$end+1);
		else
			return '';
	}
}

if( function_compare( 'create_valid_filename', 1, true, __FILE__, __LINE__ ) )
{	/**
	 * Return a valid filename from what was given
	 * 
	 * @version 1
	 * 
	 * @param string	$filename
	 * 
	 * @return string
	 */
	function create_valid_filename( $filename ) {
		return preg_replace( "/[^\w\.-]+/", '_', $filename );
	}
}

if( function_compare( 'create_valid_path', 2, true, __FILE__, __LINE__ ) )
{	/**
	 * Return a valid path
	 * 
	 * @version 2
	 * 
	 * @param string	$filename
	 * 
	 * @return string
	 */
	function create_valid_path ( $path )
	{	// Changes \\ to /, and adds a / to the end if it's not already not present
		
		// Convert slashes
		$path = str_replace( '\\', '/', $path );
		
		if( substr($path, strlen($path)-1) !== '/' )
		{	// Does not end with a /, should we add one?
			if( !strstr(basename($path),'.') || is_dir($path) )
				$path .= '/';
		}
		
		// Return
		return $path;
	}
}


if( function_compare( 'unlink_dir', 3, true, __FILE__, __LINE__ ) )
{	/**
	 * Unlink/Delete a directory/folder
	 * 
	 * @version 3.1, April 24, 2008
	 * 
	 * @param string	$dir_path
	 * 
	 * @return boolean
	 */
	function unlink_dir ( $dir_path )
	{	// Removes a directory and all subfiles, modded by balupton
		$dir_path = create_valid_path($dir_path);
		if ( !is_dir($dir_path) ) return true; // already deleted
		if ( $handle = opendir($dir_path) )
		{
			while ( false !== ($item = readdir($handle)) ) {
				switch( $item )
				{
					case '.':
					case '..':
						break;
					default:
						$c = $dir.$item;
						if ( is_dir($c) )
							unlink_dir($c);
						else
							unlink($c);
				}
			}
			closedir($handle);
			rmdir($dir);
		}
		return true;
	}
}

if ( function_compare('get_relative_path', 1, true, __FILE__, __LINE__) )
{	/**
	 * Gets the relative location of $wanted_file based on the $base_file's location
	 * 
	 * @version 1, 2007
	 * 
	 * @param string	$wanted_path
	 * @param string	$base_path
	 * 
	 * @return string
	 */
	function get_relative_path( $wanted_path, $base_path )
	{	
		$wanted_path = create_valid_path($wanted_path);
		$base_path = create_valid_path($base_path, true);
		
		// remove the file from $base_file if it exists
		if( substr($base_path,strlen($base_path)-1,1) !== '/' )
			$base_path = substr($base_path,0,strrpos($base_path,'/'));
		
		$start_on = 0;
		$change_on = 0;
		
		$a = explode('/',$wanted_path);
		$aa = explode('/',$base_path);
		$s = sizeof($a);
		$ss = sizeof($aa);
		
		// Remove the empty parts
		for( $i = 0; $i < $s; $i++ )
		{	// remove empty parts
			if( empty($a[$i]) ) {
				array_splice($a,$i,1);
				$i--;
				$s--;
			}
		}
		
		for( $i = 0; $i < $ss; $i++ )
		{	// remove empty parts
			if( empty($aa[$i]) ) {
				array_splice($aa,$i,1);
				$i--;
				$ss--;
			}
		}
		
		for( $i = 0; $i < $s && $i < $ss; $i++ )
		{	// gets the first similarity between the two locations
			$c = & $a[$i];
			$cc = & $aa[$i];
			if( $c === $cc ) {
				$start_on = $i;
				break;
			}
		}
		
		for( $i = $start_on; $i < $s && $i < $ss; $i++ )
		{	// gets the first difference between the two locations
			$c = & $a[$i];
			$cc = & $aa[$i];
			if( $c !== $cc ) {
				$change_on = $i;
				break;
			}
		}
		
		array_splice($a,0,$change_on);
		array_splice($aa,0,$change_on);
		$new_file = implode('/',$a);
		$ss = sizeof($aa);
		for ( $i = 0; $i < $ss; $i++ )
			$new_file = '../'.$new_file;
			
		return $new_file;
	}
}


if ( function_compare('copy_dir', 1, true, __FILE__, __LINE__) )
{	/**
	 * Copy a directory to another location
	 * 
	 * @version 1
	 * 
	 * @param string	$source
	 * @param string	$dest
	 * @param string	$overwrite
	 * 
	 * @return boolean
	 */
	function copy_dir($source, $dest, $overwrite = false){
	
		$source = create_valid_path($source);
		$dest = create_valid_path($dest);
	
		if( !(is_dir($dest) || @mkdir($dest)) )
		{	// if the destination directory does not exist then create it
			return false;
		}
			
		if( $handle = @opendir($source) )
		{	// open'd the directory for reading
			while( false !== ($file = readdir($handle)) )
			{	// cycle through the contents of the directory
				if( substr($file,0,1) != '.' )
				{	// if the file is not a dummy file
					$source_file = $source.$file;
					$dest_file = $dest.$file;
					
					if( is_file($dest_file) && is_file($source_file) )
					{	// if both things are files then remove the destination file
						if( $overwrite )
							if ( !@unlink($dest_file) )
								return false;
					}
						
					if( is_file($source_file) )
					{	// if the source file is a file then copy it over
						if( !@copy($source_file, $dest_file) )
							return false;
					}
					elseif( is_dir($source_file) )
					{	// if the source file is a directory then recurse
						if( !copy_dir($source_file, $dest_file, $overwrite) )
							return false;
					}
					
				}
			}
			closedir($handle);
		} else
		{
			return false;
		}
		
		return true;
	}
}

if ( function_compare('rename_dir', 1, true, __FILE__, __LINE__) )
{	/**
	 * Rename/Move a directory to another location
	 * 
	 * @version 1
	 * 
	 * @param string	$source
	 * @param string	$dest
	 * @param string	$overwrite
	 * 
	 * @return boolean
	 */
	function rename_dir ($source, $dest, $overwrite = false)
	{
	
		$source = create_valid_path($source);
		$dest = create_valid_path($dest);
	
		if( !(is_dir($dest) || @mkdir($dest)) )
		{	// if the destination directory does not exist then create it
			return false;
		}
			
		if( $handle = @opendir($source) )
		{	// open'd the directory for reading
			while( false !== ($file = readdir($handle)) )
			{	// cycle through the contents of the directory
				if( substr($file,0,1) != '.' )
				{	// if the file is not a dummy file
					$source_file = $source.$file;
					$dest_file = $dest.$file;
					
					if( is_file($dest_file) && is_file($source_file) )
					{	// if both things are files then remove the destination file
						if( $overwrite )
							if ( !@unlink($dest_file) )
								return false;
					}
						
					if( is_file($source_file) )
					{	// if the source file is a file then copy it over
						if( !@rename($source_file, $dest_file) )
							return false;
					}
					elseif( is_dir($source_file) )
					{	// if the source file is a directory then recurse
						if( !rename_dir($source_file, $dest_file, $overwrite) )
							return false;
					}
					
				}
			}
			closedir($handle);
		} else
		{
			return false;
		}
		
		return unlink_dir($source);
	}
}

if( function_compare( 'move_dir', 1, true, __FILE__, __LINE__ ) )
{	/**
	 * Alias for rename_dir
	 *
	 * @see rename_dir
	 */
	function move_dir ($source, $dest, $overwrite = false)
	{
		return rename_dir($source, $dest, $overwrite);
	}
}

?>