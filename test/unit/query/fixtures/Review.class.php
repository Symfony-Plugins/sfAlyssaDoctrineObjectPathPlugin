<?php

class Review extends Doctrine_Record
{
  public function setTableDefinition()
  {
    $this->setTableName('review');
    $this->hasColumn('id', 'integer', 10, array('primary' => true, 'autoincrement' => true));
    $this->hasColumn('name', 'string', 255);
    $this->hasColumn('book_id', 'string', 255);
  }

  public function setUp()
  {
    $this->hasOne('Book', array(
      'local' => 'book_id',
      'foreign' => 'id'
        )
    );
  }
}