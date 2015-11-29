<?php
namespace Craft;

class Polls_OptionElementType extends BaseElementType
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc IComponentType::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Poll option');
	}

	/**
	 * @inheritDoc IElementType::hasContent()
	 *
	 * @return bool
	 */
	public function hasContent()
	{
		return true;
	}

	/**
	 * @inheritDoc IElementType::hasTitles()
	 *
	 * @return bool
	 */
	public function hasTitles()
	{
		return true;
	}

	/**
	 * @inheritDoc IElementType::isLocalized()
	 *
	 * @return bool
	 */
	public function isLocalized()
	{
		return true;
	}

	/**
	 * @inheritDoc IElementType::getSources()
	 *
	 * @param string|null $context
	 *
	 * @return array|false
	 */
	public function getSources($context = null)
	{
		$sources = array(
			'*' => array(
				'label' => Craft::t('All')
			)
		);

		return $sources;
	}

	/**
	 * @inheritDoc IElementType::getAvailableActions()
	 *
	 * @param string|null $source
	 *
	 * @return array|null
	 */
	public function getAvailableActions($source = null)
	{
		// Now figure out what we can do with these
		$actions = array();

		$deleteAction = craft()->elements->getAction('Delete');
		$deleteAction->setParams(array(
			'confirmationMessage' => Craft::t('Are you sure you want to delete the selected options?'),
			'successMessage'      => Craft::t('Options deleted.'),
		));
		$actions[] = $deleteAction;

		// Allow plugins to add additional actions
		$allPluginActions = craft()->plugins->call('addPollOptionActions', array($source), true);

		foreach ($allPluginActions as $pluginActions)
		{
			$actions = array_merge($actions, $pluginActions);
		}

		return $actions;
	}

	/**
	 * @inheritDoc IElementType::defineSortableAttributes()
	 *
	 * @retrun array
	 */
	public function defineSortableAttributes()
	{
		$attributes = array(
			'title'      => Craft::t('Title'),
		);

		// Allow plugins to modify the attributes
		craft()->plugins->call('modifyPollOptionSortableAttributes', array(&$attributes));

		return $attributes;
	}

	/**
	 * @inheritDoc IElementType::defineTableAttributes()
	 *
	 * @param string|null $source
	 *
	 * @return array
	 */
	public function defineTableAttributes($source = null)
	{
		$attributes = array(
			'title' => Craft::t('Title'),

		);

		// Allow plugins to modify the attributes
		craft()->plugins->call('modifyPollOptionTableAttributes', array(&$attributes, $source));

		return $attributes;
	}

	/**
	 * @inheritDoc IElementType::getTableAttributeHtml()
	 *
	 * @param BaseElementModel $element
	 * @param string           $attribute
	 *
	 * @return mixed|null|string
	 */
	public function getTableAttributeHtml(BaseElementModel $element, $attribute)
	{
		// First give plugins a chance to set this
		$pluginAttributeHtml = craft()->plugins->callFirst('getPollOptionTableAttributeHtml', array($element, $attribute), true);

		if ($pluginAttributeHtml !== null)
		{
			return $pluginAttributeHtml;
		}

		switch ($attribute)
		{

			default:
			{
				return parent::getTableAttributeHtml($element, $attribute);
			}
		}
	}

	/**
	 * @inheritDoc IElementType::defineCriteriaAttributes()
	 *
	 * @return array
	 */
	public function defineCriteriaAttributes()
	{
		return array(
			'questionId' => AttributeType::Mixed,
			'question' => AttributeType::Mixed,
		);
	}

	/**
	 * @inheritDoc IElementType::modifyElementsQuery()
	 *
	 * @param DbCommand            $query
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return mixed
	 */
	public function modifyElementsQuery(DbCommand $query, ElementCriteriaModel $criteria)
	{
		$query->addSelect('po.questionId')
			->join('polls_options po', 'po.id = elements.id');

		if ($criteria->question)
		{
			if ($criteria->section instanceof Polls_QuestionModel)
			{
				$criteria->questionId = $criteria->question->id;
				$criteria->question = null;
			}
		}

		if ($criteria->questionId)
		{
			$query->andWhere(DbHelper::parseParam('po.questionId', $criteria->questionId, $query->params));
		}
	}

	/**
	 * @inheritDoc IElementType::populateElementModel()
	 *
	 * @param array $row
	 *
	 * @return array
	 */
	public function populateElementModel($row)
	{
		return Polls_OptionModel::populateModel($row);
	}

	/**
	 * @inheritDoc IElementType::getEditorHtml()
	 *
	 * @param BaseElementModel $element
	 *
	 * @return string
	 */
	public function getEditorHtml(BaseElementModel $element)
	{
		$html = craft()->templates->renderMacro('_includes/forms', 'textField', array(
			array(
				'label'     => Craft::t('Title'),
				'locale'    => $element->locale,
				'id'        => 'title',
				'name'      => 'title',
				'value'     => $element->getContent()->title,
				'errors'    => $element->getErrors('title'),
				'first'     => true,
				'autofocus' => true,
				'required'  => true
			)
		));

		$html .= parent::getEditorHtml($element);

		return $html;
	}

}
