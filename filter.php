<?php
/***
  * Filter who turn PodLille1 urls into iframe for video integration,
  * like the multimedia filter.
  *
  * @package    filter
  * @subpackage pod
  * @copyright  2017 Joël Obled
  * @author     Obled Joel <joel.obled@univ-lille1.fr>
  * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * This classes can turn pod's urls into iframe who contains video
 */
class filter_pod extends moodle_text_filter {

	/**
	 * Pod filter for Moodle
	 *
	 * @param string $text text that possibly contains a pod's url to filter
	 * @return string the text that contains urls into iframe
	 */
	public function filter($text, array $options = array()) {
		global $CFG, $COURSE, $PAGE;

		// Initialize default values in case the administrator has not completed correctly the global configuration of the filter
		$config['url']		= 'pod.univ-lille1.fr';
		$config['size']		= 480;
		$config['width']	= 854;
		$config['height']	= 480;
		$config['width_interactive']	= 625;
		$config['height_interactive']	= 530;
		$courseconfig = array();

		// We retrieve the current course ID and then retrieve the context in which the filter runs
		$courseid		= (isset($COURSE->id)) ? $COURSE->id : null;
		$coursecontext	= context_course::instance($courseid);

		// We retrive the filter parameters into the current running context
		$courseconfig = get_active_filters($coursecontext->id);

		// If no local parameter defined pod's server url, we first search into the context, and then a global value in last resort
		if (isset($this->localconfig['url'])) {
			$config['url'] = $this->localconfig['url'];
		} elseif (isset($courseconfig['url'])) {
			$config['url'] = $courseconfig['url'];
		} elseif (isset($CFG->filter_pod_url) && ($CFG->filter_pod_url != null)) {
			$config['url'] = $CFG->filter_pod_url;
		}

		// Quick verification if url are not in the text to filter, to prevent unnecessary work then
		if (stripos($text, $config['url']) === false) {
			return $text;
		}

		// Depending on the existence or not of local parameters, contextual and then generals, we defined the url's parameters values
		if (isset($this->localconfig['size'])) {
			$config['size'] = $this->localconfig['size'];
		} elseif (isset($courseconfig['size'])) {
			$config['size'] = $courseconfig['size'];
		} elseif (isset($CFG->filter_pod_size) && ($CFG->filter_pod_size != null)) {
			$config['size'] = $CFG->filter_pod_size;
		}

		if (isset($this->localconfig['width'])) {
			$config['width'] = $this->localconfig['width'];
		} elseif (isset($courseconfig['width'])) {
			$config['width'] = $courseconfig['width'];
		} elseif (isset($CFG->filter_pod_width) && ($CFG->filter_pod_width != null)) {
			$config['width'] = $CFG->filter_pod_width;
		}

		if (isset($this->localconfig['height'])) {
			$config['height'] = $this->localconfig['height'];
		} elseif (isset($courseconfig['height'])) {
			$config['height'] = $courseconfig['height'];
		} elseif (isset($CFG->filter_pod_height) && ($CFG->filter_pod_height != null)) {
			$config['height'] = $CFG->filter_pod_height;
		}

		//DAPI FIX
		$config['height'] = "100%";
		$config['width'] = "100%";

		//var_dump($config);die;
		
		// We stock values into the localconfig variable to recover them in the callback function later
		$this->localconfig['config'] = $config;

		$matches = array();

		// Regular expression for defined a standard pod's url and avoid those already contained in a iframe
		$word = addslashes($config['url']);
		$text = htmlspecialchars_decode($text);
		
		//big clean before process
		$text = str_replace('<a href="http://videos.univ-grenoble-alpes.fr/','<a href="https://videos.univ-grenoble-alpes.fr/',$text);
		$text = str_replace('<iframe src="http://videos.univ-grenoble-alpes.fr/','<iframe src="https://videos.univ-grenoble-alpes.fr/',$text);
		/*-----------------------------*/
		/* traitement injection iframe */
		/*-----------------------------*/
		$list_iframe = [];
		$indice = 0;

		while (strpos($text,'<iframe'/* src="https://videos.univ-grenoble-alpes.fr/'*/) !== false) {
			$debutiframe = strpos($text,'<iframe'/* src="https://videos.univ-grenoble-alpes.fr/'*/);
			$finiframe = strpos($text,'</iframe>')+9;

			$thisiframe = substr($text, $debutiframe, $finiframe - $debutiframe);
			$text = str_replace($thisiframe, "markeuriframe".$indice."&&", $text);
			
			$list_iframe["markeuriframe".$indice."&&"] = $thisiframe;
			$indice++;


		}
/*
		//in case of IFRAME at the first position
		$text_injection_first = "";
		
		if($pos_frame_first = strpos($text,'<iframe src="https://videos.univ-grenoble-alpes.fr/') != false) {
			$pos_frame_first_deux = strpos($text,'</iframe>')+9;
			
			$text_injection_first = substr($text,0,$pos_frame_first_deux);

			$text = substr($text, $pos_frame_first_deux, strlen($text) -1 - $pos_frame_first_deux);
		}
*/
		/*-----------------------------*/
        /*  traitement injection href  */
        /*-----------------------------*/
		$list_href = [];
		$indice = 0;
		
		while (strpos($text,'<a href="https://videos.univ-grenoble-alpes.fr/') !== false) {
			$debuthref = strpos($text,'<a href="https://videos.univ-grenoble-alpes.fr/');
			$finherf = strpos($text,'</a>',$debuthref)+4;
			
			$thishref = substr($text, $debuthref, $finherf - $debuthref);
			$text = str_replace($thishref, "markeurhref".$indice."&&", $text);
			
			$list_href["markeurhref".$indice."&&"] = $thishref;
			$indice++;

		}
/*
		if(strpos($text,'href="https://videos.univ-grenoble-alpes.fr/') != false) {
			$pos_frame_first_deux = strpos($text,'</a>')+4;
			
			$text_injection_first = substr($text,0,$pos_frame_first_deux);

			$text = substr($text, $pos_frame_first_deux, strlen($text) -1 - $pos_frame_first_deux);
		}
*/
		// Prevent tag a href or video source
		//$text = preg_replace('/(<a href="|<video.*><source src=")(.*)(">.*<\/a>|">.*<\/video>)/', '$2', $text);
		$text = preg_replace('/<(a.*href="|video.*src=")((https?)?:?\/\/videos\.univ\-grenoble\-alpes\.fr\/video\/[\w\-]+\/)">([\w\-\s\/\.^<]+)<\/(a|video)>/', '$2', $text);
		$iframetagpattern	= '(?P<ifr>iframe\s+src\s*=\s*")?';
		$podpattern 		= '((?:https?\:)?(?:\/\/)?(?P<pod>'.$word.'\/[a-zA-Z\d\-\/_]*(video|video_priv)\/([a-zA-Z\d\-\/_]+|[a-zA-Z\d\-_]+\/)))';
		
		$parampattern		= '(?:([(\?|\&)a-zA-Z_]*=)([a-zA-Z\d]*))?';

		// They cannot have more of 4 parameters in a pod's video url
		$pat = '('.$iframetagpattern.$podpattern.$parampattern.$parampattern.$parampattern.$parampattern.')'; //var_dump($text);die;
		// We run the replace :
		$text = preg_replace_callback($pat, array(&$this, 'filter_pod::filter_process_pod'), $text, -1, $cpt);

		$haystack = $text;
		$needle = '<iframe src="//videos.univ-grenoble-alpes.fr/';
		$needle2 = '</iframe>';

		$pos1 = strpos($haystack, $needle);
		$pos1b = strpos($haystack, $needle2);

		if ($pos1 !== false) {
			$subpositions = array();

			array_push($subpositions, array ("begin_sub" => $pos1, "end_sub" => $pos1b));

			$currentpos = $pos1;
			$currentpos2 = $pos1b;

			while ($currentpos !== false ) {
				$posx = strpos($haystack, $needle, $currentpos + strlen($needle));
				$posxb = strpos($haystack, $needle2, $currentpos2 + strlen($needle2));

				if ($posx !== false) {
					array_push($subpositions, array ("begin_sub" => $posx, "end_sub" => $posxb));
				}

				$currentpos = $posx;
				$currentpos2 = $posxb;
			}

		}

		$begin_text_position = 0;
		$total_return = "";

		foreach ($subpositions as $sp) {
			$subiframe = substr($text, $sp["begin_sub"], ($sp["end_sub"]-$sp["begin_sub"])+9);
			$begintextbeforeiframe = substr($text, $begin_text_position, intval($sp["begin_sub"] - $begin_text_position));		

			$subiframe = "<div style='max-width: min(1000px,80vh*16/9)'>".
			"<div style='position: relative;width: 100%;height: 0;padding-bottom: 56.25%;'>".
			str_replace('style="padding: 0; margin: 0; border: 0" allowfullscreen', 'style="padding: 0;margin: 0;border:0;position: absolute;width: 100%;height: 100%;left: 0;top: 0" allow="fullscreen"', $subiframe)
			."</div></div>";

			$total_return .=  $begintextbeforeiframe.$subiframe;
			$begin_text_position = $sp["end_sub"]+9;

		}

		$endtextafteriframe = substr($text, intval($sp ["end_sub"]), ((strlen($text))- intval($sp ["end_sub"])));

		//replace href at right place
		foreach ($list_href as $klh => $lh) {
			$text_injection_first = str_replace($klh,$lh,$text_injection_first);

		}
		foreach ($list_href as $klh => $lh) {
			$total_return = str_replace($klh,$lh,$total_return);

		}
		foreach ($list_href as $klh => $lh) {
			$endtextafteriframe = str_replace($klh,$lh,$endtextafteriframe);

		}

		//replace iframe at right place
		foreach ($list_iframe as $kli => $li) {
			$text_injection_first = str_replace($kli,$li,$text_injection_first);

		}
		foreach ($list_iframe as $kli => $li) {
			$total_return = str_replace($kli,$li,$total_return);

		}
		foreach ($list_iframe as $kli => $li) {
			$endtextafteriframe = str_replace($kli,$li,$endtextafteriframe);

		}

		return $text_injection_first.$total_return.$endtextafteriframe;
	}

