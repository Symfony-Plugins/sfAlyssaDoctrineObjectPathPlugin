<?php

class Book extends Doctrine_Record
{
  public function setTableDefinition()
  {
    $this->setTableName('book');
    $this->hasColumn('id', 'integer', 10, array('primary' => true, 'autoincrement' => true));
    $this->hasColumn('name', 'string', 255);
    $this->hasColumn('author_id', 'string', 255);
  }

  public function setUp()
  {
  	$this->hasOne('Author', array(
  		'local' => 'author_id',
  		'foreign' => 'id'
  	    )
  	);
  }
}
