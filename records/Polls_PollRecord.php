<?php
namespace Craft;

/**
 * Polls - Poll record
 */
class Polls_PollRecord extends BaseRecord
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
		return 'polls';
	}

	/**
	 * @inheritDoc BaseRecord::defineRelations()
	 *
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'locales' => array(static::HAS_MANY, 'Polls_PollLocaleRecord', 'pollId'),
			'questions' => array(static::HAS_MANY, 'Polls_QuestionRecord', 'pollId'),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('name'), 'unique' => true),
			array('columns' => array('handle'), 'unique' => true),
		);
	}

	/**
	 * @return array
	 */
	public function scopes()
	{
		return array(
			'ordered' => array('order' => 'name'),
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
			'name'          					=> array(AttributeType::Name, 'required' => true),
			'handle'        					=> array(AttributeType::Handle, 'required' => true),
		);
	}
}
