<?php
namespace Craft;

/**
 * Polls - Answer record
 */
class Polls_AnswerRecord extends BaseRecord
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
		return 'polls_answers';
	}

	/**
	 * @inheritDoc BaseRecord::defineRelations()
	 *
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'option' => array(static::BELONGS_TO, 'Polls_OptionRecord', 'onDelete' => static::CASCADE),
			'user' => array(static::BELONGS_TO, 'UserRecord', 'onDelete' => static::CASCADE),
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
			'text'				=> AttributeType::String,
			'name'				=> AttributeType::String, // for guests
			'email'				=> AttributeType::Email,	// for guests
			'ipAddress'		=> AttributeType::String,
			'userAgent'		=> AttributeType::String,
		);
	}
}
