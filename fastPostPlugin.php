<?php
/**
 * plugin fastPostPlugin
 * @version 0.5 - July 2013
 * @package fastPostPlugin
 * @copyright Copyright (c) SupaDupa Productions http://SupaDesign.ca
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

/**
 * Info
 * =======================
 *
 * Use Plugin Trigger with URL inside brackets:
 *   {FP http://example.com}
 */


defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.plugin.plugin');
jimport('simplehtmldom.simple_html_dom');

class plgContentFastPostPlugin extends JPlugin {


  function plgContentFastPostPlugin( &$subject, $params )
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
                if (JString::strpos($article->text, '{FP') === false)  {
					return true;
                }
				
                $patterns = array(); 
				$patterns[0] = '((<a href=")(http://[a-zA-Z0-9./-]+)(">))';  //strip out a href to avoid duplicate
				$patterns[1] = '([}])';
				$patterns[2] = '([{FP ])';

                $replacements = array();
                $replacements[0] = '';
                $replacements[1] = '';
                $replacements[2] = '';
				$article->text = preg_replace($patterns, $replacements, $article->text); //remove opening and closing curly braces as well as FP
					
				$article->text = preg_replace_callback('(http://[a-zA-Z0-9./-]+)',function ($m){return $this->urlScrape($m[0]);}, $article->text);
                return true;
        }

        function urlScrape( $url )
        {
				$url = str_replace('//www.','//',$url);
				//var_dump($url);
				//$params = $this->params;
				$domain = parse_url($url, PHP_URL_HOST);
				//$path = parse_url($url, PHP_URL_PATH); 
				
				switch ($domain)
				{
					case 'cbc.ca': $useParse = true;
					$html = file_get_html($url);
					$ret['title'] = $html->find('div[class="headline"] h1', 0)->innertext;
					//$ret['title'] = $html->find('[id="storyhead"]h1', 0)->innertext;
					//$ret['subtitle'] = $html->find('h3[class="deck"]', 0)->innertext;
					$ret['subtitle'] = $html->find('[id="storyhead"]h3.deck', 0)->innertext;
					//$ret['posted'] = $html->find('h4[class="posted"]', 0)->innertext;
					$ret['posted'] = $html->find('[id="storyhead"]h4.posted', 0)->innertext;
					//$ret['lastupdated'] = $html->find('h4[class="lastupdated"]', 0)->innertext;
					$ret['lastupdated'] = $html->find('[id="storyhead"]h4.lastupdated', 0)->innertext;
					$ret['leadmedia'] = $html->find('[id="leadmedia"]', 0)->innertext;
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
					$parsed= '<br><h1>'.$ret['title'].'</h1><br><h2>'.$ret['subtitle'].'</h2><br><h6>'.$ret['posted'].$ret['lastupdated'].'</h6><br>'.$ret['leadmedia'];
					$parsed.=$ret['storybody'];
					break;			
				}
				
				
				if($useParse){
					return $parsed.'<p><a href="'.$url.'" target="_blank">'.$url.'</a><br><h6>FastPost Article</h6></p>';
				}
				else{
					return '<a href="'.$url.'" target="_blank">'.$url.'</a>';
				}
        }
		


}

?>
