<?php
/**
* @package		plg_captcha_qa (Plugin Captcha Q&A)
* @copyright	(C) 2013-2026 RJCreations. All rights reserved.
* @license		GNU General Public License version 3 or later; see LICENSE.txt
* @since		1.5.2
*/
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/** @var array $displayData */
$questions = $displayData['questions'] ?? [];
$customnum = $displayData['customnum'] ?? '';
?>
<style>
.complex-data-wrapper {
	padding: 0 .5rem;
}
.qaq-rows {
	display: grid;
	grid-template-columns: 1fr 10rem;
	justify-content: start;
	column-gap: 1rem;
	row-gap: .4rem;
}
form input {
	font-size: inherit;
}
.qaq-rows input {
	width: 100%;
}
.bold-text {
	font-weight: bold;
}
.btns {
	display: flex;
	justify-content: space-between;
	padding-bottom: 1rem;
	gap: 1rem;
}
</style>
<div class="complex-data-wrapper"><form id="qaq-form">
	<div class="btns">
		<input type="text" name="FN" value="<?=$customnum?>">
		<button onclick="save()">Save</button>
	</div>
	<div id="qaq-rows" class="qaq-rows">
		<div><span class="bold-text">Question</span> <button onclick="addQ(event)"> + </button></div><div class="bold-text">Answer(s)</div>
<?php
foreach ($questions as $q => $a) {
	echo '<div><input type="text" name="Q[]" value="'.$q.'"></div><div><input type="text" name="A[]" value="'.$a.'"></div>';
}
echo '</div><input type="hidden" name="QN" value="'.$customnum.'"></div>';
?>
</form></div>
<script>
const addQ = (e) => {
	e.preventDefault();
	const rows = document.getElementById('qaq-rows');
	const elm1 = document.createElement('div');
	elm1.innerHTML = '<input type="text" name="Q[]" value="">';
	const elm2 = document.createElement('div');
	elm2.innerHTML = '<input type="text" name="A[]" value="">';
	const refNode = rows.children[2];
	rows.insertBefore(elm1, refNode);
	rows.insertBefore(elm2, refNode);
};
const save = () => {
	const form = document.getElementById('qaq-form');
	const formData = new FormData(form);
	fetch('index.php?option=com_ajax&group=captcha&plugin=qa&format=json&action=save', {
		method: 'POST',
		body: formData,
		headers: {
			'X-Requested-With': 'XMLHttpRequest'
		}
	})
	.then(response => response.json())
	.then(result => {
		console.log(result);
		if (result.success) {
			window.parent.postMessage('saved');
		} else {
			alert('Error: ' + result.message);
		}
	})
	.catch(error => {
		alert('Save failed: ' + error.message);
	})
	.finally(() => {
		// Re-enable button
	//	submitBtn.disabled = false;
	//	spinner.classList.add('d-none');
	});
}
</script>
