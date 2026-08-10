<?php
/**
* @package		plg_captcha_qa (Plugin Captcha Q&A)
* @copyright	(C) 2013-2026 RJCreations. All rights reserved.
* @license		GNU General Public License version 3 or later; see LICENSE.txt
* @since		1.5.2
*/
namespace RJCreations\Plugin\Captcha\Qa\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\Filesystem\Folder;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class CustomqasField extends FormField
{
	protected $type = 'customqas';

	protected function getInput ()
	{
		$nam = $this->name;
		$val = $this->value;
		$files = Folder::files(JPATH_ROOT.'/media/plg_captcha_qa/custom');
		$ck = $val=='0' ? ' checked' : '';
		$html = '<div><label><input type="radio" name="'.$nam.'" value=""'.$ck.'> '.Text::_('PLG_CAPTCHA_QA_NO_CUSTOM').'</label></div>';
		foreach($files as $file) {
			$ck = $val==$file ? ' checked' : '';
			$html .= '<div><label><input type="radio" name="'.$nam.'" value="'.$file.'"'.$ck.'> '.$file.'</label> <span class="icon-edit" onclick="editQas(\''.$file.'\')"> </span></div>';
		}
		$html .= '<button type="button" class="btn btn-secondary" onclick="editQas()"><span class="icon-plus" aria-hidden="true"></span> Create</button>';
		return $html;
	}

}
