<?php
namespace Craft;

/**
 * Polls - Option record
 */
class Polls_OptionRecord extends BaseRecord
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseRecord::getTableName()
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'polls_options';
	}

	/**
	 * @inheritDoc BaseRecord::defineRelations()
	 *
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'element'					=> array(static::BELONGS_TO, 'ElementRecord', 'id', 'required' => true, 'onDelete' => static::CASCADE),
			'type' 						=> array(static::BELONGS_TO, 'Polls_OptionTypeRecord', 'onDelete' => static::CASCADE),
			'question'				=> array(static::BELONGS_TO, 'Polls_QuestionRecord', 'required' => true, 'onDelete' => static::CASCADE),
			'answers'					=> array(static::HAS_MANY, 'Polls_AnswerRecord', 'optionId'),
		);
	}

	/**
	 * @inheritDoc BaseRecord::defineIndexes()
	 *
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('sortOrder')),
		);
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseRecord::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'sortOrder' => AttributeType::SortOrder,
		);
	}
}
