<?php
/*
  be.chiro.civi.queryapitools - tools for creating API's based on query results.
  Copyright (C) 2016  Chirojeugd-Vlaanderen vzw

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU Affero General Public License as
  published by the Free Software Foundation, either version 3 of the
  License, or (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU Affero General Public License for more details.

  You should have received a copy of the GNU Affero General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * SelectQuery that works on a SQL query instead of a BAO.
 * 
 * This class extends \Civi\API\SelectQuery so that you can use it on a
 * custom SQL query, and you don't need an existing BAO.
 *
 * @author johanv
 */
class CRM_Queryapitools_SelectQuery extends \Civi\API\SelectQuery {
  /**
   * Constructs a select query.
   * 
   * @param string $query Query that provides all data.
   * @param array $params parameters for the API call.
   * @param string $baoName The API will be called as if this is a get-
   *                        operation on this kind of BAO. This is relevant
   *                        for ACL's, permissions and custom fields,
   *                        I guess.
   * @param array $extraFields Field specs for columns your query will return, 
   *                           in addition to the standard fields provided by the
   *                           BAO.
   */
  public function __construct($query, $params, $baoName, $extraFields) {
    // Let's pretend to create an ordinary query.
    parent::__construct($baoName, $params, FALSE);
    
    // Append field names to field names and spec of $bao.
    foreach ($extraFields as $key => $value) {
      $this->entityFieldNames[] = $key;
      $this->apiFieldSpec[$key] = $value;
    }
   
    // Mess with the query.
    $this->query = \CRM_Utils_SQL_Select::from('(' . $query . ')' . ' ' . self::MAIN_TABLE_ALIAS);

    // Add ACLs first to avoid redundant subclauses
    $this->query->where($this->getAclClause(self::MAIN_TABLE_ALIAS, $baoName));
  }
  
  /**
   * Get acl clause for an entity.
   * 
   * I had to copy this from the base class, because it is a private function.
   * :-P
   *
   * @param string $tableAlias
   * @param string $baoName
   * @param array $stack
   * @return array
   */
  private function getAclClause($tableAlias, $baoName, $stack = array()) {
    if (!$this->checkPermissions) {
      return array();
    }
    // Prevent (most) redundant acl sub clauses if they have already been applied to the main entity.
    // FIXME: Currently this only works 1 level deep, but tracking through multiple joins would increase complexity
    // and just doing it for the first join takes care of most acl clause deduping.
    if (count($stack) === 1 && in_array($stack[0], $this->aclFields)) {
      return array();
    }
    $clauses = $baoName::getSelectWhereClause($tableAlias);
    if (!$stack) {
      // Track field clauses added to the main entity
      $this->aclFields = array_keys($clauses);
    }
    return array_filter($clauses);
  }  
}