	/**
	 * Function that retrieve the preg_replace result and
	 * use the callback function to make the replace.
	 * It check if we already have a iframe. If we have that, we don't replace.
	 * It check also if we have a link (a href), to properly replace the tag.
	 * @param array $matches an array that contains the captured regular expressions
	 * @return string the text of the iframe that replace the url
	 */
	function filter_process_pod($matches) {
		// We don't filter a pod's url already in a iframe
		if ($matches['ifr']) {
			return $matches[0];
		} else {
			return replace_url($matches, $this->localconfig['config']);
		}
	}
}

/**
 * Function that return the text with the iframe for replace the original
 * 
 * @param array $matches an array that contains the captured regular expressions
 * @param array $config an array that contains the default parameters for the url
 * @return string the text of the iframe that replace the video url
 */
function replace_url($matches, $config) {

	$u = $matches['pod'];
	
	// By default, we defined the values according to the filter configuration in the activity
	$width 		= ' width="'.$config['width'].'" ';
	$height 	= ' height="'.$config['height'].'" ';
	$size 		= '&size='.$config['size'];
	$autoplay	= '';
	$start		= '';
	$interactive= '';

	// We retrieve the possible parameters in the video url 
	while(list(, $m)=each($matches)) {
		switch($m) {
			case "&start=":
			case "?start=":
				$start		= "&start=".current($matches);
				break;
			case "&size=":
			case "?size=":
				$size 		= "&size=".current($matches);
				break;
			case "&autoplay=":
			case "?autoplay=":
				$autoplay 	= "&autoplay=".current($matches);
				break;
			case "&interactive=":
			case "?interactive=":
				$interactive 	= "&interactive=".current($matches);
				if(current($matches)=="true") {
				    $width 		= ' width="'.$config['width_interactive'].'" ';
	                $height 	= ' height="'.$config['height_interactive'].'" ';
	            }
				break;
		}
	}

	// We return the filtered url in a iframe with all the parameters
	return '<iframe src="//'.$u.'?is_iframe=true'.$size.$start.$autoplay.$interactive.'"'.$width.$height.' style="padding: 0; margin: 0; border: 0" allowfullscreen></iframe>';
}

