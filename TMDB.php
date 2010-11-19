<?php
class TMDB {
	var $tmdbapikey;
	var $lang = 'en';
	var $api_version = '2.1';
	var $usecache = false;
	var $outputurl = false;
	
	var $movie_cachedir = 'Thirdparty/TMDB/cache/movie/';
	var $actor_cachedir;

	var $movie_search_cachedir = 'cache/search/movie/';
	var $actor_search_cachedir = 'cache/search/actor/';

	var $movie_cache_ttl = 86400; // 1 day = 86400, 1 hours = 3600
	var $actor_cache_ttl = 86400; // 1 day = 86400, 1 hours = 3600

	var $movie_search_cache_ttl = 86400; // 1 day = 86400, 1 hours = 3600
	var $actor_search_cache_ttl = 86400; // 1 day = 86400, 1 hours = 3600
	
	
function movie_info($tmdbid)
    {
        $url ="http://api.themoviedb.org/$this->api_version/Movie.getInfo/$this->lang/xml/$this->tmdbapikey/".$tmdbid;
		if ($this->outputurl == 'true') { echo $url; }
        if ($this->usecache == 'true')
            {
            if (!file_exists( $this->movie_cachedir.$tmdbid.'.xml'))
                { 
				 $this->_save_file($url, $this->movie_cachedir.$tmdbid.'.xml'); 
				} else {
				 if ($this->_cache($this->movie_cachedir.$tmdbid.'.xml',$this->movie_cache_ttl))
					{
					$this->_save_file($url, $this->movie_cachedir.$tmdbid.'.xml'); 	
					}
				}
		
            $tmdbmovie = $this->_xml2array($this->movie_cachedir.$tmdbid.'.xml');

            } else {
            $tmdbmovie = $this->_xml2array($url); //grab xml fresh
            }
        $movie = $tmdbmovie['OpenSearchDescription']['movies']['movie'];
        $output = $this->_tmdb_movie_xml2array($movie);

        return($output); 
    }

function actor_info($tmdbid)
    {
        //global $tmdbapikey;
        $url = "http://api.themoviedb.org/$this->api_version/Person.getInfo/$this->lang/xml/$this->tmdbapikey/".$tmdbid;
		if ($this->outputurl == 'true') { echo $url; }
        if ($this->usecache == 'true')
            {
            if (!file_exists($this->actor_cachedir . $tmdbid.'.xml'))
                { 
				$this->_save_file($url, $this->actor_cachedir . $tmdbid.'.xml'); 
				} else {
				 if ($this->_cache($this->actor_cachedir.$tmdbid.'.xml',$this->actor_cache_ttl))
					{
					$this->_save_file($url, $this->actor_cachedir.$tmdbid.'.xml'); 	
					}					
					
				}
				
				
            $tmdbactor = $this->_xml2array($this->actor_cachedir . $tmdbid.'.xml'); 
            } else {
            $tmdbactor = $this->_xml2array($url);
            }  
        //print_r($tmdbactor);
        $actor=$tmdbactor['OpenSearchDescription']['people']['person'];
        $output = $this->_tmdb_actor_xml2array($actor);
    return($output);
    }

function actor_search($search)
    {
        $search = strtolower(str_replace(' ','+',$search));
        $search = str_replace('%20','+',$search);
        $url = "http://api.themoviedb.org/$this->api_version/Person.search/$this->lang/xml/$this->tmdbapikey/".$search;   
		if ($this->outputurl == 'true') { echo $url; }
        if ($this->usecache == 'true')
            {
	            if (!file_exists($this->actor_search_cachedir . $search.'.xml'))
	                { 
					$this->_save_file($url, $this->actor_search_cachedir . $search.'.xml'); 
					} else {
					 if ($this->_cache($this->actor_search_cachedir.$search.'.xml',$this->actor_search_cache_ttl))
						{
						$this->_save_file($url, $this->actor_search_cachedir.$search.'.xml'); 	
						}					

					}
			 $tmdbsearch = $this->_xml2array($this->actor_search_cachedir . $search.'.xml'); 		
			} else { 
				//cache not on
			 $tmdbsearch = $this->_xml2array($url);	
			
			}


       
       //print_r($tmdbsearch);
        $searched = $tmdbsearch['OpenSearchDescription']['people']['person'];
        if ($searched == 'Nothing found.')
            {
                $output = 'Nothing found.';
            } else {
            foreach ($searched as $result)
                {
                    $output[]=$this->_tmdb_actor_xml2array($result);
                } 
            }            
        return($output);
    }
function movie_search($search)
    {
        //global $tmdbapikey;
        $search = strtolower(str_replace(' ','+',$search));
        $search = str_replace('%20','+',$search);
        $url = "http://api.themoviedb.org/$this->api_version/Movie.search/$this->lang/xml/$this->tmdbapikey/".$search;
        //echo $url;
        if ($this->outputurl == 'true') { echo $url; }
        if ($this->usecache == 'true')
            {
	            if (!file_exists($this->movie_search_cachedir . $search.'.xml'))
	                { 
					$this->_save_file($url, $this->movie_search_cachedir . $search.'.xml'); 
					} else {
					 if ($this->_cache($this->movie_search_cachedir.$search.'.xml',$this->movie_search_cache_ttl))
						{
						$this->_save_file($url, $this->movie_search_cachedir.$search.'.xml'); 	
						}					

					}
			 $tmdbsearch = $this->_xml2array($this->movie_search_cachedir . $search.'.xml'); 		
			} else { 
				//cache not on
			 $tmdbsearch = $this->_xml2array($url);	
			
			}
        $searched = $tmdbsearch['OpenSearchDescription']['movies']['movie'];

        if($searched == 'Nothing found.')
            {
                $output = 'Nothing found.';
            } else {
			if (is_array($searched) && !isset($searched['name']))
				{

	            foreach ($searched as $result)
	                {
	                    //print_r($searched);
	                	$output[]=$this->_tmdb_movie_xml2array($result);
	                }
				} elseif (isset($search['name'])) {
					$output[0] = $searched;
				} else {
					 $output = 'Nothing found.';
				}
            }
        return($output);
    }
function _tmdb_movie_xml2array($xml)
    {
        $attrs = array('name','popularity','type','id','imdb_id','url','overview','rating','released','runtime','budget','revenue','homepage','trailer',);
        foreach($attrs as $attr)
            {
                if (isset($xml[$attr]))
                    {
                        $output[$attr] = $xml[$attr];
                    } 
            }
        //Cast
        $attrs = array('name','job','url','thumb','id','character');
        if(isset($xml['cast']['person']) && is_array($xml['cast']['person']))
            {
                $i=0;
                foreach ($xml['cast']['person'] as $cast)
                    {
                    $inc = false;
                    foreach($attrs as $attr)
                        {
                            if(isset($cast[$attr]))
                                {
                                    $output['cast'][$i][$attr] = $cast[$attr];
                                    $inc = true;
                                }                         
                        }
                        if ($inc == true) { $i++; } // only increment if theres actually data
                    }           
            }
        //Posters
        $attrs = array('type','size','url','id');
        if(isset($xml['images']['image']) && is_array($xml['images']['image']))
            {
                $i=0;
                foreach ($xml['images']['image'] as $image)
                    {
                    $inc = false;
                    foreach($attrs as $attr)
                        {
                            if(isset($image[$attr]))
                                {
                                    $output['images'][$i][$attr] = $image[$attr];
                                    $inc = true;
                                }
                            
                        }
                        if ($inc == true) { $i++; } // only increment if theres actually data
                    }
                
            } 
        return($output);
    }

function _tmdb_actor_xml2array($xml)

