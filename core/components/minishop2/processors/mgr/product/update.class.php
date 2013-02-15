<?php

require_once MODX_CORE_PATH.'model/modx/modprocessor.class.php';
require_once MODX_CORE_PATH.'model/modx/processors/resource/update.class.php';

class msProductUpdateProcessor extends modResourceUpdateProcessor {
	public $classKey = 'msProduct';
	public $languageTopics = array('resource','minishop2:default');
	public $permission = 'msproduct_save';
	public $objectType = 'resource';
	public $beforeSaveEvent = 'OnBeforeDocFormSave';
	public $afterSaveEvent = 'OnDocFormSave';

	/**
	 * Handle formatting of various checkbox fields
	 * @return void
	 */
	public function handleCheckBoxes() {
		parent::handleCheckBoxes();
		$this->setCheckbox('new');
		$this->setCheckbox('popular');
		$this->setCheckbox('favorite');
	}

	/**
	 * Set publishedon date if publish change is different
	 * @return int
	 */
	public function checkPublishedOn() {
		$published = $this->getProperty('published',null);
		if ($published !== null && $published != $this->object->get('published')) {
			if (empty($published)) { /* if unpublishing */
				$this->setProperty('publishedon',0);
				$this->setProperty('publishedby',0);
			} else { /* if publishing */
				$publishedOn = $this->getProperty('publishedon',null);
				$this->setProperty('publishedon',!empty($publishedOn) ? strtotime($publishedOn) : time());
				$this->setProperty('publishedby',$this->modx->user->get('id'));
			}
		} else { /* if no change, unset publishedon/publishedby */
			if (empty($published)) { /* allow changing of publishedon date if resource is published */
				$this->unsetProperty('publishedon');
				$this->unsetProperty('publishedby');
			}
		}
		return $this->getProperty('publishedon');
	}

	/**
	 * {@inheritDoc}
	 * @return boolean
	 */
	public function checkFriendlyAlias() {
		parent::checkFriendlyAlias();
		foreach ($this->modx->error->errors as $k => $v) {
			if ($v['id'] == 'alias') {
				unset($this->modx->error->errors[$k]);
				$this->setProperty('alias', $this->object->id);
			}
		}
	}

	/**
	 * {@inheritDoc}
	 * @return boolean
	 */
	public function beforeSave() {
		$this->object->set('isfolder', 0);

		return parent::beforeSave();
	}

}

return 'msProductUpdateProcessor';