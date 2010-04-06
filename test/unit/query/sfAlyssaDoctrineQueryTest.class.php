<?php

/*
 * (c) 2010 - Cooperativa de Trabajo Alyssa Limitada
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once(dirname(__FILE__).'/../../bootstrap/unit.php');

// initialize Doctrine
$autoload = sfSimpleAutoload::getInstance(sys_get_temp_dir().DIRECTORY_SEPARATOR.sprintf('sf_autoload_unit_doctrine_%s.data', md5(__FILE__)));
$autoload->addDirectory(realpath($_SERVER['SYMFONY'].'/plugins/sfDoctrinePlugin/lib'));
$autoload->register();

class MyProjectConfiguration extends sfProjectConfiguration {
  public function setup()
  {
    $this->setPluginPath('sfAlyssaDoctrineObjectPathPlugin', dirname(__FILE__).'/../../../../sfAlyssaDoctrineObjectPathPlugin');

    $this->enablePlugins('sfAlyssaDoctrineObjectPathPlugin', 'sfDoctrinePlugin') ;
  }
}

$configuration = new MyProjectConfiguration(dirname(__FILE__).'/../../lib', new sfEventDispatcher());
$database = new sfDoctrineDatabase(array('name' => 'doctrine', 'dsn' => 'sqlite::memory:'));

Doctrine_Core::createTablesFromModels(dirname(__FILE__).'/fixtures');

// initialize data
//Doctrine_Core::loadData(dirname(__FILE__).'/fixtures/data.yml');


$t = new lime_test(5);

$q = Doctrine_Query::create();

// load correct class query
$t->diag('sfAlyssaDoctrineQuery');
$t->isa_ok($q, 'sfAlyssaDoctrineQuery', 'Doctrine is set up with correct query class \'sfAlyssaDoctrineQuery\'');

// join
$t->diag('->joinByObjectPath()');

$q->from('Review')->joinByObjectPath('Book.Author');
$expectated = ' FROM Review LEFT JOIN Review.Book Review___Book LEFT JOIN Review___Book.Author Review___Book___Author';
$t->is($q->getDql(), $expectated, '->joinByObjectPath() generate correct DQL query');

// where
$t->diag('->whereProperyPath()');
$q = Doctrine_Query::create()
      ->from('Review')
      ->joinByObjectPath('Book.Author')
      ->whereProperyPath('Book.Author.name = ?', 'Fabien Potencier');
$expectated = ' FROM Review LEFT JOIN Review.Book Review___Book LEFT JOIN Review___Book.Author Review___Book___Author WHERE Review___Book___Author.name = ?';
$t->is($q->getDql(), $expectated, '->whereProperyPath() generate correct DQL query');

// orderBy
$t->diag('->orderByProperyPath()');
$q = Doctrine_Query::create()
      ->from('Review')
      ->joinByObjectPath('Book.Author')
      ->orderByProperyPath('Book.Author.name');
$expectated = ' FROM Review LEFT JOIN Review.Book Review___Book LEFT JOIN Review___Book.Author Review___Book___Author ORDER BY Review___Book___Author.name';
$t->is($q->getDql(), $expectated, '->orderByProperyPath() generate correct DQL query');

// test with data
$t->todo('->execute()');

