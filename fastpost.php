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
		
		public function _graceGet( $find, $type, $html ) 
		{
			//This functions is for gracefully getting a single element(first only), if no element available then no php warnings or notices
			if($type=='inner'){
				foreach($html->find($find)as $element)
					return $html->find($find, 0)->innertext;
			}
			elseif($type=='outer'){
				foreach($html->find($find)as $element)
					return $html->find($find, 0)->outertext;
			}
			elseif($type=='plain'){
				foreach($html->find($find)as $element)
					return $html->find($find, 0)->plaintext;
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
					if(empty($firstrunCBC)){
						$document->addCustomTag( '<link rel="stylesheet" href="'.JURI::base().'plugins/content/fastpost/cbc.css" type="text/css" />' );
						/*
						foreach($html->find('script[type="text/javascript"]') as $element){ 
							if(isset($element->src)){
								if(strpos($element->src,'http://') || strpos($element->src,'//') ===false){$element->src="http://$domain".$element->src;}
							}
							$document->addCustomTag($element->outertext);
						}*/
						$firstrunCBC = true;
					}
					foreach($html->find('img') as $element){if(strpos($element->src,'http://')===false)$element->src="http://$domain/".$element->src;}
					$ret['title'] = $html->find('div[class="headline"] h1', 0)->innertext;
					//$ret['title'] = $html->find('[id="storyhead"]h1', 0)->innertext;
					//$ret['subtitle'] = $html->find('h3[class="deck"]', 0)->innertext;
					$ret['subtitle'] = $this->_graceGet('[id="storyhead"]h3.deck','inner', $html);
						//$html->find('[id="storyhead"]h3.deck', 0)->innertext;
					//$ret['posted'] = $html->find('h4[class="posted"]', 0)->innertext;
					$ret['posted'] = $html->find('[id="storyhead"]h4.posted', 0)->innertext;
					//$ret['lastupdated'] = $html->find('h4[class="lastupdated"]', 0)->innertext;
					$ret['lastupdated'] = $html->find('[id="storyhead"]h4.lastupdated', 0)->innertext;
					$ret['leadmedia'] = $html->find('div[id="leadmedia"]', 0)->innertext;
					echo $ret['leadmedia'];

					foreach($html->find('div[id*="video"]') as $element){$ret['videos'][]=$element;}
					var_dump($ret['videos']);//var_dump($html->find('[id*=head]')->outertext);
					//Broke $ret['leadmediaimage'] = $html->find('[class="leadimage"]img', 0)->src;
					//Broke $ret['leadmediaimage'] = $ret['leadmedia']->find('img[src]');
					//$ret['leadmediacaption'] = $html->find('[id="leadmedia"]em.caption', 0)->innertext;
					//Broke $ret['video'] = $html->find('div[id^="video"]', 0)->innertext;
					//Broke $ret['video1'] = $html->find('[id="video"]', 0)->innertext;
					//Broke $ret['video2'] = $html->find('[class="tpmedia video"]', 0)->innertext;
					$ret['storybody'] = $html->find('[id="storybody"]', 0)->innertext;
					//$ret['sidebar'] = $html->find('div[class="sidebar"]', 0)->innertext;
					//var_dump($ret);
					//var_dump($html);
					//$article->title = $ret['title'];
					$parsed.= '<h1>'.$ret['title'].'</h1><h2>'.$ret['subtitle'].'</h2><h6>'.$ret['posted'].$ret['lastupdated'].'</h6><br>'.$ret['leadmedia'];
					$parsed.=$ret['storybody'];
					$html->clear();
					}
					break;	
					
					case 'janinebandcroft.wordpress.com': 
					$useParse = true;
					$html = file_get_html($url);

					$ret['title'] = $html->find('h2[class="entry-title"]', 0)->innertext;
					$ret['entry-meta'] = $html->find('div[class="entry-meta"]', 0)->innertext;
					//$ret['audio'] = $html->find('span[style="text-align:left;display:block;"] p', 0)->innertext;
					//$ret['audio'] = $html->find('audio[id] span', 0)->innertext;
					foreach($html->find('audio[id] span') as $element){
						$ret['audio'][] = $element->innertext;
					}
					
					$ret['audiolist']='';
					foreach ($ret['audio'] as $value){
						$ret['audiolist'].=$value.'<br>';}
					
					/*foreach($html->find('audio span') as $element){
					echo $element;}
					foreach($html->find('object[type="application/x-shockwave-flash"] span[id] audio[id]') as $element){ 
							if(isset($element->src)){
								if(strpos($element->src,'http://') || strpos($element->src,'//') ===false){$element->src="http://$domain".$element->src;}
							}
							$ret['audio'][] = $element->first_child()->innertext;
							echo 'script parent=';var_dump($element->first_child()->innertext);
							//if(strlen($element) > 10000){echo 'break';break;}
						}*/
					//echo $ret['audio'];
					//echo $ret['audio'][1];
					//echo $html->find('audio[id] span',-1) ;
					//$ret['entry-content'] = $html->find('div[class="entry-content"]', 0)->innertext;
					//$ret['entry-content'][] = $html->find('div[class="entry-content"] p',0)->innertext;
					//$ret['entry-content'][] = $html->find('div[class="entry-content"] p',1)->innertext;
					//$ret['entry-content'][] = $html->find('div[class="entry-content"] p',2)->innertext;
					foreach($html->find('div[class="entry-content"] p') as $element){
						if(strlen($element->innertext)<10000){
							$ret['entry-content'][] = $element->innertext;
						}
						else {
							echo $element->innertext;
							$jsErr = str_get_html($element->innertext);
							echo 'oversize'.$jsErr->plaintext;
						}
					}
					
					/*$i=0;
					$ret['sizecheck'] = strlen($html->find('div[class="entry-content"] p',$i)->innertext);
					while ($ret['sizecheck'] < 10000){
						$ret['entry-content'][] = $html->find('div[class="entry-content"] p',$i)->innertext;
						$i++;
						$ret['sizecheck'] = strlen($html->find('div[class="entry-content"] p',$i)->innertext);
					}
					*/
					$ret['body']='';
					foreach ($ret['entry-content'] as $value) {
						$ret['body'] .= '<p>'.$value.'</p>';
					}
					
					//var_dump($ret);
					$parsed= '<h1>'.$ret['title'].'</h1><br><h6>'.$ret['entry-meta'].'</h6><br>'.$ret['audiolist'];
					$parsed.=$ret['body'];
					$html->clear();
					break;
					
					
					case 'thetyee.ca': 
					$useParse = true;
					$html = file_get_html($url);
					foreach($html->find('a') as $element){if(strpos($element->href,'http://')===false)$element->href="http://$domain/".$element->href;}
					
					
					$ret['title'] = $html->find('h2[class="title"]', 0)->innertext;
					$ret['subtitle'] = $html->find('p[class="tagline"]', 0)->innertext;
					$ret['author'] = $html->find('div[class="node-inner"] p[class="meta"]', 0)->innertext;
					$skip='';
					foreach($html->find('div[id="content-inner"] div[class="content"] p') as $element){
						if($element->class == 'photo-insert') {
							$element->style = 'float: right;width: 300px;clear: both;'; //set image style and add caption on next line
							$element->innertext = $element->innertext.'<div style="float: right;width: 300px;clear: both;font-size: 0.9em;">'.$element->next_sibling().'</div>';
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
					
					$parsed= '<h1>'.$ret['title'].'</h1><h2>'.$ret['subtitle'].'</h2><p>'.$ret['author'].'</p>';
					$parsed.=$ret['body'];
					$html->clear();
					break;
					
					case 'focusonline.ca': 
					$useParse = true;
					$html = file_get_html($url);
					foreach($html->find('img') as $element){if(strpos($element->src,'http://')===false)$element->src="http://$domain/".$element->src;}
					foreach($html->find('a') as $element){if(strpos($element->href,'http://')===false)$element->href="http://$domain/".$element->href;}
					
					//$ret['title']='';$ret['author']='';
					//foreach($html->find('div[id="content"] h1[class="node-title"]')as $element)
						//$ret['title'] = $html->find('div[id="content"] h1[class="node-title"]', 0)->innertext;
					//foreach($html->find('div[id="content"] div[class="content"] h3')as $element)
						//$ret['author'] = $html->find('div[id="content"] div[class="content"] h3', 0)->innertext;
					//created _graceGet function to replace above above lines and avoid missing object calls if bad URL or element missing
					$ret['title']= $this->_graceGet('div[id="content"] h1[class="node-title"]','inner',$html);
					$ret['author']= $this->_graceGet('div[id="content"] div[class="content"] h3','inner',$html);
					
					$ret['body']='';
					foreach($html->find('div[id="content"] div[class="content"] p') as $element){
						$ret['body'].=$element;
					}
					
					$ret['issuecover']= $this->_graceGet('div[id="sidebar-second"] div[class="inner"] div[class="content"]','inner',$html);
					//$ret['issuecover'] = $html->find('div[id="sidebar-second"] div[class="inner"] div[class="content"]', 0)->innertext;
					//$issuecontent = str_get_html($ret['issuecover']);
					//foreach($issuecontent->find('a') as $element){if(strpos($element->href,'http://')===false)$element->href="http://$domain/".$element->href;}
					//$ret['issuecover'] = $issuecontent->innertext;
					$ret['attachments']='';
					foreach($html->find('table[id="attachments"]')as $element)$ret['attachments'] = $html->find('table[id="attachments"]', 0)->outertext;

					$parsed= '<h1>'.$ret['title'].'</h1><p>'.$ret['author'].'</p>';
					$parsed.= '<div style="width:180px;float:right;margin:0 10px;text-align:center;line-height:.5em;">'.$ret['issuecover'].'</div>';
					$parsed.=$ret['body'];
					//if(!empty($ret['attachments']))
					$parsed.='<br>'.$ret['attachments'];

					$html->clear();
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