/**
 * https://docs.moodle.org/dev/Filter_enable/disable_by_context#Getting_filter_configuration
 *
 * Get the list of active filters, in the order that they should be used
 * for a particular context.
 *
 * @param object $context a context
 * @return array an array where the keys are the filter names and the values are any local
 *      configuration for that filter, as an array of name => value pairs
 *      from the filter_config table. In a lot of cases, this will be an
 *      empty array.
 */
function get_active_filters($contextid) {
    global $DB;
    
    $sql = "SELECT fc.id, active.FILTER, fc.name, fc.VALUE
            FROM (SELECT f.FILTER
            FROM {filter_active} f
            JOIN {context} ctx ON f.contextid = ctx.id
            WHERE ctx.id IN ($contextid) AND f.FILTER LIKE 'podlille1'
            GROUP BY FILTER
            HAVING MAX(f.active * ctx.depth) > -MIN(f.active * ctx.depth)
            ORDER BY MAX(f.sortorder)) active
            LEFT JOIN {filter_config} fc ON fc.FILTER = active.FILTER AND fc.contextid = $contextid";
    
    $courseconfig = array();
    
    if ($results = $DB->get_records_sql($sql, null)) {
        // On récupère les paramètres du filtre, locaux au contexte dont l'ID a été passé en paramètre
        foreach ($results as $res) {
            if ($res->filter=="podlille1") {
                switch($res->name) {
                case "url":
                    $courseconfig['url']   = $res->value;
                    break;
                case "size":
                    $courseconfig['size']  = $res->value;
                    break;
                case "height":
                    $courseconfig['height']= $res->value;
                    break;
                case "width":
                    $courseconfig['width'] = $res->value;
                    break;
                }
            }
        }
    }
    
    return $courseconfig;
}

?>
