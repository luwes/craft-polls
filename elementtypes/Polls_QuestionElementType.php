<?php
namespace Craft;

class Polls_QuestionElementType extends BaseElementType
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
		return Craft::t('Poll question');
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
				'label' => Craft::t('All polls'),
				'defaultSort' => array('dateCreated', 'desc'),
			)
		);

		$sources['polls-heading'] = array('heading' => 'Groups');

		foreach (craft()->polls->getAllPolls() as $poll)
		{
			$key = 'poll:'.$poll->id;

			$sources[$key] = array(
				'label'    => Craft::t($poll->name),
				'criteria' => array('pollId' => $poll->id),
				'defaultSort' => array('dateCreated', 'desc'),
			);
		}

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
			'confirmationMessage' => Craft::t('Are you sure you want to delete the selected questions?'),
			'successMessage'      => Craft::t('Questions deleted.'),
		));
		$actions[] = $deleteAction;

		// Allow plugins to add additional actions
		$allPluginActions = craft()->plugins->call('addPollQuestionActions', array($source), true);

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
			'dateCreated'   => Craft::t('Created'),
			'title'      		=> Craft::t('Title'),
		);

		// Allow plugins to modify the attributes
		craft()->plugins->call('modifyPollQuestionSortableAttributes', array(&$attributes));

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
			'title' 				=> Craft::t('Title'),
			'id' 						=> Craft::t('Options'),
			'dateCreated'   => Craft::t('Created'),
		);

		if ($source == '*')
		{
			$attributes['poll'] = Craft::t('Poll');
		}

		// Allow plugins to modify the attributes
		craft()->plugins->call('modifyPollQuestionTableAttributes', array(&$attributes, $source));

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
		$pluginAttributeHtml = craft()->plugins->callFirst('getPollQuestionTableAttributeHtml', array($element, $attribute), true);

		if ($pluginAttributeHtml !== null)
		{
			return $pluginAttributeHtml;
		}

		switch ($attribute)
		{
			case 'id':
			{
				$question = craft()->polls_questions->getQuestionById($element->id);
				$optionsCount = count($question->options);
				$params = array(
					'url' => $question->getCpEditUrl().'/options',
					'count' => $optionsCount,
				);

				$html = '<a href="{url}">Edit options ({count})</a>';
				if ($optionsCount > 0) 
				{
					$html .= '<a class="menubtn options-menubtn" title="Options"></a>';
					$html .= '<div class="menu">';
					$html .= '<ul>';
					foreach ($question->options as $optionRecord) {
						$option = Polls_OptionModel::populateModel($optionRecord);
						$html .= '<li>';
						$html .= '<a href="'. $option->getCpEditUrl() .'">'. $option->title .'</a>';
						$html .= '</li>';
					}
					$html .= '</ul>';
					$html .= '</div>';
				}

				return HtmlHelper::encodeParams($html, $params);
			}

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
			'poll'   => AttributeType::Mixed,
			'pollId' => AttributeType::Mixed,
			'optionId' => AttributeType::Mixed,
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
		$query
			->addSelect('pq.pollId, pq.multipleOptions, pq.multipleVotes, pq.answerRequired')
			->join('polls_questions pq', 'pq.id = elements.id');

		if ($criteria->pollId)
		{
			$query->andWhere(DbHelper::parseParam('pq.pollId', $criteria->pollId, $query->params));
		}

		if ($criteria->poll)
		{
			$query->join('polls polls', 'polls.id = polls_questions.pollId');
			$query->andWhere(DbHelper::parseParam('polls.handle', $criteria->poll, $query->params));
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
		return Polls_QuestionModel::populateModel($row);
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
