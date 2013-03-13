<?php

require_once MODX_CORE_PATH.'model/modx/processors/resource/getnodes.class.php';

class msCategoryGetNodesProcessor  extends modResourceGetNodesProcessor {
	public $nodes = array();
	public $sort = 'id';
	public $dir = 'ASC';
	public $pid;
	public $parent_id;

	public function initialize() {
		$initialize = parent::initialize();
		$this->pid = $this->getProperty('currentResource');
		if ($res = $this->modx->getObject('msProduct', $this->pid)) {
			$this->parent_id = $res->get('parent');
		}
		return $initialize;
	}

	/**
	 * Get the query object for grabbing Resources in the tree
	 * @return xPDOQuery
	 */
	public function getResourceQuery() {
		$resourceColumns = array(
			'id'
			,'pagetitle'
			,'longtitle'
			,'alias'
			,'description'
			,'parent'
			,'published'
			,'deleted'
			,'isfolder'
			,'menuindex'
			,'menutitle'
			,'hidemenu'
			,'class_key'
			,'context_key'
		);
		$this->itemClass= 'modResource';
		$c= $this->modx->newQuery($this->itemClass);
		$c->leftJoin('modResource', 'Child', array('modResource.id = Child.parent'));
		$c->leftJoin('msCategoryMember', 'Member', array('modResource.id = Member.category_id AND Member.product_id = '.$this->pid));
		$c->select($this->modx->getSelectColumns('modResource', 'modResource', '', $resourceColumns));
		$c->select(array(
			'childrenCount' => 'COUNT(Child.id)',
			'member' => 'category_id'
		));
		$c->where(array(
			'context_key' => $this->contextKey
			,'show_in_tree' => true
			,'isfolder' => true
		));
		if (empty($this->startNode) && !empty($this->defaultRootId)) {
			$c->where(array(
				'id:IN' => explode(',', $this->defaultRootId),
				'parent:NOT IN' => explode(',', $this->defaultRootId),
			));
		} else {
			$c->where(array(
				'parent' => $this->startNode,
			));
		}
		$c->groupby($this->modx->getSelectColumns('modResource', 'modResource', '', $resourceColumns), '');
		$c->sortby('modResource.'.$this->getProperty('sortBy'),$this->getProperty('sortDir'));

		return $c;
	}


	public function prepareContextNode(modContext $context) {
		$context->prepare();
		return array(
			'text' => $context->get('key')
			,'id' => $context->get('key') . '_0'
			,'pk' => $context->get('key')
			,'ctx' => $context->get('key')
			,'leaf' => false
			,'cls' => 'icon-context'
			,'qtip' => $context->get('description') != '' ? strip_tags($context->get('description')) : ''
			,'type' => 'modContext'
		);
	}


	public function prepareResourceNode(modResource $resource) {
		$qtipField = $this->getProperty('qtipField');
		$nodeField = $this->getProperty('nodeField');
		$noHref = $this->getProperty('noHref',false);

		$hasChildren = (int)$resource->get('childrenCount') > 0 && $resource->get('hide_children_in_tree') == 0 ? true : false;

		$class = array();
		$class[] = 'icon-'.strtolower(str_replace('mod','',$resource->get('class_key')));
		$class[] = $resource->isfolder ? 'icon-folder' : 'x-tree-node-leaf icon-resource';
		if (!$resource->get('published')) $class[] = 'unpublished';
		if ($resource->get('deleted')) $class[] = 'deleted';
		if ($resource->get('hidemenu')) $class[] = 'hidemenu';

		$qtip = '';
		if (!empty($qtipField)) {
			$qtip = '<b>'.strip_tags($resource->$qtipField).'</b>';
		} else {
			if ($resource->longtitle != '') {
				$qtip = '<b>'.strip_tags($resource->longtitle).'</b><br />';
			}
			if ($resource->description != '') {
				$qtip = '<i>'.strip_tags($resource->description).'</i>';
			}
		}

		$idNote = $this->modx->hasPermission('tree_show_resource_ids') ? ' <span dir="ltr">('.$resource->id.')</span>' : '';
		$itemArray = array(
			'text' => strip_tags($resource->$nodeField).$idNote,
			'id' => $resource->context_key . '_'.$resource->id,
			'pk' => $resource->id,
			'cls' => implode(' ',$class),
			'type' => 'modResource',
			'classKey' => $resource->class_key,
			'ctx' => $resource->context_key,
			'hide_children_in_tree' => $resource->hide_children_in_tree,
			'qtip' => $qtip,
			'checked' => !empty($resource->member) || $resource->id == $this->parent_id ? true : false
			,'disabled' =>  $resource->id == $this->parent_id ? true : false
		);
		if (!$hasChildren) {
			$itemArray['hasChildren'] = false;
			$itemArray['children'] = array();
			$itemArray['expanded'] = true;
		} else {
			$itemArray['hasChildren'] = true;
		}

		if ($itemArray['classKey'] != 'msCategory') {
			unset($itemArray['checked']);
		}

		return $itemArray;
	}
	/*
	public function getNodes($id) {
		$c = $this->modx->newQuery('msCategory', array(array('parent' => $id, 'class_key' => 'msCategory')));
		$c->select('id,pagetitle as text,parent');
		$c->sortby($this->sort,$this->dir);

		if ($c->prepare() && $c->stmt->execute()) {
			$items = $c->stmt->fetchAll(PDO::FETCH_ASSOC);

			foreach ($items as $item) {
				$item['leaf'] = true;
				$item['checked'] = false;
				$item['cls'] = '';
				$item['classKey'] = 'msCategory';
				$item['draggable'] = false;

				$this->nodes[] = $item;
			}
		}
	}
	*/

}

return 'msCategoryGetNodesProcessor';