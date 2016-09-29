<?php

namespace Craft;

/**
 * Ingredients Fieldtype
 *
 * Allows entries to select an associated poll
 */
class Polls_PollFieldType extends BaseFieldType
{
	/**
	 * Get the name of this fieldtype
	 */
	public function getName()
	{
		return Craft::t('Poll');
	}

	/**
	 * Get this fieldtype's column type.
	 *
	 * @return mixed
	 */
	public function defineContentAttribute()
	{
		// "Mixed" represents a "text" column type, which can be used to store arrays etc.
		return AttributeType::Mixed;
	}

	/**
	 * Get this fieldtype's form HTML
	 *
	 * @param  string $name
	 * @param  mixed  $value
	 * @return string
	 */
	public function getInputHtml($name, $value)
	{
		// call our service layer to get a current list of polls
		$polls = craft()->polls->getAllPolls();

		$options = array();
		foreach ($polls as $poll) 
		{
			$options[] = array(
				'label' => $poll->name,
				'value' => $poll->id,
			);
		}

		$id = craft()->templates->formatInputId($name);

		return craft()->templates->render('polls/_fieldtypes/polls', array(
			'id'        => $id,
			'name'      => $name,
			'options'   => $options,
			'values'    => $value,
		));
	}
}