    {
        $attrs = array('id','name','birthday','birthplace','url','biography');
         foreach($attrs as $attr)
            {
                if (isset($xml[$attr]))
                    {
                        $output[$attr] = $xml[$attr];
                    } 
            } 
          //Filmography
        $attrs = array('name','job','url','thumb','id','character');
        if(isset($xml['filmography']) && is_array($xml['filmography']['movie']))
            {
                $i=0;
                foreach ($xml['filmography']['movie'] as $movie)
                    {
                    $inc = false;
                    foreach($attrs as $attr)
                        {
                            if(isset($movie[$attr]))
                                {
                                    $output['movie'][$i][$attr] = $movie[$attr];
                                    $inc = true;
                                }                     
                        }
                        if ($inc == true) { $i++; } // only increment if theres actually data
                    }
            }   
         //images
         $attrs = array('type','size','url','id');      
         if(isset($xml['images']['image']) && is_array($xml['images']['image']))
            {
                $i=0;
                foreach ($xml['images']['image'] as $image)
                    {
                    $inc = false;
                    foreach($attrs as $attr)
                        {

                            if(isset($image[$attr]))
                                {
                                    $output['images'][$i][$attr] = $image[$attr];
                                    $inc = true;
                                }
                            
                        }
                        if ($inc == true) { $i++; } // only increment if theres actually data
                    }
            } 
            return($output);
    }

function _xml2array($url, $get_attributes = 1, $priority = 'tag')
	{
	    $contents = "";
	    if (!function_exists('xml_parser_create'))
	    {
	        return array ();
	    }
	    $parser = xml_parser_create('');
	    if (!($fp = @ fopen($url, 'rb')))
	    {
	        return array ();
	    }
	    while (!feof($fp))
	    {
	        $contents .= fread($fp, 8192);
	    }
	    fclose($fp);
	    xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
	    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
	    xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
	    xml_parse_into_struct($parser, trim($contents), $xml_values);
	    xml_parser_free($parser);
	    if (!$xml_values)
	        return; //Hmm...
	    $xml_array = array ();
	    $parents = array ();
	    $opened_tags = array ();
	    $arr = array ();
	    $current = & $xml_array;
	    $repeated_tag_index = array (); 
	    foreach ($xml_values as $data)
	    {
	        unset ($attributes, $value);
	        extract($data);
	        $result = array ();
	        $attributes_data = array ();
	        if (isset ($value))
	        {
	            if ($priority == 'tag')
	                $result = $value;
	            else
	                $result['value'] = $value;
	        }
	        if (isset ($attributes) and $get_attributes)
	        {
	            foreach ($attributes as $attr => $val)
	            {
	                if ($priority == 'tag')
	                    $attributes_data[$attr] = $val;
	                else
	                    $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
	            }
	        }
	        if ($type == "open")
	        { 
	            $parent[$level -1] = & $current;
	            if (!is_array($current) or (!in_array($tag, array_keys($current))))
	            {
	                $current[$tag] = $result;
	                if ($attributes_data)
	                    $current[$tag . '_attr'] = $attributes_data;
	                $repeated_tag_index[$tag . '_' . $level] = 1;
	                $current = & $current[$tag];
	            }
	            else
	            {
	                if (isset ($current[$tag][0]))
	                {
	                    $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
	                    $repeated_tag_index[$tag . '_' . $level]++;
	                }
	                else
	                { 
	                    $current[$tag] = array (
	                        $current[$tag],
	                        $result
	                    ); 
	                    $repeated_tag_index[$tag . '_' . $level] = 2;
	                    if (isset ($current[$tag . '_attr']))
	                    {
	                        $current[$tag]['0_attr'] = $current[$tag . '_attr'];
	                        unset ($current[$tag . '_attr']);
	                    }
	                }
	                $last_item_index = $repeated_tag_index[$tag . '_' . $level] - 1;
	                $current = & $current[$tag][$last_item_index];
	            }
	        }
	        elseif ($type == "complete")
	        {
	            if (!isset ($current[$tag]))
	            {
	                $current[$tag] = $result;
	                $repeated_tag_index[$tag . '_' . $level] = 1;
	                if ($priority == 'tag' and $attributes_data)
	                    $current[$tag . '_attr'] = $attributes_data;
	            }
	            else
	            {
	                if (isset ($current[$tag][0]) and is_array($current[$tag]))
	                {
	                    $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
	                    if ($priority == 'tag' and $get_attributes and $attributes_data)
	                    {
	                        $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
	                    }
	                    $repeated_tag_index[$tag . '_' . $level]++;
	                }
	                else
	                {
	                    $current[$tag] = array (
	                        $current[$tag],
	                        $result
	                    ); 
	                    $repeated_tag_index[$tag . '_' . $level] = 1;
	                    if ($priority == 'tag' and $get_attributes)
	                    {
	                        if (isset ($current[$tag . '_attr']))
	                        { 
	                            $current[$tag]['0_attr'] = $current[$tag . '_attr'];
	                            unset ($current[$tag . '_attr']);
	                        }
	                        if ($attributes_data)
	                        {
	                            $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
	                        }
	                    }
	                    $repeated_tag_index[$tag . '_' . $level]++; //0 and 1 index is already taken
	                }
	            }
	        }
	        elseif ($type == 'close')
	        {
	            $current = & $parent[$level -1];
	        }
	    }
	    return ($xml_array);
	}
 function _save_file($file,$fullpath)
	{
	    $ch = curl_init ($file);
	    curl_setopt($ch, CURLOPT_HEADER, 0);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
	    $rawdata=curl_exec($ch);
	    curl_close ($ch);
	    if(file_exists($fullpath)){
	        unlink($fullpath);
	    }
	    $fp = fopen($fullpath,'x');
	    fwrite($fp, $rawdata);
	    fclose($fp); 
	}
function _cache($file,$ttl)
	{
		$cacheage =  time() - filemtime($file);
		//echo $cacheage;
		if ($cacheage > $ttl)
			{
				//delete old file
				unlink($file);
				return true;
			} else {
				return false;
			
			}
	}
 
}
?>

