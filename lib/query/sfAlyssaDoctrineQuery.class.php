<?php
/*
 * (c) 2010 - Cooperativa de Trabajo Alyssa Limitada
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * sfAlyssaDoctrineQuery
 *
 * Adds to the custom Doctrine_Query class support for ObjectPath.
 * An ObjectPath is a chain of relationNames, seperated by a dot, resulting in
 * a joined object
 *
 * @package    lib
 * @subpackage query
 * @author     Sergio Fabian Vier <sergio.vier@alyssa-it.com>
 * @version    SVN: $Id$
 */
class sfAlyssaDoctrineQuery extends Doctrine_Query{
  const ALIAS_DELIMITER = '___';

  /**
   * create
   * returns a new Doctrine_Query object
   *
   * @see Doctrine_Query::create()
   *
   * @param Doctrine_Connection $conn  optional connection parameter
   * @param string $class              Query class to instantiate
   * @return Doctrine_Query
   */
  public static function create($conn = null, $class = null)
  {
    // Doctrine < 1.2 does not support customized query class
    // and factory method "create" brings only a Doctrine_Query instance.
    if (strpos(Doctrine::VERSION, '1.2') === false){
      return new sfAlyssaDoctrineQuery($conn);
    }

    return parent::create($conn, $class);

  }

  /**
   * Recursively performs join on the provided ObjectPaths
   *
   * @param  mixed  $objectPath one or more strings with objectPaths
   * @param  string $rootAlias optional, base alias for recursive generation
   * @return sfAlyssaDoctrineQuery
   */
  public function joinByObjectPath($objectPath, $rootAlias = null)
  {
    $objectPaths = is_array($objectPath) ? $objectPath : array($objectPath);

    // process all objectPaths
    foreach ($objectPaths as $objectPath)
    {
      // brake up the objectPath into an array of relationsNames
      $relationsNames = explode('.', $objectPath, 2);

      // resolve current object and the (first) relationName
      if (count($relationsNames)<1)
      {
        throw new Doctrine_Query_Exception('no relation provided');
      }

      // take an root alias if not defined
      if ($rootAlias === null){
        $rootAlias = $this->getRootAlias();
      }

      $relationName = $relationsNames[0];
      $alias = $this->translateObjectPathToAlias($relationName, $rootAlias);

      //$newJoin = !$this->hasAliasDeclaration($alias);
      $newJoin = !isset($this->join[$alias]);
      if ($newJoin)
      {
        $this->join[$alias] = true;
        //TODO: only leftjoin is supported
        $this->leftJoin($rootAlias.'.'.$relationName.' '.$alias);
      }

      // if more relations are provided, continue (recursively) parsing the objectPath
      if (isset($relationsNames[1]))
      {
        $this->joinByObjectPath($relationsNames[1], $alias);
      }
    }

    //fluid interface
    return $this;
  }

  /**
   * Resolves the database alias for an objectPath
   *
   * Basically this comes down to replacing all dots with an underscore
   * and preceeding it with the queryModel-alias
   *
   * so $reviewQuery->translateObjectPathToAlias('Book.Author')
   * becomes Review_Book_Author as the table-alias for Book-Author
   *
   * @param string $objectPath
   * @param string $rootAlias optional, base alias for recursive generation
   * @return string the constructed alias
   */
  public function translateObjectPathToAlias($objectPath, $rootAlias = null)
  {
    // brake up the objectPath into an array of relationsNames
    $relationsNames = explode('.', $objectPath, 2);

    // resolve current object and the (first) relationName
    if (count($relationsNames)<1)
    {
      throw new Doctrine_Query_Exception('no relation provided');
    }

    // take an root alias if not defined
    if ($rootAlias === null)
    {
      $rootAlias = $this->getRootAlias();
    }
    $relationName = $relationsNames[0];
    $alias = $rootAlias.self::ALIAS_DELIMITER.$relationName;

    // if more relations are provided, continue (recursively) parsing the objectPath
    if (isset($relationsNames[1]))
    {
      $alias = $this->translateObjectPathToAlias($relationsNames[1], $alias);
    }

    return $alias;
  }

  /**
   * Resolves the column name alias for an PropertyPath
   *
   * PropertyPaths are an optional ObjectPaths plus a PhpColumnName. So [Relation.]*PhpColumnName
   * @see translateObjectPathToAlias()
   *
   * @param string $columnName
   * @return string the constructed alias
   */
  protected function propertyPathToColumn($columnName){

    // check if an objectPath has been given
    $lastDot = strrpos($columnName, '.');
    if ($lastDot !== false)
    {
      // get the objectPath
      $objectPath = substr($columnName, 0, $lastDot);

      // and get Related Query Class
      $strRelated = $this->translateObjectPathToAlias($objectPath);
      $columnName = $strRelated.'.'.substr($columnName, $lastDot + 1);

    }

    return $columnName;

  }

  /**
   * Adds ProperyPaths to the WHERE part of the query.
   *
   * @param string $join         Query WHERE part
   * @param mixed $params        an array of parameters or a simple scalar
   * @return sfAlyssaDoctrineQuery
   */
  public function whereProperyPath($where, $params = array())
  {
    $expr = $this->propertyPathToColumn($where);

    return $this->where($expr, $params);
  }

  /**
   * Adds ProperyPaths to the WHERE part of the query.
   * <code>
   * $q->andWhere('u.birthDate > ?', '1975-01-01');
   * $q->andWhere('Book.Author.birthDate > ?', '1975-01-01');
   * </code>
   *
   * @param string $where Query WHERE part
   * @param mixed $params An array of parameters or a simple scalar
   * @return sfAlyssaDoctrineQuery
   */
  public function addWhereProperyPath($where, $params = array())
  {
    $expr = $this->propertyPathToColumn($where);

    return $this->addWhere($expr, $params);
  }

  /**
   * Adds ProperyPaths to the GROUP BY part of the query.
   * <code>
   * $q->groupBy('Relation.id');
   * </code>
   *
   * @param string $groupby       Query GROUP BY part
   * @return sfAlyssaDoctrineQuery
   */
  public function groupByProperyPath($groupby)
  {
    $columnName = $this->propertyPathToColumn($groupby);

    return $this->groupBy($columnName);
  }

  /**
   * Adds ProperyPaths to the GROUP BY part of the query.
   * <code>
   * $q->addGroupBy('Relation.id');
   * </code>
   *
   * @param string $groupby       Query GROUP BY part
   * @return sfAlyssaDoctrineQuery
   */
  public function addGroupByProperyPath($groupby)
  {
    $columnName = $this->propertyPathToColumn($groupby);

    return $this->addGroupBy($columnName);
  }

  /**
   * Adds ProperyPaths to the ORDER BY part of the query
   *
   * <code>
   * $query->orderBy('Relation.age');
   * $query->orderBy('Relation.birthDate DESC');
   * </code>
   *
   *
   * @param string $orderby      Query ORDER BY part
   * @return sfAlyssaDoctrineQuery
   */
  public function orderByProperyPath($orderby)
  {
    $columnName = $this->propertyPathToColumn($orderby);

    return $this->orderBy($columnName);
  }

  /**
   * Adds ProperyPaths to the ORDER BY part of the query
   *
   * @param string $orderby       Query ORDER BY part
   * @return sfAlyssaDoctrineQuery
   */
  public function addOrderByProperyPath($orderby)
  {
    $columnName = $this->propertyPathToColumn($orderby);

    return $this->addOrderBy($columnName);
  }

}

