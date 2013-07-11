<?php
/**
 * plugin fastpost
 * @version 0.6 - July 2013
 * @package fastpost
 * @copyright Copyright (c) SupaDupa Productions http://SupaDesign.ca
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

/**
 * Info
 * =======================
 *
 * Use Plugin Trigger then use as many *known* URLs as you want:
 *   =FP http://example.com/story45  http://example.org/story79
 */


defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.plugin.plugin');
jimport('simplehtmldom.simple_html_dom');

class plgContentFastPost extends JPlugin {


	function plgContentFastPost( &$subject, $params )
        {
                parent::__construct( $subject, $params );
        }


        /**
        * Example prepare content method
        *
        * Method is called by the view
        *
        * @param object The article object. Note $article->text is also available
        * @param object The article params
        * @param int The 'page' number
        */
        function onContentPrepare( $context, &$article, &$params, $limitstart=0)
        {
                global $mainframe;
                if (JString::strpos($article->text, '=FP') === false)  {
					return true;
                }

                $patterns = array(); 
				$patterns[0] = '((<a href=")(http://[\?\&\#=a-zA-Z0-9./-]+)(">))';  //strip out a href to avoid duplicate
				$patterns[1] = '#(=FP)#'; //remove trigger =FP

                $replacements = array();
                $replacements[0] = '';
                $replacements[1] = '';

				$article->text = preg_replace($patterns, $replacements, $article->text); 

				if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
					echo 'I am at least PHP version 5.4.0, my version: ' . PHP_VERSION . "\n";
					$article->text = preg_replace_callback('(http://[\?\&\#=a-zA-Z0-9./-]+)',function ($m){return $this->urlScrape($m[0]);}, $article->text);
					return true;
				}elseif(version_compare(PHP_VERSION, '5.3.0')>= 0)
				{
					echo 'I am at least PHP version 5.3.0, my version: ' . PHP_VERSION . "\n";
					$article->text = preg_replace('|(http://[\?\&\#=a-zA-Z0-9./-]+)|e','$this->urlScrape("\1")', $article->text);
					return true;
				}elseif(version_compare(PHP_VERSION, '5.2.0')>= 0)
				{
					echo 'I am at least PHP version 5.2.0, my version: ' . PHP_VERSION . "\n";
					$article->text = preg_replace('|(http://[\?\&\#=a-zA-Z0-9./-\\?]+)|e','$this->urlScrape("\1")', $article->text);
					return true;
				}

