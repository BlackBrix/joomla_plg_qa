<?php
/**
* @package		plg_captcha_qa (Plugin Captcha Q&A)
* @copyright	(C) 2013-2026 RJCreations. All rights reserved.
* @license		GNU General Public License version 3 or later; see LICENSE.txt
* @since		1.5.4
*/
namespace RJCreations\Plugin\Captcha\Qa\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Layout\FileLayout;
use Joomla\Event\SubscriberInterface;

class Qa extends CMSPlugin implements SubscriberInterface
{
	protected $autoloadLanguage = true;
	protected $timecheck;
	protected $qandas = [];

	public function __construct ($subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();
		$this->timecheck = $this->params->get('time_check', 10, 'INT');
	}

	/*
	 * Initialise the captcha
	 * @param   string	$id	The id of the field.
	 * @return  Boolean	True on success, false otherwise
	 */
	public function onInit ($id=null)
	{
		$lang = Factory::getLanguage();
		$lang->load('custom' , dirname(__FILE__, 3), $lang->getTag(), true);
		$cstm = $this->params->get('customqa', '', 'STRING');
		if ($cstm) {
			$this->getQandas($cstm, true);
			return true;
		}
		$this->getQandas('en-GB');
		if ($lang->getTag() != 'en-GB') {
			$this->getQandas($lang->getTag());
		}
		return true;
	}

	/*
	 * Gets the challenge HTML
	 * @return  string  The HTML to be embedded in the form.
	 */
	public function onDisplay ($name, $id, $class)
	{
		if ($this->qandas) {
			$rq = (rand() % count($this->qandas));
			$tm = time();
			$sf = (($rq+1) * $tm) % 97;
			$fld = '<br><input type="text" class="form-control'.($class?' '.$class:'').'" id="'.$id.'" name="'.$name.'" required="required" aria-required="true" value="" />';
			$ccd = '<input type="hidden" name="captcha_code" value="'.($rq+1)."-{$tm}-{$sf}".'" />';
			$label = '<span>'.Text::_('PLG_CAPTCHA_QA_LABEL_PLEASE').'</span>';
			$q = key($this->qandas[$rq]);
			return $label.'<br>'.trim($q).$fld.$ccd;
		}
		$rq = (rand() % 9) + 1;
		$tm = time();
		$sf = ($rq * $tm) % 97;
		$fld = '<br><input type="text" class="form-control'.($class?' '.$class:'').'" id="'.$id.'" name="'.$name.'" required="required" aria-required="true" value="" />';
		$ccd = '<input type="hidden" name="captcha_code" value="'."{$rq}-{$tm}-{$sf}".'" />';
		$label = '<span>'.Text::_('PLG_CAPTCHA_QA_LABEL_PLEASE').'</span>';
		$qa = Text::_('PLG_CAPTCHA_QA_Q'.$rq);
		[$q, $a] = explode('|',$qa);
		return $label.'<br>'.trim($q).$fld.$ccd;
	}

	/*
	 * Calls an HTTP POST function to verify if the user's guess was correct
	 * @return  True if the answer is correct, false otherwise
	 */
	public function onCheckAnswer ($code)
	{
		// have to force an init here
		$this->onInit(0);

		$app = Factory::getApplication();
		$input = $app->getInput();
		$ccd = $input->get('captcha_code', '--', 'cmd');
		[$qn, $tm, $ck] = explode('-', $ccd);
		if ((int)$qn * (int)$tm % 97 !== (int)$ck) {
			$app->enqueueMessage(Text::_('PLG_CAPTCHA_QA_ERROR_GENERAL'), 'error');
			return false;
		}
		if (!$qn) {
			$app->enqueueMessage(Text::_('PLG_CAPTCHA_QA_ERROR_GENERAL'), 'error');
			return false;
		}
		if ($this->timecheck && ((time()-$tm) < $this->timecheck)) {
			$app->enqueueMessage(Text::_('PLG_CAPTCHA_QA_ERROR_NOT_HUMAN').' '.Text::_('PLG_CAPTCHA_QA_ERROR_TOO_QUICK'), 'error');
			return false;
		}
		$cas = $this->getQans($qn);
		if (in_array(trim($code), array_map(trim(...), $cas))) {
			return true;
		}
		$app->enqueueMessage(Text::_('PLG_CAPTCHA_QA_ERROR_NOT_HUMAN').' '.Text::_('PLG_CAPTCHA_QA_ERROR_INCORRECT'), 'error');
		return false;
	}

	public function onAjaxQa (): void
	{
		$app = Factory::getApplication();

		$input = $app->getInput();
		$indat = [$input->get->getArray(), $input->post->getArray()];
		file_put_contents('DADAT.txt', print_r($indat, true), FILE_APPEND);

		$post = $input->post->getArray();
		if ($post) {
			$this->saveQandas($post);
		} else {
			$this->sendForm($input->getString('itemid', 0));
		}
		$app->close();
	}

	private function loadComplexData ($id): array
	{
		$this->getQandas($id, true);
		if ($this->qandas) {
			$qans = [];
			foreach ($this->qandas as $qa) {
				foreach ($qa as $q=>$a) {
					$qans[$q] = implode(' | ',$a);
				}
			}
			return [
				'questions' => $qans,
				'customnum' => $id
			];
		}
		// Replace with your database queries or external API calls
		return [
			'questions' => [''=>''],
			'customnum' => $id
		];
	}

	private function getQans (string $qn)
	{
		if ($this->qandas) {
			return array_values($this->qandas[$qn-1])[0];
		}
		$qa = Text::_('PLG_CAPTCHA_QA_Q'.$qn);
		[$q, $a] = explode('|',$qa);
		return explode(',',trim($a));
	}

	private function getQandas ($ln, bool $cstm=false): void				//<<< @@@@@@@@@  fix logic
	{
		foreach (['/custom',''] as $subd) {
			if ($cstm) {
				$qaf = JPATH_ROOT.'/media/plg_captcha_qa'.$subd.'/custom/'.$ln;
			} else {
				$qaf = JPATH_ROOT.'/media/plg_captcha_qa/qalang'.$subd.'/qandas_'.$ln.'.json';
			}
			if (file_exists($qaf)) {
				try {
					$qas = json_decode(file_get_contents($qaf),true);
					$this->qandas = $qas;
					file_put_contents('QAS.txt',print_r($qas, true));
					return;
				} catch (\JsonException $e) {
				}
			}
		}
	}

	private function saveQandas (array $data): void
	{
		$qas = [];
		$cnt = count($data['Q']);
		for ($i=0; $i<$cnt; $i++) {
			$qas[] = [$data['Q'][$i] => array_map(trim(...), explode('|',$data['A'][$i]))];
		}
		file_put_contents(JPATH_ROOT.'/media/plg_captcha_qa/custom/'.$data['FN'], json_encode($qas, JSON_PRETTY_PRINT));
		echo json_encode(['success'=>true]);
	}

	private function sendForm ($qnum): void
	{
		$data = $this->loadComplexData($qnum);

		$basePath = JPATH_PLUGINS . '/captcha/qa/layouts';
		$layout = new FileLayout('modal.questions', $basePath);
		$htmlOutput = $layout->render([
			'questions' => $data['questions'],
			'customnum' => $data['customnum']
		]);
		echo $htmlOutput;
	}

	public static function getSubscribedEvents (): array
	{
		return ['onInit' => 'onInit', 'onDisplay' => 'onDisplay', 'onCheckAnswer' => 'onCheckAnswer', 'onAjaxQa' => 'onAjaxQa'];
	}

}
