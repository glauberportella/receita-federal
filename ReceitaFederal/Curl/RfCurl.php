<?php
namespace ReceitaFederal\Curl;

class RfCurl
{
	public static function curlExecFollow($ch, &$maxredirect = null)
	{
	  // we emulate a browser here since some websites detect
	  // us as a bot and don't let us do our job
	  $user_agent = "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7.5)".
	                " Gecko/20041107 Firefox/1.0";

	  curl_setopt($ch, CURLOPT_USERAGENT, $user_agent );

	  $mr = $maxredirect === null ? 5 : intval($maxredirect);

	  if (ini_get('open_basedir') == '' && ini_get('safe_mode') == 'Off') {

	    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $mr > 0);
	    curl_setopt($ch, CURLOPT_MAXREDIRS, $mr);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

	  } else {
	    
	    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

	    if ($mr > 0)
	    {
	      $original_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
	      $newurl = $original_url;
	      
	      $rch = curl_copy_handle($ch);
	      
	      curl_setopt($rch, CURLOPT_HEADER, true);
	      curl_setopt($rch, CURLOPT_NOBODY, true);
	      curl_setopt($rch, CURLOPT_FORBID_REUSE, false);
	      do
	      {
	        curl_setopt($rch, CURLOPT_URL, $newurl);
	        $header = curl_exec($rch);
	        if (curl_errno($rch)) {
	          $code = 0;
	        } else {
	          $code = curl_getinfo($rch, CURLINFO_HTTP_CODE);
	          if ($code == 301 || $code == 302) {
	            preg_match('/Location:(.*?)\n/', $header, $matches);
	            $newurl = trim(array_pop($matches));
	            
	            // if no scheme is present then the new url is a
	            // relative path and thus needs some extra care
	            if(!preg_match("/^https?:/i", $newurl)){
	            	// if original_url dont finish with a /
	            	// we supose it is something .../some.html
	            	// so we care it
	            	$slash = substr($original_url, -1);
	            	if ($slash != '/') {
	            		$pos = strrpos($original_url, '/');
	            		$original_url = substr($original_url, 0, $pos).'/';
	            	}
					$newurl = $original_url . $newurl;
	            }   
	          } else {
	            $code = 0;
	          }
	        }
	      } while ($code && --$mr);
	      
	      curl_close($rch);
	      
	      if (!$mr)
	      {
	        if ($maxredirect === null)
	        trigger_error('Too many redirects.', E_USER_WARNING);
	        else
	        $maxredirect = 0;
	        
	        return false;
	      }
	      curl_setopt($ch, CURLOPT_URL, $newurl);
	    }
	  }
	  return curl_exec($ch);
	}
}