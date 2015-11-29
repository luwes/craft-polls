<?php
namespace Craft;

/**
 * Polls - Question record
 */
class Polls_QuestionRecord extends BaseRecord
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
		return 'polls_questions';
	}

	/**
	 * @inheritDoc BaseRecord::defineRelations()
	 *
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'element' 				=> array(static::BELONGS_TO, 'ElementRecord', 'id', 'required' => true, 'onDelete' => static::CASCADE),
			'poll' 						=> array(static::BELONGS_TO, 'Polls_PollRecord', 'required' => true, 'onDelete' => static::CASCADE),
			'type'    				=> array(static::BELONGS_TO, 'Polls_QuestionTypeRecord', 'onDelete' => static::CASCADE),
			'options' 				=> array(static::HAS_MANY, 'Polls_OptionRecord', 'questionId'),
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
			'sortOrder' 				=> AttributeType::SortOrder,
			'multipleOptions'		=> array(AttributeType::Bool, 'default' => false),
			'multipleVotes'			=> array(AttributeType::Bool, 'default' => false),
			'answerRequired'		=> array(AttributeType::Bool, 'default' => true),
		);
	}
}