                return true;
        }

		public function _tidy( $find, $type, $html, $index = 0) 
		{
			//This functions is for gracefully getting a single element(default first), if no element from find phrase then no php warnings or notices
			if($type=='inner'){
				foreach($html->find($find)as $element)
					return $html->find($find, $index)->innertext;
			}
			elseif($type=='outer'){
				foreach($html->find($find)as $element)
					return $html->find($find, $index)->outertext;
			}
			elseif($type=='plain'){
				foreach($html->find($find)as $element)
					return $html->find($find, $index)->plaintext;
			}			
			elseif($type=='tag'){
				foreach($html->find($find)as $element)
					return $html->find($find, $index)->tag;
			}
			else{
			 return false;
			}
		}

        public function urlScrape( $url )
        {
				$url = str_replace('//www.','//',$url);
				$useParse = false;
				$parsed='';
				//$params = $this->params;
				$domain = parse_url($url, PHP_URL_HOST);
				$path = parse_url($url, PHP_URL_PATH); 
				//var_dump($path);
				$document =& JFactory::getDocument();

				
				switch ($domain)
				{
					case 'cbc.ca': 
					global $firstrunCBC;

					if(strpos($path,'/news/yourcommunity/') !== false){$useParse = false;} //handle community blog section
					else{
					$useParse = true;
					$html = file_get_html($url);
					if(empty($firstrunCBC)){ //set to add cbc.css only once if multiple CBC articles are loading on the same page
						$document->addCustomTag( '<link rel="stylesheet" href="'.JURI::base().'plugins/content/fastpost/cbc.css" type="text/css" />' );
						$firstrunCBC = true;
					}
					foreach($html->find('img') as $element){if(strpos($element->src,'http://')===false)$element->src="http://$domain/".$element->src;}
					foreach($html->find('a') as $element){if(strpos($element->href,'http://')===false)$element->href="http://$domain/".$element->href;}
					$ret['title'] = $this->_tidy('div[class="headline"] h1','inner', $html);
					$ret['subtitle'] = $this->_tidy('[id="storyhead"]h3.deck','inner', $html);
					$ret['posted'] = $this->_tidy('[id="storyhead"]h4.posted','inner', $html);
					$ret['lastupdated'] = $this->_tidy('[id="storyhead"]h4.lastupdated','inner', $html);
					$ret['leadmedia'] = $this->_tidy('[id="leadmedia"]','inner', $html);
					//echo $ret['leadmedia'];

					foreach($html->find('div[id*="video"]') as $element){$ret['videos'][]=$element;}//not working(possibly js err)
					//var_dump($ret['videos']);
					
					$ret['storybody'] = $this->_tidy('[id="storybody"]','inner', $html);

					$parsed.= '<h1>'.$ret['title'].'</h1><h2>'.$ret['subtitle'].'</h2><h6>'.$ret['posted'].$ret['lastupdated'].'</h6><br>'.$ret['leadmedia'];
					$parsed.=$ret['storybody'];
					$html->clear();
				/* separating commments from used
					//$ret['title'] = $html->find('[id="storyhead"]h1', 0)->innertext;
					//$ret['subtitle'] = $html->find('h3[class="deck"]', 0)->innertext;
					//$html->find('[id="storyhead"]h3.deck', 0)->innertext;
					//$ret['posted'] = $html->find('h4[class="posted"]', 0)->innertext;
					//$ret['lastupdated'] = $html->find('h4[class="lastupdated"]', 0)->innertext;
					//var_dump($html->find('[id*=head]')->outertext);
					//Broke $ret['leadmediaimage'] = $html->find('[class="leadimage"]img', 0)->src;
					//Broke $ret['leadmediaimage'] = $ret['leadmedia']->find('img[src]');
					//$ret['leadmediacaption'] = $html->find('[id="leadmedia"]em.caption', 0)->innertext;
					//Broke $ret['video'] = $html->find('div[id^="video"]', 0)->innertext;
					//Broke $ret['video1'] = $html->find('[id="video"]', 0)->innertext;
					//Broke $ret['video2'] = $html->find('[class="tpmedia video"]', 0)->innertext;
					//$ret['sidebar'] = $html->find('div[class="sidebar"]', 0)->innertext;
					//$article->title = $ret['title'];
					grab js files but too many and not able to distinguish since no other attributes in tag, also grabs from inside and outside <body>
						foreach($html->find('script[type="text/javascript"]') as $element){ 
							if(isset($element->src)){
								if(strpos($element->src,'http://') || strpos($element->src,'//') ===false){$element->src="http://$domain".$element->src;}
							}
							$document->addCustomTag($element->outertext);
						}
				*/
					
					}
					break;	

					case 'janinebandcroft.wordpress.com': 
					$useParse = true;
					$html = file_get_html($url);
					foreach($html->find('img') as $element){if(strpos($element->src,'http://')===false)$element->src="http://$domain/".$element->src;}
					foreach($html->find('a') as $element){if(strpos($element->href,'http://')===false)$element->href="http://$domain/".$element->href;}

					$ret['title'] = $this->_tidy('h2[class="entry-title"]', 'inner', $html);
					$ret['meta'] = $this->_tidy('div[class="entry-meta"]', 'inner', $html);
					foreach($html->find('audio[id] span') as $element){
						$ret['audio'][] = $element->innertext;
					}

					$ret['audiolist']='';
					foreach ($ret['audio'] as $value){
						$ret['audiolist'].=$value.'<br>';}

					$i=0;
					$ret['sizecheck'] = strlen($html->find('div[class="entry-content"] p',$i)->innertext);
					while ($ret['sizecheck'] < 10000){
						$ret['entry-content'][] = $html->find('div[class="entry-content"] p',$i)->innertext;
						$i++;
						$ret['sizecheck'] = strlen($html->find('div[class="entry-content"] p',$i)->innertext);
					}
					
					$ret['body']='';
					foreach ($ret['entry-content'] as $value) {
						$ret['body'] .= '<p>'.$value.'</p>';
					}

					$parsed.= '<h1>'.$ret['title'].'</h1><br><h6>'.$ret['meta'].'</h6><br>'.$ret['audiolist'];
					$parsed.=$ret['body'];
					$html->clear();
					
				/* Comment tests
					//$ret['audio'] = $html->find('span[style="text-align:left;display:block;"] p', 0)->innertext;
					//$ret['audio'] = $html->find('audio[id] span', 0)->innertext;
					
				Tests to use foreach instead of while but pulls one div incorrectly at end, may be an indication of how to solve js issue
				   foreach($html->find('audio span') as $element){
					echo $element;}
					foreach($html->find('object[type="application/x-shockwave-flash"] span[id] audio[id]') as $element){ 
							if(isset($element->src)){
								if(strpos($element->src,'http://') || strpos($element->src,'//') ===false){$element->src="http://$domain".$element->src;}
							}
							$ret['audio'][] = $element->first_child()->innertext;
							echo 'script parent=';var_dump($element->first_child()->innertext);
							//if(strlen($element) > 10000){echo 'break';break;}
						}*/
					
				/* 
					foreach($html->find('div[class="entry-content"] p') as $element){
						if(strlen($element->innertext)<10000){
							$ret['entry-content'][] = $element->innertext;
						}
						else {
							//echo $element->innertext;
							$jsErr = str_get_html($element->innertext);
							foreach($jsErr->find('div') as $e)if($e->find('[class="wpa"]')){break;}
							//echo 'oversize'.$jsErr->plaintext;
							//break;
						}
					}
					//echo $ret['audio'];
					//echo $ret['audio'][1];
					//echo $html->find('audio[id] span',-1);
					//$ret['entry-content'] = $html->find('div[class="entry-content"]', 0)->innertext;
					//$ret['entry-content'][] = $html->find('div[class="entry-content"] p',0)->innertext;
					//$ret['entry-content'][] = $html->find('div[class="entry-content"] p',1)->innertext;
					//$ret['entry-content'][] = $html->find('div[class="entry-content"] p',2)->innertext;
					*/
					
					break;


					case 'thetyee.ca': 
					$useParse = true;
					$html = file_get_html($url);
					foreach($html->find('img') as $element){if(strpos($element->src,'http://')===false)$element->src="http://$domain/".$element->src;}
					foreach($html->find('a') as $element){if(strpos($element->href,'http://')===false)$element->href="http://$domain/".$element->href;}

					$ret['title'] = $this->_tidy('h2[class="title"]', 'inner', $html);
					$ret['subtitle'] = $this->_tidy('p[class="tagline"]', 'inner', $html);
					$ret['meta'] = $this->_tidy('div[class="node-inner"] p[class="meta"]','inner', $html);
					
					foreach($html->find('div[class="photo-caption"]') as $e){ //match tyee photo caption styling
						$e->first_child()->setAttribute('style','font-family: \'Lucida Grande\', Verdana, sans-serif;margin: 15px 5px;font-size: 0.7em;');}
					
					$skip=''; //this takes the caption paragraph out of main story and replicates it under the image(only works since they are siblings)
					foreach($html->find('div[id="content-inner"] div[class="content"] p') as $element){
						if($element->class == 'photo-insert') {
							$element->style = 'float: right;width: 300px;clear: both;'; //set image style and add caption on next line
							$element->innertext = $element->innertext.'<div style="float: right;width: 300px;clear: both;">'.$element->next_sibling().'</div>';
							
							$skip = $element->next_sibling()->plaintext; //grab caption from next element and then skip adding it on next foreach pass
						}

						if(trim($skip) == trim($element->plaintext)){ $skip='';} //echo 'skipped='.$element;
						else {
						 $ret['contentlist'][] = $element->outertext;
						}
					}
					
					//var_dump($ret['contentlist']);
					$ret['body'] = '';
					foreach($ret['contentlist'] as $element){
						if(strpos($element,'input') === false)$ret['body'].=$element; //remove input tags while compiling body
					}

					$parsed= '<h1>'.$ret['title'].'</h1><h2>'.$ret['subtitle'].'</h2><p>'.$ret['meta'].'</p>';
					$parsed.=$ret['body'];
					$html->clear();
					break;

					case 'focusonline.ca': 
					$useParse = true;
					$html = file_get_html($url);
					foreach($html->find('img') as $element){if(strpos($element->src,'http://')===false)$element->src="http://$domain/".$element->src;}
					foreach($html->find('a') as $element){if(strpos($element->href,'http://')===false)$element->href="http://$domain/".$element->href;}

					$ret['title']= $this->_tidy('div[id="content"] h1[class="node-title"]','inner',$html);
					$ret['author']= $this->_tidy('div[id="content"] div[class="content"] h3','inner',$html);

					$ret['body']='';
					foreach($html->find('div[id="content"] div[class="content"] p') as $element){
						$ret['body'].=$element;
					}

					$ret['issuecover']= $this->_tidy('div[id="sidebar-second"] div[class="inner"] div[class="content"]','inner',$html);
					$ret['attachments']='';
					foreach($html->find('table[id="attachments"]')as $element)$ret['attachments'] = $html->find('table[id="attachments"]', 0)->outertext;

					$parsed.= '<h1>'.$ret['title'].'</h1><p>'.$ret['author'].'</p>';
					$parsed.= '<div style="width:180px;float:right;margin:0 10px;text-align:center;line-height:.5em;">'.$ret['issuecover'].'</div>';
					$parsed.=$ret['body'];
					if(!empty($ret['attachments']))
						$parsed.='<br>'.$ret['attachments'];
					
					echo strlen($ret['issuecover']);
					if(strlen($parsed) < 500){$useParse = false;}				
					$html->clear();
					
					//$ret['title']='';$ret['author']='';
					//foreach($html->find('div[id="content"] h1[class="node-title"]')as $element)
						//$ret['title'] = $html->find('div[id="content"] h1[class="node-title"]', 0)->innertext;
					//foreach($html->find('div[id="content"] div[class="content"] h3')as $element)
						//$ret['author'] = $html->find('div[id="content"] div[class="content"] h3', 0)->innertext;
					//created _tidy function to replace above above lines and avoid missing object calls if bad URL or element missing
					
					//$ret['issuecover'] = $html->find('div[id="sidebar-second"] div[class="inner"] div[class="content"]', 0)->innertext;
					//$issuecontent = str_get_html($ret['issuecover']);
					//foreach($issuecontent->find('a') as $element){if(strpos($element->href,'http://')===false)$element->href="http://$domain/".$element->href;}
					//$ret['issuecover'] = $issuecontent->innertext;
					
					break;
				}

				

				if($useParse){
					return $parsed.'<p><a href="'.$url.'" target="_blank">'.$url.' <h6>FastPost Article</h6></a></p>';
				}
				else{
					return '<a href="'.$url.'" target="_blank">'.$url.'</a>';
				}
        }


}

?>